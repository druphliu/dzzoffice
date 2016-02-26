<?php
/**
 * Created by PhpStorm.
 * User: thinkpad
 * Date: 2016/2/15
 * Time: 21:59
 */
if(!defined('IN_DZZ')) {
    exit('Access Denied');
}
define('CURSCRIPT', 'user');
require libfile('class/user');
define('NOROBOT', TRUE);
if(!in_array($_GET['action'], array('login', 'logout'))) {
    $_GET['action']='login';
}
$ctl_obj = new logging_ctl();
$ctl_obj->setting = $_G['setting'];
$method = 'api_'.$_GET['action'];
$ctl_obj->$method();
