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
define('CURSCRIPT', 'news');

define('NOROBOT', TRUE);
if (!in_array($_GET['action'], array('category', 'list', 'view'))) {
    $_GET['action'] = 'category';
}
$action = $_GET['action'];
//判断用户访问权限
include libfile('function/organization');
include libfile('function/news');
$perm = getPermByUid($_G['uid']);
$result = true;
switch ($action) {
    case 'category':
        $message = 'success';
        $data = catList(0);
        break;
    case 'list':
        $message = 'success';
        break;
    case 'view':
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


function catList($catid = 0)
{
    foreach (C::t('news_cat')->fetch_all_by_pid($catid) as $value) {
        //每个分类下是否有未读消息

        $result[$value['catid']] = $value;
        $sun = C::t('news_cat')->getSonByCatid($value['catid']);
        if ($sun) {
            $result[$value['catid']]['sub'] = $sun;
        }
    }
    return $result;
}