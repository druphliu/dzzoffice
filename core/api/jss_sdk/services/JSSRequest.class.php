<?php

define('JINGDONG_TAG','jingdong');
define('CONTENT_TYPE_TAG','Content-Type');
define('CONTENT_TYPE_LOWER',strtolower(CONTENT_TYPE_TAG));
define('CONTENT_MD5_TAG','Content-MD5');
define('CONTENT_MD5_LOWER',strtolower(CONTENT_MD5_TAG));
define('CONTENT_LENGTH_TAG','Content-Length');
define('CONTENT_LENGTH_LOWER',strtolower(CONTENT_LENGTH_TAG));
define('AUTHORIZATION_TAG','Authorization');
define('AUTHORIZATION_LOWER',strtolower(AUTHORIZATION_TAG));
define('USER_AGENT_TAG','User-Agent');
define('DATE_TAG','Date');
define('DATE_LOWER','date');
define('RANGE_TAG','Range');
define('RANGE_LOWER',strtolower(RANGE_TAG));
define("EXPIRES_TAG","Expires");
define('EXPIRES_LOWER',strtolower(EXPIRES_TAG));
define('SIGNATURE_TAG','Signature');
define('ACCESSKEY_TAG','AccessKey');
define('DEFAULT_USER_AGENT','JSS-SDK-PHP/1.0.0 (PHP 5.2.17; Linux 3.8.0-22-generic; HttpClient 4.2.1)');
define('SEEK_TO_TAG',"SeekTo");

class JSSRequest {
	protected $method;
	protected $url;
	protected $headers = array();
	protected $body;
	protected $debug = false ;
	protected $conn;
	protected $jssresponse;
	protected $needheader = true;
	protected $host = '';
	protected $path;
	const user_agent = DEFAULT_USER_AGENT;
	protected $request_header_map = array(
	            CONTENT_TYPE_LOWER =>CONTENT_TYPE_TAG ,
	            CONTENT_MD5_LOWER => CONTENT_MD5_LOWER,
	            CONTENT_LENGTH_LOWER => CONTENT_LENGTH_TAG,
	            AUTHORIZATION_LOWER => AUTHORIZATION_TAG,
	            DATE_LOWER => DATE_TAG,
	            RANGE_LOWER => RANGE_TAG,
	            EXPIRES_LOWER => EXPIRES_TAG,	            
	          );
	
	/**
	 * access_key
	 * @access protected
	 */
	protected $access_key;

	/**
	 * access_secret
	 * @access protected
	 */
	protected $access_secret;
		
	/**
	 * last curl error
	 * @access protected
	 */
	protected $last_curl_error;
	
    
    public function JSSRequest($host = ""){
    	$this->debug = defined('DEBUG') ? DEBUG : false;
    	if(!empty($host)) {
    		$this->host = $host;
    	}
    	return $this;
    }
	 /**
	 * set region host
	 * @param string $host
	 * @return $this object
	 */

	public function set_host($host) {
		$this->host = $host;

		return $this;
	}
    
	/**
	 * get current region host
	 * @param void
	 * @return string
	 */
	public function get_host() {
		return $this->host;
	}

	/**
	 * set access_key and access_secret
	 * @param string $access_key
	 * @param string $access_secret
	 * @return $this object
	 */
	public function set_key_secret($access_key, $access_secret) {
		$this->access_key = $access_key;
		$this->access_secret = $access_secret;
		return $this;
	}

	/**
	 * get current access_key
	 * @param void
	 * @return string
	 */
	public function get_access_key() {
		return $this->access_key;
	}

	/**
	 * get current access_secret
	 * @param void
	 * @return string
	 */
	public function get_access_secret() {
		return $this->access_secret;
	}
	
	
	/**
	 * Set http request header fields
	 * @param string $field  http header field
	 * @param string $value  value of the field
	 * usually $field is a string without ":",and $value is not empty,
	 * example:$filed = "mykey1",$value = "myvalue1";
	 * meanwhile,$field can be like array($key=>$value),
	 * and $value will unused in this situation.
	 * 
	 * @return $this object
	 */
	public function set_header($field, $value=null) {
		if (empty($field)) {
			return $this;
		}
		if (is_array($field)) { 
			foreach ($field as $key=>$value) {
                $this->set_single_header($key,$value);
			}
		} else {
			$this->set_single_header($field,$value);
		}
		return $this;
	}
	
	protected function set_single_header($field,$value){
		$field = strtolower(trim($field));
		$key = $field;
		if(isset($this->request_header_map[$key])) {
			$key = $this->request_header_map[$key];
		}
		$value = trim($value);
		$this->headers[$field] = "{$key}:$value";
	}

	/**
	 * Remove http header field
	 * @param string $field
	 * @return $this object
	 */
	public function remove_header($field) {
		$field = strtolower(trim($field));
		if (isset($this->headers[$field])) {
			unset($this->headers[$field]);
		}

		return $this;
	}
	
	/**
	 * Get http header field
	 * @param string $field
	 * @return string,the value if the $filed,empty string if $field does not exist
	 */
	public function get_header($field){
		$field = trim($field);
		$field = strtolower($field);
		if(isset($this->headers[$field])) {
			return trim(substr($this->headers[$field],strlen($field)+1));
		}
		return '';
	}
	
	public function initJSSRequest($method,$url,$headers,$body){
		    $this->method = $method;
		    $this->url = $url;
		    $this->headers = $headers;
		    $this->body = body;
	}
	
	
	protected function make_request(){

		$this->debug_out("url:".$this->url."\n");
		$this->debug_out("method:".$this->method."\n");
		$this->debug_out("headers:");
		$this->debug_out($this->headers);
	
		$this->conn = curl_init();
		if($this->conn) {
				curl_setopt_array($this->conn, array(
							CURLOPT_URL             => $this->url,
							CURLOPT_VERBOSE         => $this->debug,
							CURLOPT_CUSTOMREQUEST   => $this->method,
							CURLOPT_CONNECTTIMEOUT  => 10,
							CURLOPT_FOLLOWLOCATION  => true,
							CURLOPT_HEADER          => true,
							CURLOPT_NOBODY          => 'HEAD' === $this->method,
							CURLOPT_RETURNTRANSFER  => true,
							CURLOPT_BINARYTRANSFER  => true,
							CURLOPT_HTTPHEADER      => $this->headers,
							CURLOPT_USERAGENT => self::user_agent,
							));
					
					if (!empty($this->body)) {
						if (is_array($this->body)) {
						   $this->body = http_build_query($this->body);
						}
						curl_setopt_array($this->conn, array(
						CURLOPT_POST          => 1,
						CURLOPT_POSTFIELDS    => $this->body
						));
					}
			} else {
				throw new Exception('Failed to init curl, maybe it is not supported yet?');
			}
		
	}
	
	/**
	 * Execute curl request
	 * @param bool $close_request  whether call curl_close() after execute request
	 * @return JSSResponse or false
	 */
	public function exec_request($ch = null,$close_request = true,$parse_response = true) {
		if(is_resource($ch)) {
			$this->conn = $ch;
		}
		
		if (!is_resource($this->conn)) {
			return false;
		}

		$response = curl_exec($this->conn);
	
		$this->last_curl_error = curl_error($this->conn);
		if (!empty ($this->last_curl_error)) {
			throw new Exception($this->last_curl_error, 0);
		}

        $this->debug_out("response&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&\n");
        $this->debug_out($response);
        $this->debug_out("response&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&\n");
        return $this->process_response($this->conn,$response);
        
	}
	
	/**
	 * Get basic infomation from curl which is already excuted,the infomation can be like response_header,
	 * body,code.
     * @param resource $curl_handle (Optional) The reference to the already executed cURL request.
     * @param string $response (Optional) The actual response content itself that needs to be parsed.
     * return JSSResponse on success,false on failure. 
     */
   	 protected function process_response($curl_handle = null,$response = null) {
	        $this->jssresponse = new JSSResponse();
	        //As long as this came back as a valid resource...  
	        if(is_resource($this->conn)) {
		           //Determine what's what
				if($this->needheader) {
				    $header_size = curl_getinfo($this->conn,CURLINFO_HEADER_SIZE);
				} else {
					$header_size = 0;
				}
			    $response_headers = substr($response, 0, $header_size);
				$response_body = substr($response, $header_size);
				$response_code = curl_getinfo($this->conn, CURLINFO_HTTP_CODE);
				$response_info = curl_getinfo($this->conn);
		
				// Parse out the headers
				$response_headers = explode("\r\n\r\n", trim($response_headers));
				$response_headers = array_pop($response_headers);
				$response_headers = explode("\r\n", $response_headers);
				array_shift($response_headers);
		
				// Loop through and split up the headers.
				$header_assoc = array();
				foreach ($response_headers as $header)
				{
					$kv = explode(': ', $header);
					$header_assoc[strtolower($kv[0])] = $kv[1];
				}
		
				// Reset the headers to the appropriate property.
				$response_headers = $header_assoc;
				$response_headers['_info'] = $response_info;
				$response_headers['_info']['method'] = $this->method;
				if($this->debug === false) {
					unset($response_headers['_info']);
				}
				$this->jssresponse->set_headers($response_headers);
				$this->jssresponse->set_body($response_body);
				$this->jssresponse->set_code($response_code);
				$this->debug_out($response_body);
		        return $this->jssresponse;
	        }
	        return false;
    }	
    
    /**
	 * Generate request handler
	 * @param string $method           GET, HEAD, PUT, DELETE
	 * @param string $path             resource $path,used in sign
	 * @param array $params            $query params
	 * @param string $content_meta     x-jingdong-meta-XXXX field
	 * @param string $content_type     Content-Type field
	 * @param string $content_md5      Content-MD5 field
	 * @return cURL handle on success, false if any error.
	 */
	public function make_request_with_path_and_params_split_t($method, $path, $query_params = array(),$content_meta='', $content_type='', $content_md5='') {
		$this->path = $path;
		$this->get_url_with_params($query_params);
		$this->method = $method;
		if ($content_meta) {
			$content_meta = $this->make_meta($content_meta);
			$this->set_header($content_meta);
		}
		
		if ($content_type) {
			$this->set_header(CONTENT_TYPE_TAG, $content_type);
		}
		
		if ($content_md5) {
			$this->set_header(CONTENT_MD5_TAG, base64_encode($content_md5));
		}
		
		$this->set_auth_header();
		return $this->make_request();
	}
	
	 /**
	 * Generate request handler
	 * @param string $method           GET, HEAD, PUT, DELETE
	 * @param string $path             resource $path,used in sign
	 * @param array $params            $query params
	 * @param array $request_headers    request headers 
	 * @return cURL handle on success, false if any error.
	 */
	public function make_request_with_path_and_params_split($method, $path, $query_params = array(),$request_headers=array()) {
		$this->path = $path;
		$this->get_url_with_params($query_params);
		$this->method = $method;
		if(!empty($request_headers)) {
		   $this->set_header($request_headers);
		}
		$this->set_auth_header();
		return $this->make_request();
	}
	
	/**
	 * Make a get request, and save the body into target_stream
	 * @param $path
	 * @param $target_stream,an stream,it can be an open file
	 * @param $other_headers, an key-value array,contains the infomation such as Range,and so on 
	 * return  cURL handle on success, false if any error.
	 */
	public function make_get_request_with_body_save_in_target($path,$target_stream=null,$other_headers=array()) {
		if(!empty($other_headers) && is_array($other_headers)) {
			foreach($other_headers as $key => $value){
				$this->set_header($key,$value);
			}
		}
		$this->make_request_with_path_and_params_split("GET",$path);
		if ($target_stream) {
				curl_setopt_array($this->conn, array(
						CURLOPT_HEADER    => false,
						CURLOPT_FILE      => $target_stream
				));
		}
		return $this->conn;
	}
	
	 /**
     * process post or put request
     * @param string $path,path need to sign
     * @param resource $source,source data to post
     * @param array $query_params,params in query
     * @param string $content_meta,fileds will be sended as request headers, 
	 *               like x-jingdong-meta-XXXX or those headers do not necessary.
     * @param string $content_type
     * @param string $content_md5
     * @throws Exception
     * @return $jss_response;
     */
    public function post_or_put_request($method,$path, $source, $query_params=array(),$request_header = array()) {
    	
    	
    	if (is_resource($source)) { // stream upload
    		$source_stream = $source;   	
    		$source_fstat = fstat($source);
    		$source_size = isset($source_fstat['size']) ? $source_fstat['size'] : 0;
    	} elseif ($source === null) { // no content
    		$source_stream = null;
    	    $source_size = 0;  
    	} else {
            $this->debug_out("Hey,what happened?");
    		throw new Exception('Unsupported source type!', 500);
    	}



    	if(empty($request_header) === false) {
    		$this->set_header($request_header);	
    	}

        if (isset($request_header[SEEK_TO_TAG])) {
            $seekTo = intval($request_header[SEEK_TO_TAG]);
            fseek($source_stream,$seekTo,0);
        }
        $this->debug_out($request_header);
        $content_length = $this->get_header(CONTENT_LENGTH_TAG);
    	if(is_numeric($content_length)) {   //if $content_length is set,check weather $content_length is illegal
    		$content_length = intval($content_length);
    		if($content_length > $source_size || $content_length < 0) {
    			throw new Exception("Content_length({$content_length}) is illegal",500);
    		} else {
    			$source_size = $content_length;
    		}
    	}
    	
    	try {
    		if ($source_size === 0) {
    			$this->set_header(CONTENT_LENGTH_TAG, $source_size);
    		} else {
    			$this->remove_header(CONTENT_LENGTH_TAG);
    		}
    	
    		$this->make_request_with_path_and_params_split($method, $path,$query_params);    	
    		if ($source_size !== 0) {
    			curl_setopt_array($this->conn, array(
    					CURLOPT_PUT         => true,
    					CURLOPT_INFILE      => $source_stream,
    					CURLOPT_INFILESIZE  => $source_size
    			));
    		}
    		$jss_response = $this->exec_request();
    	
    	} catch (Exception $e){
    		throw $e;
    	}
    	return $jss_response;
    }
    
	
	/**
	 * sign the data
	 * @return string
	 */
	protected function make_sign() {
		$auth = $this->method."\n"          // HTTP Method
				.$this->get_header(CONTENT_MD5_TAG)."\n"     // Content-MD5 Field
				.$this->get_header(CONTENT_TYPE_TAG)."\n"    // Content-Type Field
				.$this->get_header(DATE_TAG)."\n"            // Date Field
				.$this->get_meta_tosign()          // Canonicalized jingdong Headers
				.$this->path;                 // resource path
	    $this->debug_out("string to sign:\n".$auth."\n");
		return base64_encode(hash_hmac('sha1', $auth, $this->access_secret, true));
	}

	/**
	 * adjust the meta
	 * @param string $meta
	 * @return string
	 */
	protected function make_meta($meta) {
		/**
		 * compress
		 * x-jingdong-meta-row: abc, x-jingdong-meta-row: bcd
		 * to
		 * x-jingdong-meta-row:abc,bcd  // value have no lead space
		 */
		$tmparray = array();
		foreach (explode(',', trim($meta)) as $item) {
			$item = explode(':', $item);

			if (isset($item[1])) {
				$tmparray[trim($item[0])][] = trim($item[1]);
			}
		}

		$keys = array_keys($tmparray);
		sort($keys);

		$meta = '';
		foreach ($keys as $key) {
			$meta .= "{$key}:".join(',', $tmparray[$key])."\n";
		}

		return $meta;
	}
	
	/**
	 * 从header里面提取出以x-jingdong为前缀的header
	 */
	protected function get_meta_tosign(){
		$tmparray =array();
		foreach($this->headers as $key=>$value) {
			if(strpos($key,"x-jingdong") === 0) {
				$tmparray[$key] = $value;
			}
		} 
	    $keys = array_keys($tmparray);
		sort($keys);

		$meta = '';
		foreach ($keys as $key) {
			$meta .= "{$key}:".join(',', $tmparray[$key])."\n";
		}
		return $meta;
		
	}

    protected function get_encoded_url(){
        if($this->path[0]!='/') {
            $this->path = '/'.$this->path;
        }

        //$this->url = $this->host.$this->path;
        $argOffset = strpos($this->path,"?");
        if ($argOffset == false) {
            $arg = "";
            $tmpPath = substr($this->path,1);  //rm prefix "/"
        } else {
            $arg = substr($this->path,$argOffset+1);
            $tmpPath = substr($this->path,1,$argOffset-1);  //rm prefix "/"
        }

        $bOffSet = strpos($tmpPath,"/");
        if ($bOffSet == false) {
            $bucket = $tmpPath;
            $object = "";
        } else {
            $bucket = substr($tmpPath,0,$bOffSet);
            $object = substr($tmpPath,$bOffSet+1);
        }
        $realPath = "/";
        if (strlen($bucket) > 0) {
            $realPath .= rawurlencode($bucket);
        }

        if (strlen($object) > 0) {
            $object = str_replace(rawurlencode("/"),"/",rawurlencode($object));
            //$realPath .= "/".rawurlencode($object);
            $realPath .= "/".$object;
        }

        if (strlen($arg) > 0) {
           $realPath .= "?".$arg;
        }

        $this->debug_out("realPath:".$realPath);
        $this->url = $this->host.$realPath;
    }

	protected function get_url_with_params($query_params = array()){
        $this->get_encoded_url();

		if (!empty ($query_params)) {
			$params_str = http_build_query($query_params);
			if (false === strstr($this->url, "?")) {
				$this->url .= "?";
			} else {
				$this->url .= "&";
			}
			$this->url .= $params_str;
		}
		
		
		return $this->url;
	}
	
	protected function set_auth_header() {
		$date = date('r');
		$this->set_header(DATE_TAG, $date);
		$this->set_header(AUTHORIZATION_TAG, JINGDONG_TAG.' '.$this->access_key.':'.$this->make_sign());
		$this->debug_out("auth:".$this->get_header(AUTHORIZATION_TAG)."\n");
		$this->set_header('Expect', '');
	}
	
	/**
	 * Generate pre signed url,
	 * @param string,$method http method
	 * @param string,$path
	 * @param string,$expire,after $expire seconends the pre signed url will expire,default 300
	 * @param array,$headers
	 * @param array,$url_params
	 */
	public function generate_pre_signed_url($method,$path,$expire = 300, $headers = array(),$url_params=array()) {
        $this->method = $method;
        $this->path = $path;
       
        foreach($headers as $key=>$value) {
        	$this->set_header($key,$value);
        }
        $date = $expire + time();
        $this->set_header(DATE_TAG,$date);
        $auth = $this->make_sign();
        $url_params[EXPIRES_TAG] = $date;
        $url_params[SIGNATURE_TAG] = $auth;
        $url_params[ACCESSKEY_TAG] = $this->access_key;
        $this->debug_out($url_params);
        return $this->get_url_with_params($url_params);   		
	}
	protected function debug_out($message){
		if($this->debug) {
		    print_r($message);
		    print_r("\n");
		}
	}
	
}


?>
