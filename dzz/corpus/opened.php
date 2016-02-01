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
$do=trim($_GET['do']);
$navtitle='文集首页 - 公开的';
$navlast="公开的文集";
$archiveview=C::t('corpus_setting')->fetch('archiveview');

if($do=='getMore'){
	$perpage=20;
	$keyword=trim($_GET['keyword']);
	$start=intval($_GET['start']);
	$limit=$start.'-'.$perpage;
	$next=false;
	$count=C::t('corpus')->getOpenedCorpus($limit,$keyword,true);
	$list=C::t('corpus')->getOpenedCorpus($limit,$keyword);
	if($count>$start+$perpage) $next=true;
	$nextstart=$start+$perpage;
	include template('corpus_opened_item');
	exit();
}elseif($do=='getUserPerm'){
	$cid=intval($_GET['cid']);
	$perm=C::t('corpus_user')->getUserPermByCid($cid,$_G['uid']);
	exit(json_encode(array('perm'=>$perm)));
}elseif($do=='follow'){
	$cid=intval($_GET['cid']);
	$follow=intval($_GET['follow']);
	if(!$follow){
		$data=C::t('corpus')->fetch($cid);
		if($data['perm']>0) exit(json_encode(array('error'=>'隐私的文集不能关注')));
		if(C::t('corpus_user')->insert_uids_by_cid($cid,array($_G['uid']),1)){
			exit(json_encode(array('msg'=>'success')));
		}else{
			exit(json_encode(array('error'=>'关注失败')));
		}
	}else{
		if($return=C::t('corpus_user')->remove_uid_by_cid($cid,$_G['uid'])){
			if(is_array($return) && $return['error']){
				exit(json_encode(array('error'=>$return['error'])));
			}else{
				exit(json_encode(array('msg'=>'success')));
			}
		}else{
			exit(json_encode(array('error'=>'取消关注失败')));
		}
	}
	
}else{
	$list=array();
	$limit=20;
	$next=false;
	$keyword=trim($_GET['keyword']);
	$count=C::t('corpus')->getOpenedCorpus($limit,$keyword,true);
	$list=C::t('corpus')->getOpenedCorpus($limit,$keyword);
	if($count>$limit) $next=true;
	$nextstart=$limit;
}
include template('corpus_opened');
?>
