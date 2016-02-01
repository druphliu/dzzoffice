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
				  'perm'=>intval($_GET['perm']),
				  'forbidcommit'=>intval($_GET['forbidcommit']),
				  'titlehide'=>intval($_GET['titlehide'])
				  );
	C::t('corpus')->update($cid,$setarr);
    showmessage('do_success',$_GET['refer']);
	
}else{
	$refer=dreferer();
	$navtitle='文集 - '.$corpus['name'].' - 设置';
	$navlast='设置';
	$covers=C::t('corpus_setting')->getCovers();
	$left=-1;
	if($corpus['aid']){
		foreach($covers as $key=> $value){
			if($value['aid']==$corpus['aid']) $left=$key;
		}
		if($left<0){
			$newcover=C::t('attachment')->fetch($corpus['aid']);
			$newcover['img']=C::t('attachment')->getThumbByAid($newcover,171,225);
			$covers=array_merge(array($newcover['aid']=>$newcover),$covers);
			$left=0;
		}
	}else{
		$corpus['aid']=$covers[0]['aid'];
		$left=0;
	}
	$left*=-171;
}
include template('list/edit');

?>
