<ul>
	<?php foreach ($_['mailboxes'] as $mboxName => $mailbox) { ?>
		<?php if (count($mailbox['phoneNumbers']) > 0) { ?>
		<li><a href="<?php p($mailbox['url']); ?>"><?php p($mailbox['label']); ?></a>
		<ul>
		<?php foreach ($mailbox['phoneNumbers'] as $number) { ?>
			<li><?php p($number); ?></li>
		<?php } ?>
		</ul></li>
		<?php }
	} ?>
</ul>
