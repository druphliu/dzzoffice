<?php
/**
 * =======================================================================
 * simple:
 *   require_once (dirname(__FILE__).'/JingdongStorageService.php');
 *   $service = new JingdongStorageService("ACCESS_KEY","ACCESS_SECRET");
 *   $bucket = "your bucket"
 *   $local_file = '/path/to/your/local/file';
 *   $key = "logo.png"; 
 *   // list bucket
 *   $service->list_buckets();  
 *   //create new  bucket
 *   $service->put_bucket($bucket);
 *   //put object
 *   $service->put_object($bucket,$key,$local_file);
 *   //head object
 *   $service->head_object($bucket,$key);
 *   //get object
 *   $service->get_object($bucket,$key,dirname(__FILE__) . "/".$key);
 *   //get pre-sign url
 *   $url = $service->get_object_resource($bucket,$key);
 *       echo "url for {$bucket}/{$key} :".$url."\n";
 *
 *   // list objects
 *   $jss_entity = $service->list_objects($bucket); 
 *   $object_list = $jss_entity->get_object();
 *   foreach($object_list as $object){
 *       // delete object
 *       $service->delete_object($bucket,$object->get_key()); 
 *       echo "Delete object {$object->get_key()} success!\n";
 *   }
 *   // delete bucket
 *   $service->delete_bucket($bucket);
 *
 * =======================================================================
 */

date_default_timezone_set('Asia/Shanghai');
//检测API路径
if(!defined('JSS_API_PATH')){
  define('JSS_API_PATH', dirname(__FILE__));
}
//加载conf.inc.php文件^M
require_once JSS_API_PATH.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'error.class.php';
require_once JSS_API_PATH.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'bucket.class.php';
require_once JSS_API_PATH.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'object.class.php';
require_once JSS_API_PATH.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'entity.class.php';
require_once JSS_API_PATH.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'MultipartUpload.class.php';
require_once JSS_API_PATH.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'MultiSinglePart.class.php';
require_once JSS_API_PATH.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'ListPartsEntity.class.php';
require_once JSS_API_PATH.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'MultipartUploadEntity.class.php';
require_once JSS_API_PATH.DIRECTORY_SEPARATOR.'utils'.DIRECTORY_SEPARATOR.'mimetypes.class.php';
require_once JSS_API_PATH.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'JSSRequest.class.php';
require_once JSS_API_PATH.DIRECTORY_SEPARATOR.'services'.DIRECTORY_SEPARATOR.'JSSResponse.class.php';

define("BUCKETS_TAG",'Buckets');
define("NAME_TAG",'Name');
define('CREATIONDATE_TAG','CreationDate');

class JingdongStorageService {
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
	 * debug switch
	 * @access protected
	 */
	protected $debug;
	
	protected $host = 'http://storage.jcloud.com';
	protected $use_batch_flow = true;
	   

	/**
	 * constructor
	 * @param string $access_key
	 * @param string $access_secret
	 * @return $this object
	 */
	public function __construct($access_key, $access_secret) {
		$this->debug = defined('DEBUG') ? DEBUG : false;
		return $this->set_key_secret($access_key,$access_secret);
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
	 * set debug switch
	 * @param bool $flag  true/false
	 * @return $this object
	 */
	public function set_debug($flag) {
		$this->debug = ($flag === true);
		return $this;
	}
	
	public function set_host($host){
		$this->host = $host;
	}
	
	public function get_host(){
		return $this->host;
	}

	/**
	 * Get all buckets,corresponds to "GET Service" in API
	 * @param array $request headers,can be empty
	 * @return Bucket objects list
	 * @exception see JSSError
	 */
	public function list_buckets($request_headers = array()) {
		$jss_response =  $this->make_request_with_path_and_params_split("GET",'/',array(),$request_headers);
	    return $this->parse_bucketsresponse($jss_response);
	}
	
	/**
	 * Create new bucket,corresponds to "PUT Bucket" in API
	 * @param string $name  bucket's name to create
	 * @param string $options bucket's properties,
	 * is useless now,future it may contains something like bucket's region,and so on
	 * @return JSSResponse on success
	 * @exception see JSSError
	 */
	public function put_bucket($name,$options=array()) {
		$jss_response = $this->make_request_with_path_and_params_split("PUT",$name);;
	    return $jss_response->check_response();
	}
	
	/**
	 * Delete specified bucket,corresponds to "Delete Bucket" in API
	 * @param string $name  bucket's name to delete
	 * @return JSSResponse on success
	 * @exception throw JSSError when bucket is not empty or response invalid
	 */
	public function delete_bucket($name) {
		$jss_response = $this->make_request_with_path_and_params_split("DELETE",$name);
		return $jss_response->check_response();
	}


	/**
	 * List all objects of specified bucket,corresponds to "GET Bucket" in API
	 * @param string $bucket  bucket's name
	 * @param array $options,search key for this list operations, example:
	 *   $options = array(
	 *     "marker" => '',
	 *     "max-keys" => 100,
	 *     "prefix" => 'a',
	 *     "delimiter" => '/', 
	 *     );
	 *     can be:
		      @option integer $maxkeys  max response objects number of per-request
		      @option string $marker  response objects offset
		      @option string $delimiter  response objects name filter
		      @option string $prefix  response objects name filter
		
	   @params $request_headers	
	 * @return JSSEntity object
	 * @exception see JSSError
	 */
	public function list_objects($bucket,$options=array(),$request_headers = array()) {
		$bucket = trim($bucket, '/');
		$path = "/{$bucket}";
        $jss_response= $this->make_request_with_path_and_params_split("GET",$path,$options,$request_headers);
        $objects_entity = new JSSEntity();
        if($jss_response->is_xml_response()){
           	$objects_entity->init_from_xml($jss_response->get_body());
        } else if(true || $jss_response->is_json_response()) {
        	return $objects_entity->init_from_json($jss_response->get_body());
        } else {
        	throw new JSSError($jss_response);
        }
	}


	/**
	 * Get object's metas(corresponds to "HEAD Object" in API)
	 * @param string $bucket,bucket's name 
	 * @param string $key,object's name
	 * @return JSSResponse when success
	 * @exception see JSSError
	 */
	public function head_object($bucket,$key) {
		$path = "/{$bucket}/{$key}";
		$jss_response = $this->make_request_with_path_and_params_split("HEAD",$path);;
	    return $jss_response->check_response();
	}

	/**
	 * Put object to storage(corresponds to "PUT Object" in API)
	 * @param string $bucketname,bucket's name 
	 * @param string $objectname  object's name
	 * @param string $source  local file path(/path/to/filename.ext) or stream
	 * @param array $request headers,can be empty
	 * @return JSSResponse on success
	 * @exception see JSSError
	 */
	public function put_object($bucketname,$objectname, $source, $request_headers=array()) {
		if (is_resource($source)) { // stream upload
			if (empty($objectname)) {
				throw new Exception('$objectname must be supplied for resource type!', 500);
			}
            $source_stream = $source;
            //fseek($source,0,0);
		}
		elseif (is_string($source)) { // file upload			
			if (empty($objectname)) {
                $objectname = basename($source);
			}

            $source_stream = $this->getSourceInfo($source);

		}
		
		$set_content = false;
		foreach($request_headers as $key => $value) {
			if(strcasecmp($key,CONTENT_TYPE_TAG) === 0) {
				$set_content = true;
				break;
			}
		}
        if(false === $set_content) {
			$pathinfo = pathinfo($objectname);
			$content_type = JSSMIME::get_type(isset($pathinfo['extension']) ? $pathinfo['extension'] : '');
			$request_headers[CONTENT_TYPE_TAG] = $content_type;
		}
		$path = "/".$bucketname."/".$objectname;
		
		$jss_response = $this->post_or_put_request("PUT", $path, $source_stream,array(),$request_headers);

        if (is_string($source)) {
            fclose($source_stream);
        }
		//printf($jss_response);
		return $jss_response;
	}


    /**
     * Put object to storage with multipart upload
     * @param string $bucketname,bucket's name
     * @param string $objectname  object's name
     * @param string $source  local file path(/path/to/filename.ext) or stream
     * @param array $options headers,can be empty
     * @return JSSResponse on success
     * @exception see JSSError
     */
    public function put_mpu_object($bucketname,$objectname, $source, $options=array()) {
        if (empty($objectname)) {
            throw new Exception('$objectname must be supplied for resource type!', 500);
        }

        if (is_resource($source)) { // stream upload
            fseek($source,0,0);
            $source_stream = $source;
            $source_fstat = fstat($source);
            $source_size = isset($source_fstat['size']) ? $source_fstat['size'] : 0;

        } elseif (is_string($source)) { // file upload
            clearstatcache();
            if (!is_file($source)) {
                throw new Exception("{$source} doesn't exist", 404);
            }

            $source_stream = fopen($source, 'rb');
            if (!$source_stream) {
                throw new Exception("Unable to read {$source}", 500);
            }
            $source_size = filesize($source);
        } else {
            throw new Exception('Unsupported source type!', 500);
        }


        $options = $this->set_content_type($objectname,$options);

       // Handle part size
       if (isset($options['partSize'])) {
            // If less that 5 MB...
           if ((integer) $options['partSize'] < 5242880) {
               $options['partSize'] = 5242880; // 5 MB
           }
           // If more than 500 MB...
           elseif ((integer) $options['partSize'] > 524288000) {
               $options['partSize'] = 524288000; // 500 MB
           }
        } else {
           $options['partSize'] = 52428800; // 50 MB
        }

        // If the upload size is smaller than the piece size, failover to create_object().
        if ($source_size < $options['partSize']) {
        	$this->debug_out("file size is too small use put object instead.");
            return $this->put_object($bucketname, $objectname, $source_stream,$options);
        }


        // Compose options for initiate_multipart_upload().
        $_opt = array();
        foreach (array(CONTENT_TYPE_TAG) as $param) {
            if (isset($options[$param])) {
               $_opt[$param] = $options[$param];
            }
        }

        $upload = $this->init_multipart_upload($bucketname,$objectname,$_opt); //throw exception and return when failed.


                    // Fetch the UploadId
        $upload_id = $upload->get_uploadid();


        // Get the list of pieces
        $pieces = $this->get_multipart_counts($source_size, (integer) $options['partSize']);
        $parts = array();
       // Queue batch requests
        foreach ($pieces as $i => $piece) {
            $request_headers = array(
                SEEK_TO_TAG => intval($piece['seekTo']),
                CONTENT_LENGTH_TAG => intval($piece['length']),
                'expect' => '100-continue',
            );
            $this->debug_out("upload part ".print_r($request_headers,true));
            //throw exception when failed
            $etag= $this->upload_part($bucketname,$objectname,$upload_id,($i+1),$source_stream,$request_headers);
            $this->debug_out("upload part success,etag:".$etag);
            $parts[]= array('PartNumber' => ($i + 1), 'ETag' => $etag);
        }


        return $this->complete_multipartupload($bucketname, $objectname, $upload_id, json_encode($parts));
    }
	

	/**
	 * Get object from storage(corresponds to "GET Object" in API)
	 * @param string $bucket,bucket's name 
	 * @param string $key  object's name
	 * @param string $target  write to local file path(/path/to/filename.ext) or stream
	 * @param boolean $auto_close  if auto close the $target passed when it is a stream?
	 * @param array,$other_headers ,a key-value array,indicate the information such as Range,If-Modified-Since,and so on.
	 * @return JSSResponse on success,false when $target is null;
	 * @exception see JSSError
	 */
	public function get_object($bucket,$key, $target, $auto_close=false,$other_headers=array()) {
		$path = "/{$bucket}/{$key}";
        $jss_request = new JSSRequest($this->host);  
        $jss_request->set_key_secret($this->access_key,$this->access_secret);      
		$is_stream = false;
		if ($target !== null) {
			if (is_resource($target)) { // write to stream
				$is_stream = true;

				$target_stream = $target;
			}
			else if (is_string($target)) { // write to local file
				$target_stream = fopen($target, 'wb');
				if (!$target_stream) {
					throw new Exception("Unable to open {$target}", 500);
				}
			}

		    $jss_request->make_get_request_with_body_save_in_target($path,$target_stream,$other_headers);
		    $jss_response = $jss_request->exec_request();

		    if ($auto_close && $is_stream) {
			    fclose($target_stream);
		    }
		    return $jss_response->check_response(); 
		 }
         
		 return false;
	}

	/**
	 * Get object resource from storage
	 * @param string,$bucket,bucket's name 
	 * @param string $key,  object's name
	 * @param integer $expire  expire of resource
	 * @return resource on success
	 */
	public function get_object_resource($bucket,$key, $expire=300) {
		$this->head_object($bucket,$key);
		$path = "/{$bucket}/{$key}";
        $jss_request = new JSSRequest($this->host);
		$jss_request->set_key_secret($this->access_key,$this->access_secret);
		return $jss_request->generate_pre_signed_url("GET",$path,$expire);
	}

	/**
	 * Delete object from storage (corresponds to "Delete Object" in API)
	 * @param string $bucket,bucket's name
	 * @param string $key,object's name
	 * @param string,$request_headers,can be empty
	 * @return true on success
	 * @exception see JSSError
	 */
	public function delete_object($bucket,$key,$request_headers=array()) {
		$path = "/{$bucket}/{$key}";
		$jss_response = $this->make_request_with_path_and_params_split("DELETE",$path,array(),$request_headers);
	    return $jss_response->check_response();
	
	}
	
	/**
	 *Initiate Multipart Upload "Init Multipart Upload" in API
	 * @param string $bucket_name  bucket's name to create
	 * @param string $object_key object's key,
	 * @param array,request_headers,request header needed in init mulitpart upload,can be empty
	 * @return MultipartUpload on success
	 * @exception see JSSError
	 */
	public function init_multipart_upload($bucket_name,$object_key,$request_headers = array()) {
		$path = "/{$bucket_name}/{$object_key}?uploads";
		$jss_response = $this->make_request_with_path_and_params_split("POST",$path,array(),$request_headers);
		$jss_response->check_response();
		$multipartUpload = new MultipartUpload();
        return	$multipartUpload->init_from_json($jss_response->get_body());
	}
    
    
	/**
	 * Upload part to storage
	 * @param string $bucketname bucket's name
	 * @param string $key  object's name
	 * @param string $uploadid multipart upload's id
	 * @param int $partnumber of this part
	 * @param string $source  local file path(/path/to/filename.ext) or stream
	 * @param array, $request_headers,can be empty,request headers setted by user
	 * @return etag on success
	 * @exception throw exception when failed
	 */
	public function upload_part($bucketname, $key, $uploadid,$partnumber,$source, $request_headers = array()) {

		if("" === $bucketname || "" === $key || (! is_numeric($partnumber))){
			throw new Exception('Illegal params');
		}
		$params = array(
				"partNumber" => $partnumber,
				"uploadId" => $uploadid
				);
		$path = "/{$bucketname}/{$key}?".http_build_query($params);

        $source_stream = $this->getSourceInfo($source);

		$jss_response = $this->post_or_put_request("PUT", $path, $source_stream,array(),$request_headers);
        if (is_string($source)){
            fclose($source_stream);
        }
		return $jss_response->get_header('ETag');
	}
    
    /**
	 * list parts of multipart upload
	 * @param string,$bucket,bucket's name
	 * @param string,$key,object's name
	 * @param string $uploadId,the uploadid of the multipart upload
	 * @param array,$options,key=>velue,the key can be:
	 *            max-parts,int,the maximum number of parts returned in the response body
	 *            part-number-marker,the part to start with
	 *            ...
	 * @return ListPartsEntity on success
	 * @exception,throw exception when failed
	 */
	public function list_parts($bucket,$key,$uploadId,$options=array()) {
		if("" === $bucket || "" === $key || "" === $uploadId){
			throw new Exception('Illegal params');
		}
		$bucket = trim($bucket);
		$params = array();
		$path = "/{$bucket}/{$key}?uploadId={$uploadId}";
		$jss_response = $this->make_request_with_path_and_params_split("GET", $path,$options);
        $multipartEntity = new ListPartsEntity();
        $multipartEntity->init_from_json($jss_response->get_body());
		return $multipartEntity;
	}

    /**
	 * complete a multipartupload
	 * @param string $bucket
	 * @param string $key
	 * @param string $uploadid
	 * @param string $complete_json,can be empty.if is '',then we will get it by list_parts($uploadid)
	 * @throws Exception when failed
	 * @return $jss_response
	 */
	public function complete_multipartupload($bucket,$key,$uploadid,$complete_json = ''){
		if(empty($complete_json)) {
			try {
				$partEntity = $this->list_parts($bucket, $key, $uploadid);
				$complete_json = $partEntity->to_completemultipartuploadjson();
			} catch(Exception $e) {
				throw $e;
			}
		}
        $this->debug_out($complete_json);
		$path = "/{$bucket}/{$key}?uploadId={$uploadid}";
		$stream = fopen('data://text/plain,' . rawurlencode($complete_json), 'rb');
		$jss_response = $this->post_or_put_request("POST",$path,$stream);
        fclose($stream);
		return $jss_response;
	}
	
	
	/**
	 * Abort multipart upload
	 * @param string $bucket,bucket's name
	 * @param string $key,object's name
	 * @param string $uploadId, the uploadid of the multipart upload
	 * @throws Exception when failed
	 * @true on success
	 */
	public function abortMultipartUpload($bucket,$key,$uploadId){
		$path = "/{$bucket}/{$key}?uploadId={$uploadId}";
		$this->make_request_with_path_and_params_split('DELETE', $path);
		return true;
	}
	
	
	/**
	 * List multipart upload (corresponds to "List Multipart Upload" in API)
	 * @param string $bucket, your bucketname
	 * @param array,$options, pairs of $key=>$value,the key can be:
	 *                          key-marker,the key to start with
	 * 							upload-id-marker,the uploadid to start with
	 * 							max-uploads,the maximum number of keys returned in the response body
	 * 							prefix,the prefix parameter to the key of the multipart upload you want to retrive
	 * 							delimiter,the param you use to group keys
	 * @return MultipartUploadEntity object on success
	 * @exception throw exception when response invalid
	 */
	public function list_multipart_upload($bucket,$options = array()) {
		$path = "/{$bucket}?uploads";		
		$jss_response = $this->make_request_with_path_and_params_split('GET',$path,$options);	
		$multipartUploadEntity = new MultipartUploadEntity();
		$multipartUploadEntity->init_from_json($jss_response->get_body());	
		return $multipartUploadEntity;
	}
	
	
    public function parse_bucketsresponse($jss_response) {
        if($jss_response->is_json_response()) {
        	return $this->parse_buckets_json($jss_response->get_body());
        } else {
        	return $this->parse_bucketsxml($jss_response->get_body());
        }
    }
    
	public function parse_bucketsxml($bucketsxml) {
		$bucketsxml = $this->get_xmlpart($bucketsxml);
		$doc = new DOMDocument();
		$doc->loadXML($bucketsxml);

		$buckets = $doc->getElementsByTagName(BUCKETS_TAG);

		$bucketsarray = array();
		foreach($buckets as $xml) {
			$name = $xml->getElementsByTagName(NAME_TAG)->item(0)->nodeValue;
			$ctime = $xml->getElementsByTagName(CREATIONDATE_TAG)->item(0)->nodeValue;
			$bucketsarray[] = new Bucket($name, $ctime);
		}
		return $bucketsarray;
	}
	
	public function parse_buckets_json($bucketjson) {
		$this->debug_out($bucketjson);
		$bucketslist = json_decode($bucketjson,true);
		$buckets = $bucketslist[BUCKETS_TAG];
		$bucketsarray = array();
		
		foreach($buckets as $bucket) {
			$bucketsarray[] = new Bucket($bucket[NAME_TAG],$bucket[CREATIONDATE_TAG]);
		}
		return $bucketsarray;
	}
	
	/**
	 * Get xml part from response body
	 */
	protected function get_xmlpart($response_body) {
       $tmparray = explode("\r\n\r\n", $response_body);
       $realbody = array();	
       for($i=0;$i<count($tmparray);$i++) {
       	$tmp = trim($tmparray[$i]);
       	//printf("\nvc".substr($tmp,0,strlen("<?xml"))."\n");
       	if(substr($tmp,0,strlen("<?xml")) === "<?xml") {
       		break;
       	}
       }
       for(;$i<count($tmparray);$i++) {
       	 $realbody[]=$tmparray[$i];
       }
       
       $realxml = implode("\r\n\r\n",$realbody);
      // printf("realxml:\n".$realxml."\n");
       return $realxml;
	}

	/**
	 * generate uuid string
	 * @param string $prefix
	 * @return string
	 */
	protected function make_uuid($prefix='') {
		$chars = md5(uniqid(mt_rand(), true));
		$uuid = substr($chars,0,8) . '-';
		$uuid .= substr($chars,8,4) . '-';
		$uuid .= substr($chars,12,4) . '-';
		$uuid .= substr($chars,16,4) . '-';
		$uuid .= substr($chars,20,12);

		return $prefix . $uuid;
	}
     
	
	/**
	 * Generate and excute request handler
	 * @param string $method           GET, HEAD, PUT, DELETE
	 * @param string $path             resource $path,used in sign
	 * @param array $params            $query params
	 * @param string $content_meta     x-jingdong-meta-XXXX field
	 * @param string $content_type     Content-Type field
	 * @param string $content_md5      Content-MD5 field
	 * @return JSSResponse when success , throw Exceptions if any error.
	 */
	protected function make_request_with_path_and_params_split($method, $path, $query_params = array(),$request_headers=array()) {
		$jss_request = new JSSRequest($this->host);
		$jss_request->set_key_secret($this->access_key,$this->access_secret);
		$jss_request->set_header(CONTENT_LENGTH_TAG,0);
		$this->debug_out($request_headers);
		$conn = $jss_request->make_request_with_path_and_params_split($method, $path, $query_params,$request_headers);
		$jss_response = $jss_request->exec_request($conn);
		return $jss_response->check_response();
	}
  
    /**
     * process post or put request
     * @param string $path,path need to sign
     * @param resource $source,source data to post
     * @param array $query_params,params in query
     * @param $request_headers request_headers set by users
     * @throws Exception
     * @return code;
     */
    protected function post_or_put_request($method,$path, $source, $query_params=array(),$request_headers = array()) {
    	$jss_request = new JSSRequest($this->host);
		$jss_request->set_key_secret($this->access_key,$this->access_secret);
		$jss_request->set_header($request_headers);
		$jss_response=$jss_request->post_or_put_request($method,$path, $source, $query_params);
		return $jss_response->check_response();
    }

    protected function debug_out($message){
		if($this->debug) {
		    print_r($message);
		    print_r("\n");
		}
	}


    protected function getSourceInfo($source){
        if (is_resource($source)) { // stream upload
            return $source;
        }
        elseif (is_string($source)) { // file upload
            clearstatcache();
            if (!is_file($source)) {
                throw new Exception("{$source} doesn't exist", 404);
            }

            $source_stream = fopen($source, 'rb');
            if (!$source_stream) {
                throw new Exception("Unable to read {$source}", 500);
            }
            return $source_stream;
        } else {
            $this->debug_out("Hey,what happened?");
            throw new Exception('Unsupported source type!', 500);
        }
    }

    protected function set_content_type($objectKey,$options=array()){
        $set_content = false;
        foreach($options as $key => $value) {
            if(strcasecmp($key,CONTENT_TYPE_TAG) === 0) {
                $set_content = true;
                break;
            }
        }

        if(false === $set_content) {
            $pathinfo = pathinfo($objectKey);
            $content_type = JSSMIME::get_type(isset($pathinfo['extension']) ? $pathinfo['extension'] : '');
            $options[CONTENT_TYPE_TAG] = $content_type;
        }
        return $options;
    }

    protected function get_multipart_counts($filesize, $part_size) {
        $i = 0;
        $sizecount = $filesize;
        $values = array();

        while ($sizecount > 0) {
            $sizecount -= $part_size;
            $values[] = array(
                'seekTo' => ($part_size * $i),
                'length' => (($sizecount > 0) ? $part_size : ($sizecount + $part_size)),
            );
            $i++;
        }

        return $values;
    }
}
