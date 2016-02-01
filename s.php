<?php
/*
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
 
define('APPTYPEID', 1);
define('CURSCRIPT', 'dzz');
define('DZZSCRIPT', 'index.php');
require './core/class/class_core.php';
require './dzz/function/dzz_core.php';
$dzz = C::app();
$dzz->cachelist =array();
$dzz->init();
$sid=$_GET['sid'];
$share=C::t('share')->fetch($sid);
if($share['status']==-4) showmessage('此分享链接已被管理员屏蔽');
//判断是否过期
if($share['endtime'] && $share['endtime']<TIMESTAMP){
	C::t('share')->update($sid,array('status'=>-1));
	 showmessage('此分享链接已经过期');
}
if($share['times'] && $share['times']<$share['count']){
	C::t('share')->update($sid,array('status'=>-2));
	 showmessage('此分享连接已经到达最大使用次数');
}
if($share['password']){
	if(submitcheck('passwordsubmit')){
		if($_GET['password']!=dzzdecode($share['password'])){
			showmessage('提取密码错误',dreferer());
		}
		dsetcookie('pass_'.$sid,authcode($_GET['password'],'ENCODE'));
		
	}else{
		include template('common/share_password');
		exit();
	}
}

//dsetcookie('dzz_share_sid',$sid);
C::t('share')->addview($sid);
if($share['type']=='url'){//网址类型的直接跳转
	$url=$share['path'];
	if(strpos($url,'?')!==false  && strpos($url,'sid=')===false){
		$url.='&sid='.$sid;
	}
	@header("Location: ".$share['path']);
	exit();
}
$path=$share['path'];
$dpath=dzzencode($path,'',300);
$icoarr=IO::getMeta($path);
if(empty($icoarr['path'])){
	C::t('share')->update($sid,array('status'=>-3));
	showmessage('分享文件已经删除');
}

if($icoarr['type']=='shortcut') $icoarr['type']=$icoarr['tdata']['type'];
if($icoarr['type']=='link' || $icoarr['type']=='video'){
	$url=$icoarr['url'];
	if(strpos($url,'?')!==false  && strpos($url,'sid=')===false){
		$url.='&sid='.$sid;
	}
	@header("Location: ".$url);
	exit();
}elseif($icoarr['type']=='dzzdoc'){
	$url="index.php?mod=document&icoid=".$dpath;
	if(strpos($url,'?')!==false  && strpos($url,'sid=')===false){
		$url.='&sid='.$sid;
	}
	@header("Location: ".$url);
	exit();
}elseif($icoarr['type']=='folder'){
	$dpath=dzzencode($path,'',60*60);
	$url="index.php?mod=folder&sid=".$sid;
	@header("Location: ".$url);
	exit();
}elseif($icoarr['type']=='app'){
	$url=str_replace(array('{dzzscript}','{adminscript}'),array('index.php','admin.php'),$icoarr['appurl']);
	if(strpos($url,'dzzjs:')!==false){//dzzjs形式时
		showmessage('不支持此应用');
	}else{
		//替换参数
		$url=preg_replace("/{(\w+)}/ie", "cansu_replace('\\1')", $url);
		//添加sid参数；
		if(strpos($url,'?')!==false  && strpos($url,'sid=')===false){
			$url.='&sid='.$sid;
		}
		@header("Location: $url");
		exit();
	}
}
if($_GET['a']=='down' && in_array($icoarr['type'],array('image','attach','document'))){
	IO::download($icoarr['path']);
	exit();
}

$imageexts=array('jpg','jpeg','png','gif'); //图片使用；
$filename=$icoarr['name'];
$ext=$icoarr['ext'];
if(!$ext) $ext=preg_replace("/\?.+/i",'',strtolower(substr(strrchr(rtrim($url,'.dzz'), '.'), 1, 10)));
if(in_array($ext,$imageexts)){
	$url='index.php?mod=io&op=thumbnail&original=1&path='.$dpath;
	@header("Location: $url");
	exit();
}elseif($ext=='mp3'){
	$url='index.php?mod=sound&path='.$dpath;
	@header("Location: $url");
	exit();
}
$bzarr=explode(':',$icoarr['rbz']?$icoarr['rbz']:$icoarr['bz']);
$bz=$bzarr[0];
$extall=C::t('app_open')->fetch_all_ext();
$exts=array();
foreach($extall as $value){
	if(!isset($exts[$value['ext']]) || $value['isdefault']) $exts[$value['ext']]=$value;
}

if(isset($exts[$bz.':'.$ext])){
	$data=$exts[$bz.':'.$ext];
}else{
	$data=$exts[$ext]?$exts[$ext]:array();
}
if($data){
	$url=$data['url'];
	if(strpos($url,'dzzjs:')!==false){//dzzjs形式时
		
	}else{
		//替换参数
		$url=preg_replace("/{(\w+)}/ie", "cansu_replace('\\1')", $url);
		//添加sid参数；
		if(strpos($url,'?')!==false  && strpos($url,'sid=')===false){
			$url.='&sid='.$sid;
		}	
		//添加path参数；
		if(strpos($url,'?')!==false  && strpos($url,'path=')===false){
			$url.='&path='.$dpath;
		}
		@header("Location: $url");
		exit();
	}
	
}else{//没有可用的打开方式，转入下载；
	IO::download($path);
	exit();
}
	
function cansu_replace($key,$icoarr){
	global $dpath,$icoarr;
	if($key=='path'){
		return $dpath;
	}else if($key=='icoid'){
		return 'preview_'.random(5);
	}else{
		return urlencode($icoarr[$key]);
	}
}

?>