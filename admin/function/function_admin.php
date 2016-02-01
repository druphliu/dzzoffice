<?php
/*
 * @copyright   Leyun internet Technology(Shanghai)Co.,Ltd
 * @license     http://www.dzzoffice.com/licenses/license.txt
 * @package     DzzOffice
 * @link        http://www.dzzoffice.com
 * @author      zyx(zyx@dzz.cc)
 */
if(!defined('IN_DZZ') || !defined('IN_ADMIN')) {
	exit('Access Denied');
}
function runquery($sql) {
	global $_G;
	$tablepre = $_G['config']['db'][1]['tablepre'];
	$dbcharset = $_G['config']['db'][1]['dbcharset'];

	$sql = str_replace(array(' dzz_', ' `dzz_',' cdb_', ' `cdb_', ' pre_', ' `pre_'), array(' {tablepre}', ' `{tablepre}',' {tablepre}', ' `{tablepre}', ' {tablepre}', ' `{tablepre}'), $sql);
	$sql = str_replace("\r", "\n", str_replace(array(' {tablepre}', ' `{tablepre}'), array(' '.$tablepre, ' `'.$tablepre), $sql));

	$ret = array();
	$num = 0;
	foreach(explode(";\n", trim($sql)) as $query) {
		$queries = explode("\n", trim($query));
		foreach($queries as $query) {
			$ret[$num] .= $query[0] == '#' || $query[0].$query[1] == '--' ? '' : $query;
		}
		$num++;
	}
	unset($sql);

	foreach($ret as $query) {
		$query = trim($query);
		if($query) {

			if(substr($query, 0, 12) == 'CREATE TABLE') {
				$name = preg_replace("/CREATE TABLE ([a-z0-9_]+) .*/is", "\\1", $query);
				DB::query(createtable($query, $dbcharset));

			} else {
				DB::query($query);
			}

		}
	}
}

function createtable($sql, $dbcharset) {
	$type = strtoupper(preg_replace("/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU", "\\2", $sql));
	$type = in_array($type, array('MYISAM', 'HEAP')) ? $type : 'MYISAM';
	return preg_replace("/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU", "\\1", $sql).
	(DB::$db->version() > '4.1' ? " ENGINE=$type DEFAULT CHARSET=$dbcharset" : " TYPE=$type");
}

function cron_create($pluginid, $filename, $name, $weekday, $day, $hour, $minute) {
	if(!ispluginkey($pluginid)) {
		return false;
	}
	$dir = DZZ_ROOT.'./dzz/'.$pluginid.'/cron';
	if(!file_exists($dir)) {
		return false;
	}
	$crondir = dir($dir);
	while($filename = $crondir->read()) {
		if(!in_array($filename, array('.', '..')) && preg_match("/^cron\_[\w\.]+$/", $filename)) {
			$content = file_get_contents($dir.'/'.$filename);
			preg_match("/cronname\:(.+?)\n/", $content, $r);$name =  trim($r[1]);
			preg_match("/week\:(.+?)\n/", $content, $r);$weekday = trim($r[1]) ? intval($r[1]) : -1;
			preg_match("/day\:(.+?)\n/", $content, $r);$day = trim($r[1]) ? intval($r[1]) : -1;
			preg_match("/hour\:(.+?)\n/", $content, $r);$hour = trim($r[1]) ? intval($r[1]) : -1;
			preg_match("/minute\:(.+?)\n/", $content, $r);$minute = trim($r[1]) ? trim($r[1]) : 0;
			$minutenew = explode(',', $minute);
			foreach($minutenew as $key => $val) {
				$minutenew[$key] = $val = intval($val);
				if($val < 0 || $var > 59) {
					unset($minutenew[$key]);
				}
			}
			$minutenew = array_slice(array_unique($minutenew), 0, 12);
			$minutenew = implode("\t", $minutenew);
			$filename = $pluginid.':'.$filename;
			$cronid = C::t('cron')->get_cronid_by_filename($filename);
			if(!$cronid) {
				C::t('cron')->insert(array(
					'available' => 1,
					'type' => 'app',
					'name' => $name,
					'filename' => $filename,
					'weekday' => $weekday,
					'day' => $day,
					'hour' => $hour,
					'minute' => $minutenew,
				), true);
			} else {
				C::t('cron')->update($cronid, array(
					'name' => $name,
					'weekday' => $weekday,
					'day' => $day,
					'hour' => $hour,
					'minute' => $minutenew,
				));
				
			}
		}
	}
}

function cron_delete($pluginid) {
	if(!ispluginkey($pluginid)) {
		return false;
	}
	$dir = DZZ_ROOT.'./dzz/'.$pluginid.'/cron';
	if(!file_exists($dir)) {
		return false;
	}
	$crondir = dir($dir);
	$count = 0;
	while($filename = $crondir->read()) {
		if(!in_array($filename, array('.', '..')) && preg_match("/^cron\_[\w\.]+$/", $filename)) {
			$filename = $pluginid.':'.$filename;
			$cronid = C::t('cron')->get_cronid_by_filename($filename);
			C::t('cron')->delete($cronid);
			$count++;
		}
	}
	return $count;
}
function isplugindir($dir) {
	return preg_match("/^[a-z]+[a-z0-9_]*\/$/", $dir);
}

function ispluginkey($key) {
	return preg_match("/^[a-z]+[a-z0-9_]*$/i", $key);
}

function dir_writeable($dir) {
	if(!is_dir($dir)) {
		@mkdir($dir, 0777);
	}
	if(is_dir($dir)) {
		if($fp = @fopen("$dir/test.txt", 'w')) {
			@fclose($fp);
			@unlink("$dir/test.txt");
			$writeable = 1;
		} else {
			$writeable = 0;
		}
	}
	return $writeable;
}
function getimportdata($name = '', $addslashes = 0, $ignoreerror = 0,$data='') {
	global $_G;
	if(empty($data)){
		if($_GET['importtype'] == 'file') {
			$data = @implode('', file($_FILES['importfile']['tmp_name']));
			@unlink($_FILES['importfile']['tmp_name']);
		} else {
			if(!empty($_GET['importtxt'])) {
				$data = $_GET['importtxt'];
			} else {
				$data = $GLOBALS['importtxt'];
			}
		}
	}
	require_once libfile('class/xml');
	$xmldata = xml2array($data);
	if(!is_array($xmldata) || !$xmldata) {
		if(!$ignoreerror) {
			showmessage('数据导入错误',dreferer());
		} else {
			return array();
		}
	} else {
		if($name && $name != $xmldata['Title']) {
			if(!$ignoreerror) {
				showmessage('数据类型错误，只能导入应用数据');
			} else {
				return array();
			}
		}
		$data = exportarray($xmldata['Data'], 0);
	}
	if($addslashes) {
		$data = daddslashes($data, 1);
	}
	return $data;
}

function exportdata($name, $filename, $data) {
	global $_G;
	require_once libfile('class/xml');
	$root = array(
		'Title' => $name,
		'Version' => $_G['setting']['version'],
		'Time' => dgmdate(TIMESTAMP, 'Y-m-d H:i'),
		'From' => $_G['setting']['bbname'].' ('.$_G['siteurl'].')',
		'Data' => exportarray($data, 1)
	);
	$filename = strtolower(str_replace(array('!', ' '), array('', '_'), $name)).'_'.$filename.'.xml';
	$plugin_export = array2xml($root, 1);
	ob_end_clean();
	dheader('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	dheader('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	dheader('Cache-Control: no-cache, must-revalidate');
	dheader('Pragma: no-cache');
	dheader('Content-Encoding: none');
	dheader('Content-Length: '.strlen($plugin_export));
	dheader('Content-Disposition: attachment; filename='.$filename);
	dheader('Content-Type: text/xml');
	echo $plugin_export;
	define('FOOTERDISABLED' , 1);
	exit();
}
function exportarray($array, $method) {
	$tmp = $array;
	if($method) {
		foreach($array as $k => $v) {
			if(is_array($v)) {
				$tmp[$k] = exportarray($v, 1);
			} else {
				$uv = unserialize($v);
				if($uv && is_array($uv)) {
					$tmp['__'.$k] = exportarray($uv, 1);
					unset($tmp[$k]);
				} else {
					$tmp[$k] = $v;
				}
			}
		}
	} else {
		foreach($array as $k => $v) {
			if(is_array($v)) {
				if(substr($k, 0, 2) == '__') {
					$tmp[substr($k, 2)] = serialize(exportarray($v, 0));
					unset($tmp[$k]);
				} else {
					$tmp[$k] = exportarray($v, 0);
				}
			} else {
				$tmp[$k] = $v;
			}
		}
	}
	return $tmp;
}
function imagetobase64($file){
	$type=getimagesize($file);//取得图片的大小，类型等  
	$fp=fopen($file,"r")or die("Can't open file");  
	$file_content=chunk_split(base64_encode(fread($fp,filesize($file))));//base64编码  
	switch($type[2]){//判读图片类型  
	case 1:$img_type="gif";break;  
	case 2:$img_type="jpg";break;  
	case 3:$img_type="png";break;  
	}  
	$img='data:image/'.$img_type.';base64,'.$file_content;//合成图片的base64编码  
	fclose($fp);
	return $img;
}
function base64toimage($data,$dir='appimg',$target=''){
	global $_G;
	$dataarr=explode(',',$data);
	$imgcontent=base64_decode($dataarr[1]);
	$imgext=str_replace(array('data:image/',';base64'),'',$dataarr[0]);
	if(!$target) {
		$imageext=array('jpg','jpeg','png','gif');
		if(!in_array($imgext,$imageext)) $ext='jpg';
		$subdir = $subdir1 = $subdir2 = '';
		$subdir1 = date('Ym');
		$subdir2 = date('d');
		$subdir = $subdir1.'/'.$subdir2.'/';
		$target1=$_G['setting']['attachdir'].$dir.'/'.$subdir.''.date('His').''.strtolower(random(16)).'.'.$imgext;
		$target=str_replace($_G['setting']['attachdir'],'',$target1);
	}else{
		$target1=$_G['setting']['attachdir'].$target;
	}
	$targetpath = dirname($target1);
	dmkdir($targetpath);
	if(file_put_contents($target1, $imgcontent)){
		if(@filesize($target1)<200) {
			@unlink($target1);
			return false;
		}
		return $target;
	}else return false;
}
function importByarray($arr,$force=0){
	$app=$arr['app'];
	//判断应用是否已经存在
	$oapp=DB::fetch_first("select * from %t where appurl=%s",array('app_market',$app['appurl'],$app['identifier']));
	if(!$force && $oapp){
		showmessage('应用已经存在！');
	}
	
	//转化应用图标
	if($app['appico']){
		$app['appico']=base64toimage($app['appico']);
	}
	
	$app['extra']=serialize($app['extra']);
	
	if($oapp){
		$appid=$oapp['appid'];
		C::t('app_market')->update($appid,$app);
	}else{
		$app['available']=0;
		$appid=$app['appid']=C::t('app_market')->insert($app,1);
	}
	if($appid){
		C::t('app_open')->insert_by_exts($appid,($app['fileext']?explode(',',$app['fileext']):array()));
		C::t('app_tag')->addtags(($app['tag']?explode(',',$app['tag']):array()),$appid);
	}
	return $app;
}
function upgradeinformation($status = 0) {
	global $_G, $upgrade_step;

	if(empty($upgrade_step)) {
		return '';
	}
    if($status==1 && $upgrade_step['step']==2) return '';
	$update = array();
	$siteuniqueid = C::t('setting')->fetch('siteuniqueid');
    $update['siteurl']=$_G['siteurl'];
	$update['sitename']=$_G['setting']['sitename'];
	$update['uniqueid'] = $siteuniqueid;
	$update['curversion'] = $upgrade_step['curversion'];
	$update['currelease'] = $upgrade_step['currelease'];
	$update['upgradeversion'] = $upgrade_step['version'];
	$update['upgraderelease'] = $upgrade_step['release'];
	$update['step'] = $upgrade_step['step'] == 'dbupdate' ? 4 : $upgrade_step['step'];
	$update['status'] = $status;

	$data = '';
	foreach($update as $key => $value) {
		$data .= $key.'='.rawurlencode($value).'&';
	}
	$upgradeurl =  'ht'.'tp:/'.'/dev'.'.'.'d'.'zzo'.'ffice.'.'c'.'om/upg'.'rade'.'.p'.'hp?'.'os=d'.'zzoff'.'ice&update='.rawurlencode(base64_encode($data)).'&timestamp='.TIMESTAMP;
	return '<img src="'.$upgradeurl.'" width="0" height="0" />';
}
function getwheres($intkeys, $strkeys, $randkeys, $likekeys, $pre='') {

	$wherearr = array();
	$urls = array();

	foreach ($intkeys as $var) {
		$value = isset($_GET[$var])?$_GET[$var]:'';
		if(strlen($value)) {
			$urls[] = "$var=$value";
			$var = addslashes($var);
			$wherearr[] = "{$pre}{$var}='".intval($value)."'";
		}
	}

	foreach ($strkeys as $var) {
		$value = isset($_GET[$var])?trim($_GET[$var]):'';
		if(strlen($value)) {
			$urls[] = "$var=".rawurlencode($value);
			$var = addslashes($var);
			$value = addslashes($value);
			$wherearr[] = "{$pre}{$var}='$value'";
		}
	}

	foreach ($randkeys as $vars) {
		$value1 = isset($_GET[$vars[1].'1'])?$vars[0]($_GET[$vars[1].'1']):'';
		$value2 = isset($_GET[$vars[1].'2'])?$vars[0]($_GET[$vars[1].'2']):'';
		if($value1) {
			$urls[] = "{$vars[1]}1=".rawurlencode($_GET[$vars[1].'1']);
			$vars[1] = addslashes($vars[1]);
			$value1 = addslashes($value1);
			$wherearr[] = "{$pre}{$vars[1]}>='$value1'";
		}
		if($value2) {
			$wherearr[] = "{$pre}{$vars[1]}<='$value2'";
			$vars[2] = addslashes($vars[2]);
			$value2 = addslashes($value2);
			$urls[] = "{$vars[1]}2=".rawurlencode($_GET[$vars[1].'2']);
		}
	}

	foreach ($likekeys as $var) {
		$value = isset($_GET[$var])?stripsearchkey($_GET[$var]):'';
		if(strlen($value)>1) {
			$urls[] = "$var=".rawurlencode($_GET[$var]);
			$var = addslashes($var);
			$value = addslashes($value);
			$wherearr[] = "{$pre}{$var} LIKE BINARY '%$value%'";
		}
	}

	return array('wherearr'=>$wherearr, 'urls'=>$urls);
}

function getorders($alloworders, $default, $pre='') {
	$orders = array('sql'=>'', 'urls'=>array());
	if(empty($_GET['orderby']) || !in_array($_GET['orderby'], $alloworders)) {
		$_GET['orderby'] = $default;
		if(empty($_GET['ordersc'])) $_GET['ordersc'] = 'desc';
	}

	$orders['sql'] = " ORDER BY {$pre}$_GET[orderby] ";
	$orders['urls'][] = "orderby=$_GET[orderby]";

	if(!empty($_GET['ordersc']) && $_GET['ordersc'] == 'desc') {
		$orders['urls'][] = 'ordersc=desc';
		$orders['sql'] .= ' DESC ';
	} else {
		$orders['urls'][] = 'ordersc=asc';
	}
	return $orders;
}

?>