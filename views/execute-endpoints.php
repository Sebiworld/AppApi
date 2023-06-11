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
	$this->_('Path'),
	$this->_('Method'),
	$this->_('Handler'),
	$this->_('Settings')
]);

if (isset($endpoints) && is_array($endpoints)) {
	foreach ($endpoints as $endpointKey => $endpoint) {
		foreach ($endpoint as $index => $child) {
			$method = $child[0];
			$methodStyles = [
				'display: inline-block',
				'position: relative',
				'padding: 8px 24px',
				'border-radius: 4px'
			];
			$methodAddition = '';
			if ($method === 'OPTIONS' && !empty($child[2]) && is_array($child[2])) {
				$methodAddition = '<br><small>(' . implode(', ', $child[2]) . ')</small>';
				$methodStyles[] = 'background: #8d939e';
				$methodStyles[] = 'color: #ffffff';
			} else if ($method === 'GET') {
				$methodStyles[] = 'background: #61affe';
				$methodStyles[] = 'color: #ffffff';
			} else if ($method === 'POST') {
				$methodStyles[] = 'background: #49cc90';
				$methodStyles[] = 'color: #ffffff';
			} else if ($method === 'UPDATE' || $method === 'PUT' || $method === 'PATCH') {
				$methodStyles[] = 'background: #fca130';
				$methodStyles[] = 'color: #ffffff';
			} else if ($method === 'DELETE') {
				$methodStyles[] = 'background: #f93e3e';
				$methodStyles[] = 'color: #ffffff';
			} else {
				$methodStyles[] = 'background: #d9e1ea';
				$methodStyles[] = 'color: #354b60';
			}

			$method = '<code style="' . implode(';', $methodStyles) . '">' . $method . $methodAddition . '</code>';

			$handler = '';
			if (!empty($child[2]) && !is_array($child[2])) {
				$handler = $child[2];
			}
			if (!empty($child[3]) && !is_array($child[3])) {
				$handler .= '::' . $child[3] . '()';
			}
			if (!empty($handler)) {
				$handler = '<code>' . $handler . '</code>';
			}

			if (!empty($child[5]) && is_array($child[5])) {
				$traceParts = [];
				if (isset($child[5]['file'])) {
					$traceParts[] = $this->_('Defined in') . ' ' . $child[5]['file'];
				}
				if (isset($child[5]['line'])) {
					$traceParts[] = $this->_('line') . ' ' . $child[5]['line'];
				}
				// if (isset($child[5]['class'])) {
				// 	$classPart = $child[5]['class'];
				// 	if (isset($child[5]['function'])) {
				// 		$classPart .= '::' . $child[5]['function'] . '()';
				// 	}

				// 	$traceParts[] = '<br>' . $classPart;
				// }

				if (!empty($traceParts)) {
					if (!empty($handler)) {
						$handler .= '<br>';
					}
					$handler .= '<i><small>' . implode(', ', $traceParts) . '</small></i>';
				}
			}

			$settings = [];
			if (!empty($child[4]) && is_array($child[4])) {
				foreach ($child[4] as $key => $value) {
					$settings[] = $key . ': ' . json_encode($value);
				}
			}

			$table->row([
				'<strong ' . ($index === 0 ? '' : 'style="color: transparent;"') . '>' . $endpointKey . '</strong>',
				$method,
				$handler,
				!empty($settings) ? '<code>' . json_encode($settings) . '</code>' : ''
			], [
				'separator' => $index === 0
			]);
		}
	}

	$tableOutput = $table->render();
}

if (empty($tableOutput)) {
	$tableOutput = '<p><i>' . $this->_('There are no endpoints set for your api') . '</i></p>';
}

// Build form:
$form = $this->wire('modules')->get('InputfieldForm');
$form->method = 'POST';
$form->action = $this->wire('page')->url . 'endpoints/';

$field = $this->modules->get('InputfieldButton');
$field->type = 'button';
$field->value = 'Refresh';
$field->name = 'action-refresh';
$field->href = $this->wire('page')->url . 'endpoints/';
$field->header = true;
$field->icon = 'refresh';
$form->add($field);

$toolbarOutput = $form->render();
?>

<p style="width: 42em; max-width: 100%;">
  <?= $this->_('These are all enpoints that are registered to be handled by AppApi.'); ?>
</p>
<p style="width: 42em; max-width: 100%;">
  <?= AppApi::replacePlaceholders(
	$this->_('{{l1}}Here{{/l1}} you will learn how to create your own Api endpoints in routes.php. In addition, other ProcessWire modules can {{l2}}register{{/l2}} their own endpoints with AppApi, which will then also be listed here.'),
	[
		'l1' => '<a href="https://github.com/Sebiworld/AppApi/wiki/3.0:-Creating-Endpoints" target="_blank">',
		'/l1' => '</a>',
		'l2' => '<a href="https://github.com/Sebiworld/AppApi/wiki/4.0:-AppApi-Modules" target="_blank">',
		'/l2' => '</a>'
	]
);
?>
</p>

<?= $tableOutput; ?>

<?= $toolbarOutput; ?>

<p style='padding-top: 20px;'>
  <a href='<?= $this->wire('page')->url; ?>'>
    <i
      class="fa fa-arrow-left"></i>&nbsp;<?= $this->_('Go Back'); ?>
  </a>
</p>

<pre>
  <?php var_dump($endpoints); ?>
</pre>