<?php
/* @authorcode  f8b5ecdc1c3eb0725f1d38f5f0216d53
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
if(!defined('IN_DZZ')) {
	exit('Access Denied');
}

if($_GET['do']=='imageupload'){
		include libfile('class/uploadhandler');
		$options=array( 'accept_file_types' => '/\.(gif|jpe?g|png)$/i',
						'upload_dir' =>$_G['setting']['attachdir'].'cache/',
						'upload_url' => $_G['setting']['attachurl'].'cache/',
						'thumbnail'=>array('max-width'=>240,'max-height'=>160)
						);
		$upload_handler = new uploadhandler($options);
		exit();
}elseif($_GET['do']=='updateview'){
	$newid=intval($_GET['newid']);
	C::t('news')->increase($newid,array('views'=>1));
	if($_G['uid']){
		if($vid=DB::result_first("select vid from %t where newid=%d and uid=%d",array('news_viewer',$newid,$_G['uid']))){
			DB::query("update %t SET views=views+1 where vid=%d",array('news_viewer',$vid));
		}else{
			$addviewer=array('newid'=>$newid,
							 'uid'=>$_G['uid'],
							 'username'=>$_G['username'],
							 'dateline'=>TIMESTAMP
							 );
			C::t('news_viewer')->insert($addviewer);
		}
	}
	exit('success');
}elseif($_GET['do']=='sendModNotice'){
	if(!$_G['cache']['news:setting']) loadcache('news:setting');
	$setting=$_G['cache']['news:setting'];
	
	//通知管理员审核
	$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=news',1);
	foreach($setting['moderators'] as $muid){
		//发送通知
		$notevars=array(
						'from_id'=>$appid,
						'from_idtype'=>'app',
						'url'=>DZZSCRIPT.'?mod=news&status=2',
						'author'=>getglobal('username'),
						'authorid'=>getglobal('uid'),
						'dataline'=>dgmdate(TIMESTAMP),
						);
		
			$action='news_moderate';
			$type='news_moderate';
		
		dzz_notification::notification_add($muid, $type, $action, $notevars, 0,'dzz/news');
	}
	exit(json_encode(array('msg'=>'提醒信息发送成功')));
}elseif($_GET['do']=='news_delete'){
	include_once libfile('function/news');
	$newid=!empty($_GET['newid'])?intval($_GET['newid']):0;
	$data=C::t('news')->fetch($newid);
	$perm=getPermByUid($_G['uid']);
	if($perm<2 && $data['authorid']!=$_G['uid']) exit(json_encode(array('error'=>'没有权限')));
	if(C::t('news')->delete_by_newid($newid)){
		exit(json_encode(array('msg'=>'success')));
	}else{
		exit(json_encode(array('error'=>'删除失败')));
	}
}elseif($_GET['do']=='getViewerByNewid'){
	$newid=empty($_GET['newid'])?0:intval($_GET['newid']);
	//查阅情况
	$page = empty($_GET['page'])?1:intval($_GET['page']);
	$perpage=21;
	$gets = array(
			'mod'=>'news',
			'op'=>'ajax',
			'do'=>'getViewerByNewid',
			'newid'=>$newid
		);
	$theurl = DZZSCRIPT."?".url_implode($gets);
	$start=($page-1)*$perpage;
	$limit=($page-1)*$perpage.'-'.$perpage;
	if($count=C::t('news_viewer')->fetch_all_by_newid($newid,$limit,1)){
		$list=C::t('news_viewer')->fetch_all_by_newid($newid,$limit);
		$multi=multi($count, $perpage, $page, $theurl,'pull-right');
	}
	include template('news_ajax');
}
?>
