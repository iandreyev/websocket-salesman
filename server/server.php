<?php
error_reporting(E_ERROR);

use Workerman\Lib\Timer;
use Workerman\Worker;

require_once dirname(__DIR__).'/vendor/autoload.php';

// сюда будем складывать все подключения
$connections = [];

// SSL context.
$context = [
	'ssl' => [
		'local_cert'  => '/your/path/of/server.pem',
		'local_pk'    => '/your/path/of/server.key',
		'verify_peer' => false,
	]
];

// Create a Websocket server
$worker = new Worker('websocket://127.0.0.1:8099');

// 4 processes
$worker -> count = 4;

$worker -> onWorkerStart = static function ($worker) use (&$connections) {

	// пингуем каждые 5 секунд
	$interval = 5;

	Timer ::add($interval, static function () use (&$connections) {

		foreach ($connections as $channelID) {

			foreach ($channelID as $c) {

				// Если ответ не пришел 3 раза, то удаляем соединение из списка
				if ($c -> pingWithoutResponseCount >= 3) {

					printf("Channel: %s, UserID: %s - Unregistered\n", $channelID, $c -> userID);

					unset($connections[$channelID][$c -> userID]);

					// уничтожаем соединение
					$c -> destroy();

				}
				else {

					$c -> send(json_encode(["action" => "Ping"], JSON_THROW_ON_ERROR));

					// увеличиваем счетчик пингов
					$c -> pingWithoutResponseCount++;

				}

			}

		}

	});

};

// Обработка нового подключения
$worker -> onConnect = static function ($connection) {

	// $connection -> send('This message was sent from Backend(index.php), when server was started.');
	echo "New connection\n";

	// Эта функция выполняется при подключении пользователя к WebSocket-серверу
	$connection -> onWebSocketConnect = static function ($connection) use (&$connections) {

		// Добавляем соединение в список
		$connection -> userID    = $_GET['userID'];
		$connection -> channelID = $_GET['channelID'];

		// счетчик безответных пингов
		$connection -> pingWithoutResponseCount = 0;

		// формируем список подключений с разбивкой по channelID
		$connections[$connection -> channelID][$connection -> userID] = $connection;

		// Собираем список всех пользователей
		// todo: не используется
		/*$users = [];
		foreach ($connections[$connection -> channelID] as $c) {
			$users[$c -> channelID][] = $c -> userID;
		}

		// Отправляем пользователю данные авторизации
		$messageData = [
			'action'      => 'Authorized',
			'userID'      => $connection -> userID,
			'channelID'   => $connection -> channelID,
			'users'       => $users,
			"connections" => $connections
		];
		$connection -> send(json_encode($messageData, JSON_THROW_ON_ERROR));*/

	};

};

$worker -> onMessage = static function ($connection, $message) use (&$connections) {

	if( !empty($message) ) {

		$messageData = json_decode($message, true);
		$toUserId    = isset($messageData['toID']) ? (int)$messageData['toID'] : 0;
		$action      = $messageData['action'] ?? '';

		// проверка соединения
		if ($action === 'Pong') {

			// При получении сообщения "Pong", обнуляем счетчик пингов
			$connection -> pingWithoutResponseCount = 0;

		}
		// обычные сообщения
		else {

			printf("Channel: %s, UserID: %s\n", $connection -> channelID, $connection -> userID);
			echo "Message: $message\n\n";

			// Дополняем сообщение данными об отправителе
			$messageData['userID']    = $connection -> userID;
			$messageData['channelID'] = $connection -> channelID;

			if (isset($messageData['message'])) {

				// Преобразуем специальные символы в HTML-сущности в тексте сообщения
				$messageData['message'] = htmlspecialchars($messageData['message']);
				// Заменяем текст заключенный в фигурные скобки на жирный
				$messageData['message'] = preg_replace('/\{(.*)\}/u', '<b>\\1</b>', $messageData['message']);

				// общая отправка в канал
				if ($toUserId === 0) {

					foreach ($connections[$connection -> channelID] as $c) {
						$c -> send(json_encode($messageData, JSON_THROW_ON_ERROR));
					}

				}
				elseif (isset($connections[$connection -> channelID][$toUserId])) {

					// Отправляем приватное сообщение указанному пользователю
					$connections[$connection -> channelID][$toUserId] -> send(json_encode($messageData, JSON_THROW_ON_ERROR));

				}
				// если не существует, то отправляем ошибку отправителю
				else {

					// и отправителю
					$messageData['error']   = true;
					$messageData['message'] = 'Не найден получатель. Возможно он оффлайн.';
					$connection -> send(json_encode($messageData, JSON_THROW_ON_ERROR));

				}

			}

		}

	}
	/*else{

		// При получении сообщения "Pong", обнуляем счетчик пингов
		$connection -> pingWithoutResponseCount = 0;

	}*/

};

// Emitted when connection closed
$worker -> onClose = static function ($connection) {

	echo "Connection closed\n";
	echo json_encode($connection)."\n";

	// Эта функция выполняется при закрытии соединения
	if (!isset($connections[$connection -> channelID])) {
		return;
	}

	// Удаляем соединение из списка
	unset($connections[$connection -> channelID][$connection -> userID]);

	// Оповещаем всех пользователей о выходе участника из чата
	$messageData = [
		'action'    => 'Disconnected',
		'userID'    => $connection -> userID,
		'channelID' => $connection -> channelID
	];
	$message     = json_encode($messageData);

	foreach ($connections[$connection -> channelID] as $c) {
		$c -> send($message);
	}

};

// Run worker
Worker ::runAll();