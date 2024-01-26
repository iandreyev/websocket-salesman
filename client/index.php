<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

use Salesman\WebSocket;

$rootpath = dirname(__DIR__);

require_once $rootpath.'/php/class/WebSocket.php';
require_once $rootpath.'/vendor/autoload.php';

$websocket = new WebSocket(25);
$config = $websocket ->settings;
$server = $config['protocol']."://".$config['host'].":".$config['port']."/server/?userID=".$config['userID']."&channelID=".$config['chatID'];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Пример работы WebSocket</title>
</head>
<body>
<script type="text/javascript">
	
	let ws = new WebSocket('<?=$server?>');
	
	ws.addEventListener('message', (event) => {
		console.info('Frontend got message: ' + event.data); // get from server
	})
	
	ws.onopen = function () {
		console.log("Connected");
	};
	ws.onclose = function (event) {
		if (event.wasClean) {
			console.log('Соединение закрыто чисто');
		}
		else if (event.code === 1006) {
			console.log('Соединение закрыто как 1006');
		}
		else {
			console.log('Обрыв соединения');
		}
	};
	
	ws.onmessage = function (event) {
		console.log("Получены данные " + event.data);
		ws.close();
	};
	
	ws.onerror = function (error) {
		console.log("Ошибка " + error.message);
	};
	
	const func = () => {
		ws.send('Hello Admin!'); //send on server
	};
	setTimeout(func, 2 * 1000);

</script>
</body>
</html>