<?php
declare(strict_types=1);
namespace request;

class request_body {

/**
*@param array<string, string> $headers
*/
	public function __construct(
		private string $body,
		private array $headers,
		bool &$_named_as_array
	) {
		//TODO: Mind casing...
		if(isset($this->headers['Content-Disposition'])) {

			$this->parse_content_disposition_header($this->headers['Content-Disposition'], $_named_as_array);
		}
	}


	public function	is_file() : bool {
		return null!==$this->filename;
	}

	public function get_filename() : ?string {
		return $this->filename;
	}
	
/**
*@return array<string, string>
*/
	public function get_headers() : array {
	
		return $this->headers;
	}

	public function get_name() : string {
			return $this->name;
	}

	public function get_body() : string {
			return $this->body;
	}
	
	public function to_string(
		string $_separator
	) : string {

		$headers='';
		foreach($this->headers as $k => $v) {
			$headers.=$k.':'.$v.PHP_EOL;
		}

		return <<<R
{$_separator}
{$headers}
{$this->body}

R;
	}

	private function parse_content_disposition_header(
		string $_value, 
		bool &$_named_as_array
	) : void {

		$find_value=function(string $_str, string $_find) : string {

			$pos=strpos($_str, $_find);
			if(false!==$pos) {
				$end_marker=$pos+strlen($_find);

				return substr($_str, $end_marker, strpos($_str, '"', $end_marker)-$end_marker);
			}
			return "";
		};

		$this->name=$find_value($_value, ' name="');
		if("[]"==substr($this->name, -2)) {
			$_named_as_array=true;
			$this->name=substr($this->name, 0, -2);
		}

		$this->filename=$find_value($_value, ' filename="');
	}

	private string $name=""; //After content disposition, which is always form-data.
	private ?string $filename=null;
}
