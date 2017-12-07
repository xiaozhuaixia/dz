<?php

/**
 * 南京灵衍信息科技有限公司
 * User: wangxiao
 * Date: 2016/11/23
 * Time: 10:28
 */

//ini_set("display_errors", "On");
//error_reporting(E_ALL | E_STRICT);
error_reporting(0);
define('IN_MOBILE_API', 1);
define('IN_MAGMOBILE_API', 1);
define('IN_MOBILE', 1);
define('DISABLEXSSCHECK', 1); //预防formhash报错
include ('../mobile/mobile.class.php');
include ('config.php');
$_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
if(($_SERVER['HTTP_MAGDZAPISALT'] != $_MAG_CONFIG['HTTP_MAG_DZ_API_SALT']) && !in_array(strtolower($_GET['mag_fuc']),array('setuserlogin','imagethumb','qqloginconnect'))){
	exit;
}
$_SERVER['HTTP_CLIENT_IP'] = $_SERVER['HTTP_MAGCLIENTIP'] ;

if($_POST['cookiepre']){
	$_GET['cookiepre']=$_POST['cookiepre'];
}
if($_POST['auth']){
	$_GET['auth']=$_POST['auth'];
}
if($_POST['saltkey']){
	$_GET['saltkey'] = $_POST['saltkey'];
}
if($_POST['formhash']){
	$_GET['formhash']=$_POST['formhash'];
}
if($_GET['formhash']){
	$_POST['formhash'] = $_GET['formhash'];
}
$cpre =  $_GET['cookiepre'];
$_COOKIE[$cpre . 'auth'] = str_replace( ' ' , '+', $_GET['auth'] );
$_COOKIE[$cpre.'saltkey'] = $_GET['saltkey'];


chdir('../../../');
 
$magMod = $_GET['mag_mod'];
if(!in_array($magMod,array('forum','home','member','portal'))){
	exit;
}
$magFuc = $_GET['mag_fuc'];
$commonFuc = $magFuc.'Common';
$outputFuc = $magFuc.'Output';
$_GET['version'] = $_GET['version'] ?  $_GET['version'] : 'v1';
include_once 'source/plugin/magmobileapi/magmobileapi.class.php';
$controllerfile = 'source/plugin/magmobileapi/controller/'.$_GET['version'].'/'.$magMod.'.php';
if(file_exists($controllerfile)) {
	include_once $controllerfile;
} else {
	//文件不存在
	error($controllerfile.'文件不存在');
}



function success($msg,$data=array(),$code=''){
	global $_G;
	$array = array();
	$array['success'] = true;
	$array['data'] = $data;
	if(!empty($_G['messageparam']) && empty($msg)) {
		$message_result = lang('plugin/mobile', $_G['messageparam'][0], $_G['messageparam'][2]);
		$array['trace']['$message_result'] = $message_result;
		if($message_result == $_G['messageparam'][0]) {
			$vars = explode(':', $_G['messageparam'][0]);
			if (count($vars) == 2) {
				$message_result = lang('plugin/' . $vars[0], $vars[1], $_G['messageparam'][2]);
				$_G['messageparam'][0] = $vars[1];
			} else {
				$message_result = lang('message', $_G['messageparam'][0], $_G['messageparam'][2]);
			}
		}
		$message_result = strip_tags($message_result);
		$array['msg'] = $message_result;
	}else{
		$array['msg'] = $msg;
	}
	if($_G['charset'] != 'utf-8'){
		//gbk to utf8
		$array_str = var_export($array,true);
		$array = eval('return '.iconv('gbk','utf-8//IGNORE',$array_str).';');
	}
	echo json_encode($array);exit;
}



function error($msg,$data=array(),$code=''){
	global $_G;
	$array = array();

	$array['success'] = false;
	$array['code'] = $code;
	$array['data'] = $data;
	if(!empty($_G['messageparam'])) {
		$message_result = lang('plugin/mobile', $_G['messageparam'][0], $_G['messageparam'][2]);
		$array['trace']['$message_result'] = $message_result;
		if($message_result == $_G['messageparam'][0]) {
			$vars = explode(':', $_G['messageparam'][0]);
			if (count($vars) == 2) {
				$message_result = lang('plugin/' . $vars[0], $vars[1], $_G['messageparam'][2]);
				$_G['messageparam'][0] = $vars[1];
			} else {
				$message_result = lang('message', $_G['messageparam'][0], $_G['messageparam'][2]);
			}
		}
		$message_result = strip_tags($message_result);
		$array['msg'] = $message_result;
	}else{
		$array['msg'] = $msg;
	}

	if($_G['charset'] != 'utf-8'){
		//gbk格式转utf8
		$array_str = var_export($array,true);
		$array = eval('return '.iconv('gbk','utf-8//IGNORE',$array_str).';');
	}
	echo json_encode($array);exit;
}


function getvalues($variables, $keys, $subkeys = array()) {
	$return = array();
	foreach($variables as $key => $value) {
		foreach($keys as $k) {
			if($k{0} == '/' && preg_match($k, $key) || $key == $k) {
				if($subkeys) {
					$return[$key] = getvalues($value, $subkeys);
				} else {
					if(!empty($value) || !empty($_GET['debug']) || (is_numeric($value) && intval($value) === 0 )) {
						$return[$key] = is_array($value) ? arraystring($value) : (string)$value;
					}
				}
			}
		}
	}
	return $return;
}

function arraystring($array) {
	foreach($array as $k => $v) {
		$array[$k] = is_array($v) ? arraystring($v) : (string)$v;
	}
	return $array;
}

function getConfig(){
	return include ('source/plugin/magmobileapi/config.php');
}

function getMsg(){
	global $_G;
	if(!empty($_G['messageparam'])) {
		$message_result = lang('plugin/mobile', $_G['messageparam'][0], $_G['messageparam'][2]);
		if($message_result == $_G['messageparam'][0]) {
			$vars = explode(':', $_G['messageparam'][0]);
			if (count($vars) == 2) {
				$message_result = lang('plugin/' . $vars[0], $vars[1], $_G['messageparam'][2]);
				$_G['messageparam'][0] = $vars[1];
			} else {
				$message_result = lang('message', $_G['messageparam'][0], $_G['messageparam'][2]);
			}
		}
		return $message_result = strip_tags($message_result);

		if(defined('IS_WEBVIEW') && IS_WEBVIEW && in_array('mobileoem', $_G['setting']['plugins']['available'])) {
			include_once DISCUZ_ROOT.'./source/plugin/mobileoem/discuzcode.func.php';
			include mobileoem_template('common/showmessage');
			if(!empty($_GET['debug'])) {
				exit;
			}
			$content = ob_get_contents();
			ob_end_clean();
			$xml['Variables']['datatype'] = -1;
			$xml['Variables']['webview_page'] = $content;
			return $xml;
		}

		if($_G['messageparam'][4]) {
			$_G['messageparam'][0] = "custom";
		}
		if ($_G['messageparam'][3]['login'] && !$_G['uid']) {
			$_G['messageparam'][0] .= '//' . $_G['messageparam'][3]['login'];
		}
		$xml['Message'] = array("messageval" => $_G['messageparam'][0], "messagestr" => $message_result);
		if($_GET['mobilemessage']) {
			$return = mobile_core::json($xml);
			header("HTTP/1.1 301 Moved Permanently");
			header("Location:discuz://" . rawurlencode($_G['messageparam'][0]) . "//" . rawurlencode(diconv($message_result, $_G['charset'], "utf-8")) . ($return ? "//" . rawurlencode($return) : '' ));
			exit;
		}
	}
}


function date_ago($string){
	$now = time();
	$c = $now - $string;
	$nowyear = date('Y',$now);
	$stringdate = date('Y-m-d',$string);
	$stringyear = date('Y',$string);
	$yestoday = date("Y-m-d",strtotime("-1 day"));
	if($stringdate == $yestoday){
		return '昨天'.date('H:i',$string);
	}
	if($stringyear != $nowyear){
		return date('Y-m-d' ,$string);
	}
	if ($c < 120) {
		return '刚刚';
	}
	if ($c < 180) {
		return '3分钟前';
	}
	if ($c < 3600) {
		return (($c - $c % 60) / 60) . '分钟前';
	}
	if ($c < 86400) {
		return (($c - $c % 3600) / 3600) . '小时前';
	}
	return date('m-d' ,$string);
}
 