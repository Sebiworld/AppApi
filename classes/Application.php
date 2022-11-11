<?php
namespace ProcessWire;

class Application extends WireData {
   private $initiated = false;
   protected $id;
   protected $title;
   protected $description;
   protected $created;
   protected $createdUser;
   protected $modified;
   protected $modifiedUser;

   protected $tokenSecret;
   protected $accesstokenSecret;

   protected $defaultApplication;
   protected $expiresIn;

   protected $authtype = 0;

   /*
    * A normal website-application can be used to access public data with only a valid apikey. Protected data will be authorized with classic php-sessions. If you are logged in, you can use protected apipages.
    */
   const authtypeSession = 0;

   /**
    * A protected website-application shows contents only if you have a valid JWT-token that authorizes you to use an endpoint. A JWT-token should be requested via PHP and has to be transferred to JS on pageload (e.g. as a special data-attribute). It can be limited to only those special endpoints that the api function uses. The JWT-token is linked to the php-session so it has a limited livetime. With protectedWebsite-endpoints we can prevent that api-access via token can be used for general apicalls from other services.
    */
   const authtypeSingleJWT = 1;

   /*
    * A classic app allows users to log in and authenticate via refresh- and access-tokens afterwards. Public contents are available with only a valid apikey.
    */
   const authtypeDoubleJWT = 2;

   /*
    * A secure double JWT saves the refresh_token in a secure httpOnly cookie that the client app cant access via JS, it gets sent automatically with every request 
    * and is validated to provide an access_token, which the client app doesnt need to store anywhere and can live safely in a memory state where an atacker cant access it, 
    * allows users to access via refresh- and access-tokens afterwards. Public contents are available with only a valid apikey.
    */
   const authtypeDoubleJWTsecure = 3;

   public static function getAuthtypeLabel($authtype) {
      if ($authtype === self::authtypeSession) {
         return __('PHP Session');
      } elseif ($authtype === self::authtypeSingleJWT) {
         return __('Single JWT');
      } elseif ($authtype === self::authtypeDoubleJWT) {
         return __('Double JWT');
      } elseif ($authtype === self::authtypeDoubleJWTsecure) {
         return __('Double JWT (secure)');
      }
      return 'Unknown: ' . $authtype;
   }

   const logintypeOptions = [
      'logintypeUsernamePassword',
      'logintypeEmailPassword'
   ];

   protected $logintype = ['logintypeUsernamePassword'];


   public static function getLogintypeLabel($logintype) {
      if ($logintype === self::logintypeOptions[0]) {
         return __('Username sign-in');
      } elseif ($logintype === self::logintypeOptions[1]) {
         return __('Email sign-in');
      }
      return 'Unknown: ' . $loginype;
   }


   public function __construct(array $import = []) {
      $this->id = null;
      $this->created = time();
      $this->createdUser = null;
      $this->modified = time();
      $this->modifiedUser = null;
      $this->title = '';
      $this->description = '';
      $this->defaultApplication = false;

      $this->tokenSecret = '';
      $this->accesstokenSecret = '';

      $this->expiresIn = 30 * 24 * 60 * 60; // default: 30 days

      if (is_array($import) && wireCount($import) > 0) {
         $this->import($import);
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

      if (!isset($values['id'])) {
         throw new ApplicationException('You cannot import an application without an id.');
      }
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

      if (isset($values['title'])) {
         $this->___setTitle($values['title']);
      }

      if (isset($values['description'])) {
         $this->___setDescription($values['description']);
      }

      if (isset($values['default_application'])) {
         $this->___setDefaultApplication($values['default_application']);
      }

      if (isset($values['token_secret'])) {
         $this->___setTokenSecret($values['token_secret']);
      }

      if (isset($values['accesstoken_secret'])) {
         $this->___setAccesstokenSecret($values['accesstoken_secret']);
      }

      if (isset($values['authtype'])) {
         $this->___setAuthtype($values['authtype']);
      }

      if (isset($values['logintype']) && !!json_decode($values['logintype'])) {
         $this->___setLogintype(json_decode($values['logintype']));
      }

      if (isset($values['expires_in'])) {
         $this->___setExpiresIn($values['expires_in']);
      }
   }

   public function ___isSaveable() {
      if (!$this->isValid()) {
         return false;
      }
      return true;
   }

   public function ___isValid() {
      return $this->isIDValid() && $this->isCreatedValid() && $this->isCreatedUserValid() && $this->isModifiedValid() && $this->isModifiedUserValid() && $this->isTitleValid() && $this->isDescriptionValid() && $this->isTokenSecretValid() && $this->isAccesstokenSecretValid() && $this->isAuthtypeValid() && $this->isLogintypeValid() && $this->isExpiresInValid() && $this->isDefaultApplicationValid();
   }

   public function ___isNew() {
      return empty($this->id);
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
         throw new ApplicationException('No valid modified date');
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
         throw new ApplicationException('No valid user');
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
         throw new ApplicationException('No valid modified date');
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
         throw new ApplicationException('No valid user');
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

   public function ___setTitle($title) {
      $title = $this->sanitizer->text($title);
      if (!$this->isTitleValid($title)) {
         throw new ApplicationException('No valid title');
      }
      $this->title = $title;
      if ($this->initiated) {
         $this->modified = time();
         $this->modifiedUser = $this->wire('user');
      }
      return $this->title;
   }

   public function isTitleValid($value = false) {
      if ($value === false) {
         $value = $this->title;
      }
      return is_string($value) && strlen($value) > 0;
   }

   public function getTitle() {
      return $this->title;
   }

   public function ___setDescription($description) {
      $description = $this->sanitizer->textarea($description);
      if (!$this->isDescriptionValid($description)) {
         throw new ApplicationException('No valid description');
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

   public function ___setTokenSecret($tokenSecret) {
      if (!$this->isTokenSecretValid($tokenSecret)) {
         throw new ApplicationException('No valid token secret');
      }
      $this->tokenSecret = $tokenSecret;
      if ($this->initiated) {
         $this->modified = time();
         $this->modifiedUser = $this->wire('user');
      }
      return $this->tokenSecret;
   }

   public function isTokenSecretValid($value = false) {
      if ($value === false) {
         $value = $this->tokenSecret;
      }
      return is_string($value) && strlen($value) > 10;
   }

   public function ___regenerateTokenSecret($length = 42) {
      $this->tokenSecret = AppApiHelper::generateRandomString($length, false);
   }

   public function getTokenSecret() {
      return $this->tokenSecret;
   }

   public function ___setAccesstokenSecret($accesstokenSecret) {
      if (!$this->isAccesstokenSecretValid($accesstokenSecret)) {
         throw new ApplicationException('No valid access token secret');
      }
      $this->accesstokenSecret = $accesstokenSecret;
      if ($this->initiated) {
         $this->modified = time();
         $this->modifiedUser = $this->wire('user');
      }
      return $this->accesstokenSecret;
   }

   public function isAccesstokenSecretValid($value = false) {
      if ($value === false) {
         $value = $this->accesstokenSecret;
      }
      return is_string($value) && strlen($value) > 10;
   }

   public function regenerateAccesstokenSecret($length = 30) {
      $this->accesstokenSecret = AppApiHelper::generateRandomString($length, false);
   }

   public function getAccesstokenSecret() {
      return $this->accesstokenSecret;
   }

   public function ___setDefaultApplication($defaultApplication) {
      $this->defaultApplication = !!$defaultApplication && $defaultApplication !== 0;
      if ($this->initiated) {
         $this->modified = time();
         $this->modifiedUser = $this->wire('user');
      }
      return $this->defaultApplication;
   }

   public function isDefaultApplicationValid($value = false) {
      return true;
   }

   public function isDefaultApplication() {
      return !!$this->defaultApplication;
   }

   public function ___setExpiresIn($expiresIn) {
      if (is_string($expiresIn)) {
         $expiresIn = intval($expiresIn);
      }
      if (!$this->isExpiresInValid($expiresIn)) {
         throw new ApplicationException('No valid expires in value');
      }
      $this->expiresIn = $expiresIn;
      if ($this->initiated) {
         $this->modified = time();
         $this->modifiedUser = $this->wire('user');
      }
      return $this->expiresIn;
   }

   public function isExpiresInValid($value = false) {
      if ($value === false) {
         $value = $this->expiresIn;
      }
      return is_integer($value) && $value > 0;
   }

   public function getExpiresIn() {
      return $this->expiresIn;
   }

   public function ___setAuthtype($authtype) {
      $authtype = $this->sanitizer->int($authtype);
      if (!$this->isAuthtypeValid($authtype)) {
         throw new ApplicationException('No valid authtype');
      }
      $this->authtype = $authtype;
      if ($this->initiated) {
         $this->modified = time();
         $this->modifiedUser = $this->wire('user');
      }
      return $this->authtype;
   }

   public function isAuthtypeValid($value = false) {
      if ($value === false) {
         $value = $this->authtype;
      }
      return $value >= 0 && $value <= 3;
   }

   public function getAuthtype() {
      return $this->authtype;
   }

   public function ___setLogintype($logintype) {
      $logintype = $this->sanitizer->options($logintype, self::logintypeOptions);
      if (!$this->isLogintypeValid($logintype)) {
         throw new ApplicationException('No valid logintype');
      }
      $this->logintype = $logintype;
      if ($this->initiated) {
         $this->modified = time();
         $this->modifiedUser = $this->wire('user');
      }
      return $this->logintype;
   }

   public function isLogintypeValid($value = []) {
      if (!count($value)) {
         $value = $this->logintype;
      }
      return !!count($value);
   }

   public function getLogintype() {
      return $this->logintype;
   }

   public function ___getApikeys() {
      if ($this->isNew()) {
         return new WireArray();
      }

      $apikeys = new WireArray();
      $db = wire('database');
      $query = $db->prepare('SELECT * FROM ' . AppApi::tableApikeys . ' WHERE `application_id`=:application_id;');
      $query->closeCursor();
      $query->execute([
         ':application_id' => $this->getID()
      ]);
      $queueRaw = $query->fetchAll(\PDO::FETCH_ASSOC);

      foreach ($queueRaw as $queueItem) {
         if (!isset($queueItem['id']) || empty($queueItem['id'])) {
            continue;
         }

         try {
            $apikey = new Apikey($queueItem);
            if ($apikey->isValid()) {
               $apikeys->add($apikey);
            }
         } catch (\Exception $e) {
         }
      }
      return $apikeys;
   }

   public function ___getApikey($key) {
      if ($key instanceof Apikey) {
         $key = $key->getKey();
      }
      return $this->getApikeys()->findOne('key=' . $key);
   }

   public function ___hasApikey($key) {
      return $this->getApikey($key) instanceof Apikey;
   }

   public function ___removeApikey($key) {
      if ($key instanceof Apikey) {
         $key = $key->getKey();
      }
      $apikey = $this->getApikey($key);
      if (!$apikey instanceof Apikey) {
         return true;
      }

      return $apikey->delete();
   }

   public function ___getApptokens() {
      if ($this->isNew()) {
         return new WireArray();
      }

      $apptokens = new WireArray();
      $db = wire('database');
      $query = $db->prepare('SELECT * FROM ' . AppApi::tableApptokens . ' WHERE `application_id`=:application_id;');
      $query->closeCursor();
      $query->execute([
         ':application_id' => $this->getID()
      ]);
      $queueRaw = $query->fetchAll(\PDO::FETCH_ASSOC);

      foreach ($queueRaw as $queueItem) {
         if (!isset($queueItem['id']) || empty($queueItem['id'])) {
            continue;
         }

         try {
            $apptoken = new Apptoken($queueItem);
            if ($apptoken->isValid()) {
               $apptokens->add($apptoken);
            }
         } catch (\Exception $e) {
         }
      }
      return $apptokens;
   }

   public function ___getApptoken($tokenID) {
      try {
         $db = wire('database');
         $query = $db->prepare('SELECT * FROM ' . AppApi::tableApptokens . ' WHERE `token_id`=:token_id AND `application_id`=:application_id;');
         $query->closeCursor();

         $query->execute([
            ':token_id' => $tokenID,
            ':application_id' => $this->getID()
         ]);
         $queueRaw = $query->fetch(\PDO::FETCH_ASSOC);

         return new Apptoken($queueRaw);
      } catch (\Exception $e) {
         throw $e;
         return false;
      }

      return false;
   }

   /**
    * Deletes the application and all associated apikeys & tokens
    *
    * @return boolean
    */
   public function ___delete() {
      if ($this->isNew()) {
         return true;
      }

      try {
         $db = wire('database');
         $queryVars = [
            ':id' => $this->getID()
         ];

         $preparedQuery = 'DELETE FROM `' . AppApi::tableApikeys . '` WHERE `application_id`=:id;';
         $preparedQuery .= 'DELETE FROM `' . AppApi::tableApptokens . '` WHERE `application_id`=:id;';
         $preparedQuery .= 'DELETE FROM `' . AppApi::tableApplications . '` WHERE `id`=:id;';

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
         ':created_user_id' => $this->getCreatedUser()->id,
         ':created' => date('Y-m-d G:i:s', $this->getCreated() === null ? 0 : $this->getCreated()),
         ':modified_user_id' => $this->getModifiedUser()->id,
         ':modified' => date('Y-m-d G:i:s', $this->getModified() === null ? 0 : $this->getModified()),
         ':title' => $this->getTitle(),
         ':description' => $this->getDescription(),
         ':default_application' => $this->isDefaultApplication() ? 1 : 0,
         ':token_secret' => $this->getTokenSecret(),
         ':accesstoken_secret' => $this->getAccesstokenSecret(),
         ':authtype' => $this->getAuthtype(),
         ':logintype' => json_encode($this->getLogintype()),
         ':expires_in' => $this->getExpiresIn()
      ];

      if (!$this->isNew()) {
         // This application already exists in db and shall be updated.

         $queryVars[':id'] = $this->getID();

         try {
            $updateStatement = 'UPDATE `' . AppApi::tableApplications . '` SET `created_user_id`=:created_user_id, `created`=:created, `modified_user_id`=:modified_user_id, `modified`=:modified, `title`=:title, `description`=:description, `default_application`=:default_application, `token_secret`=:token_secret, `accesstoken_secret`=:accesstoken_secret, `authtype`=:authtype, `logintype`=:logintype, `expires_in`=:expires_in WHERE `id`=:id;';
            if ($this->isDefaultApplication()) {
               $updateStatement .= 'UPDATE `' . AppApi::tableApplications . '` SET `default_application`=0 WHERE `id`!=:id;';
            }

            $query = $db->prepare($updateStatement);
            $query->closeCursor();
            $query->execute($queryVars);
         } catch (\Exception $e) {
            $this->error('The application [' . $this->getID() . '] could not be saved: ' . $e->getMessage());
            return false;
         }

         return true;
      }

      // New application should be saved into db:
      try {
         $createStatement = 'INSERT INTO `' . AppApi::tableApplications . '` (`id`, `created_user_id`, `created`,`modified_user_id`, `modified`, `title`, `description`, `default_application`, `token_secret`, `accesstoken_secret`, `authtype`, `logintype`, `expires_in`) VALUES (NULL, :created_user_id, :created, :modified_user_id, :modified, :title, :description, :default_application, :token_secret, :accesstoken_secret, :authtype, :logintype, :expires_in);';
         if ($this->isDefaultApplication()) {
            $createStatement .= 'UPDATE `' . AppApi::tableApplications . '` SET `default_application`=0 WHERE `id`!=:id;';
         }

         $query = $db->prepare($createStatement);
         $query->closeCursor();
         $query->execute($queryVars);
         $this->id = $db->lastInsertId();
      } catch (\Exception $e) {
         $this->error('The application could not be saved: ' . $e->getMessage());
         return false;
      }

      return true;
   }
}
