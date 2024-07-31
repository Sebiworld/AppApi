<?php

namespace ProcessWire;

class Apikey extends WireData {
	private $initiated = false;
	protected $id;
	protected $application;
	protected $created;
	protected $createdUser;
	protected $modified;
	protected $modifiedUser;
	protected $key;
	protected $version;
	protected $description;
	protected $accessibleUntil;

	public function __construct($import = []) {
		$this->id = null;
		$this->applicationID = null;
		$this->created = time();
		$this->createdUser = null;
		$this->modified = time();
		$this->modifiedUser = null;
		$this->key = '';
		$this->version = '';
		$this->description = '';
		$this->accessibleUntil = null;

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
			throw new ApikeyException('You cannot create an apikey without an application-id.');
		}

		if ($this->isNew()) {
			$this->created = time();
			$this->createdUser = $this->wire('user');
			$this->modified = time();
			$this->modifiedUser = $this->wire('user');
		}
		$this->initiated = true;
	}

	protected function import(array $values) {
		if (!isset($values['application_id'])) {
			throw new ApikeyException('You cannot import an apikey without an application-id.');
		}

		if (!isset($values['id'])) {
			throw new ApikeyException('You cannot import an apikey without an id.');
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

		if (isset($values['key'])) {
			$this->___setKey($values['key']);
		}

		if (isset($values['version'])) {
			$this->___setVersion($values['version']);
		}

		if (isset($values['description'])) {
			$this->___setDescription($values['description']);
		}

		if (isset($values['accessible_until'])) {
			$this->___setAccessibleUntil($values['accessible_until']);
		}
	}

	public function ___isSaveable() {
		if (!$this->isValid()) {
			return false;
		}
		return true;
	}

	public function ___isValid() {
		return $this->isApplicationIDValid() && $this->isIDValid() && $this->isCreatedValid() && $this->isCreatedUserValid() && $this->isModifiedValid() && $this->isModifiedUserValid() && $this->isKeyValid() && $this->isVersionValid() && $this->isDescriptionValid() && $this->isAccessibleUntilValid();
	}

	public function ___isAccessible() {
		return $this->isValid() && ($this->getAccessibleUntil() === null || $this->getAccessibleUntil() > time());
	}

	public function ___isNew() {
		return empty($this->id);
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
			throw new ApikeyException('No valid modified date');
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
			throw new ApikeyException('No valid user');
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
			throw new ApikeyException('No valid modified date');
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
			throw new ApikeyException('No valid user');
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
			$modifiedUserString = '<a href="' . $modifiedUser->editUrl . '" target="_blank">' . $modifiedUserString . '</a>';
		}
		return $modifiedUserString;
	}

	public function ___setKey($key) {
		if (!$this->isKeyValid($key)) {
			throw new ApikeyException('No valid key');
		}
		$this->key = $key;
		if ($this->initiated) {
			$this->modified = time();
			$this->modifiedUser = $this->wire('user');
		}
		return $this->key;
	}

	public function isKeyValid($value = false) {
		if ($value === false) {
			$value = $this->key;
		}
		return is_string($value) && strlen($value) > 5;
	}

	public function ___regenerateKey($length = 21) {
		try {
			$keyFound = false;
			while (!$keyFound) {
				// Generate a new key:
				$tempKey = AppApiHelper::generateRandomString($length);

				// Test, if the key is already in use:
				$db = wire('database');
				$query = $db->prepare('SELECT * FROM ' . AppApi::tableApikeys . ' WHERE `key` = :key;');
				$query->closeCursor();

				$query->execute([
					':key' => $tempKey
				]);

				$result = $query->fetch(\PDO::FETCH_ASSOC);
				if (!$result) {
					// Key doesn't exist in db, can be used for new apikey
					$keyFound = $tempKey;
				}
			}
		} catch (\Exception $e) {
			return false;
		}

		if ($keyFound) {
			$this->key = $keyFound;
		}
	}

	public function getKey() {
		return $this->key;
	}

	public function ___setVersion($version) {
		if (!$this->isVersionValid($version)) {
			throw new ApikeyException('No valid version');
		}
		$this->version = $version;
		if ($this->initiated) {
			$this->modified = time();
			$this->modifiedUser = $this->wire('user');
		}
		return $this->version;
	}

	public function isVersionValid($value = false) {
		if ($value === false) {
			$value = $this->version;
		}
		return is_numeric($value) || (is_string($value) && strlen($value) > 0);
	}

	public function getVersion() {
		return $this->version;
	}

	public function ___setDescription($description) {
		if (!$this->isDescriptionValid($description)) {
			throw new ApikeyException('No valid description');
		}
		$this->description = $description;
		if ($this->initiated) {
			$this->modified = time();
			$this->modifiedUser = $this->wire('user');
		}
		return $this->description;
	}

	public function isDescriptionValid($value = false) {
		if ($value === false) {
			$value = $this->description;
		}
		return is_string($value);
	}

	public function getDescription() {
		return $this->description;
	}

	public function ___setAccessibleUntil($accessibleUntil) {
		if (is_string($accessibleUntil)) {
			$accessibleUntil = strtotime($accessibleUntil);
		}

		if (!$accessibleUntil || !is_integer($accessibleUntil) || $accessibleUntil <= 0) {
			$accessibleUntil = null;
		}

		if (!$this->isAccessibleUntilValid($accessibleUntil)) {
			throw new ApikeyException('No valid accessible-until date');
		}

		$this->accessibleUntil = $accessibleUntil;
		if ($this->initiated) {
			$this->modified = time();
			$this->modifiedUser = $this->wire('user');
		}
		return $this->accessibleUntil;
	}

	public function isAccessibleUntilValid($value = false) {
		if ($value === false) {
			$value = $this->accessibleUntil;
		}
		return $value === null || (is_integer($value) && $value > 0);
	}

	public function ___getAccessibleUntil() {
		return $this->accessibleUntil;
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
			$preparedQuery = 'DELETE FROM `' . AppApi::tableApikeys . '` WHERE `id`=:id;';
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
			':key' => $this->getKey(),
			':version' => $this->getVersion(),
			':description' => $this->getDescription(),
			':accessible_until' => $this->getAccessibleUntil() === null ? null : date('Y-m-d G:i:s', $this->getAccessibleUntil())
		];

		if (!$this->isNew()) {
			// This apikey already exists in db and shall be updated.

			$queryVars[':id'] = $this->getID();

			try {
				$query = $db->prepare('UPDATE `' . AppApi::tableApikeys . '` SET `application_id`=:application_id, `created_user_id`=:created_user_id, `created`=:created, `modified_user_id`=:modified_user_id, `modified`=:modified, `key`=:key, `version`=:version, `description`=:description, `accessible_until`=:accessible_until WHERE `id`=:id;');
				$query->closeCursor();
				$query->execute($queryVars);
			} catch (\Exception $e) {
				$this->error('The apikey [' . $this->getID() . '] could not be saved: ' . $e->getMessage());
				return false;
			}

			return true;
		}

		// New apikey should be saved into db:
		try {
			$query = $db->prepare('INSERT INTO `' . AppApi::tableApikeys . '` (`application_id`,`id`, `created_user_id`, `created`,`modified_user_id`, `modified`, `key`, `version`, `description`, `accessible_until`) VALUES (:application_id, NULL, :created_user_id, :created, :modified_user_id, :modified, :key, :version, :description, :accessible_until);');
			$query->closeCursor();
			$query->execute($queryVars);
			$this->id = $db->lastInsertId();
		} catch (\Exception $e) {
			$this->error('The apikey could not be saved: ' . $e->getMessage());
			return false;
		}

		return true;
	}
}
