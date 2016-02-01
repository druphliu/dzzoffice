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

function uc_user_login($username, $password, $isuid, $checkques, $questionid, $answer, $ip){
		if($isuid == 1) {
			$user = C::t('user')->fetch_by_uid($username);
		} elseif($isuid == 2) {
			$user = C::t('user')->fetch_by_email($username);
		} else {
			$user = C::t('user')->fetch_by_nickname($username);
		}
		$passwordmd5 = preg_match('/^\w{32}$/', $password) ? $password : md5($password);
		if(empty($user)) {
			$status = -1;
		} elseif($user['password'] != md5($passwordmd5.$user['salt'])) {
			$status = -2;
		} elseif($checkques && $user['secques'] != '' && $user['secques'] !=quescrypt($questionid, $answer)) {
			$status = -3;
		} else {
			$status = $user['uid'];
		}
		$merge = 0;
	

		return array($status, $user['username'], $password, $user['email'], $merge);
	
}

function userlogin($username, $password, $questionid, $answer, $loginfield = 'auto', $ip = '') {
	$return = array();

	if($loginfield == 'uid' && getglobal('setting/uidlogin')) {
		$isuid = 1;
	} elseif($loginfield == 'email') {
		$isuid = 2;
	} elseif($loginfield == 'auto') {
		$isuid = 3;
	} else {
		$isuid = 0;
	}
	if($isuid == 3) {
		if(!strcmp(dintval($username), $username) && getglobal('setting/uidlogin')) {
			$return['ucresult'] = uc_user_login($username, $password, 1, 1, $questionid, $answer, $ip);
		} elseif(isemail($username)) {
			$return['ucresult'] = uc_user_login($username, $password, 2, 1, $questionid, $answer, $ip);
		}
		if($return['ucresult'][0] <= 0 && $return['ucresult'][0] != -3) {
			$return['ucresult'] = uc_user_login(addslashes($username), $password, 0, 1, $questionid, $answer, $ip);
		}
	} else {
		$return['ucresult'] = uc_user_login(addslashes($username), $password, $isuid, 1, $questionid, $answer, $ip);
	}
	$tmp = array();
	$duplicate = '';
	list($tmp['uid'], $tmp['username'], $tmp['password'], $tmp['email'], $duplicate) = $return['ucresult'];
	$return['ucresult'] = $tmp;
	if($duplicate && $return['ucresult']['uid'] > 0 || $return['ucresult']['uid'] <= 0) {
		$return['status'] = 0;
		return $return;
	}

	$member = getuserbyuid($return['ucresult']['uid'], 1);
	if(!$member || empty($member['uid'])) {
		$return['status'] = -1;
		return $return;
	}
	if($member['status']>0){
		$return['status'] = -2;
		return $return;
	}
	$return['member'] = $member;
	$return['status'] = 1;
	if($member['_inarchive']) {
		C::t('user_archive')->move_to_master($member['uid']);
	}
	/*if($member['email'] != $return['ucresult']['email']) {
		C::t('user')->update($return['ucresult']['uid'], array('email' => $return['ucresult']['email']));
	}*/

	return $return;
}

function setloginstatus($member, $cookietime) {
	global $_G;
	$_G['uid'] = intval($member['uid']);
	$_G['username'] = $member['username'];
	$_G['nickname'] = $member['nickname'];
	$_G['email'] = $member['email'];
	$_G['adminid'] = $member['adminid'];
	$_G['groupid'] = $member['groupid'];
	$_G['formhash'] = formhash();
	//$_G['session']['invisible'] = getuserprofile('invisible');
	$_G['member'] = $member;
	
	loadcache('usergroup_'.$_G['groupid']);
	C::app()->session->isnew = true;
	C::app()->session->updatesession();
	dsetcookie('auth', authcode("{$member['password']}\t{$member['uid']}", 'ENCODE'), $cookietime, 1, true);
	dsetcookie('loginuser');
	dsetcookie('activationauth');
	dsetcookie('pmnum');
}

function logincheck($username) {
	global $_G;
	
	$return = 0;
	$username = trim($username);
	$login = C::t('failedlogin')->fetch_ip($_G['clientip']);
	$return = (!$_G['config']['userlogin']['checkip'] || !$login || (TIMESTAMP - $login['lastupdate'] > 900)) ? 5 : max(0, 5 - $login['count']);

	if(!$login) {
		C::t('failedlogin')->insert(array(
			'ip' => $_G['clientip'],
			'count' => 0,
			'lastupdate' => TIMESTAMP
		), false, true);
	} elseif(TIMESTAMP - $login['lastupdate'] > 900) {
		C::t('failedlogin')->insert(array(
			'ip' => $_G['clientip'],
			'count' => 0,
			'lastupdate' => TIMESTAMP
		), false, true);
		C::t('failedlogin')->delete_old(901);
	}
	return $return;
}

function loginfailed($username) {
	global $_G;

	if(function_exists('uc_user_logincheck')) {
		return;
	}
	C::t('failedlogin')->update_failed($_G['clientip']);
}

function getinvite() {
	global $_G;

	if($_G['setting']['regstatus'] == 1) return array();
	$result = array();
	$cookies = empty($_G['cookie']['invite_auth']) ? array() : explode(',', $_G['cookie']['invite_auth']);
	$cookiecount = count($cookies);
	$_GET['invitecode'] = trim($_GET['invitecode']);
	if($cookiecount == 2 || $_GET['invitecode']) {
		$id = intval($cookies[0]);
		$code = trim($cookies[1]);
		if($_GET['invitecode']) {
			$invite = C::t('user_invite')->fetch_by_code($_GET['invitecode']);
			$code = trim($_GET['invitecode']);
		} else {
			$invite = C::t('user_invite')->fetch($id);
		}
		if(!empty($invite)) {
			if($invite['code'] == $code && empty($invite['fuid']) &&  (empty($invite['endtime']) || $_G['timestamp'] < $invite['endtime'])) {
				$result['uid'] = $invite['uid'];
				$result['id'] = $invite['id'];
				$result['join_orgid']=$invite['join_orgid'];
			}
		}
	}
	if($result['uid']) {
		$member = getuserbyuid($result['uid']);
		$result['username'] = $member['username'];
	} else {
		dsetcookie('invite_auth', '');
	}

	return $result;
}

function replacesitevar($string, $replaces = array()) {
	global $_G;
	$sitevars = array(
		'{sitename}' => $_G['setting']['sitename'],
		
		'{time}' => dgmdate(TIMESTAMP, 'Y-n-j H:i'),
		'{adminemail}' => $_G['setting']['adminemail'],
		'{username}' => $_G['member']['username'],
		'{myname}' => $_G['member']['username']
	);
	$replaces = array_merge($sitevars, $replaces);
	return str_replace(array_keys($replaces), array_values($replaces), $string);
}

function clearcookies() {
	global $_G;
	foreach($_G['cookie'] as $k => $v) {
		if($k != 'widthauto') {
			dsetcookie($k);
		}
	}
	$_G['uid'] = $_G['adminid'] = 0;
	$_G['username'] = $_G['member']['password'] = '';
}

	
function checkfollowfeed() {
	global $_G;

	if($_G['uid']) {
		$lastcheckfeed = 0;
		if(!empty($_G['cookie']['lastcheckfeed'])) {
			$time = explode('|', $_G['cookie']['lastcheckfeed']);
			if($time[0] == $_G['uid']) {
				$lastcheckfeed = $time[1];
			}
		}
		if(!$lastcheckfeed) {
			$lastcheckfeed = getuserprofile('lastactivity');
		}
		dsetcookie('lastcheckfeed', $_G['uid'].'|'.TIMESTAMP, 31536000);
		$followuser = C::t('home_follow')->fetch_all_following_by_uid($_G['uid']);
		$uids = array_keys($followuser);
		if(!empty($uids)) {
			$count = C::t('home_follow_feed')->count_by_uid_dateline($uids, $lastcheckfeed);
			if($count) {
				notification_add($_G['uid'], 'follow', 'member_follow', array('count' => $count, 'from_id'=>$_G['uid'], 'from_idtype' => 'follow'), 1);
			}
		}
	}
	dsetcookie('checkfollow', 1, 30);
}
function checkemail($email) {
	global $_G;

	$email = strtolower(trim($email));
	if(strlen($email) > 32) {
		showmessage('profile_email_illegal', '', array(), array('handle' => false));
	}
	if($_G['setting']['regmaildomain']) {
		$maildomainexp = '/('.str_replace("\r\n", '|', preg_quote(trim($_G['setting']['maildomainlist']), '/')).')$/i';
		if($_G['setting']['regmaildomain'] == 1 && !preg_match($maildomainexp, $email)) {
			showmessage('profile_email_domain_illegal', '', array(), array('handle' => false));
		} elseif($_G['setting']['regmaildomain'] == 2 && preg_match($maildomainexp, $email)) {
			showmessage('profile_email_domain_illegal', '', array(), array('handle' => false));
		}
	}

	$ucresult = uc_user_checkemail($email);

	if($ucresult == -4) {
		showmessage('profile_email_illegal', '', array(), array('handle' => false));
	} elseif($ucresult == -5) {
		showmessage('profile_email_domain_illegal', '', array(), array('handle' => false));
	} elseif($ucresult == -6) {
		showmessage('profile_email_duplicate', '', array(), array('handle' => false));
	}
}
function uc_user_checkemail($email) {
	global $_G;
	if(!isemail($email)) {
		return -4;
	} elseif(!check_emailaccess($email)) {
		return -5;
	} elseif(check_emailexists($email)) {
		return -6;
	} else {
		return 1;
	}
}
function is($email) {
	return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
}
function check_emailaccess($email) {
	$accessemail = $_G['setting']['accessemail'];
	$censoremail = $_G['setting']['censoremail'];
	$accessexp = '/('.str_replace("\r\n", '|', preg_quote(trim($accessemail), '/')).')$/i';
	$censorexp = '/('.str_replace("\r\n", '|', preg_quote(trim($censoremail), '/')).')$/i';
	if($accessemail || $censoremail) {
		if(($accessemail && !preg_match($accessexp, $email)) || ($censoremail && preg_match($censorexp, $email))) {
			return FALSE;
		} else {
			return TRUE;
		}
	} else {
		return TRUE;
	}
}
function check_emailexists($email) {
	$sqladd = $username !== '' ? "AND username<>'$username'" : '';
	$email =C::t('user')->fetch_by_email($email);
	return $email;
}
function uc_user_checkname($username) {
	$username = addslashes(trim(stripslashes($username)));
	if(!check_username($username)) {
		return -1;
	} elseif(!check_usernamecensor($username)) {
		return -2;
	} elseif(check_usernameexists($username)) {
		return -3;
	} 
	return 1;
}
function uc_user_checkusername($username) {
	$username = addslashes(trim(stripslashes($username)));
	if(!check_username($username)) {
		return -7;
	}	
	return 1;
}
function check_username($username) {
	$guestexp = '^Guest';
	$len = dstrlen($username);
	if($len < 3 || preg_match("/\s+|^c:\\con\\con|[%,\*\"\s\<\>\&]|$guestexp/is", $username)) {
		return FALSE;
	} else {
		return TRUE;
	}
}
function check_usernamecensor($username) {
	global $_G;
	return true;
}

function check_usernameexists($username) {
	return C::t('user')->fetch_by_nickname($username);
}

function uc_user_register($username, $password, $email,$nickname ,$questionid = '', $answer = '', $regip = '',$addorg=1) {
	

	if($nickname && ($status = uc_user_checkname($nickname)) < 0) {
		return $status;
	}
	if(($status = uc_user_checkusername($username))< 0){
		return $status;
	}
	if(($status = uc_user_checkemail($email)) < 0) {
		return $status;
	}

	$uid =uc_add_user($username, $password, $email, $nickname, 0, $questionid, $answer, $regip);
	//加入默认机构
	if($addorg && is_array($uid) && getglobal('setting/defaultdepartment') && DB::fetch_first("select orgid from %t where orgid=%d ",array('organization',getglobal('setting/defaultdepartment')))){
		C::t('organization_user')->insert($uid['uid'],getglobal('setting/defaultdepartment'));
	}
	return $uid;
}
function uc_add_user($username, $password, $email, $nickname,$uid = 0, $questionid = '', $answer = '', $regip = '') {
	global $_G;
	$salt=substr(uniqid(rand()), -6);
	$setarr=array(
					'salt'=>$salt,
					'password'=>md5(md5($password).$salt),
					'username'=>$username,
					'nickname'=>$nickname,
					'secques'=>quescrypt($questionid, $answer),
					'email'=>$email,
					'regdate'=>TIMESTAMP,
					);
	
	$setarr['uid'] =DB::insert('user',$setarr,1);
	return $setarr;
}

?>
