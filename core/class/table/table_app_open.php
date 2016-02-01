<?php
/*
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
if(!defined('IN_DZZ')) {
	exit('Access Denied');
}

class table_app_open extends dzz_table
{
	public function __construct() {
		$this->_table = 'app_open';
		$this->_pk    = 'extid';
		$this->_pre_cache_key = 'app_open_';
		$this->_cache_ttl =0;
		parent::__construct();
	}
	public function setDefault($extid){
		$data=self::fetch($extid);
		DB::update($this->_table,array('isdefault'=>0),"ext='{$data[ext]}'");
		$this->clear_cache('ext_all');
		$this->clear_cache('all');
		return self::update($extid,array('isdefault'=>1));
		
	}
	public function delete_by_appid($appid){
			if(!$appid) return false;
			$this->clear_cache('ext_all');
			$this->clear_cache('all');
			return DB::delete($this->_table," appid='{$appid}'");
		}
	public function insert_by_exts($appid,$exts){
		if(!$appid) return false;
		if(!is_array($exts)) $exts=$exts?explode(',',$exts):array();
		//删除原来的ext
		$oexts=array();
		$delids=array();
		$oextarr=DB::fetch_all("select * from ".DB::table('app_open')." where appid='{$appid}'");
		foreach($oextarr as $value){
			$oexts[]=$value['ext'];
			if(!in_array($value['ext'],$exts)) $delids[]=$value['extid'];
		}
		if($delids) {
			self::delete($delids);
		}
		foreach($exts as $ext){
			if($ext && !in_array($ext,$oexts))	parent::insert(array('ext'=>$ext,'appid'=>$appid));
		}
		$this->clear_cache('ext_all');
		$this->clear_cache('all');
		return true;
	}
	
	public function fetch_all_ext(){
		$data = array();
		if(($data = $this->fetch_cache('all')) === false) {
			$data = array();
			$query=DB::query("SELECT * FROM %t WHERE 1 ",array($this->_table));
			while($value=DB::fetch($query)){
				if($value['appid']){
					 if($app=C::t('app_market')->fetch_by_appid($value['appid'],false)){
						 if($app['available']<1) continue;
						 if(!$value['icon']) $value['icon']=$app['appico'];
						 if(!$value['name']) $value['name']=$app['appname'];
						 if(!$value['url'])  $value['url']=$app['appurl'];
						 if(!$value['nodup']) $value['nodup']=$app['nodup'];
						 if(!$value['feature']) $value['feature']=$app['feature'];
					 }else{
						continue; 
					 }
				}
				$value['url']=replace_canshu($value['url']);
				$data[$value['extid']]=$value;
			}
			if(!empty($data)) $this->store_cache('all', $data);
		}
		return $data;
	}
	public function fetch_all_orderby_ext($uid){
		$data = array();
		$appids=C::t('icos')->fetch_appids_by_uid($uid);
		foreach(self::fetch_all_ext() as $value){
			if($value['appid'] && !in_array($value['appid'],$appids)){
				continue;
			}
			$data[$value['ext']][]=$value['extid'];
		}
		return $data;
		
	}
	
}
?>
