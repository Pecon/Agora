<?php
	global $site_name;
	setPageTitle("$site_name - Admin");

	if(!$_SESSION['admin'])
	{
		error("You do not have permission to view this page.");
		return;
	}
?>
<h1>Admin</h1>

<?php

	if(isSet($_GET['viewLog']))
	{
		$logTypes = Array();

		if(isSet($_POST['logViewInfo']))
		{
			if($_POST['logViewInfo'] == true)
				array_push($logTypes, "info");
		}
		else
			$_POST['logViewInfo'] = false;

		if(isSet($_POST['logViewWarning']))
		{
			if($_POST['logViewWarning'] == true)
				array_push($logTypes, "warning");
		}
		else
			$_POST['logViewWarning'] = false;

		if(isSet($_POST['logViewError']))
		{
			if($_POST['logViewError'] == true)
				array_push($logTypes, "error");
		}
		else
			$_POST['logViewError'] = false;

		if(isSet($_POST['logViewSecurity']))
		{
			if($_POST['logViewSecurity'] == true)
				array_push($logTypes, "security");
		}
		else
			$_POST['logViewSecurity'] = false;

		if(isSet($_POST['logViewAdmin']))
		{
			if($_POST['logViewAdmin'] == true)
				array_push($logTypes, "admin");
		}
		else if(count($logTypes) < 1)
		{
			$_POST['logViewAdmin'] = true; // We should show just the admin log by default
			array_push($logTypes, "admin");
		}
		else
			$_POST['logViewAdmin'] = false;

		
		?>
		<p>
			Logs to view: 
			<form method="POST" action="./?action=admin&amp;viewLog=1">
				<label for="logViewInfo">
					<input type="checkbox" name="logViewInfo" <?php print($_POST['logViewInfo'] == true ? "checked" : "") ?> >
					Info
				</label>

				<label for="logViewWarning">
					<input type="checkbox" name="logViewWarning" <?php print($_POST['logViewWarning'] == true ? "checked" : "") ?> >
					Warning
				</label>

				<label for="logViewError">
					<input type="checkbox" name="logViewError" <?php print($_POST['logViewError'] == true ? "checked" : "") ?> >
					Error
				</label>

				<label for="logViewAdmin">
					<input type="checkbox" name="logViewAdmin" <?php print($_POST['logViewAdmin'] == true ? "checked" : "") ?> >
					Admin
				</label>

				<label for="logViewSecurity">
					<input type="checkbox" name="logViewSecurity" <?php print($_POST['logViewSecurity'] == true ? "checked" : "") ?> >
					Security
				</label>

				<input type="submit" value="View logs">
			</form>
		</p>

		<?php

		if(count($logTypes) > 0)
			$logs = getLogs($logTypes, 0, 100);
		else
			$logs = array();

		?>
		<table style="width: 100%;">
			<tr>
				<td>Date</td>
				<td>Message</td>
				<td>User</td>
				<td>IP Address</td>
			</tr>
		<?php

		foreach($logs as $log)
		{
			?>
			<tr>
				<td>
					<?php print(date("M d, Y H:i:s", $log['logTime']));	?>
				</td>
				<td>
					<?php print($log['logMessage']); ?>
				</td>
				<td>
					<?php
						$name = findUserByID($log['logUserID'])['username'];
						print($log['logUserID'] === null ? "N/A" : '<a href="./action=viewProfile&amp;user=' . $log['logUserID'] . '">' . $name . '</a>');
					?>
				</td>
				<td>
					<?php print($log['logIPAddress'] === null ? "N/A" : $log['logIPAddress']); ?>
				</td>
			</tr>
			<?php
		}

		?>
		</table>
		<?php
		return;
	}
?>
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