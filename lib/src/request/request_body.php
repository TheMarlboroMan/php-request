<?php
namespace request;

class request_body {

	private $headers;
	private $body;
	private $name; //After content disposition, which is always form-data.
	private $filename=null;

	public function	is_file() {
		return null!==$this->filename;
	}

	public function get_filename() {
		return $this->filename;
	}
	
	public function get_headers() {
	
		return $this->headers;
	}

	public function get_name() {
			return $this->name;
	}

	public function get_body(){
			return $this->body;
	}
	
	public function to_string($_separator) {

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

	public function __construct($_b, array $_h, &$_named_as_array) {
		$this->body=$_b;
		$this->headers=$_h;

		//TODO: Mind casing...
		if(isset($this->headers['Content-Disposition'])) {
			$this->parse_content_disposition_header($this->headers['Content-Disposition'], $_named_as_array);
		}
	}

	private function parse_content_disposition_header($_value, &$_named_as_array) {

		$find_value=function($_str, $_find) {
			$pos=strpos($_str, $_find);
			if(false!==$pos) {
				$end_marker=$pos+strlen($_find);

				return substr($_str, $end_marker, strpos($_str, '"', $end_marker)-$end_marker);
			}
			return null;
		};

		$this->name=$find_value($_value, ' name="');
		if("[]"==substr($this->name, -2)) {
			$_named_as_array=true;
			$this->name=substr($this->name, 0, -2);
		}

		$this->filename=$find_value($_value, ' filename="');
	}
}
