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

require_once $rootpath.'/vendor/autoload.php';

$websocket = new WebSocket(25);
$config    = $websocket -> settings;

$server = $config['protocol']."://".$config['host'].":".$config['wsport']."/server/?userID=".$config['userID']."&channelID=".$config['chatID'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Пример работы WebSocket</title>
</head>
<body>
<div>

</div>
<script>

	let count = 0
	let ws
	
	connect();

	function connect(){

		ws = new WebSocket('<?=$server?>')

		ws.onopen = function () {
			console.log("Connected")
			ws.send('Hello Admin!')
		}

		ws.onclose = function (event) {

			if (event.wasClean) {
				console.log('Соединение закрыто чисто')
			}
			else if (event.code === 1006) {
				console.log('Соединение закрыто как 1006')
			}
			else {
				console.log('Обрыв соединения')
			}

			if(count < 4) {

				setTimeout(function () {

					connect();
					count++;
					console.log("ReConnecting: "+ count)

				}, 1000);

			}


		}

		ws.onmessage = function (event) {
			console.log(event.data)
		}

		ws.onerror = function (error) {
			console.log("Ошибка: " + error.message)
		}

	}

	/*
	const func = () => {
		//send on server
		ws.send('Hello Admin!')
	};
	setTimeout(func, 2 * 1000)
	*/

</script>
</body>
</html>