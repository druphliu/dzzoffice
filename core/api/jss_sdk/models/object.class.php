<?php

require_once JSS_API_PATH.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'BasicObject.class.php';
// StorageObject对象
class StorageObject extends BasicObject{

	public function __construct($key, $size='', $last_modified='', $etag='') {
		parent::__construct();
		if(is_array($key)) {
			$this->init_from_array($key);
			return $this;
		}
		$this->key = $key;
		$this->size = $size;
		$this->etag = $etag;
		$this->last_modified = $last_modified;

		return $this;
	}
}

?>