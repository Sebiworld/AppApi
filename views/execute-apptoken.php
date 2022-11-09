<?php
namespace ProcessWire;

if (!wire('user')->hasPermission(AppApi::manageApplicationsPermission)) {
	echo '<h2>' . $this->_('Access denied') . '</h2>';
	echo '<p>' . $this->_('You don\'t have the needed permissions to access this function. Please contact a Superuser.') . '</p>';
	return;
}

if (isset($locked) && $locked === true) {
	echo '<h2>' . $this->_('Access denied') . '</h2>';
	if (!empty($message)) {
		echo $message;
	}
	return;
}

if (!isset($application) || !$application instanceof Application) {
	echo '<h2>' . $this->_('Access denied') . '</h2>';
	echo '<p>' . $this->_('No application found.') . '</p>';
	return;
}

if (!isset($apptoken) || !$apptoken instanceof Apptoken) {
	$apptoken = new Apptoken($application->getID());
}

// Build form:
$form = $this->modules->get('InputfieldForm');
$form->method = 'POST';
$form->action = $apptoken->isNew() ? $this->wire('page')->url . 'apptoken/new/' . $application->getID() : $this->wire('page')->url . 'apptoken/edit/' . $apptoken->getID();

if ($apptoken->isNew()) {
	// TokenID:
	$field = $this->modules->get('InputfieldText');
	$field->label = $this->_('Token ID');
	$field->attr('id+name', 'form_token_id');
	$field->columnWidth = '100%';
	$field->required = 1;
	$field->value = $apptoken->getTokenID();
	$field->collapsed = Inputfield::collapsedNever;
	$field->description = $this->_('The token ID has to be included in every request as "AUTHORIZATION" header value to authenticate the user.');
	$field->notes = $this->_('Use an ID which is longer than 5 characters.');
	$field->pattern = '[a-zA-Z0-9]{5,100}';
	$field->minlength = 5;
	$field->maxlength = 100;
	$field->showCount = InputfieldText::showCountChars;
	$form->add($field);

	// User:
	$field = $this->modules->get('InputfieldPage');
	$field->label = $this->_('User');
	$field->attr('id+name', 'form_user');
	$field->columnWidth = '100%';
	$field->required = 1;
	$field->value = $apptoken->getUser();
	$field->collapsed = Inputfield::collapsedNever;
	$field->findPagesSelector = 'template=user, include=hidden, check_access=0';
	$field->inputfield = 'InputfieldSelect';
	$field->derefAsPage = FieldtypePage::derefAsPageOrNullPage;
	$form->add($field);

	// Not Before Time:
	$field = $this->modules->get('InputfieldDatetime');
	$field->label = $this->_('Not Before Date');
	$field->attr('id+name', 'form_not_before_time');
	$field->columnWidth = '50%';
	$field->required = 0;
	$field->value = $apptoken->getNotBeforeTime();
	$field->datepicker = InputfieldDatetime::datepickerFocus;
	$field->timeInputSelect = true;
	$field->timeInputFormat = 'H:i:s';
	$field->collapsed = Inputfield::collapsedNever;
	$field->description = $this->_('Use this field to limit a token´s validity to an startdate.');
	$form->add($field);

	// Expiration Time:
	$field = $this->modules->get('InputfieldDatetime');
	$field->label = $this->_('Expiration Date');
	$field->attr('id+name', 'form_expiration_time');
	$field->columnWidth = '50%';
	$field->required = 0;
	$field->value = $apptoken->getExpirationTime();
	$field->datepicker = InputfieldDatetime::datepickerFocus;
	$field->timeInputSelect = true;
	$field->timeInputFormat = 'H:i:s';
	$field->collapsed = Inputfield::collapsedNever;
	$field->description = $this->_('Use this field to limit a token´s validity to an enddate. After that, the user has to login to get a new token.');
	$form->add($field);
} else {
	// TokenID:
	$field = $this->modules->get('InputfieldMarkup');
	$field->label = $this->_('Token ID');
	$field->attr('id+name', 'form_token_id');
	$field->columnWidth = '100%';
	$field->value = $apptoken->getTokenID();
	$field->collapsed = Inputfield::collapsedNever;
	$form->add($field);

	// User:
	$field = $this->modules->get('InputfieldMarkup');
	$field->label = $this->_('User');
	$field->attr('id+name', 'form_user');
	$field->columnWidth = '100%';
	$field->value = $apptoken->getUserLink();
	$field->collapsed = Inputfield::collapsedNever;
	$form->add($field);

	// The Timestamps are linked with the token and cannot be changed after publishing the token:
	// Not Before Time:
	$field = $this->modules->get('InputfieldMarkup');
	$field->label = $this->_('Not Before Time (nbf claim)');
	$field->columnWidth = '50%';
	$field->value = wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $apptoken->getNotBeforeTime());
	$field->collapsed = Inputfield::collapsedNever;
	$form->add($field);

	// Expiration Time:
	$field = $this->modules->get('InputfieldMarkup');
	$field->label = $this->_('Expiration Time (exp claim)');
	$field->columnWidth = '50%';
	$field->value = wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $apptoken->getExpirationTime());
	$field->collapsed = Inputfield::collapsedNever;
	$form->add($field);
}

if ($apptoken->isNew()) {
	// Submit-Buttons:
	$button = $this->modules->get('InputfieldSubmit');
	$button->type = 'submit';
	$button->value = 'Save';
	$button->icon = 'floppy-o';
	$button->name = 'action-save';
	$button->header = false;
	$button->addActionValue('save-and-back', 'Save and back to Application', 'arrow-left');
	$form->add($button);
} else {
	$button = $this->modules->get('InputfieldButton');
	$button->href = $this->page->url . 'apptoken/delete/' . $apptoken->getID();
	$button->value = 'delete';
	$button->name = 'action-delete';
	$button->icon = 'trash-o';
	$button->secondary = true;
	$form->add($button);
}

// Handle Requests:
if (wire('input')->post('action-save')) {
	// form submitted
	$form->processInput(wire('input')->post);
	$errors = $form->getErrors();
	$messages = [];

	if (count($errors)) {
		// The submitted form-data has errors
		foreach ($errors as $error) {
			$this->error($error);
		}
	} else {
		// The submitted form has no errors. We can save the apptoken.

		try {
			$doRedirect = $apptoken->isNew();
			$apptoken->setTokenID($form->get('form_token_id')->attr('value'));
			$apptoken->setUser($form->get('form_user')->attr('value'));

			if ($apptoken->isNew()) {
				$apptoken->setNotBeforeTime($form->get('form_not_before_time')->attr('value'));
				$apptoken->setExpirationTime($form->get('form_expiration_time')->attr('value'));
			}

			if (!$apptoken->save()) {
				throw new \Exception('The apptoken could not be saved.');
			}

			$this->notices->add(new NoticeMessage($apptoken->isNew() ? $this->_('The apptoken was successfully created.') : $this->_('The changes to your apptoken were saved.')));

			if ($doRedirect) {
				$this->session->redirect($this->wire('page')->url . 'apptoken/edit/' . $apptoken->getID());
			} elseif (isset($_POST['_action_value']) && $_POST['_action_value'] === 'save-and-back') {
				$this->session->redirect($this->wire('page')->url . 'application/edit/' . $apptoken->getApplicationID());
			}
		} catch (\Exception $e) {
			$messages['errors'][] = $e->getMessage();
			$this->session->error($e->getMessage());
		}
	}
}

// Created- and Modified-Output is added after submission-handling, because only then the modified-date will have the correct time:
$field = $this->modules->get('InputfieldMarkup');
$field->label = $this->_('Modified');
$field->columnWidth = '50%';
$field->value = sprintf($this->_('On %s by %s'), wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $apptoken->getModified()), $apptoken->getModifiedUserLink());
$field->collapsed = Inputfield::collapsedNever;
$form->prepend($field);

$field = $this->modules->get('InputfieldMarkup');
$field->label = $this->_('Created');
$field->columnWidth = '50%';
$field->value = sprintf($this->_('On %s by %s'), wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $apptoken->getCreated()), $apptoken->getCreatedUserLink());
$field->collapsed = Inputfield::collapsedNever;
$form->prepend($field);

$field = $this->modules->get('InputfieldMarkup');
$field->label = $this->_('Last used');
$field->columnWidth = '100%';
$field->value = $apptoken->getLastUsed() ? (sprintf($this->_('The token was last used on %s'), wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $apptoken->getLastUsed()))) : $this->_('The token was never used.');
$field->collapsed = Inputfield::collapsedNever;
$form->insertBefore($field, $button);

// Output errors:
if (isset($messages['errors']) && is_array($messages['errors'])) {
	?>
<div class="NoticeError ui-state-error" style="padding: 5px 10px; margin-bottom: 20px;">
	<strong><?= $this->_('The form has errors: '); ?></strong><br />
	<?php
		$firstFlag = true;
	foreach ($messages['errors'] as $error) {
		if ($firstFlag) {
			echo $error;
			$firstFlag = false;
			continue;
		}
		echo '<br/>' . $error;
	} ?>
</div>
<?php
}
?>

<?= $form->render(); ?>

<p style='padding-top: 20px;'>
	<a
		href='<?= $this->wire('page')->url . 'application/edit/' . $apptoken->getApplicationID(); ?>'>
		<i
			class="fa fa-arrow-left"></i>&nbsp;<?= $this->_('Go Back'); ?>
	</a>
</p>