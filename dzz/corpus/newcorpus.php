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

if(submitcheck('corpussubmit')){
	$cid=intval($_GET['cid']);
	$setarr=array('name'=>str_replace('...','',getstr($_GET['name'],80)),
				  'aid'=>intval($_GET['aid']),
				  'forbidcommit'=>intval($_GET['forbidcommit']),
				  'perm'=>intval($_GET['perm']),
				  'titlehide'=>intval($_GET['titlehide'])
				  );
	if($cid){
		if(C::t('corpus')->update_by_cid($cid,$setarr)){
			showmessage('do_success',$_GET['refer']);
		}
	}else{
		$setarr['dateline']=TIMESTAMP;
		$setarr['uid']=$_G['uid'];
		$setarr['username']=$_G['username'];
		if($cid=C::t('corpus')->insert_by_cid($setarr)){
			showmessage('do_success',$_GET['refer']);
		}
	}
	showmessage('未知错误',$_GET['refer']);
}else{
	$refer=dreferer();
	$cid=intval($_GET['cid']);
	$corpus=array();
	if($cid){
		$corpus=C::t('corpus')->fetch($cid);
		$navtitle='文集 - '.$corpus['name'].' - 设置';
		$navlast='设置';
	}else{
		$navtitle='文集 - 新建文集';
		$navlast=' 新建文集';
	}
	$covers=C::t('corpus_setting')->getCovers();
	$left=-1;
	if($corpus['aid']){
		foreach($covers as $key=> $value){
			if($value['aid']==$corpus['aid']) $left=$key;
		}
		if($left<0){
			$newcover=C::t('attachment')->fetch($corpus['aid']);
			$newcover['img']=C::t('attachment')->getThumbByAid($newcover,171,225);
			$covers=array_merge($newcover,$covers);
			$left=0;
		}
	}else{
		$corpus['aid']=$covers[0]['aid'];
		$left=0;
	}
	$left*=-171;
}
include template('corpus_edit');

?>
