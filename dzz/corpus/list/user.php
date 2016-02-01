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
$permtitle=array('1'=>'关注成员','2'=>'协作成员','3'=>'管理员');
$operation=empty($_GET['operation'])?'':trim($_GET['operation']);
if($operation=='selectuser'){
	if($corpus['perm']<3){
		showmessage('没有权限',dreferer());
	}
	if(submitcheck('selectsubmit')){
		$uids=$_GET['uids'];
		foreach($uids as $key =>$value){
			if($value==$_G['uid']){
				unset($uids[$key]);
			}
		}
		$perm=intval($_GET['perm']);
		if(C::t('corpus_user')->insert_uids_by_cid($cid,$uids,$perm)){
			showmessage('do_success',DZZSCRIPT.'?mod=corpus&op=list&do=user&cid='.$cid);
		}
	}else{
		$type=intval($_GET['type']);
		$title=$type==2?'协作成员':'关注成员';
		$navtitle=$title." - ".$navtitle;
		$navlast=$title;
		$refer=dreferer();
		$uids=C::t('corpus_user')->fetch_uids_by_cid($cid);
		include template('list/utree');
	}
}elseif($operation=='deleteUser'){
	if($corpus['perm']<3){
		exit(json_encode(array('error'=>'没有权限')));
	}
	$uid=intval($_GET['uid']);
	$arr=array();
	if($return=C::t('corpus_user')-> remove_uid_by_cid($cid,$uid)){
		if(is_array($return) && $return['error']){
			$arr['error']=$return['error'];
		}else{
			$arr['msg']='success';
		}
	}else{
		$arr['error']='删除失败';
	}
	exit(json_encode($arr));
}elseif($operation=='changeUserPerm'){
	if($corpus['perm']<3){
		exit(json_encode(array('error'=>'没有权限')));
	}
	$uid=intval($_GET['uid']);
	$perm=intval($_GET['perm']);
	$arr=array();
	if($return=C::t('corpus_user')-> change_perm_by_uid($cid,$uid,$perm)){
		if(is_array($return) && $return['error']){
			$arr['error']=$return['error'];
		}else{
			$arr['msg']='success';
		}
	}else{
		$arr['error']='删除失败';
	}
	exit(json_encode($arr));
}else if($operation=='getMore'){
	$perpage=50;
	$type=intval($_GET['type']);
	$start=intval($_GET['start']);
	if($type==1){
		$permarr=array('1');
	}else{
		$permarr=array('2','3');
	}
	$limit=$start.'-'.$perpage;
	$next=false;
	$list=array();
	$count=C::t('corpus_user')->fetch_all_by_perm($cid,$permarr,$limit,true);
	
	foreach(C::t('corpus_user')->fetch_all_by_perm($cid,$permarr,$limit) as $value){
		$value['permtitle']=$permtitle[$value['perm']];
		$list[]=$value;
	}
	if($count>$start+$perpage) $next=true;
	$nextstart=$start+$perpage;
	include template('list/user_item');
}else{
	
	$limit=50;
	$navtitle="用户管理 - ".$navtitle;
	$navlast='用户管理';
	$userlist=$follows=array();
	$follows_next=$userlist_next=false;
	$userlist_count=C::t('corpus_user')->fetch_all_by_perm($cid,array('2','3'),$limit,true);
	foreach(C::t('corpus_user')->fetch_all_by_perm($cid,array('2','3'),$limit) as $value){
		$value['permtitle']=$permtitle[$value['perm']];
		$userlist[]=$value;
	}
	if($userlist_count>$limit) $userlist_next=true;
	$follows_count=C::t('corpus_user')->fetch_all_by_perm($cid,array('1'),$limit,true);
	foreach(C::t('corpus_user')->fetch_all_by_perm($cid,array('1'),$limit) as $value){
		$value['permtitle']=$permtitle[$value['perm']];
		$follows[]=$value;
	}
	if($follows_count>$limit) $follows_next=true;
	//print_r($follows);exit('dfdsf');
	include template('list/user');
}

?>
