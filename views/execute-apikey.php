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

if (!isset($apikey) || !$apikey instanceof Apikey) {
	$apikey = new Apikey($application->getID());
	$apikey->regenerateKey();
}

// Build form:
$form = $this->modules->get('InputfieldForm');
$form->method = 'POST';
$form->action = $apikey->isNew() ? $this->wire('page')->url . 'apikey/new/' . $application->getID() : $this->wire('page')->url . 'apikey/edit/' . $apikey->getID();

// Version:
$field = $this->modules->get('InputfieldText');
$field->label = $this->_('Version');
$field->attr('id+name', 'form_version');
$field->columnWidth = '50%';
$field->required = 1;
$field->value = $apikey->getVersion();
$field->collapsed = Inputfield::collapsedNever;
$field->placeholder = $this->_('e.g. 1.0.0');
$field->description = $this->_('A version number, that matches with your app´s version.');
$field->notes = $this->_('I recommend to use a new apikey for each new version. By that you will be able to block individual versions that may be insecure or which users should be forced to update the app.');
$form->add($field);

// Key:
$field = $this->modules->get('InputfieldText');
$field->label = $this->_('Key');
$field->attr('id+name', 'form_key');
$field->columnWidth = '50%';
$field->required = 1;
$field->value = $apikey->getKey();
$field->collapsed = Inputfield::collapsedNever;
$field->description = $this->_('The key has to be included in every api-request as "X-API-KEY" header value.');
$field->notes = $this->_('Use a key which is longer than 5 characters.');
$field->pattern = '[a-zA-Z0-9]{5,100}';
$field->minlength = 5;
$field->maxlength = 100;
$field->showCount = InputfieldText::showCountChars;
$form->add($field);

// Description:
$field = $this->modules->get('InputfieldTextarea');
$field->label = $this->_('Description');
$field->description = $this->_('Use this field for personal notes. Neither does it affect anything nor will it be visible anywhere else than here.');
$field->attr('id+name', 'form_description');
$field->columnWidth = '100%';
$field->required = 0;
$field->value = $apikey->getDescription();
$field->collapsed = Inputfield::collapsedBlank;
$form->add($field);

// Accessible until:
$field = $this->modules->get('InputfieldDatetime');
$field->label = $this->_('Accessible until');
$field->description = $this->_('Use this field to lock this apikey after the given datetime.');
$field->attr('id+name', 'form_accessible_until');
$field->columnWidth = '100%';
$field->required = 0;
$field->value = $apikey->getAccessibleUntil();
$field->datepicker = InputfieldDatetime::datepickerFocus;
$field->timeInputSelect = true;
$field->timeInputFormat = 'H:i:s';
$field->collapsed = Inputfield::collapsedBlank;
$form->add($field);

// Submit-Buttons:
$button = $this->modules->get('InputfieldSubmit');
$button->type = 'submit';
$button->value = 'Save';
$button->icon = 'floppy-o';
$button->name = 'action-save';
$button->header = false;
$button->addActionValue('save-and-back', 'Save and back to Application', 'arrow-left');
$form->add($button);

if (!$apikey->isNew()) {
	$button = $this->modules->get('InputfieldButton');
	$button->href = $this->page->url . 'apikey/delete/' . $apikey->getID();
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
		// The submitted form has no errors. We can save the apikey.

		try {
			$doRedirect = $apikey->isNew();

			$apikey->setKey($form->get('form_key')->attr('value'));
			$apikey->setVersion($form->get('form_version')->attr('value'));
			$apikey->setDescription($form->get('form_description')->attr('value'));
			$apikey->setAccessibleUntil($form->get('form_accessible_until')->attr('value'));

			if (!$apikey->save()) {
				throw new \Exception('The apikey could not be saved.');
			}

			$this->notices->add(new NoticeMessage($apikey->isNew() ? $this->_('The apikey was successfully created.') : $this->_('The changes to your apikey were saved.')));

			if ($doRedirect) {
				$this->session->redirect($this->wire('page')->url . 'apikey/edit/' . $apikey->getID());
			} elseif (isset($_POST['_action_value']) && $_POST['_action_value'] === 'save-and-back') {
				$this->session->redirect($this->wire('page')->url . 'application/edit/' . $apikey->getApplicationID());
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
$field->value = sprintf($this->_('On %s by %s'), wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $apikey->getModified()), $apikey->getModifiedUserLink());
$field->collapsed = Inputfield::collapsedNever;
$form->prepend($field);

$field = $this->modules->get('InputfieldMarkup');
$field->label = $this->_('Created');
$field->columnWidth = '50%';
$field->value = sprintf($this->_('On %s by %s'), wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $apikey->getCreated()), $apikey->getCreatedUserLink());
$field->collapsed = Inputfield::collapsedNever;
$form->prepend($field);

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
		href='<?= $this->wire('page')->url . 'application/edit/' . $apikey->getApplicationID(); ?>'><i
			class="fa fa-arrow-left"></i>&nbsp;<?= $this->_('Go Back'); ?></a>
</p>