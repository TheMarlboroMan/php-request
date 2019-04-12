<?php
namespace request;

class header_does_not_exist_exception extends exception {
	public function __construct($_key) {
		parent::__construct("header ".$_key." does not exist");
	}
};