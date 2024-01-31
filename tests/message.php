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

//require_once $rootpath.'/php/class/WebSocket.php';
require_once $rootpath.'/php/functions.php';
require_once $rootpath.'/vendor/autoload.php';

$websocket = new WebSocket(25);
$res = $websocket -> sendMessage(25, $websocket ->settings['chatID'], 'ЧТО ЗА НАХРЕН?');
//$res = $websocket -> sendHTTPMessage(25, $websocket ->settings['chatID'], 'test');

print_r($res);