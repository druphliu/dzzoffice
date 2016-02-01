<?php
/*
 * Created on 2013-7-26
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class MultipartUpload {
	protected $bucket;// bucket's name
	protected $key;//the object name of this Multipart Upload
	protected $uploadid;//id used to identify multipartupload
	protected $initiated; //when this upload initiated 

	/**Tags used in parse xml body*/
	const InitiateMultipartUploadResultTag = "InitiateMultipartUploadResult";
	const bucketTag = "Bucket";
	const keyTag = "Key";
	const uploadIdTag = "UploadId";
	const initiatedTag = "Initiated";


	public function get_bucket() {
		return $this->bucket;
	}

	public function get_key() {
		return $this->key;
	}

	public function get_uploadid() {
		return $this->uploadid;
	}

	public function get_initated() {
		return $this->initiated;
	}

	public function to_array() {
		$meta_data =array(
				self::bucketTag => $this->bucket,
				self::keyTag => $this->key,
				self::uploadIdTag => $this->uploadid
		);

		if (! empty($this->initiated)) {
			$meta_data[self::initiatedTag] = $this->initiated;
		}
		return $meta_data;
	}
	

	public function init_from_json($json){
		$info = json_decode($json,true);
		return $this->init_from_array($info);
	}
	
	public function init_from_array($info){
		$this->bucket = isset($info[self::bucketTag])?$info[self::bucketTag]:'';
		$this->key = isset($info[self::keyTag])?$info[self::keyTag]:'';
		$this->uploadid = isset($info[self::uploadIdTag])?$info[self::uploadIdTag]:'';
		$this->initiated = isset($info[self::initiatedTag])?$info[self::initiatedTag]:'';
		return $this;
	}
}