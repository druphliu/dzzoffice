<?php
/* @authorcode  c847417817641cfe67af4008fac750a0
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */

if(!defined('IN_DZZ')) {
	exit('Access Denied');
}

class table_corpus_user extends dzz_table
{
	public function __construct() {

		$this->_table = 'corpus_user';
		$this->_pk    = 'id';

		parent::__construct();
	}
	public function fetch_all_by_uid($uid){
		return DB::fetch_all("select * from %t where uid=%d ",array($this->_table,$uid,$perm)) ;
	}
	public function fetch_all_by_cid($cid){
		//if(!is_array($perm)) $perm=array($perm);
		return DB::fetch_all("select * from %t where cid=%d order by perm DESC,dateline DESC",array($this->_table,$cid,$perm)) ;
	}
	public function fetch_uids_by_cid($cid){
		//if(!is_array($perm)) $perm=array($perm);
		$uids=array();
		foreach(DB::fetch_all("select uid from %t where cid=%d ",array($this->_table,$cid)) as $value){
			$uids[]=$value['uid'];
		}
		return $uids;
	}
	public function fetch_perm_by_uid($uid,$cid){
		$user=getuserbyuid($uid);
		if($user['adminid']==1) return 3;
		if(DB::result_first("select COUNT(*) from %t where cid=%d and uid=%d",array($this->_table,$cid,$uid))){
			return DB::result_first("select `perm` from %t where cid=%d and uid=%d",array($this->_table,$cid,$uid));
		 }else{
			 return 0;
		 }
		return DB::result_first("select perm from %t where cid=%d and uid=%d",array($this->_table,$cid,$uid));
	}
	public function insert($arr){
		return parent::insert($arr,1,1);
	}
	public function insert_uids_by_cid($cid,$uids,$perm){
		$ouids=array();
		foreach(DB::fetch_all("select * from %t where cid=%d and  uid in (%n)",array($this->_table,$cid,$uids)) as $value){
			parent::update($value['id'],array('perm'=>$perm));
			$ouids[]=$value['uid'];
		}
		$uids=array_diff($uids,$ouids);
		$user=C::t('user')->fetch_all($uids);
		
		$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=corpus',1);
		$corpus=C::t('corpus')->fetch($cid);
		$permtitle=array('1'=>'关注成员','2'=>'协作成员','3'=>'管理员');
		foreach($uids as $uid){
			$userarr=array('uid'=>$uid,
						   'username'=>$user[$uid]['username'],
						   'perm'=>$perm,
						   'dateline'=>TIMESTAMP,
						   'cid'=>$cid,
						   );
		    if(C::t('corpus_user')->insert($userarr)){
			
				if($uid!=getglobal('uid')){
					//发送通知
					
					$notevars=array(
									'from_id'=>$appid,
									'from_idtype'=>'app',
									'url'=>DZZSCRIPT.'?mod=corpus&op=list&cid='.$corpus['cid'].'&do=user',
									'author'=>getglobal('username'),
									'authorid'=>getglobal('uid'),
									'dataline'=>dgmdate(TIMESTAMP),
									'corpusname'=>getstr($corpus['name'],30),
									'permtitle'=>$permtitle[$perm],
									);
					
						$action='corpus_user_add';
						$type='corpus_user_add_'.$cid;
					
					dzz_notification::notification_add($uid, $type, $action, $notevars, 0,'dzz/corpus');
				}
			}
		}
		return true;
	}
	public function remove_uid_by_cid($cid,$uid){
		//管理员必须留一人
		$data=DB::fetch_first("select * from %t where cid=%d and uid=%d",array($this->_table,$cid,$uid));
		if($data['perm']>2 && DB::result_first("select COUNT(*) from %t where cid=%d and perm>2",array($this->_table,$cid))<2){
			return array('error'=>'至少需要一名管理员');
		}
		$permtitle=array('1'=>'关注成员','2'=>'协作成员','3'=>'管理员');
			if($uid!=getglobal('uid')){
				//发送通知
				$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=corpus',1);
				$corpus=C::t('corpus')->fetch($cid);
				$notevars=array(
								'from_id'=>$appid,
								'from_idtype'=>'app',
								'url'=>DZZSCRIPT.'?mod=corpus&op=list&cid='.$corpus['cid'].'&do=user',
								'author'=>getglobal('username'),
								'authorid'=>getglobal('uid'),
								'dataline'=>dgmdate(TIMESTAMP),
								'corpusname'=>getstr($corpus['name'],30),
								'permtitle'=>$permtitle[$data['perm']],
								);
				
					$action='corpus_user_remove';
					$type='corpus_user_remove_'.$cid;
				
				dzz_notification::notification_add($uid, $type, $action, $notevars, 0,'dzz/corpus');
			}
		return parent::delete($data['id']);
	}
	public function change_perm_by_uid($cid,$uid,$perm){
		//管理员必须留一人
		$data=DB::fetch_first("select * from %t where cid=%d and uid=%d",array($this->_table,$cid,$uid));
		if($data['perm']>2 && $perm<3 && DB::result_first("select COUNT(*) from %t where cid=%d and perm>2",array($this->_table,$cid))<2){
			return array('error'=>'至少需要一名管理员');
		}
		parent::update($data['id'],array('perm'=>$perm,'dateline'=>TIMESTAMP));
		if($data['perm']!=$perm){//权限改变
			$permtitle=array('1'=>'关注成员','2'=>'协作成员','3'=>'管理员');
			if($uid!=getglobal('uid')){
				//发送通知
				$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=corpus',1);
				$corpus=C::t('corpus')->fetch($cid);
				$notevars=array(
								'from_id'=>$appid,
								'from_idtype'=>'app',
								'url'=>DZZSCRIPT.'?mod=corpus&op=list&cid='.$corpus['cid'].'&do=user',
								'author'=>getglobal('username'),
								'authorid'=>getglobal('uid'),
								'dataline'=>dgmdate(TIMESTAMP),
								'corpusname'=>getstr($corpus['name'],30),
								'permtitle'=>$permtitle[$perm],
								);
				
					$action='corpus_user_change';
					$type='corpus_user_change_'.$cid;
				
				dzz_notification::notification_add($uid, $type, $action, $notevars, 0,'dzz/corpus');
			}
		}
		return true;
	}
	public function fetch_all_by_perm($cid,$perm,$limit,$iscount){
		if(!is_array($perm)) $perm=array($perm);
		$limitsql='';
		if($limit){
			$limit=explode('-',$limit);
			if(count($limit)>1){
				$limitsql.=" limit ".intval($limit[0]).",".intval($limit[1]);
			}else{
				$limitsql.=" limit ".intval($limit[0]);
			}
		}
		if($iscount) return DB::result_first("select COUNT(*) from %t where cid=%d and perm in(%n)",array($this->_table,$cid,$perm));
		else return  DB::fetch_all("select * from %t where cid=%d and perm in(%n) order by perm DESC,dateline ASC $limitsql ",array($this->_table,$cid,$perm));
	}
	public function getUserPermByCid($cid,$uid){//获取用户的权限，没有的话用户权限为0
	     if(DB::result_first("select COUNT(*) from %t where cid=%d and uid=%d",array($this->_table,$cid,$uid))){
			return DB::result_first("select `perm` from %t where cid=%d and uid=%d",array($this->_table,$cid,$uid));
		 }else{
			 return 0;
		 }
	}
}

?>
