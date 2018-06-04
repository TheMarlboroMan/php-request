<?php
ini_set('display_errors', 1);
ini_set('error_reporting', -1);
include "autoload.php";


if(!isset($_POST['userid'])) {

	echo <<<R
	<h1>Send this here</h1>
	<form method="POST" action="main.php" enctype="multipart/form-data">
		<input type="text" name="userid" value="1" />
		<input type="text" name="filecomment" value="This is an image file" />
		<input type="file" name="data" />
		<input type="submit" />
	</form>

	<h1>Send this here</h1>
	<form method="POST" action="main.php">
		<input type="text" name="userid" value="1" />
		<input type="text" name="filecomment" value="This is an image file" />
		<input type="submit" />
	</form>

	<h1>Send this to localhost:10000 (lc -l localhost 10000)</h1>
	<form method="POST" action="http://localhost:10000" enctype="multipart/form-data">
		<input type="text" name="userid" value="1" />
		<input type="text" name="filecomment" value="This is an image file" />
		<input type="file" name="data" />
		<input type="submit" />
	</form>
R;
	die();
}
else {
	try {

		$request=\tools\request::from_apache_request();

		die('done');
//		print_r($request->get_body_form());
	}
	catch(\Exception $e) {
		die("ERROR:".$e->getMessage()."\n");
	}
}
