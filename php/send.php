<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

/**
 * Пример отправки сообщения пользователю через ws-сервер
 */

use Salesman\WebSocket;

$rootpath = dirname(__DIR__);

require_once $rootpath.'/vendor/autoload.php';

$user = 25;

$message = json_decode(file_get_contents('php://input'), true)['message'];

$websocket = new WebSocket($user);
$res       = $websocket -> sendMessage($user, $websocket -> settings['chatID'], [
	"event"   => "message",
	"payload" => $message,
	//"UserID"  => $websocket -> settings['userID'],
	//"ChannelID" => $websocket -> settings['chatID'],
]);

print json_encode(["result" => $res['result'], "error" => $res['error']]);