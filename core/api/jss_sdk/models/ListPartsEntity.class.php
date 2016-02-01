<?php
/*
 * Created on 2013-7-25
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class ListPartsEntity {
	protected $bucket;
	protected $objectKey;
    protected $uploadId;
    protected $partNumberMarker;
    protected $nextPartNumberMarker;
    protected $maxParts;
    protected $hasNext;
    protected $parts_list = array();
    
    const BUCKET_TAG = "Bucket";
    const KEY_TAG = "Key";
    const UPLOADID_TAG = "UploadId";
    const PART_NUMBER_MARKER_TAG = "PartNumberMarker";
    const NEXT_PART_NUMBER_MARKER_TAG = "NextPartNumberMarker";
    const MAX_PARTS_TAG = "MaxParts";
    const HAS_NEXT_TAG = "HasNext";
    const PART_TAG = "Part";
    
    public function __construct() {
		return $this;
	}
	
    public function init_from_json($json) {
    	
    	$info = json_decode($json,true);
    	$this->bucket = isset($info[self::BUCKET_TAG])?$info[self::BUCKET_TAG]:'';
    	$this->objectKey = isset($info[self::KEY_TAG])?$info[self::KEY_TAG]:'';
    	$this->uploadId = isset($info[self::UPLOADID_TAG])?$info[self::UPLOADID_TAG]:'';
    	$this->partNumberMarker = isset($info[self::PART_NUMBER_MARKER_TAG])?$info[self::PART_NUMBER_MARKER_TAG]:'';
    	$this->nextPartNumberMarker = isset($info[self::NEXT_PART_NUMBER_MARKER_TAG])?$info[self::NEXT_PART_NUMBER_MARKER_TAG]:'';
    	$this->maxParts =  isset($info[self::MAX_PARTS_TAG])?$info[self::MAX_PARTS_TAG]:'';
    	$this->hasNext = isset($info[self::HAS_NEXT_TAG])?$info[self::HAS_NEXT_TAG]:'';
    	if(isset($info[self::PART_TAG])) {
    		foreach($info[self::PART_TAG] as $part) {
    			$cur_part = new MultiSinglePart();
    			$this->parts_list[] = $cur_part->init_from_array($part);//->init_from_array($part));
    		}
    	}
    	
    	return $this;
    }
    
    public function get_part_list_to_array(){
    	$cur_parts = array();
    	foreach($this->parts_list as $part) {
    		$cur_parts[] = $part->to_array();
    	}
    	return $cur_parts;
    }
     public function to_array(){
    	return array(
    	    self::BUCKET_TAG => $this->bucket,
    	    self::KEY_TAG => $this->objectKey,
    	    self::UPLOADID_TAG => $this->uploadId,
    	    self::PART_NUMBER_MARKER_TAG => $this->partNumberMarker,
    	    self::NEXT_PART_NUMBER_MARKER_TAG => $this->nextPartNumberMarker,
    	    self::MAX_PARTS_TAG => $this->maxParts,
    	    self::HAS_NEXT_TAG => $this->hasNext,
    	    self::PART_TAG => $this->get_part_list_to_array()
    	);
    }
    
    public function to_completemultipartuploadjson(){
    	$userful = array();
    	foreach($this->parts_list as $one_part) {
    		$userful[] = $one_part->to_completemultipartupload_array();
    	}
    	return json_encode($userful);
    }

	public function get_bucket(){
		return $this->bucket;
	}
	
	public function get_objectKey(){
		return $this->objectKey;
	}
	
	public function get_uploadId(){
		return $this->uploadId;
	}
	
	public function get_PartNumberMarker(){
		return $this->partNumberMarker;
	}
	
	public function get_nextPartNumberMarker(){
		return $this->nextPartNumberMarker;
	}
	
	public function get_maxParts(){
		return $this->maxParts;
	}
	
	public function get_hasNext(){
		return $this->hasNext;
	}
	
	public function get_part_list(){
		return $this->parts_list;
	}
    
}
