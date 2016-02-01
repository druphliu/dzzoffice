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

$navtitle='';
$fid=intval($_GET['fid']);
if(!$class=C::t('corpus_class')->fetch($fid)){
	showmessage('文档不存在或已经删除',dreferer());
}
$did=$class['did'];
$version=intval($_GET['ver']);
if($document=C::t('document')->fetch_by_did($did)){
	
	$document['dateline']=dgmdate($document['dateline'],'u');
	//获取此文件的所有版本
	$versions=C::t('document_reversion')->fetch_all_by_did($did);
	if($version>0){//版本比较模式，显示当前版本与前一版本的差异
		$current=$versions[$version];
		if(isset($versions[$version])){
			$dzzpath=getDzzPath($versions[$version]);
			$str_new=IO::getFileContent($dzzpath);//str_replace(array("\r\n", "\r", "\n"), "",io::getFileContent($dzzpath));
		}else{
			$dzzpath=getDzzPath($document);
			$str_new=IO::getFileContent($dzzpath);//str_replace(array("\r\n", "\r", "\n"), "",io::getFileContent($dzzpath));
		}
		if($versions[$version-1]){
			$dzzpath_old=getDzzPath($versions[$version-1]);
			$str_old=IO::getFileContent($dzzpath_old);//str_replace(array("\r\n", "\r", "\n"), "",io::getFileContent($dzzpath_old));
			
		}else{
			$str_old=$str_new;
		}
		include_once dzz_libfile('class/html_diff','document');
		$diff=new html_diff();
		$str=$diff->compare($str_old,$str_new);
	}else{
		$current=$document;
		$dzzpath=getDzzPath($document);
		$str=IO::getFileContent($dzzpath);//str_replace(array("\r\n", "\r", "\n"), "",IO::getFileContent($dzzpath));
		$navtitle=$document['subject'];
	}
}else{
	$document=$class;
	$document['subject']=$class['fname'];
	$document['dateline']=dgmdate($document['dateline'],'u');
}
include template('list/view');
?>
