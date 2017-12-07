<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Magapp-X探针</title>
    <style>
        .form {
            line-height: 26px;
            font-size: 14px;
            border-bottom: 1px #ccc solid
        }

        .form .title {
            font-size: 18px;
            font-weight: bold
        }

        .form .result {
            color: #999
        }

        .form textarea {
            border: 1px solid #ccc;
            height: 150px;
            width: 300px;
        }
    </style>
</head>
<body>
<div id="app">
    <div class="form">
        <p class="title">PHP版本</p>
        <div>
            <p class="result"><?php echo PHP_VERSION;?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">MYSQL版本</p>
        <div>
            <?php
            $con = mysql_connect('rm-wz98dol1asi4asw9d.mysql.rds.aliyuncs.com','magapp','lNF7vn4^$3bfhNa1');
            if (!$con)
            {
                echo ('Could not connect: ' . mysql_error());
            }
            $res=mysql_query("select VERSION()",$con);$row=mysql_fetch_row($res);
            echo json_encode($row);
            ?>
            <p class="result">请在dz/pw后台查看</p>
        </div>
    </div>
    <div class="form">
        <p class="title">操作系统</p>
        <div>
            <p class="result"><?php echo PHP_OS;?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">web服务端</p>
        <div>
            <p class="result"><?php echo $_SERVER ['SERVER_SOFTWARE'];?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">GD库</p>
        <div>
            <p class="result"><?php echo extension_loaded('gd');?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">Curl 扩展</p>
        <div>
            <p class="result"><?php echo extension_loaded('curl');?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">MYSQL 扩展</p>
        <div>
            <p class="result"><?php echo extension_loaded('mysql');?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">PDO 扩展</p>
        <div>
            <p class="result"><?php echo extension_loaded('PDO');?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">libxml 扩展</p>
        <div>
            <p class="result"><?php echo extension_loaded('libxml');?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">pdo_mysql 扩展</p>
        <div>
            <p class="result"><?php echo extension_loaded('pdo_mysql');?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">mbstring 扩展</p>
        <div>
            <p class="result"><?php echo extension_loaded('mbstring');?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">json 扩展</p>
        <div>
            <p class="result"><?php echo extension_loaded('json');?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">iconv 扩展</p>
        <div>
            <p class="result"><?php echo extension_loaded('iconv');?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">magic_quotes_gpc 配置</p>
        <div>
            <p class="result"><?php echo get_magic_quotes_gpc();?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">magic_quotes_runtime 配置</p>
        <div>
            <p class="result"><?php echo get_magic_quotes_runtime();?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">函数依赖性检查(file_get_contents)</p>
        <div>
            <p class="result"><?php echo function_exists("file_get_contents")?"是":"否";?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">函数依赖性检查(mysql_connect)</p>
        <div>
            <p class="result"><?php echo function_exists("mysql_connect")?"是":"否";?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">函数依赖性检查(getimagesize)</p>
        <div>
            <p class="result"><?php echo function_exists("getimagesize")?"是":"否";?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">函数依赖性检查(iconv)</p>
        <div>
            <p class="result"><?php echo function_exists("iconv")?"是":"否";?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">函数依赖性检查(mb_convert_encoding)</p>
        <div>
            <p class="result"><?php echo function_exists("mb_convert_encoding")?"是":"否";?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">函数依赖性检查(json_encode)</p>
        <div>
            <p class="result"><?php echo function_exists("json_encode")?"是":"否";?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">最大执行时间：(max_execution_time)</p>
        <div>
            <p class="result"><?php echo get_cfg_var("max_execution_time")."秒 ";?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">脚本运行占用最大内存：(max_execution_time)</p>
        <div>
            <p class="result"><?php echo get_cfg_var ("memory_limit")?get_cfg_var("memory_limit"):"无" ?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">MySQL最大连接数：</p>
        <div>
            <p class="result"><?php echo @get_cfg_var("mysql.max_links")==-1 ? "不限" : @get_cfg_var("mysql.max_links"); ?></p>
        </div>
    </div>
    <div class="form">
        <p class="title">网络性能调优(sysctl参数)</p>
        <div>
            <p class="result">sysctl -n net.core.netdev_max_backlog net.core.somaxconn net.core.optmem_max net.core.rmem_default net.core.rmem_max net.core.wmem_default net.core.wmem_max net.ipv4.tcp_fin_timeout net.ipv4.tcp_keepalive_time net.ipv4.tcp_max_orphans net.ipv4.tcp_max_syn_backlog net.ipv4.tcp_orphan_retries net.ipv4.tcp_rmem net.ipv4.tcp_syn_retries net.ipv4.tcp_synack_retries net.ipv4.tcp_syncookies vm.swappiness | tr '\t' ' '</p>
        </div>
    </div>
</div>
<?php phpinfo();?>
</body>
</html>