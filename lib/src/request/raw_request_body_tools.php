<?php
namespace request;

class raw_request_body_tools {

	const					win_line_feed="\r\n";
	const					unix_line_feed="\n";

	//!Stupid fetch will lowercase all headers.
	public static function	get_content_type(array $_headers) {

		return isset($_headers['Content-Type'])
			? $_headers['Content-Type']
			: (isset($_headers['content-type'])
				? $_headers['content-type']
				: null);
	}

	public static function	boundary_from_content_type_header($_header) {

		//Lol... Explode the header by ;, the second part is the boundary=xxxx part. Explode that by = and return the second part.
		return 	trim(explode('=', explode(';',$_header, 2)[1])[1]);
	}

	//!Converts the post and files superglobals into their original raw forms.
	public static function raw_body_from_php_parsed_data(array $_post, array $_files, array $_headers) {

		$content_type=raw_request_body_tools::get_content_type($_headers);
		if(null===$content_type) {
				throw new request_exception("Could not find content-type header in headers when parsing raw body raw_request_body_tools::raw_body_from_php_parsed_data");
		}
		$boundary=raw_request_body_tools::boundary_from_content_type_header($content_type);

		//First the post data...
		$post_data=null;
		foreach($_post as $k => $v) {
			$post_data.=<<<R
{$boundary}
Content-Disposition: form-data; name="{$k}"

{$v}

R;
		}

		//TODO: What about invalid files???
		//TODO: Try sending an empty file!.
		//TODO: There should be an option to be a little less hardcore, like just grab $_FILES.

		$file_data=null;
		foreach($_files as $k => $v) {

			//TODO: Will lie about the contents of the stuff... Better to see how it is done in raw.
			if(!file_exists($v['tmp_name'])) {
				continue;
			}

			$file_body=file_get_contents($v['tmp_name']);
			$file_data.=<<<R
{$boundary}
Content-Disposition: form-data; name="{$k}"; filename="{$v['name']}"
Content-Type: {$v['type']}

{$file_body}

R;
		}

		return <<<R
{$post_data}{$file_data}{$boundary}--
R;
	}

	public static function	parse_multipart_bodies(array &$_bodies, $_body, $_boundary) {

		$end_boundary=$_boundary.'--';
		$current_body='';
		$_body=str_replace(self::win_line_feed,self::unix_line_feed,$_body);
		$tk=new string_tokenizer($_body, self::unix_line_feed);

		while(!$tk->is_done()) {
			$line=$tk->next();

			if($_boundary==$line) {

				if(strlen($current_body)) {
					$_bodies[]=self::request_body_from_raw_part($current_body);
					$current_body='';
				}
			}
			else if($end_boundary==$line) {
				$_bodies[]=self::request_body_from_raw_part($current_body);
				break;
			}

			$current_body.=$line.self::unix_line_feed;
		}
	}

	public static function	request_body_from_raw_part($_raw) {

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

		return new request_body($body, $headers);
	}
}
