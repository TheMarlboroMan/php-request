<?php
declare(strict_types=1);
namespace request;

class exception extends \Exception {

	public function __construct(
		string $_msg=""
	) {

		$msg=$_msg ? "request exception : ".$_msg : "request exception";
		parent::__construct($msg);
	}
};
