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
class table_corpus_class extends dzz_table
{
	public function __construct() {

		$this->_table = 'corpus_class';
		$this->_pk    = 'fid';

		parent::__construct();
	}
	public function rename_by_fid($fid,$name){
		$data=parent::fetch($fid);
		
		if($return=parent::update($fid,array('fname'=>$name))){
		//产生事件
			$event =array(    'uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  
							  'body_template'=>'corpus_rename_'.($data['type']=='file'?'doc':'dir'),
							  'body_data'=>serialize(array('cid'=>$data['cid'],'ofname'=>$data['fname'],'fid'=>$fid,'fname'=>$name)),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$data['cid'],
							  );
				C::t('corpus_event')->insert($event);
			return $return;
		}
	}
	public function insert_default_by_cid($cid){
		$setarr=array('fname'=>'新文档',
					  'cid'=>$cid,
					  'pfid'=>0,
					  'type'=>'file',
					  'uid'=>getglobal('uid'),
					  'username'=>getglobal('username'),
					  'disp'=>0,
					  'dateline'=>TIMESTAMP
					  );
		return self::insert($setarr);
	}
	public function insert($setarr){
		if($fid=parent::insert($setarr,1)){
			
			//产生事件
			$event =array(    'uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							
							  'body_template'=>'corpus_create_'.($setarr['type']=='file'?'doc':'dir'),
							  'body_data'=>serialize(array('cid'=>$setarr['cid'],'fid'=>$fid,'fname'=>$setarr['fname'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$setarr['cid'],
							  );
				C::t('corpus_event')->insert($event);
		}
		return $fid;
	}
	public function fetch_all_by_deletetime($cid,$limit,$keyword,$iscount){
		$limitsql='';
		if($limit){
			$limit=explode('-',$limit);
			if(count($limit)>1){
				$limitsql.=" limit ".intval($limit[0]).",".intval($limit[1]);
			}else{
				$limitsql.=" limit ".intval($limit[0]);
			}
		}
		$parameter=array($this->_table,$cid);
		$ssql='';
		if(!empty($keyword)) {
			$parameter[] = '%'.$keyword.'%';
			$ssql= " and fname LIKE %s";
		}
		if($iscount) return DB::result_first("select COUNT(*) from %t where cid=%d and deletetime>0 $ssql ",$parameter);
		$data=array();
		$deleteuids=array();
		foreach(DB::fetch_all("select * from %t where cid=%d and deletetime>0 $ssql order by deletetime DESC $limitsql ",$parameter) as $value){
			$deleteuids[$value['fid']]=$value['deleteuid'];
			$data[$value['fid']]=$value;
		}
		$user=C::t('user')->fetch_all($deleteuids);
		foreach($deleteuids as $key =>$uid){
			if($data[$key])$data[$key]['deleteusername']=$user[$uid]['username'];
		}
		return $data;
	}
	public function fetch_all_by_pfid($cid,$pfid){
		return DB::fetch_all("select * from %t where cid=%d and pfid=%d  and deletetime<1 order by disp",array($this->_table,$cid,$pfid));
	}
	private function getTreeData($cid,$fid=0){
		static $data=array();
		foreach(self::fetch_all_by_pfid($cid,$fid) as $value){
			$data[]=$value;
			self::getTreeData($cid,$value['fid']);
		}
		return $data;
	}
	public function fetch_all_by_cid($cid){
		return self::getTreeData($cid);
	}
	public function setDispByFid($fid,$pfid,$position){
		$oclass=self::fetch($fid);
		$list=array();
		foreach(self::fetch_all_by_pfid($oclass['cid'],$pfid) as $key=> $value){
			
			if($value['disp']>=$positon) parent::update($value['fid'],array('disp'=>$key+1));
			else{
				 parent::update($value['fid'],array('disp'=>$key));
			}
		}
		foreach(self::fetch_all_by_pfid($oclass['cid'],$pfid) as $key=> $value){
			
			if($value['disp']>=$positon) parent::update($value['fid'],array('disp'=>$key+1));
			else{
				 parent::update($value['fid'],array('disp'=>$key));
			}
		}
		return parent::update($fid,array('pfid'=>$pfid,'disp'=>$position));
	}
	public function delete_by_fid($fid,$force=false){
		$class=parent::fetch($fid);
		
		if($force){
			return self::delete_permanent_by_pfid($fid);
		}elseif($class['deletetime']>0){
			return self::delete_permanent_by_pfid($fid);
		}else{
			if($class['pfid']<1 && !DB::result_first("select COUNT(*) from %t where cid=%d and fid!=%d",array($this->_table,$class['cid'],$class['fid']))){
				return false;
			}
			$ret=parent::update($fid,array('deletetime'=>TIMESTAMP,'deleteuid'=>getglobal('uid')));
			//产生事件
			$event =array(    'uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							 
							  'body_template'=>'corpus_delete_'.($class['type']=='file'?'doc':'dir'),
							  'body_data'=>serialize(array('cid'=>$class['cid'],'fid'=>$fid,'fname'=>$class['fname'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$class['cid'],
						  );
			C::t('corpus_event')->insert($event);
			//通知文档原作者
			$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=corpus',1);
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
				
					$action='corpus_doc_delete';
					$type='corpus_doc_delete_'.$class['cid'];
				
				dzz_notification::notification_add($class['uid'], $type, $action, $notevars, 0,'dzz/corpus');
			}
			return $ret;
		}
	}
	public function recylce_empty_by_cid($cid){
		foreach(self::fetch_all_by_deletetime($cid) as $value){
			self::delete_by_fid($value['fid']);
		}
		return true;
	}
	public function restore_by_fid($fid){
		
		$fids=self::getParentByFid($fid);
		if($fids && $return=parent::update($fids,array('deletetime'=>0,'deleteuid'=>0))){
			//产生事件
			foreach($fids as $value){
				$class=parent::fetch($value);
				$event =array(    'uid'=>getglobal('uid'),
								  'username'=>getglobal('username'),
								  
								  'body_template'=>'corpus_restore_'.($class['type']=='file'?'doc':'dir'),
								  'body_data'=>serialize(array('cid'=>$class['cid'],'fid'=>$class['fid'],'fname'=>$class['fname'])),
								  'dateline'=>TIMESTAMP,
								  'bz'=>'corpus_'.$class['cid'],
							  );
					C::t('corpus_event')->insert($event);
			}
		}
		return $return;
	}
	public function delete_permanent_by_pfid($pfid){
		
		if(!$class=parent::fetch($pfid)) return false;
		foreach(self::fetch_all_by_pfid($pfid,$class['cid']) as $value){
			self::delete_permanent_by_pfid($value['fid'],$class['cid']);
		}
		if($class['did']){
			C::t('document')->delete_by_did($class['did'],true);
		}
		//删除评论
		 C::t('comment')->delete_by_id_idtype($class['fid'],'corpus');
		return parent::delete($pfid);
	}
	public function getParentByFid($fid){
		static $fids=array();
		if($class=parent::fetch($fid)){
			$fids[]=$fid;
			if($class['pfid']>0){
				self::getParentByFid($class['pfid']);
			}
		}
		return $fids;
	}
}

?>
