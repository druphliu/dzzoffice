<?php
/*
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
if(!defined('IN_DZZ')) {
	exit('Access Denied');
}
include libfile('function/cache');
$dzz->reject_robot(); //阻止机器人访问
if($_G['adminid']!=1){
	exit('没有权限');
}
$typearr=array('all'=>'全部动态','aboutme'=>'与我相关','fromme'=>'我发布的','atme'=>'@我的','collect'=>'我收藏的','replyme'=>'回复我的');
$setting = C::t('setting')->fetch_all(array('feed_feedType_default','feed_at_range','feed_at_depart_title','feed_at_user_title','feed_guest_allow'));
$setting['feed_at_range']=unserialize($setting['feed_at_range']);
if(submitcheck('settingsubmit')){
	$settingnew=$_GET['settingnew'];
	$updatecache = FALSE;
	$settings = array();
	foreach($settingnew as $key => $val) {
		if($setting[$key] != $val) {
			$updatecache = TRUE;
			$settings[$key] = $val;
		}
	}
	if($settings) {
		C::t('setting')->update_batch($settings);
	}
	if($updatecache) {
		updatecache('setting');
	}
	showmessage('设置成功',dreferer(),array(),array('alert'=>'right'));
}else{
	$usergroups=DB::fetch_all("select f.*,g.grouptitle from %t f LEFT JOIN %t g ON g.groupid=f.groupid where f.groupid IN ('1','2','9') order by groupid DESC",array('usergroup_field','usergroup'));
	include template('setting');
}

?>
