<?php
\OCP\Util::addScript('ocsms', 'script');
\OCP\Util::addStyle('ocsms', 'style');
?>

<div id="app">
	<div id="app-mailbox-peers">
		<ul>
			<li><div id="ocsms-empty-peers">There isn't any conversation</div></li>
		</ul>
	</div>
	<div id="app-content">
		<div id="app-content-header" style="display: none;">	
			<div id="ocsms-phone-label"></div>
			<div id="ocsms-phone-opt-number"></div>
		</div>
		<div id="app-content-wrapper">
			<div id="ocsms-empty-conversation">Please choose a conversation on the left menu</div>
		</div>
	</div>
</div>
