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
if($_G['uid']){
	$op='my';
	require DZZ_ROOT.'./dzz/corpus/my.php';
	exit();
}else{
	$op='opened';
	require DZZ_ROOT.'./dzz/corpus/opened.php';
	exit();
}

include template('corpus_index');
?>
