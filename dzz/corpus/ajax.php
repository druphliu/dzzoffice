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

if($_GET['do']=='updateview'){
	$cid=intval($_GET['cid']);
	DB::query("update %t set viewnum=viewnum+1 where cid=%d ",array('corpus',$cid));
}
?>
