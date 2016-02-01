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
if($corpus['perm']<3){
	showmessage('没有权限',dreferer());
}
$operation=empty($_GET['operation'])?'':trim($_GET['operation']);
if(submitcheck('recyclesubmit')){
	$dels=$_GET['del'];
	foreach($dels as $fid){
		C::t('corpus_class')->delete_by_fid($fid);
	}
	showmessage('文档彻底删除成功',urldecode($_GET['refer']));
}elseif($operation=='empty'){
	C::t('corpus_class')->recylce_empty_by_cid($cid);
	showmessage('清空回收站成功',DZZSCRIPT.'?mod=corpus&op=list&do=recycle&cid='.$cid);
}elseif($operation=='delete'){
	$fid=intval($_GET['fid']);
	C::t('corpus_class')->delete_by_fid($fid);
	showmessage('文档彻底删除成功',$_GET['refer']);
}elseif($operation=='restore'){
	$fid=intval($_GET['fid']);
	
	if(C::t('corpus_class')->restore_by_fid($fid)){
		showmessage('文档恢复成功',$_GET['refer']);
	}else{
		showmessage('文档恢复失败',$_GET['refer']);
	}
}else{
	$navtitle="回收站 - ".$navtitle;
	$navlast='回收站';
	$page = empty($_GET['page'])?1:intval($_GET['page']);
	$perpage=10;
	$keyword=trim($_GET['keyword']);
	$gets = array(
			'mod'=>'corpus',
			'keyword'=>$keyword,
			'op' =>'list',
			'do'=>'recycle',
			'cid'=>$cid,
		);
	$theurl = BASESCRIPT."?".url_implode($gets);
	$refer=urlencode($theurl.'&page='.$page);
	$limit=($page-1)*$perpage.'-'.$perpage;
	$list=array();
	if($count=C::t('corpus_class')->fetch_all_by_deletetime($cid,$limit,$keyword,true)){
		$list=C::t('corpus_class')->fetch_all_by_deletetime($cid,$limit,$keyword);
	}
	$multi=multi($count, $perpage, $page, $theurl,'pull-right');
	include template('list/recycle');
}
?>
