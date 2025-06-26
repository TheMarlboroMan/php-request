<?php
declare(strict_types=1);

namespace request;

class cookie_does_not_exist_exception extends exception {
	
	public function __construct(
		string $_key
	) {
		parent::__construct("cookie ".$_key." does not exist");
	}
};
