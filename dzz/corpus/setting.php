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
include libfile('function/organization');
$navs=array('basic'=>'基本设置',
			'cover'=>'封面管理',
			'manage'=>'文集管理',
			'default'=>'默认设置'
			);
$do=empty($_GET['do'])?'basic':trim($_GET['do']);
$navtitle=$navs[$do].' - 文集';
$navlast=$navs[$do];
$operation=trim($_GET['operation']);
$muids=array();

//判断用户是否有管理员权限
if($_G['adminid']!=1 ){
	showmessage('没有权限',dreferer());
}

if($do=='basic'){
	if(submitcheck('settingsubmit')){
		$setarr=$_GET['settingnew'];
		$setarr['maxcorpus']=intval($setarr['maxcorpus']);
		$setarr['allownewcorpus']=intval($setarr['allownewcorpus']);
		$setarr['archiveview']=intval($_GET['archiveview']);
		C::t('corpus_setting')->update_batch($setarr);
		
		showmessage('do_success',DZZSCRIPT.'?mod=corpus&op=setting&do=basic');
	}else{
		
		$setting=C::t('corpus_setting')->fetch_all(array('moderators','allownewcorpus','maxcorpus','archiveview'));
		$setting['allownewcorpus']=intval($setting['allownewcorpus']);
		$setting['maxcorpus']=intval($setting['maxcorpus']);
		$setting['archiveview']=intval($setting['archiveview']);
		if($setting['moderators']){
			$muids=explode(',',$setting['moderators']);
		}
		//$moderators=array();
		//$moderators=C::t('user')->fetch_all($muids);
		//处理发布权限
		$orgids=$uids=$sel_org=$sel_user=array();
		foreach($muids as $value){
			if(strpos($value,'uid_')!==false){
				$uids[]=str_replace('uid_','',$value);
			}else{
				$orgids[]=$value;
			}
		} 
		$open=array();
		if($orgids){
			$sel_org=C::t('organization')->fetch_all($orgids);
			foreach($sel_org  as $key=> $value){
				$orgpath=getPathByOrgid($value['orgid']);
				$sel_org[$key]['orgpath']=implode('-',array_reverse($orgpath));
				$arr=array_reverse(array_keys($orgpath));
				array_pop($arr);
				$count=count($arr);
				if($open[$arr[$count-1]]){
					if(count($open[$arr[$count-1]])>$count) $open[$arr[count($arr)-1]]=$arr;
				}else{
					$open[$arr[$count-1]]=$arr;
				}
			}
			if(in_array('other',$orgids)){
				$sel_org[]=array('orgname'=>'无机构人员','orgid'=>'other','forgid'=>1);
			}
		}
		if($uids){
			$sel_user=C::t('user')->fetch_all($uids);
			if($aorgids=C::t('organization_user')->fetch_orgids_by_uid($uids)){
				foreach($aorgids as $orgid){
					$arr=getUpOrgidTree($orgid,true);
					$arr=array_reverse($arr);
					$count=count($arr);
					if($open[$arr[$count-1]]){
						if(count($open[$arr[$count-1]])>$count) $open[$arr[count($arr)-1]]=$arr;
					}else{
						$open[$arr[$count-1]]=$arr;
					}
				 }
			}
		} 
		 $openarr=json_encode(array('muids'=>$open));
	}
}elseif($do=='cover'){
	
	if($operation=='upload'){
		include libfile('class/uploadhandler');
		$options=array( 'accept_file_types' => '/\.(gif|jpe?g|png)$/i',
						'upload_dir' =>$_G['setting']['attachdir'].'cache/',
						'upload_url' => $_G['setting']['attachurl'].'cache/',
						'thumbnail'=>array('max-width'=>130,'max-height'=>160)
						);
		$upload_handler = new uploadhandler($options);
		exit();
	}elseif($operation=='save'){
		$aids=empty($_GET['aids'])?array():explode(',',$_GET['aids']);
		C::t('corpus_setting')->updateCovers($aids);
		exit('success');
	}else{
		$covers=C::t('corpus_setting')->getCovers();
		include template('setting_cover');
		exit();
	}
}elseif($do=='manage'){//文集管理
	if(submitcheck('settingsubmit')){
		foreach($_GET['del'] as $cid){
			C::t('corpus')->delete_permanent_by_cid($cid);
		}
		showmessage('文集删除成功',$_GET['refer']);
	}elseif($operation=='archive'){
		$cid=intval($_GET['cid']);
		if(C::t('corpus')->archive_by_cid($cid)){
			showmessage('文集归档成功',$_GET['refer']);
		}else{
			showmessage('文集归档失败',$_GET['refer']);
		}
	}elseif($operation=='restore'){
		$cid=intval($_GET['cid']);
		if(C::t('corpus')->restore_by_cid($cid)){
			showmessage('文集恢复成功',$_GET['refer']);
		}else{
			showmessage('文集恢复失败',$_GET['refer']);
		}	
	}elseif($operation=='delete'){
		$cid=intval($_GET['cid']);
		if(C::t('corpus')->delete_permanent_by_cid($cid)){
			showmessage('文集删除成功',$_GET['refer']);
		}else{
			showmessage('文集删除失败',$_GET['refer']);
		}
	}elseif($operation=='forceindex'){
		$cid=intval($_GET['cid']);
		$data=C::t('corpus')->fetch($cid);
		if($data['perm']>0 && $data['forceindex']<1){
			exit(json_encode(array('error'=>'私有的文集无法设置')));
		}
		if(C::t('corpus')->update($cid,array('forceindex'=>$data['forceindex']?0:1))){
			exit(json_encode(array('msg'=>'设置成功！','cid'=>$cid,'forceindex'=>!$data['forceindex'])));
		}else{
			exit(json_encode(array('error'=>'设置失败！')));
		}
	}else{
		
		$page = empty($_GET['page'])?1:intval($_GET['page']);
		$perpage=10;
		$keyword=trim($_GET['keyword']);
		$archive=intval($_GET['archive']);
		$delete=intval($_GET['delete']);
		$forceindex=intval($_GET['forceindex']);
		$gets = array(
				'mod'=>'corpus',
				'keyword'=>$keyword,
				'op' =>'setting',
				'do'=>'manage',
				'archive'=>$archive,
				'delete'=>$delete,
				'forceindex'=>$forceindex,
				
			);
		$theurl = BASESCRIPT."?".url_implode($gets);
		$refer=urlencode($theurl.'&page='.$page);
		$limit=($page-1)*$perpage.'-'.$perpage;
		$temp=$list=array();
		if($count=C::t('corpus')->fetch_all_for_manage($limit,$keyword,$delete,$archive,$forceindex,true)){
			$temp=C::t('corpus')->fetch_all_for_manage($limit,$keyword,$delete,$archive,$forceindex);
		}
		foreach($temp as $value){
			if($value['deleteuid']){
				 $user=getuserbyuid($value['deleteuid']);
				 $value['deleteusername']=$user['username'];
			}elseif($value['archiveuid']){
				 $user=getuserbyuid($value['archiveuid']);
				 $value['archiveusername']=$user['username'];
			}
			$arr=C::t('corpus')->update_count_by_cid($value['cid']);
			$value['documents']=$arr['documents'];
			$value['follows']=$arr['follows'];
			$value['members']=$arr['members'];
			$list[]=$value;
		}
		$multi=multi($count, $perpage, $page, $theurl,'pull-right');
	}
}elseif($do=='wxapp'){
	$setting=C::t('corpus_setting')->fetch_all();
	$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=corpus',1);
	$baseurl_info=DZZSCRIPT.'?mod=corpus&op=setting&do=wxapp';
	$baseurl_menu=DZZSCRIPT.'?mod=corpus&op=setting&do=wxapp&operation=menu';
	$baseurl_ajax=DZZSCRIPT.'?mod=corpus&op=setting&do=wxapp&operation=ajax';
	if(empty($operation)){
		if(submitcheck('settingsubmit')){
			$settingnew=array();
			$settingnew['agentid']=intval($_GET['agentid']);
			$settingnew['appstatus']=intval($_GET['appstatus']);
			if($appid) C::t('wx_app')->update($appid,array('agentid'=>$settingnew['agentid'],'status'=>$settingnew['appstatus']));
			C::t('corpus_setting')->update_batch($settingnew);
			
			showmessage('do_success',dreferer(),array(),array('alert'=>'right'));
		}else{
			$navtitle='微信应用设置';
			$navlast='微信设置';
			$settingnew=array();
			if(empty($setting['token'])) $settingnew['token']=$setting['token']=random(8);
			if(empty($setting['encodingaeskey']))  $settingnew['encodingaeskey']=$setting['encodingaeskey']=random(43);
			if($settingnew){
				C::t('corpus_setting')->update_batch($settingnew);
			}
			$wxapp=array('appid'=>$appid,
						 'name'=>'文集',
						 'desc'=>'企业文集应用，知识库、多人写作协作、支持版本控制。',
						 'icon'=>'dzz/corpus/images/0.jpg',
						 'agentid'=> $setting['agentid'],
						 'token'=>$setting['token'],
						 'encodingaeskey'=>$setting['encodingaeskey'],
						 'host'=>$_SERVER['HTTP_HOST'],
						 'callback'=>$_G['siteurl'].'index.php?mod=corpus&op=wxreply',
						 'otherpic'=>'dzz/corpus/images/c.png',
						 'status'=>$setting['appstatus'],	//应用状态
						 'report_msg'=>1,                	//用户消息上报
						 'notify'=>0,                   	 //用户状态变更通知
						 'report_location'=>0,           	//上报用户地理位置
					);
			C::t('wx_app')->insert($wxapp,1,1);
		}
	}elseif($operation=='menu'){
		$menu=$setting['menu']?unserialize($setting['menu']):'';
	}elseif($operation=='ajax'){	
		if($_GET['action']=='setEventkey'){
			//支持的菜单事件
			$menu_select=array('click'=>array(),
								'link'=>array(
										$_G['siteurl'].DZZSCRIPT.'?mod=corpus'=>'我的文集',
										$_G['siteurl'].DZZSCRIPT.'?mod=corpus&op=opened'=>'公开文集',
										$_G['siteurl'].DZZSCRIPT.'?mod=corpus&op=archive'=>'归档文集'
								)
						);
			 
			
			$json_menu_select=json_encode($menu_select);
			$type=trim($_GET['type']);
			$typetitle=array('click'=>'设置菜单KEY值','link'=>'设置菜单跳转链接');
			
		}elseif($_GET['action']=='menu_save'){ //菜单保存
				C::t('corpus_setting')->update('menu',array('button'=>$_GET['menu']));
				if($appid) C::t('wx_app')->update($appid,array('menu'=>serialize(array('button'=>$_GET['menu']))));
				exit(json_encode(array('msg'=>'success')));
		}elseif($_GET['action']=='menu_publish'){//发布到微信
				$data=array('button'=>$_GET['menu']);
				  C::t('corpus_setting')->update('menu',$data);
				if($appid) C::t('wx_app')->update($appid,array('menu'=>serialize($data)));
				//发布菜单到微信
				if(getglobal('setting/CorpID') && getglobal('setting/CorpSecret') && $setting['agentid']){
					$wx=new qyWechat(array('appid'=>getglobal('setting/CorpID'),'appsecret'=>getglobal('setting/CorpSecret')));
					//处理菜单数据，所有本站链接添加oauth2地址
					foreach($data['button'] as $key=>$value){
						if($value['url'] && strpos($value['url'],$_G['siteurl'])===0){
							$data['button'][$key]['url']=$wx->getOauthRedirect(getglobal('siteurl').'index.php?mod=system&op=wxredirect&url='.dzzencode($value['url']));
						}elseif($value['sub_button']){
							foreach($value['sub_button'] as $key1=>$value1){
								if($value1['url'] && strpos($value1['url'],$_G['siteurl'])===0){
									$data['button'][$key]['sub_button'][$key1]['url']=$wx->getOauthRedirect(getglobal('siteurl').'index.php?mod=system&op=wxredirect&url='.dzzencode($value1['url']));
								}
							}
						}
					}
					if($wx->createMenu($data,$setting['agentid'])){
						exit(json_encode(array('msg'=>'success')));
					}else{
						exit(json_encode(array('error'=>'发布失败,errCode:'.$wx->errCode.',errMsg:'.$wx->errMsg)));
					}
				}else{
					exit(json_encode(array('error'=>'发布失败,应用还没有创建微信agentid')));
				}
				
		}elseif($_GET['action']=='menu_default'){//恢复默认
			
			$menu_default=array('button'=>array(
												array(
													'type'=>'view',	
													'name'=>'我的文集',
													'url'=>$_G['siteurl'].DZZSCRIPT.'?mod=corpus'
												),
												array(
													'type'=>'view',	
													'name'=>'公开文集',
													'url'=>$_G['siteurl'].DZZSCRIPT.'?mod=corpus&op=opened'
												),
												array(
													'type'=>'view',	
													'name'=>'归档文集',
													'url'=>$_G['siteurl'].DZZSCRIPT.'?mod=corpus&op=archive'
												)
										
								)
						  );
			C::t('corpus_setting')->update('menu',$menu_default);
			exit('success');
		}
		include template('common/wx_ajax');
		exit();
	}


}

include template('corpus_setting');
?>
 
