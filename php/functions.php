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

/**
 * Замена функции json_encode с поддержкой кириллицы
 *
 * @param $str
 *
 * @return array|false|string|string[]
 * @category Core
 * @package  Func
 */
function json_encode_cyr($str) {

    $arr_replace_utf = [
        '\u0410',
        '\u0430',
        '\u0411',
        '\u0431',
        '\u0412',
        '\u0432',
        '\u0413',
        '\u0433',
        '\u0414',
        '\u0434',
        '\u0415',
        '\u0435',
        '\u0401',
        '\u0451',
        '\u0416',
        '\u0436',
        '\u0417',
        '\u0437',
        '\u0418',
        '\u0438',
        '\u0419',
        '\u0439',
        '\u041a',
        '\u043a',
        '\u041b',
        '\u043b',
        '\u041c',
        '\u043c',
        '\u041d',
        '\u043d',
        '\u041e',
        '\u043e',
        '\u041f',
        '\u043f',
        '\u0420',
        '\u0440',
        '\u0421',
        '\u0441',
        '\u0422',
        '\u0442',
        '\u0423',
        '\u0443',
        '\u0424',
        '\u0444',
        '\u0425',
        '\u0445',
        '\u0426',
        '\u0446',
        '\u0427',
        '\u0447',
        '\u0428',
        '\u0448',
        '\u0429',
        '\u0449',
        '\u042a',
        '\u044a',
        '\u042b',
        '\u044b',
        '\u042c',
        '\u044c',
        '\u042d',
        '\u044d',
        '\u042e',
        '\u044e',
        '\u042f',
        '\u044f'
    ];
    $arr_replace_cyr = [
        'А',
        'а',
        'Б',
        'б',
        'В',
        'в',
        'Г',
        'г',
        'Д',
        'д',
        'Е',
        'е',
        'Ё',
        'ё',
        'Ж',
        'ж',
        'З',
        'з',
        'И',
        'и',
        'Й',
        'й',
        'К',
        'к',
        'Л',
        'л',
        'М',
        'м',
        'Н',
        'н',
        'О',
        'о',
        'П',
        'п',
        'Р',
        'р',
        'С',
        'с',
        'Т',
        'т',
        'У',
        'у',
        'Ф',
        'ф',
        'Х',
        'х',
        'Ц',
        'ц',
        'Ч',
        'ч',
        'Ш',
        'ш',
        'Щ',
        'щ',
        'Ъ',
        'ъ',
        'Ы',
        'ы',
        'Ь',
        'ь',
        'Э',
        'э',
        'Ю',
        'ю',
        'Я',
        'я'
    ];
    $str1            = json_encode($str);

    return str_replace($arr_replace_utf, $arr_replace_cyr, $str1);

}