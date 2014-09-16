<ul>
	<?php if (count($_["PNLConversations"]) > 0) { ?>
	<li><a href="#">Conversations</a>
	<ul>
	<?php foreach ($_["PNLConversations"] as $number) { ?>
		<li><?php p($number); ?></li>
	<?php } ?>
	</ul></li>
	<?php }	?>
	
	<?php if (count($_["PNLDrafts"]) > 0) { ?>
	<li><a href="#">Drafts</a>
	<ul>
	<?php foreach ($_["PNLDrafts"] as $number) { ?>
		<li><?php p($number); ?></li>
	<?php } ?>
	</ul></li>
	<?php }	?>
</ul>
