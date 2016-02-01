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

class table_folder extends dzz_table
{
	public function __construct() {

		$this->_table = 'folder';
		$this->_pk    = 'fid';
		$this->_pre_cache_key = 'folder_';
		$this->_cache_ttl = 0;
		parent::__construct();
	}
	public function fetch_all_by_fid($fids){
		$data=array();
		foreach(self::fetch_all($fids) as $fid => $value){
			if($arr=self::fetch_by_fid($fid)) $data[$fid]=$arr;
		}
		return $data;
	}
	public function fetch_by_fid($fid){ //返回一条数据同时加载附件表数据
	 global $_G;
	 
		$fid = intval($fid);
		if(!$data=self::fetch($fid)) return false;
		
		//$data['icon']=($data['ficon']?$data['ficon']:geticonfromext('','folder'));
		$data['title']=$data['fname'];
		
		if($data['flag']=='recycle'){
			$data['iconum']= DB::result_first("select COUNT(*) from ".DB::table('icos') ." where isdelete>0 and uid='{$_G['uid']}'");
		}elseif($data['uid']<1){
			$data['iconum']= DB::result_first("select COUNT(*) from ".DB::table('icos') ." where pfid='{$fid}' and uid='{$_G['uid']}' and isdelete<1");
		}else{
			$data['iconum']= DB::result_first("select COUNT(*) from ".DB::table('icos') ." where pfid='{$fid}' and isdelete<1");
		}
		$data['perm']=perm_check::getPerm($fid);
		$data['perm1']=perm_check::getPerm1($fid);
		
		//print_r($data);
		if($data['gid']>0){
			$data['ismoderator']=C::t('organization_admin')->ismoderator_by_uid_orgid($data['gid'],$_G['uid']);
			$permtitle=perm_binPerm::getGroupTitleByPower($data['perm1']);
			if(file_exists('dzz/images/default/system/folder-'.$permtitle['flag'].'.png')){
				$data['icon']='dzz/images/default/system/folder-'.$permtitle['flag'].'.png';
			}else{
				$data['icon']='dzz/images/default/system/folder-read.png';
			}
		}
		$data['path']=$data['fid'];
		$data['oid']=$data['fid'];
		$data['bz']='';
		return $data;
	}
	public function delete_by_fid($fid,$force){ //删除目录
		$folder=self::fetch($fid);
		if(!defined('IN_ADMIN') && $folder['flag']!='folder'){
			return;
		}
		if(!$force && !perm_check::checkperm_container($fid,'delete')){
			return array('error'=>lang('message','no_privilege'));
		}
		foreach(DB::fetch_all("select icoid from %t where pfid=%d",array('icos',$fid)) as $value){
			C::t('icos')->delete_by_icoid($value['icoid'],true);
		}
		
		//删除统计
		C::t('count')->delete_by_type($fid,'folder');
		
		//删除下级目录
		foreach(self::fetch_all_by_pfid($fid) as $value){
			self::delete_by_fid($value['fid'],$force);
		}
		return self::delete($fid);
	}
	public function empty_by_fid($fid){ //清空目录
		global $_G;
		if(!$folder=self::fetch($fid)){
			return array('error'=>lang('message','folder_not_exist'));
		}
		if(!perm_check::checkperm_container($fid,'delete')){
			return array('error'=>lang('message','no_privilege'));
		}
		if($folder['flag']=='recycle'){
			foreach(DB::fetch_all("SELECT icoid FROM %t WHERE uid=%d and isdelete>0", array('icos',$_G['uid'])) as $value){
				C::t('icos')->delete_by_icoid($value['icoid'],true);
			}
			
		}else{
			foreach(DB::fetch_all("select icoid from %t where pfid=%d",array('icos',$fid)) as $value){
				C::t('icos')->delete_by_icoid($value['icoid'],true);
			}
		}
		return true;
	}
	public function fetch_all_default_by_uid($uid){
		return DB::fetch_all("SELECT * FROM %t WHERE `default`!= '' and uid=%d  ",array($this->_table,$uid),'fid');
	}
	public function fetch_typefid_by_uid($uid){
		$data=array();
		foreach(DB::fetch_all("SELECT * FROM %t WHERE `flag`!= 'folder' and  uid='{$uid}' and gid<1  ",array($this->_table),'fid') as $value){
			$data[$value['flag']]=$value['fid'];
		}
		return $data;
	}
	public function fetch_all_by_uid(){
		return DB::fetch_all("SELECT * FROM %t WHERE  uid='0'  ",array($this->_table),'fid');
	}
	public function fetch_all_by_pfid($pfid,$count){
		if($count) return DB::result_first("SELECT COUNT(*) FROM %t WHERE pfid = %d  and isdelete<1",array($this->_table,$pfid));
		else return DB::fetch_all("SELECT * FROM %t WHERE pfid=%d and isdelete<1",array($this->_table,$pfid),'fid');
	}
	
}

?>
