<?php
/**
 * 南京灵衍信息科技有限公司
 * User: wangxiao
 * Date: 2016/11/23
 * Time: 15:51
 */



$_GET['mod'] = 'spacecp';
$_GET['ac'] = empty($_GET['ac']) ? 'profile' : $_GET['ac'] ;
include_once dirname(__file__).'/../../../../../home.php';



class home{

    public function sendnoticeCommon(){
        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';
        $discuz = C::app();

        define('IN_UC', TRUE);
        $discuz->init();
        require_once './uc_client/client.php';
        $_GET['mod'] = 'space';
        $do = $_GET['do'] = empty($_GET['do']) ? 'notice' : $_GET['do'] ;


        require_once libfile('function_home', 'function');

        $uid = $_POST['uid'];
        $msg = $_POST['msg'];
        $link = $_POST['link'];
        if(empty($uid)){
            error('用户id不能为空',array(),'userid_empty');
        }
        $addmsg = '';
        if($link){
            $addmsg = '   <a href="'.$link.'">查看</a>';
        }
        notification_add($uid, 'system', 'system_notice',
            $rs = array('subject' => $msg .$addmsg,
                'message' => '',
                'from_id' => 0,
                'from_idtype' => 'sendnotice'), 1);
        if($rs){
            $rs['link'] = $link;
            $rs['text'] = strip_tags($msg.$addmsg);
        }else{
            $rs = array();
        }
        success('', $rs);

    }


    public function sendnoticeOutput(){


    }

    public function listNoticeCommon(){
        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';
        $discuz = C::app();
        global $_G;
        define('IN_UC', TRUE);
        $discuz->init();
        require_once './uc_client/client.php';
        $_GET['mod'] = 'space';
        $do = $_GET['do'] = empty($_GET['do']) ? 'notice' : $_GET['do'] ;


        require_once libfile('function_home', 'function');

        $perpage = 30;
        $perpage = mob_perpage($perpage);
        $page = empty($_GET['page'])?0:intval($_GET['page']);
        if($page<1) $page = 1;
        $start = ($page-1)*$perpage;

        ckstart($start, $perpage);

        $list = array();
        $mynotice = $count = 0;
        $multi = '';

        if(empty($_G['member']['category_num']['manage']) && !in_array($_G['adminid'], array(1,2,3))) {
            unset($_G['notice_structure']['manage']);
        }
        $view =  'mypost';
        $actives = array($view=>' class="a"');
        $opactives[$view] = 'class="a"';
        $categorynum = $newprompt = array();
        if(!empty($_GET['ignore'])) {
            C::t('home_notification')->ignore($_G['uid']);
        }

        foreach (array('wall', 'piccomment', 'blogcomment', 'clickblog', 'clickpic', 'sharecomment', 'doing', 'friend', 'credit', 'bbs', 'system', 'thread', 'task', 'group') as $key) {
            $noticetypes[$key] = lang('notification', "type_$key");
        }
 
        $category = $type = '';
        if(isset($_G['notice_structure'][$view])) {
            if(!in_array($view, array('mypost', 'interactive'))) {
                $category = $view;
            } else {
                $deftype = $_G['notice_structure'][$view][0];
                if($_G['member']['newprompt_num']) {
                    foreach($_G['notice_structure'][$view] as $subtype) {
                        if($_G['member']['newprompt_num'][$subtype]) {
                            $deftype = $subtype;
                            break;
                        }
                    }
                }
                $type = in_array($_GET['type'], $_G['notice_structure'][$view]) ? trim($_GET['type']) : $deftype;
            }
        }
        $wherearr = array();
        $new = -1;
        if(!empty($type)) {
            $wherearr[] = "`type`='$type'";
        }

        $sql = ' AND '.implode(' AND ', $wherearr);


        $newnotify = true;
        $count = C::t('home_notification')->count_by_uid($_G['uid'], $new, $type, $category);
        if($count) {
            if($new == 1 && $perpage == 30) {
                $perpage = 200;
            }
            foreach(C::t('home_notification')->fetch_all_by_uid($_G['uid'], $new, $type, $start, $perpage, $category) as $value) {
                if($value['new']) {
                    $newnotify = true;
                    $value['style'] = 'color:#000;font-weight:bold;';
                } else {
                    $value['style'] = '';
                }
                $value['rowid'] = '';
                if(in_array($value['type'], array('friend', 'poke'))) {
                    $value['rowid'] = ' id="'.($value['type'] == 'friend' ? 'pendingFriend_' : 'pokeQuery_').$value['authorid'].'" ';
                }
                if($value['from_num'] > 0) $value['from_num'] = $value['from_num'] - 1;
                $list[$value['id']] = $value;
            }

            $multi = '';
            $multi = multi($count, $perpage, $page, "home.php?mod=space&do=$do&isread=1");
        }
        //		echo json_encode($list);exit;
        /*if($newnotify) {
            C::t('home_notification')->ignore($_G['uid'], $type, $category, true, true);
            if($_G['setting']['cloud_status']) {
                $noticeService = Cloud::loadClass('Service_Client_Notification');
                $noticeService->setNoticeFlag($_G['uid'], TIMESTAMP);
            }
        }*/
        helper_notification::update_newprompt($_G['uid'], ($type ? $type : $category));
        if($_G['setting']['my_app_status']) {
            $mynotice = C::t('common_myinvite')->count_by_touid($_G['uid']);
        }
        if($_G['member']['newprompt']) {
            $recountprompt = 0;
            foreach($_G['member']['category_num'] as $promptnum) {
                $recountprompt += $promptnum;
            }
            $recountprompt += $mynotice;
            if($recountprompt == 0) {
                C::t('common_member')->update($_G['uid'], array('newprompt' => 0));
            }
        }

        $noticelist = array();
        if(!empty($list)){
            foreach($list as $value){
                $noticerow = array();
                preg_match_all("/<a.*?href=\"(.+?)\"/", $value['note'], $matches);
                //			echo json_encode($matches);exit;
                $link= $matches[1][1] ;
                if(strpos($link,'mod=redirect&goto=findpost')){
                    preg_match_all("/(\w+=\w+)(#\w+)?/i", $link, $mpid);
//                    $noticerow['link'] = 'magapp://threadView?tid='.str_replace('ptid=','',$mpid[0][2]);
                    $noticerow['link'] = '/mag/circle/v1/forum/threadViewPage?tid='.str_replace('ptid=','',$mpid[0][2]);
                }else{
                    $noticerow['link'] = site_index_url.'/'.$link ;
                }
                $noticerow['text'] = strip_tags($value['note']) ;
                $noticerow['dateline'] = $value['dateline'] ;
                $noticerow['type'] = $value['type'] ;
                $noticerow['is_new'] = $value['new'];
                $noticelist[] = $noticerow;
            }
        }
        success('', $noticelist);
    }

    public function listNoticeOuput(){


    }


    public function listUnreadMyPostNoticeCommon(){
        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';
        $discuz = C::app();
        global $_G;
        define('IN_UC', TRUE);
        $discuz->init();
        require_once './uc_client/client.php';
        $_GET['mod'] = 'space';
        $do = $_GET['do'] = empty($_GET['do']) ? 'notice' : $_GET['do'] ;


        require_once libfile('function_home', 'function');

        $perpage = 100;
        $page = 1;
        $start = ($page-1)*$perpage;

        ckstart($start, $perpage);

        $list = array();
        $mynotice = $count = 0;
        $multi = '';

        if(empty($_G['member']['category_num']['manage']) && !in_array($_G['adminid'], array(1,2,3))) {
            unset($_G['notice_structure']['manage']);
        }
        $view =  'mypost';
        $actives = array($view=>' class="a"');
        $opactives[$view] = 'class="a"';
        $categorynum = $newprompt = array();
        if(!empty($_GET['ignore'])) {
            C::t('home_notification')->ignore($_G['uid']);
        }

        foreach (array('wall', 'piccomment', 'blogcomment', 'clickblog', 'clickpic', 'sharecomment', 'doing', 'friend', 'credit', 'bbs', 'system', 'thread', 'task', 'group') as $key) {
            $noticetypes[$key] = lang('notification', "type_$key");
        }

        $category = $type = '';
        if(isset($_G['notice_structure'][$view])) {
            if(!in_array($view, array('mypost', 'interactive'))) {
                $category = $view;
            } else {
                $deftype = $_G['notice_structure'][$view][0];
                if($_G['member']['newprompt_num']) {
                    foreach($_G['notice_structure'][$view] as $subtype) {
                        if($_G['member']['newprompt_num'][$subtype]) {
                            $deftype = $subtype;
                            break;
                        }
                    }
                }
                $type = in_array($_GET['type'], $_G['notice_structure'][$view]) ? trim($_GET['type']) : $deftype;
            }
        }
        $wherearr = array();
        $new = -1;
        if(!empty($type)) {
            $wherearr[] = "`type`='$type'";
        }

        $sql = ' AND '.implode(' AND ', $wherearr);


        $newnotify = true;
        $count = C::t('home_notification')->count_by_uid($_G['uid'], $new, $type, $category);
        if($count) {
            if($new == 1 && $perpage == 30) {
                $perpage = 200;
            }
            foreach(C::t('home_notification')->fetch_all_by_uid($_G['uid'], $new, $type, $start, $perpage, $category) as $value) {
                if($value['new']) {
                    $newnotify = true;
                    $value['style'] = 'color:#000;font-weight:bold;';
                } else {
                    $value['style'] = '';
                }
                $value['rowid'] = '';
                if(in_array($value['type'], array('friend', 'poke'))) {
                    $value['rowid'] = ' id="'.($value['type'] == 'friend' ? 'pendingFriend_' : 'pokeQuery_').$value['authorid'].'" ';
                }
                if($value['from_num'] > 0) $value['from_num'] = $value['from_num'] - 1;
                $list[$value['id']] = $value;
            }

        }
        //		echo json_encode($list);exit;
        /*if($newnotify) {
            C::t('home_notification')->ignore($_G['uid'], $type, $category, true, true);
            if($_G['setting']['cloud_status']) {
                $noticeService = Cloud::loadClass('Service_Client_Notification');
                $noticeService->setNoticeFlag($_G['uid'], TIMESTAMP);
            }
        }*/
        helper_notification::update_newprompt($_G['uid'], ($type ? $type : $category));
        if($_G['setting']['my_app_status']) {
            $mynotice = C::t('common_myinvite')->count_by_touid($_G['uid']);
        }
        if($_G['member']['newprompt']) {
            $recountprompt = 0;
            foreach($_G['member']['category_num'] as $promptnum) {
                $recountprompt += $promptnum;
            }
            $recountprompt += $mynotice;
            if($recountprompt == 0) {
                C::t('common_member')->update($_G['uid'], array('newprompt' => 0));
            }
        }

        $noticelist = array();
        if(!empty($list)){
            foreach($list as $value){
                if($value['new'] == 0){
                   // continue;
                }
                if($value['dateline'] < $_GET['last_time_line']){
                    continue;
                }
                $noticerow = array();
                preg_match('/<div class=\"quote\".*?>.*?<\/div>/ism', $value[ 'note' ], $quotematches);
                if($quotematches[0]){
                    $noticerow['quote'] = strip_tags($quotematches[0]);
                    $value['note'] = str_replace($quotematches[0],'',$value['note']);
                }else{
                    $noticerow['quote'] = '';
                }

                preg_match_all("/<a.*?href=\"(.+?)\">(.+?)<\/a>/", $value['note'], $matches);
//                			echo json_encode($matches);exit;
                foreach($matches[1] as $linkkey => $linkvalue){
                    $link= $linkvalue ;
//                    echo $link;exit;
                    if(strpos($link,'mod=redirect&goto=findpost')){
                        preg_match_all("/(\w+=\w+)(#\w+)?/i", $link, $mpid);
//                        echo json_encode($mpid);exit;
//                        $noticerow['link'] = 'magapp://threadView?tid='.str_replace('ptid=','',$mpid[0][2]);
                        $noticerow['link'] = '/mag/circle/v1/forum/threadViewPage?tid='.str_replace('ptid=','',$mpid[0][2]).'&reply_id='.str_replace('pid=','',$mpid[0][3]);
                        $noticerow['title'] = $matches[2][$linkkey];
                        $value['note'] = str_replace($noticerow['title'],'',$value['note']);
                        break;
                    }else if(strpos($link,'mod=viewthread')){
                        preg_match_all("/(\w+=\w+)(#\w+)?/i", $link, $mpid);
//                        echo json_encode($mpid);exit;
//                        $noticerow['link'] = 'magapp://threadView?tid='.str_replace('tid=','',$mpid[0][1]);
                        $noticerow['link'] = '/mag/circle/v1/forum/threadViewPage?tid='.str_replace('tid=','',$mpid[0][1]);
                        $noticerow['title'] = $matches[2][$linkkey];
                        $value['note'] = str_replace($noticerow['title'],'',$value['note']);
                        break;
                    }else{
                        $noticerow['link'] = site_index_url.'/'.$link ;
                    }
                }

                $noticerow['id'] = (int)$value['id'] ;
                $noticerow['user_head'] = avatar($value[ 'authorid' ], 'small', true);
                $noticerow['title'] = strip_tags($noticerow['title']) ;
                $noticerow['content'] = strip_tags($value['note']) ;
                $noticerow['create_time'] = (int)$value['dateline'] ;
                $noticerow['type'] = $value['type'] ;
                $noticerow['is_new'] = $value['new'] ;
                $noticelist[] = $noticerow;
            }
        }
        success('', $noticelist);
    }


    public function listUnreadSystemNoticeCommon(){
        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';
        $discuz = C::app();
        global $_G;
        define('IN_UC', TRUE);
        $discuz->init();
        require_once './uc_client/client.php';
        $_GET['mod'] = 'space';
        $do = $_GET['do'] = empty($_GET['do']) ? 'notice' : $_GET['do'] ;


        require_once libfile('function_home', 'function');

        $perpage = 100;
        $page = 1;
        $start = ($page-1)*$perpage;

        ckstart($start, $perpage);

        $list = array();
        $mynotice = $count = 0;
        $multi = '';

        if(empty($_G['member']['category_num']['manage']) && !in_array($_G['adminid'], array(1,2,3))) {
            unset($_G['notice_structure']['manage']);
        }
        $view =  'system';
        $actives = array($view=>' class="a"');
        $opactives[$view] = 'class="a"';
        $categorynum = $newprompt = array();
        if(!empty($_GET['ignore'])) {
            C::t('home_notification')->ignore($_G['uid']);
        }

        foreach (array('wall', 'piccomment', 'blogcomment', 'clickblog', 'clickpic', 'sharecomment', 'doing', 'friend', 'credit', 'bbs', 'system', 'thread', 'task', 'group') as $key) {
            $noticetypes[$key] = lang('notification', "type_$key");
        }

        $category = $type = '';
        if(isset($_G['notice_structure'][$view])) {
            if(!in_array($view, array('mypost', 'interactive'))) {
                $category = $view;
            } else {
                $deftype = $_G['notice_structure'][$view][0];
                if($_G['member']['newprompt_num']) {
                    foreach($_G['notice_structure'][$view] as $subtype) {
                        if($_G['member']['newprompt_num'][$subtype]) {
                            $deftype = $subtype;
                            break;
                        }
                    }
                }
                $type = in_array($_GET['type'], $_G['notice_structure'][$view]) ? trim($_GET['type']) : $deftype;
            }
        }
        $wherearr = array();
        $new = -1;
        if(!empty($type)) {
            $wherearr[] = "`type`='$type'";
        }

        $sql = ' AND '.implode(' AND ', $wherearr);


        $newnotify = true;
        $count = C::t('home_notification')->count_by_uid($_G['uid'], $new, $type, $category);
        if($count) {
            if($new == 1 && $perpage == 30) {
                $perpage = 200;
            }
            foreach(C::t('home_notification')->fetch_all_by_uid($_G['uid'], $new, $type, $start, $perpage, $category) as $value) {
                if($value['new']) {
                    $newnotify = true;
                    $value['style'] = 'color:#000;font-weight:bold;';
                } else {
                    $value['style'] = '';
                }
                $value['rowid'] = '';
                if(in_array($value['type'], array('friend', 'poke'))) {
                    $value['rowid'] = ' id="'.($value['type'] == 'friend' ? 'pendingFriend_' : 'pokeQuery_').$value['authorid'].'" ';
                }
                if($value['from_num'] > 0) $value['from_num'] = $value['from_num'] - 1;
                $list[$value['id']] = $value;
            }

        }
        //		echo json_encode($list);exit;
        /*if($newnotify) {
            C::t('home_notification')->ignore($_G['uid'], $type, $category, true, true);
            if($_G['setting']['cloud_status']) {
                $noticeService = Cloud::loadClass('Service_Client_Notification');
                $noticeService->setNoticeFlag($_G['uid'], TIMESTAMP);
            }
        }*/
        helper_notification::update_newprompt($_G['uid'], ($type ? $type : $category));
        if($_G['setting']['my_app_status']) {
            $mynotice = C::t('common_myinvite')->count_by_touid($_G['uid']);
        }
        if($_G['member']['newprompt']) {
            $recountprompt = 0;
            foreach($_G['member']['category_num'] as $promptnum) {
                $recountprompt += $promptnum;
            }
            $recountprompt += $mynotice;
            if($recountprompt == 0) {
                C::t('common_member')->update($_G['uid'], array('newprompt' => 0));
            }
        }

        $noticelist = array();
        if(!empty($list)){
            foreach($list as $value){
                if($value['new'] == 0){
                    // continue;
                }
                if($value['dateline'] < $_GET['last_time_line']){
                    continue;
                }
                $noticerow = array();
                preg_match_all("/<a.*?href=\"(.+?)\"/", $value['note'], $matches);
//                			echo json_encode($matches);exit;
                $link= $matches[1][0] ;
                if(strpos($link,'viewthread')){
                    preg_match_all("/(\w+=\w+)(#\w+)?/i", $link, $mpid);
//                    echo json_encode($mpid);exit;
                    //                    $noticerow['link'] = 'magapp://threadView?tid='.str_replace('ptid=','',$mpid[0][2]);
                    $noticerow['link'] = '/mag/circle/v1/forum/threadViewPage?tid='.str_replace('tid=','',$mpid[0][1]);
                }else{
                    continue;
//                    $noticerow['link'] =  stripos($link,'http')===0 ? $link: site_index_url.'/'.$link ;
                }
                $noticerow['id'] = (int)$value['id'] ;
                $noticerow['subject'] = '来自论坛的系统通知';
                $noticerow['content'] = strip_tags($value['note']) ;
                $noticerow['create_time'] = (int)$value['dateline'] ;
                $noticerow['type'] = $value['type'] ;
                $noticerow['is_new'] = $value['new'] ;
                $noticelist[] = $noticerow;
            }
        }
        success('', $noticelist);
    }

    public function listMessageCommon(){
        global $_G;
        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';
        $discuz = C::app();

        define('IN_UC', TRUE);
        $discuz->init();
        require_once './uc_client/client.php';
        $_GET['mod'] = 'space';
        require_once libfile('function_home', 'function');
        if($_GET['subop'] == 'view') {
            $list = array();
            $daterange = empty($_GET[ 'daterange' ]) ? 0 : intval($_GET[ 'daterange' ]);
            $touid = empty($_GET[ 'touid' ]) ? 0 : intval($_GET[ 'touid' ]);

            $page = empty($_GET[ 'page' ]) ? 1 : intval($_GET[ 'page' ]);
            if ($touid) {
                $perpage = 20;
                $perpage = mob_perpage($perpage);
                if (!$daterange) {
                    $count = uc_pm_view_num($_G[ 'uid' ], $touid, 0);
                    //                        echo $count;exit;
                    $totalpage = ceil($count / $perpage);
                    //ceil($count/$perpage)-$page+1
                    $list = uc_pm_view($_G[ 'uid' ], 0, $touid, 5, $page, $perpage, 0, 0);
                    if ($page > $totalpage || $page <= 0) {
                        $list = array();
                    }
                }
            }
//            $list = array_reverse($list);
            $msglist = array();
            foreach ($list as $key => $value) {
                $msg = array();
                $msg[ 'mid' ] = $value[ 'pmid' ];
                $msg[ 'uid' ] = $value[ 'authorid' ];
                $msg[ 'name' ] = $value[ 'author' ];
                $msg[ 'head' ] = avatar($value[ 'authorid' ], 'small', true);
                $msg[ 'title' ] = $value[ 'subject' ];
                $msg[ 'content' ] = $value[ 'message' ];
                $msg[ 'dateline' ] = $value[ 'dateline' ];
                $msg[ 'isown' ] = $_G[ 'uid' ] == $value[ 'authorid' ] ? "1" : "0";
                $msglist[] = $msg;
            }
            success('', $msglist);
        }else{
            $filter = in_array($_GET['filter'], array('newpm', 'privatepm', 'announcepm')) ? $_GET['filter'] : 'privatepm';

            $perpage = 1000;
            $perpage = mob_perpage($perpage);

            $page = empty($_GET['page'])?0:intval($_GET['page']);
            if($page<1) $page = 1;

            $grouppms = $gpmids = $gpmstatus = array();
            $newpm = $newpmcount = 0;

            if($filter == 'privatepm' && $page == 1 || $filter == 'announcepm' || $filter == 'newpm') {
                $announcepm  = 0;
                foreach(C::t('common_member_grouppm')->fetch_all_by_uid($_G['uid'], $filter == 'announcepm' ? 1 : 0) as $gpmid => $gpuser) {
                    $gpmstatus[$gpmid] = $gpuser['status'];
                    if($gpuser['status'] == 0) {
                        $announcepm ++;
                    }
                }
                $gpmids = array_keys($gpmstatus);
                if($gpmids) {
                    foreach(C::t('common_grouppm')->fetch_all_by_id_authorid($gpmids) as $grouppm) {
                        $grouppm['message'] = cutstr(strip_tags($grouppm['message']), 100, '');
                        $grouppms[] = $grouppm;
                    }
                }
            }

            if($filter == 'privatepm' || $filter == 'newpm') {
                $result = uc_pm_list($_G['uid'], $page, $perpage, 'inbox', $filter, 200);
                $count = $result['count'];
                $list = $result['data'];
            }

            if($filter == 'privatepm' && $page == 1 || $filter == 'newpm') {
                $newpmarr = uc_pm_checknew($_G['uid'], 1);
                $newpm = $newpmarr['newpm'];
            }
            $newpmcount = $newpm + $announcepm;
            if($_G['member']['newpm']) {
                if($newpm && $_G['setting']['cloud_status']) {
                    $msgService = Cloud::loadClass('Cloud_Service_Client_Message');
                    $msgService->setMsgFlag($_G['uid'], $_G['timestamp']);
                }
                C::t('common_member')->update($_G['uid'], array('newpm' => 0));
                uc_pm_ignore($_G['uid']);
            };
            $actives = array($filter=>' class="a"');
            $grouplist = array();
            if(!empty($list)){
                foreach($list as $key=> $value){
                    $grouplist[$key]['uid'] = $value['msgtoid'];
                    $grouplist[$key]['head'] =  avatar($value['msgtoid'], 'middle', true);
                    $grouplist[$key]['name'] = $value['tousername'];
                    $grouplist[$key]['last_msg_content'] = $value['lastsummary'];
                    $grouplist[$key]['last_msg_time'] = $value['lastdateline'];
                    $grouplist[$key]['unread_msg_count'] = $value['isnew'];
                }
            }
            $variable = array(
                'list' => $grouplist
            );

            success('', $grouplist);
        }
    }

    public function listMessageOuput(){
        
    }

    public function sendMsgCommon(){

    }

    public function sendMsgOutput(){
        global $_G;
        if($_G['hookscriptmessage'] !='do_success'){
            error($_G['lang']['message'][$_G['hookscriptmessage']],array(),$_G['hookscriptmessage']);
        }else{
            success('', array());
        }
    }

    public function delMsgCommon(){
        global $_G;
        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';
        $discuz = C::app();

        define('IN_UC', TRUE);
        $discuz->init();
        require_once './uc_client/client.php';

        $mid = (int)$_POST['mid'];
        if(!$mid){
            error('mid不能为空',array());
        }
        uc_pm_delete($_G['uid'], 'inbox', $mid);
        success('删除成功');
    }


    public function delMsgOutput(){


    }

    public function delUserCommon(){
        global $_G;
        require_once './source/class/class_core.php';
        require_once './config/config_ucenter.php';
        $discuz = C::app();

        define('IN_UC', TRUE);
        $discuz->init();
        require_once './uc_client/client.php';

        $deluid = (int)$_POST['uid'];
        if(!$deluid){
            error('uid不能为空',array());
        }
        uc_pm_deleteuser($_G['uid'], array($deluid));
        success('删除成功');
    }


    public function delUserOutput(){

    }
}