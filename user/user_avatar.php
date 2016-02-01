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
if(!$_G['uid']){
	include template('common/header_reload');
	echo "<script type=\"text/javascript\">";
	echo "try{top._login.logging();win.Close();}catch(e){location.href='user.php?mod=logging';}";
	echo "</script>";	
	include template('common/footer_reload');
	exit();
}

if(submitcheck('avatarsubmit')) {
	showmessage('do_success', 'user.php?mod=avatar');
}

//loaducenter();
$uc_avatarflash = uc_avatar($_G['uid'], 'virtual', 0);
$user=getuserbyuid($_G['uid']);
if(empty($user['avatarstatus']) && uc_check_avatar($_G['uid'], 'middle')) {
	C::t('user')->update($_G['uid'], array('avatarstatus'=>'1'));
}
$reload = intval($_GET['reload']);

include template("avatar");
function uc_check_avatar($uid, $size = 'middle', $type = 'virtual') {
	global $_G;
	$url = $_G['siteurl']."avatar.php?uid=$uid&size=$size&type=$type&check_file_exists=1";
	$res =dfsockopen($url, 500000, '', '', TRUE, '', 20);
	if($res == 1) {
		return 1;
	} else {
		return 0;
	}
}
function uc_avatar($uid, $type = 'virtual', $returnhtml = 1) {
	global $_G;
	$uid = intval($uid);
	$uc_input = authcode("uid=$uid",'ENCODE');
	$uc_avatarflash = $_G['siteurl'].'data/avatar/camera.swf?inajax=1&appid=1&input='.$_G[uid].'&agent='.md5($_SERVER['HTTP_USER_AGENT']).'&ucapi='.urlencode(str_replace('http://', '', $_G['siteurl'].'user/avatar')).'&avatartype='.$type.'&uploadSize=2048';
	if($returnhtml) {
		return '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,0,0" width="450" height="253" id="mycamera" align="middle">
			<param name="allowScriptAccess" value="always" />
			<param name="scale" value="exactfit" />
			<param name="wmode" value="transparent" />
			<param name="quality" value="high" />
			<param name="bgcolor" value="#ffffff" />
			<param name="movie" value="'.$uc_avatarflash.'" />
			<param name="menu" value="false" />
			<embed src="'.$uc_avatarflash.'" quality="high" bgcolor="#ffffff" width="450" height="253" name="mycamera" align="middle" allowScriptAccess="always" allowFullScreen="false" scale="exactfit"  wmode="transparent" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
		</object>';
	} else {
		return array(
			'width', '450',
			'height', '253',
			'scale', 'exactfit',
			'src', $uc_avatarflash,
			'id', 'mycamera',
			'name', 'mycamera',
			'quality','high',
			'bgcolor','#ffffff',
			'menu', 'false',
			'swLiveConnect', 'true',
			'allowScriptAccess', 'always'
		);
	}
}
?>
