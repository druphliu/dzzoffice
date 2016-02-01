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
$ismobile=helper_browser::ismobile();
if(empty($_G['uid'])) {
	include template('common/header_reload');
	echo "<script type=\"text/javascript\">";
	echo "try{top._login.logging();}catch(e){}";
	echo "try{win.Close();}catch(e){}";
	echo "</script>";	
	include template('common/footer_reload');
	exit('<a href="user.php?mod=logging&action=login">需要登录</a>');
}
if(submitcheck('edit')){
	$did=intval($_GET['did']);
	//$subject=empty($_GET['subject'])?'新文档':str_replace('...','',getstr($_GET['subject'],80));
	//桌面上的文档 $area=='' && $areaid=0;
	//项目内文档  $area=='project' && $areaid==$pjid;
	$area='corpus';
	$cid=intval($_GET['cid']);
	$fid=intval($_GET['fid']);
	$new=intval($_GET['newversion']);
	$autosave=intval($_GET['autosave']);
	$class=C::t('corpus_class')->fetch($fid);
	if($autosave) $new=0;
	//存储文档内容到文本文件内
	$message=helper_security::checkhtml($_GET['message']);//str_replace(array("\r\n", "\r", "\n"), "",$_GET['message']); //去除换行
	if(!$attach=getTxtAttachByMd5($message,$class['fname'].'.dzzdoc')){
		showmessage('保存文档错误，请检查您的磁盘是否有足够空间或写入权限',dreferer());
	}
	//获取文档内附件
	$attachs=getAidsByMessage($message);
	$setarr=array(
				  'uid'=>$_G['uid'],
				  'username'=>$_G['username'],
				  'aid'=>$attach['aid'],
				  'fid'=>$fid,
				  'did'=>$did,
				  );
				 
	if(!$did=C::t('document')->insert($setarr,$attachs,$area,$cid,$new)){
		showmessage('保存文档错误，请检查您数据库是否正常');
	}else{
		C::t('corpus_class')->update($fid,array('did'=>$did));
			
			//产生事件
			if(!$autosave){//自动保存不产生事件
				$event =array(    'uid'=>getglobal('uid'),
								  'username'=>getglobal('username'),
								  'body_template'=>$new?'corpus_reversion_doc':'corpus_edit_doc',
								  'body_data'=>serialize(array('cid'=>$cid,'fid'=>$fid,'fname'=>$class['fname'])),
								  'dateline'=>TIMESTAMP,
								  'bz'=>'corpus_'.$cid,
							  );
				C::t('corpus_event')->insert($event);
				//通知文档原作者
				if($class['uid']!=getglobal('uid')){
					//发送通知
					$notevars=array(
									'from_id'=>$appid,
									'from_idtype'=>'app',
									'url'=>DZZSCRIPT.'?mod=corpus&op=list&cid='.$class['cid'].'&fid='.$class['fid'],
									'author'=>getglobal('username'),
									'authorid'=>getglobal('uid'),
									'dataline'=>dgmdate(TIMESTAMP),
									'fname'=>getstr($class['fname'],30),
									
									);
					if($new){
						$action='corpus_doc_reversion';
						$type='corpus_doc_reversion_'.$class[$cid];
					}else{
						$action='corpus_doc_edit';
						$type='corpus_doc_edit_'.$class[$cid];
					}
					dzz_notification::notification_add($class['uid'], $type, $action, $notevars, 0,'dzz/corpus');
				}
			
			}
		
		$return=array('id'=>$fid, 'autosave'=>$autosave);
		showmessage('do_success',dreferer(),array('data'=>rawurlencode(json_encode($return))),array('showmsg'=>true));
	}
}else{
	$navtitle='';
	$str='';
	$cid=intval($_GET['cid']);
	$fid=intval($_GET['fid']);
	$class=C::t('corpus_class')->fetch($fid);
	$did=$class['did'];
	if($document=C::t('document')->fetch_by_did($did)){
		$dzzpath=getDzzPath($document);
		$str=trim(IO::getFileContent($dzzpath));
		$navtitle=$document['subject'];
	}else{
		$navtitle=$class['fname'];
		$document['subject']=$class['fname'];
	}
	include template('list/newdoc');
}
function getAidsByMessage($message){
	$aids=array();
	if(preg_match_all("/".rawurlencode('attach::')."(\d+)/i",$message,$matches)){
		$aids=$matches[1];
	}
	if(preg_match_all("/path=\"attach::(\d+)\"/i",$message,$matches1)){
		if($matches1[1]) $aids=array_merge($aids,$matches1[1]);
	}
	return array_unique($aids);
}


?>
