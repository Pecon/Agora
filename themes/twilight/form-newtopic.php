<?php
	global $_postContentPrefill, $_subjectPrefill, $_script_nonce;

	if(!isSet($_postContentPrefill))
		$_postContentPrefill = "";

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
	<span>&nbsp;&rarr;&nbsp;</span><h3>New Topic</h3><hr />
	<form action="./?action=newtopic" method="POST">
		<div>
			Subject
		</div>
		<input class="editor-input" type="text" maxLength="130" minLength="3" name="newtopicsubject" value="<?php print($_subjectPrefill); ?>" tabIndex="1" required>
		<div class="editor-tray" id="editor-tray">

		</div>
		<div class="editor-warnings" id="editor-warnings">
			​
		</div>
		<div class="editor-textarea">
			<textarea id="replytext" class="postbox" maxLength="<?php print($_SESSION['admin'] ? 100000 : 30000); ?>" minLength="3" name="newtopicpost" tabindex="2"><?php

			print($_postContentPrefill);

			?></textarea>
		</div>
		<div class="editor-formbuttons">
			<input class="postButtons" type="submit" name="post" value="Post" tabindex="4">
			<input class="postButtons" type="submit" name="preview" value="Preview" tabindex="3">
		</div>
	</form>
</div>
<script src="./themes/twilight/js/editor.js" nonce="<?php print($_script_nonce); ?>" async></script>