<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

namespace Salesman;

use Exception;

/**
 * Class Comet
 *
 * @package Salesman
 */
class WebSocket {

	/**
	 * Различные параметры, в основном из GLOBALS
	 *
	 * @var mixed
	 */
	public $db, $identity, $iduser, $userName;

	public $settings;

	public function __construct($iduser = 0) {

		$rootpath = dirname(__DIR__, 2);

		require_once $rootpath."/vendor/autoload.php";

		$this -> identity = $GLOBALS['identity'];
		$this -> db       = $GLOBALS['db'];
		$this -> iduser   = $iduser;

		$set = json_decode(file_get_contents($rootpath."/cash/comet.config.json"), true);

		$set['server'] = $set['host'].":".$set['port'];

		// настройки для подключения пользователя
		// и дальнейшей его авторизации через WS
		// обычно не нужны
		if ($this -> iduser > 0) {

			$set['user_id']  = $this -> userUID($this -> iduser);
			$set['user_key'] = md5($this -> identity.$this -> iduser.$this -> userName);

		}

		$this -> settings = $set;

	}

	/**
	 * Возвращает параметры подключения к серверу
	 * @return mixed
	 */
	public function getSettings(): array {

		return $this -> settings;

	}

	/**
	 * Отправка сообщения пользователю
	 *
	 * @param int $iduser
	 * @param string $chatid
	 * @param string|array $text
	 * @return array
	 */
	public function sendMessage(int $iduser = 0, string $chatid = '', $text = ''): array {

		$header = [
			"accept" => "application/json",
		];
		$params = [
			"user_id" => $this -> userUID($iduser),
			"chat_id" => $chatid,
			"message" => $text
		];

		$req = SendRequestCurl("https://".$this -> settings['server']."/message", $params, $header);

		return [
			"url"    => "https://".$this -> settings['server']."/message",
			"code"   => $req -> info['http_code'],
			"data"   => json_decode($req -> response, true),
			//"info" => $req -> info,
			"error"  => $req -> error,
			"params" => $params
		];

	}

	/**
	 * Метод для получения списка пользователей по чатам (http/https)
	 * @return array
	 */
	public function getChats(): array {

		$header = [
			"accept" => "application/json",
		];
		$params = [];

		$req = SendRequestCurl("https://".$this -> settings['server']."/chats", $params, $header, 'form', 'GET');

		return [
			"code"   => $req -> info['http_code'],
			"data"   => json_decode($req -> response, true),
			"error"  => $req -> error,
			"params" => $params
		];

	}

	/**
	 * попытка составить UID из id пользователя и host сервера, выраженный целыми числами
	 * @param $iduser
	 * @return int
	 */
	public function userUID($iduser): int {

		$name    = str_split($_SERVER["HTTP_HOST"]);
		$alfabet = array_flip([
			'a',
			'b',
			'c',
			'd',
			'e',
			'f',
			'g',
			'h',
			'i',
			'j',
			'k',
			'l',
			'm',
			'n',
			'o',
			'p',
			'q',
			'r',
			's',
			't',
			'u',
			'v',
			'w',
			'x',
			'y',
			'z'
		]);

		$uid = $iduser;

		foreach ($name as $a) {

			$uid .= $alfabet[ $a ];

		}

		return (int)substr($uid, 0, 9);

	}

	/**
	 * Генератор названия чата
	 * @return string|string[]
	 */
	public function chatID() {

		return str_replace([
			".",
			"-"
		], "", $_SERVER["SERVER_NAME"]);

	}

}