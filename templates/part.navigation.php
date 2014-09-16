<ul>
	<?php foreach ($_['mailboxes'] as $mboxName => $mailbox) { ?>
		<?php if (count($mailbox['phoneNumbers']) > 0) { ?>
		<li><a href="<?php p($mailbox['url']); ?>" nav-feed="<?php p($mailbox['label']); ?>"><?php p($mailbox['label']); ?></a></li>
	<?php }
	} ?>
</ul>
