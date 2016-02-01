<?php
class MultipartUploadEntity {
	protected $bucket; // bucket名称
	protected $delimiter; // 获取对象时使用的分隔符
	protected $prefix = ''; // 获取对象时前缀过滤字符串
	protected $keyMarker = '';
	protected $uploadIdMarker = '';
	protected $nextKeyMarker = '';
	protected $nextUploadIdMarker = '';
	protected $upload = array();
	protected $commonPrefix = array();

	
	const BUCKET_TAG = 'Bucket';
	const DELEMITER_TAG = 'Delimiter';
	const PREFIX_TAG = "Prefix";
	const KEY_MARKER = "KeyMarker";
    const UPLOADID_MARKER = "UploadIdMarker";
    const NEXT_KEY_MARKER = "NextKeyMarker";
    const NEXT_UPLOAD_ID_MARKER = "NextUploadIdMarker";
    const UPLOAD_TAG = "Upload";
    const COMMON_PREFIXS = "CommonPrefixes";
    
    public function to_array(){
    	$info = array(
    	    self::BUCKET_TAG => $this->bucket,
    	    self::DELEMITER_TAG => $this->delimiter,
    	    self::PREFIX_TAG => $this->prefix,
    	    self::KEY_MARKER => $this->keyMarker,
    	    self::UPLOADID_MARKER => $this->uploadIdMarker,
    	    self::NEXT_KEY_MARKER => $this->nextKeyMarker,
    	    self::NEXT_UPLOAD_ID_MARKER => $this->nextUploadIdMarker,
    	    self::UPLOAD_TAG => $this->upload,
    	    self::COMMON_PREFIXS => $this->commonPrefix
    	);
    	return $info;
    }	     
    public function init_from_json($objects_json){
		$objects_list = json_decode($objects_json,true);
		$this->bucket = $objects_list[self::BUCKET_TAG];
		$this->prefix = $objects_list[self::PREFIX_TAG];
		$this->commonPrefix = $objects_list[self::COMMON_PREFIXS];
		$this->keyMarker = isset($objects_list[self::KEY_MARKER])?$objects_list[self::KEY_MARKER]:'';
		$this->uploadIdMarker =isset($objects_list[self::UPLOADID_MARKER])?$objects_list[self::UPLOADID_MARKER]:'';
		$this->nextKeyMarker = isset($objects_list[self::NEXT_KEY_MARKER])?$objects_list[self::NEXT_KEY_MARKER]:'';
		$this->nextUploadIdMarker = isset($objects_list[self::NEXT_UPLOAD_ID_MARKER])?$objects_list[self::NEXT_UPLOAD_ID_MARKER]:'';
		$this->delimiter = isset($objects_list[self::DELEMITER_TAG])?$objects_list[self::DELEMITER_TAG]:'';
		foreach($objects_list[self::UPLOAD_TAG] as $one_object) {
			$multipart = new MultipartUpload();
			$this->upload[] =$multipart->init_from_array($one_object);
		}
		
		return $this;
	}
    
	public function __construct() {
		return $this;
	}

	public function get_bucket() {
		return $this->bucket;
	}

	public function get_prefix() {
		return $this->prefix;
	}

	public function get_keyMarker() {
		return $this->keyMarker;
	}

    public function get_uploadIdMarker() {
    	return $this->uploadIdMarker;
    }
    
	public function get_nextKeyMarker() {
		return $this->nextKeyMarker;
	}

	public function get_nextUploadIdMarker() {
		return $this->nextUploadIdMarker;
	}

	public function get_delimiter() {
		return $this->delimiter;
	}
}

?>