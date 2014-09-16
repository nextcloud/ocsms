<ul>
	<li><a href="#">Conversations</a></li>
	<?php if count($_["PNLConversations"]) > 0) { ?>
	<ul>
	<?php foreach ($_["PNLConversations"] as $number) { ?>
		<li><?php p($number); ?></li>
	<?php } ?>
	</ul>
	<?php }	?>
	
	<li><a href="#">Drafts</a></li>
	<?php if count($_["PNLDrafts"]) > 0) { ?>
	<ul>
	<?php foreach ($_["PNLDrafts"] as $number) { ?>
		<li><?php p($number); ?></li>
	<?php } ?>
	</ul>
	<?php }	?>
</ul>
