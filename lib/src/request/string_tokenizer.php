<?php
declare(strict_types=1);
namespace request;

class string_tokenizer {

	public function __construct(
		private string $string, 
		private string $delimiter,
		private int $index=0,
		private int $last_index=0
	) {

		$this->last_index=strlen($this->string)-1;
	}

	public function is_done() : bool {

		return $this->index>$this->last_index;
	}

	public function next() : string {

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
