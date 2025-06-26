<?php
declare(strict_types=1);
namespace request;

class body_name_not_found_exception extends exception {

	public function __construct(
		string $_name
	) {

		parent::__construct("body name '$_name' not found in request.");
	}
};
