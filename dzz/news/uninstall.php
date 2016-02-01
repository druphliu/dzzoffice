<?php
/* @authorcode  f8b5ecdc1c3eb0725f1d38f5f0216d53
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */

if(!defined('IN_DZZ') || !defined('IN_ADMIN')) {
	exit('Access Denied');
}

//提示用户删除的严重程度
if($_GET['confirm']=='DELETE'){
	include DZZ_ROOT.'./dzz/news/uninstall_real.php';
}else{
	$url='index.php?mod=news&appid='.$appid.'&op=uninstall_confirm&adminurl='.urlencode($request_uri);
	header("Location: $url");
	exit();
}
