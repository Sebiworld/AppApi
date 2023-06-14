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
$table->addClass('endpoints-table');
$table->setSortable(false);

$table->headerRow([
	$this->_('Path'),
	$this->_('Method'),
	$this->_('Description'),
	$this->_('Handler')
]);

$openApiOutput = [
	'openapi' => '3.0.3',
	'info' => [
		'title' => 'AppApi Endpoints',
		'version' => '1.0.0'
	],
	'paths' => []
];
if (isset($endpoints) && is_array($endpoints)) {
	foreach ($endpoints as $endpointPath => $endpoint) {
		$table->row([
			'<strong>' . $endpointPath . '</strong>',
			'',
			'',
			''
		], [
			'class' => 'group-row'
		]);

		$openApiOutput['paths'][$endpointPath] = [];

		foreach ($endpoint as $index => $child) {
			if (empty($child[0]) || !is_string($child[0])) {
				continue;
			}

			if (!empty($child[5]) && is_array($child[5])) {
				$methodLowercase = strtolower($child[0]);
				$openApiOutput['paths'][$endpointPath][$methodLowercase] = $child[5] ?? [];

				if (empty($openApiOutput['paths'][$endpointPath][$methodLowercase]['responses'])) {
					$openApiOutput['paths'][$endpointPath][$methodLowercase]['responses'] = [
						'200' => [
							'description' => 'Successfull operation'
						]
					];
				}
			}

			// Build method cell:
			$method = $child[0];
			$methodStyles = [];
			$methodAddition = '';
			if ($method === 'OPTIONS' && !empty($child[2]) && is_array($child[2])) {
				$methodAddition = '<br><small>(' . implode(', ', $child[2]) . ')</small>';
			} else if ($method === 'GET') {
				$methodStyles[] = 'background: #61affe';
				$methodStyles[] = 'color: #ffffff';
			} else if ($method === 'POST') {
				$methodStyles[] = 'background: #fca130';
				$methodStyles[] = 'color: #ffffff';
			} else if ($method === 'UPDATE' || $method === 'PUT' || $method === 'PATCH') {
				$methodStyles[] = 'background: #49cc90';
				$methodStyles[] = 'color: #ffffff';
			} else if ($method === 'DELETE') {
				$methodStyles[] = 'background: #f93e3e';
				$methodStyles[] = 'color: #ffffff';
			}

			$method = '<code class="method-indicator" style="' . implode(';', $methodStyles) . '">' . $method . $methodAddition . '</code>';

			// Build description cell:
			$description = '<strong>' . ($child[5]['summary'] ?? '') . '</strong>';
			if (!empty($child[5]['description'])) {
				$description .= '<br>' . $child[5]['description'];
			}

			// Build handler cell:
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

			if (!empty($child[6]) && is_array($child[6])) {
				$traceParts = [];
				if (isset($child[6]['file'])) {
					$traceParts[] = $this->_('Endpoint registered in') . ' ' . $child[6]['file'];
				}
				if (isset($child[6]['line'])) {
					$traceParts[] = $this->_('line') . ' ' . $child[6]['line'];
				}

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
			if (!empty($settings)) {
				$handler .= '<br>' . $this->_('Settings: ') . '<code>' . json_encode($settings) . '</code>';
			}

			$table->row([
				'<strong style="color: transparent;">' . $endpointPath . '</strong>',
				$method,
				$description,
				$handler
			], [
				'class' => 'method-row' . ($index === 0 ? ' first' : '')
			]);
		}

		if (empty($openApiOutput['paths'][$endpointPath])) {
			unset($openApiOutput['paths'][$endpointPath]);
		}
	}

	$tableOutput = $table->render();
}

if (empty($tableOutput)) {
	$tableOutput = '<p><i>' . $this->_('There are no endpoints set for your api') . '</i></p>';
}

if ($action === 'action-get-openapi') {
	echo '<pre>';
	echo json_encode($openApiOutput, JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES);
	echo '</pre>';
	die();
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
$field->secondary = true;
$form->add($field);

$toolbarOutput = $form->render();
?>

<div class="appapi-content-wrapper">

	<p style="width: 42em; max-width: 100%;">
		<?= $this->_('These are all enpoints that are registered to be handled by AppApi.'); ?>
	</p>
	<p style="width: 42em; max-width: 100%;">
		<?= AppApi::replacePlaceholders(
	$this->_('{{l1}}Here{{closelink}} you will learn how to create your own Api endpoints in routes.php. In addition, other ProcessWire modules can {{l2}}register{{closelink}} their own endpoints with AppApi, which will then also be listed here. You can even overwrite the default Routes with your custom implementations!'),
	[
		'l1' => '<a href="https://github.com/Sebiworld/AppApi/wiki/3.0:-Creating-Endpoints" target="_blank">',
		'l2' => '<a href="https://github.com/Sebiworld/AppApi/wiki/4.0:-AppApi-Modules" target="_blank">',
		'closelink' => '</a>',
	]
);
?>
	</p>

	<div class="content-box">
		<p style="width: 42em; max-width: 100%;">
			<?= AppApi::replacePlaceholders(
	$this->_('Did you think about {{l1}}adding a proper documentation array{{closelink}} to your custom endpoints? AppApi has a fully functioning {{l2}}OpenAPI 3.0.3 Spec{{closelink}} JSON for you that can be used with {{l3}}Swagger{{closelink}} or similar tools.'),
	[
		'l1' => '<a href="https://github.com/Sebiworld/AppApi/wiki/3.4:-Add-Documentation" target="_blank">',
		'l2' => '<a href="https://spec.openapis.org/oas/v3.0.3" target="_blank" rel="noreferrer">',
		'l3' => '<a href="https://swagger.io/" target="_blank" rel="noreferrer">',
		'closelink' => '</a>',
	]
);
?>
			<a class="inline-action-button"
				href="<?= $this->wire('page')->url . 'endpoints/action-get-openapi/'; ?>"
				target="_blank">
				<button class="ui-button ui-widget ui-corner-all ui-state-default" name="action-get-openapi"
					value="Get OpenAPI JSON" type="button">
					<span class="ui-button-text">
						<i class="fa fa-database"></i>
						<?= $this->_('Get OpenAPI JSON'); ?>
					</span>
				</button>
			</a>
		</p>
	</div>

	<?= $tableOutput; ?>
	<?= $toolbarOutput; ?>

	<p style='padding-top: 20px;'>
		<a
			href='<?= $this->wire('page')->url; ?>'>
			<i
				class="fa fa-arrow-left"></i>&nbsp;<?= $this->_('Go Back'); ?>
		</a>
	</p>
</div>