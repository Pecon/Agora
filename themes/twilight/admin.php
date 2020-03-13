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

		if(isSet($_GET['logViewInfo']))
		{
			if($_GET['logViewInfo'] == true)
				array_push($logTypes, "info");
		}
		else
			$_GET['logViewInfo'] = false;

		if(isSet($_GET['logViewWarning']))
		{
			if($_GET['logViewWarning'] == true)
				array_push($logTypes, "warning");
		}
		else
			$_GET['logViewWarning'] = false;

		if(isSet($_GET['logViewError']))
		{
			if($_GET['logViewError'] == true)
				array_push($logTypes, "error");
		}
		else
			$_GET['logViewError'] = false;

		if(isSet($_GET['logViewSecurity']))
		{
			if($_GET['logViewSecurity'] == true)
				array_push($logTypes, "security");
		}
		else
			$_GET['logViewSecurity'] = false;

		if(isSet($_GET['logViewAdmin']))
		{
			if($_GET['logViewAdmin'] == true)
				array_push($logTypes, "admin");
		}
		else if(count($logTypes) < 1)
		{
			$_GET['logViewAdmin'] = true; // We should show just the admin log by default
			array_push($logTypes, "admin");
		}
		else
			$_GET['logViewAdmin'] = false;

		
		?>
		<p>
			Logs to view: 
			<form method="GET" action="./?action=admin&amp;viewLog=1">
				<input type="hidden" name="action" value="admin">
				<input type="hidden" name="viewLog" value="1">
				<label for="logViewInfo">
					<input type="checkbox" name="logViewInfo" id="logViewInfo" <?php print($_GET['logViewInfo'] == true ? "checked" : "") ?> >
					Info
				</label>

				<label for="logViewWarning">
					<input type="checkbox" name="logViewWarning" id="logViewWarning" <?php print($_GET['logViewWarning'] == true ? "checked" : "") ?> >
					Warning
				</label>

				<label for="logViewError">
					<input type="checkbox" name="logViewError" id="logViewError" <?php print($_GET['logViewError'] == true ? "checked" : "") ?> >
					Error
				</label>

				<label for="logViewAdmin">
					<input type="checkbox" name="logViewAdmin" id="logViewAdmin" <?php print($_GET['logViewAdmin'] == true ? "checked" : "") ?> >
					Admin
				</label>

				<label for="logViewSecurity">
					<input type="checkbox" name="logViewSecurity" id="logViewSecurity" <?php print($_GET['logViewSecurity'] == true ? "checked" : "") ?> >
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
				<td>Category</td>
				<td>User</td>
				<td>Message</td>
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
					<?php print($log['logType']); ?>
				</td>
				<td>
					<?php
						$name = findUserByID($log['logUserID'])['username'];
						print($log['logUserID'] === null ? "N/A" : '<a href="./?action=viewProfile&amp;user=' . $log['logUserID'] . '" target="_BLANK">' . $name . '</a>');
					?>
				</td>
				<td>
					<?php print(expandLogLinks($log['logMessage'])); ?>
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