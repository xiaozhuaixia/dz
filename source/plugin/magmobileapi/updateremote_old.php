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
//echo MAGMOBILEAPI_VERSION;exit;

if (!is_dir(C_R.'/update')) @mkdir(C_R.'/update', 0777);
if (!is_dir(C_R.'/bak')) @mkdir(C_R.'/bak', 0777);
/** 下载安装包 **/
$ch = curl_init();
$datetime = date('Y-m-d His');
$updatefile = C_R.'/update/'.$datetime.'update.zip';
$URL = 'http://magcloud.magapp-x.magcloud.cc/public/uploads/dzplugins/magmobileapi_utf8.zip';
curl_setopt($ch, CURLOPT_URL, $URL);
curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$temp = curl_exec($ch);
if(@file_put_contents($updatefile, $temp) && !curl_error($ch)){
}else {
    echo 'download file error';
    exit;
}

/**备份老代码**/
$zip = new ZipArchive;//新建一个ZipArchive的对象
if ($zip->open(C_R.'/bak/'.$datetime.'bak.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    addFileToZip('.', $zip); //调用方法，对要打包的根目录进行操作，并将ZipArchive的对象传递给方法
    $zip->close(); //关闭处理的zip文件
}else{
    echo 'file bak error';
    exit;
}

/** 覆盖新版本**/
if (@$zip->open($updatefile) === TRUE)
{
    $zip->extractTo('./');//假设解压缩到在当前路径下images文件夹的子文件夹php
    $zip->close();//关闭处理的zip文件
}else{
    echo 'file unzip error';
    exit;
}

echo 'success';exit;


function addFileToZip($path, ZipArchive $zip) {
    $handler = opendir($path); //打开当前文件夹由$path指定。
    /*
    循环的读取文件夹下的所有文件和文件夹
    其中$filename = readdir($handler)是每次循环的时候将读取的文件名赋值给$filename，
    为了不陷于死循环，所以还要让$filename !== false。
    一定要用!==，因为如果某个文件名如果叫'0'，或者某些被系统认为是代表false，用!=就会停止循环
    */
    while (($filename = readdir($handler)) !== false) {
        //        echo $filename."\n";
        if ($filename != "." && $filename != ".." && !in_array($filename,array('.git','bak','.idea'))) {//文件夹文件名字为'.'和‘..’，不要对他们进行操作
            if (is_dir($path . "/" . $filename) && !in_array($path,array('./bak','./.git','./.idea'))) {// 如果读取的某个对象是文件夹，则递归
                addFileToZip($path . "/" . $filename, $zip);
            } else { //将文件加入zip对象
                $zip->addFile($path . "/" . $filename);
            }
        }
    }
    @closedir($path);
}


// 原目录，复制到的目录
function recurse_copy($src,$dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . '/' . $file) ) {
                recurse_copy($src . '/' . $file,$dst . '/' . $file);
            }
            else {
                copy($src . '/' . $file,$dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}
