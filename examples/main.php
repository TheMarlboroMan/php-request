<?php
ini_set('display_errors', 1);
ini_set('error_reporting', -1);
include "../lib/autoload.php";

session_start();

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

	<h1>Send this to localhost:10000 (nc -l localhost 10000)</h1>
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

		setcookie ("A-cookie", "With a value", time()+120, '/');
		setcookie ("Some-other-cookie", "With;some;other;value", time()+120, '/');
		setcookie ("The-third-cookie", "With;loads=of;crap", time()+120, '/');
		$request=request\request_factory::from_apache_request();

		echo $request->has_body('filecomment').' lalala'.PHP_EOL;
		echo $request->to_string().PHP_EOL.PHP_EOL.PHP_EOL;
//		$data=print_r($request, true);
//		echo $data;
//		die('end');
	}
	catch(\Exception $e) {
		die("ERROR:".$e->getMessage()."\n");
	}
}
