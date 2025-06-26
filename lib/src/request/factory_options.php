<?php
declare(strict_types=1);
namespace request;

class factory_options {

	public function __construct(
		public bool $trim_cookies=true,
		public bool $urldecode_cookies=true
	) {}
}
