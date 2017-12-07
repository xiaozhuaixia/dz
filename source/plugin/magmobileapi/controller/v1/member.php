<?php
/**
 * 南京灵衍信息科技有限公司
 * User: wangxiao
 * Date: 2016/11/23
 * Time: 15:51
 */

$_GET[ 'mod' ] = $_GET[ 'mod' ] ? $_GET[ 'mod' ] : 'logging';
$_GET['action'] = !empty($_GET['action']) ? $_GET['action'] : 'login';
include_once dirname(__file__).'/../../../../../member.php';



class member{

    public function loginCommon(){
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG; 
        if(is_phone_number($_POST['username'])  ){
            //如果是手机格式
            $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_phone'] ."=".$_POST['username']);
            if($usermobilerow[$_MAG_CONFIG['user_mobile_relations_userid']]){
                $userrow = DB::fetch_first("SELECT * FROM " .DB::table('common_member')." WHERE uid=".(int)$usermobilerow['userid']);
                if($userrow['uid']){
                    $_GET['username'] = $_POST['username'] = $userrow['username'];
                }
            }
        }
    }

    public function loginOutput(){
        global $_G;
        if($_G['hookscriptmessage'] !='login_succeed'){
            error($_G['lang']['message'][$_G['hookscriptmessage']],array(),$_G['hookscriptmessage']);
        }else{
            if(in_array('mobileoem', $_G['setting']['plugins']['available'])) {
                $check = C::t('#mobileoem#mobileoem_member')->fetch($_G['uid']);
            }
            $membercredit = 0;
            $member = $_G['member'];
            $tableext = isset($member['_inarchive']) ? '_archive' : '';
            $membercount = C::t('common_member_count'.$tableext)->fetch($_G['uid']);
            $membergroup = C::t('common_usergroup')->fetch($member['groupid']);
            $member = array_merge($member, $membercount, $membergroup);
            //		echo json_encode($member);exit;
            eval("\$membercredit = @round({$_G[setting][creditsformula]});");
            $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
            $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_userid']."=".$member['uid']);

            $globals = array(
                'cookiepre' => $_G['config']['cookie']['cookiepre'],
                'auth' => $_G['cookie']['auth'],
                'saltkey' => $_G['cookie']['saltkey'],
                'member_uid' => $member['uid'],
                'time' => time(),
                'member_username' => $member['username'],
                'member_sex' => $member['gender'],
                'member_phone' => $usermobilerow[$_MAG_CONFIG['user_mobile_relations_phone']] ? $usermobilerow[$_MAG_CONFIG['user_mobile_relations_phone']] :'',
                'member_avatar' => avatar($member['uid'], 'small', true),
                'groupid' => $_G['groupid'],
                'grouptitle' => $member['grouptitle'],
                'credits' => $membercredit,
                'formhash' => formhash(),
                'ismoderator' => $_G['forum']['ismoderator'],
                'readaccess' => $_G['group']['readaccess'],
                'key1' => $_G['authkey'],
                'key' => md5($_G['authkey'].$_SERVER['HTTP_USER_AGENT']),
                //			'G' => $_G,
                'notice' => array(
                    'newpush' => $check['newpush'] ? 1 : 0,
                    'newpm' => dintval($_G['member']['newpm']),
                    'newprompt' => dintval(($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) >= 0 ? ($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) : 0),
                    'newmypost' => dintval($_G['member']['category_num']['mypost']),
                )
            );

            $retrundata =  $globals;

            success('', $retrundata);
        }
    }

    public function forceLoginCommon(){
        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('NOROBOT', TRUE);

        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';

        define('IN_UC', TRUE);
        $discuz = C::app();
        $discuz->init();
        include_once './uc_client/client.php';

        global $_G;
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        if(is_phone_number($_POST['username'])  ){
            //如果是手机格式
            $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_phone'] ."=".$_POST['username']);
            if($usermobilerow[$_MAG_CONFIG['user_mobile_relations_userid']]){
                $userrow = DB::fetch_first("SELECT * FROM " .DB::table('common_member')." WHERE uid=".(int)$usermobilerow[$_MAG_CONFIG['user_mobile_relations_userid']]);
                if($userrow['uid']){
                    $_GET['username'] = $_POST['username'] = $userrow['username'];
                }
            }
        }
        $username = $_POST['username'];
        $password = $_POST['password'];
        $result = uc_user_login(addslashes($username), $password);
        $tmp = array();
        list($tmp['uid'], $tmp['username'], $tmp['password'], $tmp['email'], $duplicate) = $result;
        $ucresult = $tmp;

        if($ucresult['uid'] == -1){
            error('用户不存在，或者被删除');
        }else if($ucresult['uid'] == -2){
            error('密码错误');
        }
        $member = getuserbyuid($ucresult['uid'], 1);
        if(!$member || empty($member['uid'])) { 
            error('您的帐号处于未激活状态,请登录网站进行激活操作');
        }
        if($member['_inarchive']) {
            C::t('common_member_archive')->move_to_master($member['uid']);
        }
        if($member['email'] != $ucresult['email']) {
            C::t('common_member')->update($ucresult['uid'], array('email' => $ucresult['email']));
        }
        $cookietime = 31536000;
        setloginstatus($result['member'],$cookietime);

        $auth = authcode("$member[password]\t$member[uid]", 'ENCODE');
        dsetcookie('auth', $auth, $cookietime);
        $_G['cookie']['auth'] = $auth;
        $_G['username'] = $member['username'];
        $_G['uid'] = $member['uid'];

        C::t('common_member_status')->update($_G['uid'], array('lastip' => $_G['clientip'], 'lastvisit' =>TIMESTAMP, 'lastactivity' => TIMESTAMP));

        $tableext = isset($member['_inarchive']) ? '_archive' : '';
        $membercount = C::t('common_member_count'.$tableext)->fetch($_G['uid']);
        $membergroup = C::t('common_usergroup')->fetch($member['groupid']);
        $member = array_merge($member, $membercount, $membergroup);
        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_userid']."=".$member['uid']);
        $membercredit = 0;
        eval("\$membercredit = @round({$_G[setting][creditsformula]});");
        $globals = array(
            'cookiepre' => $_G['config']['cookie']['cookiepre'],
            'auth' => $_G['cookie']['auth'],
            'saltkey' => $_G['cookie']['saltkey'],
            'member_uid' => $member['uid'],
            'time' => time(),
            'member_username' => $member['username'],
            'member_sex' => $member['gender'],
            'member_phone' => $usermobilerow[$_MAG_CONFIG['user_mobile_relations_phone']] ? $usermobilerow[$_MAG_CONFIG['user_mobile_relations_phone']] :'',
            'member_avatar' => avatar($member['uid'], 'small', true),
            'groupid' => $_G['groupid'],
            'grouptitle' => $member['grouptitle'],
            'credits' => $membercredit,
            'formhash' => formhash(),
            'ismoderator' => $_G['forum']['ismoderator'],
            'readaccess' => $_G['group']['readaccess'],
            'key1' => $_G['authkey'],
            'key' => md5($_G['authkey'].$_SERVER['HTTP_USER_AGENT']),
            //			'G' => $_G,
            'notice' => array(
                'newpm' => dintval($_G['member']['newpm']),
                'newprompt' => dintval(($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) >= 0 ? ($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) : 0),
                'newmypost' => dintval($_G['member']['category_num']['mypost']),
            )
        );

        $retrundata =  $globals;

        success('', $retrundata);
    }

    public function phoneLoginCommon(){
        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('NOROBOT', TRUE);

        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';

        define('IN_UC', TRUE);
        $discuz = C::app();
        $discuz->init();
        include_once './uc_client/client.php';
        $variable = array();
        global $_G;
        if(!is_phone_number($_POST['phone'])){
            error('手机号格式错误',array(),'phone_format_error');
         }
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_phone']."=".$_POST['phone']);
        //echo json_encode($usermobilerow);exit;
        if($usermobilerow[$_MAG_CONFIG['user_mobile_relations_userid']]){
            $uid = $usermobilerow[$_MAG_CONFIG['user_mobile_relations_userid']];
            $cookietime = 31536000;
            if(($member = getuserbyuid($uid, 1))) {
                $_G['member'] = $member;
                setloginstatus($member,$cookietime);
                $auth = authcode("$member[password]\t$member[uid]", 'ENCODE');
                dsetcookie('auth', $auth, $cookietime);
                $_G['cookie']['auth'] = $auth;
                $_G['username'] = $member[username];
                $_G['uid'] = $member[uid];
            }else{
                error('用户不存在',array(),'login_error');
            }
        }else{
            error('手机号不存在',array(),'login_error');
        }

        if(in_array('mobileoem', $_G['setting']['plugins']['available'])) {
            $check = C::t('#mobileoem#mobileoem_member')->fetch($_G['uid']);
        }
        $membercredit = 0;
        $member = $_G['member'];
        $tableext = isset($member['_inarchive']) ? '_archive' : '';
        $membercount = C::t('common_member_count'.$tableext)->fetch($_G['uid']);
        $membergroup = C::t('common_usergroup')->fetch($member['groupid']);
        $member = array_merge($member, $membercount, $membergroup);
        //		echo json_encode($member);exit;
        eval("\$membercredit = @round({$_G[setting][creditsformula]});");
        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_userid']."=".$member['uid']);

        $globals = array(
            'cookiepre' => $_G['config']['cookie']['cookiepre'],
            'auth' => $_G['cookie']['auth'],
            'saltkey' => $_G['cookie']['saltkey'],
            'time' => time(),
            'member_uid' => $member['uid'],
            'member_username' => $member['username'],
            'member_phone' => $usermobilerow[$_MAG_CONFIG['user_mobile_relations_phone']] ? $usermobilerow[$_MAG_CONFIG['user_mobile_relations_phone']] :'',
            'member_sex' => $member['gender'],
            'member_avatar' => avatar($member['uid'], 'small', true),
            'groupid' => $_G['groupid'],
            'grouptitle' => $member['grouptitle'],
            'credits' => $membercredit,
            'formhash' => formhash(),
            'ismoderator' => $_G['forum']['ismoderator'],
            'readaccess' => $_G['group']['readaccess'],
            'key1' => $_G['authkey'],
            'key' => md5($_G['authkey'].$_SERVER['HTTP_USER_AGENT']),
            //			'G' => $_G,
            'notice' => array(
                'newpush' => $check['newpush'] ? 1 : 0,
                'newpm' => dintval($_G['member']['newpm']),
                'newprompt' => dintval(($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) >= 0 ? ($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) : 0),
                'newmypost' => dintval($_G['member']['category_num']['mypost']),
            )
        );

        $retrundata =  $globals;

        success('', $retrundata);
    }

    public function phoneLoginOutput(){


    }


    public function QQLoginCommon(){
        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('NOROBOT', TRUE);

        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';

        define('IN_UC', TRUE);
        $discuz = C::app();
        $discuz->init();
        include_once './uc_client/client.php';
        $variable = array();
        global $_G;
        if(!($_POST['openid'])){
            error('openid不能为空',array(),'openid_empty_error');
        }
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_qq_relations'])." WHERE ".$_MAG_CONFIG['user_qq_relations_openid']."='".$_POST['openid']."'");
        //echo json_encode($usermobilerow);exit;
        if($usermobilerow[$_MAG_CONFIG['user_qq_relations_userid']]){
            $uid = $usermobilerow[$_MAG_CONFIG['user_qq_relations_userid']];
            $cookietime = 31536000;
            if(($member = getuserbyuid($uid, 1))) {
                $_G['member'] = $member;
                setloginstatus($member,$cookietime);
                $auth = authcode("$member[password]\t$member[uid]", 'ENCODE');
                dsetcookie('auth', $auth, $cookietime);
                $_G['cookie']['auth'] = $auth;
                $_G['username'] = $member[username];
                $_G['uid'] = $member[uid];
            }else{
                error('用户不存在',array(),'login_error');
            }
        }else{
            error('openid不存在',array(),'login_error');
        }

        if(in_array('mobileoem', $_G['setting']['plugins']['available'])) {
            $check = C::t('#mobileoem#mobileoem_member')->fetch($_G['uid']);
        }
        $membercredit = 0;
        $member = $_G['member'];
        $tableext = isset($member['_inarchive']) ? '_archive' : '';
        $membercount = C::t('common_member_count'.$tableext)->fetch($_G['uid']);
        $membergroup = C::t('common_usergroup')->fetch($member['groupid']);
        $member = array_merge($member, $membercount, $membergroup);
        //		echo json_encode($member);exit;
        eval("\$membercredit = @round({$_G[setting][creditsformula]});");

        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_userid']."=".$member['uid']);
        $globals = array(
            'cookiepre' => $_G['config']['cookie']['cookiepre'],
            'auth' => $_G['cookie']['auth'],
            'saltkey' => $_G['cookie']['saltkey'],
            'time' => time(),
            'member_uid' => $member['uid'],
            'member_username' => $member['username'],
            'member_phone' => $usermobilerow[$_MAG_CONFIG['user_mobile_relations_phone']] ? $usermobilerow[$_MAG_CONFIG['user_mobile_relations_phone']] :'',
            'member_sex' => $member['gender'],
            'member_avatar' => avatar($member['uid'], 'small', true),
            'groupid' => $_G['groupid'],
            'grouptitle' => $member['grouptitle'],
            'credits' => $membercredit,
            'formhash' => formhash(),
            'ismoderator' => $_G['forum']['ismoderator'],
            'readaccess' => $_G['group']['readaccess'],
            'key1' => $_G['authkey'],
            'key' => md5($_G['authkey'].$_SERVER['HTTP_USER_AGENT']),
            //			'G' => $_G,
            'notice' => array(
                'newpush' => $check['newpush'] ? 1 : 0,
                'newpm' => dintval($_G['member']['newpm']),
                'newprompt' => dintval(($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) >= 0 ? ($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) : 0),
                'newmypost' => dintval($_G['member']['category_num']['mypost']),
            )
        );

        $retrundata =  $globals;

        success('', $retrundata);
    }

    public function QQLoginOutput(){


    }

    public function WeixinLoginCommon(){
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('NOROBOT', TRUE);

        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';

        define('IN_UC', TRUE);
        $discuz = C::app();
        $discuz->init();
        include_once './uc_client/client.php';
        $variable = array();
        global $_G;
        if(!($_POST['openid'] || $_POST['unionid'])){
            error('openid或unionid不能为空',array(),'unionid_empty_error');
        }
        if($_POST['unionid']){
            $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_weixin_relations'])." WHERE ".$_MAG_CONFIG['user_weixin_relations_unionid']."='".$_POST['unionid']."'");
            //echo json_encode($usermobilerow);exit;
            if($usermobilerow[$_MAG_CONFIG['user_weixin_relations_userid']]){
                $uid = $usermobilerow[$_MAG_CONFIG['user_weixin_relations_userid']];
                $cookietime = 31536000;
                if(($member = getuserbyuid($uid, 1))) {
                    $_G['member'] = $member;
                    setloginstatus($member,$cookietime);
                    $auth = authcode("$member[password]\t$member[uid]", 'ENCODE');
                    dsetcookie('auth', $auth, $cookietime);
                    $_G['cookie']['auth'] = $auth;
                    $_G['username'] = $member[username];
                    $_G['uid'] = $member[uid];
                }else{
                    error('用户不存在',array(),'login_error');
                }
            }else{
                error('unionid不存在',array(),'login_error');
            }
        }else{
            $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_weixin_relations'])." WHERE ".$_MAG_CONFIG['user_weixin_relations_openid']."='".$_POST['openid']."'");
            //echo json_encode($usermobilerow);exit;
            if($usermobilerow[$_MAG_CONFIG['user_weixin_relations_userid']]){
                $uid = $usermobilerow[$_MAG_CONFIG['user_weixin_relations_userid']];
                $cookietime = 31536000;
                if(($member = getuserbyuid($uid, 1))) {
                    $_G['member'] = $member;
                    setloginstatus($member,$cookietime);
                    $auth = authcode("$member[password]\t$member[uid]", 'ENCODE');
                    dsetcookie('auth', $auth, $cookietime);
                    $_G['cookie']['auth'] = $auth;
                    $_G['username'] = $member[username];
                    $_G['uid'] = $member[uid];
                }else{
                    error('用户不存在',array(),'login_error');
                }
            }else{
                error('openid不存在',array(),'login_error');
            }
        }

        if(in_array('mobileoem', $_G['setting']['plugins']['available'])) {
            $check = C::t('#mobileoem#mobileoem_member')->fetch($_G['uid']);
        }
        $membercredit = 0;
        $member = $_G['member'];
        $tableext = isset($member['_inarchive']) ? '_archive' : '';
        $membercount = C::t('common_member_count'.$tableext)->fetch($_G['uid']);
        $membergroup = C::t('common_usergroup')->fetch($member['groupid']);
        $member = array_merge($member, $membercount, $membergroup);
        //		echo json_encode($member);exit;
        eval("\$membercredit = @round({$_G[setting][creditsformula]});");

        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_userid']."=".$member['uid']);
        $globals = array(
            'cookiepre' => $_G['config']['cookie']['cookiepre'],
            'auth' => $_G['cookie']['auth'],
            'saltkey' => $_G['cookie']['saltkey'],
            'member_uid' => $member['uid'],
            'time' => time(),
            'member_username' => $member['username'],
            'member_phone' => $usermobilerow[$_MAG_CONFIG['user_mobile_relations_phone']] ? $usermobilerow[$_MAG_CONFIG['user_mobile_relations_phone']] :'',
            'member_sex' => $member['gender'],
            'member_avatar' => avatar($member['uid'], 'small', true),
            'groupid' => $_G['groupid'],
            'grouptitle' => $member['grouptitle'],
            'credits' => $membercredit,
            'formhash' => formhash(),
            'ismoderator' => $_G['forum']['ismoderator'],
            'readaccess' => $_G['group']['readaccess'],
            'key1' => $_G['authkey'],
            'key' => md5($_G['authkey'].$_SERVER['HTTP_USER_AGENT']),
            //			'G' => $_G,
            'notice' => array(
                'newpush' => $check['newpush'] ? 1 : 0,
                'newpm' => dintval($_G['member']['newpm']),
                'newprompt' => dintval(($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) >= 0 ? ($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) : 0),
                'newmypost' => dintval($_G['member']['category_num']['mypost']),
            )
        );

        $retrundata =  $globals;

        success('', $retrundata);
    }

    public function WeixinLoginOutput(){


    }


    public function registerCommon(){
        $_GET['formhash'] = formhash();
        global $_G;
        if(empty($_POST['regsubmit'])) {
            $_G['mobile_version'] = intval($_GET['version']);
        }
        require_once libfile('class/member');
        $ctl_obj = new register_ctl();
        $ctl_obj->setting = $_G['setting'];
        $ctl_obj->template = 'mobile:register';

        if(!is_phone_number($_POST['mobile'])){
            error('手机号格式错误',array(),'phone_format_error');
        };
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_phone']."=".$_POST['mobile']);
        if($usermobilerow[$_MAG_CONFIG['user_mobile_relations_userid']]){
            $userrow = DB::fetch_first("SELECT * FROM " .DB::table('common_member')." WHERE uid=".$usermobilerow[$_MAG_CONFIG['user_mobile_relations_userid']]);
            if(!$userrow['uid']){
                DB::delete($_MAG_CONFIG['user_mobile_relations'],$_MAG_CONFIG['user_mobile_relations_phone']."=".$_POST['mobile'],1);
            }else{
                error('手机号已注册',array(),'phone_is_exist_error');
            }
        }
        $_GET[$_G['setting']['reginput']['username']] = trim($_POST['username']);
        $_GET[$_G['setting']['reginput']['password']] = $_POST['password'];
        $_GET[$_G['setting']['reginput']['password2']] = $_POST['password2'];
        $_GET[$_G['setting']['reginput']['email']] = $_POST['email'];
        $_GET['mobile'] = $_POST['mobile'];

        $ctl_obj->on_register();
        if(empty($_POST['regsubmit'])) {
            exit;
        }
    }

    public function registerOutput(){
        global $_G;
        if ($_G[ 'hookscriptmessage' ] != 'register_succeed') {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
            exit;
        }
        //		echo $_G['member']['uid'];exit;
        //		echo $_POST['mobile'];exit;
        if($_G['member']['uid']){
            $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
            $insertData[$_MAG_CONFIG['user_mobile_relations_userid']] =  $_G['member']['uid'];
            $insertData[$_MAG_CONFIG['user_mobile_relations_phone']] =  $_POST['mobile'];
            if($_MAG_CONFIG['user_mobile_relations_create_time']){
                $insertData[$_MAG_CONFIG['user_mobile_relations_create_time']] = time();
            }
            DB::insert($_MAG_CONFIG['user_mobile_relations'],$insertData);
        }

        if(in_array('mobileoem', $_G['setting']['plugins']['available'])) {
            $check = C::t('#mobileoem#mobileoem_member')->fetch($_G['uid']);
        }
        $membercredit = 0;
        $member = $_G['member'];
        $tableext = isset($member['_inarchive']) ? '_archive' : '';
        $membercount = C::t('common_member_count'.$tableext)->fetch($_G['uid']);
        $membergroup = C::t('common_usergroup')->fetch($member['groupid']);
        $member = array_merge($member, $membercount, $membergroup);
        //		echo json_encode($member);exit;
        eval("\$membercredit = @round({$_G[setting][creditsformula]});");
        $globals = array(
            'cookiepre' => $_G['config']['cookie']['cookiepre'],
            'auth' => $_G['cookie']['auth'],
            'time' => time(),
            'saltkey' => $_G['cookie']['saltkey'],
            'member_uid' => $member['uid'],
            'member_username' => $member['username'],
            'member_sex' => $member['gender'],
            'member_avatar' => avatar($member['uid'], 'small', true),
            'groupid' => $_G['groupid'],
            'grouptitle' => $member['grouptitle'],
            'credits' => $membercredit,
            'formhash' => formhash(),
            'ismoderator' => $_G['forum']['ismoderator'],
            'readaccess' => $_G['group']['readaccess'],
            'key1' => $_G['authkey'],
            'key' => md5($_G['authkey'].$_SERVER['HTTP_USER_AGENT']),
            //			'G' => $_G,
            'notice' => array(
                'newpush' => $check['newpush'] ? 1 : 0,
                'newpm' => dintval($_G['member']['newpm']),
                'newprompt' => dintval(($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) >= 0 ? ($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) : 0),
                'newmypost' => dintval($_G['member']['category_num']['mypost']),
            )
        );

        $retrundata =  $globals;

        success('', $retrundata);
    }

    public function userBasicCommon(){
        global $_G;
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('NOROBOT', TRUE);

        require_once './source/class/class_core.php';
        $discuz = C::app();
        $discuz->init();
        $uid = $_GET['userid'] ;
        if(empty($uid)) {
            //error('用户id为空',array(),'userid_empty');
            success('', array(
                'uid' => 0,
                'username' =>'匿名',
                'avatar' => avatar(0, 'big', true).'&random='.random(2),
                'stars' => 0,
                'groupid' => 0
            ));
        }

        $member = getuserbyuid($uid,1);
//        echo 111;die;
//        echo json_encode($_G);exit;
//        echo json_encode($member);exit;
        if(empty($member)){
            error('用户不存在',array(),'user_no_exist');
        }
        $tableext = isset($member['_inarchive']) ? '_archive' : '';
        $member = array_merge($member, C::t('common_member_field_forum'.$tableext)->fetch($uid),
            C::t('common_member_field_home'.$tableext)->fetch($uid),
            C::t('common_member_count'.$tableext)->fetch($uid),
            C::t('common_member_status'.$tableext)->fetch($uid),
            C::t('common_member_profile'.$tableext)->fetch($uid),
            C::t('common_usergroup')->fetch($member['groupid']),
            C::t('common_usergroup_field')->fetch($member['groupid']));
        if(!empty($_G['setting']['connect']['allow'])) {
            $member = array_merge($member, C::t('#qqconnect#common_member_connect')->fetch($uid));
            $uin = C::t('common_uin_black')->fetch_by_uid($uid);
            $member = array_merge($member, array('uinblack'=>$uin['uin']));
        }
        $member['avatar'] = avatar($uid, 'big', true).'&random='.random(2);
        if(!empty($_G['setting']['creditsformula'])){
            eval("\$credits = round(".$_G['setting']['creditsformula'].");");
            $member['credits']  = $credits;
        }
        $member['scorerules'] = strip_tags($_G['setting']['creditsformulaexp']);
        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_userid']."=".$uid);
        $member['phone'] = $usermobilerow[$_MAG_CONFIG['user_mobile_relations_phone']];


        $userweixinrow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_weixin_relations'])." WHERE ".$_MAG_CONFIG['user_weixin_relations_userid']."=".$uid);
        $member['weixin_openid'] = $userweixinrow[$_MAG_CONFIG['user_weixin_relations_openid']];
        $member['weixin_unionid'] = $userweixinrow[$_MAG_CONFIG['user_weixin_relations_unionid']];
        $member['weixin_name'] = $userweixinrow[$_MAG_CONFIG['user_weixin_relations_name']];

        $userqqrow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_qq_relations'])." WHERE ".$_MAG_CONFIG['user_qq_relations_userid']."=".$uid);
        $member['qq_openid'] = $userqqrow[$_MAG_CONFIG['user_qq_relations_openid']];
        $member['qq_name'] = $userqqrow[$_MAG_CONFIG['user_qq_relations_name']];

        //        echo json_encode($member);exit;
        success('', $member);
    }

    public function userBasicOutput(){

    }

    public function userBasicByPhoneCommon(){
        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('NOROBOT', TRUE);

        require_once './source/class/class_core.php';
        $discuz = C::app();
        $discuz->init();
        $phone = $_GET['phone'] ;
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_phone']."=".$phone);

        $uid = $usermobilerow[$_MAG_CONFIG['user_mobile_relations_userid']];
        if(empty($uid)) {
            error('手机号不存在',array(),'phone_no_exist');
        }

        $member = getuserbyuid($uid);
        if(empty($member)){
            error('用户不存在',array(),'user_no_exist');
        }
        $tableext = isset($member['_inarchive']) ? '_archive' : '';
        $member = array_merge($member, C::t('common_member_field_forum'.$tableext)->fetch($uid),
            C::t('common_member_field_home'.$tableext)->fetch($uid),
            C::t('common_member_count'.$tableext)->fetch($uid),
            C::t('common_member_status'.$tableext)->fetch($uid),
            C::t('common_member_profile'.$tableext)->fetch($uid),
            C::t('common_usergroup')->fetch($member['groupid']),
            C::t('common_usergroup_field')->fetch($member['groupid']));
        if(!empty($_G['setting']['connect']['allow'])) {
            $member = array_merge($member, C::t('#qqconnect#common_member_connect')->fetch($uid));
            $uin = C::t('common_uin_black')->fetch_by_uid($uid);
            $member = array_merge($member, array('uinblack'=>$uin['uin']));
        }
        $member['avatar'] = avatar($uid, 'middle', true).'&random='.random(2);

        success('', $member);
    }

    public function userBasicByPhoneOutput(){

    }

    function img2thumb($src_img, $dst_img, $width = 75, $height = 75, $cut = 0, $proportion = 0){
        //echo $src_img;die;
        $ot = fileext($dst_img);
        $otfunc = 'image' . ($ot == 'jpg' ? 'jpeg' : $ot);
        $srcinfo = getimagesize($src_img);
        $src_w = $srcinfo[0];
        $src_h = $srcinfo[1];
        $type  = strtolower(substr(image_type_to_extension($srcinfo[2]), 1));
        $createfun = 'imagecreatefrom' . ($type == 'jpg' ? 'jpeg' : $type);

        $dst_h = $height;
        $dst_w = $width;
        $x = $y = 0;

        /**
         * 缩略图不超过源图尺寸（前提是宽或高只有一个）
         */
        if(($width> $src_w && $height> $src_h) || ($height> $src_h && $width == 0) || ($width> $src_w && $height == 0))
        {
            $proportion = 1;
        }
        if($width> $src_w)
        {
            $dst_w = $width = $src_w;
        }
        if($height> $src_h)
        {
            $dst_h = $height = $src_h;
        }

        if(!$width && !$height && !$proportion)
        {
            return false;
        }
        if(!$proportion)
        {
            if($cut == 0)
            {
                if($dst_w && $dst_h)
                {
                    if($dst_w/$src_w> $dst_h/$src_h)
                    {
                        $dst_w = $src_w * ($dst_h / $src_h);
                        $x = 0 - ($dst_w - $width) / 2;
                    }
                    else
                    {
                        $dst_h = $src_h * ($dst_w / $src_w);
                        $y = 0 - ($dst_h - $height) / 2;
                    }
                }
                else if($dst_w xor $dst_h)
                {
                    if($dst_w && !$dst_h)  //有宽无高
                    {
                        $propor = $dst_w / $src_w;
                        $height = $dst_h  = $src_h * $propor;
                    }
                    else if(!$dst_w && $dst_h)  //有高无宽
                    {
                        $propor = $dst_h / $src_h;
                        $width  = $dst_w = $src_w * $propor;
                    }
                }
            }
            else
            {
                if(!$dst_h)  //裁剪时无高
                {
                    $height = $dst_h = $dst_w;
                }
                if(!$dst_w)  //裁剪时无宽
                {
                    $width = $dst_w = $dst_h;
                }
                $propor = min(max($dst_w / $src_w, $dst_h / $src_h), 1);
                $dst_w = (int)round($src_w * $propor);
                $dst_h = (int)round($src_h * $propor);
                $x = ($width - $dst_w) / 2;
                $y = ($height - $dst_h) / 2;
            }
        }
        else
        {
            $proportion = min($proportion, 1);
            $height = $dst_h = $src_h * $proportion;
            $width  = $dst_w = $src_w * $proportion;
        }

        $src = $createfun($src_img);
        $dst = imagecreatetruecolor($width ? $width : $dst_w, $height ? $height : $dst_h);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        if(function_exists('imagecopyresampled'))
        {
            imagecopyresampled($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
        }
        else
        {
            imagecopyresized($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
        }
        $otfunc($dst, $dst_img);
        imagedestroy($dst);
        imagedestroy($src);
        return true;
    }
    //下载图片
    function dlfile($file_url, $save_to){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//跳过https的验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);////跳过https的验证
        curl_setopt($ch,CURLOPT_URL,$file_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $file_content = curl_exec($ch);
        curl_close($ch);
        $downloaded_file = fopen($save_to, 'w');
        fwrite($downloaded_file, $file_content);
        fclose($downloaded_file);
    }
    public function changeUserHeadCommon(){
        global $_G;
        include('./config/config_ucenter.php');
        //echo UC_KEY;exit;
        //define('UC_KEY', $_G['setting']['uc']['key']);
        $api = UC_API;//$_G['setting']['uc']['api'];
        $appid = UC_APPID ;//$_G['setting']['uc']['appid'];
        $uid = $_POST['userid'];
        $path = '/temp/';
        $img_path = $_POST['path'];
        $local = $path.$uid;
        $suffix = substr($img_path,strrpos($img_path,'.')+1);
        $file = $local.'.'.$suffix;
        rename($local,$file);
        $file_ext = dirname(__FILE__);
        //原图
        $imgfile = $file_ext.$path.'i_'.$uid.'.'.$suffix;
        $bigavatarfile = $file_ext.$path.'b_'.$uid.'.'.$suffix;
        $middleavatarfile = $file_ext.$path.'m_'.$uid.'.'.$suffix;
        $smallavatarfile = $file_ext.$path.'s_'.$uid.'.'.$suffix;
        //echo $bigavatarfile;die;
        $this -> dlfile($img_path,$imgfile);
        $this ->img2thumb($imgfile,$bigavatarfile, 200, 200,1,0);
        $this ->img2thumb($imgfile,$middleavatarfile, 120, 120 ,1,0);
        $this ->img2thumb($imgfile,$smallavatarfile, 48, 48,1,0);

        $formvars["avatar1"] = $this->imgTo16Code($bigavatarfile);
        $formvars["avatar2"] = $this->imgTo16Code($middleavatarfile);
        $formvars["avatar3"] = $this->imgTo16Code($smallavatarfile);
        $input = $this ->uc_api_input("uid=".$uid);
        $agent = md5($_SERVER['HTTP_USER_AGENT']);

        $action = $api.'/index.php?m=user&inajax=1&a=rectavatar&appid='.$appid.'&input='.$input.'&agent='.$agent.'&avatartype=virtual';
        $get_token_url = $action;
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$get_token_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//跳过https的验证
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);////跳过https的验证
        curl_setopt($ch,CURLOPT_HEADER,0);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($formvars));//所需传的数组用http_bulid_query()函数处理一下，就ok了
        $res = curl_exec($ch);
        curl_close($ch);
        //echo json_encode($_G['setting']);exit;
        //echo $res;exit;
        //echo time();die;
        file_exists($imgfile) && unlink($imgfile);
        file_exists($bigavatarfile) && unlink($bigavatarfile);
        file_exists($middleavatarfile) && unlink($middleavatarfile);
        file_exists($smallavatarfile) && unlink($smallavatarfile);

        //跟新用户头像字段
        DB::query("UPDATE ".DB::table('common_member')." SET `avatarstatus`=1 WHERE `uid`='".$uid."'");/*global $_G;
        echo json_encode($_G['setting']['ucenterurl']);EXIT;
        $ucroot = $_G['setting']['ucenterurl'];
        echo $ucroot;exit;
        $last = strrpos($ucroot,'/');
        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('IN_UC', TRUE);
        define('UC_ROOT', dirname(__FILE__).'/../../../../../'.substr($ucroot,$last+1).'/');
        define('UC_API', strtolower(($_SERVER['HTTPS'] == 'on' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'))));
        define('UC_DATADIR', UC_ROOT.'data/');
        define('UC_DATAURL', UC_API.'/data');
        define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());

        require_once './source/class/class_core.php';
        $discuz = C::app();
        $discuz->init();

        $uid = $_POST['userid'] ;
        $img_path = $_POST['path'] ;

        if(empty($uid)) {
            error('用户id为空',array(),'userid_empty');
        }
        if(empty($img_path)) {
            error('图片路径为空',array(),'imgpath_empty');
        }
        $home = get_home($uid);
        if(!is_dir(UC_DATADIR.'./avatar/'.$home)) {
            set_home($uid, UC_DATADIR.'./avatar/');
        }
        $avatartype = getgpc('avatartype', 'G') == 'real' ? 'real' : 'virtual';
        $bigavatarfile = UC_DATADIR.'./avatar/'.get_avatar($uid, 'big', $avatartype);
        $middleavatarfile = UC_DATADIR.'./avatar/'.get_avatar($uid, 'middle', $avatartype);
        $smallavatarfile = UC_DATADIR.'./avatar/'.get_avatar($uid, 'small', $avatartype);
        imagecropper($img_path,$bigavatarfile, 200, 200);
        imagecropper($img_path,$middleavatarfile, 120, 120);
        imagecropper($img_path,$smallavatarfile, 48, 48);

        $biginfo = @getimagesize($bigavatarfile);
        $middleinfo = @getimagesize($middleavatarfile);
        $smallinfo = @getimagesize($smallavatarfile);*/
        //if(!$biginfo || !$middleinfo || !$smallinfo || $biginfo[2] == 4 || $middleinfo[2] == 4 || $smallinfo[2] == 4
            //|| $biginfo[0] > 200 || $biginfo[1] > 250 || $middleinfo[0] > 120 || $middleinfo[1] > 120 || $smallinfo[0] > 48 || $smallinfo[1] > 48) {
            //file_exists($bigavatarfile) && unlink($bigavatarfile);
            //file_exists($middleavatarfile) && unlink($middleavatarfile);
            //file_exists($smallavatarfile) && unlink($smallavatarfile);
            //$success = 0;
        //}

        success('头像更换成功', avatar($uid, 'middle', true).'&random='.random(2));
    }
    public function imgTo16Code($filename){
        $file = file_get_contents($filename);
        $code = strtoupper(bin2hex($file));
        return $code;
    }

    public function uc_api_input($data) {
        $s = urlencode($this->uc_authcode($data.'&agent='.md5($_SERVER['HTTP_USER_AGENT'])."&time=".time(), 'ENCODE', UC_KEY));
        return $s;
    }
    public function uc_authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {
        //echo time();die;
        $ckey_length = 4;

        $key = md5($key ? $key : UC_KEY);
        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya.md5($keya.$keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;
        $string_length = strlen($string);

        $result = '';
        $box = range(0, 255);

        $rndkey = array();
        for($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if($operation == 'DECODE') {
            if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc.str_replace('=', '', base64_encode($result));
        }
    }
    public function changeUserHeadOutput(){

    }

    public function changePasswordCommon(){
        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('NOROBOT', TRUE);

        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';

        define('IN_UC', TRUE);
        $discuz = C::app();
        $discuz->init();

        include_once './uc_client/client.php';

        $account = $_POST['accountOrPhone'];
        $newpassword = $_POST['newpassword'];
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        if(is_phone_number($account)){
            $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_phone']."=".$account);
            if(!$usermobilerow[$_MAG_CONFIG['user_mobile_relations_userid']]){
                error('手机号未注册',array(),'user_not_exist');
            }
            $member = uc_get_user($usermobilerow[$_MAG_CONFIG['user_mobile_relations_userid']], 1);
        }else{
            $member = uc_get_user($account);
        }

        if(empty($member[0])) {
            error('用户不存在',array(),'user_not_exist');
        }
        $rs = uc_user_edit($member[1], $newpassword, $newpassword,'', 1, 0);
        success('', array());
    }
    public function changePasswordOutput(){

    }

    public function bindPhoneCommon(){
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        require_once './source/class/class_core.php';
        $discuz = C::app();
        $discuz->init();

        if(!is_phone_number($_POST['phone'])){
            error('手机号格式错误',array(),'phone_format_error');
        }
        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_phone']."=".$_POST['phone']);
        if($usermobilerow[$_MAG_CONFIG['user_mobile_relations_userid']]){
            DB::delete($_MAG_CONFIG['user_mobile_relations'],$_MAG_CONFIG['user_mobile_relations_phone']."=".$_POST['phone'],1);
        }
        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_userid']."=".$_POST['userid']);
        if($usermobilerow[$_MAG_CONFIG['user_mobile_relations_userid']]){
            DB::delete($_MAG_CONFIG['user_mobile_relations'],$_MAG_CONFIG['user_mobile_relations_userid']."=".$_POST['userid'],1);
        }

        $insert_data[$_MAG_CONFIG['user_mobile_relations_userid']] = $_POST['userid'];
        $insert_data[$_MAG_CONFIG['user_mobile_relations_phone']] = $_POST['phone'];
        if($_MAG_CONFIG['user_mobile_relations_create_time']){
            $insert_data[$_MAG_CONFIG['user_mobile_relations_create_time']] = time();
        }

        DB::insert($_MAG_CONFIG['user_mobile_relations'],$insert_data);
        DB::query("UPDATE ".DB::table('common_member_profile')." SET `mobile`=".daddslashes($_POST['phone'])." WHERE `uid`='".$_POST['userid']."'");
        success('', array());
    }

    public function bindPhoneOuput(){


    }

    public function unbindPhoneCommon(){
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        require_once './source/class/class_core.php';
        $discuz = C::app();
        $discuz->init();
        DB::query("UPDATE ".DB::table($_MAG_CONFIG['user_mobile_relations'])." SET ".$_MAG_CONFIG['user_mobile_relations_phone']."='' WHERE ".$_MAG_CONFIG['user_mobile_relations_userid']."=".$_POST['userid']);
        success('', array());
    }

    public function bindQQCommon(){
        require_once './source/class/class_core.php';
        $discuz = C::app();
        $discuz->init();
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        if(!($_POST['openid'])){
            error('openid不能为空',array(),'openid_empty_error');
        }
        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_qq_relations'])." WHERE ".$_MAG_CONFIG['user_qq_relations_openid']."='".$_POST['openid']."'");
        if($usermobilerow[$_MAG_CONFIG['user_qq_relations_userid']]){
            DB::delete($_MAG_CONFIG['user_qq_relations'],$_MAG_CONFIG['user_qq_relations_openid']."='".$_POST['openid']."'",1);
        }
        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_qq_relations'])." WHERE ".$_MAG_CONFIG['user_qq_relations_userid']."=".$_POST['userid']);
        if($usermobilerow[$_MAG_CONFIG['user_qq_relations_userid']]){
            DB::delete($_MAG_CONFIG['user_qq_relations'],$_MAG_CONFIG['user_qq_relations_userid']."=".$_POST['userid'],1);
        }
        $insertData[$_MAG_CONFIG['user_qq_relations_userid']] = $_POST['userid'];
        $insertData[$_MAG_CONFIG['user_qq_relations_openid']] = $_POST['openid'];
        if($_MAG_CONFIG['user_qq_relations_create_time']){
            $insertData[$_MAG_CONFIG['user_qq_relations_create_time']] =time();
        }
        if($_MAG_CONFIG['user_qq_relations_name']){
            $insertData[$_MAG_CONFIG['user_qq_relations_name']] = trim(strip_tags($_POST['name']));
        }
        DB::insert($_MAG_CONFIG['user_qq_relations'],$insertData);

        success('', array());

    }

    public function bindQQOuput(){


    }

    public function unbindQQCommon(){
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        require_once './source/class/class_core.php';
        $discuz = C::app();
        $discuz->init();
        DB::query("UPDATE ".DB::table($_MAG_CONFIG['user_qq_relations'])." SET ".$_MAG_CONFIG['user_qq_relations_openid']."='' WHERE ".$_MAG_CONFIG['user_qq_relations_userid']."=".$_POST['userid']);
        success('', array());
    }


    public function bindWeixinCommon(){
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        require_once './source/class/class_core.php';
        $discuz = C::app();
        $discuz->init();

        if(!($_POST['openid'] || $_POST['unionid'] )){
            error('openid或unionid不能为空',array(),'openid_unionid_empty_error');
        }
        if($_POST['unionid']){
            $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_weixin_relations'])." WHERE ".$_MAG_CONFIG['user_weixin_relations_unionid']."='".$_POST['unionid']."'");
            if($usermobilerow[$_MAG_CONFIG['user_weixin_relations_userid']]){
                DB::delete($_MAG_CONFIG['user_weixin_relations'],$_MAG_CONFIG['user_weixin_relations_unionid']."='".$_POST['unionid']."'",1);
            }
            $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_weixin_relations'])." WHERE ".$_MAG_CONFIG['user_weixin_relations_userid']."=".$_POST['userid']);
            if($usermobilerow[$_MAG_CONFIG['user_weixin_relations_userid']]){
                DB::delete($_MAG_CONFIG['user_weixin_relations'],$_MAG_CONFIG['user_weixin_relations_userid']."=".$_POST['userid'],1);
            }
            $insertData[$_MAG_CONFIG['user_weixin_relations_userid']] = $_POST['userid'];
            $insertData[$_MAG_CONFIG['user_weixin_relations_unionid']] = $_POST['unionid'];
            $insertData[$_MAG_CONFIG['user_weixin_relations_openid']] = $_POST['openid'];
            if($_MAG_CONFIG['user_weixin_relations_create_time']){
                $insertData[$_MAG_CONFIG['user_weixin_relations_create_time']]  = time();
            }
            if($_MAG_CONFIG['user_weixin_relations_name']){
                $insertData[$_MAG_CONFIG['user_weixin_relations_name']] = trim(strip_tags($_POST['name']));
            }
            DB::insert($_MAG_CONFIG['user_weixin_relations'],$insertData);

        }

        if($_POST['openid']){
            $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_weixin_relations'])." WHERE ".$_MAG_CONFIG['user_weixin_relations_openid']."='".$_POST['openid']."'");
            if($usermobilerow[$_MAG_CONFIG['user_weixin_relations_userid']]){
                DB::delete($_MAG_CONFIG['user_weixin_relations'],$_MAG_CONFIG['user_weixin_relations_openid']."='".$_POST['openid']."'",1);
            }
            $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_weixin_relations'])." WHERE ".$_MAG_CONFIG['user_weixin_relations_userid']."=".$_POST['userid']);
            if($usermobilerow[$_MAG_CONFIG['user_weixin_relations_userid']]){
                DB::delete($_MAG_CONFIG['user_weixin_relations'],$_MAG_CONFIG['user_weixin_relations_userid']."=".$_POST['userid'],1);
            }
            $insertData[$_MAG_CONFIG['user_weixin_relations_userid']] = $_POST['userid'];
            $insertData[$_MAG_CONFIG['user_weixin_relations_unionid']] = $_POST['unionid'];
            $insertData[$_MAG_CONFIG['user_weixin_relations_openid']] = $_POST['openid'];
            if($_MAG_CONFIG['user_weixin_relations_create_time']){
                $insertData[$_MAG_CONFIG['user_weixin_relations_create_time']]  = time();
            }
            if($_MAG_CONFIG['user_weixin_relations_name']){
                $insertData[$_MAG_CONFIG['user_weixin_relations_name']] = trim(strip_tags($_POST['name']));
            }
            DB::insert($_MAG_CONFIG['user_weixin_relations'],$insertData);
        }

        success('', array());
    }

    public function bindWeixinOuput(){


    }

    public function unbindWeixinCommon(){
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        require_once './source/class/class_core.php';
        $discuz = C::app();
        $discuz->init();
        DB::query("UPDATE ".DB::table($_MAG_CONFIG['user_weixin_relations'])." SET ".$_MAG_CONFIG['user_weixin_relations_unionid']."=''  , ".$_MAG_CONFIG['user_weixin_relations_openid']."=''WHERE ".$_MAG_CONFIG['user_weixin_relations_userid']."=".$_POST['userid']);
        success('', array());
    }

    public function userNoticeCountCommon(){
        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('NOROBOT', TRUE);

        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';
        require_once './uc_client/client.php';

        define('IN_UC', TRUE);
        $discuz = C::app();
        $discuz->init();

        global $_G;
        $notice  =  array(
            'newpm' => intval(uc_pm_checknew($_G['uid'])),
//           'newprompt' => dintval(($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) >= 0 ? ($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) : 0),
            'system' => dintval($_G['member']['category_num']['system']),
            'mypost' => dintval($_G['member']['category_num']['mypost']),
        );
        success('',$notice);
    }

    public function userNoticeCountOut(){


    }

    public function scoreAddCommon(){
        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('NOROBOT', TRUE);

        require_once './source/class/class_core.php';
        $discuz = C::app();
        $discuz->init();
        $uid = $_GET['userid'] ;
        global $_G;
        if(empty($uid)) {
            error('用户id为空',array(),'userid_empty');
        }
        $setarr = array();
        foreach($_G['setting']['extcredits'] as $id => $value) {
            if(isset($_GET['extcredits'.$id])) {
                if($_GET['updatecredittype'] == 0) {
                    $setarr['extcredits'.$id] = $_GET['extcredits'.$id];
                } else {
                    $setarr[] = 'extcredits'.$id;
                }
            }
        }

        $uids = array($uid);
        $allcount = C::t('common_member_count')->fetch_all($uids);
        $insertmember = array_diff($uids, array_keys($allcount));
        foreach($insertmember as $uid) {
            C::t('common_member_count')->insert(array('uid' => $uid));
        }
        if($_GET['updatecredittype'] == 0) {
            C::t('common_member_count')->increase($uids, $setarr);
        } else {
            C::t('common_member_count')->clear_extcredits($uids, $setarr);
        }
        success('',array());
    }

    public function scoreAddOutput(){


    }

    public function scoreSettingCommon(){
        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('NOROBOT', TRUE);
        global $_G;
        require_once './source/class/class_core.php';
        $discuz = C::app();
        $discuz->init();
        $extcredits = $_G['setting']['extcredits'];
        $setting = array();
        foreach($extcredits as $key => $value){
            $setting['extcredits'.$key] = $value['title'];
        }
        success('',$setting);

    }
    public function scoreSettingOutput(){
        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('NOROBOT', TRUE);
        global $_G;
        require_once './source/class/class_core.php';
        $discuz = C::app();
        $discuz->init();
        $extcredits = $_G['setting']['extcredits'];
        $setting = array();
        foreach($extcredits as $key => $value){
            $setting['extcredits'.$key] = $value['title'];
        }
        success('',$setting);
    }



    public function sexEditCommon(){
        global $_G;
        C::t('common_member_profile')->update($_G['uid'], array(
            'gender' => $_POST['sex']
        ));
        success('');
    }


    public function sexEditOuput(){

    }


    public function scoreRulesCommon(){
        global $_G;
        $scorerules = strip_tags($_G['setting']['creditsformulaexp']);
        success('',$scorerules);
    }


    public function scoreRulesOuput(){

    }


    public function userGroupsCommon(){
        $groups = C::t('common_usergroup')->range_orderby_creditshigher() ;
        $list = array();
        foreach ($groups as $key => $value){
            $row = array();
            $row['id'] = $value['groupid'];
            $row['name'] = $value['grouptitle'];
            $row['creditshigher'] = $value['creditshigher'];
            $row['creditslower'] = $value['creditslower'];
            $row['type'] = $value['type'];
            $row['level'] = $value['stars'];
            $list[] = $row;
        }
        success('',$list);
    }



    public function userGroupsOuput(){

    }


    public function loginByUseridCommon(){
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('NOROBOT', TRUE);

        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';

        define('IN_UC', TRUE);
        $discuz = C::app();
        $discuz->init();
        include_once './uc_client/client.php';
        global $_G;
        $uid = (int)$_POST['userid'];
        $cookietime = 31536000;
        if(($member = getuserbyuid($uid, 1))) {
            $_G['member'] = $member;
            setloginstatus($member,$cookietime);
            $auth = authcode("$member[password]\t$member[uid]", 'ENCODE');
            dsetcookie('auth', $auth, $cookietime);
            $_G['cookie']['auth'] = $auth;
            $_G['username'] = $member[username];
            $_G['uid'] = $member[uid];
        }else{
            error('用户不存在',array(),'login_error');
        }
        
        if(in_array('mobileoem', $_G['setting']['plugins']['available'])) {
            $check = C::t('#mobileoem#mobileoem_member')->fetch($_G['uid']);
        }
        $membercredit = 0;
        $member = $_G['member'];
        $tableext = isset($member['_inarchive']) ? '_archive' : '';
        $membercount = C::t('common_member_count'.$tableext)->fetch($_G['uid']);
        $membergroup = C::t('common_usergroup')->fetch($member['groupid']);
        $member = array_merge($member, $membercount, $membergroup);
        //		echo json_encode($member);exit;
        eval("\$membercredit = @round({$_G[setting][creditsformula]});");
        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_userid']."=".$member['uid']);

        $globals = array(
            'cookiepre' => $_G['config']['cookie']['cookiepre'],
            'auth' => $_G['cookie']['auth'],
            'saltkey' => $_G['cookie']['saltkey'],
            'time' => time(),
            'member_uid' => $member['uid'],
            'member_username' => $member['username'],
            'member_phone' => $usermobilerow[$_MAG_CONFIG['user_mobile_relations_phone']] ? $usermobilerow[$_MAG_CONFIG['user_mobile_relations_phone']] :'',
            'member_sex' => $member['gender'],
            'member_avatar' => avatar($member['uid'], 'small', true),
            'groupid' => $_G['groupid'],
            'grouptitle' => $member['grouptitle'],
            'credits' => $membercredit,
            'formhash' => formhash(),
            'ismoderator' => $_G['forum']['ismoderator'],
            'readaccess' => $_G['group']['readaccess'],
            'key1' => $_G['authkey'],
            'key' => md5($_G['authkey'].$_SERVER['HTTP_USER_AGENT']),
            //			'G' => $_G,
            'notice' => array(
                'newpush' => $check['newpush'] ? 1 : 0,
                'newpm' => dintval($_G['member']['newpm']),
                'newprompt' => dintval(($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) >= 0 ? ($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) : 0),
                'newmypost' => dintval($_G['member']['category_num']['mypost']),
            )
        );

        $retrundata =  $globals;

        success('', $retrundata);
    }


    public function setUserLoginCommon(){
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        $HTTP_MAG_DZ_API_SALT = $_MAG_CONFIG['HTTP_MAG_DZ_API_SALT'];

        $uid = $_GET['uid'];
        $asynAskTime = $_GET['t'];
        $asynAskKey = $_GET['k'];

        if(abs(time() - $asynAskTime ) >60){
            //超时
            error('t',array());
        }
        if($asynAskKey != md5($asynAskTime.($HTTP_MAG_DZ_API_SALT).'Mag123!@#'.$uid)){
            //校验出错
            error('k',array());
        }

        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('NOROBOT', TRUE);

        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';

        define('IN_UC', TRUE);
        $discuz = C::app();
        $discuz->init();
        include_once './uc_client/client.php';
        global $_G;
        $cookietime = 3600*24*365*10;
        $member = getuserbyuid($uid, 1);
        $_G['member'] = $member;
        setloginstatus($member,$cookietime);
        $auth = authcode("$member[password]\t$member[uid]", 'ENCODE');
        dsetcookie('auth', $auth, $cookietime);
        success('',array());
    }

    public function qqLoginConnectCommon(){
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        $HTTP_MAG_DZ_API_SALT = $_MAG_CONFIG['HTTP_MAG_DZ_API_SALT'];
        define('APPTYPEID', 0);
        define('CURSCRIPT', 'ucenter');
        define('NOROBOT', TRUE);

        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';

        define('IN_UC', TRUE);
        $discuz = C::app();
        $discuz->init();
        include_once './uc_client/client.php';
        global $_G;
        $time = time();
        $token = json_encode(array('uid' => $_G['uid'],'t' => $time,'k' => md5($time.($HTTP_MAG_DZ_API_SALT).'Magqq!@#'.$_G['uid'])));
        echo  <<<EOF
        <script>
           (function(){function iosConnect(callback){if(window.MagAndroidClient){return}if(window.WebViewJavascriptBridge){return callback(WebViewJavascriptBridge)}else{document.addEventListener("WebViewJavascriptBridgeReady",function(evt){callback(WebViewJavascriptBridge)},false)}if(window.WVJBCallbacks){return window.WVJBCallbacks.push(callback)}else{window.WVJBCallbacks=[callback];var WVJBIframe=document.createElement("iframe");WVJBIframe.style.display="none";WVJBIframe.src="wvjbscheme://__BRIDGE_LOADED__";document.documentElement.appendChild(WVJBIframe);setTimeout(function(){document.documentElement.removeChild(WVJBIframe)},0)}}iosConnect(function(bridge){if(bridge.init&&typeof bridge.init==="function"){bridge.init(function(message,responseCallback){})}bridge.registerHandler("jsCallBack",function(data,responseCallback){var call=JSON.parse(data);var id=call.id;var val=call.val;var callback=mag.callbacks[id];if(callback){if(callback.type&&callback.type=="json"){if(val){val=JSON.parse(val)}}callback.success(val)}})});mag={VERSION:"1.0",ready:function(fun){iosConnect(function(){fun()});if(window.MagAndroidClient){fun()}},callbacks:{},iosConnect:iosConnect,jsCallBack:function(id,val){var callback=mag.callbacks[id];if(callback){if(callback.type&&callback.type=="json"){if(val){val=JSON.parse(val)}}callback.success(val)}},qqConnectLogin:function(token){if(window.MagAndroidClient){window.MagAndroidClient.qqConnectLogin(token)}iosConnect(function(bridge){bridge.callHandler("qqConnectLogin",token,function(rs){})})}};window.mag=mag;mag.VERSION="1.0"})();
            </script>
EOF;
        echo "<script >mag.qqConnectLogin('".$token."')</script>" ;
    }

    public function qqLoginConnectCheckCommon(){
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        $HTTP_MAG_DZ_API_SALT = $_MAG_CONFIG['HTTP_MAG_DZ_API_SALT'];

        $uid = $_GET['uid'];
        $asynAskTime = $_GET['t'];
        $asynAskKey = $_GET['k'];

        if(abs(time() - $asynAskTime ) >60){
            //超时
            error('t',array());
        }
        if($asynAskKey != md5($asynAskTime.($HTTP_MAG_DZ_API_SALT).'Magqq!@#'.$uid)){
            //校验出错
            error('k',array());
        }
        success('',array());
    }

    public function userSearchCommon(){
        $keywords = $_GET['keywords'];
        if(empty($keywords)){
            error('请输入关键字');
        }
        $page = $_GET['p'] <1 || $_GET['p'] >100  ? 1: (int)$_GET['p'];
        $step = (int)$_GET['step'] > 0 ?  (int)$_GET['step'] : 10;
        $sql =  "select * from ". DB::table('common_member') . " where username like '%".stripsearchkey($keywords)."%' ". DB::limit( ($page-1)*$step, $step);
//        echo $sql;exit;
        $rs = DB::fetch_all($sql)	;
        $list = array();
        if(is_array($rs)){
            foreach ($rs as $key => $value){
                $user['user_id'] = $value['uid'];
                $user['user_name'] = $value['username'];
                $user['user_head'] =  avatar($value[ 'uid' ], 'small', true);
                $list[] = $user;
            }
        }
        success('',$list);
    }



    public function forceRegistCommon(){
        global $_G;
        $newusername = trim($_POST['username']);
        $newpassword = $_POST['password'];
        $newemail =  time() . rand(100000, 999999) . '@qq.com';
        $mobile = $_POST['mobile'];

        if(!is_phone_number($_POST['mobile'])){
            error('手机号格式错误',array(),'phone_format_error');
        };
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        $usermobilerow = DB::fetch_first("SELECT * FROM " .DB::table($_MAG_CONFIG['user_mobile_relations'])." WHERE ".$_MAG_CONFIG['user_mobile_relations_phone']."=".$_POST['mobile']);
        if($usermobilerow[$_MAG_CONFIG['user_mobile_relations_userid']]){
            $userrow = DB::fetch_first("SELECT * FROM " .DB::table('common_member')." WHERE uid=".$usermobilerow[$_MAG_CONFIG['user_mobile_relations_userid']]);
            if(!$userrow['uid']){
                DB::delete($_MAG_CONFIG['user_mobile_relations'],$_MAG_CONFIG['user_mobile_relations_phone']."=".$_POST['mobile'],1);
            }else{
                error('手机号已注册',array(),'phone_is_exist_error');
            }
        }


        $_GET['newgroupid'] = 10;
        lang('admincp_msg');
        if(C::t('common_member')->fetch_uid_by_username($newusername) || C::t('common_member_archive')->fetch_uid_by_username($newusername)) {
            //            cpmsg('members_add_username_duplicate', '', 'error');
            error($_G[ 'lang' ][ 'admincp_msg' ]['members_add_username_duplicate']);
        }
        loaducenter();
        $uid = uc_user_register(addslashes($newusername), $newpassword, $newemail);
        if($uid <= 0) {
            if($uid == -1) {
                error($_G[ 'lang' ][ 'admincp_msg' ]['members_add_illegal']);
            } elseif($uid == -2) {
                error($_G[ 'lang' ][ 'admincp_msg' ]['members_username_protect']);
            } elseif($uid == -3) {
                if(empty($_GET['confirmed'])) {
                    error($_G[ 'lang' ][ 'admincp_msg' ]['members_add_username_activation']);
                } else {
                    list($uid,, $newemail) = uc_get_user(addslashes($newusername));
                }
            } elseif($uid == -4) {
                error($_G[ 'lang' ][ 'admincp_msg' ]['members_email_illegal']);
            } elseif($uid == -5) {
                error($_G[ 'lang' ][ 'admincp_msg' ]['members_email_domain_illegal']);
            } elseif($uid == -6) {
                error($_G[ 'lang' ][ 'admincp_msg' ]['members_email_duplicate']);
            }
        }

        $group = C::t('common_usergroup')->fetch($_GET['newgroupid']);
        $newadminid = in_array($group['radminid'], array(1, 2, 3)) ? $group['radminid'] : ($group['type'] == 'special' ? -1 : 0);
        if($group['radminid'] == 1) {
            error($_G[ 'lang' ][ 'admincp_msg' ]['members_add_admin_none']);
        }
        if(in_array($group['groupid'], array(5, 6, 7))) {
            error($_G[ 'lang' ][ 'admincp_msg' ]['members_add_ban_all_none']);
        }
        $profile = $verifyarr = array();
        loadcache('fields_register');
        $init_arr = explode(',', $_G['setting']['initcredits']);
        $password = md5(random(10));
        C::t('common_member')->insert($uid, $newusername, $password, $newemail, 'Manual Acting', $_GET['newgroupid'], $init_arr, $newadminid);
        require_once libfile('function/cache');
//        updatecache('setting');

        $_G['member']['uid'] = $uid;
        //手机号绑定
        $insertData[$_MAG_CONFIG['user_mobile_relations_userid']] =  $_G['member']['uid'];
        $insertData[$_MAG_CONFIG['user_mobile_relations_phone']] =  $_POST['mobile'];
        if($_MAG_CONFIG['user_mobile_relations_create_time']){
            $insertData[$_MAG_CONFIG['user_mobile_relations_create_time']] = time();
        }
        DB::insert($_MAG_CONFIG['user_mobile_relations'],$insertData);

        if(in_array('mobileoem', $_G['setting']['plugins']['available'])) {
            $check = C::t('#mobileoem#mobileoem_member')->fetch($_G['uid']);
        }

        $member = getuserbyuid($uid, 1);
        $cookietime = 31536000;
        setloginstatus($member,$cookietime);

        $auth = authcode("$member[password]\t$member[uid]", 'ENCODE');
        dsetcookie('auth', $auth, $cookietime);
        $_G['cookie']['auth'] = $auth;
        $_G['username'] = $member['username'];
        $_G['uid'] = $member['uid'];

        $membercredit = 0;
        $tableext = isset($member['_inarchive']) ? '_archive' : '';
        $membercount = C::t('common_member_count'.$tableext)->fetch($_G['uid']);
        $membergroup = C::t('common_usergroup')->fetch($member['groupid']);
        $member = array_merge($member, $membercount, $membergroup);
        //		echo json_encode($member);exit;
        eval("\$membercredit = @round({$_G[setting][creditsformula]});");
        //更新用户手机字段
        DB::query("UPDATE ".DB::table('common_member_profile')." SET `mobile`=".daddslashes($_POST['mobile'])." WHERE `uid`='".$member['uid']."'");
        $_G['setting']['avatarmethod'] = 0;
        $globals = array(
            'cookiepre' => $_G['config']['cookie']['cookiepre'],
            'auth' => $_G['cookie']['auth'],
            'time' => time(),
            'saltkey' => $_G['cookie']['saltkey'],
            'member_uid' => $member['uid'],
            'member_username' => $member['username'],
            'member_sex' => $member['gender'],
            'member_avatar' => avatar($member['uid'], 'small', true),
            'groupid' => $_G['groupid'],
            'grouptitle' => $member['grouptitle'],
            'credits' => $membercredit,
            'formhash' => formhash(),
            'ismoderator' => $_G['forum']['ismoderator'],
            'readaccess' => $_G['group']['readaccess'],
            'key1' => $_G['authkey'],
            'key' => md5($_G['authkey'].$_SERVER['HTTP_USER_AGENT']),
            //			'G' => $_G,
            'notice' => array(
                'newpush' => $check['newpush'] ? 1 : 0,
                'newpm' => dintval($_G['member']['newpm']),
                'newprompt' => dintval(($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) >= 0 ? ($_G['member']['newprompt'] - $_G['member']['category_num']['mypost']) : 0),
                'newmypost' => dintval($_G['member']['category_num']['mypost']),
            )
        );

        $retrundata =  $globals;
        success('注册成功', $retrundata);
    }

    public function userAddCommon(){
        global $_G;
        $newusername = trim($_GET['user_name']);
        $newpassword = 123456;
        $newemail = time().rand(1000,9999).'@magapp.com';
        $_GET['newgroupid'] = 10;
        lang('admincp_msg');
        if(C::t('common_member')->fetch_uid_by_username($newusername) || C::t('common_member_archive')->fetch_uid_by_username($newusername)) {
//            cpmsg('members_add_username_duplicate', '', 'error');
            error($_G[ 'lang' ][ 'admincp_msg' ]['members_add_username_duplicate']);
        }

        loaducenter();

        $uid = uc_user_register(addslashes($newusername), $newpassword, $newemail);
        if($uid <= 0) {
            if($uid == -1) {
                error($_G[ 'lang' ][ 'admincp_msg' ]['members_add_illegal']);
            } elseif($uid == -2) {
                error($_G[ 'lang' ][ 'admincp_msg' ]['members_username_protect']);
            } elseif($uid == -3) {
                if(empty($_GET['confirmed'])) {
                    error($_G[ 'lang' ][ 'admincp_msg' ]['members_add_username_activation']);
                } else {
                    list($uid,, $newemail) = uc_get_user(addslashes($newusername));
                }
            } elseif($uid == -4) {
                error($_G[ 'lang' ][ 'admincp_msg' ]['members_email_illegal']);
            } elseif($uid == -5) {
                error($_G[ 'lang' ][ 'admincp_msg' ]['members_email_domain_illegal']);
            } elseif($uid == -6) {
                error($_G[ 'lang' ][ 'admincp_msg' ]['members_email_duplicate']);
            }
        }

        $group = C::t('common_usergroup')->fetch($_GET['newgroupid']);
        $newadminid = in_array($group['radminid'], array(1, 2, 3)) ? $group['radminid'] : ($group['type'] == 'special' ? -1 : 0);
        if($group['radminid'] == 1) {
            error($_G[ 'lang' ][ 'admincp_msg' ]['members_add_admin_none']);
        }
        if(in_array($group['groupid'], array(5, 6, 7))) { 
            error($_G[ 'lang' ][ 'admincp_msg' ]['members_add_ban_all_none']);
        }
        $profile = $verifyarr = array();
        loadcache('fields_register');
        $init_arr = explode(',', $_G['setting']['initcredits']);
        $password = md5(random(10));
        C::t('common_member')->insert($uid, $newusername, $password, $newemail, 'Manual Acting', $_GET['newgroupid'], $init_arr, $newadminid);
        require_once libfile('function/cache');
        updatecache('setting');
        success('用户添加成功',array(
            'username' => $newusername,
            'uid' => $uid
        ));

    }
}



function is_phone_number($phonenumber){
    $MAG_CONFIG = MagConfig::$_MAG_CONFIG;
    $rule = "/^1[34578]{1}\d{9}$/";
    if ($MAG_CONFIG['is_foreign']) { //海外客户
        $rule = "/^[0-9]{7,15}$/";
    }
    if(preg_match($rule, $phonenumber)){
        return true;
    }else{
        return false;
    }
}



function get_avatar($uid, $size = 'big', $type = '') {
    $size = in_array($size, array('big', 'middle', 'small')) ? $size : 'big';
    $uid = abs(intval($uid));
    $uid = sprintf("%09d", $uid);
    $dir1 = substr($uid, 0, 3);
    $dir2 = substr($uid, 3, 2);
    $dir3 = substr($uid, 5, 2);
    $typeadd = $type == 'real' ? '_real' : '';
    return  $dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).$typeadd."_avatar_$size.jpg";
}

function get_home($uid) {
    $uid = sprintf("%09d", $uid);
    $dir1 = substr($uid, 0, 3);
    $dir2 = substr($uid, 3, 2);
    $dir3 = substr($uid, 5, 2);
    return $dir1.'/'.$dir2.'/'.$dir3;
}

function set_home($uid, $dir = '.') {
    $uid = sprintf("%09d", $uid);
    $dir1 = substr($uid, 0, 3);
    $dir2 = substr($uid, 3, 2);
    $dir3 = substr($uid, 5, 2);
    !is_dir($dir.'/'.$dir1) && mkdir($dir.'/'.$dir1, 0777);
    !is_dir($dir.'/'.$dir1.'/'.$dir2) && mkdir($dir.'/'.$dir1.'/'.$dir2, 0777);
    !is_dir($dir.'/'.$dir1.'/'.$dir2.'/'.$dir3) && mkdir($dir.'/'.$dir1.'/'.$dir2.'/'.$dir3, 0777);
}


function imagecropper($source_path,$save_path, $target_width, $target_height)
{
    $source_info   = getimagesize($source_path);
    $source_width  = $source_info[0];
    $source_height = $source_info[1];
    $source_mime   = $source_info['mime'];
    $source_ratio  = $source_height / $source_width;
    $target_ratio  = $target_height / $target_width;

    // 源图过高
    if ($source_ratio > $target_ratio)
    {
        $cropped_width  = $source_width;
        $cropped_height = $source_width * $target_ratio;
        $source_x = 0;
        $source_y = ($source_height - $cropped_height) / 2;
    }
    // 源图过宽
    elseif ($source_ratio < $target_ratio)
    {
        $cropped_width  = $source_height / $target_ratio;
        $cropped_height = $source_height;
        $source_x = ($source_width - $cropped_width) / 2;
        $source_y = 0;
    }
    // 源图适中
    else
    {
        $cropped_width  = $source_width;
        $cropped_height = $source_height;
        $source_x = 0;
        $source_y = 0;
    }

    switch ($source_mime)
    {
        case 'image/gif':
            $source_image = imagecreatefromgif($source_path);
            break;

        case 'image/jpeg':
            $source_image = imagecreatefromjpeg($source_path);
            break;

        case 'image/png':
            $source_image = imagecreatefrompng($source_path);
            break;

        default:
            return false;
            break;
    }

    $target_image  = imagecreatetruecolor($target_width, $target_height);
    $cropped_image = imagecreatetruecolor($cropped_width, $cropped_height);

    // 裁剪
    imagecopy($cropped_image, $source_image, 0, 0, $source_x, $source_y, $cropped_width, $cropped_height);
    // 缩放
    imagecopyresampled($target_image, $cropped_image, 0, 0, 0, 0, $target_width, $target_height, $cropped_width, $cropped_height);

    imagejpeg($target_image, $save_path, 100);
    imagedestroy($source_image);
    imagedestroy($target_image);
    imagedestroy($cropped_image);
}