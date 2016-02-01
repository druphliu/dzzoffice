<?php

require_once JSS_API_PATH.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.'BasicObject.class.php';
/**
 * Part in Multipart upload 
 */
class MultiSinglePart extends BasicObject{
    const PART_NUMBER_TAG = 'PartNumber';
	public function __construct() {
		parent::__construct();
		return $this;
	}

    public function init_from_array($info=array()){
    	parent::init_from_array($info);
    	$this->key = isset($info[self::PART_NUMBER_TAG])?$info[self::PART_NUMBER_TAG]:'';
    	return $this;
    }
    
	public function get_part_numer(){
		return $this->key;
	}

	public function to_array() {
		$result = parent::to_array();
		$result[self::PART_NUMBER_TAG] = $this->get_part_numer();
		return $result;
	}
	
	public function to_completemultipartupload_array(){
		return array(
		  self::PART_NUMBER_TAG => $this->get_part_numer(),
		  ETAG => $this->etag
		);
	}
}

?>