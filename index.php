<?php
error_reporting(E_ERROR);

use Workerman\Lib\Timer;
use Workerman\Worker;

require_once __DIR__.'/vendor/autoload.php';

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
$worker = new Worker('websocket://127.0.0.1:8090');

// 4 processes
$worker -> count = 4;

$worker -> onWorkerStart = static function ($worker) use (&$connections) {

	// пингуем каждые 5 секунд
	$interval = 5;

	Timer ::add($interval, static function () use (&$connections) {

		foreach ($connections as $c) {

			// Если ответ не пришел 3 раза, то удаляем соединение из списка
			// и оповещаем всех участников об "отвалившемся" пользователе
			if ($c -> pingWithoutResponseCount >= 3) {

				unset($connections[$c -> id]);

				$messageData = [
					'action'    => 'ConnectionLost',
					'userId'    => $c -> id,
					'userName'  => $c -> userName,
					'gender'    => $c -> gender,
					'userColor' => $c -> userColor
				];
				$message     = json_encode($messageData);

				// уничтожаем соединение
				$c -> destroy();
				$c -> send($message);

			}
			else {

				$c -> send('{"action":"Ping"}');
				// увеличиваем счетчик пингов
				$c -> pingWithoutResponseCount++;

			}

		}

	});

};

// Emitted when new connection come
$worker -> onConnect = static function ($connection) {

	$connection -> send('This message was sent from Backend(index.php), when server was started.');
	echo "New connection\n";
	echo json_encode($connection)."\n";

	// Эта функция выполняется при подключении пользователя к WebSocket-серверу
	$connection -> onWebSocketConnect = static function ($connection) use (&$connections) {

		echo "New WebSocket connection\n";
		echo json_encode($connection)."\n";
		echo json_encode($_GET)."\n";

		$gender    = 0;
		$userColor = "#000000";

		// Достаём имя пользователя, если оно было указано
		if (isset($_GET['userName'])) {
			$originalUserName = preg_replace('/[^a-zA-Zа-яА-ЯёЁ0-9\-\_ ]/u', '', trim($_GET['userName']));
		}
		else {
			$originalUserName = 'Инкогнито';
		}

		// Половая принадлежность, если указана
		// 0 - Неизвестный пол
		// 1 - М
		// 2 - Ж
		if (isset($_GET['gender'])) {
			$gender = (int)$_GET['gender'];
		}

		if (
			!in_array($gender, [
				0,
				1,
				2
			])
		) {
			$gender = 0;
		}

		// Цвет пользователя
		if (isset($_GET['userColor'])) {
			$userColor = $_GET['userColor'];
		}

		// Проверяем уникальность имени в чате
		$userName = $originalUserName;

		$num = 2;
		do {

			$duplicate = false;

			foreach ((array)$connections as $c) {

				if ($c -> userName === $userName) {
					$userName = "$originalUserName ($num)";
					$num++;
					$duplicate = true;
					break;
				}

			}

		}
		while ($duplicate);

		// Добавляем соединение в список
		$connection -> userName  = $userName;
		$connection -> gender    = $gender;
		$connection -> userColor = $userColor;

		// счетчик безответных пингов
		$connection -> pingWithoutResponseCount = 0;

		$connections[$connection -> id] = $connection;

		// Собираем список всех пользователей
		$users = [];
		foreach ($connections as $c) {
			$users[] = [
				'userId'    => $c -> id,
				'userName'  => $c -> userName,
				'gender'    => $c -> gender,
				'userColor' => $c -> userColor
			];
		}

		// Отправляем пользователю данные авторизации
		$messageData = [
			'action'    => 'Authorized',
			'userId'    => $connection -> id,
			'userName'  => $connection -> userName,
			'gender'    => $connection -> gender,
			'userColor' => $connection -> userColor,
			'users'     => $users
		];
		$connection -> send(json_encode($messageData));

		// Оповещаем всех пользователей о новом участнике в чате
		$messageData = [
			'action'    => 'Connected',
			'userId'    => $connection -> id,
			'userName'  => $connection -> userName,
			'gender'    => $connection -> gender,
			'userColor' => $connection -> userColor
		];
		$message     = json_encode($messageData);

		foreach ($connections as $c) {
			$c -> send($message);
		}

	};

};

$worker -> onMessage = static function ($connection, $message) use (&$connections) {

	$messageData = json_decode($message, true);
	$toUserId    = isset($messageData['toUserId']) ? (int)$messageData['toUserId'] : 0;
	$action      = $messageData['action'] ?? '';

	// проверка соединения
	if ($action === 'Pong') {

		// При получении сообщения "Pong", обнуляем счетчик пингов
		$connection -> pingWithoutResponseCount = 0;

	}
	// обычные сообщения
	else {

		// Дополняем сообщение данными об отправителе
		$messageData['userId']    = $connection -> id;
		$messageData['userName']  = $connection -> userName;
		$messageData['gender']    = $connection -> gender;
		$messageData['userColor'] = $connection -> userColor;

		if( isset($messageData['text']) ) {

			// Преобразуем специальные символы в HTML-сущности в тексте сообщения
			$messageData['text'] = htmlspecialchars($messageData['text']);
			// Заменяем текст заключенный в фигурные скобки на жирный
			$messageData['text'] = preg_replace('/\{(.*)\}/u', '<b>\\1</b>', $messageData['text']);

		}

		if ($toUserId === 0) {

			// Отправляем сообщение всем пользователям
			$messageData['action'] = 'PublicMessage';

			foreach ($connections as $c) {
				$c -> send(json_encode($messageData));
			}

		}
		else {

			$messageData['action'] = 'PrivateMessage';

			if (isset($connections[$toUserId])) {

				// Отправляем приватное сообщение указанному пользователю
				$connections[$toUserId] -> send(json_encode($messageData));

			}
			else {
				$messageData['text'] = 'Не удалось отправить сообщение выбранному пользователю';
			}

			// и отправителю
			$connection -> send(json_encode($messageData));

		}

	}

};

// Emitted when connection closed
$worker -> onClose = static function ($connection) {

	echo "Connection closed\n";
	echo json_encode($connection)."\n";

	// Эта функция выполняется при закрытии соединения
	if (!isset($connections[$connection -> id])) {
		return;
	}

	// Удаляем соединение из списка
	unset($connections[$connection -> id]);

	// Оповещаем всех пользователей о выходе участника из чата
	$messageData = [
		'action'    => 'Disconnected',
		'userId'    => $connection -> id,
		'userName'  => $connection -> userName,
		'gender'    => $connection -> gender,
		'userColor' => $connection -> userColor
	];
	$message     = json_encode($messageData);

	foreach ($connections as $c) {
		$c -> send($message);
	}

};

// Run worker
Worker ::runAll();