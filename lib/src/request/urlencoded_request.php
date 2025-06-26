<?php
declare(strict_types=1);
namespace request;

//Terrible naming...
class urlencoded_request extends request {

	private string $body="";
	/** @var array<string, string> */
	private array $body_form=[];
	private bool $body_form_loaded=false;

	public function 				__construct(
		string $_ip, 
		string $_method, 
		string $_uri, 
		string $_query_string, 
		string $_protocol, 
		array $_headers, 
		string $_body, 
		array $_cookies,
	) {

		parent::__construct($_ip, $_method, $_uri, $_query_string, $_protocol, $_headers, $_cookies);
		$this->body=$_body;
	}

	public function 				is_multipart() : bool {

		return false;
	}

	//Body manipulation.

	//!Returns the full body.
	public function					get_body() : string {
		return $this->body;
	}

/**
*Returns the full body as an array.
*@return array<string, string>
*/
	public function 				get_body_form() : array {

		//TODO: Should throw if not parseable.
		if(!$this->body_form_loaded) {

			//@phpstan-ignore-next-line sorry phpstan, but I don't see how parse_str writes anything else...
			parse_str($this->body, $this->body_form);
			$this->body_form_loaded=true;
		}

		return $this->body_form;
	}

	//!Returns the given body key, $_default if not present.
	public function					body(
		string $_key, 
		string $_default=""
	) : string {

		$data=$this->get_body_form();
		return $this->has_body($_key) ? $data[$_key] : $_default;
	}

	//!Checks if the given body key exists.
	public function					has_body(
		string $_key
	) : bool {

		return isset($this->get_body_form()[$_key]);
	}

	protected function				body_to_string() : string {
		return $this->body;
	}
};
