<?php
namespace ProcessWire;

class AppApiHelper {
	public static function preflight() {
		return;
	}

	public static function noEndPoint() {
		throw new BadRequestException('No endpoint defined');
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

			if (!method_exists(wire('sanitizer'), $sanitizer) && !method_exists(wire('sanitizer'), '___' . $sanitizer)) {
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
		$headers = [];

		foreach (array_keys($_SERVER) as $skey) {
			$headername = str_replace(' ', '-', strtoupper($skey));
			if (substr($headername, 0, 9) === 'REDIRECT_') {
				$headername = substr($skey, 9);
			}

			if (substr($headername, 0, 5) === 'HTTP_') {
				$headername = substr($headername, 5);
			}

			$headers[$headername] = $_SERVER[$skey];
		}

		if (function_exists('apache_request_headers')) {
			if (apache_request_headers()) {
				$headers = array_merge($headers, array_change_key_case(apache_request_headers(), CASE_UPPER));
			}
		}

		return $headers;
	}
}
