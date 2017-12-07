<?php
/**
 * 南京灵衍信息科技有限公司
 * User: wangxiao
 * Date: 2017/4/17
 * Time: 14:41
 */
date_default_timezone_set("PRC");

define('C_R',dirname(__file__));
@include C_R.'/version.php';
include C_R.'/../../../config/config_global.php';
$charset = $_config['output']['charset']  == 'utf-8'? 'utf-8': 'gbk' ;
//echo $charset;exit;
//echo MAGMOBILEAPI_VERSION;exit;

$filelist = array(
    'magmobileapi.class.php',
    'magmobileapi.php',
    'version.php',
    'controller/v1/forum.php',
    'controller/v1/home.php',
    'controller/v1/member.php',
    'controller/v1/portal.php',
    'static/magapp.css',
    'updateremote.php',
);

if (!is_dir(C_R.'/update')) @mkdir(C_R.'/update', 0777);
if (!is_dir(C_R.'/bak')) @mkdir(C_R.'/bak', 0777);
/** 下载安装包 **/
$ch = curl_init();
$datetime = date('Y-m-d His');
foreach ($filelist as $file){
    @mkdir(C_R.'/update/'.$datetime, 0777);
    @mkdir(C_R.'/update/'.$datetime.'/controller', 0777);
    @mkdir(C_R.'/update/'.$datetime.'/controller/v1', 0777);
    @mkdir(C_R.'/update/'.$datetime.'/static/', 0777);
    $updatefile = C_R.'/update/'.$datetime.'/'.$file;
    $URL = 'http://magcloud.magapp-x.magcloud.cc/public/uploads/dzplugins/downloadfile.php?charset='.$charset.'&file='.$file;
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $temp = curl_exec($ch);
    if(!$temp){
        echo $URL.' curl error';
        exit;
    }
    if(@file_put_contents($updatefile, $temp) && !curl_error($ch)){
    }else {
        echo $updatefile.' file write error';
        exit;
    }
}

/**备份老代码**/
foreach ($filelist as $file){
    @mkdir(C_R.'/bak/'.$datetime, 0777);
    @mkdir(C_R.'/bak/'.$datetime.'/controller', 0777);
    @mkdir(C_R.'/bak/'.$datetime.'/controller/v1', 0777);
    @mkdir(C_R.'/bak/'.$datetime.'/static/', 0777);
    $bakfile = C_R.'/bak/'.$datetime.'/'.$file;
    if(file_exists(C_R.'/'.$file)){
        if(!copy(C_R.'/'.$file,$bakfile)){
            echo $bakfile.' file write error';
            exit;
        }
    }

}

/** 覆盖新版本**/
foreach ($filelist as $file){
    $updatefile = C_R.'/update/'.$datetime.'/'.$file;
    if(!copy($updatefile,C_R.'/'.$file)){
        echo $file.' file write error';
        exit;
    }
}

echo 'success';exit;