<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<div align="center" style="padding-top:10px;">
	<?php echo html::image('application/views/themes/default/icons/icon.png',''); ?>

	<h1><?php echo _('Livestatus query failed'); ?></h1>

	<p><?php echo sprintf(_('A livestatus query failed. Make sure <strong>Nagios is running</strong>, <strong>livestatus is loaded</strong>, and that <strong>livestatus is configured to create the socket "%s"</strong>'), preg_replace("~^unix://~", null, Kohana::config('database.livestatus.path'))); ?></p>
</div>
