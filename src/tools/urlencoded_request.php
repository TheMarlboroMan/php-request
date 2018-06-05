<?php
namespace tools;

class urlencoded_request extends request {

	private	$body=null;
	private $body_form=null;

	public function 	__construct($_method, $_uri, $_protocol, array $_headers, $_body) {

		parent::__construct($_method, $_uri, $_protocol, $_headers);
		$this->body=$_body;
	}

	public function 	is_multipart() {

		return false;
	}

	public function		get_body() {
		return $this->body;
	}

	public function 	get_body_form() {

		if(null===$this->body_form) {
			parse_str($this->body, $this->body_form);
		}

		return $this->body_form;
	}
};
