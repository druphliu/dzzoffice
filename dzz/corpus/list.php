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
$cid=intval($_GET['cid']);
if(!$corpus=C::t('corpus')->fetch_by_cid($cid,$_G['uid']) ){
	showmessage('文件不存在或已删除',dreferer());
}
if($corpus['deletetime']>0 && $corpus['perm']<3){
	showmessage('文件不存在或已删除',dreferer());
}
if($corpus['perm']<1 && $corpus['viewperm']>0){ //私有的文件只有成员才能查看
	showmessage('此文集为私有，你不是文集成员，无法查看',dreferer());
}
$archive=C::t('corpus_setting')->fetch('archiveview');
if($archive>1 && $corpus['archivetime']>0 && $corpus['perm']<3){
	showmessage('此文集已归档，你不是文集管理员，无法查看',dreferer());
}
if($archive==1 && $corpus['archivetime']>0 && $corpus['perm']<1){
	showmessage('此文集已归档，你不是文集成员，无法查看',dreferer());
}
$navtitle=$corpus['name'];
if($fid=intval($_GET['fid'])){
	if($class=C::t('corpus_class')->fetch($fid)){
		$navtitle=$class['fname'].' - '.$navtitle;
	}
}
$do=empty($_GET['do'])?'index':trim($_GET['do']);
if($do=='newdoc'){
	require( DZZ_ROOT.'./dzz/corpus/list/newdoc.php');
	exit();
}elseif($do=='event'){
	require(DZZ_ROOT.'./dzz/corpus/list/event.php');
	exit();
}elseif($do=='recycle'){
	require(DZZ_ROOT.'./dzz/corpus/list/recycle.php');
	exit();
}elseif($do=='ctree'){
	require(DZZ_ROOT.'./dzz/corpus/list/ctree.php');
	exit();	
}elseif($do=='edit'){
	require(DZZ_ROOT.'./dzz/corpus/list/edit.php');
	exit();
}elseif($do=='view'){
	require(DZZ_ROOT.'./dzz/corpus/list/view.php');
	exit();	
}elseif($do=='user'){
	require(DZZ_ROOT.'./dzz/corpus/list/user.php');
	exit();	
}elseif($do=='ajax'){
	require(DZZ_ROOT.'./dzz/corpus/list/ajax.php');
	exit();
}elseif($do=='restore'){
	    if($corpus['perm']<3){
			showmessage('没有权限',dreferer());
		}
		if(C::t('corpus')->restore_by_cid($cid)){
			showmessage('文集恢复成功',DZZSCRIPT.'?mod=corpus&op=list&cid='.$cid);
		}else{
			showmessage('文集恢复失败',DZZSCRIPT.'?mod=corpus&op=list&cid='.$cid);
		}
}elseif($do=='archive'){
	 if($corpus['perm']<3){
		showmessage('没有权限',dreferer());
	}
	if(C::t('corpus')->archive_by_cid($cid)){
		showmessage('文集归档成功',DZZSCRIPT.'?mod=corpus&op=list&cid='.$cid);
	}else{
		showmessage('文集归档失败',DZZSCRIPT.'?mod=corpus&op=list&cid='.$cid);
	}		
}elseif($do=='delete'){
	 if($corpus['perm']<3){
		showmessage('没有权限',dreferer());
	}
	if(C::t('corpus')->delete_by_cid($cid)){
		showmessage('文集删除成功',DZZSCRIPT.'?mod=corpus&op=list&cid='.$cid);
	}else{
		showmessage('文集删除失败',DZZSCRIPT.'?mod=corpus&op=list&cid='.$cid);
	}	
}elseif($do=='index'){
	
	$fid=intval($_GET['fid']);//加入此参数可以直接定位到此分类
	include template('corpus_list');
	
}



?>
