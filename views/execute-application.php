<?php

namespace ProcessWire;

if (!wire('user')->hasPermission(RestApi::manageApplicationsPermission)) {
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
    $application = new Application();
    $application->regenerateTokenSecret();
    $application->regenerateAccesstokenSecret();
}

if (!$application->isNew()) {
    $apikeysTable = $modules->get('MarkupAdminDataTable');
    $apikeysTable->setEncodeEntities(false);

    $apikeysTable->headerRow(array(
        $this->_('Key'),
        $this->_('Version'),
        $this->_('Created'),
        $this->_('Created by'),
        $this->_('Accessable until'),
        $this->_('Actions')
    ));

    $apikeys = $application->getApikeys();

    if ($apikeys instanceof WireArray && $apikeys->count > 0) {
        foreach ($apikeys as $apikey) {
            $row = array(
                '<a href="' . $this->wire('page')->url . 'apikey/edit/' . $apikey->getID() . '">' . $apikey->getKey() . '</a>',
                $apikey->getVersion(),
                wire('datetime')->date('', $apikey->getCreated()),
                $apikey->getCreatedUserLink(),
                wire('datetime')->date('', $apikey->getAccessableUntil()),
                '<a href="' . $this->wire('page')->url . 'apikey/delete/' . $apikey->getID() . '"><i class="fa fa-trash"></i></a>'
            );

            $apikeysTable->row($row);
        }

        $apikeysTableOutput = $apikeysTable->render();
    }

    if (empty($apikeysTableOutput)) {
        $apikeysTableOutput = '<p><i>' . $this->_('There are no apikeys set for this application.') . '</i></p>';
    }

    $button        = $this->modules->get('InputfieldButton');
    $button->value = $this->_('Add new Apikey');
    $button->icon  = 'plus';
    $button->setSmall(true);
    $button->attr('href', $this->wire('page')->url . 'apikey/new/' . $application->getID());
    $apikeysTableOutput .= $button->render();
} else {
    $apikeysTableOutput = '<p><i>' . $this->_('You can add apikeys after saving the application the first time.') . '</i></p>';
}

// Build form:
$form         = $this->modules->get('InputfieldForm');
$form->method = 'POST';
$form->action = $application->isNew() ? $this->wire('page')->url . 'application/new' : $this->wire('page')->url . 'application/edit/' . $application->getID();

// Title
$field              = $this->modules->get('InputfieldText');
$field->label       = $this->_('Title');
$field->attr('id+name', 'form_title');
$field->columnWidth     = '100%';
$field->required        = 1;
$field->value           = $application->getTitle();
$field->collapsed       = Inputfield::collapsedNever;
$form->add($field);

// Description:
$field              = $this->modules->get('InputfieldTextarea');
$field->label       = $this->_('Description');
$field->description = $this->_('Use this field for personal notes. Neither does it affect anything nor will it be visible anywhere else than here.');
$field->attr('id+name', 'form_description');
$field->columnWidth = '100%';
$field->required    = 0;
$field->value       = $application->getDescription();
$field->collapsed   = Inputfield::collapsedBlank;
$form->add($field);

// Authtype:
$field              = $this->modules->get('InputfieldRadios');
$field->label       = $this->_('Auth-Type');
$field->attr('id+name', 'form_authtype');
$field->columnWidth = 20;
$field->required    = 1;
$field->addOptions(array(
    Application::authtypeSession                => Application::getAuthtypeLabel(Application::authtypeSession),
    Application::authtypeSingleJWT              => Application::getAuthtypeLabel(Application::authtypeSingleJWT),
    Application::authtypeDoubleJWT              => Application::getAuthtypeLabel(Application::authtypeDoubleJWT)
));
$field->value           = $application->getAuthtype();
$field->collapsed       = Inputfield::collapsedNever;
$form->add($field);

$descriptionString = '';
$descriptionString .= '<p><strong>' . Application::getAuthtypeLabel(Application::authtypeSession) . ':</strong><br/>' . $this->_('A normal website-application can be used to access public data with only a valid apikey. Protected data will be authorized with classic php-sessions. If you are logged in, you can use protected apipages.') . '</p>';
$descriptionString .= '<p><strong>' . Application::getAuthtypeLabel(Application::authtypeSingleJWT) . ':</strong><br/>' . $this->_('A protected website-application shows contents only if you have a valid JWT-token that authorizes you to use an endpoint. A JWT-token should be requested via PHP and has to be transferred to JS on pageload (e.g. as a special data-attribute). It can be limited to only those special endpoints that the api function uses. The JWT-token is linked to the php-session so it has a limited livetime. With protectedWebsite-endpoints we can prevent that api-access via token can be used for general apicalls from other services.') . '</p>';
$descriptionString .= '<p><strong>' . Application::getAuthtypeLabel(Application::authtypeDoubleJWT) . ':</strong></br>' . $this->_('A classic app allows users to log in and authenticate via refresh- and access-tokens afterwards. Public contents are available with only a valid apikey.') . '</p>';

$field              = $this->modules->get('InputfieldMarkup');
$field->attr('id+name', 'form_descriptions');
$field->label       = 'Description';
$field->columnWidth = 80;
$field->value       = $descriptionString;
$field->collapsed   = Inputfield::collapsedNever;
$field->textFormat  = Inputfield::textFormatBasic;
$field->skipLabel   = Inputfield::skipLabelHeader;
$form->add($field);

// Apikeys:
$field                  = $this->modules->get('InputfieldMarkup');
$field->attr('id+name', 'form_apikeys');
$field->label       = $this->_('Apikeys');
$field->value       = $apikeysTableOutput;
$field->columnWidth = 100;
$form->add($field);

// Expires In:
$field              = $this->modules->get('InputfieldInteger');
$field->label       = $this->_('Expires In');
$field->attr('id+name', 'form_expires_in');
$field->columnWidth     = '100%';
$field->required        = 1;
$field->value           = $application->getExpiresIn();
$field->collapsed       = Inputfield::collapsedNever;
$form->add($field);

// Token-Secret:
$field              = $this->modules->get('InputfieldText');
$field->label       = $this->_('Token Secret');
$field->attr('id+name', 'form_token_secret');
$field->columnWidth = '100%';
$field->required    = 1;
$field->value       = $application->getTokenSecret();
$field->collapsed   = Inputfield::collapsedPopulated;
$field->description = $this->_('The token secret is used to sign and verify your jwt-tokens. Do not make this token public - you dont need to have it somewhere else than the backend. Changing the secret will invalidate all tokens that were generated on its base.');
$field->notes       = $this->_('Use a token secret which is longer than 10 characters.');
$field->minlength   = 10;
$field->maxlength   = 100;
$field->showCount   = InputfieldText::showCountChars;
$form->add($field);

// Accesstoken-Secret:
$field              = $this->modules->get('InputfieldText');
$field->label       = $this->_('Accesstoken Secret');
$field->attr('id+name', 'form_accesstoken_secret');
$field->columnWidth = '100%';
$field->required    = 1;
$field->value       = $application->getAccesstokenSecret();
$field->collapsed   = Inputfield::collapsedPopulated;
$field->description = $this->_('The token secret is used to sign and verify your jwt-accesstokens. Do not make this token public - you dont need to have it somewhere else than the backend. Changing the secret will invalidate all accesstokens that were generated on its base.');
$field->notes       = $this->_('Use a accesstoken secret which is longer than 10 characters.');
$field->minlength   = 10;
$field->maxlength   = 100;
$field->showCount   = InputfieldText::showCountChars;
$form->add($field);

// Submit-Buttons:
$submitButton         = $this->modules->get('InputfieldButton');
$submitButton->type   = 'submit';
$submitButton->value  = 'save';
$submitButton->icon   = 'floppy-o';
$submitButton->name   = 'action-save';
$submitButton->header = false;
$form->add($submitButton);

if (!$application->isNew()) {
    $button            = $this->modules->get('InputfieldButton');
    $button->href      = $this->page->url . 'application/delete/' . $application->getID();
    $button->value     = 'delete';
    $button->icon      = 'trash-o';
    $button->name      = 'action-delete';
    $button->secondary = true;
    $form->add($button);
}

if (wire('input')->post('action-save')) {
    // form submitted
    $form->processInput(wire('input')->post);
    $errors   = $form->getErrors();
    $messages = array();

    if (count($errors)) {
        // The submitted form-data has errors
        foreach ($errors as $error) {
            $this->session->error($error);
        }
    } else {
        // The submitted form has no errors. We can save the application.

        try {
            $doRedirect = $application->isNew();

            $application->setModifiedUser(wire('user'));
            $application->setTitle($form->get('form_title')->attr('value'));
            $application->setDescription($form->get('form_description')->attr('value'));
            $application->setTokenSecret($form->get('form_token_secret')->attr('value'));
            $application->setAccesstokenSecret($form->get('form_accesstoken_secret')->attr('value'));
            $application->setExpiresIn($form->get('form_expires_in')->attr('value'));
            $application->setAuthtype($form->get('form_authtype')->attr('value'));

            if (!$application->save()) {
                throw new \Exception('The application could not be saved.');
            }

            $this->notices->add(new NoticeMessage($application->isNew() ? $this->_('The application was successfully created.') : $this->_('The changes to your application were saved.')));

            if ($doRedirect) {
                $this->session->redirect($this->wire('page')->url . 'application/edit/' . $application->getID());
            }
        } catch (\Exception $e) {
            $this->session->error($e->getMessage());
        }
    }
}

if (!$application->isNew()) {
    $field              = $this->modules->get('InputfieldMarkup');
    $field->label       = $this->_('ID');
    $field->columnWidth = '20%';
    $field->value       = $application->getID();
    $field->collapsed   = Inputfield::collapsedNever;
    $form->prepend($field);
}

// Created- and Modified-Output is added after submission-handling, because only then the modified-date will have the correct time:
$field              = $this->modules->get('InputfieldMarkup');
$field->label       = $this->_('Modified');
$field->columnWidth = '40%';
$field->value       = sprintf($this->_('On %s by %s'), wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $application->getModified()), $application->getModifiedUserLink());
$field->collapsed   = Inputfield::collapsedNever;
$form->prepend($field);

$field              = $this->modules->get('InputfieldMarkup');
$field->label       = $this->_('Created');
$field->columnWidth = '40%';
$field->value       = sprintf($this->_('On %s by %s'), wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $application->getCreated()), $application->getCreatedUserLink());
$field->collapsed   = Inputfield::collapsedNever;
$form->prepend($field);

if (!$application->isNew() && $application->getAuthtype() === Application::authtypeDoubleJWT) {
    $apptokensTable = $modules->get('MarkupAdminDataTable');
    $apptokensTable->setEncodeEntities(false);

    $apptokensTable->headerRow(array(
        $this->_('User'),
        $this->_('id'),
        $this->_('last used'),
        $this->_('Actions')
    ));

    $apptokens = $application->getApptokens();

    if ($apptokens instanceof WireArray && $apptokens->count > 0) {
        foreach ($apptokens as $apptoken) {
            $row = array(
                $apptoken->getUserLink(),
                '<a href="' . $this->wire('page')->url . 'apptoken/edit/' . $apptoken->getID() . '">' . $apptoken->getTokenId() . '</a>',
                $apptoken->getLastUsed() !== null ? wire('datetime')->date('', $apptoken->getLastUsed()) : '-',
                '<a href="' . $this->wire('page')->url . 'apptoken/delete/' . $apptoken->getID() . '"><i class="fa fa-trash"></i></a>'
            );

            $apptokensTable->row($row);
        }

        $apptokensTableOutput = $apptokensTable->render();
    }

    if (empty($apptokensTableOutput)) {
        $apptokensTableOutput = '<p><i>' . $this->_('There are no apptokens set for this application.') . '</i></p>';
    }

    $button        = $this->modules->get('InputfieldButton');
    $button->value = $this->_('Add new Apptoken');
    $button->icon  = 'plus';
    $button->setSmall(true);
    $button->attr('href', $this->wire('page')->url . 'apptoken/new/' . $application->getID());
    $apptokensTableOutput .= $button->render();

    $field                  = $this->modules->get('InputfieldMarkup');
    $field->attr('id+name', 'form_apptokens');
    $field->label       = $this->_('Apptokens');
    $field->value       = $apptokensTableOutput;
    $field->columnWidth = 100;
    $form->insertBefore($field, $submitButton);
}

// Output errors:
if (isset($messages['errors']) && is_array($messages['errors'])) {
    ?>
	<div class="NoticeError" style="padding: 5px 10px;">
		<strong><?= $this->_('The form has errors: '); ?></strong><br/>
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
	<a href='<?= $this->wire('page')->url . 'applications/'; ?>'><i class="fa fa-arrow-left"></i>&nbsp;<?= $this->_('Go Back'); ?></a>
</p>
