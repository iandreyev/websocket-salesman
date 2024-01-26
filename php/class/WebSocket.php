<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2024 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/* ============================ */

namespace Salesman;

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
	public $iduser, $userChat;

	public $settings;

	public function __construct($iduser = 0) {

		$rootpath = dirname(__DIR__, 2);

		// загружаем конфиг
		$config = yaml_parse_file($rootpath.'/cached/settings.yaml');

		$set = $config['config'];

		$set['protocol'] = !empty($config['ssl']['local_cert']) && !empty($config['ssl']['local_pk']) ? "wss" : "ws";

		if(!empty($config['ssl']['local_cert']) && !empty($config['ssl']['local_pk'])) {

			$set['context'] = [
				"ssl" => $config['ssl']
			];

		}

		// настройки для подключения пользователя
		// и дальнейшей его авторизации через WS
		// обычно не нужны
		if ($this -> iduser > 0) {

			$set['userID']  = $this -> userUID($this -> iduser);
			$set['chatID']  = $config['chat']['chatID'];
			$set['userKEY'] = md5($this -> iduser.$this -> userChat);

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
			"userID"  => $this -> userUID($iduser),
			"chatID"  => $chatid,
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

		return $this -> settings['chat']['chatID'];

	}

}