<?php
/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: install.php 25889 2011-11-24 09:52:20Z monkey $
 */
//echo 111;die;
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$sql = <<<EOF
 
CREATE TABLE  IF NOT EXISTS `pre_user_mobile_relations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `create_time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `phone` (`phone`)
) ENGINE=MyISAM;


CREATE TABLE IF NOT EXISTS `pre_user_qq_relations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned DEFAULT NULL,
  `openid` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `create_time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `phone` (`openid`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `pre_user_weixin_relations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned DEFAULT NULL,
  `unionid` varchar(255) DEFAULT NULL,
  `openid` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `create_time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `phone` (`unionid`)
) ENGINE=MyISAM ;
EOF;
runquery($sql);
$finish = TRUE;

//pre_common_member_profile mobile字段 触发器
/*
DROP TRIGGER IF EXISTS t_update_mobile;
CREATE TRIGGER t_update_mobile
AFTER UPDATE ON pre_common_member_profile
FOR EACH ROW
    begin
if   NEW.mobile!=OLD.mobile   then
	set @mobile=trim(NEW.mobile);
	if   @mobile=''   then
		delete from  pre_user_mobile_relations    where userid =OLD.uid ;
	else
		set @count = (select count(*) from pre_user_mobile_relations where userid = NEW.uid);
		if  @count=0  then
		insert into pre_user_mobile_relations(userid,phone,create_time) values ( NEW.uid,@mobile,UNIX_TIMESTAMP());
		else
		update  pre_user_mobile_relations    set   phone = @mobile  where userid =NEW.uid ;
		end if;
	end if;
end if;
end
*/


/*
 * t_insert_mobile
 * begin
if   NEW.mobile then
     insert into pre_user_mobile_relations(userid,phone,create_time) values ( NEW.uid,@mobile,UNIX_TIMESTAMP());
end if;
end
 */
?>