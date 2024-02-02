#! /opt/php74/bin/php
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
//$protocol = $config['protocol'] === 'wss' ? 'https' : 'http';
$protocol = "tcp";
$httpurl  = $protocol."://".$config['host'].":".$config['httpport'];

// Channel server.
$channel_server = new Channel\Server();

// Create a Websocket server
$ws_worker          = new Worker("websocket://".$config['host'].":".$config['wsport'], $context);
$ws_worker -> name  = 'pusher';
$ws_worker -> count = 1;

$ws_worker -> onWorkerStart = static function ($ws_worker) use (&$connections) {

	global $connections;

	// Channel client.
	Channel\Client ::connect();

	$event_name = 'message';

	Channel\Client ::on($event_name, static function ($event_data) use ($ws_worker, &$connections) {

		$userID    = $event_data['userID'];
		$channelID = $event_data['channelID'];
		$message   = is_array($event_data['payload']) ? json_encode($event_data['payload']) : $event_data['payload'];

		if (!isset($ws_worker -> connections[$channelID][$userID])) {
			echo "connection not exists\n";
			return;
		}

		foreach ($ws_worker -> connections[$channelID][$userID] as $c) {

			$to_connection = $ws_worker -> connections[$channelID][$userID][$c -> id];
			$to_connection -> send($message);

		}

		//echo "Sended message: ".$message."\n";

		printf("to userID: %s, channelID: %s sended message: %s\n", $userID, $channelID, $message);

	});

	// пингуем каждые 5 секунд
	$interval = 5;

	Timer ::add($interval, static function () use (&$connections) {

		foreach ($connections as $channelID) {

			// отправляем пинг
			foreach ($channelID as $userID => $c) {

				// Если ответ не пришел 3 раза, то удаляем соединение из списка
				if ($c -> pingWithoutResponseCount >= 3) {

					printf("Channel: %s, UserID: %s - Unregistered\n", $channelID, $c -> userID);

					unset($connections[$channelID][$userID][$c -> id]);

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

	// Эта функция выполняется при подключении пользователя к WebSocket-серверу
	global $ws_worker;

	$connection -> onWebSocketConnect = static function ($connection) use ($ws_worker, &$connections) {

		// Добавляем соединение в список
		$connection -> userID    = $_GET['userID'];
		$connection -> channelID = $_GET['channelID'];

		echo "New WebSocket connection\n";

		// счетчик безответных пингов
		$connection -> pingWithoutResponseCount = 0;

		// формируем список подключений с разбивкой по channelID
		$connections[$connection -> channelID][$connection -> userID][$connection -> id]              = $connection;
		$ws_worker -> connections[$connection -> channelID][$connection -> userID][$connection -> id] = $connection;

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
	// Channel\Client ::publish('broadcast', $message);

	print $message."\n";

	if (!empty($message)) {

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
			echo "Message: $message\n";

			// Дополняем сообщение данными об отправителе
			$messageData['userID']    = $connection -> userID;
			$messageData['channelID'] = $connection -> channelID;

			if( !empty($messageData['payload']) ){
				$messageData['message'] = is_array($messageData['payload']) ? json_encode($messageData['payload']) : $messageData['payload'];
			}

			if (!empty($messageData['message'])) {

				// общая отправка в канал
				if ($toUserId === 0) {

					foreach ($connections[$connection -> channelID] as $c) {
						$c -> send(json_encode($messageData));
					}

				}
				// Отправляем приватное сообщение указанному пользователю во все его соединения
				elseif (isset($connections[$connection -> channelID][$toUserId])) {

					foreach ($connections[$connection -> channelID][$toUserId] as $c) {
						$c -> send(json_encode($messageData));
					}

				}
				// если не существует, то отправляем ошибку отправителю
				else {

					// и отправителю
					$messageData['error']   = true;
					$messageData['message'] = 'Не найден получатель. Возможно он оффлайн.';
					$connection -> send(json_encode($messageData));

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
	//echo json_encode($connection)."\n";

	// Эта функция выполняется при закрытии соединения
	if (!isset($connections[$connection -> channelID])) {
		return;
	}

	// Удаляем соединение из списка
	//unset($connections[$connection -> channelID][$connection -> userID]);

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

	//unset($connection);

};

// Http server.
$http_worker                  = new Worker($httpurl);
$http_worker -> name          = 'publisher';
$http_worker -> onWorkerStart = static function () {
	Channel\Client ::connect();
};
$http_worker -> onMessage     = static function ($connection, $data) {

	$connection -> send('ok');

	// print $data."\n";

	$data = json_decode($data, true);

	if (!empty($data['userID']) && !empty($data['chatID'])) {

		Channel\Client ::publish('message', [
			'userID'    => $data['userID'],
			'channelID' => $data['chatID'],
			'payload'   => $data['payload']
		]);

	}

};

// Run worker
Worker ::runAll();