<?php
	global $_postContentPrefill, $_recipientPrefill, $_subjectPrefill, $_script_nonce;

	if(!isSet($_postContentPrefill))
		$_postContentPrefill = "";

	if(!isSet($_recipientPrefill))
		$_recipientPrefill = "";

	if(!isSet($_subjectPrefill))
		$_subjectPrefill = "";
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
	<span>&nbsp;&rarr;&nbsp;</span><h3>Compose Message</h3><hr />
	<form action="./?action=composemessage" method="POST">
		<div>
			To
		</div>
		<input class="editor-input" type="text" maxLength="40" minLength="2" name="recipient" value="<?php print($_recipientPrefill); ?>" tabIndex="1" required>
		<div>
			Subject
		</div>
		<input class="editor-input" type="text" maxLength="130" minLength="3" name="subject" value="<?php print($_subjectPrefill); ?>" tabIndex="2" required>
		<div class="editor-tray" id="editor-tray">

		</div>
		<div class="editor-warnings" id="editor-warnings">
			​
		</div>
		<div class="editor-textarea">
			<textarea id="replytext" class="postbox" maxLength="<?php print($_SESSION['admin'] ? 100000 : 30000); ?>" minLength="3" name="postcontent" tabindex="3"><?php

			print($_postContentPrefill);

			?></textarea>
		</div>
		<div class="editor-formbuttons">
			<input class="postButtons" type="submit" name="send" value="Post" tabindex="5">
			<input class="postButtons" type="submit" name="preview" value="Preview" tabindex="4">
		</div>
	</form>
</div>
<script src="./themes/twilight/js/editor.js" nonce="<?php print($_script_nonce); ?>" async></script>