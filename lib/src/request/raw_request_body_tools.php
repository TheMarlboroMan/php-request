<?php
declare(strict_types=1);
namespace request;

class raw_request_body_tools {

	const					win_line_feed="\r\n";
	const					unix_line_feed="\n";


/**
*@param array<string, string> $_headers
*/
	public static function	get_content_type(
		array $_headers
	) :?string {

		return array_key_exists("Content-Type", $_headers)
			? $_headers['Content-Type']
			: (array_key_exists('content-type', $_headers)
				? $_headers['content-type']
				: null);
	}

	public static function	boundary_from_content_type_header(
		string $_header
	) : string {

		//Lol... Explode the header by ;, the second part is the boundary=xxxx part. Explode that by = and return the second part.
		return 	trim(explode('=', explode(';',$_header, 2)[1])[1]);
	}

/**
*Converts the post and files superglobals into their original raw forms.
*@param array<string, string> $_post
*@param array<string, array<string, mixed> > $_files
*@param array<string, string> $_headers
*/
	public static function raw_body_from_php_parsed_data(
		array $_post, 
		array $_files, 
		array $_headers
	) : string {

		$content_type=raw_request_body_tools::get_content_type($_headers);
		if(null===$content_type) {
				throw new exception("Could not find content-type header in headers when parsing raw body raw_request_body_tools::raw_body_from_php_parsed_data");
		}
		$boundary=raw_request_body_tools::boundary_from_content_type_header($content_type);

		//First the post data...
		$post_data=self::reduce_array($_post, $boundary);

		//TODO: What about invalid files???
		//TODO: Try sending an empty file!.
		//TODO: There should be an option to be a little less hardcore, like just grab $_FILES.
		$file_data=null;
		foreach($_files as $k => $v) {

			//TODO: Will lie about the contents of the stuff... Better to see how it is done in raw.
			/** @var string */
			$temp_name=$v["tpm_name"];

			if(!file_exists($temp_name)) {
				continue;
			}

			$file_body=file_get_contents($temp_name);
			/** @var string */
			$name=$v["name"];
			/** @var string */
			$type=$v["type"];

			$file_data.=<<<R
{$boundary}
Content-Disposition: form-data; name="{$k}"; filename="{$name}"
Content-Type: {$type}

{$file_body}

R;
		}

		return <<<R
{$post_data}{$file_data}{$boundary}--
R;
	}

/**
*@param array<string, string | array<string,string> > $_data
*/
	private static function reduce_array(
		array $_data, 
		string $_boundary, 
		?string $_key_name=null
	) : string {

		$result='';

		foreach($_data as $k => $v) {

			if(is_array($v)) {
				//TODO: What if these keys are named???
				$result.=self::reduce_array($v, $_boundary, $k.'[]');
			}
			else {

				$name=null!==$_key_name ? $_key_name : $k;
				$result.=<<<R
{$_boundary}
Content-Disposition: form-data; name="{$name}"

{$v}

R;
			}
		}

		return $result;
	}

/**
*@param array<string, request_body> $_bodies
*@param-out array<string, request_body> $_bodies
*/
	public static function	parse_multipart_bodies(
		array &$_bodies, 
		string $_body, 
		string $_boundary
	) :void {

		$end_boundary=$_boundary.'--';
		$current_body='';
		$_body=str_replace(self::win_line_feed,self::unix_line_feed,$_body);
		$tk=new string_tokenizer($_body, self::unix_line_feed);

		/** @var array<string, int> */
		$used_name_count=[];

		$add_body=function() use (&$current_body, &$_bodies, &$used_name_count) {
			
			$named_as_array=false;
			$body=self::request_body_from_raw_part($current_body, $named_as_array);
			$current_body='';

			//A body can be an array.. in which case we'll just number it. Sorry.
			$name=$body->get_name();
			if($named_as_array) {

				if(!array_key_exists($name, $used_name_count)) {

					$used_name_count[$name]=0;
				}

				$array_name=$name.$used_name_count[$name]++;
				$_bodies[$array_name]=$body;
			}
			else {
				$_bodies[$name]=$body;
			}
			
		};

		while(!$tk->is_done()) {
			$line=$tk->next();

			if($_boundary==$line) {
				if(strlen($current_body)) {
					$add_body();
				}
			}
			else if($end_boundary==$line) {
				$add_body();
				break;
			}

			$current_body.=$line.self::unix_line_feed;
		}
	}

/**
*@param-out bool $_named_as_array
*/
	public static function	request_body_from_raw_part(
		string $_raw, 
		bool &$_named_as_array
	) : request_body {

		$_raw=str_replace(self::win_line_feed,self::unix_line_feed,$_raw);
		$tk=new string_tokenizer($_raw, self::unix_line_feed);

		//Discard the first line.
		$line=$tk->next();

		//Read headers until an empty line is found.
		$headers=[];
		while(true) {
			$line=$tk->next();
			if(!strlen($line)) {
				break;
			}

			$header_data=explode(':', $line, 2);
			$headers[$header_data[0]]=$header_data[1];
		}

		//Read the rest in the body.
		$body='';
		while(true) {
			$body.=$tk->next();

			if($tk->is_done()) {
				break;
			}

			$body.=self::unix_line_feed;
		}

		return new request_body($body, $headers, $_named_as_array);
	}
}
