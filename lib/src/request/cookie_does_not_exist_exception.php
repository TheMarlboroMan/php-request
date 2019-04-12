<?php
namespace request;

class cookie_does_not_exist_exception extends exception {
	public function __construct($_key) {
		parent::__construct("cookie ".$_key." does not exist");
	}
};