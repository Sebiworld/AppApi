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
			isset($existingLogs[AppApi::logExceptions]['modified']) ||
			isset($existingLogs[AppApi::logAccess]['modified'])
		)) {
        ?>
				<dl class="uk-description-list uk-description-list-divider" style="margin-bottom: 50px;">
					<h2><small><?= $this->_('Logs: '); ?></small></h2>
					<?php
            if (isset($existingLogs[AppApi::logAccess]['modified'])) {
              ?>
								<dt>
									<a style="display: flex; align-items: center; text-decoration: none;" class="label" href="<?= wire('config')->urls->admin ?>setup/logs/view/<?= AppApi::logAccess; ?>/">
										<i style="margin-right: 10px; text-decoration: none;" class="fa fa-2x fa-fw fa-code ui-priority-secondary"></i>
										<?= $this->_('Access-Log'); ?>
									</a>
								</dt>
								<dd style="margin-top: 12px;">
									<?= $this->_('Access-Logging is '); ?>
									<?php
									if($accesslogsActivated){
										echo '<strong style="color: green;">' . $this->_('ENABLED') . '</strong>';
									}else{
										echo '<strong style="color: red;">' . $this->_('DISABLED') . '</strong>';
									}
									?><br>
									<i><?= $this->_('Last entry: '); ?><?= wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $existingLogs[AppApi::logAccess]['modified']); ?></i>
								</dd>
							<?php
						}

            if (isset($existingLogs[AppApi::logExceptions]['modified'])) {
                ?>
								<dt>
									<a style="display: flex; align-items: center; text-decoration: none;" class="label" href="<?= wire('config')->urls->admin ?>setup/logs/view/<?= AppApi::logExceptions; ?>/">
										<i style="margin-right: 10px; text-decoration: none;" class="fa fa-2x fa-fw fa-code ui-priority-secondary"></i>
										<?= $this->_('Exception-Log'); ?>
									</a>
								</dt>
								<dd style="margin-top: 12px;">
									<i><?= $this->_('Last entry: '); ?><?= wire('datetime')->date($this->_('Y-m-d @ H:i:s'), $existingLogs[AppApi::logExceptions]['modified']); ?></i>
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

	<dt>
		<a style="display: flex; align-items: center; text-decoration: none;" class="label" target="_blank" href="https://github.com/Sebiworld/AppApi/wiki
">
			<i style="margin-right: 10px;" class="fa fa-2x fa-fw fa-book ui-priority-secondary"></i>
			<?= $this->_('AppApi-Wiki'); ?>
		</a>
	</dt>
	<dd style="margin-top: 12px;"><?= $this->_('Official documentation'); ?></dd>
</dl>