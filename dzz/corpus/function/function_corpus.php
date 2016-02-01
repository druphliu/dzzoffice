<?php
/* @authorcode  c847417817641cfe67af4008fac750a0
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
 
function getAdminPerm($moderators){//检查用户是否是管理员
	global $_G;
	if($_G['uid']<1) return 0;
	if($_G['adminid']==1) return 3;
	$muids=$moderators?explode(',',$moderators):array();
	if(!$muids) return 0;
	//转换为数组
	$orgids=array();
	$uids=array();
	foreach($muids as $value){
		if(strpos($value,'uid_')!==false){
			$uids[]=str_replace('uid_','',$value);
		}else{
			$orgids[]=$value;
		}
	}
	if(in_array($_G['uid'],$uids)) return 1;
	
	//当未加入机构和部门在部门列表中时，单独判断;
	if(in_array('other',$orgids) && !DB::result_first("SELECT COUNT(*) from %t where uid=%d and orgid>0",array('organization_user',$_G['uid']))){ 
		 return 1;		
	}
	//获取用户所在的机构或部门
	$uorgids=C::t('organization_user')->fetch_orgids_by_uid($_G['uid']);
	
	if(array_intersect($uorgids,$orgids)) return 1;
	
	//检查每个部门的上级
	include_once libfile('function/organization');
	foreach($uorgids as $orgid){
		$upids=getUpOrgidTree($orgid,true);
		if($upids && array_intersect($upids,$orgids)) return 1;
	}
	return 0;
}
