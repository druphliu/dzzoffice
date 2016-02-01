<?php
if(!defined('IN_DZZ')) {
	exit('Access Denied');
}

function build_cache_app_setting() {
	global $_G;
	$data=array();
	$data=C::t('#corpus#corpus_setting')->fetch_all();
	savecache('corpus:setting', $data);
}

?>