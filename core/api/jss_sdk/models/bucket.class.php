<?php

// Bucket对象
class Bucket {

	protected $name; // bucket名称
	protected $ctime; // bucket创建时间，参见：date('r')

	public function __construct( $name, $ctime) {
		$this->name = $name;
		$this->ctime = $ctime;

		return $this;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_ctime() {
		return $this->ctime;
	}

	public function to_array() {
		return array(
				'name' => $this->name,
				'ctime' => $this->ctime
		);
	}
}

?>