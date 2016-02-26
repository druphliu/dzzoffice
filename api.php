<?php
/*
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept,x-auth-token,X-AUTH-TOKEN");//解决跨域提交
header("Access-Control-Allow-Methods: POST, GET,PUT, OPTIONS, DELETE");
header("Access-Control-Max-Age:3600");

define('APPTYPEID', 0);
require './core/class/class_core.php';
$dzz = C::app();
$modarray = array('user', 'news');
$mod = !in_array($dzz->var['mod'], $modarray) && (!preg_match('/^\w+$/', $dzz->var['mod']) || !file_exists(DZZ_ROOT . './member/member_' . $dzz->var['mod'] . '.php')) ? 'space' : $dzz->var['mod'];
define('CURMODULE', $mod);
$dzz->init();
if (@!file_exists(DZZ_ROOT . './api/api_' . $mod . '.php')) {
    json_error(lang('message', 'undefined_action'));
}

require DZZ_ROOT . './api/api_' . $mod . '.php';

function json_error($t)
{
    die(json_message(false, $t));
}

function json_success($message = '', $data = array())
{
    die(json_message(true, $message, $data));
}

function json_message($status, $message, $data = array())
{
    return json_encode(array('status' => $status, 'message' => $message, 'data' => $data));
}

?>