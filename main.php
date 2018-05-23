<?php
ini_set('display_errors', 1);
ini_set('error_reporting', -1);
include "autoload.php";

try {
	$request=\tools\request::from_apache_request();
	print_r($request);

//	echo $_SERVER['QUERY_STRING'].' vs '.$request->get_query_string();
//	print_r($_GET);
//	print_r($request->get_query_string_form());
}
catch(\Exception $e) {
	die("ERROR:".$e->getMessage()."\n");
}

/*
echo <<<R
<form method="get" action="">
	<input type="text" name="hi[]" value="a" />
	<input type="text" name="hi[]" value="b" />
	<input type="submit" />
</form>
R;
*/
