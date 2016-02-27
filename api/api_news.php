<?php
/**
 * Created by PhpStorm.
 * User: thinkpad
 * Date: 2016/2/15
 * Time: 21:59
 */
if (!defined('IN_DZZ')) {
    exit('Access Denied');
}
define('CURSCRIPT', 'dzz');
define('CURMODULE', 'news');
define('NOROBOT', TRUE);
if (!in_array($_GET['action'], array('category', 'list', 'view'))) {
    $_GET['action'] = 'category';
}
if(!$_G['uid'])
    json_error('请先登录');
$action = $_GET['action'];
//判断用户访问权限
include libfile('function/organization');
include libfile('function/news', '', 'dzz/news');
$perm = getPermByUid($_G['uid']);
$result = true;
$sql="1";
if($perm<2){

    //阅读范围查询语句
    if($_G['uid']<1){
        $sql.=" and n.orgids='' and n.uids=''";
    }else{

        $sql.=" and ( n.authorid=%d OR (";
        $param[]=$_G['uid'];

        $sql_gid=array("n.orgids=''");
        if($orgarr=getDepartmentByUid($_G['uid'])){ //获取当前用户所在的部门数组
            foreach($orgarr as $value){
                foreach($value as $value1){
                    $sql_gid[]="FIND_IN_SET(%d,orgids)";
                    $param[]=$value1['orgid'];
                }
            }
        }else{
            $sql_gid[]="FIND_IN_SET(%s,orgids)";
            $param[]='other';
        }

        $sql.="(".implode(' OR ',$sql_gid).") and ( n.uids='' OR FIND_IN_SET(%d,n.uids))";

        $sql.="))";
        $param[]=$_G['uid'];
    }

}
switch ($action) {
    case 'category':
        $message = 'success';
        $catid = empty($_GET['catid']) ? 0 : intval($_GET['catid']);
        $data = catList($catid,$sql,$param);
        break;
    case 'list':
        $catid = empty($_GET['catid']) ? 0 : intval($_GET['catid']);
        if($catid==0)
            json_error('分类不存在');
        $subids=C::t('news_cat')->getSonByCatid($catid);
        $sql.=' and catid IN(%n)';
        $param[]=$subids;
        $sql.=" and status='1'";
        $data = listView($sql,$param);
        $message = 'success';
        break;
    case 'view':
        $newid = empty($_GET['newid'])?0:intval($_GET['newid']);
        if(!$news=C::t('news')->fetch($newid))
            json_error('信息不存在或者已被删除');
        if(!getViewPerm($news)){
            json_error('您没有查看此信息的权限，请联系管理员');
        }
        //更新查阅
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
        $data = $news;
        $message = 'success';
        break;
    default:
        $result = false;
        $message = '不存在的方法';
        break;
}
if ($result)
    json_success($message, $data);
else
    json_error($message);


function catList($catid = 0,$sql,$param)
{
    global $_G;
    //查询
    $params = array('news');
    $params[] = 'news_viewer';
    $params[] = $_G['uid'];
    $params = array_merge_recursive($params,$param);
    foreach (C::t('news_cat')->fetch_all_by_pid($catid) as $value) {
        $catids = $common='';
        $result[$value['catid']] = $value;
        $sun = C::t('news_cat')->fetch_all_by_pid($value['catid']);

        if ($sun) {
            foreach($sun as $s){
                $catids .= $common.$s['catid'];
                $common = ',';
            }
            $result[$value['catid']]['sub'] = $sun;
        }else{
            $catids = $value['catid'];
        }
        //每个分类下未读消息个数
        $count = DB::fetch_first("select count(1) as count from %t  n LEFT JOIN %t v ON n.newid=v.newid and v.uid=%d where $sql and n.catid in ({$catids}) and v.vid is NULL ", $params);
        $result[$value['catid']]['unread_count'] = $count['count'];
    }
    return $result;
}

function listView($sql,$param){
    global $_G;
    //查询
    $orderby="ORDER BY n.istop DESC , n.dateline DESC";
    $perpage=20;
    $page = empty($_GET['page'])?1:intval($_GET['page']);
    $start=($page-1)*$perpage;
    $params = array('news');
    $params[] = 'news_viewer';
    $params[] = $_G['uid'];
    $params = array_merge_recursive($params,$param);
    if($count=DB::result_first("select count(*) from %t n LEFT JOIN %t v ON n.newid=v.newid and v.uid=%d where $sql",$params)){
        foreach(DB::fetch_all("select n.*,v.vid as isread from %t  n LEFT JOIN %t v ON n.newid=v.newid and v.uid=%d where $sql $orderby limit $start,$perpage",$params) as $value){
            $today=strtotime(dgmdate(TIMESTAMP,'Y-m-d'));
            if($value['topendtime']<$today){
                if($value['istop']){
                    $updatearr[]=$value['newid'];
                }
                $value['istop']=0;
            }
            if($value['highlightendtime']<$today ){
                $value['ishighlight']=0;
            }
            if($value['opuid'] && $opuser=getuserbyuid($value['opuid'])){
                $value['opauthor']=$opuser['username'];
            }
            $data[]=$value;
        }
    }
    return array('list'=>$data,'perpage'=>$perpage,'page'=>$page,'count'=>$count);
}