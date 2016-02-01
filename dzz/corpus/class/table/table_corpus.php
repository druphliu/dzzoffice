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
class table_corpus extends dzz_table
{
	public function __construct() {

		$this->_table = 'corpus';
		$this->_pk    = 'cid';

		parent::__construct();
	}
	public function checkMaxCorpus($uid){
		$maxboard=C::t('corpus_setting')->fetch('maxcorpus');
		if($maxboard==0){
			return '无限制';
		}else{
			$sum=DB::result_first("select COUNT(*) from %t where uid=%d and deletetime<1",array($this->_table,$uid));
			if($sum<$maxboard) return $maxboard-$sum;
		}
		 return false;
	}
	public function fetch_by_cid($cid,$uid){
		$data=array();
		if($data=parent::fetch($cid)){
			$data['viewperm']=$data['perm'];//文集的查看权限使用viewperm；
			if($uid>0) $data['perm']=C::t('corpus_user')->fetch_perm_by_uid($uid,$cid);
			else $data['perm']=0;
		}
		return $data;
	}
	public function archive_by_cid($cid){
		
		$setarr=array('deletetime'=>0,
					  'archivetime'=>TIMESTAMP,
					  'archiveuid'=>getglobal('uid')
					  );
		if($return =parent::update($cid,$setarr)){
			self::setArchiveMonthTree();
			//产生事件
			$corpus=parent::fetch($cid);
			$event =array(    'uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  
							  'body_template'=>'corpus_archive',
							  'body_data'=>serialize(array('cid'=>$corpus['cid'],'corpusname'=>$corpus['name'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
							  );
				C::t('corpus_event')->insert($event);
			
			//通知文集所有参与者
				$users=C::t('corpus_user')->fetch_all_by_perm($cid,array('2','3'));
				$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=corpus',1);
				foreach($users as $value){
					if($value['uid']!=getglobal('uid')){
						//发送通知
						$notevars=array(
										'from_id'=>$appid,
										'from_idtype'=>'app',
										'url'=>DZZSCRIPT.'?mod=corpus&op=list&cid='.$corpus['cid'],
										'author'=>getglobal('username'),
										'authorid'=>getglobal('uid'),
										'dataline'=>dgmdate(TIMESTAMP),
										'corpusname'=>getstr($corpus['name'],30),
										
										);
						
							$action='corpus_archived';
							$type='corpus_archived_'.$cid;
						
						dzz_notification::notification_add($value['uid'], $type, $action, $notevars, 0,'dzz/corpus');
					}
				}
		}
		return $return;
	}
	public function restore_by_cid($cid){
		//删除的文集也可以归档，归档后删除属性清除
		$data=parent::fetch($cid);
		$setarr=array('deletetime'=>0,
					  'deleteuid'=>0,
					  'archivetime'=>0,
					  'archiveuid'=>0
					  );
		if($return=parent::update($cid,$setarr)){
			//产生事件
			if($data['archivetime']>0){
				self::setArchiveMonthTree($data['archivetime']);
			}
			$event =array(    'uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							 
							  'body_template'=>'corpus_restore',
							  'body_data'=>serialize(array('cid'=>$data['cid'],'corpusname'=>$data['name'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$cid,
							  );
				C::t('corpus_event')->insert($event);
			
			//通知文集所有参与者
				$users=C::t('corpus_user')->fetch_all_by_perm($cid,array('2','3'));
				$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=corpus',1);
				foreach($users as $value){
					if($value['uid']!=getglobal('uid')){
						//发送通知
						$notevars=array(
										'from_id'=>$appid,
										'from_idtype'=>'app',
										'url'=>DZZSCRIPT.'?mod=corpus&op=list&cid='.$data['cid'],
										'author'=>getglobal('username'),
										'authorid'=>getglobal('uid'),
										'dataline'=>dgmdate(TIMESTAMP),
										'corpusname'=>getstr($data['name'],30),
										
										);
						
							$action='corpus_restore';
							$type='corpus_restore_'.$cid;
						
						dzz_notification::notification_add($value['uid'], $type, $action, $notevars, 0,'dzz/corpus');
					}
				}
		}
		return $return;
	}
	public function delete_by_cid($cid,$force=false){//删除文集；
		//删除文集
		$data=parent::fetch($cid);
		if($force || $data['deletetime']>0){
			return self::delete_permanent_by_cid($cid);
		}else{
			$setarr=array('archivetime'=>0,
						  'deletetime'=>TIMESTAMP,
						  'deleteuid'=>getglobal('uid')
						  );
			if($return =parent::update($cid,$setarr)){
				//产生事件
				if($data['archivetime']>0){
					self::setArchiveMonthTree($data['archivetime']);
				}
				$event =array(    'uid'=>getglobal('uid'),
								  'username'=>getglobal('username'),
								  
								  'body_template'=>'corpus_delete',
								  'body_data'=>serialize(array('cid'=>$cid,'corpusname'=>$data['name'])),
								  'dateline'=>TIMESTAMP,
								  'bz'=>'corpus_'.$cid,
								  );
					C::t('corpus_event')->insert($event);
			}
			return $return;
		}
	}
	public function delete_permanent_by_cid($cid){
		$data=parent::fetch($cid);
		
		//删除文集用户
		DB::query("delete  from %t where cid=%d",array('corpus_user',$cid));
		//删除文集相关事件
		DB::query("delete  from %t where bz=%s",array('corpus_event','corpus_'.$cid));
		//删除文集内容
		foreach(DB::fetch_all("select fid from %t where cid=%d ",array('corpus_class',$cid)) as $value){
			C::t('corpus_class')->delete_by_fid($value['fid'],true);
		}
		//删除文集封面
		$aids=C::t('corpus_setting')->getCoverAids();
		if(!in_array($data['aid'],$aids)){//用户自定义的封面删除
			C::t('attachment')->delete_by_aid($data['aid']);
		}
		if($return=parent::delete($cid)){
			if($data['archivetime']>0){
				self::setArchiveMonthTree($data['archivetime']);
			}
		}
		return $return;
	}
	public function insert_by_cid($arr){
		if($cid=parent::insert($arr,1)){
			C::t('attachment')->addcopy_by_aid($arr['aid']);//封面copys+1
			C::t('corpus_class')->insert_default_by_cid($cid);//创建默认分类
			$userarr=array('uid'=>$arr['uid'],
						   'username'=>$arr['username'],
						   'perm'=>3,//管理员
						   'dateline'=>TIMESTAMP,
						   'cid'=>$cid,
						   );
		    C::t('corpus_user')->insert($userarr);//创建用户
			//产生事件
			$event =array(    'uid'=>getglobal('uid'),
							  'username'=>getglobal('username'),
							  'body_template'=>'corpus_create',
							  'body_data'=>serialize(array('cid'=>$cid,'corpusname'=>$arr['name'])),
							  'dateline'=>TIMESTAMP,
							  'bz'=>'corpus_'.$arr['cid'],
							  );
				C::t('corpus_event')->insert($event);
		}
		return $cid;
	}
	public function update_by_cid($cid,$arr){
		$data=parent::fetch($cid);
		if($return=parent::update($cid,$arr)){
			if($arr['aid']!=$data['aid']){
				C::t('attachment')->addcopy_by_aid($arr['aid']);
				$aids=C::t('corpus_setting')->getCoverAids();
				if(!in_array($data['aid'],$aids)){//用户自定义的封面删除
					C::t('attachment')->delete_by_aid($data['aid']);
				}
			}
		}
		return $return;
	}
	
	public function getMyCorpus($uid,$keyword){
		$users=C::t('corpus_user')->fetch_all_by_uid($uid);
		$cids=array();
		foreach($users as $value){
			$cids[$value['cid']]=$value['cid'];
		}
			$temp=$data=array();
			$param=array($this->_table,$cids);
			
			$searchsql='';
			if(!empty($keyword)){
				$param[]='%'.$keyword.'%';
				$param[]=$keyword;
				$searchsql=' and ( name like %s or username=%s )';
			}
			
			//归档的在归档里列
			$searchsql.=' and archivetime<1';
			//删除的也不列出来
			$searchsql.=' and deletetime<1';
			$forceindex=array();
			foreach(DB::fetch_all("select * from %t where cid IN(%n) $searchsql order by dateline DESC",$param,'cid') as $value){
				if($value['aid']) $value['img']=C::t('attachment')->getThumbByAid($value['aid'],171,225);
				if($value['forceindex']) $forceindex[]=$value['cid'];
				$data[$value['cid']]=$value;
			}
			foreach(self::fetch_all_for_manage(0,'',0,0,1) as $value){
				if(!in_array($value['cid'],$forceindex)){
					if($value['perm']>0) continue; //隐私的不列出
					if($value['aid']) $value['img']=C::t('attachment')->getThumbByAid($value['aid'],171,225);
					$data[$value['cid']]=$value;
				}
			}
			$paixu=C::t('corpus_setting')->fetch('paixu_'.$uid);
			if($paixu) $paixu=explode(',',$paixu);
			foreach($paixu as $cid){
				if(isset($data[$cid])){
					 $temp[]=$data[$cid];
					 unset($data[$cid]);
				}
			}
			return array_merge($data,$temp);
	}
	public function getOpenedCorpus($limit,$keyword,$iscount){
		$param=array($this->_table);
		$searchsql='';
		if(!empty($keyword)){
			$param[]='%'.$keyword.'%';
			$param[]=$keyword;
			$searchsql=' and ( name like %s or username=%s )';
		}
		if($iscount){
			return DB::result_first("select COUNT(*) from %t where  perm<1 and  archivetime<1  $searchsql ",$param);
		}
		$limitsql='';
		if($limit){
			$limit=explode('-',$limit);
			if(count($limit)>1){
				$limitsql.=" limit ".intval($limit[0]).",".intval($limit[1]);
			}else{
				$limitsql.=" limit ".intval($limit[0]);
			}
		}
		foreach(DB::fetch_all("select * from %t where perm<1 and  archivetime<1 and deletetime<1  $searchsql order by forceindex DESC,dateline DESC $limitsql",$param,'cid') as $value){
			if($value['aid']) $value['img']=C::t('attachment')->getThumbByAid($value['aid'],171,225);
			$data[$value['cid']]=$value;
		}
		return $data;
	}
	public function getArchivedCorpus($month=0,$keyword='',$iscount=false){
		$param=array($this->_table);
		$searchsql='';
		if(!empty($keyword)){
			$param[]='%'.$keyword.'%';
			$param[]=$keyword;
			$searchsql=' and ( name like %s or username=%s )';
		}
		if(!empty($month)){
			$dateline_low=strtotime($month);
			$dateline_up=strtotime('+1 month',strtotime($month));
			$param[]=$dateline_low;
			$param[]=$dateline_up;
			$searchsql=' and archivetime>=%d and archivetime<=%d';
		}else{
			$searchsql=' and archivetime>0';
		}
		//删除的不列出来
		$searchsql.=' and deletetime<1';
		if($iscount){
			return DB::result_first("select COUNT(*) from %t where 1 $searchsql ",$param);
		}
		foreach(DB::fetch_all("select * from %t where 1  $searchsql order by dateline DESC",$param,'cid') as $value){
			if($value['aid']) $value['img']=C::t('attachment')->getThumbByAid($value['aid'],171,225);
			$value['userperm']=C::t('corpus_user')->fetch_perm_by_uid(getglobal('uid'),$value['cid']);
			$data[$value['cid']]=$value;
		}
		return $data;
	}
	public function fetch_all_for_manage($limit,$keyword,$delete,$archive,$forceindex,$count){
		$param=array($this->_table);
		$searchsql='';
		if(!empty($keyword)){
			$param[]='%'.$keyword.'%';
			$param[]='%'.$keyword.'%';
			$searchsql=' and ( name like %s or username=%s )';
		}
		if(!empty($forceindex)){
			$searchsql.=' and forceindex>0';
		}
		if(!empty($delete)){
			$searchsql.=' and deletetime>0';
		}else{
			$searchsql.=' and deletetime<1';
		}
		if(!empty($archive)){
			$searchsql.=' and archivetime>0';
		}else{
			$searchsql.=' and archivetime<1';
		}
		if($count){
			return DB::result_first("select COUNT(*) from %t where 1 $searchsql ",$param);
		}
		$limitsql='';
		if($limit){
			$limit=explode('-',$limit);
			if(count($limit)>1){
				$limitsql.=" limit ".intval($limit[0]).",".intval($limit[1]);
			}else{
				$limitsql.=" limit ".intval($limit[0]);
			}
		}
		foreach(DB::fetch_all("select * from %t where 1 $searchsql order by dateline DESC $limitsql",$param,'cid') as $value){
			if($value['aid']) $value['img']=C::t('attachment')->getThumbByAid($value['aid'],171,225);
			$data[$value['cid']]=$value;
		}
		return $data;
	}
	public function update_count_by_cid($cid){
		$arr=array();
		$arr['documents']=DB::result_first("select COUNT(*) from %t where cid=%d and type='file' and deletetime<1 ",array('corpus_class',$cid));
		$arr['follows']=DB::result_first("select COUNT(*) from %t where cid=%d and perm=1",array('corpus_user',$cid));
		$arr['members']=DB::result_first("select COUNT(*) from %t where cid=%d and perm>1",array('corpus_user',$cid));
		parent::update($cid,$arr);
		return $arr;
	}
	public function setArchiveMonthTree($dateline){
		if(!$dateline) $dateline=time();
		if(!$archivetree=C::t('corpus_setting')->fetch('archivetree',true)){
			$archivetree=array();
		}
		$montharr=getdate($dateline);
		$monkey=$montharr['year'].'-'.$montharr['mon'];
		
		if($sum=self::getArchivedCorpus($monthkey,'',true)){
			$archivetree[$monkey]=$sum;
		}else{
			if(isset($archivetree[$monkey])) unset($archivetree[$monkey]);
		}
		krsort($archivetree);
		C::t('corpus_setting')->update('archivetree',$archivetree);
	}
	public function resetArchiveMonthTree(){
		$tree=array();
		foreach(self::getArchivedCorpus() as $value){
			$key=dgmdate($value['archivetime'],'Y-m');
			if($tree[$key]) $tree[$key]+=1;
			else{
				$tree[$key]=1;
			}
		}
		krsort($tree);
		C::t('corpus_setting')->update('archivetree',$tree);
	}
	
	 //评论回调函数
	 public function callback_by_comment($comment,$action='add',$ats=array()){
		 $fid=$comment['id'];
		 $class=C::t('#corpus#corpus_class')->fetch($fid);
		$replyaction='';
		$rpost=array();
			if($comment['rcid']>0){
				$rpost=C::t('comment')->fetch($comment['rcid']);
				$replyaction='_reply';
			}elseif($comment['pcid']>0){
				$rpost=C::t('comment')->fetch($comment['pcid']);
				$replyaction='_reply';
			}
		 //产生事件 
		 $event =array('uid'=>$comment['authorid'],
					  'username'=>$comment['author'],
					  'body_template'=>'corpus_commit_doc_'.$action.$replyaction,
					  'body_data'=>serialize(array('author'=>$rpost['author'],'cid'=>$class['cid'],'fid'=>$fid,'fname'=>$class['fname'],'comment'=>$comment['message'])),
					  'dateline'=>TIMESTAMP,
					  'bz'=>'corpus_'.$class['cid'],
					  );
					  
		C::t('#corpus#corpus_event')->insert($event);
		$appid=C::t('app_market')->fetch_appid_by_mod('{dzzscript}?mod=corpus',1);
		if($action=='add'&& $ats){//如果评论中@用户时，给用户发送通知
			foreach($ats as $uid){
				//发送通知
				if($uid!=getglobal('uid')){
					//发送通知
					$notevars=array(
									'from_id'=>$appid,
									'from_idtype'=>'app',
									'url'=>DZZSCRIPT.'?mod=corpus&op=list&cid='.$class['cid'].'&fid='.$class['fid'],
									'author'=>getglobal('username'),
									'authorid'=>getglobal('uid'),
									'dataline'=>dgmdate(TIMESTAMP),
									'fname'=>getstr($class['fname'],30),
									'comment'=>$comment['message'],
									);
					
					dzz_notification::notification_add($uid, 'corpus_comment_at_'.$class[$cid], 'corpus_comment_at', $notevars, 0,'dzz/corpus');
				}
			}
		}
		if($action=='add'){
			if($comment['pcid']==0){
				//发送通知,通知文档的作者；
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
										'comment'=>$comment['message'],
										);
						
						dzz_notification::notification_add($class['uid'], 'corpus_comment_mydoc_'.$class[$cid], 'corpus_comment_mydoc', $notevars, 0,'dzz/corpus');
				}
			}else{
				//通知原评论人	
				if($rpost['uid']!=getglobal('uid')){
						
						//发送通知
						$notevars=array(
										'from_id'=>$appid,
										'from_idtype'=>'app',
										'url'=>DZZSCRIPT.'?mod=corpus&op=list&cid='.$class['cid'].'&fid='.$class['fid'],
										'author'=>getglobal('username'),
										'authorid'=>getglobal('uid'),
										'dataline'=>dgmdate(TIMESTAMP),
										'fname'=>getstr($class['fname'],30),
										'comment'=>$comment['message'],
										);
						
						dzz_notification::notification_add($rpost['authorid'], 'corpus_comment_reply_'.$class[$cid], 'corpus_comment_reply', $notevars, 0,'dzz/corpus');
				}
			}
		}
	 }
}

?>
