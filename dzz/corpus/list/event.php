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
$operation=empty($_GET['operation'])?'':trim($_GET['operation']);
if($operation=='getEventByDate'){
	$date=trim($_GET['date']);
	$uid=intval($_GET['uid']);
	if($count=C::t('corpus_event')->fetch_all_by_bz_date('corpus_'.$cid,$date,$uid,true)){
		$list=C::t('corpus_event')->fetch_all_by_bz_date('corpus_'.$cid,$date,$uid);
		$datearr=array_keys($list);
		$lastdate=array_pop($datearr);
	}
	$list_count=0;
	foreach($list as $value){
		$list_count+=count($value);
	}
	$next=false;
	if($count && $count>$list_count) $next=true;
	include template('list/event_item');
}else{
	$navtitle="文档回顾 - ".$navtitle;
	$navlast='文档回顾';
	$limit=50;
	$users=C::t('corpus_user')->fetch_all_by_perm($cid,array('2','3'),50);
	$uid=intval($_GET['uid']);
	if($count=C::t('corpus_event')->fetch_all_by_bz_date('corpus_'.$cid,"now",$uid,true)){
		$list=C::t('corpus_event')->fetch_all_by_bz_date('corpus_'.$cid,"now",$uid);
		$datearr=array_keys($list);
		$lastdate=array_pop($datearr);
	}
	$list_count=0;
	foreach($list as $value){
		$list_count+=count($value);
	}
	$next=false;
	if($count && $count>$list_count) $next=true;
	include template('list/event');
}
?>
