<?php 
	require_once 'data.php';
	require_once 'page.php';

	setPageTitle("About Agora");

	$info = <<<EOT
	<h1>About Agora</h1>
	<br />
	<div class="finetext" style="width: 400px; text-align: left">
		Agora is a single-board forum system for online discussions.<br />
		Copyright (C) 2018  pecon.us<br />
		<br />
		This program is free software: you can redistribute it and/or modify<br />
		it under the terms of the GNU Affero General Public License as published<br />
		by the Free Software Foundation, either version 3 of the License, or<br />
		(at your option) any later version.<br />
		<br />
		This program is distributed in the hope that it will be useful<br />
		but WITHOUT ANY WARRANTY; without even the implied warranty of<br />
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the<br />
		GNU Affero General Public License for more details.<br />
		<br />
		You should have received a copy of the GNU Affero General Public License<br />
		along with this program.  If not, see http://www.gnu.org/licenses/<br />
		<br />
		Get the source code here: <a href="https://github.com/Pecon/Agora">https://github.com/Pecon/Agora</a>
	</div>
	<br />
EOT;

	addToBody($info);
	finishPage();
?>