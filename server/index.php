# /opt/php74/bin/php
<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

error_reporting(E_ERROR);

use Salesman\WebSocket;
use Workerman\Lib\Timer;
use Workerman\Worker;

$rootpath = dirname(__DIR__);

//require_once $rootpath.'/php/class/WebSocket.php';
require_once $rootpath.'/vendor/autoload.php';

// загружаем конфиг
$websocket = new WebSocket();
$config    = $websocket -> settings;

// сюда будем складывать все подключения
$connections = [];

// SSL context, если настроен
$context = [];

if (!empty($config['context'])) {
	$context = $config['context'];
}

// адрес http сервера
$protocol = $config['protocol'] === 'wss' ? 'https' : 'http';
$httpurl  = $protocol."://".$config['host'].":".$config['httpport'];

// Channel server.
$channel_server = new Channel\Server('0.0.0.0');

// Create a Websocket server
$ws_worker = new Worker("websocket://".$config['host'].":".$config['wsport'], $context);
$ws_worker -> name  = 'pusher';
$ws_worker -> count = 1;

$ws_worker -> onWorkerStart = static function ($ws_worker) use (&$connections, $httpurl, $config) {

	$http_worker              = new Worker($httpurl);
	$http_worker -> name      = 'publisher';
	$http_worker -> onWorkerStart = static function () use (&$connections, $httpurl, $config) {
		echo "New http worker\n";
		echo json_encode($config)."\n";
	};
	$http_worker -> onMessage = static function ($connection, $data) use (&$connections) {

		$data = json_decode($data);
		echo json_encode($data)."\n";

	};
	$http_worker -> listen();

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

					$c -> send(json_encode(["action" => "Ping"]));

					// увеличиваем счетчик пингов
					$c -> pingWithoutResponseCount++;

				}

			}

		}

	});

};

// Обработка нового подключения
$ws_worker -> onConnect = static function ($connection) {

	// $connection -> send('This message was sent from Backend(index.php), when server was started.');
	// echo "New connection\n";

	// Эта функция выполняется при подключении пользователя к WebSocket-серверу
	$connection -> onWebSocketConnect = static function ($connection) use (&$connections) {

		// Добавляем соединение в список
		$connection -> userID    = $_GET['userID'];
		$connection -> channelID = $_GET['channelID'];

		echo "New WebSocket connection\n";
		//echo json_encode($connection)."\n";
		echo json_encode($_GET)."\n";

		// счетчик безответных пингов
		$connection -> pingWithoutResponseCount = 0;

		// формируем список подключений с разбивкой по channelID
		$connections[$connection -> channelID][$connection -> userID] = $connection;

		$messageData = [
			'action'    => 'Authorized',
			'userID'    => $connection -> userID,
			'channelID' => $connection -> channelID,
		];
		$connection -> send(json_encode($messageData));

	};

};

$ws_worker -> onMessage = static function ($connection, $message) use (&$connections) {

	// Publish broadcast event to all worker processes.
	Channel\Client::publish('broadcast', $message);

	if (!empty($message)) {

		$messageData = json_decode($message, true);
		$toUserId    = isset($messageData['toID']) ? (int)$messageData['toID'] : 0;
		$action      = $messageData['action'] ?? '';

		// проверка соединения
		if ($action === 'Pong') {

			// При получении сообщения "Pong", обнуляем счетчик пингов
			$connection -> pingWithoutResponseCount = 0;

		} // обычные сообщения
		else {

			printf("Channel: %s, UserID: %s\n", $connection -> channelID, $connection -> userID);
			echo "Message: $message\n";

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

				} // если не существует, то отправляем ошибку отправителю
				else {

					// и отправителю
					$messageData['error']   = true;
					$messageData['message'] = 'Не найден получатель. Возможно он оффлайн.';
					$connection -> send(json_encode($messageData, JSON_THROW_ON_ERROR));

				}

			}

		}

	}
	else {

		// При получении сообщения "Pong", обнуляем счетчик пингов
		$connection -> pingWithoutResponseCount = 0;

	}

};

// Emitted when connection closed
$ws_worker -> onClose = static function ($connection) {

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