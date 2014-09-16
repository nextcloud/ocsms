<?php
\OCP\Util::addScript('ocsms', 'script');
\OCP\Util::addStyle('ocsms', 'style');
?>

<div id="app">
	<div id="app-navigation">
		<?php print_unescaped($this->inc('part.navigation')); ?>
	</div>

	<div id="app-mailbox-peers">
		<ul>
		</ul>
	</div>

	<div id="app-content">
		<div id="app-content-wrapper">
			No conversation loaded
		</div>
	</div>
</div>
