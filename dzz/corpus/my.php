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
include libfile('function/corpus');
$navtitle='文集首页 - 我的';
$navlast="我的文集";

$setting=C::t('corpus_setting')->fetch_all(array('moderators','maxcorpus','allownewcorpus'));
$ismoderator=0;
$archiveview=$setting['archiveview'];
if($setting['allownewcorpus']>0){
	$ismoderator=getAdminPerm($setting['moderators']);
}elseif($_G['uid']>0){
	$ismoderator=1;
}
if(($_G['adminid']<1) &&  $setting['maxcorpus'] && !C::t('corpus')->checkMaxCorpus($_G['uid'])){
	$ismoderator=0;
}
if($_G['adminid']==1) $ismoderator=1;

if($_GET['do']=='mySort'){
	$cids=trim($_GET['cids']);
	C::t('corpus_setting')->update('paixu_'.$_G['uid'],$cids);
	exit('success');
}elseif($_GET['do']=='getUserPerm'){
	$cid=intval($_GET['cid']);
	$perm=C::t('corpus_user')->getUserPermByCid($cid,$_G['uid']);
	exit(json_encode(array('perm'=>$perm)));
}elseif($_GET['do']=='follow'){
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
	$keyword=trim($_GET['keyword']);
	$list=array();
	$list=C::t('corpus')->getMyCorpus($_G['uid'],$keyword);
	include template('corpus_my');
}
?>
