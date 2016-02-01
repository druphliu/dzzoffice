<?php
/* @authorcode  c847417817641cfe67af4008fac750a0
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */

if(!defined('IN_DZZ') || !defined('IN_ADMIN')) {
	exit('Access Denied');
}

//卸载文集程序；

 //删除文集封面图片
 	$aids=C::t('#corpus#corpus_setting')->fetch('cover');
	$aids=explode(',',$aids);
	foreach($aids as $aid){
		C::t('attachment')->delete_by_aid($aid);
	}
 
//删文集所有附件

	foreach(DB::fetch_all("select did from %t where area='corpus'",array('document')) as $value){
		$dids[]=$value['did'];
	}
	C::t('document_reversion')->delete_by_did($dids);
	C::t('document')->delete($dids);
	
//删除文集所有评论
	foreach(DB::fetch_all("select cid from %t where  idtype='corpus'",array('comment')) as $value){
		$dels[]=$value['cid'];
	}
	C::t('comment')->delete($dels);
	
	C::t('comment_at')->delete_by_cid($dels); //删除@
	
	C::t('comment_attach')->delete_by_cid($dels);//删除附件
		
$sql = <<<EOF

DROP TABLE IF EXISTS `dzz_corpus_user`;
DROP TABLE IF EXISTS `dzz_corpus_setting`;
DROP TABLE IF EXISTS `dzz_corpus_event`;
DROP TABLE IF EXISTS `dzz_corpus_class`;
DROP TABLE IF EXISTS `dzz_corpus`;

EOF;

runquery($sql);

$finish = true;
