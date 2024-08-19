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

$user = 25;

$websocket = new WebSocket($user);
$config    = $websocket -> settings;

$server = $config['protocol']."://".$config['host'].":".$config['wsport']."/?userID=".$config['userID']."&channelID=".$config['chatID'];

print json_encode([
	"url"       => $server,
	"userID"    => $config['userID'],
	"channelID" => $config['chatID']
]);
exit();