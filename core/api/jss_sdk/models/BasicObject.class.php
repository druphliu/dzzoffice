<?php
define("ETAG","ETag");
define("KEY_TEG","Key");
define("LASTMODIFIED","LastModified");
define("SIZE_TEG","Size");
class BasicObject {
	protected $key; // object key
	protected $size; // object size
	protected $etag; // object ETAG
	protected $last_modified; // object last modified time

	public function __construct() {
		return $this;
	}

    public function init_from_array($info=array()){
    	$this->key = isset($info[KEY_TEG])?$info[KEY_TEG]:'';
    	$this->size = isset($info[SIZE_TEG])?$info[SIZE_TEG]:'';
    	$this->etag = isset($info[ETAG])?$info[ETAG]:'';
    	$this->last_modified = isset($info[LASTMODIFIED])? $info[LASTMODIFIED]:'';
    	return $this;
    }
    
	public function get_key() {
		return $this->key;
	}

	public function get_size() {
		return $this->size;
	}

	public function get_etag() {
		return $this->etag;
	}

	public function get_last_modified() {
		return $this->last_modified;
	}

	public function to_array() {
		return array(
				'key' => $this->key,
				'size' => $this->size,
				'etag' => $this->etag,
				'last_modified' => $this->last_modified
		);
	}
}

?>