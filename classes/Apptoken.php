<?php

namespace ProcessWire;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/AppApiHelper.php';

use \Firebase\JWT\JWT;

class Apptoken extends WireData {
	private $initiated = false;
	protected $id;
	protected $application;
	protected $created;
	protected $createdUser;
	protected $modified;
	protected $modifiedUser;
	protected $tokenID;
	protected $user;
	protected $expirationTime;
	protected $notBeforeTime;
	protected $lastUsed;

	public function __construct($import = []) {
		$this->id = null;
		$this->applicationID = null;
		$this->created = time();
		$this->createdUser = null;
		$this->modified = time();
		$this->modifiedUser = null;
		$this->tokenID = '';
		$this->user = null;
		$this->expirationTime = null;
		$this->notBeforeTime = time();
		$this->lastUsed = null;

		if (is_array($import) && wireCount($import) > 0) {
			$this->import($import);
		} elseif ($import instanceof Application) {
			$this->applicationID = $import->getID();
		} elseif (is_integer($import)) {
			$this->applicationID = $import;
		} elseif (is_string($import)) {
			$this->applicationID = (int)$import;
		}

		if (!$this->isApplicationIDValid()) {
			throw new ApptokenException('You cannot create an apptoken without an application-id.');
		}

		if ($this->isNew()) {
			$this->created = time();
			$this->createdUser = $this->wire('user');
			$this->modified = time();
			$this->modifiedUser = $this->wire('user');
			$this->regenerateTokenID();
		}
		$this->initiated = true;
	}

	protected function import(array $values) {
		if (!isset($values['application_id'])) {
			throw new ApptokenException('You cannot import an apptoken without an application-id.');
		}

		if (!isset($values['id'])) {
			throw new ApptokenException('You cannot import an apptoken without an id.');
		}
		$this->applicationID = (int) $values['application_id'];
		$this->id = (int) $values['id'];

		if (isset($values['created'])) {
			$this->___setCreated($values['created']);
		}
		if (isset($values['created_user_id'])) {
			$this->___setCreatedUser($values['created_user_id']);
		}

		if (isset($values['modified'])) {
			$this->___setModified($values['modified']);
		}
		if (isset($values['modified_user_id'])) {
			$this->___setModifiedUser($values['modified_user_id']);
		}

		if (isset($values['token_id'])) {
			$this->___setTokenID($values['token_id']);
		}

		if (isset($values['user_id'])) {
			$this->___setUser($values['user_id']);
		}

		if (isset($values['last_used'])) {
			$this->___setLastUsed($values['last_used']);
		}

		if (isset($values['expiration_time'])) {
			$this->___setExpirationTime($values['expiration_time']);
		}

		if (isset($values['not_before_time'])) {
			$this->___setNotBeforeTime($values['not_before_time']);
		}
	}

	public function ___isSaveable() {
		if (!$this->isValid()) {
			return false;
		}
		return true;
	}

	public function ___isValid() {
		return $this->isApplicationIDValid() && $this->isIDValid() && $this->isCreatedValid() && $this->isCreatedUserValid() && $this->isModifiedValid() && $this->isModifiedUserValid() && $this->isTokenIDValid() && $this->isUserValid() && $this->isLastUsedValid() && $this->isExpirationTimeValid() && $this->isNotBeforeTimeValid();
	}

	public function ___isAccessable() {
		return $this->isValid() && $this->getNotBeforeTime() <= time() && ($this->getExpirationTime() === null || $this->getExpirationTime() > time());
	}

	public function isNew() {
		return empty($this->id);
	}

	public function getJWT($secret, $args = []) {
		if (!is_string($secret)) {
			throw new ApptokenException('Secret not valid.', 500);
		}

		$tokenArgs = [
			'iss' => $this->wire('config')->httpHost, // issuer
			'aud' => $this->getApplicationID(), // audience
			'sub' => $this->getUser()->id, // subject
			'iat' => $this->getCreated(), // issued at
			'nbf' => $this->getNotBeforeTime(), // valid not before
			'jti' => $this->getTokenID() // Token-ID
		];

		if (is_integer($this->getExpirationTime()) && $this->getExpirationTime() > 0) {
			$tokenArgs['exp'] = $this->getExpirationTime(); // token expire time
		}

		$tokenArgs = array_merge($tokenArgs, $args);

		return JWT::encode($tokenArgs, $secret, 'HS256');
	}

	public function matchesWithJWT($jwt) {
		if (!is_object($jwt)) {
			throw new ApptokenException('Token not valid.', 400);
		}

		if ($jwt->iss !== $this->wire('config')->httpHost) {
			return false;
		}
		if ($jwt->aud !== $this->getApplicationID()) {
			return false;
		}
		if ($jwt->sub !== $this->getCreatedUser()->id) {
			return false;
		}
		if ($jwt->iat < $this->getCreated()) {
			return false;
		}

		if (isset($jwt->rtkn)) {
			// An accesstoken should be validated
			if ($jwt->rtkn !== $this->getTokenID()) {
				return false;
			}
		} else {
			if ($jwt->jti !== $this->getTokenID()) {
				return false;
			}

			if ($jwt->nbf !== $this->getNotBeforeTime()) {
				return false;
			}
			if (is_integer($this->getExpirationTime()) && $this->getExpirationTime() > 0) {
				if ($jwt->exp !== $this->getExpirationTime()) {
					return false;
				}
			}
		}

		return true;
	}

	public function getApplicationID() {
		return $this->applicationID;
	}

	public function isApplicationIDValid($value = false) {
		if ($value === false) {
			$value = $this->applicationID;
		}
		return is_integer($value) && $value >= 0;
	}

	protected function ___getApplication() {
		$db = wire('database');
		$query = $db->prepare('SELECT * FROM ' . AppApi::tableApplications . ' WHERE `id`=:id;');
		$query->closeCursor();

		$query->execute([
			':id' => $this->getID()
		]);
		$queueRaw = $query->fetch(\PDO::FETCH_ASSOC);
		return new Application($queueRaw);
	}

	public function getID() {
		return $this->id;
	}

	public function isIDValid($value = false) {
		if ($value === false) {
			$value = $this->id;
		}
		return $value === null || (is_integer($value) && $value >= 0);
	}

	public function ___setCreated($created) {
		if (is_string($created)) {
			$created = strtotime($created);
		}

		if (!$this->isCreatedValid($created)) {
			throw new ApptokenException('No valid modified date');
		}

		$this->created = $created;
		return $this->created;
	}

	public function isCreatedValid($value = false) {
		if ($value === false) {
			$value = $this->created;
		}
		return is_integer($value) && $value > 0;
	}

	public function getCreated() {
		return $this->created;
	}

	public function ___setCreatedUser($createdUser) {
		if (!$createdUser instanceof User || !$createdUser->id) {
			$createdUser = wire('users')->get($createdUser);
		}
		if (!$this->isCreatedUserValid($createdUser)) {
			throw new ApptokenException('No valid user');
		}
		$this->createdUser = $createdUser;
		return $this->createdUser;
	}

	public function isCreatedUserValid($value = false) {
		if ($value === false) {
			$value = $this->createdUser;
		}
		return $value instanceof User && $value->id;
	}

	public function getCreatedUser() {
		if (!$this->isCreatedUserValid()) {
			return wire('users')->getGuestUser();
		}
		return $this->createdUser;
	}

	public function getCreatedUserLink() {
		$createdUser = $this->getCreatedUser();
		$createdUserString = $createdUser->name . ' (' . $createdUser->id . ')';
		if ($createdUser->editable()) {
			$createdUserString = '<a href="' . $createdUser->editUrl . '" target="_blank">' . $createdUserString . '</a>';
		}
		return $createdUserString;
	}

	public function ___setModified($modified) {
		if (is_string($modified)) {
			$modified = strtotime($modified);
		}

		if (!$this->isModifiedValid($modified)) {
			throw new ApptokenException('No valid modified date');
		}

		$this->modified = $modified;
		return $this->modified;
	}

	public function isModifiedValid($value = false) {
		if ($value === false) {
			$value = $this->modified;
		}
		return is_integer($value) && $value > 0;
	}

	public function getModified() {
		return $this->modified;
	}

	public function ___setModifiedUser($modifiedUser) {
		if (!$modifiedUser instanceof User || !$modifiedUser->id) {
			$modifiedUser = wire('users')->get($modifiedUser);
		}
		if (!$this->isModifiedUserValid($modifiedUser)) {
			throw new ApptokenException('No valid user');
		}
		$this->modifiedUser = $modifiedUser;
		return $this->modifiedUser;
	}

	public function isModifiedUserValid($value = false) {
		if ($value === false) {
			$value = $this->modifiedUser;
		}
		return $value instanceof User && $value->id;
	}

	public function getModifiedUser() {
		if (!$this->isModifiedUserValid()) {
			return wire('users')->getGuestUser();
		}
		return $this->modifiedUser;
	}

	public function getModifiedUserLink() {
		$modifiedUser = $this->getModifiedUser();
		$modifiedUserString = $modifiedUser->name . ' (' . $modifiedUser->id . ')';
		if ($modifiedUser->editable()) {
			$modifiedUserString = '<a href="' . $modifiedUser->editUrl . '">' . $modifiedUserString . '</a>';
		}
		return $modifiedUserString;
	}

	public function ___setTokenID($tokenID) {
		if (!$this->isTokenIDValid($tokenID)) {
			throw new ApptokenException('No valid tokenID');
		}
		$this->tokenID = $tokenID;
		if ($this->initiated) {
			$this->modified = time();
			$this->modifiedUser = $this->wire('user');
		}
		return $this->tokenID;
	}

	public function isTokenIDValid($value = false) {
		if ($value === false) {
			$value = $this->tokenID;
		}
		return is_string($value) && strlen($value) > 5;
	}

	public function regenerateTokenID($length = 21) {
		try {
			$tokenIDfound = false;
			while (!$tokenIDfound) {
				// Generate a new tokenid:
				$tempTokenID = AppApiHelper::generateRandomString($length);

				// Test, if the tokenid is already in use:
				$db = wire('database');
				$query = $db->prepare('SELECT * FROM ' . AppApi::tableApptokens . ' WHERE `token_id` = :token_id AND `application_id`=:application_id;');
				$query->closeCursor();

				$query->execute([
					':token_id' => $tempTokenID,
					':application_id' => $this->getApplicationID()
				]);

				$result = $query->fetch(\PDO::FETCH_ASSOC);
				if (!$result) {
					// tokenid doesn't exist in db, can be used for new tokenid
					$tokenIDfound = $tempTokenID;
				}
			}
		} catch (\Exception $e) {
			return false;
		}

		if ($tokenIDfound) {
			$this->tokenID = $tokenIDfound;
		}
	}

	public function getTokenID() {
		return $this->tokenID;
	}

	public function ___setUser($user) {
		if (!$user instanceof User || !$user->id) {
			$user = wire('users')->get($user);
		}
		if (!$this->isUserValid($user)) {
			throw new ApptokenException('No valid user');
		}
		$this->user = $user;
		return $this->user;
	}

	public function isUserValid($value = false) {
		if ($value === false) {
			$value = $this->user;
		}
		return $value instanceof User && $value->id;
	}

	public function getUser() {
		if (!$this->isUserValid()) {
			return wire('users')->getGuestUser();
		}
		return $this->user;
	}

	public function getUserLink() {
		$user = $this->getUser();
		$userString = $user->name . ' (' . $user->id . ')';
		if ($user->editable()) {
			$userString = '<a href="' . $user->editUrl . '" target="_blank">' . $userString . '</a>';
		}
		return $userString;
	}

	public function ___setLastUsed($lastUsed) {
		if (is_string($lastUsed)) {
			$lastUsed = strtotime($lastUsed);
		}

		if (!$lastUsed || !is_integer($lastUsed) || $lastUsed <= 0) {
			$lastUsed = null;
		}

		if (!$this->isLastUsedValid($lastUsed)) {
			throw new ApptokenException('No valid last-used date');
		}

		$this->lastUsed = $lastUsed;
		if ($this->initiated) {
			$this->modified = time();
			$this->modifiedUser = $this->wire('user');
		}
		return $this->lastUsed;
	}

	public function isLastUsedValid($value = false) {
		if ($value === false) {
			$value = $this->lastUsed;
		}
		return $value === null || (is_integer($value) && $value > 0);
	}

	public function getLastUsed() {
		return $this->lastUsed;
	}

	public function ___setExpirationTime($expirationTime) {
		if (is_string($expirationTime)) {
			$expirationTime = strtotime($expirationTime);
		}

		if (!$expirationTime || !is_integer($expirationTime) || $expirationTime <= 0) {
			$expirationTime = null;
		}

		if (!$this->isExpirationTimeValid($expirationTime)) {
			throw new ApptokenException('No valid expiration-time');
		}

		$this->expirationTime = $expirationTime;
		if ($this->initiated) {
			$this->modified = time();
			$this->modifiedUser = $this->wire('user');
		}
		return $this->expirationTime;
	}

	public function isExpirationTimeValid($value = false) {
		if ($value === false) {
			$value = $this->expirationTime;
		}
		return $value === null || (is_integer($value) && $value > 0);
	}

	public function getExpirationTime() {
		return $this->expirationTime;
	}

	public function ___setNotBeforeTime($notBeforeTime) {
		if (is_string($notBeforeTime)) {
			$notBeforeTime = strtotime($notBeforeTime);
		}

		if (!$notBeforeTime || !is_integer($notBeforeTime) || $notBeforeTime <= 0) {
			$notBeforeTime = null;
		}

		if (!$this->isNotBeforeTimeValid($notBeforeTime)) {
			throw new ApptokenException('No valid not-before-time');
		}

		$this->notBeforeTime = $notBeforeTime;
		if ($this->initiated) {
			$this->modified = time();
			$this->modifiedUser = $this->wire('user');
		}
		return $this->notBeforeTime;
	}

	public function isNotBeforeTimeValid($value = false) {
		if ($value === false) {
			$value = $this->notBeforeTime;
		}
		return (is_integer($value) && $value > 0);
	}

	public function getNotBeforeTime() {
		return $this->notBeforeTime;
	}

	public function ___delete() {
		if ($this->isNew()) {
			return true;
		}

		try {
			$db = wire('database');
			$queryVars = [
				':id' => $this->getID()
			];
			$preparedQuery = 'DELETE FROM `' . AppApi::tableApptokens . '` WHERE `id`=:id;';
			$query = $db->prepare($preparedQuery);
			$query->closeCursor();
			$query->execute($queryVars);
		} catch (\Exception $e) {
			return false;
		}

		return true;
	}

	public function ___save() {
		if (!$this->isSaveable()) {
			return false;
		}

		$db = wire('database');
		$queryVars = [
			':application_id' => $this->getApplicationID(),
			':created_user_id' => $this->getCreatedUser()->id,
			':created' => date('Y-m-d G:i:s', $this->getCreated() === null ? 0 : $this->getCreated()),
			':modified_user_id' => $this->getModifiedUser()->id,
			':modified' => date('Y-m-d G:i:s', $this->getModified() === null ? 0 : $this->getModified()),
			':token_id' => $this->getTokenID(),
			':user_id' => $this->getUser()->id,
			':last_used' => $this->getLastUsed() === null ? null : date('Y-m-d G:i:s', $this->getLastUsed()),
			':expiration_time' => $this->getExpirationTime() === null ? null : date('Y-m-d G:i:s', $this->getExpirationTime()),
			':not_before_time' => $this->getNotBeforeTime() === null ? null : date('Y-m-d G:i:s', $this->getNotBeforeTime())
		];

		if (!$this->isNew()) {
			// This apptoken already exists in db and shall be updated.

			$queryVars[':id'] = $this->getID();

			try {
				$query = $db->prepare('UPDATE `' . AppApi::tableApptokens . '` SET `application_id`=:application_id, `created_user_id`=:created_user_id, `created`=:created, `modified_user_id`=:modified_user_id, `modified`=:modified, `token_id`=:token_id, `user_id`=:user_id, `last_used`=:last_used, `expiration_time`=:expiration_time, `not_before_time`=:not_before_time WHERE `id`=:id;');
				$query->closeCursor();
				$query->execute($queryVars);
			} catch (\Exception $e) {
				$this->error('The apptoken [' . $this->getID() . '] could not be saved: ' . $e->getMessage());
				throw $e;
				return false;
			}

			return true;
		}

		// New apptoken should be saved into db:
		try {
			$query = $db->prepare('INSERT INTO `' . AppApi::tableApptokens . '` (`application_id`,`id`, `created_user_id`, `created`,`modified_user_id`, `modified`, `token_id`, `user_id`, `last_used`, `expiration_time`, `not_before_time`) VALUES (:application_id, NULL, :created_user_id, :created, :modified_user_id, :modified, :token_id, :user_id, :last_used, :expiration_time, :not_before_time);');
			$query->closeCursor();
			$query->execute($queryVars);
			$this->id = $db->lastInsertId();
		} catch (\Exception $e) {
			$this->error('The apptoken could not be saved: ' . $e->getMessage());
			throw $e;
			return false;
		}

		return true;
	}
}
