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


$cid=intval($_GET['cid']);
$data=array();
$operation=trim($_GET['operation']);
if($operation && $corpus['archivetime']>0){
	exit(json_encode(array('error'=>'文集已归档，无法操作！')));
}
if($operation=='rename'){
	$fid=intval($_GET['fid']);
	if($corpus['perm']<2){
		exit(json_encode(array('error'=>'没有权限')));
	}
	C::t('corpus_class')->rename_by_fid($fid,str_replace('...','',getstr($_GET['text'],80)));
	exit(json_encode(array('msg'=>'success')));
	
}elseif($operation=='move'){
	if(!$cid){
		exit(json_encode(array('error'=>'文件不存在或已删除')));
	}
	if($corpus['perm']<2){
		exit(json_encode(array('error'=>'没有权限')));
	}
	$fid=intval($_GET['fid']);
	$pfid=intval($_GET['pfid']);
	$position=intval($_GET['position']);
	C::t('corpus_class')->setDispByFid($fid,$pfid,$position);
}elseif($operation=='delete'){	
	$fid=intval($_GET['fid']);
	C::t('corpus_class')->delete_by_fid($fid);
}elseif($operation=='deleteVersion'){	
	$fid=intval($_GET['fid']);
	$version=intval($_GET['ver']);
	$class=C::t('corpus_class')->fetch($fid);
	if($corpus['perm']<2){
		exit(json_encode(array('error'=>'没有权限')));
	}elseif($corpus['perm']==2 && $_G['uid']!=$class['uid']){
		exit(json_encode(array('error'=>'没有权限')));
	}
	if($ver=C::t('document_reversion')->delete_by_version($class['did'],$version)){
		exit(json_encode(array('msg'=>'success','ver'=>$ver)));
	}else{
		exit(json_encode(array('error'=>'删除失败')));
	}
}elseif($operation=='applyVersion'){	
	$fid=intval($_GET['fid']);
	$version=intval($_GET['ver']);
	$class=C::t('corpus_class')->fetch($fid);
	if($corpus['perm']<2){
		exit(json_encode(array('error'=>'没有权限')));
	}
	if($ver=C::t('document_reversion')->reversion($class['did'],$version,$_G['uid'],$_G['username'])){
		exit(json_encode(array('msg'=>'success','ver'=>$ver)));
	}else{
		exit(json_encode(array('error'=>'使用版本失败')));
	}
}elseif($operation=='create'){
	if(!$cid){
		exit(json_encode(array('error'=>'文件不存在或已删除')));
	}
	if($corpus['perm']<2){
		exit(json_encode(array('error'=>'没有权限')));
	}
	$pfid=intval($_GET['pfid']);
	$type=trim($_GET['type']);
	
	$setarr=array(    'fname'=>$type=='file'?'新文档':'新分类',
					  'type'=>$type,
					  'cid'=>$cid,
					  'pfid'=>$pfid,
					  'uid'=>getglobal('uid'),
					  'username'=>getglobal('username'),
					  'disp'=>DB::result_first("select max(disp) from %t where pfid=%d",array('corpus_class',$pfid))+1,
					  'dateline'=>TIMESTAMP
					  );
	if($fid=C::t('corpus_class')->insert($setarr,1)){
		$data=array(
					'id'=>$fid,
					'text'=>$setarr['fname'],
					'type'=>$type
					);
		exit(json_encode($data));
	}else{
		exit(json_encode(array('error'=>'创建分类失败')));
	}
}elseif($operation=='import'){
	if(!$cid){
		exit(json_encode(array('error'=>'文件不存在或已删除')));
	}
	if($corpus['perm']<2){
		exit(json_encode(array('error'=>'没有权限')));
	}
	$pfid=intval($_GET['pfid']);
	$type=trim($_GET['type']);
	$did=intval($_GET['did']);
	$aid=intval($_GET['aid']);
	$setarr=array(    'fname'=>$_GET['name'],
					  'type'=>$type,
					  'cid'=>$cid,
					  'pfid'=>$pfid,
					  'uid'=>getglobal('uid'),
					  'username'=>getglobal('username'),
					  'disp'=>DB::result_first("select max(disp) from %t where pfid=%d",array('corpus_class',$pfid))+1,
					  'dateline'=>TIMESTAMP
					  );
	if($fid=C::t('corpus_class')->insert($setarr,1)){
		//处理文档文件
		if($did>0){//dzzdoc文档
			if($newdid=C::t('document')->copy_by_did($did,'corpus',$cid,$fid)){
				if(C::t('corpus_class')->update($fid,array('did'=>$newdid))){
					$data=array(
								'id'=>$fid,
								'text'=>$setarr['fname'],
								'type'=>$type
								);
					exit(json_encode($data));
				}else{
					C::t('document')->delete_by_did($newdid,true);
					exit(json_encode(array('error'=>'文档导入失败')));
				}
			}else{
				C::t('corpus_class')->delete_by_fid($fid,true);
				exit(json_encode(array('error'=>'文档导入失败')));
			}
		}elseif($aid>0){ //文本类文档;
			
			if(!$attach=C::t('attachment')->fetch($aid)){
				C::t('corpus_class')->delete_by_fid($fid,true);
				exit(json_encode(array('error'=>'文档导入失败')));
			}
			$path=getDzzPath($attach);
			$message=IO::getFileContent($path);
			
			require_once DZZ_ROOT.'./dzz/class/class_encode.php';
			$p=new Encode_Core();
			$code=$p->get_encoding($message);
			 if($code) $message=diconv($message,$code, CHARSET); 
			 $message=htmlspecialchars($message);
			 $message=nl2br(str_replace(array("\t", '   ', '  '), array('&nbsp; &nbsp; &nbsp; &nbsp; ', '&nbsp; &nbsp;', '&nbsp;&nbsp;'), $message));
			if(!$attach=getTxtAttachByMd5($message,$setarr['fname'].'.dzzdoc')){
				C::t('corpus_class')->delete_by_fid($fid,true);
				exit(json_encode(array('error'=>'文档导入失败')));
			}
			$setarr1=array(
						   'uid'=>$_G['uid'],
						   'username'=>$_G['username'],
						   'aid'=>$attach['aid'],
						   'fid'=>$fid,
						  );
			if(!$newdid=C::t('document')->insert($setarr1,array(),'corpus',$cid)){
				C::t('corpus_class')->delete_by_fid($fid,true);
				exit(json_encode(array('error'=>'文档导入失败')));
			}else{
				if(C::t('corpus_class')->update($fid,array('did'=>$newdid))){
					$data=array(
								'id'=>$fid,
								'text'=>$setarr['fname'],
								'type'=>$type
								);
					exit(json_encode($data));
				}else{
					C::t('document')->delete_by_did($newdid,true);
					exit(json_encode(array('error'=>'文档导入失败')));
				}
			}
		}
	}else{
		exit(json_encode(array('error'=>'文档导入失败')));
	}
}else{
	$id=trim($_GET['id']);
	$data=array();
	foreach(C::t('corpus_class')->fetch_all_by_cid($cid) as $key => $value){
		$temp=array('id'=>$value['fid'],
					'text'=>$value['fname'],
					'parent'=>$value['pfid']?$value['pfid']:'#',
					'type'=>$value['type'],
					);
		//if($key==0) $temp['state']=array('selected'=>true);
		$data[]=$temp;
	}
	echo (json_encode($data));
	exit();
}






?>
