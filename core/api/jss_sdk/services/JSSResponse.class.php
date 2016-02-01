<?php
define('CONTENT_TYPE_TAG','Content-Type');
class JSSResponse {
	/**
	 * JSS Response
	 */
    private $code;
    private $body;
    private $headers = array();
    private $length = 0;
    
    /**
	 * last curl error
	 * @access protected
	 */
	private $last_curl_error;
    
    
   /* public function JSSResponse($code,$body,$headers){
    	$this->code = $code;
    	$this->body = $body;
    	$this->headers = $headers;
    }*/
    
    public function set_code($code){
        $this->code = $code;
        return $this;    	
    }   
    
    public function get_code(){
    	return $this->code;
    }
    
    public function set_body($body) {
    	$this->length = strlen($body);
    	$this->body = $body;
    	return $this;
    }
    
    public function get_body() {
    	return $this->body;
    }
    
    public function set_headers($headers){
    	$this->headers = $headers;
    	return $this;
    }
    
    public function get_headers() {
    	return $this->headers;
    }
    
    public function get_header($field){
    	$field = trim(strtolower($field));
    	foreach($this->headers as $key=>$value) {
    		if(trim(strtolower($key)) === $field) {
    			return $value;
    		}
    	}
    	return '';
    }
    public function get_length() {
    	return $this->length;
    }
    
    public function set_last_curl_error($last_curl_error){
    	$this->last_curl_error = $last_curl_error;
    	return $this;
    }
    
    public function get_last_curl_error(){
    	return $this->last_curl_error;
    }
    
    public function to_array() {
    	return array(
    	 "code" => $this->code,
    	 "body" => $this->body,
    	 "header" => $this->headers,
    	 "length" => $this->length,
    	 "last_curl_error" => $this->last_curl_error
    	);
    }
    
    public function is_json_response(){
    	if(strstr($this->get_header(CONTENT_TYPE_TAG),'json')) {
    		return true;
    	}
    	return false;
    }
    
    public function is_xml_response() {
    	if(strstr($this->get_header(CONTENT_TYPE_TAG),'xml')) {
    		return true;
    	}
    	return false;
    }
    
    public function check_response($right_code = false){
    	if(false == $right_code) {
	    	if(  $this->code >= 300) {
	    		throw new JSSError($this);
	    	} 
    	}else if($right_code != $this->code) {
    		throw new JSSError($this);
    	}
    	return $this;
    }
    
    public function is_ok($right_code = false){
    	if(false === $right_code ) {
    		return $this->code < 300;
    	} else {
    		return $right_code === $this->code;
    	}
    }
}
?>
