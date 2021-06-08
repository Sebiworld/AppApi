<?php

namespace ProcessWire;

use Throwable;

class AppApiException extends WireException {
	private $additionals = [];

	public function __construct(string $message, int $code = 500, array $additionals = [], Throwable $previous = null) {
		$this->additionals = array_merge($this->additionals, $additionals);
		parent::__construct($message, $code, $previous);
	}

	public function __toString() {
		return "{$this->message}";
	}

	public function getAdditionals() {
		return $this->additionals;
	}
}

class BadRequestException extends AppApiException {
	public function __construct(string $message = '', int $code = 400, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('Bad Request.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'bad_request_exception';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}
class UnauthorizedException extends AppApiException {
	public function __construct(string $message = '', int $code = 401, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('Unauthorized.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'unauthorized_exception';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}
class NotFoundException extends AppApiException {
	public function __construct(string $message = '', int $code = 404, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('Not Found.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'not_found_exception';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}
class MethodNotAllowedException extends AppApiException {
	public function __construct(string $message = '', int $code = 405, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('Method Not Allowed.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'method_not_allowed_exception';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}
class ForbiddenException extends AppApiException {
	public function __construct(string $message = '', int $code = 403, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('Forbidden.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'forbidden_exception';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}

class InternalServererrorException extends AppApiException {
	public function __construct(string $message = '', int $code = 500, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('Internal server error.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'internal_server_error';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}

class ApplicationException extends AppApiException {
	public function __construct(string $message = '', int $code = 500, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('An application exception occurred.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'general_application_exception';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}

class ApikeyException extends AppApiException {
	public function __construct(string $message = '', int $code = 500, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('An apikey exception occurred.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'general_apikey_exception';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}

class ApptokenException extends AppApiException {
	public function __construct(string $message = '', int $code = 500, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('An apptoken exception occurred.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'general_apptoken_exception';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}

class AuthException extends AppApiException {
	public function __construct(string $message = '', int $code = 401, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('An auth exception occurred.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'general_auth_exception';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}

class RefreshtokenInvalidException extends AuthException {
	public function __construct(string $message = '', int $code = 400, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('Refresh Token invalid.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'refresh_token_invalid';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}

class AccesstokenInvalidException extends AuthException {
	public function __construct(string $message = '', int $code = 400, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('Access Token invalid.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'access_token_invalid';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}

class RefreshtokenNotBeforeException extends AuthException {
	public function __construct(string $message = '', int $code = 400, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('Refresh Token is not yet valid. ');
			if (!empty($additionals['nbf'])) {
				$message .= 'Please wait until ' . wire('datetime')->date(__('Y-m-d @ H:i:s'), $additionals['nbf']) . ' before you try again.';
			}
		}

		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'refresh_token_nbf';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}

class AccesstokenNotBeforeException extends AuthException {
	public function __construct(string $message = '', int $code = 400, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('Access Token is not yet valid.');
			if (!empty($additionals['nbf'])) {
				$message .= 'Please wait until ' . wire('datetime')->date(__('Y-m-d @ H:i:s'), $additionals['nbf']) . ' before you try again.';
			}
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'access_token_nbf';
		}

		parent::__construct($message, $code, $additionals, $previous);
	}
}

class RefreshtokenExpiredException extends AuthException {
	public function __construct(string $message = '', int $code = 400, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('Refresh Token expired. Please log in to start a new session.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'refresh_token_expired';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}

class AccesstokenExpiredException extends AuthException {
	public function __construct(string $message = '', int $code = 401, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('Access Token expired.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'access_token_expired';
		}
		if (!isset($additionals['can_renew'])) {
			$additionals['can_renew'] = true;
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}

class TestException extends AppApiException {
	public function __construct(string $message = '', int $code = 402, array $additionals = [], Throwable $previous = null) {
		if (strlen($message) < 1) {
			$message = __('A test exception occurred.');
		}
		if (!isset($additionals['errorcode'])) {
			$additionals['errorcode'] = 'test_exception';
		}
		parent::__construct($message, $code, $additionals, $previous);
	}
}
