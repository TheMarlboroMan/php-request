<?php
declare(strict_types=1);
namespace request;

class body_index_out_of_bounds_exception extends exception {

	public function __construct(
		int $_index, 
		int $_total
	) {

		parent::__construct("body index [$_index] out of bounds ($_total bodies exist).");
	}
};
