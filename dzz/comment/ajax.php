<?php
/*
 * 此应用的通知接口
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
if(!defined('IN_DZZ')) {
	exit('Access Denied');
}
include libfile('function/code');

$do=trim($_GET['do']);
$guests=array('getcomment','getThread','getNewThreads','getReply','getReplys','getUserToJson');
if(empty($_G['uid']) && !in_array($do,$guests)) {
	include template('common/header_ajax');
	/*echo "<script type=\"text/javascript\">";
	echo "try{top._login.logging();}catch(e){}";
	echo "</script>";	*/
	echo '&nbsp;&nbsp;&nbsp;<a href="user.php?mod=logging&action=login" class="btn btn-primary">登录</a>&nbsp;&nbsp;&nbsp;<a href="user.php?mod=register" class="btn btn-success">注册</a>';
	include template('common/footer_ajax');
	exit();
}

if(submitcheck('replysubmit')){
	
	$message=censor($_GET['message']);
	
	if(empty($message)){
		showmessage('请输入评论内容',DZZSCRIPT.'?mod=comment',array());
	}
	//处理@
	$at_users=array();
	$message=preg_replace_callback("/@\[(.+?):(.+?)\]/i","atreplacement",$message);
	$setarr=array(  'author'=>$_G['username'],
					'authorid'=>$_G['uid'],
					'pcid'=>intval($_GET['pcid']),
					'rcid'=>intval($_GET['rcid']),
					'id'=>intval($_GET['id']),
					'idtype'=>trim($_GET['idtype']),
					'module'=>trim($_GET['module']),
					'ip'=>$_G['clientip'],
					'dateline'=>TIMESTAMP,
					'message'=>$message,
					);
	
	if(!$setarr['cid']=C::t('comment')->insert_by_cid($setarr,$at_users,$_GET['attach'])){
		showmessage('服务器内部错误,请稍候再试，或联系管理员',DZZSCRIPT.'?mod=comment',array('message'=>$message));
	}
	$setarr['attachs']=C::t('comment_attach')->fetch_all_by_cid($setarr['cid']);
	$setarr['dateline']=dgmdate($setarr['dateline'],'u');
	$setarr['message']=dzzcode($message);
	$setarr['allowattach']=intval($_GET['allowattach']);
	$setarr['allowat']=intval($_GET['allowat']);
	$setarr['allowsmiley']=intval($_GET['allowsmiley']);
	if($_G['adminid']==1 || $_G['uid']==$setarr['authorid']) $setarr['haveperm']=1;
	showmessage('do_success',DZZSCRIPT.'?mod=comment',array('data'=>rawurlencode(json_encode($setarr))));
}elseif($do=='edit'){ 
  $cid=intval($_GET['cid']);
  if($data=C::t('comment')->fetch($cid)){
	  if(!$_G['adminid']==1 && $_G['uid']!=$data['authorid']) showmessage('没有权限');
  }else{
	 showmessage('评论不存在或已删除'); 
  }
  if(!submitcheck('editsubmit')){
	$data['attachs']=C::t('comment_attach')->fetch_all_by_cid($cid);
	if($data['rcid']) $data['rpost']=C::t('comment')->fetch($data['rcid']);
	$space=dzzgetspace($_G['uid']);
	$space['attachextensions'] = $space['attachextensions']?explode(',',$space['attachextensions']):array();
	$space['maxattachsize'] =intval($space['maxattachsize']);
  }else{
	 C::t('comment')->update_by_cid($cid,censor($_GET['message']),intval($_GET['rcid']),$_GET['attach']);
	 $value=array();
	 if($value=C::t('comment')->fetch($cid)){
		$value['message']=dzzcode($value['message']);
		$value['dateline']=dgmdate($value['dateline'],'u');
		$value['attachs']=C::t('comment_attach')->fetch_all_by_cid($value['cid']);
		if($value['rcid']){
			$value['rpost']=C::t('comment')->fetch($value['rcid']);
		}
		$value['allowattach']=intval($_GET['allowattach']);
		$value['allowat']=intval($_GET['allowat']);
		$value['allowsmiley']=intval($_GET['allowsmiley']);
	}
	 showmessage('do_success',DZZSCRIPT.'?mod=comment',array('data'=>rawurlencode(json_encode($value))));
  }
	
}elseif($do=='getcomment'){
	
	$id=intval($_GET['id']);
	$idtype=trim($_GET['idtype']);
	$page=empty($_GET['page'])?1:intval($_GET['page']);
	$perpage=10;
	$start=($page-1)*$perpage;
	$limit=$start."-".$perpage;
	$gets = array(
				'mod'=>'comment',
				'op' =>'ajax',
				'do'=>'getcomment',
				'id'=>$id,
				'idtype'=>$idtype,
			);
	$theurl = BASESCRIPT."?".url_implode($gets);
	$count=C::t('comment')->fetch_all_by_idtype($id,$idtype,$limit,true);
	$list=array();
	if($count){
		$list=C::t('comment')->fetch_all_by_idtype($id,$idtype,$limit);
	}
	$multi=multi($count, $perpage, $page, $theurl,'pull-right');
}elseif($do=='getcommentbycid'){
	$cid=intval($_GET['cid']);
	
	if($value=C::t('comment')->fetch($cid)){
		$value['message']=dzzcode($value['message']);
		$value['dateline']=dgmdate($value['dateline'],'u');
		$value['attachs']=C::t('comment_attach')->fetch_all_by_cid($value['cid']);
		
		if($value['rcid']){
			$value['rpost']=C::t('comment')->fetch($value['rcid']);
		}
		$value['replies']=DB::result_first("select COUNT(*) from  %t where pcid=%d",array('comment',$value['cid']));
		$value['replys']=C::t('comment')->fetch_all_by_pcid($value['cid'],5);
		
	}
}elseif($do=='getreplys'){
	$cid=intval($_GET['cid']);
	$limit=empty($_GET['limit'])?0:intval($_GET['limit']);
	$count=C::t('comment')->fetch_all_by_pcid($cid,$limit,true);
	if($count){
		$list=C::t('comment')->fetch_all_by_pcid($cid,$limit);
	}
}elseif($do=='delete'){
	$cid=intval($_GET['cid']);
	$data=C::t('comment')->fetch($cid);
	if($_G['adminid']!=1 && $_G['uid']!=$data['authorid']) exit(json_encode(array('msg'=>'没有权限')));
	if(C::t('comment')->delete_by_cid($cid)){
		exit(json_encode(array('msg'=>'success')));
	}else{
		exit(json_encode(array('error'=>'删除错误')));
	}
}elseif($do=='upload'){
	include_once libfile('class/uploadhandler');
	$space=dzzgetspace($_G['uid']);
	$allowedExtensions = $space['attachextensions']?explode(',',$space['attachextensions']):array();
	
	// max file size in bytes
	$sizeLimit =intval($space['maxattachsize']);
	
	$options=array('accept_file_types'=>$allowedExtensions?("/(\.|\/)(".implode('|',$allowedExtensions).")$/i"):"/.+$/i",
					'max_file_size'=>$sizeLimit?$sizeLimit:null,
					'upload_dir' =>$_G['setting']['attachdir'].'cache/',
					'upload_url' => $_G['setting']['attachurl'].'cache/',
					);
	$upload_handler = new uploadhandler($options);
	exit();	

}elseif($do=='getPublishForm'){
	$id=intval($_GET['id']);
	$idtype=trim($_GET['idtype']);
	$module=trim($_GET['module']);
	$space=dzzgetspace($_G['uid']);
	$space['attachextensions'] = $space['attachextensions']?explode(',',$space['attachextensions']):array();
	$space['maxattachsize'] =intval($space['maxattachsize']);
	$pcid=0;
}elseif($do=='getReplyForm'){
	$id=intval($_GET['id']);
	$idtype=trim($_GET['idtype']);
	$module=trim($_GET['module']);
	$cid=intval($_GET['cid']);
	if($cid){
		$data=C::t('comment')->fetch($cid);
		$id=$data['id'];
		$idtype=$data['idtype'];
		$module=$data['module'];
	}
	$space=dzzgetspace($_G['uid']);
	$space['attachextensions'] = $space['attachextensions']?explode(',',$space['attachextensions']):array();
	$space['maxattachsize'] =intval($space['maxattachsize']);
}
function atreplacement($matches){
	global $at_users,$_G;
	
	include_once libfile('function/organization');
	if(strpos($matches[2],'g')!==false){
		$gid=str_replace('g','',$matches[2]);
		if(($org=C::t('organization')->fetch($gid)) && checkAtPerm($gid)){//判定用户有没有权限@此部门
			$uids=getUserByOrgid($gid,true,array(),true);
			foreach($uids as $uid){
				if($uid!=$_G['uid']) $at_users[]=$uid;
			}
			return '[org='.$gid.'] @'.$org['orgname'].'[/org]';
		}else{
			return '';
		}
	}else {
		$uid=str_replace('u','',$matches[2]);
		if(($user=C::t('user')->fetch($uid)) && $user['uid']!=$_G['uid']){
			$at_users[]=$user['uid'];
			return '[uid='.$user['uid'].']@'.$user['username'].'[/uid]';
		}else{
			return $matches[0];
		}
	}
}
include template('ajax');
?>
