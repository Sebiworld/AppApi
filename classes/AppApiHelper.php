<?php

namespace ProcessWire;

class AppApiHelper {
	public static function preflight() {
		return;
	}

	public static function noEndPoint() {
		return 'no endpoint defined';
	}

	public static function checkAndSanitizeRequiredParameters($data, $params) {
		foreach ($params as $param) {
			// Split param: Format is name|sanitizer
			$name = explode('|', $param)[0];

			// Check if Param exists
			if (!isset($data->$name)) {
				throw new AppApiException("Required parameter: '$param' missing!", 400);
			}

			$sanitizer = explode('|', $param);

			// Sanitize Data
			// If no sanitizer is defined, use the text sanitizer as default
			if (!isset($sanitizer[1])) {
				$sanitizer = 'text';
			} else {
				$sanitizer = $sanitizer[1];
			}

			if (!method_exists(wire('sanitizer'), $sanitizer)) {
				throw new AppApiException("Sanitizer: '$sanitizer' is no valid sanitizer", 400);
			}

			$data->$name = wire('sanitizer')->$sanitizer($data->$name);
		}

		return $data;
	}

	public static function baseUrl() {
		// $site->urls->httpRoot
		return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://$_SERVER[HTTP_HOST]/";
	}

	public static function generateRandomString($length = 10, $urlAble = true) {
		if (!$urlAble) {
			if (function_exists('random_bytes')) {
				return base64_encode(random_bytes($length));
			}
			return base64_encode(mcrypt_create_iv($length));
		}

		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';

		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}

		return $randomString;
	}

	public static function getRequestHeaders() {
		if (function_exists('apache_request_headers')) {
			if ($headers = apache_request_headers()) {
				return array_change_key_case($headers, CASE_UPPER);
			}
		}

		$headers = [];
		foreach (array_keys($_SERVER) as $skey) {
			if (substr($skey, 0, 5) == 'HTTP_') {
				$headername = str_replace(' ', '-', ucwords(strtoupper(str_replace('_', '', substr($skey, 5)))));
				$headers[$headername] = $_SERVER[$skey];
			}
		}

		return $headers;
	}
}
