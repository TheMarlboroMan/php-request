<?php
declare(strict_types=1);
namespace request;

class header_does_not_exist_exception extends exception {
	public function __construct(
		string $_key
	) {
		parent::__construct("header ".$_key." does not exist");
	}
};
