<?php
/*
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
if(!defined('IN_DZZ') || !defined('IN_ADMIN')) {
	exit('Access Denied');
}
if($_G['adminid']!=1) showmessage('没有权限，只有系统管理员才能导入用户',dreferer());
require_once libfile('function/organization');
$do=trim($_GET['do']);
if($do=='importing'){
	//判断邮箱是否存在
	require_once libfile('function/user','','user');
	$email=trim($_GET['email']);
	$_GET['nickname']=addslashes(trim(stripslashes(trim($_GET['nickname']))));
	$_GET['username']=addslashes(trim(stripslashes(trim($_GET['username']))));
	
	$_GET['nickname']=str_replace('...','',getstr($_GET['nickname'],30));
	$_GET['username']=str_replace('...','',getstr($_GET['username'],30));
	$_GET['password']=empty($_GET['password'])?trim($_GET['pswdefault']):trim($_GET['password']);
	
	$_GET['weixinid']=addslashes(trim(stripslashes(trim($_GET['weixinid']))));
	$_GET['mobile']=addslashes(trim(stripslashes(trim($_GET['mobile']))));
	
	if(empty($email) || empty($_GET['username'])) exit(json_encode(array('error'=>'姓名和邮箱不能为空')));
	if(!isemail($email)) exit(json_encode(array('error'=>'email格式错误')));
	
	$isappend=intval($_GET['append']);
	$sendmail=intval($_GET['sendmail']);
	/*
	if($sendmail){ //随机密码时重新设置密码为随机数；
		$_GET['password']=random(8);
	}*/
	$exist=0;
	
	//检查用户是否已经存在
	if(($user=C::t('user')->fetch_by_email($email)) || ($_GET['nickname'] && ($user=C::t('user')->fetch_by_nickname($_GET['nickname'])))){//用户已经存在时
		$uid=$user['uid'];
		$exist=1;
		if($isappend){//增量添加，如果原先没有nickname,增加
			$appendfield=array();
			if($_GET['nickname'] && empty($user['nickname'])){
				if(check_username($_GET['nickname']) && !C::t('user')->fetch_by_nickname($_GET['nickname'])){
					$appendfield['nickname']=$_GET['nickname'];
				}
			}
			if($_GET['mobile'] && empty($user['phone'])){
				if(!preg_match("/^\d+$/",$_GET['mobile'])){
					exit(json_encode(array('error'=>'手机号码不合法')));
				}
				if(C::t('user')->fetch_by_phone($_GET['mobile']) ) {
					exit(json_encode(array('error'=>'手机号码已经存在')));
				}
				$appendfield['phone']=$_GET['mobile'];
				
			}
			if($_GET['weixinid'] && empty($user['weixinid'])){
				if(!preg_match("/^[A-Za-z][\w|-]+$/i",$_GET['weixinid'])){
					exit(json_encode(array('error'=>'微信号不合法')));
				}
				if(C::t('user')->fetch_by_weixinid($_GET['weixinid']) ) {
					exit(json_encode(array('error'=>'微信号已经存在')));
				}
				$appendfield['weixinid']=$_GET['weixinid'];
			}
			if($appendfield) C::t('user')->update($uid,$appendfield);
		}else{ //覆盖导入时，覆盖用户的姓名和密码
			$salt=substr(uniqid(rand()), -6);
			if(!check_username($_GET['username'])) exit(json_encode(array('error'=>'用户姓名含有敏感字符')));
			$setarr=array('username'=>$_GET['username'],
						  'password'=>md5(md5($_GET['password']).$salt),
						  'salt'=>$salt
						  );
			if($_GET['nickname']){
				if($_GET['nickname']!=$user['nickname'] && check_username($_GET['nickname']) && !C::t('user')->fetch_by_nickname($_GET['nickname'])){
					$setarr['nickname']=$_GET['nickname'];
				}
			}
			if($_GET['mobile'] && $_GET['mobile']!=$user['phone']){
				if(!preg_match("/^\d+$/",$_GET['mobile'])){
					exit(json_encode(array('error'=>'手机号码不合法')));
				}
				if(C::t('user')->fetch_by_phone($_GET['mobile']) ) {
					exit(json_encode(array('error'=>'手机号码已经存在')));
				}
				$setarr['phone']=$_GET['mobile'];
				
			}
			if($_GET['weixinid'] && $_GET['weixinid']!=$user['weixinid']){
				if(!preg_match("/^[A-Za-z][\w|-]+$/i",$_GET['weixinid'])){
					exit(json_encode(array('error'=>'微信号不合法')));
				}
				if(C::t('user')->fetch_by_weixinid($_GET['weixinid']) ) {
					exit(json_encode(array('error'=>'微信号已经存在')));
				}
				$setarr['weixinid']=$_GET['weixinid'];
			}
			C::t('user')->update($uid,$setarr);
			if($sendmail){ //发送密码到用户邮箱，延时发送
				$email_password_message = lang('email', 'email_password_message', array(
						'sitename' => $_G['setting']['sitename'],
						'siteurl' => $_G['siteurl'],
						'email'=>$email,
						'password'=>$_GET['password']
					));
					
					if(!sendmail_cron("$email <$email>", lang('email', 'email_password_subject'), $email_password_message)) {
						runlog('sendmail', "$email sendmail failed.");
					}
			}
		}
	}else{ //新添用户
		if(!check_username($_GET['username'])) exit(json_encode(array('error'=>'用户姓名含有敏感字符')));
		
		if($_GET['nickname']){
			if(check_username($_GET['nickname']) && !C::t('user')->fetch_by_nickname($_GET['nickname'])){
			}else{
				$_GET['nickname']='';
			}
		}
		$user=uc_add_user($_GET['username'], $_GET['password'], $email, $_GET['nickname']);
		
		$uid=$user['uid'];
		if($uid<1)  exit(json_encode(array('error'=>'导入不成功')));
		$base = array(
				'uid' => $uid,
				'adminid' => 0,
				'groupid' =>9,
				'regdate' => TIMESTAMP,
				'emailstatus' => 1,
			);
			if($_GET['mobile']){
				if(!preg_match("/^\d+$/",$_GET['mobile'])){
				}elseif(C::t('user')->fetch_by_phone($_GET['mobile']) ) {
				}else{
					$base['phone']=$_GET['mobile'];
				}
			}
			if($_GET['weixinid']){
				if(!preg_match("/^[A-Za-z][\w|-]+$/i",$_GET['weixinid'])){
				}elseif(C::t('user')->fetch_by_weixinid($_GET['weixinid'])) {
				}else{
					$base['weixinid']=$_GET['weixinid'];
				}
			}
		C::t('user')->update($uid,$base);
		if($sendmail){ //发送密码到用户邮箱，延时发送
			$email_password_message = lang('email', 'email_password_message', array(
					'sitename' => $_G['setting']['sitename'],
					'siteurl' => $_G['siteurl'],
					'email'=>$email,
					'password'=>$_GET['password']
				));
				
				if(!sendmail_cron("$email <$email>", lang('email', 'email_password_subject'), $email_password_message)) {
					runlog('sendmail', "$email sendmail failed.");
				}
		}
	}
	//处理用户资料
	$_GET['gender']=trim($_GET['gender']);
	$_GET['birth']=trim($_GET['birth']);
	$_GET['telephone']=trim($_GET['telephone']);
	//$_GET['mobile']=trim($_GET['mobile']);
	
	if($exist && $isappend){ //增量时
		$oldprofile=C::t('user_profile1')->fetch($uid);
		$profile=array();
		if(!empty($_GET['birth']) && empty($oldprofile['birthyear'])){
			 $birth=strtotime($_GET['birth']);
			 if($birth<TIMESTAMP && $birth>0){
				 $arr=getdate($birth);
				 $profile['birthyear']=$arr['year'];
				 $profile['birthmonth']=$arr['mon'];
				 $profile['birthday']=$arr['mday'];
			 }
		}
		if(!empty($_GET['gender']) && empty($oldprofile['gender'])){
			if($_GET['gender']=='男') $profile['gender']=1;
			elseif($_GET['gender']=='女') $profile['gender']=2;
			else $profile['gender']=0;
		}
		
		if(!empty($_GET['telephone']) && empty($oldprofile['telephone'])){
			 $profile['telephone']=$_GET['telephone'];
		}
		foreach($_GET as $key=>$value){
			if(!empty($_GET[$key]) && empty($oldprofile[$key])){
				 if(checkprofile($key,$value))  $profile[$key]=$value;
			}
		}
		
		if($profile){
			$profile['uid']=$uid;
			C::t('user_profile1')->insert($profile);
		}
	}else{
		$profile=array();
		if(!empty($_GET['birth'])){
			 $birth=strtotime(trim($_GET['birth']));
			 if($birth<TIMESTAMP && $birth>0){
				 $arr=getdate($birth);
				 $profile['birthyear']=$arr['year'];
				 $profile['birthmonth']=$arr['mon'];
				 $profile['birthday']=$arr['mday'];
			 }
		}
		if(!empty($_GET['gender'])){
			if($_GET['gender']=='男') $profile['gender']=1;
			elseif($_GET['gender']=='女') $profile['gender']=2;
			else $profile['gender']=0;
		}
		
		if(!empty($_GET['telephone'])){
			 $profile['telephone']=$_GET['telephone'];
		}
		
		foreach($_GET as $key=>$value){
			if(checkprofile($key,$value))  $profile[$key]=$value;
		}
		
		$profile['uid']=$uid;
		
		C::t('user_profile1')->insert($profile);
		 
		 //插入用户状态表
		$status = array(
			'uid' => $uid,
			'regip' => '',
			'lastip' => '',
			'lastvisit' => TIMESTAMP,
			'lastactivity' => TIMESTAMP,
			'lastsendmail' => 0
		);
		C::t('user_status')->insert($status, false, true);
	}
	//处理部门和职位
	$orgid=intval($_GET['orgid']);
	
	$_GET['orgname']=!empty($_GET['orgname'])?explode('/',$_GET['orgname']):array();
	$_GET['job']=!empty($_GET['job'])?explode('/',$_GET['job']):array();
	
	//创建机构和部门
	foreach($_GET['orgname'] as $key => $orgname){
		if(empty($orgname)) continue;
		if($porgid=DB::result_first("select orgid from %t where forgid=%d and orgname=%s",array('organization',$orgid,$orgname))){
			$orgid=$porgid;
		}else{
			$setarr=array('forgid'=>$orgid,
						  'orgname'=>$orgname,
						  'fid'=>0,
						  'disp'=>100,
						  'indesk'=>0,
						  'dateline'=>TIMESTAMP,
						);
			if($porgid=C::t('organization')->insert_by_orgid($setarr)){
				$orgid=$porgid;
			}
		}
	}
	
	//用户加入机构
	if($isappend){//增量导入时
		C::t('organization_user')->insert($uid,$orgid);
	}else{
		C::t('organization_user')->delete_by_uid($uid,0);
		C::t('organization_user')->insert($uid,$orgid);
	}
	if($orgid){
		foreach($_GET['job'] as $key =>$jobname){ //处理职位
			$jobid=0;
			if($pjobid=DB::result_first("select jobid from %t where orgid=%d and name=%s",array('organization_job',$orgid,$jobname))){
				$jobid=$pjobid;
			}else{
				$setarr=array('orgid'=>$orgid,
							  'name'=>$_GET['job'][$key],
							  'dateline'=>TIMESTAMP,
							  'opuid'=>$_G['uid']
							  );
				if($pjobid=C::t('organization_job')->insert($setarr,1)){
					$jobid=$pjobid;
				}
			}
			if($jobid){
				if($isappend){//增量导入时
					if(!DB::result_first("select COUNT(*) from %t where uid=%d and orgid=%d and jobid>0 ",array('organization_user',$uid,$orgid))){
						DB::update('organization_user',array('jobid'=>$jobid),"uid='{$uid}' and orgid='{$orgid}'");
					}
				}else{//覆盖导入时
					DB::update('organization_user',array('jobid'=>$jobid),"uid='{$uid}' and orgid='{$orgid}'");
				}
			}
		}
	}
	exit(json_encode(array('msg'=>'success')));
}elseif($do=='list'){
	require_once DZZ_ROOT.'./core/class/class_PHPExcel.php';
	$inputFileName = $_G['setting']['attachdir'].$_GET['file'];
	if(!is_file($inputFileName)){
		showmessage('人员信息表上传未成功，请重新上传',ADMINSCRIPT.'?mod=orguser&op=import');
	}
	$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
	$objReader = PHPExcel_IOFactory::createReader($inputFileType);
	$objPHPExcel = $objReader->load($inputFileName);
	$sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
	//获取导入数据的字段
	$h0=array('username'=>'姓名','email'=>'邮箱','nickname'=>'用户名','birth'=>'出生日期','gender'=>'性别','mobile'=>'手机','weixinid'=>'微信号','orgname'=>'所属部门','job'=>'部门职位','password'=>'登录密码');
	$h1=getProfileForImport();
	$h0=array_merge($h0,$h1);
	//获取可导入的用户资料
	$h=array();
	foreach($sheetData[1] as $key =>$value){
		$value=trim($value);
		foreach($h0 as $fieldid=>$title){
			if($title==$value){
				$h[$key]=$fieldid;
				break;
			}
		}
	}
	
	if(!in_array('username',$h)){
		showmessage('缺少必填字段"姓名"');
	}elseif(!in_array('email',$h) && !in_array('username',$h)){
		 showmessage('缺少必填字段”用户名“或”邮箱“');
	} 
	if(!in_array('email',$h)){
		$h=array_merge(array('_'=>'email'),$h);
	}
	$list=array();
	foreach($sheetData as $key=> $value){
		if($key<=1) continue;
		$temp=array();
		foreach($value as $col =>$val){
			if(trim($val)=='') continue;
			if($h[$col]=='orgname'){
				$temp[$h[$col]][]=$val;
			}elseif($h[$col]=='job'){
				$temp[$h[$col]][]=$val;
			}elseif($key1=='birth'){
				$arr=explode('-',$value[$value1]);
				if(count($arr)==3){
					$temp[$key1]=dgmdate(strtotime($arr[2].'-'.$arr[0].'-'.$arr[1]),'Y-m-d');
				}else{
					$temp[$key1]=$val;
				}
			}else{
				if($h[$col]) $temp[$h[$col]]=$val;
			}
		}
		if(empty($temp['email'])) $temp['email']=random(10,true).'@163.com';
		if(isset($list[$temp['email']])){
			foreach($h as $key1 => $value1){
				if(!empty($temp[$key1])){
					$list[$temp['email']][$key1]=$temp[$key1];
				}
			}
		}else{
			if($temp) $list[$temp['email']]=$temp;
		}
	}
	$h=array_unique($h);
	$orgpath=C::t('organization')->getPathByOrgid($orgid);
	if(empty($orgpath)) $orgpath='选择导入的机构或部门';

	//默认选中
	$open=array();
	$patharr=getPathByOrgid($orgid);
	$arr=array_reverse(array_keys($patharr));
	array_pop($arr);
	$count=count($arr);
	if($open[$arr[$count-1]]){
		if(count($open[$arr[$count-1]])>$count) $open[$arr[count($arr)-1]]=$arr;
	}else{
		$open[$arr[$count-1]]=$arr;
	}
	$openarr=json_encode(array('orgid'=>$open));
	include template('import_list');	
}else{
	if(submitcheck('importfilesubmit')){
		if($_FILES['importfile']['tmp_name']){
			$allowext=array('xls','xlsx');
			$ext=strtolower(substr(strrchr($_FILES['importfile']['name'], '.'), 1, 10));
			if(!in_array($ext,$allowext)) showmessage('只允许导入xls,xlsx类型的文件',dreferer());
			if($file=uploadtolocal($_FILES['importfile'],'cache')){
				$url=ADMINSCRIPT.'?mod=orguser&op=import&do=list&file='.urlencode($file);
				@header("Location: $url");
				exit();
				showmessage('人员信息表上传成功，正在调转到导入页面',ADMINSCRIPT.'?mod=orguser&op=import&do=list&file='.urlencode($file));
			}else{
				showmessage('上传信息表未成功，请稍候重试',dreferer());
			}
		}else{
			showmessage('请选择人员信息表',dreferer());
		}
	}else{
		
		include template('import_guide');
	}
}
function checkprofile($fieldid,&$value){
	global $_G;
	if(empty($_G['cache']['profilesetting'])) {
		loadcache('profilesetting');
	}
	$field = $_G['cache']['profilesetting'][$fieldid];
	if(empty($field) || in_array($fieldid, array('department','realname','gender','birthyear','birthmonth','birthday','birth','constellation','zodiac','email','nickname','password','orgname','job','username'))) {
		return false;
	}
	
	if($field['choices']) {
		$field['choices'] = explode("\n", $field['choices']);
	}
	if($field['formtype'] == 'text' || $field['formtype'] == 'textarea') {
		$value = getstr($value);
		if($field['size'] && strlen($value) > $field['size']) {
			return false;
		} else {
			$field['validate'] = !empty($field['validate']) ? $field['validate'] : ($_G['profilevalidate'][$fieldid] ? $_G['profilevalidate'][$fieldid] : '');
			if($field['validate'] && !preg_match($field['validate'], $value)) {
				return false;
			}
		}
	} elseif($field['formtype'] == 'checkbox' || $field['formtype'] == 'list') {
		$arr = array();
		$value=explode('\n',$value);
		foreach ($value as $op) {
			if(in_array(trim($op), trim($field['choices']))) {
				$arr[] = trim($op);
			}
		}
		$value = implode("\n", $arr);
		if($field['size'] && count($arr) > $field['size']) {
			return false;
		}
	} elseif($field['formtype'] == 'radio' || $field['formtype'] == 'select') {
		if(!in_array($value, $field['choices'])){
			return false;
		}
	}
	return true;
	
}
function getProfileForImport(){
	global $_G;
	if(empty($_G['cache']['profilesetting'])) {
		loadcache('profilesetting');
	}
	$profilesetting=$_G['cache']['profilesetting'];
	$ret=array();
	foreach($profilesetting as $key=> $value){
		if(in_array($key,array('department','realname','gender','birthyear','birthmonth','birthday','constellation','zodiac'))) continue;
		elseif($value['formtype']=='file') continue;
		elseif($value['formtype']=='select' || $value['formtype']=='radio'){
			$ret[$key]=$value['title']/*.($value['choices']?'('.preg_replace("/[\r\n]/i",'|',$value['choices']).')':'')*/;
		}elseif( $value['formtype']=='checkbox'){
			$ret[$key]=$value['title']/*.($value['choices']?'('.preg_replace("/[\r\n]/i",'-',$value['choices']).')':'')*/;
		}else{	
			$ret[$key]=$value['title'];
		}
	}
	return $ret;
}

?>
