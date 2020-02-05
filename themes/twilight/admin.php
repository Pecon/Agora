<?php
	setPageTitle("$site_name - Admin");

	if(isSet($_GET['viewLog']))
	{

	}
?>
<h1>Admin</h1>
<p>
	Force edit post
	<form method="POST" action="./?action=admin">
		<div>
			<p>Post ID</p>
			<input type="text" name="postID" required />
		</div>
		<div>
			<p>New post text</p>
			<input type="text" name="editContents" required />
		</div>
		<div>
			<input type="submit" name="forceEdit" value="1" />
		</div>
	</form>
</p>
<p>
	<a href="./?action=admin&viewLog">Log viewer</a>
</p>