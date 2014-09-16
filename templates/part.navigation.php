<ul>
	<?php if (count($_["PNLConversations"]) > 0) { ?>
	<li><a href="#">Conversations</a></li>
	<ul>
	<?php foreach ($_["PNLConversations"] as $number) { ?>
		<li><?php p($number); ?></li>
	<?php } ?>
	</ul>
	<?php }	?>
	
	<?php if (count($_["PNLDrafts"]) > 0) { ?>
	<li><a href="#">Drafts</a></li>
	<ul>
	<?php foreach ($_["PNLDrafts"] as $number) { ?>
		<li><?php p($number); ?></li>
	<?php } ?>
	</ul>
	<?php }	?>
</ul>
