<?php
\OCP\Util::addScript('ocsms', 'script');
\OCP\Util::addStyle('ocsms', 'style');
?>

<div id="app">
	<div id="app-navigation">
		<?php print_unescaped($this->inc('part.navigation')); ?>
		<?php /*print_unescaped($this->inc('part.settings'));*/ ?>
	</div>

	<div id="app-mailbox-peers">
	</div>

	<div id="app-content">
		<div id="app-content-wrapper">
			<?php print_unescaped($this->inc('part.content')); ?>
		</div>
	</div>
</div>
