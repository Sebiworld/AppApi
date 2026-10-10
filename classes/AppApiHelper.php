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
				throw new AppApiException("Required parameter: '$param' missing!", 400, ['errorcode' => 'missing_required_parameter']);
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
				throw new AppApiException("Sanitizer: '$sanitizer' is no valid sanitizer", 400, ['errorcode' => 'invalid_sanitizer']);
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
			$randomString .= $characters[random_int(0, $charactersLength - 1)];
		}

		return $randomString;
	}

	/**
	 * Splits the "allowed origins" module setting into a list of trimmed, non-empty entries.
	 *
	 * @param string|array|null $value One origin per line, or an array of origins
	 *
	 * @return string[]
	 */
	public static function parseAllowedOrigins($value) {
		if (is_string($value)) {
			$value = preg_split('/[\r\n]+/', $value);
		}
		if (!is_array($value)) {
			return [];
		}

		$origins = [];
		foreach ($value as $entry) {
			if (!is_string($entry)) {
				continue;
			}
			$entry = rtrim(trim($entry), '/');
			if ($entry !== '') {
				$origins[] = $entry;
			}
		}

		return $origins;
	}

	/**
	 * Checks whether an Origin header value matches one of the allowed origins.
	 *
	 * An entry consists of scheme, host and optional port (e.g. "https://example.com:8080").
	 * Scheme and port must match exactly, the host is compared case-insensitively.
	 * A "*" in the host matches one or more characters except a dot, so it never spans more than
	 * one host label (e.g. "https://preview-*.example.com").
	 * An empty list allows every origin.
	 *
	 * @param string $origin Value of the Origin request header
	 * @param string[] $allowedOrigins
	 *
	 * @return boolean
	 */
	public static function isOriginAllowed($origin, array $allowedOrigins) {
		if (empty($allowedOrigins)) {
			return true;
		}

		$originParts = self::splitOrigin($origin);
		if ($originParts === null) {
			return false;
		}

		foreach ($allowedOrigins as $allowedOrigin) {
			$allowedParts = self::splitOrigin($allowedOrigin, true);
			if ($allowedParts === null) {
				continue;
			}

			if ($allowedParts['scheme'] !== $originParts['scheme'] || $allowedParts['port'] !== $originParts['port']) {
				continue;
			}

			$hostPattern = str_replace('\\*', '[^.]+', preg_quote($allowedParts['host'], '/'));
			if (preg_match('/^' . $hostPattern . '$/i', $originParts['host'])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Splits an origin ("scheme://host[:port]") into its parts. Returns null if the value is not a valid origin.
	 *
	 * @param string $origin
	 * @param boolean $allowWildcard Allow "*" in the host part
	 *
	 * @return array|null
	 */
	protected static function splitOrigin($origin, $allowWildcard = false) {
		if (!is_string($origin)) {
			return null;
		}

		$hostChars = $allowWildcard ? 'a-z0-9.\\-_*' : 'a-z0-9.\\-_';
		if (!preg_match('/^([a-z][a-z0-9+.\\-]*):\\/\\/([' . $hostChars . ']+|\\[[0-9a-f:.]+\\])(?::([0-9]+))?\\/?$/i', trim($origin), $matches)) {
			return null;
		}

		return [
			'scheme' => strtolower($matches[1]),
			'host' => $matches[2],
			'port' => isset($matches[3]) ? $matches[3] : ''
		];
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
