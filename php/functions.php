<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2018 Vladislav Andreev   */
/*       Salesman Project       */
/*        www.isaler.ru         */
/* ============================ */

/**
 * Отправка данных через cURL
 *
 * @param            $url
 * @param null $postdata - массив отправляемых данных
 * @param string|array|null $header - массив заголовков
 * @param string $format - формат отправки данных
 *                             - json - отправлять в формате json (добавляет заголовок)
 *                             - form - отправлять как форму (Content-type: application/x-www-form-urlencoded;)
 *                             - иначе формат указать вручную в массиве заголовков
 * @param string $method - метод отправки (POST - по умолчанию, GET, PUT, PATCH)
 *                             - если GET, то данные обрабатываются http_build_query
 *
 * @return stdClass
 *                    - response
 *                    - info
 *                    - error
 *                    - headers
 * @category Core
 * @package  Func
 */
function SendRequestCurl($url, $postdata = NULL, array $header = NULL, string $format = 'json', string $method = 'POST'): stdClass {

	$result = new stdClass();

	$headers = [];
	$format  = strtoupper($format);

	if ($format == 'JSON') {
		$headers[] = "Content-Type: application/json";
	}
	elseif ($format == 'FORM') {
		$headers[] = "Content-Type: application/x-www-form-urlencoded";
	}

	if (!empty($header)) {

		foreach ($header as $key => $head) {
			$headers[] = $key.": ".$head;
		}

	}

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_HEADER, 0);

	if (!empty($headers)) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	if ($method == 'POST') {

		$POST = ( is_array($postdata) ) ? ( $format == 'JSON' ? json_encode_cyr($postdata) : http_build_query($postdata) ) : $postdata;

		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);

	}
	if ($method == 'GET') {

		$url .= !empty($postdata) ? '?'.http_build_query($postdata) : "";

	}
	if ($method == 'PUT') {

		$POST = is_array($postdata) ? ( $format == 'JSON' ? json_encode($postdata) : http_build_query($postdata) ) : $postdata;

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);

		//print $POST;

	}
	if ($method == 'PATCH') {

		$POST = is_array($postdata) ? ( $format == 'JSON' ? json_encode($postdata) : http_build_query($postdata) ) : $postdata;

		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $POST);

		//print $POST;

	}

	curl_setopt($ch, CURLOPT_TIMEOUT, 100);
	curl_setopt($ch, CURLOPT_URL, $url);

	$result -> response = curl_exec($ch);
	$result -> info     = curl_getinfo($ch);
	$result -> error    = curl_error($ch);
	$result -> headers  = $headers;

	return $result;

}