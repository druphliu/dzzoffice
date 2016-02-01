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
include libfile('function/cache');
include libfile('function/news');


$navtitle='信息';
$navlast='基本设置';
$operation=trim($_GET['operation']);
$muids=array();
//error_reporting(E_ALL);
//判断用户是否有管理员权限
$perm=getPermByUid($_G['uid']);
if($perm<2){
	showmessage('没有权限',dreferer());
}
if(!$_G['cache']['news:setting']) loadcache('news:setting');
$setting=$_G['cache']['news:setting'];
$do=empty($_GET['do'])?'basic':trim($_GET['do']);
if($do=='basic'){
	if($operation=='selectuser'){
		$type=intval($_GET['type']);
		$muids=$type?$setting['moderators']:$setting['posters'];
		
		if(submitcheck('selectsubmit')){
			$uids=$_GET['uids'];
			if($uids){
				$muids=array_unique(array_merge($muids,$uids));
				C::t('news_setting')->update($type?'moderators':'posters',implode(',',$muids));
				updatecache('news:setting');
			}
			showmessage('do_success',DZZSCRIPT.'?mod=news&op=setting');
		}else{
			if($type)	$title='管理员';
			else $title='允许发布信息的用户';
			$navtitle=$title." - ".$navtitle;
			$navlast='<a href="'.DZZSCRIPT.'?mod=news&op=setting">设置</a> - '.$title;
			$refer=dreferer();
			
			include template('setting_utree');
			exit();
		}
	}elseif($operation=='deleteModerator'){
		$uid=intval($_GET['uid']);
		$type=intval($_GET['type']);
		$muids=$type?$setting['moderators']:$setting['posters'];
		foreach($muids as $key=>$value){
			if($value==$uid) unset($muids[$key]);
		}
		if(C::t('news_setting')->update($type?'moderators':'posters',implode(',',$muids))){
			updatecache('news:setting');
			exit(json_encode(array('msg'=>'success')));
		}else{
			exit(json_encode(array('msg'=>'removed')));
		}
	}else{
		if(submitcheck('settingsubmit')){
			$setarr=$_GET['settingnew'];
			
			$setarr['allownewnews']=intval($setarr['allownewnews']);
			$setarr['newsmod']=intval($_GET['newsmod']);
			$moderators=$_GET['moderators'];
			if($moderators) $setarr['moderators']=implode(',',$moderators);
			if($setarr['allownewnews']){
				$posters=$_GET['posters'];
				if($posters) $setarr['posters']=implode(',',$posters);
			}
			C::t('news_setting')->update_batch($setarr);
			updatecache('news:setting');
			showmessage('do_success',DZZSCRIPT.'?mod=news&op=setting',array(),array('alert'=>'right'));
		}else{
			
			$setting['allownewnews']=intval($setting['allownewnews']);
			$setting['newsmod']=intval($setting['newsmod']);
			$moderators=C::t('user')->fetch_all($setting['moderators']);
			$posters=C::t('user')->fetch_all($setting['posters']);
			
		}
	}
}elseif($do=='wxapp'){
	$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=news',1);
	$baseurl_info=DZZSCRIPT.'?mod=news&op=setting&do=wxapp';
	$baseurl_menu=DZZSCRIPT.'?mod=news&op=setting&do=wxapp&operation=menu';
	$baseurl_ajax=DZZSCRIPT.'?mod=news&op=setting&do=wxapp&operation=ajax';
	if(empty($operation)){
		if(submitcheck('settingsubmit')){
			$settingnew=array();
			$settingnew['agentid']=intval($_GET['agentid']);
			$settingnew['appstatus']=intval($_GET['appstatus']);
			if($appid) C::t('wx_app')->update($appid,array('agentid'=>$settingnew['agentid'],'status'=>$settingnew['appstatus']));
			C::t('news_setting')->update_batch($settingnew);
			updatecache('news:setting');
			showmessage('do_success',dreferer(),array(),array('alert'=>'right'));
		}else{
			$navtitle='微信信息应用设置';
			$navlast='微信设置';
			$settingnew=array();
			if(empty($setting['token'])) $settingnew['token']=$setting['token']=random(8);
			if(empty($setting['encodingaeskey']))  $settingnew['encodingaeskey']=$setting['encodingaeskey']=random(43);
			if($settingnew){
				C::t('news_setting')->update_batch($settingnew);
				updatecache('news:setting');
			}
			$wxapp=array('appid'=>$appid,
						 'name'=>'信息中心',
						 'desc'=>'企业新闻和信息应用，通过它可以让员工随时了解企业的最新资讯和新闻。',
						 'icon'=>'dzz/news/images/0.jpg',
						 'agentid'=> $setting['agentid'],
						 'token'=>$setting['token'],
						 'encodingaeskey'=>$setting['encodingaeskey'],
						 'host'=>$_SERVER['HTTP_HOST'],
						 'callback'=>$_G['siteurl'].'index.php?mod=news&op=wxreply',
						 'otherpic'=>'dzz/news/images/c.png',
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
			$menu_select=array('click'=>array('latest'=>'最新信息'),
								'link'=>array(
										$_G['siteurl'].DZZSCRIPT.'?mod=news&status=6'=>'我发布的',
										$_G['siteurl'].DZZSCRIPT.'?mod=news&status=2'=>'待审核的',
										$_G['siteurl'].DZZSCRIPT.'?mod=news&status=3'=>'我的草稿',
										$_G['siteurl'].DZZSCRIPT.'?mod=news&status=4'=>'我未读的'
								)
						);
			 foreach(DB::fetch_all("select * from %t where pid=0 order by disp",array('news_cat')) as $value){
				$menu_select['link'][$_G['siteurl'].DZZSCRIPT.'?mod=news&catid='.$value['catid']]=$value['name'];
			}
			
			$json_menu_select=json_encode($menu_select);
			$type=trim($_GET['type']);
			$typetitle=array('click'=>'设置菜单KEY值','link'=>'设置菜单跳转链接');
			
		}elseif($_GET['action']=='menu_save'){ //菜单保存
				C::t('news_setting')->update('menu',array('button'=>$_GET['menu']));
				if($appid) C::t('wx_app')->update($appid,array('menu'=>serialize(array('button'=>$_GET['menu']))));
				updatecache('news:setting');
				exit(json_encode(array('msg'=>'success')));
		}elseif($_GET['action']=='menu_publish'){//发布到微信
				$data=array('button'=>$_GET['menu']);
				C::t('news_setting')->update('menu',$data);
				if($appid) C::t('wx_app')->update($appid,array('menu'=>serialize($data)));
				updatecache('news:setting');
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
			$subclass=array();
		    foreach(DB::fetch_all("select * from %t where pid=0 order by disp limit 5",array('news_cat')) as $value){
				$subclass[]=array(
								 'type'=>'view',
								 'name'=>$value['name'],
								 'url'=>$_G['siteurl'].DZZSCRIPT.'?mod=news&catid='.$value['catid']
								 );
			}
			$menu_default=array('button'=>array(
												array(
													'type'=>'click',	
													'name'=>'最新信息',
													'key'=>'latest'
												),
												array(
													'name'=>'信息分类',
													'sub_button'=>$subclass
												),
												array(
													'name'=>'我的信息',
													'sub_button'=>array(
														array(
															'type'=>'view',	
															'name'=>'我发布的',
															'url'=>$_G['siteurl'].DZZSCRIPT.'?mod=news&status=6'
														),
														array(
															'type'=>'view',	
															'name'=>'待审核',
															'url'=>$_G['siteurl'].DZZSCRIPT.'?mod=news&status=2'
														),
														array(
															'type'=>'view',	
															'name'=>'未读信息',
															'url'=>$_G['siteurl'].DZZSCRIPT.'?mod=news&status=4'
														),
														array(
															'type'=>'view',	
															'name'=>'草稿',
															'url'=>$_G['siteurl'].DZZSCRIPT.'?mod=news&status=3'
														),
														/*array(
															'type'=>'view',	
															'name'=>'发布信息',
															'url'=>$_G['siteurl'].DZZSCRIPT.'?mod=news&op=edit'
														)*/
													)
												)
								)
						  );
			C::t('news_setting')->update('menu',$menu_default);
		    updatecache('news:setting');
			exit('success');
		}
		include template('common/wx_ajax');
		exit();
	}
}

include template('news_setting');
?>
 
