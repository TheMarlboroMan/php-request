<?php
declare(strict_types=1);
namespace request;

class multipart_request extends request {

	//TODO: Add the option to collapse non file body parts to form data...

	/** @var array<string, request_body> */
	private array $bodies=[];	//Multiple body parts, you see...

	public function 		__construct(
		string $_ip, 
		string $_method, 
		string $_uri, 
		string $_query_string, 
		string $_protocol, 
		array $_headers, 
		string $_body, 
		array $_cookies
	) {

		parent::__construct($_ip, $_method, $_uri, $_query_string, $_protocol, $_headers, $_cookies);

		$content_type_header=raw_request_body_tools::get_content_type($_headers);
		if(null===$content_type_header) {
			throw new exception("invalid request to multipart_request constructor: content-type header not found");
		}

		raw_request_body_tools::parse_multipart_bodies($this->bodies, $_body, raw_request_body_tools::boundary_from_content_type_header($content_type_header));
	}

	public function 		is_multipart() : bool {

		return true;
	}

	//!Returns the total number of bodies.
	public function			count() : int {

		return count($this->bodies);
	}

/**
*@return array<string, request_body>
*Returns the full array of bodies.
*/
	public function			get_bodies() : array {

		return $this->bodies;
	}

	public function			body_name_exists(
		string $_name
	) : bool {

		return array_key_exists($_name, $this->bodies);
	}

	public function			get_body_by_name(
		string $_name
	) : request_body {

		if(!$this->body_name_exists($_name)) {

			throw new body_name_not_found_exception($_name);
		}

		return $this->bodies[$_name];
	}

	public function			get_body_by_index(
		int $_index
	) : request_body {

		if($_index < 0 || $_index >= $this->count()) {

			throw new body_index_out_of_bounds_exception($_index, $this->count());
		}

		$keys=array_keys($this->bodies);
		return $this->bodies[$keys[$_index]];
	}

	public function		body_to_string() : string {

		$content_type_header=raw_request_body_tools::get_content_type($this->get_headers());
		if(null===$content_type_header) {
			throw new exception("multipart_request::body_to_string cannot find content-type header");
		}

		$boundary=raw_request_body_tools::boundary_from_content_type_header($content_type_header);

		$reduce=function(string $_carry, request_body $_item) use ($boundary) : string {

			return $_carry.=$_item->to_string($boundary);
		};

		$bodies=array_reduce($this->bodies, $reduce, '');
		return $bodies.$boundary.'--';
	}
};
