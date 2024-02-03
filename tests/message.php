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

$message = [
	"event"   => "message",
	"payload" => "Это тестовое сообщение",
	//"payload" => "Ты это тоже видишь?",
];

$websocket = new WebSocket(25);
$res       = $websocket -> sendMessage(25, $websocket -> settings['chatID'], $message);

print_r($res);