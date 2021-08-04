<?php
namespace request;

class string_tokenizer {

	private $string;
	private $delimiter=null;
	private $index=0;
	private $last_index=0;

	public function __construct($_s, $_d) {

		$this->string=$_s;
		$this->delimiter=$_d;
		$this->last_index=strlen($this->string)-1;
	}

	public function is_done() {

		return $this->index>$this->last_index;
	}

	public function next() {

		$result='';
		
		//Takes care of empty strings.
		if($this->last_index < 0) {
		
			return $result;
		}

		while(true) {

			$char=$this->string[$this->index++];

			if($this->delimiter===$char) {
 				break;
			}
			else if($this->is_done()) {
				if($this->delimiter!==$char) {
					$result.=$char;
				}
				break;
			}

			$result.=$char;
		}


		return $result;
	}
};
