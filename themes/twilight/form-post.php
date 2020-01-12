<?php
	global $_topicID, $_page, $_postContentPrefill, $_script_nonce;

	$_topicID = intval($_topicID);
	$_page = intval($_page);
?>

<noscript>
	<style type="text/css">
		.editor-tray
		{
			display: none;
		}
		.editor-warnings
		{
			display: none;
		}
	</style>
</noscript>

<div class="editor" id="editor">
	<?php 
	if(!isSet($_postContentPrefill))
	{?>
	<span>&nbsp;&rarr;&nbsp;</span><h3>Reply</h3><hr />
	<?php
	}?>
	<div class="editor-tray" id="editor-tray">
	</div>
	<div class="editor-warnings" id="editor-warnings">
		​
	</div>
	<form action="./?action=post&amp;topic=<?php print($_topicID); ?>&amp;page=<?php print($_page); ?>" method="POST">
		<input type="hidden" name="action" value="newpost">
		<div class="editor-textarea">
			<textarea id="replytext" class="postbox" name="postcontent" tabindex="1"><?php

			if(isSet($_postContentPrefill))
				print($_postContentPrefill);

			?></textarea>
		</div>
		<div class="editor-formbuttons">
			<input class="postButtons" type="submit" name="post" value="Post" tabindex="3">
			<input class="postButtons" type="submit" name="preview" value="Preview" tabindex="2">
		</div>
	</form>
</div>
<script src="./themes/twilight/js/editor.js" nonce="<?php print($_script_nonce); ?>" async></script>