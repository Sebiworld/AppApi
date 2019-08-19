<?php

namespace ProcessWire;

?>

<dl class="uk-description-list uk-description-list-divider">
	<h2><small>What do you want to do?</small></h2>

	<?php
    if (wire('user')->hasPermission(RestApi::manageApplicationsPermission)) {
        ?>
		<dt>
			<a style="display: flex; align-items: center;" class="label" href="./applications/">
				<i style="margin-right: 10px;" class="fa fa-2x fa-fw fa-plug ui-priority-secondary"></i>
				Manage Applications
			</a>
		</dt>
		<dd></dd>
	<?php
    }

    if (wire('user')->hasPermission('logs-view')) {
        ?>
			<dt>
				<a style="display: flex; align-items: center;" class="label" href="<?= wire('config')->urls->admin ?>setup/logs/view/rest_api/">
					<i style="margin-right: 10px; text-decoration: none;" class="fa fa-2x fa-fw fa-code ui-priority-secondary"></i>
					Logs
				</a>
			</dt>
			<dd>Access the log files of RestApi</dd>
		<?php
    }
	?>
	
	<dt>
		<a style="display: flex; align-items: center;" class="label" target="_blank" href="https://modules.processwire.com/modules/rest-api/">
			<i style="margin-right: 10px;" class="fa fa-2x fa-fw fa-globe ui-priority-secondary"></i>
			ProcessWire-Module
		</a>
	</dt>
	<dd>This module in ProcessWire's official modules-directory</dd>

	<dt>
		<a style="display: flex; align-items: center;" class="label" target="_blank" href="https://processwire.com/talk/topic/20006-module-restapi/">
			<i style="margin-right: 10px;" class="fa fa-2x fa-fw fa-commenting-o ui-priority-secondary"></i>
			Official support-thread
		</a>
	</dt>
	<dd></dd>
</dl>