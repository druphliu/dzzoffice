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
//error_reporting(E_ALL);
include_once libfile('function/organization');
$navtitle='数据同步';
$do=$_GET['do'];
if(submitcheck('synsubmit')){
	
}elseif($do=='qiwechat_syn_org'){
	$i=intval($_GET['i']);
	$wx=new qyWechat(array('appid'=>$_G['setting']['CorpID'],'appsecret'=>$_G['setting']['CorpSecret'],'agentid'=>0));
	if($i<1){
		$data=array(
				  "id"=>1,
				  "name" => $_G['setting']['sitename'],   //部门名称
			  );
			if(!$wx->updateDepartment($data)){
				runlog('wxlog','更新顶级部门为站点名称, errCode:'.$wx->errCode.'; errMsg:'.$wx->errMsg);
			}
	}
	$wd=array();
	if($wxdepart=$wx->getDepartment()){
		foreach($wxdepart['department'] as $value){
			$wd[$value['id']]=$value;
		}
	}else{
		exit(json_encode(array('error'=>' <p class="danger">获取微信企业号的机构和部门信息出错，无法继续同步！请检查是否正确绑定了企业号的CorpID和CorpSecret</p><p class="info">'.$wx->errCode.':'.$wx->errMsg.'</p>')));
	}
	
	if($org=DB::fetch_first("select * from %t where orgid>%d  order by orgid ",array('organization',$i))){
		if($org['forgid']){
			 if(($forg=C::t('organization')->fetch($org['forgid'])) && !$forg['worgid']){
				 if($worgid=C::t('organization')->wx_update($forg['orgid'])){
					$forg['worgid']=$worgid;
				 }else{
					exit(json_encode(array('msg'=>'continue','start'=>$org['orgid'],'message'=>$org['orgname'].' <span class="danger">创建父机构失败，忽略</span>')));
				 }
			 }
		}
		$parentid=($org['forgid']==0?1:$forg['worgid']);
		if($org['worgid'] && $wd[$org['worgid']] && $parentid==$wd[$org['worgid']]['parentid']){//更新机构信息
			$data=array("id"=>$org['worgid']);
			if($wd[$org['worgid']]['name']!=$org['orgname']) $data['name']=$org['orgname'];
			if($wd[$org['worgid']]['parentid']!=$parentid) $data['parentid']=$parentid;
			if($wd[$org['worgid']]['order']!=$org['order']) $data['order']=$org['order'];
			if($data) $data['id']=$org['worgid'];
			if($data){
				 if(!$wx->updateDepartment($data)){
					exit(json_encode(array('msg'=>'continue','start'=>$org['orgid'],'message'=>$org['orgname'].' <span class="danger">'.$wx->errCode.':'.$wx->errMsg.'</span>')));
				 }
			}
			exit(json_encode(array('msg'=>'continue','start'=>$org['orgid'],'message'=>$org['orgname'].' <span class="success">更新成功</span>')));
		}else{ //创建机构信息
			$data=array(
				  "name" => $org['orgname'],   //部门名称
				  "parentid" =>$org['forgid']==0?1:$forg['worgid'],         //父部门id
				  "order" => $org['disp']+1,            //(非必须)在父部门中的次序。从1开始，数字越大排序越靠后
			  );
			if($ret=$wx->createDepartment($data)){
				C::t('organization')->update($org['orgid'],array('worgid'=>$ret['id']));
				exit(json_encode(array('msg'=>'continue','start'=>$org['orgid'],'message'=>$org['orgname'].' <span class="success">创建成功</span>')));
			}else{
				if($wx->errCode=='60008'){//部门的worgid不正确导致的问题
					foreach($wd as $value){
						if($value['name']==$data['name'] && $value['parentid']=$data['parentid']){
							C::t('organization')->update($org['orgid'],array('worgid'=>$value['id']));
							exit(json_encode(array('msg'=>'continue','start'=>$org['orgid'],'message'=>$org['orgname'].' <span class="success">更新成功</span>')));
						}
					}
				}
				exit(json_encode(array('msg'=>'continue','start'=>$org['orgid'],'message'=>$org['orgname'].' <span class="danger">'.$wx->errCode.':'.$wx->errMsg.'</span>')));
			}
		}
	}else{
		exit(json_encode(array('msg'=>'success')));
	}
}elseif($do=='qiwechat_syn_user'){
	$i=intval($_GET['i']);
	$syngids=array();
	if($syngid=getglobal('setting/synorgid')){ //设置的需要同步的部门
		$syngids=getOrgidTree($syngid);
	}
	$wx=new qyWechat(array('appid'=>$_G['setting']['CorpID'],'appsecret'=>$_G['setting']['CorpSecret'],'agentid'=>0));
	
	if($user=DB::fetch_first("select u.*,o.orgid from ".DB::table('user'). " u LEFT JOIN ".DB::table('organization_user')." o ON o.uid=u.uid where u.uid>$i and o.orgid>0 order by uid")){
		
		$worgids=array();
		if($orgids=C::t('organization_user')->fetch_orgids_by_uid($user['uid'])){
			if($syngids ){
				$orgids=array_intersect($orgids,$syngids);
			}
			if($orgids){
				foreach(C::t('organization')->fetch_all($orgids) as $value){
					if($value['worgid']) $worgids[]=$value['worgid'];
					else{
						if($worgid=C::t('organization')->wx_update($value['orgid'])){
							$worgids[]=$worgid;
						}
					}
				}
			}
		}
		if(!$worgids){
			$data=array( "userid" => "dzz-".$user['uid'],
						 "enable"=>0,
						 "department"=>1,
						);
			if($wx->updateUser($data)){
				exit(json_encode(array('msg'=>'continue','start'=>$user['uid'],'message'=>$user['username'].'<span class="info">不在同步范围，已禁用</span>')));
			}else{
				exit(json_encode(array('msg'=>'continue','start'=>$user['uid'],'message'=>$user['username'].' <span class="info">不在同步范围，忽略</span>')));
			}
			
		}
		$profile=C::t('user_profile1')->fetch_all($user['uid']);
		if($wxuser=$wx->getUserInfo('dzz-'.$user['uid'])){//更新用户信息
			
				$data=array(
						 "userid" => 'dzz-'.$user['uid'],
						 "name" => $user['username'],
						
						 //"position" => '',
						 "email" =>$user['email'],
						 "enable"=>$user['status']?0:1
					 );
				  if(array_diff($wxuser['department'],$worgids)){
					 $data['department']=$worgids;
				  }
				  if($user['phone']  && $user['phone']!=$wxuser['mobile']){
					  $data['mobile']=$user['phone'];
				  }
				  if($user['weixinid'] && $wxuser['wechat_status']==4){
					  $data['weixinid']=$user['weixinid'];
				  }
				  if($profile['telephone'] && $profile['telephone']!=$wxuser['tel']){
					  $data['tel']=$profile['telephone'];
				  }
				  if($profile['gender'] && ($profile['gender']-1)!=$wxuser['gender']){
					  $data['gender']=$profile['gender']-1;
				  }
				
				if($wx->updateUser($data)){
					$setarr=array('wechat_status'=>$wxuser['status']);
					$setarr['weixinid']=empty($wxuser['weixinid'])?$user['weixinid']:$wxuser['weixinid'];
					$setarr['phone']=empty($user['phone'])?$wxuser['phone']:$user['phone'];
					$setarr['wechat_userid']='dzz-'.$user['uid'];
					C::t('user')->update($user['uid'],$setarr);
					exit(json_encode(array('msg'=>'continue','start'=>$user['uid'],'message'=>$user['username'].' <span class="success">更新成功</span>')));
				}else{
					exit(json_encode(array('msg'=>'continue','start'=>$user['uid'],'message'=>$user['username'].' <span class="danger">'.$wx->errCode.':'.$wx->errMsg.'</span>')));
				}
			
		}else{ //创建用户信息
			$data=array(
					 "userid" => "dzz-".$user['uid'],
					 "name" => $user['username'],
					 "department" => $worgids,
					 //"position" => '',
					 "email" =>$user['email'],
					 "weixinid" => $user['wechat']
				 );
			  if($user['phone']){
				  $data['mobile']=$user['phone'];
			  }
			  if($profile['telephone']){
				  $data['tel']=$profile['telephone'];
			  }
			  if($profile['gender']){
				  $data['gender']=$profile['gender']-1;
			  }
			
			if($ret=$wx->createUser($data)){
				C::t('user')->update($user['uid'],array('wechat_userid'=>'dzz-'.$user['uid']));
				exit(json_encode(array('msg'=>'continue','start'=>$user['uid'],'message'=>$user['username'].'  <span class="success">创建成功</span>')));
			}else{
				exit(json_encode(array('msg'=>'continue','start'=>$user['uid'],'message'=>$user['username'].' <span class="danger">'.$wx->errCode.':'.$wx->errMsg.'</span>')));
			}
		}
	}else{
		exit(json_encode(array('msg'=>'success')));
	}
}else{
	
	include template('wxsyn');
}
?>
