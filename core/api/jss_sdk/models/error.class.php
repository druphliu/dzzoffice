<?php

// Errors
define('ERR_ACCESS_DENIED', 'AccessDenied');
define('ERR_UNSUPPORTED_TRANSFER_ENCODING', 'UnsupportedTransferEncoding');
define('ERR_BAD_DIGEST', 'BadDigest');
define('ERR_INCOMPLETE_BODY', 'IncompleteBody');
define('ERR_BUCKET_ACCESS_DENIED', 'BucketAccessDenied');
define('ERR_BUCKET_NOT_EMPTY', 'BucketNotEmpty');
define('ERR_BUCKET_UNEXIST', 'NoSuchBucket');
define('ERR_BUCKET_TOO_MANY', 'TooManyBuckets');
define('ERR_BUCKET_NAME_CONFLICT', 'BucketAlreadyExists');
define('ERR_BUCKET_NAME_INVALID', 'InvalidBucketName');
define('ERR_BUCKET_NOPOLICY', 'NotSuchBucketPolicy');
define('ERR_ENTITY_TOO_LARGE', 'EntityTooLarge');
define('ERR_OBJECT_KEY_TOO_LONG', 'KeyTooLong');
define('ERR_OBJECT_UNEXIST', 'NoSuchKey');
define('ERR_INVALID_ACCESS_KEY', 'InvalidAccessKeyId');
define('ERR_INVALID_CONTENT_LENGTH', 'InvalidContentLength');
define('ERR_INVALID_EXPIRES', 'InvalidExpires');
define('ERR_INVALID_RANGE', 'InvalidRange');
define('ERR_INVALID_REQUEST_TIME', 'InvalidRequestTime');
define('ERR_INVALID_USER_METADATA', 'InvalidUserMetadata');
define('ERR_MALFORMED_AUTHORIZATION', 'MalformedAuthorization');
define('ERR_MALFORMED_XML', 'MalformedXML');
define('ERR_METHOD_NOT_ALLOWED', 'MethodNotAllowed');
define('ERR_MISSING_CONTENT_LENGTH', 'MissinJSSontentLength');
define('ERR_MISSING_SECURITY_HEADER', 'MissingSecurityHeader');
define('ERR_MULTIPLE_RANGE', 'MultipleRange');
define('ERR_PRECONDITION_FAILED', 'PreconditionFailed');
define('ERR_REQUEST_EXPIRED', 'RequestHasExpired');
define('ERR_REQUEST_TIMEOUT', 'RequestTimeout');
define('ERR_REQUEST_TIMESKEWED', 'RequestTimeTooSkewed');
define('ERR_SIGNATURE_UNMATCH', 'SignatureDoesNotMatch');
// Error对象
class JSSError extends Exception {
	protected $error_messages =array('400'=>'400 Bad Request - 由于包含语法错误，当前请求无法被服务器理解',
						 			'401'=>'401 Unauthorized — 当前请求需要用户验证',
									'402'=>'402 Payment Required',
									'403'=>'403 Forbidden — 服务器已经理解请求，但是拒绝执行它',
									'404'=>'404 Not Found — 请求失败，请求所希望得到的资源未被在服务器上发现',
									'404'=>'405 Method Not Allowed — 请求行中指定的请求方法不能被用于请求相应的资源',
									'407'=>'407 Proxy Authentication Required — 客户端必须在代理服务器上进行身份验证',
									'408'=>'408 Request Timeout — 请求超时',
									'409'=>'409 Conflict — 由于和被请求的资源的当前状态之间存在冲突，请求无法完成',
									'411'=>'411 Length Required',
									'412'=>'412 Precondition Failed',
									'413'=>'413 Request Entity Too Large',
									'414'=>'414 Request-URI Too Long',
									'415'=>'415 Unsupported Media Type', 
									'416'=>'416 Requested Range Not Satisfiable', 
									'417'=>'417 Expectation Failed', 
									'418'=>'415 Unsupported Media Type', 
									'500'=>'500 Internal Server Error — 因为意外情况，服务器不能完成请求',
									'501'=>'501 Not Implemented — 服务器不支持请求的工具',
									'502'=>'502 Bad Gateway — 服务器接收到来自上游服务器的无效响应',
									'504'=>'504 Gateway Timeout',
									'505'=>'505 HTTP Version Not Supported',
									'506'=>'506 Variant Also Negotiates',
									'507'=>'507 Insufficient Storage',
									'509'=>'509 Bandwidth Limit Exceeded',
									'510'=>'510 Not Extended'
									);
	protected $requestId=''; // the request sign, used for error trace
	protected $requestResource=''; // the request target resource
	protected $errorCode=''; // error code
	protected $errorMessage=''; // error message

     /**
      * @params $jss_response JSS_RESPONSE
      */
	public function __construct($jss_response) {
		if($jss_response->is_xml_response()) {
			$this->parse_errxml($jss_response->get_body());
		} else if($jss_response->is_json_response()) {
			$this->parse_errjson($jss_response->get_body());
		} else {
			$this->errorCode = "unknowResponseContentType.";
			$this->errorMessage= $jss_response->get_body();
		}
		parent::__construct($this->errorMessage, $jss_response->get_code());
	}
    
	public function getRequestId() {
		return $this->requestId;
	}

	public function getResource() {
		return $this->requestResource;
	}

	public function getErrorCode() {
		return $this->errorCode;
	}

	public function getErrorMessage() {
		return $this->errorMessage;
	}

	public function to_array() {
		return array(
				'code' => $this->code,
				'message' => $this->message,
				'errorCode' => $this->errorCode,
				'errorMessage' => $this->errorMessage,
				'requestId' => $this->requestId,
				'requestResource' => $this->requestResource
		);
	}
    
    protected function parse_errjson($error_json){
    	$error = json_decode($error_json);
    	$this->errorCode = isset($error->code)?$error->code:'';
    	$this->errorMessage = isset($error->message)?$error->message:'';
    	$this->requestResource = isset($error -> resource)?$error -> resource:'';
    	$this->requestId = isset($error -> requestId)?$error -> requestId:'';
    	
    }
	protected function parse_errxml($error_xml) {
		$tmp_xml = $error_xml;
		$error_xml = $this->get_xmlpart($error_xml);
		if(false == $error_xml) {
			$this->parse_errjson($tmp_xml);
			return;
		}
		$doc = new DOMDocument();
		$doc->loadXML($error_xml);

		$errorCode  = $doc->getElementsByTagName('Code')->item(0);
		$this->errorCode = empty($errorCode) ? 'UnknownErrorCode' : $errorCode->nodeValue;

		$errorMessage = $doc->getElementsByTagName('Message')->item(0);
		$this->errorMessage = empty($errorMessage) ? 'UnknownErrorMessage' : $errorMessage->nodeValue;

		$requestId = $doc->getElementsByTagName('RequestId')->item(0);
		$this->requestId = empty($requestId) ? 'UnknownRequestId' : $requestId->nodeValue;

		$requestResource = $doc->getElementsByTagName('Resource')->item(0);
		$this->requestResource = empty($requestResource) ? 'UnknownRequestResource' : $requestResource->nodeValue;
	}
	
	/**
	 * Get xml part from response body
	 */
	protected function get_xmlpart($response_body) {
       $tmparray = explode("\r\n\r\n", $response_body);
       $realbody = array();	
       $flag = true;
       for($i=0;$i<count($tmparray);$i++) {
       	$tmp = trim($tmparray[$i]);
       	//printf("\nvc".substr($tmp,0,strlen("<?xml"))."\n");
       	if(substr($tmp,0,strlen("<?xml")) === "<?xml") {
       		break;
       	}
       }
       if($flag) return false;//not a xml body
       for(;$i<count($tmparray);$i++) {
       	 $realbody[]=$tmparray[$i];
       }
       
       $realxml = implode("\r\n\r\n",$realbody);
      // printf("realxml:\n".$realxml."\n");
       return $realxml;
	}
}

?>