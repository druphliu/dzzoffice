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
	$h0=array('username'=>'姓名','email'=>'邮箱','nickname'=>'用户名','birth'=>'出生日期','gender'=>'性别','mobile'=>'手机','weixinid'=>'微信号','orgname'=>'所属部门','job'=>'部门职位');
	$h1=getProfileForImport();
	$h0=array_merge($h0,$h1);
	$title='批量导入用户模板';
	$objPHPExcel = new PHPExcel();
	$objPHPExcel->getProperties()->setCreator($_G['username'])
								 ->setTitle($title.' - DzzOffice')
								 ->setSubject($title)
								 ->setDescription($title.' Export By DzzOffice  '.date('Y-m-d H:i:s'))
								 ->setKeywords($title)
								 ->setCategory($title);
	$list=array();
	// Create a first sheet
	$objPHPExcel->setActiveSheetIndex(0);
	$j=0;
	foreach($h0 as $key =>$value){
		$index=getColIndex($j).'1';
		$objPHPExcel->getActiveSheet()->setCellValue($index,$value);
		$list[1][$index]=$value;
		$j++;
	}
	
	$objPHPExcel->setActiveSheetIndex(0);
	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
	$filename=$_G['setting']['attachdir'].'./cache/'.random(5).'.xlsx';
	$objWriter->save($filename);
	
	
	$name=$title.'.xlsx';
	$name = '"'.(strtolower(CHARSET) == 'utf-8' && (strexists($_SERVER['HTTP_USER_AGENT'], 'MSIE') || strexists($_SERVER['HTTP_USER_AGENT'], 'rv:11')) ? urlencode($name) : $name).'"';
	
	$filesize=filesize($filename);
	$chunk = 10 * 1024 * 1024; 
	if(!$fp = @fopen($filename, 'rb')) {
		exit('导出失败！');
	}
	dheader('Date: '.gmdate('D, d M Y H:i:s', TIMESTAMP).' GMT');
	dheader('Last-Modified: '.gmdate('D, d M Y H:i:s', TIMESTAMP).' GMT');
	dheader('Content-Encoding: none');
	dheader('Content-Disposition: attachment; filename='.$name);
	dheader('Content-Type: application/octet-stream');
	dheader('Content-Length: '.$filesize);
	@ob_end_clean();if(getglobal('gzipcompress')) @ob_start('ob_gzhandler');
	while (!feof($fp)) { 
		echo fread($fp, $chunk);
		@ob_flush();  // flush output
		@flush();
	}
	@unlink($filename);
	exit();

function getColIndex($index){
	$string="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$ret='';
	if($index>255) return '';
	for($i=0;$i<floor($index/strlen($string));$i++){
		$ret=$string[$i];
	}
	$ret.=$string[($index%(strlen($string)))];
	return $ret;
}
function getProfileForImport(){
	global $_G;
	if(empty($_G['cache']['profilesetting'])) {
		loadcache('profilesetting');
	}
	$profilesetting=$_G['cache']['profilesetting'];
	$ret=array();
	foreach($profilesetting as $key=> $value){
		if(in_array($key,array('department','realname','gender','birthyear','birthmonth','birthday','constellation','zodiac'))) continue;
		elseif($value['formtype']=='file') continue;
		elseif($value['formtype']=='select' || $value['formtype']=='radio'){
			$ret[$key]=$value['title']/*.($value['choices']?'('.preg_replace("/[\r\n]/i",'|',$value['choices']).')':'')*/;
		}elseif( $value['formtype']=='checkbox'){
			$ret[$key]=$value['title']/*.($value['choices']?'('.preg_replace("/[\r\n]/i",'-',$value['choices']).')':'')*/;
		}else{	
			$ret[$key]=$value['title'];
		}
	}
	return $ret;
}
?>
