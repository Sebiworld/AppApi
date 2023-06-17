<?php
namespace ProcessWire;

if (isset($locked) && $locked === true) {
	echo '<h2>' . $this->_('Access denied') . '</h2>';
	return;
}
if (!wire('user')->hasPermission(AppApi::manageApplicationsPermission)) {
	echo '<h2>' . $this->_('Access denied') . '</h2>';
	echo '<p>' . $this->_('You don\'t have the needed permissions to access this function. Please contact a Superuser.') . '</p>';
	return;
}

$table = $modules->get('MarkupAdminDataTable');
$table->setEncodeEntities(false);

$table->headerRow([
	$this->_('Title'),
	$this->_('Auth-Type'),
	$this->_('Created'),
	$this->_('Created by'),
	$this->_('Default'),
	$this->_('Actions')
]);

if ($applications instanceof WireArray && $applications->count > 0) {
	foreach ($applications as $app) {
		$row = [
			'<a href="' . $this->wire('page')->url . 'application/edit/' . $app->getID() . '">' . $app->getTitle() . '</a>',
			Application::getAuthtypeLabel($app->getAuthtype()),
			wire('datetime')->date('', $app->getCreated()),
			$app->getCreatedUserLink(),
			$app->isDefaultApplication() ? '<i class="fa fa-check-circle"></i>' : '<i class="fa fa-circle-thin"></i>',
			'<a href="' . $this->wire('page')->url . 'application/delete/' . $app->getID() . '"><i class="fa fa-trash"></i></a>',
		];

		$table->row($row);
	}

	$tableOutput = $table->render();
}

if (empty($tableOutput)) {
	$tableOutput = '<p><i>' . $this->_('There are no applications that can access the AppApi.') . '</i><br/><u><a href="' . $this->process_url . '../application/new/">' . $this->_('Create the first application!') . '</a></u></p>';
}

echo $tableOutput;

// Build form:
$form = $this->wire('modules')->get('InputfieldForm');
$form->method = 'POST';
$form->action = $this->wire('page')->url . 'applications/';

$field = $this->modules->get('InputfieldButton');
$field->type = 'button';
$field->value = 'Refresh';
$field->name = 'action-refresh';
$field->href = $this->wire('page')->url . 'applications/';
$field->header = true;
$field->icon = 'refresh';
$form->add($field);

$field = $this->modules->get('InputfieldButton');
$field->type = 'button';
$field->value = 'Add';
$field->name = 'action-add';
$field->href = $this->wire('page')->url . 'application/new/';
$field->icon = 'plus-circle';
// $field->header    = true;
$field->secondary = false;
$form->add($field);

echo $form->render();
?>

<p style='padding-top: 20px;'>
	<a href='<?= $this->wire('page')->url; ?>'>
		<i
			class="fa fa-arrow-left"></i>&nbsp;<?= $this->_('Go Back'); ?>
	</a>
</p>