<?php

namespace ProcessWire;

?>

	<?php
    if (wire('user')->hasPermission(AppApi::manageApplicationsPermission)) {
        ?>
		<dl class="uk-description-list uk-description-list-divider" style="margin-bottom: 50px;">
			<h2><small><?= $this->_('What would you like to do?'); ?></small></h2>
			<dt>
				<a style="display: flex; align-items: center; text-decoration: none;" class="label" href="./applications/">
					<i style="margin-right: 10px;" class="fa fa-2x fa-fw fa-plug ui-priority-secondary"></i>
					<?= $this->_('Manage Applications'); ?>
				</a>
			</dt>
			<dd></dd>

			<dt>
				<a style="display: flex; align-items: center; text-decoration: none;" class="label" href="<?= $this->wire('page')->url . 'application/new'; ?>">
					<i style="margin-right: 10px;" class="fa fa-2x fa-fw fa-plus ui-priority-secondary"></i>
					<?= $this->_('Add a new Application'); ?>
				</a>
			</dt>
			<dd></dd>

			<dt>
				<a style="display: flex; align-items: center; text-decoration: none;" class="label" href="<?= $configUrl; ?>">
					<i style="margin-right: 10px;" class="fa fa-2x fa-fw fa-gear ui-priority-secondary"></i>
					<?= $this->_('Configure Module'); ?>
				</a>
			</dt>
			<dd></dd>
		</dl>
	<?php
    }

    if (wire('user')->hasPermission('logs-view') && (
			isset($existingLogs['appapi-exception']['modified']) ||
			isset($existingLogs['appapi-access']['modified'])
		)) {
        ?>
				<dl class="uk-description-list uk-description-list-divider" style="margin-bottom: 50px;">
					<h2><small><?= $this->_('Logs: '); ?></small></h2>
					<?php
            if (isset($existingLogs['appapi-access']['modified'])) {
              ?>
								<dt>
									<a style="display: flex; align-items: center; text-decoration: none;" class="label" href="<?= wire('config')->urls->admin ?>setup/logs/view/appapi-access/">
										<i style="margin-right: 10px; text-decoration: none;" class="fa fa-2x fa-fw fa-code ui-priority-secondary"></i>
										<?= $this->_('Access-Log'); ?>
									</a>
								</dt>
								<dd style="margin-top: 12px;">
									<i><?= $this->_('Last entry: '); ?><?= wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $existingLogs['appapi-access']['modified']); ?></i>
								</dd>
							<?php
						}

            if (isset($existingLogs['appapi-exception']['modified'])) {
                ?>
								<dt>
									<a style="display: flex; align-items: center; text-decoration: none;" class="label" href="<?= wire('config')->urls->admin ?>setup/logs/view/appapi-exception/">
										<i style="margin-right: 10px; text-decoration: none;" class="fa fa-2x fa-fw fa-code ui-priority-secondary"></i>
										<?= $this->_('Exception-Log'); ?>
									</a>
								</dt>
								<dd style="margin-top: 12px;">
									<i><?= $this->_('Last entry: '); ?><?= wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $existingLogs['appapi-exception']['modified']); ?></i>
								</dd>
							<?php
            } ?>
				</dl>
				<?php
    }
  ?>

<dl class="uk-description-list uk-description-list-divider">
	<h2><small><?= $this->_('Links: '); ?></small></h2>
	<dt>
		<a style="display: flex; align-items: center; text-decoration: none;" class="label" target="_blank" href="https://processwire.com/talk/topic/24014-new-module-appapi/">
			<i style="margin-right: 10px;" class="fa fa-2x fa-fw fa-commenting-o ui-priority-secondary"></i>
			<?= $this->_('Official support-thread'); ?>
		</a>
	</dt>
	<dd style="margin-top: 12px;">
		<?= $this->_('Here you can ask questions and share experiences  with other users'); ?>
	</dd>

	<dt>
		<a style="display: flex; align-items: center; text-decoration: none;" class="label" target="_blank" href="https://modules.processwire.com/modules/app-api/">
			<i style="margin-right: 10px;" class="fa fa-2x fa-fw fa-globe ui-priority-secondary"></i>
			<?= $this->_('AppApi ProcessWire-Module'); ?>
		</a>
	</dt>
	<dd style="margin-top: 12px;"><?= $this->_('AppApi in ProcessWire\'s official modules-directory'); ?></dd>

	<dt>
		<a style="display: flex; align-items: center; text-decoration: none;" class="label" target="_blank" href="https://github.com/Sebiworld/AppApi
">
			<i style="margin-right: 10px;" class="fa fa-2x fa-fw fa-github ui-priority-secondary"></i>
			<?= $this->_('AppApi on Github'); ?>
		</a>
	</dt>
	<dd style="margin-top: 12px;"><?= $this->_('Do you want to help with the development of the module? Then the Github repository is the right place to open issues or submit pull requests. Don\'t be shy! I am happy about every contribution!'); ?></dd>
</dl>