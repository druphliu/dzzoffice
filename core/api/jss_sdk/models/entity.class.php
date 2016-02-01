<?php

// Entity对象
class JSSEntity {
	protected $bucket; // bucket名称
	protected $prefix = ''; // 获取对象时前缀过滤字符串
	protected $commonPrefix = array();
	protected $marker = ''; // 获取对象时偏移对象的名称
	protected $nextmarker;
	protected $maxkeys; // 获取对象时返回的最大记录数
	protected $delimiter; // 获取对象时使用的分隔符
	protected $hasNext = false; // 返回结果是否经过截短？
	protected $objectarray = array(); // object list array
	
	const BUCKET_NAME_TEG = 'Name';
	const DELIMITER_TEG = 'Delimiter';
	const PREFIX_TAG = "Prefix";
	const MAXKEYS_TAG = "MaxKeys";
	const MARKER_TAG = "Marker";
	const NEXTMARKER_TAG = "NextMarker";
	const CONTENTS_TAG = "Contents";
	const COMMONPREFIXES = "CommonPrefixes";
	const HAS_NEXT_KEY = 'HasNext';
	

	public function __construct() {
		return $this;
	}

	 public function get_nextmarker() {
		return $this->nextmarker;
	} 
	 public function get_commonPrefix() {
		return $this->commonPrefix;
	} 
	public function set_bucket($bucket) {
		$this->bucket = $bucket;
	}

	public function get_bucket() {
		return $this->bucket;
	}

	public function set_prefix($prefix) {
		$this->prefix = $prefix;
	}

	public function get_prefix() {
		return $this->prefix;
	}

	public function set_marker($marker) {
		$this->marker = $marker;
	}

	public function get_marker() {
		return $this->marker;
	}

	public function set_maxkeys($maxkeys) {
		$this->maxkeys = $maxkeys;
	}

	public function get_maxkeys() {
		return $this->maxkeys;
	}

	public function set_delimiter($delimiter) {
		$this->delimiter = $delimiter;
	}

	public function get_delimiter() {
		return $this->delimiter;
	}

	public function set_hasNext($hasNext) {
		$this->hasNext = $hasNext;
	}

	public function get_hasNext() {
		return $this->hasNext;
	}

	public function add_object($object) {
		$this->objectarray[]= $object;
	}

	public function get_object($idx=null) {
		if ($idx === null) {
			return $this->objectarray;
		}

		$max = count($this->objectarray);

		$idx = intval($idx);
		if ($idx < 0) {
			$idx += $max;
		}

		if ($idx >= 0 && $idx < $max) {
			return $this->objectarray[$idx];
		}

		return null;
	}

	public function to_array() {
		return array(
				self::BUCKET_NAME_TEG => $this->bucket,
				self::PREFIX_TAG => $this->prefix,
				self::MARKER_TAG => $this->marker,
				self::MAXKEYS_TAG => $this->maxkeys,
				self::DELIMITER_TEG => $this->delimiter,
				self::HAS_NEXT_KEY => $this->hasNext,
				self::CONTENTS_TAG => $this->objectarray
		);
	}
	
	     
    public function init_from_json($objects_json){
		$objects_list = json_decode($objects_json,true);
		$this->bucket = $objects_list[self::BUCKET_NAME_TEG];
		$this->prefix = $objects_list[self::PREFIX_TAG];
		$this->commonPrefix = $objects_list[self::COMMONPREFIXES];
		$this->marker = isset($objects_list[self::MARKER_TAG])?$objects_list[self::MARKER_TAG]:'';
		$this->nextmarker =isset($objects_list[self::NEXTMARKER_TAG])?$objects_list[self::NEXTMARKER_TAG]:'';
		$this->maxkeys = $objects_list[self::MAXKEYS_TAG];
		$this->delimiter = $objects_list[self::DELIMITER_TEG];
		$this->hasNext = $objects_list[self::HAS_NEXT_KEY];
		foreach($objects_list[self::CONTENTS_TAG] as $one_object) {
			$this->objectarray[] = new StorageObject($one_object);
		}
		return $this;
	}
	
}

?>