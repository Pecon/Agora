<?php
	global $site_name, $_userData, $_id;
	setPageTitle("Profile of " . $_userData['username']);
	setPageDescription("The profile of ${_userData['username']} on $site_name.");

	if(!isSet($_SESSION['loggedin']))
		$adminControl = "";
	else
	{
		if($_SESSION['admin'])
			$adminControl = "<a href=\"./?action=ban&amp;id=${_id}\">" . ($_userData['banned'] ? "Unban" : "Ban") . " this user</a> &nbsp; <a href=\"./?action=promote&amp;id=${_id}\">" . ($_userData['usergroup'] == 'admin' ? "Demote" : "Promote") . " this user</a><br >\n";
		else
			$adminControl = "";
	}


	$username = $_userData['username'];
	$lastActive = $_userData['lastActive'];
	$reg_date = date('Y-m-d g:i:s', $_userData['reg_date']);
	$postCount = $_userData['postCount'];
	$tagLine = $_userData['tagline'];
	$website = $_userData['website'];
	$profileText = $_userData['profiletext'];
	$profileDisplayText = $_userData['profiletextPreparsed'];

	$taglineClass = "tagline";
	if($_userData['usergroup'] == 'admin')
		$taglineClass = "taglineAdmin";
	if($_userData['banned'])
		$taglineClass = "taglineBanned";

	$websiteComps = parse_url($website);
	if(isSet($websiteComps['host']))
		$websitePretty = $websiteComps['host'] . (isSet($websiteComps['path']) ? (strlen($websiteComps['path']) > 1 ? $websiteComps['path'] : "") : "");


	print("\n<div class=\"profileContainer\">${adminControl}\n<div class=\"profileContents\"><div class=\"profileStats\"><div class=\"profileEntry\">\n${username}\n</div>\n<div class=\"profileEntry\">\n" .
			(strLen($tagLine) > 0 ? "<span class=\"${taglineClass}\">${tagLine}</span></div>\n" : "</div>") .
			"<div class=\"profileEntry\"><img class=avatar src=\"./avatar.php?user=${_id}&amp;${_userData['avatarUpdated']}\" /></div>
			<div class=\"profileEntry\">Posts: {$postCount}</div>
			<div class=\"profileEntry\">Registered: {$reg_date}</div>
			<div class=\"profileEntry\">Last active: {$lastActive}</div>\n<div class=\"profileEntry\">" .
			(strLen($website) > 0 && isSet($websitePretty) ? "Website: <a target=\"_blank\" href=\"${website}\">${websitePretty}</a>" : "Website: None") .
			"</div>
			<div class=\"profileEntry\"><a href=\"./?action=recentPosts&user=${_userData['id']}\">View this user's posts</a></div>
			</div>
			<div class=\"profileText\">
			${profileDisplayText}
			</div>
			</div>\n");

	if(strlen($website) == 0)
		$website = "http://";

	if(isSet($_SESSION['userid']))
		if($_SESSION['userid'] == $_id)
		{
			$updateProfileText = $profileText;
			$table = <<<EOT
				<div class="profileControls">
					<div class="profileUserSettings">
						User&nbsp;settings<br />
						<a href="./?action=avatarchange">Change&nbsp;avatar</a><br />
						<a href="./?action=emailchange">Change&nbsp;email</a><br />
						<a href="./?action=passwordchange">Change&nbsp;password</a><br />
					</div>
					<div class="profileSettings">
						Profile info<br />
						<form action="./?action=updateprofile" method=POST>
							Tagline: <input type="text" name="tagline" maxLength="30" value="${tagLine}"/><br />
							Website: <input type="text" name="website" maxLength="200" value="${website}"/><br />
							<br />
							Update profile text (you may use bbcode here):<br />
							<textarea class="postbox" maxLength="1000" name="updateProfileText">${updateProfileText}</textarea><br />
							<input class="postButtons" type="submit" value="Update profile">
						</form>
					</div>
				</div>
EOT;
			print($table);
		}

		print("</div>");
?>