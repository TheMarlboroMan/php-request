<?php
namespace request;

class no_source_exception extends exception {
	public function __construct() {parent::__construct("cannot create web request in cli mode.");}
};
