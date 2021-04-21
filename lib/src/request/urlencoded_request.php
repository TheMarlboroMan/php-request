<?php
namespace request;

//Terrible naming...
class urlencoded_request extends request {

	private							$body=null;
	private 						$body_form=null;

	public function 				__construct($_ip, $_method, $_uri, $_query_string, $_protocol, array $_headers, $_body) {

		parent::__construct($_ip, $_method, $_uri, $_query_string, $_protocol, $_headers);
		$this->body=$_body;
	}

	public function 				is_multipart() {

		return false;
	}

	//Body manipulation.

	//!Returns the full body.
	public function					get_body() {
		return $this->body;
	}

	//!Returns the full body as an array.
	public function 				get_body_form() {

		//TODO: Should throw if not parseable.
		if(null===$this->body_form) {
			parse_str($this->body, $this->body_form);
		}

		return $this->body_form;
	}

	//!Returns the given body key, $_default if not present.
	public function					body($_key, $_default=null) {

		$data=$this->get_body_form();
		return $this->has_body($_key) ? $data[$_key] : $_default;
	}

	//!Checks if the given body key exists.
	public function					has_body($_key) {

		return isset($this->get_body_form()[$_key]);
	}

	protected function				body_to_string() {
		return $this->body;
	}
};
