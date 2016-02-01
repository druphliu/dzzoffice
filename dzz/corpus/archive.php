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
$navtitle='文集首页 - 归档的';

$navlast="归档的文集";

$archiveview=C::t('corpus_setting')->fetch('archiveview');
$do=trim($_GET['do']);
if($do=='list'){
	$month=trim($_GET['month']);
	$list=C::t('corpus')->getArchivedCorpus($month);
	include template('corpus_archive_item');
}else{
	$archivetree=C::t('corpus_setting')->fetch('archivetree',true);
	$tree='';
	foreach($archivetree  as $key => $sum){
		if(!$key) continue;
		$arr=explode('-',$key);
		$month=$arr[0].'年'.$arr[1].'月';
		$tree.='<li id="'.$key.'" month="'.$key.'" >'.$month.'(<em>'.$sum.'</em>)</li>';
	}
	include template('corpus_archive');
}
?>
