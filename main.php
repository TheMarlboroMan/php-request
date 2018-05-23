<?php
ini_set('display_errors', 1);
ini_set('error_reporting', -1);
include "autoload.php";


if(!isset($_POST['userid'])) {

	echo <<<R
	<form method="POST" action="main.php" enctype="multipart/form-data">
		<input type="text" name="userid" value="1" />
		<input type="text" name="filecomment" value="This is an image file" />
		<input type="file" name="data" />
		<input type="submit" />
	</form>

	<form method="POST" action="main.php">
		<input type="text" name="userid" value="1" />
		<input type="text" name="filecomment" value="This is an image file" />
		<input type="submit" />
	</form>
R;
	die();
}
else {
	try {

	//echo "INPUT: ".PHP_EOL.file_get_contents('php://input').PHP_EOL;
	//echo "STDIN: ".file_get_contents('php://stdin').PHP_EOL;
	//print_r(getallheaders());
	//print_r($_SERVER);
		$request=\tools\request::from_apache_request();
//		die($request->get_body()).'loool';
//		print_r($request);
		print_r($request->get_body_form());

	//	echo $_SERVER['QUERY_STRING'].' vs '.$request->get_query_string();
	//	print_r($_GET);
	//	print_r($request->get_query_string_form());
	}
	catch(\Exception $e) {
		die("ERROR:".$e->getMessage()."\n");
	}
}
