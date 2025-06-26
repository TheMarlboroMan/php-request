<?php
declare(strict_types=1);

namespace request;


//https://stackoverflow.com/questions/41427359/phpunit-getallheaders-not-work/4142872
if (!function_exists('getallheaders')) {

/**
*@return array<string, string>
*/
	function getallheaders() : array {

		$headers = [];
		foreach ($_SERVER as $name => $value) {

			if (substr($name, 0, 5) == 'HTTP_') {

				$headername=str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
				$headers[$headername]=$value;
			}
		}

		//@phpstan-ignore-next-line $_SERVER is a map of string to string, sorry.
		return $headers;
	}
}

class request_factory {

	public static function	from_apache_request(
		?factory_options $_options=null
	) : request {

		if(null===$_options) {

			$_options=new factory_options();
		}

		if(php_sapi_name()=="cli") {
			
			throw new no_source_exception;
		}

		$headers=getallheaders();
		/** @var string */
		$method=$_SERVER['REQUEST_METHOD'];
		/** @var string */
		$uri_host=isset($headers['Host']) ? $headers['Host'] : '';
		/** @var string */
		$protocol=$_SERVER['SERVER_PROTOCOL'];
		/** @var string */
		$scheme=isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : "";
		$ip=self::extract_ip();


		if(!strlen($scheme)) {

		    $scheme=isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off' ? 'https' : 'http';
		}

		/** @var string */
		$request_uri=$_SERVER['REQUEST_URI'];
		$uri="{$scheme}://{$uri_host}{$request_uri}";

		/** @var string */
		$query_string=$_SERVER['QUERY_STRING'];

		$body=self::can_get_body_from_input($headers, $method) ?
			\file_get_contents('php://input') :
			//@phpstan-ignore-next-line sorry, but these superglobals are always arrays of string to string!
			raw_request_body_tools::raw_body_from_php_parsed_data($_POST, $_FILES, $headers);

		if(false===$body) {

			throw new exception("could not read php input for request body!");
		}

		//TODO: Mind the casing!.
		$cookies=array_key_exists("Cookie", $headers)
			? self::load_cookies($headers["Cookie"], $_options)
			: [];

		return self::is_multipart($headers) ?
				new multipart_request($ip, $method, $uri, $query_string, $protocol, $headers, $body, $cookies) :
				new urlencoded_request($ip, $method, $uri, $query_string, $protocol, $headers, $body, $cookies);
	}

/**
*@return array<string, string>
*/
	private static function load_cookies(
		string $_raw_cookie_string,
		factory_options $_options
	) : array {

		/** @var array<string, string> */
		$result=array_reduce(
			explode(';', $_raw_cookie_string),
			/** @param array<string, string> $_carry */
			function(array $_carry, string $_item) use ($_options) : array {

				if(false!==strpos($_item, '=')) {

					list($key, $value)=explode('=', $_item, 2);

					if($_options->trim_cookies) {
						$value=trim($value);
					}

					if($_options->urldecode_cookies) {

						$value=urldecode($value);
					}


					$_carry[trim($key)]=$value;
				}

				return $_carry;
			}, 
			[]
		);

		return $result;
	}

/**
*@param array<string, string> $_headers
*@return bool
*/
	private static function	is_multipart(
		array $_headers
	) : bool {

			$header_value=raw_request_body_tools::get_content_type($_headers);
			return $header_value && false!==strpos($header_value, 'multipart/form-data');
	}

/**
*Body cannot be retrieved from php://input when there is a multipart/form-data content type in a post request.
*@param array<string, string> $_headers
*/
	private static function can_get_body_from_input(
		array $_headers, 
		string $_method
	) : bool {

		$header_value=raw_request_body_tools::get_content_type($_headers);
		return ! ('POST'===strtoupper($_method) && null!==$header_value && false!==strpos($header_value, 'multipart/form-data'));
	}

	private static function extract_ip() : string {

		foreach(["HTTP_X_FORWARDED_FOR", "REMOTE_ADDR"] as $key) {

			if(array_key_exists($key, $_SERVER)) {

				/** @var string */
				$val=$_SERVER[$key];
				if(strlen($val)) {

					return $val;
				}
			}
		}

		return "???";
	}
}
