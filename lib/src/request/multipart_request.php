<?php
namespace request;

//TODO: Move into own file,
class request_exception_body_index_out_of_bounds extends request_exception {
	public function __construct() {parent::__construct("body index out of bounds.");}
};

//TODO: Move into own file,
class request_exception_body_name_not_found extends request_exception {
	public function __construct() {parent::__construct("body name not found.");}
};

class multipart_request extends request {

	//TODO: Add the option to collapse non file body parts to form data...

	private 				$bodies=[];	//Multiple body parts, you see...

	public function 		__construct($_method, $_uri, $_query_string, $_protocol, array $_headers, $_body) {

		parent::__construct($_method, $_uri, $_query_string, $_protocol, $_headers);

		$content_type_header=raw_request_body_tools::get_content_type($_headers);
		if(null===$content_type_header) {
			throw new request_exception("invalid request to multipart_request constructor: content-type header not found");
		}
		raw_request_body_tools::parse_multipart_bodies($this->bodies, $_body, raw_request_body_tools::boundary_from_content_type_header($content_type_header));
	}

	public function 		is_multipart() {

		return true;
	}

	public function			count() {

		return count($this->bodies);
	}

	public function			body_name_exists($_name) {

		foreach($this->bodies as $k => $v) {
			if($v->get_name() == $_name) {
				return true;
			}
		}

		return false;
	}

	public function			get_body_by_name($_name) {

		foreach($this->bodies as $k => $v) {
			if($v->get_name() == $_name) {
				return $v;
			}
		}

		throw new request_exception_body_name_not_found;
	}

	public function			get_body_by_index($_index) {

		if($_index < 0 || $_index >= $this->count()) {

			throw new request_exception_body_index_out_of_bounds;
		}

		return $this->bodies[$_index];
	}

	protected function		body_to_string() {

		$content_type_header=raw_request_body_tools::get_content_type($this->get_headers());
		if(null===$content_type_header) {
			throw new request_exception("multipart_request::body_to_string cannot find content-type header");
		}
		$boundary=raw_request_body_tools::boundary_from_content_type_header($content_type_header);

		$bodies=array_reduce($this->bodies, function($_carry, request_body $_item) use ($boundary) {

			$_carry.=$_item->to_string($boundary);
			return $_carry;
		}, '');

		return $bodies.$boundary.'--';
	}
};
