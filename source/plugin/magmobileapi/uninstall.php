<?php
/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: uninstall.php 25889 2011-11-24 09:52:20Z monkey $
 */

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF
DROP TABLE pre_user_mobile_relations;
DROP TABLE pre_user_qq_relations;
DROP TABLE pre_user_weixin_relations;
EOF;

//runquery($sql);

$finish = TRUE;
?>