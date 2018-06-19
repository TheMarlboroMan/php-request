<?php
namespace request;

//Terrible naming...
class urlencoded_request extends request {

	private							$body=null;
	private 						$body_form=null;

	public function 				__construct($_method, $_uri, $_query_string, $_protocol, array $_headers, $_body) {

		parent::__construct($_method, $_uri, $_query_string, $_protocol, $_headers);
		$this->body=$_body;
	}

	public function 				is_multipart() {

		return false;
	}

	public function					get_body() {
		return $this->body;
	}

	public function 				get_body_form() {

		//TODO: Should throw if not parseable.
		if(null===$this->body_form) {
			parse_str($this->body, $this->body_form);
		}

		return $this->body_form;
	}

	public function					data($_key, $_default=null) {

		$data=$this->get_body_form();
		return $this->has_data($_key) ? $data[$_key] : $_default;
	}

	public function					has_data($_key) {

		return isset($this->get_body_form()[$_key]);
	}

	protected function				body_to_string() {
		return $this->body;
	}
};
