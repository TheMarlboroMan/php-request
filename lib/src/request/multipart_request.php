<?php
namespace request;

class multipart_request extends request {

	//TODO: Add the option to collapse non file body parts to form data...

	private 				$bodies=[];	//Multiple body parts, you see...

	public function 		__construct($_ip, $_method, $_uri, $_query_string, $_protocol, array $_headers, $_body) {

		parent::__construct($_ip, $_method, $_uri, $_query_string, $_protocol, $_headers);

		$content_type_header=raw_request_body_tools::get_content_type($_headers);
		if(null===$content_type_header) {
			throw new exception("invalid request to multipart_request constructor: content-type header not found");
		}
		raw_request_body_tools::parse_multipart_bodies($this->bodies, $_body, raw_request_body_tools::boundary_from_content_type_header($content_type_header));
	}

	public function 		is_multipart() {

		return true;
	}

	//!Returns the total number of bodies.
	public function			count() {

		return count($this->bodies);
	}

	//!Returns the full array of bodies.
	public function			get_bodies() {

		return $this->bodies;
	}

	public function			body_name_exists($_name) {

		return array_key_exists($_name, $this->bodies);
	}

	public function			get_body_by_name($_name) {

		if(!$this->body_name_exists($_name)) {

			throw new body_name_not_found_exception($_name);
		}

		return $this->bodies[$_name];
	}

	public function			get_body_by_index($_index) {

		if($_index < 0 || $_index >= $this->count()) {

			throw new body_index_out_of_bounds_exception($_index, $this->count());
		}

		$keys=array_keys($this->bodies);
		return $this->bodies[$keys[$_index]];
	}

	public function		body_to_string() {

		$content_type_header=raw_request_body_tools::get_content_type($this->get_headers());
		if(null===$content_type_header) {
			throw new exception("multipart_request::body_to_string cannot find content-type header");
		}

		$boundary=raw_request_body_tools::boundary_from_content_type_header($content_type_header);
		$reduce=function($_carry, $_item) use ($boundary, &$reduce) {

			if(is_array($_item)) {
				return $_carry.=array_reduce($_item, $reduce, '');
			}
			else {
				return $_carry.=$_item->to_string($boundary);
			}
		};

		$bodies=array_reduce($this->bodies, $reduce, '');
		return $bodies.$boundary.'--';
	}
};
