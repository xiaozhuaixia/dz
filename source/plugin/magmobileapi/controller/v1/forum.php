<?php
/**
 * 南京灵衍信息科技有限公司
 * User: wangxiao
 * Date: 2016/11/23
 * Time: 15:51
 */

$_GET[ 'mod' ] = $_GET[ 'mod' ] ? $_GET[ 'mod' ] : 'forumdisplay';
include_once dirname(__file__).'/../../../../../forum.php';


class forum{

    public function forumListCommon(){
        //
    }

    public function forumListOutput(){
        global $_G;
        $fup = (int)$_GET[ 'fup' ];
        $fupwhere = $fup ? 'and f.fup = ' . $fup : '';
        $forums = array();
        $sql = !empty($_G[ 'member' ][ 'accessmasks' ]) ? "SELECT f.fid, f.type, f.name,  ff.icon, ff.seodescription as des ,ff.redirect,ff.password
                FROM " . DB::table('forum_forum') . " f 
                LEFT JOIN " . DB::table('forum_forumfield') . " ff ON ff.fid=f.fid
                LEFT JOIN " . DB::table('forum_access') . " a ON a.uid='$_G[uid]' AND a.allowview>'0' AND a.fid=f.fid
                WHERE f.status='1' " . $fupwhere . " ORDER BY f.type, f.displayorder" : "SELECT f.fid, f.type, f.name, f.threads, f.posts, ff.icon, ff.seodescription as des ,ff.redirect,ff.password
                FROM " . DB::table('forum_forum') . " f 
                LEFT JOIN " . DB::table('forum_forumfield') . " ff USING(fid)
                WHERE f.status='1' " . $fupwhere . " ORDER BY f.type, f.displayorder";
        //              echo $sql;die;
        $query = DB::query($sql);
        while ($forum = DB::fetch($query)) {
            if ($forum[ 'redirect' ] || $forum[ 'password' ]) {
                continue;
            }
            if (!$forum[ 'viewperm' ] || ($forum[ 'viewperm' ] && forumperm($forum[ 'viewperm' ]))) {
                if ($forum[ 'threadsorts' ]) {
                    $forum[ 'threadsorts' ] = getvalues(unserialize($forum[ 'threadsorts' ]), array('required', 'types'));
                }
                if ($forum[ 'threadtypes' ]) {
                    $forum[ 'threadtypes' ] = unserialize($forum[ 'threadtypes' ]);
                    $unsetthreadtype = false;
                    if ($_G[ 'adminid' ] == 3 && strpos($forum[ 'moderators' ], $_G[ 'username' ]) === false) {
                        $unsetthreadtype = true;
                    }
                    if ($_G[ 'adminid' ] == 0) {
                        $unsetthreadtype = true;
                    }
                    if ($unsetthreadtype) {
                        foreach ($forum[ 'threadtypes' ][ 'moderators' ] AS $k => $v) {
                            if (!empty($v)) {
                                unset($forum[ 'threadtypes' ][ 'types' ][ $k ]);
                            }
                        }
                    }
                    $flag = 0;
                    foreach ($forum[ 'threadtypes' ][ 'types' ] as $k => $v) {
                        if ($k == 0) {
                            $flag = 1;
                            break;
                        }
                    }
                    if ($flag == 1) {
                        krsort($forum[ 'threadtypes' ][ 'types' ]);
                    }
                    $forum[ 'threadtypes' ] = getvalues($forum[ 'threadtypes' ], array('required', 'types'));
                }
                $forums[] = $forum;
            }
        }
        $data[ 'forums' ] = $forums;
        success('', $data);
    }


    public function forumTreeCommon(){

    }

    public function forumTreeOutPut(){
        global $_G;
        $catlistall = DB::fetch_all("SELECT fid,fup,`name`,`type` FROM " . DB::table('forum_forum') . " f WHERE status=1 ORDER BY f.type, f.displayorder");
        foreach ($catlistall as &$value) {
            $value2 = DB::fetch_first("SELECT icon,seodescription as des,password,redirect FROM " . DB::table('forum_forumfield') . " WHERE fid=" . $value[ 'fid' ]);
            //                  echo 212121;die;
            $value = array_merge($value, $value2);
        }
        foreach($catlistall as $key => &$value){
            if($value['password']|| $value['redirect']){
                unset($catlistall[$key]);
            }
        }
        $data = array('catlistall' => $catlistall);
        success('', $data);
    }

    public function getForumOutput(){

    }

    public function getForumCommon(){
        global $_G;
        require_once libfile('function/discuzcode');
        $fup = (int)$_GET[ 'fid' ];
        $where = $fup ? 'and f.fid = ' . $fup : '';
        $forums = array();
        $sql = !empty($_G[ 'member' ][ 'accessmasks' ]) ? "SELECT f.fid, f.type, f.name,  ff.icon, ff.description as des ,f.todayposts as post_today, f.threads as thread,f.posts as post,f.lastpost as last_reply  ,ff.threadtypes  as threadtypes,
                ff.threadsorts ,ff.moderators ,ff.password
                FROM " . DB::table('forum_forum') . " f 
                LEFT JOIN " . DB::table('forum_forumfield') . " ff ON ff.fid=f.fid
                LEFT JOIN " . DB::table('forum_access') . " a ON a.uid='$_G[uid]' AND a.allowview>'0' AND a.fid=f.fid
                WHERE f.status='1' " . $where . " ORDER BY f.type, f.displayorder" : "SELECT f.fid, f.type, f.name,  ff.icon, ff.description as des ,f.todayposts as post_today, f.threads as thread,f.posts as post,f.lastpost as last_reply  ,ff.threadtypes  as threadtypes,
                ff.threadsorts ,ff.moderators ,ff.password
                FROM " . DB::table('forum_forum') . " f 
                LEFT JOIN " . DB::table('forum_forumfield') . " ff USING(fid)
                WHERE f.status='1' " . $where . " ORDER BY f.type, f.displayorder";
        //              echo $sql;die;
        $query = DB::query($sql);
        while ($forum = DB::fetch($query)) {
            //            echo  json_encode($forum);exit;
            //            if ($forum[ 'redirect' ] || $forum[ 'password' ]) {
            if ($forum[ 'redirect' ]) {
                continue;
            }
            if (!$forum[ 'viewperm' ] || ($forum[ 'viewperm' ] && forumperm($forum[ 'viewperm' ]))) {
                //                echo json_encode($forum);exit;
                if ($forum[ 'threadsorts' ]) {
                    $forum[ 'threadsorts' ] = getvalues(unserialize($forum[ 'threadsorts' ]), array('required', 'types'));
                    $forum[ 'sorts' ] = array();
                    $forum[ 'sorts' ][ 'required' ] = $forum[ 'threadsorts' ][ 'required' ] ? true : false;
                    foreach ($forum[ 'threadsorts' ][ 'types' ] as $key => $value) {
                        $forum[ 'sorts' ][ 'types' ][] = array('id' => $key, 'name' => strip_tags(discuzcode($value)));
                    }
                }
                if ($forum[ 'threadtypes' ]) {
                    $forum[ 'threadtypes' ] = unserialize($forum[ 'threadtypes' ]);
                    $unsetthreadtype = false;
                    if ($_G[ 'adminid' ] == 3 && strpos($forum[ 'moderators' ], $_G[ 'username' ]) === false) {
                        $unsetthreadtype = true;
                    }
                    if ($_G[ 'adminid' ] == 0) {
                        $unsetthreadtype = true;
                    }
                    if ($unsetthreadtype) {
                        foreach ($forum[ 'threadtypes' ][ 'moderators' ] AS $k => $v) {
                            if (!empty($v)) {
                                unset($forum[ 'threadtypes' ][ 'types' ][ $k ]);
                            }
                        }
                    }
                    $flag = 0;
                    foreach ($forum[ 'threadtypes' ][ 'types' ] as $k => $v) {
                        if ($k == 0) {
                            $flag = 1;
                            break;
                        }
                    }
                    if ($flag == 1) {
                        krsort($forum[ 'threadtypes' ][ 'types' ]);
                    }
                    $forum[ 'threadtypes' ] = getvalues($forum[ 'threadtypes' ], array('listable', 'required', 'types'));
                    if ($forum[ 'threadtypes' ][ 'listable' ]) {
                        $forum[ 'types' ] = array();
                        $forum[ 'types' ][ 'required' ] = $forum[ 'threadtypes' ][ 'required' ] ? true : false;
                        $forum[ 'types' ][ 'listable' ] = $forum[ 'threadtypes' ][ 'listable' ] ? true : false;
                        $forum[ 'types' ][ 'types' ] = array();
                        foreach ($forum[ 'threadtypes' ][ 'types' ] as $key => $value) {
                            $forum[ 'types' ][ 'types' ][] = array('id' => $key, 'name' => strip_tags($value));
                        }
                    }
                }
                //              $forums[] = mobile_core::getvalues($forum, array('fid', 'type', 'name', 'fup', 'viewperm', 'postperm', 'status', 'threadsorts', 'threadtypes'));
                if(strpos($_G[ 'setting' ][ 'attachurl' ],'http') !== 0) {
                    //echo $forum[ 'icon' ];die;
                    if(strpos($forum[ 'icon' ],'http') !== 0) {
                        $forum[ 'icon' ] = $forum[ 'icon' ] ? site_index_url . '/' . $_G[ 'setting' ][ 'attachurl' ] . 'common/' . $forum[ 'icon' ] : '';
                    } else {
                        $forum[ 'icon' ] =  $forum[ 'icon' ];
                    }

                } else {
                    //$forum[ 'icon' ] = $forum[ 'icon' ] ? $_G[ 'setting' ][ 'attachurl' ] . 'common/' . $forum[ 'icon' ] : '';
                    if(strpos($forum[ 'icon' ],'http') !== 0) {
                        $forum[ 'icon' ] = $forum[ 'icon' ] ? $_G[ 'setting' ][ 'attachurl' ]  . 'common/' . $forum[ 'icon' ] : '';
                    } else {
                        $forum[ 'icon' ] =  $forum[ 'icon' ];
                    }
                }
                //$forum[ 'icon' ] = $forum[ 'icon' ] ? site_index_url . '/' . $_G[ 'setting' ][ 'attachurl' ] . 'common/' . $forum[ 'icon' ] : '';
                //echo $forum[ 'icon' ];die;
                $forums = $forum;
                $forums[ 'children' ] = self::childrenlist($forums[ 'fid' ]);
                $forums[ 'children_count' ] = count($forums[ 'children' ]);
                $forums[ 'post_count' ] = $forums[ 'post' ];
            }
        }
        $forums[ 'orders' ] = array(array('name' => '默认', 'value' => ''), array('name' => '最新', 'value' => 'lastpost'), array('name' => '热门', 'value' => 'heat'), array('name' => '热帖', 'value' => 'hot'), array('name' => '发帖时间', 'value' => 'dateline'), array('name' => '回复/查看', 'value' => 'replies'), array('name' => '查看', 'value' => 'views'));
        $forums[ 'moderators_user' ] = array();
        if ($forums[ 'moderators' ]) {
            $moderators_array = explode("\t", $forums[ 'moderators' ]);
            foreach ($moderators_array as $key => $value) {
                $userrow = DB::fetch_first("SELECT * FROM " . DB::table('common_member') . " WHERE username='" . $value . '\'');
                $userprofile = DB::fetch_first("SELECT * FROM " . DB::table('common_member_profile') . " WHERE uid='" . $userrow[ 'uid' ] . '\'');
                $forums[ 'moderators_user' ][ $key ][ 'user_id' ] = $userrow[ 'uid' ];
                $forums[ 'moderators_user' ][ $key ][ 'name' ] = $userrow[ 'username' ];
                $forums[ 'moderators_user' ][ $key ][ 'sex' ] = $userprofile[ 'gender' ];
                $forums[ 'moderators_user' ][ $key ][ 'head' ] = avatar($userrow[ 'uid' ], 'middle', true) . '&random=' . random(2);
            }
        }
        $forums[ 'moderators' ] = explode("\t", $forums[ 'moderators' ]);
        if ($forums[ 'last_reply' ]) {
            $last_reply_array = explode("\t", $forums[ 'last_reply' ]);
            //          echo json_encode($last_reply_array);exit;
            $forums[ 'last_comment' ][ 'id' ] = $last_reply_array[ 0 ];
            $forums[ 'last_comment' ][ 'title' ] = $last_reply_array[ 1 ];
            $forums[ 'last_comment' ][ 'create_time' ] = $last_reply_array[ 2 ];
            $forums[ 'last_comment' ][ 'user_name' ] = $last_reply_array[ 3 ];
        } else {
            $forums[ 'last_comment' ] = array();
        }
        //        $forums[ 'icon' ] = $forums[ 'icon' ] ? $forums[ 'icon' ] : site_index_url . '/static/image/common/forum_new.gif';
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        $forums[ 'icon' ] = $forums[ 'icon' ] ? $forums[ 'icon' ] :  $_MAG_CONFIG['MAG_INDEX_URL'] . '/public/icons/forum/forum_new.png';
        $data[ 'forums' ] = $forums;
        //echo json_encode($data);exit;
        success('', $data);
    }

    private function childrenlist($fup){
        global $_G;
        $fupwhere = 'and f.fup = ' . $fup;
        $forums = array();
        $sql = "SELECT f.fid, f.type, f.name,  ff.icon, ff.description as des
                FROM " . DB::table('forum_forum') . " f
                LEFT JOIN " . DB::table('forum_forumfield') . " ff USING(fid)
                WHERE f.status='1' " . $fupwhere . " ORDER BY f.type, f.displayorder";
        $query = DB::query($sql);
        while ($forum = DB::fetch($query)) {
            if(strpos($_G[ 'setting' ][ 'attachurl' ],'http') !== 0) {
                //echo $forum[ 'icon' ];die;
                if(strpos($forum[ 'icon' ],'http') !== 0) {
                    $forum[ 'icon' ] = $forum[ 'icon' ] ? site_index_url . '/' . $_G[ 'setting' ][ 'attachurl' ] . 'common/' . $forum[ 'icon' ] : '';
                } else {
                    $forum[ 'icon' ] =  $forum[ 'icon' ];
                }

            } else {
                //$forum[ 'icon' ] = $forum[ 'icon' ] ? $_G[ 'setting' ][ 'attachurl' ] . 'common/' . $forum[ 'icon' ] : '';
                if(strpos($forum[ 'icon' ],'http') !== 0) {
                    $forum[ 'icon' ] = $forum[ 'icon' ] ? $_G[ 'setting' ][ 'attachurl' ]  . 'common/' . $forum[ 'icon' ] : '';
                } else {
                    $forum[ 'icon' ] =  $forum[ 'icon' ];
                }
            }
            $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
            $forum[ 'icon' ] = $forum[ 'icon' ] ? $forum[ 'icon' ] :  $_MAG_CONFIG['MAG_INDEX_URL'] . '/public/icons/forum/forum_new.png';
            $forums[] = $forum;
        }
        return $forums;
    }


    public function forumClassifiedCommon(){


    }

    public function forumClassifiedOutput(){
        global $_G;
        $optionlist = array();
        $selectoptionlist = array();
        $searchoptionlist = array();
        if ($_G[ 'forum_optionlist' ]) {
            foreach ($_G[ 'forum_optionlist' ] as $key => $value) {
                $option = $value;
                $option[ 'id' ] = $key;
                switch($value[ 'type' ]) {
                    case 'select' :
                        foreach ($option[ 'choices' ] as $key2 => $value2) {
                            $option[ 'choices' ][ $key2 ][ 'name' ] = $value2[ 'content' ]. $value[ 'unit' ];
                            $option[ 'choices' ][ $key2 ][ 'value' ] = $value2[ 'optionid' ] . "";
                        }
                        $option[ 'choices' ] = array_values($option[ 'choices' ]);
                        $option[ 'choices' ] = list_to_tree($option[ 'choices' ], $pk = 'optionid', $foptionid = 'foptionid', $child = 'children', $root = 0);
                        array_unshift($option[ 'choices' ], array('name' => '不限', 'value' => 'all'));
                        if ($option[ 'search' ] & 2) {
                            //文字检索
                            $option[ 'key' ] = 'selectoption[' . $option[ 'identifier' ] . ']';
                            $selectoptionlist[] = $option;
                        }
                        if ($option[ 'search' ] & 1) {
                            //表单搜索
                            $option[ 'key' ] = 'searchoption[' . $option[ 'id' ] . '][value]';
                            $option[ 'extrakeyvalue' ] = array('searchoption[' . $option[ 'id' ] . '][type]' => $value[ 'type' ]);
                            $searchoptionlist[] = $option;
                        }

                        break;
                    case 'radio' :

                        $option[ 'choices' ] = array();
                        $option[ 'choices' ][] = array('name' => '不限', 'value' => 'all');
                        foreach ($value[ 'choices' ] as $key2 => $value2) {
                            $choice = array();
                            $choice[ 'name' ] = $value2. $value[ 'unit' ];
                            $choice[ 'value' ] = (string)$key2;
                            $option[ 'choices' ] [] = $choice;
                        }
                        if ($option[ 'search' ] & 2) {
                            //文字检索
                            $option[ 'key' ] = 'selectoption[' . $option[ 'identifier' ] . ']';
                            $selectoptionlist[] = $option;
                        }
                        if ($option[ 'search' ] & 1) {
                            //表单搜索
                            $option[ 'key' ] = 'searchoption[' . $option[ 'id' ] . '][value]';
                            $option[ 'extrakeyvalue' ] = array('searchoption[' . $option[ 'id' ] . '][type]' => $value[ 'type' ]);
                            $searchoptionlist[] = $option;
                        }

                        break;

                    case 'checkbox' :
                        $option[ 'choices' ] = array();
                        $option[ 'choices' ][] = array('name' => '不限', 'value' => '');
                        foreach ($value[ 'choices' ] as $key2 => $value2) {
                            $choice = array();
                            $choice[ 'name' ] = $value2. $value[ 'unit' ];
                            $choice[ 'value' ] = (string)$key2;
                            $option[ 'choices' ] [] = $choice;
                        }
                        if ($option[ 'search' ] & 2) {
                            //文字检索
                            $option[ 'key' ] = 'selectoptionp[' . $option[ 'identifier' ] . ']';
                            $selectoptionlist[] = $option;
                        }
                        if ($option[ 'search' ] & 1) {
                            //表单搜索
                            $option[ 'key' ] = 'searchoption[' . $option[ 'id' ] . '][value]';
                            $option[ 'valueformat' ] = '[value1:value1,value2:value2]';
                            $option[ 'extrakeyvalue' ] = array('searchoption[' . $option[ 'id' ] . '][type]' => $value[ 'type' ]);
                            $searchoptionlist[] = $option;
                        }

                        break;
                    case 'range' :
                        $option[ 'choices' ] = array();
                        $option[ 'choices' ][] = array('name' => '不限', 'value' => 'all');
                        foreach ($option[ 'searchtxt' ] as $key2 => $value2) {
                            $choice = array();
                            if ($key2 == 0) {
                                if ($value2 == 0) {
                                    continue;
                                } else {
                                    $choice[ 'name' ] = '低于' . $value2. $value[ 'unit' ];
                                    $choice[ 'value' ] = 'd|' . $value2;
                                    $option[ 'choices' ] [] = $choice;
                                    if (count($option[ 'searchtxt' ]) == 1) {
                                        $choice[ 'name' ] = '高于' . $value2;
                                        $choice[ 'value' ] = 'u|' . $value2;
                                        $option[ 'choices' ] [] = $choice;
                                    }
                                    continue;
                                }
                            }
                            if ($option[ 'searchtxt' ][ $key2 - 1 ] == 0) {
                                $choice[ 'name' ] = '低于' . $option[ 'searchtxt' ][ $key2 ]. $value[ 'unit' ];
                                $choice[ 'value' ] = 'd|' . $option[ 'searchtxt' ][ $key2 ];
                                $option[ 'choices' ] [] = $choice;
                            } else {
                                $choice[ 'name' ] = $option[ 'searchtxt' ][ $key2 - 1 ] . '-' . $option[ 'searchtxt' ][ $key2 ]. $value[ 'unit' ];
                                $choice[ 'value' ] = $option[ 'searchtxt' ][ $key2 - 1 ] . '|' . $option[ 'searchtxt' ][ $key2 ];
                                $option[ 'choices' ] [] = $choice;
                            }
                            if ($key2 + 1 == count($option[ 'searchtxt' ])) {
                                $choice[ 'name' ] = '高于' . $option[ 'searchtxt' ][ $key2 ]. $value[ 'unit' ];
                                $choice[ 'value' ] = 'u|' . $option[ 'searchtxt' ][ $key2 ];
                                $option[ 'choices' ] [] = $choice;
                            }

                        }

                        if ($option[ 'search' ] & 2) {
                            //文字检索
                            $option[ 'key' ] = 'selectoption[' . $option[ 'identifier' ] . ']';
                            $selectoptionlist[] = $option;
                        }
                        if ($option[ 'search' ] & 1) {
                            //表单搜索
                            $option[ 'key' ] = 'searchoption[' . $option[ 'id' ] . '][value]';
                            $option[ 'extrakeyvalue' ] = array('searchoption[' . $option[ 'id' ] . '][type]' => $value[ 'type' ]);
                            $searchoptionlist[] = $option;
                        }
                        break;

                    default:
                        if ($option[ 'search' ] & 2) {
                            //文字检索
                            $option[ 'key' ] = 'selectoption[' . $option[ 'identifier' ] . ']';
                            $selectoptionlist[] = $option;
                        }
                        if ($option[ 'search' ] & 1) {
                            //表单搜索
                            $option[ 'key' ] = 'searchoption[' . $option[ 'id' ] . '][value]';
                            $searchoptionlist[] = $option;
                        }
                }

                $optionlist[] = $option;
            }
        }
        $data = array(//            'forum_optionlist' => $optionlist,
            'searchoptionlist' => $searchoptionlist, 'selectoptionlist' => $selectoptionlist);
        success('', $data);
    }


    public function threadListCommon(){
        global $_G;
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        if( $_MAG_CONFIG ['threadlist_step']){
            $_GET[ 'step' ]  = $_MAG_CONFIG ['threadlist_step'];
        }
        if ($_GET[ 'step' ] > 0) {
            $_G[ 'tpp' ] = $_GET[ 'step' ];
        }
    }

    public function threadListOutput(){
        global $_G;
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        if(!empty($_MAG_CONFIG['emotion_url_contain'][0])) {
            //echo $_MAG_CONFIG['emotion_url_contain'][0];die;
            $emotion_url_contain = explode(',',$_MAG_CONFIG['emotion_url_contain'][0]);
        }
        //var_dump($emotion_url_contain);die;
        foreach ($_G[ 'forum_threadlist' ] as $k => $thread) {
            $_G[ 'forum_threadlist' ][ $k ][ 'cover' ] = array();
//            if ($thread[ 'cover' ]) {
//                $_img = @getimagesize($thread[ 'coverpath' ]);
//                if ($_img) {
//                    $_G[ 'forum_threadlist' ][ $k ][ 'cover' ] = array('w' => $_img[ 0 ], 'h' => $_img[ 1 ]);
//                }
//            }
            if (!$thread[ 'authorid' ] || !$thread[ 'author' ]) {
                $_G[ 'forum_threadlist' ][ $k ][ 'author' ] = $_G[ 'setting' ][ 'anonymoustext' ];
                $_G[ 'forum_threadlist' ][ $k ][ 'authorid' ] = 0;
            }
            $posttablename = 'forum_post'.($thread['posttableid'] ? "_".$thread['posttableid'] : '');
            $content = DB::fetch_first("SELECT * FROM " . DB::table($posttablename) . " WHERE first=1 and tid=" . $thread[ 'tid' ]);
            if(!$content) {
                unset($_G[ 'forum_threadlist' ][$k]);
                continue;
            }
            //            echo json_encode($content);exit;
            //          $_G['forum_threadlist'][$k]['_post'] = $content;
            $pics = array();
            preg_match_all("/\[img.*\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/i", $content[ 'message' ], $imgmatchaids);
            /*if ($imgmatchaids[ 1 ]) {
                foreach ($imgmatchaids[ 1 ] as $key => $value) {
                    if (!strpos($value, 'smiley') && !strpos($value, 'emotion')) {
                        $pics[] = $value;
                    }
                }
            }*/
            if ($imgmatchaids[ 1 ]) {
                foreach ($imgmatchaids[ 1 ] as $key => $value) {
                    if(!empty($emotion_url_contain)) {
                        foreach($emotion_url_contain as $v) {
                            if (strpos($value, $v) !== false) {
                                //array_splice($imgmatchaids[1], $key, 1);
                                continue 2;
                            }
                        }
                        $pics[] = $value;

                        /*$flag = true;
                        foreach($emotion_url_contain as $v) {
                            $flag &= !strpos($value, $v);
                        }
                        if($flag) {
                            $pics[] = $value;
                        }*/
                    }
                }
            }
            /*从附件表中取缩略图*/
            $attlist = DB::fetch_all("SELECT * FROM " . DB::table('forum_attachment') . " WHERE tid=" . $thread[ 'tid' ] ." and pid=".$content[ 'pid' ]);
            if ($attlist) {
                //              echo json_encode($attmatchaids[1]);exit;
                foreach ($attlist as $att) {
                    if ($att) {
                        $att2 = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment_' . $att[ 'tableid' ]) . " WHERE aid=" . $att[ 'aid' ]);
                        //                        echo json_encode($att2);exit;
                        if ($att2[ 'isimage' ]) {
                            //$pics[] = site_index_url . '/data/attachment/forum/' . $att2[ 'attachment' ];
                            $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att2[ 'aid' ] . '&size=200x200&key=' . md5($att2[ 'aid' ] . '|200|200');
                        }
                    }
                }
            }
            /* 从正文中取缩略图
             * preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $content[ 'message' ], $attmatchaids);
            if ($attmatchaids[ 1 ]) {
                //              echo json_encode($attmatchaids[1]);exit;
                foreach ($attmatchaids[ 1 ] as $value) {
                    $att = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment') . " WHERE aid=" . $value);
                    if ($att) {
                        $att2 = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment_' . $att[ 'tableid' ]) . " WHERE aid=" . $att[ 'aid' ]);
                        //                        echo json_encode($att2);exit;
                        if ($att2[ 'isimage' ]) {
                            //                            $pics[] = site_index_url . '/data/attachment/forum/' . $att2[ 'attachment' ];
                            $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att2[ 'aid' ] . '&size=200x200&key=' . md5($att2[ 'aid' ] . '|200|200');
                        }
                    }
                }
            }*/
            $_G[ 'forum_threadlist' ][ $k ][ 'title' ] = $_G[ 'forum_threadlist' ][ $k ][ 'subject' ];
            $_G[ 'forum_threadlist' ][ $k ][ 'user_id' ] = $_G[ 'forum_threadlist' ][ $k ][ 'authorid' ];
            $_G[ 'forum_threadlist' ][ $k ][ 'user_name' ] = $_G[ 'forum_threadlist' ][ $k ][ 'author' ];
            $_G[ 'forum_threadlist' ][ $k ][ 'user_head' ] = avatar($_G[ 'forum_threadlist' ][ $k ][ 'authorid' ], 'middle', true) . '&random=' . random(2);
            $_G[ 'forum_threadlist' ][ $k ][ 'pics' ] = array_values(array_unique($pics));
            $_G[ 'forum_threadlist' ][ $k ][ 'click' ] = $_G[ 'forum_threadlist' ][ $k ][ 'views' ];
            $_G[ 'forum_threadlist' ][ $k ][ 'reply_count' ] = $_G[ 'forum_threadlist' ][ $k ][ 'replies' ];
            $_G[ 'forum_threadlist' ][ $k ][ 'create_time' ] = $_G[ 'forum_threadlist' ][ $k ][ 'dbdateline' ];
            $_G[ 'forum_threadlist' ][ $k ][ 'last_reply_time' ] = $_G[ 'forum_threadlist' ][ $k ][ 'dblastpost' ];
            $_G[ 'forum_threadlist' ][ $k ][ 'is_top' ] = $thread[ 'displayorder' ] ? 1 : -1;

            $iconsrc = array();

            if ($thread[ 'displayorder' ]) {
                $iconsrc[] =  $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/pin_' . $thread[ displayorder ] . '.png';
            }
            if ($thread[ 'digest' ]) {
                $iconsrc[] =  $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/digest_' . $thread[ digest ] . '.png';
            }
            if ($thread[ 'rushreply' ]) {
                $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum//rushreply.png';
            }
            if ($thread[ 'special' ] == 1) {
                $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum//pollsmall.png';
            } elseif ($thread[ 'special' ] == 2) {
                $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/tradesmall.png';
            } elseif ($thread[ 'special' ] == 3) {
                $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/rewardsmall.png';
            } elseif ($thread[ 'special' ] == 4) {
                $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/activitysmall.png';
            } elseif ($thread[ 'special' ] == 5) {
                $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/debatesmall.png';
            }
            if ($thread[ 'icon' ] > 0) {
                $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/'. substr($_G[ 'cache' ][ 'stamps' ][ $thread[ icon ] ][ 'url' ],0,-3).'png';
            }
            $_G[ 'forum_threadlist' ][ $k ][ 'iconsrc' ] = $iconsrc;
            //          echo json_encode($_G['forum_threadlist'][$k]['pics'] );exit;
        }

        $threadlist = array();
        foreach ($_G[ 'forum_threadlist' ] as $value) {
            $threadli = array();
            $threadli[ 'tid' ] = $value[ 'tid' ];
            $threadli[ 'fid' ] = $value[ 'fid' ];
            $threadli[ 'sortid' ] = $value[ 'sortid' ];
            $threadli[ 'typeid' ] = $value[ 'typeid' ];
            $threadli[ 'dateline' ] = date_ago($value[ 'create_time' ]);
            $threadli[ 'lastpost' ] = date_ago($value[ 'last_reply_time' ]);
            $threadli[ 'subject' ] = $value[ 'subject' ];
            if (is_array($value[ 'iconsrc' ])) {
                foreach ($value[ 'iconsrc' ] as $iconvalue) {
                    $threadli[ 'title' ] .= '<img src="' . $iconvalue . '" />';
                }
            }

            $threadli[ 'title' ] .= ' '.$value[ 'title' ];
            $threadli[ 'user_id' ] = $value[ 'user_id' ];
            $threadli[ 'user_name' ] = $value[ 'user_name' ];
            $threadli[ 'user_head' ] = $value[ 'user_head' ];
            $threadli[ 'user_id' ] = $value[ 'user_id' ];
            $threadli[ 'pics' ] = $value[ 'pics' ];
            $threadli[ 'click' ] = $value[ 'click' ];
            $threadli[ 'reply_count' ] = $value[ 'reply_count' ];
            $threadli[ 'create_time' ] = $value[ 'create_time' ];
            $threadli[ 'last_reply_time' ] = $value[ 'last_reply_time' ];
            $threadli[ 'is_top' ] = $value[ 'is_top' ];
            $threadli[ 'iconsrc' ] = $value[ 'iconsrc' ];
            $threadli[ 'special' ] = $value[ 'special' ];
            $threadlist[] = $threadli;
        }
        $realpages = @ceil($_G['forum_threadcount']/$_G['tpp']);
        $maxpage = ($_G['setting']['threadmaxpages'] && $_G['setting']['threadmaxpages'] < $realpages) ? $_G['setting']['threadmaxpages'] : $realpages;
        if ($GLOBALS[ 'page' ] > $maxpage) {
            $threadlist = array();
        }

        $data = array('forum_threadlist' => $threadlist, 'step' => $_G[ 'tpp' ], 'page' => $GLOBALS[ 'page' ], 'maxpage' => $maxpage);
        if (!empty($_G[ 'hookscriptmessage' ])) {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        }else if(!empty($_G['messageparam'])){
            error($_G['messageparam'], array(), $_G['messageparam']);
        } else {
            success('', $data);
        }
    }

    public function is_smile_pic($url) {
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        if(!empty($_MAG_CONFIG['emotion_url_contain'][0])) {
            //echo $_MAG_CONFIG['emotion_url_contain'][0];die;
            $emotion_url_contain = explode(',',$_MAG_CONFIG['emotion_url_contain'][0]);
        }
        //var_dump($emotion_url_contain);die;
        if(!empty($emotion_url_contain) && is_array($emotion_url_contain)) {
            //echo 1;die;
            foreach($emotion_url_contain as $v) {
                //echo $url;die;
                if (strpos($url, $v) !== false) {
                    //array_splice($imgmatchaids[1], $key, 1);
                    //echo 1;die;
                    return true;
                }
            }
        }
    }
    public function viewthreadCommon(){
        global $_G ;
        //兼容超级活动插件xj_event
        $_SERVER['HTTP_REFERER'] = 'http://wsq.discuz.com';
    }

    public function viewthreadOutput(){
        global $_G, $thread;
        //var_dump($_G);die;
        if ($GLOBALS[ 'hiddenreplies' ]) {
            foreach ($GLOBALS[ 'postlist' ] as $k => $post) {
                if (!$post[ 'first' ] && $_G[ 'uid' ] != $post[ 'authorid' ] && $_G[ 'uid' ] != $_G[ 'forum_thread' ][ 'authorid' ] && !$_G[ 'forum' ][ 'ismoderator' ]) {
                    $GLOBALS[ 'postlist' ][ $k ][ 'message' ] = lang('plugin/mobile', 'mobile_post_author_visible');
                    $GLOBALS[ 'postlist' ][ $k ][ 'attachments' ] = array();
                }
            }
        }
        $_G[ 'thread' ][ 'lastpost' ] = dgmdate($_G[ 'thread' ][ 'lastpost' ]);
        $thread = $_G[ 'thread' ];

        $thread[ 'mobile' ] = base_convert(getstatus($thread[ 'status' ], 13) . getstatus($thread[ 'status' ], 12) . getstatus($thread[ 'status' ], 11), 2, 10);


        $allowblockrecommend = $_G[ 'group' ][ 'allowdiy' ] || getstatus($_G[ 'member' ][ 'allowadmincp' ], 4) || getstatus($_G[ 'member' ][ 'allowadmincp' ], 5) || getstatus($_G[ 'member' ][ 'allowadmincp' ], 6);
        if ($_G[ 'setting' ][ 'portalstatus' ]) {
            $allowpostarticle = $_G[ 'group' ][ 'allowmanagearticle' ] || $_G[ 'group' ][ 'allowpostarticle' ] || getstatus($_G[ 'member' ][ 'allowadmincp' ], 2) || getstatus($_G[ 'member' ][ 'allowadmincp' ], 3);
            $allowpusharticle = empty($_G[ 'forum_thread' ][ 'special' ]) && empty($_G[ 'forum_thread' ][ 'sortid' ]) && !$_G[ 'forum_thread' ][ 'pushedaid' ];
        } else {
            $allowpostarticle = $allowpusharticle = false;
        }
        if ($_G[ 'forum_thread' ][ 'displayorder' ] != -4) {
            $modmenu = array('thread' => $_G[ 'forum' ][ 'ismoderator' ] || $allowblockrecommend || $allowpusharticle && $allowpostarticle, 'post' => $_G[ 'forum' ][ 'ismoderator' ] && ($_G[ 'group' ][ 'allowwarnpost' ] || $_G[ 'group' ][ 'allowbanpost' ] || $_G[ 'group' ][ 'allowdelpost' ] || $_G[ 'group' ][ 'allowstickreply' ]) || $_G[ 'forum_thread' ][ 'pushedaid' ] && $allowpostarticle || $_G[ 'forum_thread' ][ 'authorid' ] == $_G[ 'uid' ]);
        } else {
            $modmenu = array();
        }

        $GLOBALS['postlist'] = array_values($GLOBALS['postlist']);
        foreach ($GLOBALS['postlist'] as $k => $post) {
            if($k > 0){
                break;
            }
            if (!$_G[ 'forum' ][ 'ismoderator' ] && $_G[ 'setting' ][ 'bannedmessages' ] & 1 && (($post[ 'authorid' ] && !$post[ 'username' ]) || ($_G[ 'thread' ][ 'digest' ] == 0 && ($post[ 'groupid' ] == 4 || $post[ 'groupid' ] == 5 || $post[ 'memberstatus' ] == '-1')))) {
                $message = '<div class="locked">'.lang('forum/template', 'message_banned').'</div>';
            } elseif (!$_G[ 'forum' ][ 'ismoderator' ] && $post[ 'status' ] & 1) {
                $message = '<div class="locked">'.lang('forum/template', 'message_single_banned').'</div>';
            } elseif ($GLOBALS[ 'needhiddenreply' ]) {
                $message = '<div class="locked">'.lang('forum/template', 'message_ishidden_hiddenreplies').'</div>';
            } elseif ($post[ 'first' ] && $_G[ 'forum_threadpay' ]) {
                $message = '<div class="locked">'.lang('forum/template', 'pay_threads') . ' ' . $GLOBALS[ 'thread' ][ 'price' ] . ' ' . $_G[ 'setting' ][ 'extcredits' ][ $_G[ 'setting' ][ 'creditstransextra' ][ 1 ] ][ 'unit' ] . $_G[ 'setting' ][ 'extcredits' ][ $_G[ 'setting' ][ 'creditstransextra' ][ 1 ] ][ 'title' ].'</div>';
            } elseif ($_G[ 'forum_discuzcode' ][ 'passwordlock' ]) {
                $message = '<div class="locked">'.lang('forum/template', 'message_password_exists').'</div>';
                $message = preg_replace( "@<script(.*?)</script>@is", "", $message );
                $message = strip_tags($message);
            } else {
                $message = '';
            }
            //            echo $post['status'] & 1;exit;
            if ($message) {
                $GLOBALS[ 'postlist' ][ $k ][ 'message' ] = $message;
            }else{
                if($_G['setting']['bannedmessages'] & 1 && (($post['authorid'] && !$post['username']) || ($_G['thread']['digest'] == 0 && ($post['groupid'] == 4 || $post['groupid'] == 5 || $post['memberstatus'] == '-1')))) {
                    $GLOBALS['postlist'][$k]['message'] = '<div class="locked">' . lang('forum/template', 'admin_message_banned') . '</div>'.$GLOBALS['postlist'][$k]['message'];
                } elseif($post['status'] & 1) {
                    $GLOBALS['postlist'][$k]['message'] = '<div class="locked">'.lang('forum/template', 'admin_message_single_banned').'</div>'.$GLOBALS['postlist'][$k]['message'];
                }
            }


            $GLOBALS['postlist'][$k]['message'] = preg_replace("/<a\shref=\"([^\"]+?)\"\starget=\"_blank\">\[viewimg\]<\/a>/is", "<img src=\"\\1\" />", $GLOBALS['postlist'][$k]['message']);
        }
        $topic = array_shift($GLOBALS[ 'postlist' ]);
        $imagelist = $topic['imagelist'];//未插入的图片id
        $pics = array();
        //        echo $topic['username'];exit;
//        $topic[ 'message' ] = preg_replace("/\[media\]([\s\S]*?)\[\/media\]/i", '[该视频或音乐请在电脑版查看]', $topic[ 'message' ]);
//        $topic[ 'message' ]  = preg_replace("/\[media.*\]\s*([^\[\<\r\n]+?)\s*\[\/media\]/ie", "magparsemedia('\\1')" , $topic[ 'message' ] );
        preg_match_all("/\[media.*\]\s*([^\[\<\r\n]+?)\s*\[\/media\]/i", $topic[ 'message' ], $mediamatchaids);
        if($mediamatchaids[1]) {
            foreach ($mediamatchaids[ 1 ] as $mk => $mv) {
                $target = magparsemedia($mv);
                $topic[ 'message' ] = str_replace($mediamatchaids[ 0 ][$mk],$target,$topic[ 'message' ]);
            }
        }
        preg_match_all("/<img.*?src=\"(.*?)\".*?>/i", $topic[ 'message' ], $imgmatchaids);
//        if ($imgmatchaids[ 1 ]) {
//            foreach ($imgmatchaids[ 1 ] as $key => $value) {
//                if (!strpos($value, 'smiley') && !strpos($value, 'emotion')) {
//                    $pics[] = $value;
//                }
//            }
//        }
        if ($imgmatchaids[ 1 ]) {
            foreach ($imgmatchaids[ 1 ] as $key => $value) {
                if(!empty($emotion_url_contain)) {
                    foreach($emotion_url_contain as $v) {
                        if (strpos($value, $v) !== false) {
                            //array_splice($imgmatchaids[1], $key, 1);
                            continue 2;
                        }
                    }
                    $pics[] = $value;

                    /*$flag = true;
                    foreach($emotion_url_contain as $v) {
                        $flag &= !strpos($value, $v);
                    }
                    if($flag) {
                        $pics[] = $value;
                    }*/
                }
            }
        }
        //print_r($pics);die;
        //        echo $topic[ 'message' ];exit;
        preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $topic[ 'message' ], $attmatchaids);
        if ($attmatchaids[ 1 ]) {
            //                          echo json_encode($attmatchaids[1]);exit;
            foreach ($attmatchaids[ 1 ] as $value) {
                if(array_search($value,$imagelist) !== false){
                    array_splice($imagelist,array_search($value,$imagelist),1);
                }
                $att = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment') . " WHERE aid=" . $value);
                if ($att) {
                    $att2 = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment_' . $att[ 'tableid' ]) . " WHERE aid=" . $att[ 'aid' ]);
                    //                        echo json_encode($att2);exit;
                    if ($att2[ 'isimage' ]) {
                        //                            $pics[] = site_index_url . '/data/attachment/forum/' . $att2[ 'attachment' ];
                        $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att2[ 'aid' ] . '&size=200x200&key=' . md5($att2[ 'aid' ] . '|200|200');
                    }
                }
            }
        }
        foreach($imagelist as $key => $value){
            $topic[ 'message' ] .='<br />[attach]'.$value.'[/attach]';
        }
        //        echo json_encode($imagelist);exit;
        //print_r($pics);die;
        preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $topic[ 'message' ], $matchaids);
        //        echo json_encode($topic);exit;
        foreach ($matchaids[ 0 ] as $key2 => $value2) {
            $att = $topic[ 'attachments' ][ $matchaids[ 1 ][ $key2 ] ];
            //                  echo $value2;exit;
            //                  print_r($att);exit;
            if ($att[ 'isimage' ]) {
                //                      echo '<img src="'.$att['url'].'/'.$att['attachment'].'" />';exit;
                $imgtb_src = '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att[ 'aid' ] . '&size=10000x10000&key=' . md5($att[ 'aid' ] . '|10000|10000');
                //                $topic[ 'message' ] = str_replace($value2, '<img src="' . $att[ 'url' ] . $att[ 'attachment' ] . '" />', $topic[ 'message' ]);
                //                $pics[] = $imgtb_src;
                $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att[ 'aid' ] . '&size=200x200&key=' . md5($att[ 'aid' ] . '|200|200');
                $topic[ 'message' ] = str_replace($value2, '<img src="' . $imgtb_src . '" />', $topic[ 'message' ]);
            } else {
                $topic[ 'message' ] = str_replace($value2, '[附件请在网站中查看]', $topic[ 'message' ]);
            }
        }

        preg_match_all('/<img.*?src="(.*?)".*?>/is', $topic[ 'message' ], $mt);
        $mt[ 3 ] = array();
        $lazy_pic_count = 0;
        $lazy_pic_limit = 0;//前三张不懒加载
        //var_dump($mt[ 0 ]);die;
        foreach ($mt[ 0 ] as $key2 => $value2) {
            if (strpos($mt[ 1 ][ $key2 ], 'http') !== 0) {
                //论坛本地上传
                if (strpos($mt[ 1 ][ $key2 ], 'smiley')) {
                    //表情
                    $mt[ 3 ][ $key2 ] = '<img class="img-emoj" src="' . site_index_url . '/' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                } else {
                    //                    $imagesinfo = getimagesize(site_index_url . '/' . $mt[ 1 ][ $key2 ]);
                    //                    $imagesscale = number_format($imagesinfo[ 0 ] / $imagesinfo[ 1 ], 3);
                    $imagesscale = 1;
                    //                    $mt[ 3 ][ $key2 ] = '<img class="img-posted"  data-scale="' . $imagesscale . '"  style="max-width:' . $imagesinfo[ 0 ] . 'px;" src="" data-original="' . site_index_url . '/' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                    if($lazy_pic_count >= $lazy_pic_limit){
                        $mt[ 3 ][ $key2 ] =  '<img class="img-posted"  src="" data-original="' . site_index_url . '/' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                    }else{
                        $mt[ 3 ][ $key2 ] =  '<img  src="' . site_index_url . '/' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                        $lazy_pic_count++;
                    }
                    //$pics[] = site_index_url . '/' . $mt[ 1 ][ $key2 ];
                }
            } else {
                //远程图片
                //echo 1;die;
                //$this ->is_smile_pic($mt[ 1 ][ $key2 ]);
                if ($this ->is_smile_pic($mt[ 1 ][ $key2 ])) {
                    //表情
                    $mt[ 3 ][ $key2 ] = '<img class="img-emoj" src="' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                } else {
                    //                    $imagesinfo = getimagesize($mt[ 1 ][ $key2 ]);
                    //                    $imagesscale = number_format($imagesinfo[ 0 ] / $imagesinfo[ 1 ], 3);
                    $imagesscale = 1;
                    //                    $mt[ 3 ][ $key2 ] = '<img class="img-posted" data-scale="' . $imagesscale . '"  style="max-width:' . $imagesinfo[ 0 ] . 'px;" src="" data-original="' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                    if($lazy_pic_count >= $lazy_pic_limit){
                        $mt[ 3 ][ $key2 ] = '<img class="img-posted"  src="" data-original="' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                    }else{
                        $mt[ 3 ][ $key2 ] =  '<img  src="'. $mt[ 1 ][ $key2 ] . '" alt="" />';
                        $lazy_pic_count++;
                    }
                    $pics[] = $mt[ 1 ][ $key2 ] ;
                }
            }
        }
        $topic[ 'message' ] = str_replace($mt[ 0 ], $mt[ 3 ], $topic[ 'message' ]);

        //过滤图片链接
        preg_match_all('/<a([^>]*?)>(.*?)<\/a>/is', $topic[ 'message' ], $mtta);
        //         echo json_encode($mtta);exit;
        if($mtta[0]){
            foreach($mtta[0] as $keya => $valuea){
                // echo $valuea;exit;
                if(stripos($valuea, '<img')){
                    $topic[ 'message' ] = str_replace($valuea, $mtta[2][$keya], $topic[ 'message' ]);
                }else if(stripos($valuea, '@')){
                    $topic[ 'message' ] = str_replace($valuea, $mtta[2][$keya], $topic[ 'message' ]);
                }
            }
        }
        //过滤text-indent
        $topic[ 'message' ]  = str_replace('text-indent:', 'text - indent : ', $topic[ 'message' ] );

        $forum_option = $_G[ 'forum_option' ] ? $_G[ 'forum_option' ] : array();
        //        echo json_encode($forum_option);exit;
        foreach ($forum_option as $key => $value) {
            switch($value[ 'type' ]) {
                case 'image' :
                    //preg_match('/<a href="(.*?)" /', $value[ 'value' ], $mt);
                    if (strpos($value['value'], 'http') === false) {
                        $forum_option[ $key ][ 'value' ] = '<img src="' . site_index_url . '/' . $value['value'] . '" />';
                    } else {
                        $forum_option[ $key ][ 'value' ] = '<img src="' . $value['value'] . '" />';
                    }
                    break;
                case 'url' : break;
                    //                    preg_match('/<a href="(.*?)" /', $value[ 'value' ], $mt);
                    //                    $forum_option[ $key ][ 'value' ] =  $mt[ 1 ] ;
                default: $forum_option[ $key ][ 'value' ] = $forum_option[ $key ][ 'value' ].$forum_option[ $key ][ 'unit' ];
                    break;
            }
        }
        //echo json_encode($thread);exit;

        $iconsrc = array();

        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        if ($thread[ 'displayorder' ]) {
            $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/pin_' . $thread[ displayorder ] . '.png';
        }
        if ($thread[ 'digest' ]) {
            $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/digest_' . $thread[ digest ] . '.png';
        }
        if ($thread[ 'special' ] == 1) {
            $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/pollsmall.png';
        } elseif ($thread[ 'special' ] == 2) {
            $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/tradesmall.png';
        } elseif ($thread[ 'special' ] == 3) {
            $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/rewardsmall.png';
        } elseif ($thread[ 'special' ] == 4) {
            $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/activitysmall.png';
        } elseif ($thread[ 'special' ] == 5) {
            $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/debatesmall.png';
        }
        if ($thread[ 'icon' ] > 0) {
            $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/' . substr($_G[ 'cache' ][ 'stamps' ][ $thread[ icon ] ][ 'url' ],0,-3).'png';
        }

        $theahrow = array();
        $theahrow[ 'fid' ] = $thread[ 'fid' ];
        $theahrow[ 'forum_name' ] = $_G[ 'forum' ][ 'name' ];
        $theahrow[ 'tid' ] = $thread[ 'tid' ];
        $theahrow[ 'pid' ] = $topic[ 'pid' ];
        //echo json_encode($thread);die;
        $theahrow[ 'title' ] = $thread[ 'subject' ];
        $theahrow[ 'des' ] = mb_substr(str_replace(array("\r", "\n", "\t", '&nbsp;', ' '), '', strip_tags($topic[ 'message' ])), 0, 128, $_G['charset'] );

        //获取视频标签配置的提示语
        $get_video_sql = "SELECT replacement FROM pre_forum_bbcode WHERE tag = 'video'";
        $sql_result = DB::fetch_first($get_video_sql);
        if ($sql_result['replacement']) {
            $replacement = mb_substr(str_replace(array("\r", "\n", "\t", '&nbsp;', ' '), '', strip_tags($sql_result['replacement'])), 0, 128, $_G['charset'] );
            $share_des = str_replace($replacement, '', $theahrow[ 'des' ]);
            if (empty($share_des)) {
                $share_des = '点击查看详情';
            }
            $theahrow[ 'des' ] = $share_des;
        }

        //echo json_encode($theahrow);exit;
        $theahrow[ 'user_head' ] =$thread[ 'author' ] ? avatar($thread[ 'authorid' ], 'middle', true) : avatar(0, 'middle', true) ;
        $theahrow[ 'user_id' ] = $thread[ 'author' ] ? $thread[ 'authorid' ] : 0;
        $theahrow[ 'user_name' ] = $thread[ 'author' ] ? $thread[ 'author' ] : '匿名';
        $theahrow[ 'user_sex' ] = $topic[ 'gender' ];
        $theahrow[ 'status' ] = $topic[ 'status' ];
        $theahrow[ 'content' ] = $topic[ 'message' ];
        //$theahrow[ 'click' ] = click_count_format($thread[ 'views' ]);
        $theahrow[ 'click' ] = $thread[ 'views' ];
        $theahrow[ 'pics' ] =  array_values(array_unique($pics));;
        $theahrow[ 'iconsrc' ] = $iconsrc;
        $theahrow[ 'create_time' ] = $thread[ 'dateline' ];
        $theahrow[ 'create_time_ago' ] = date_ago($thread[ 'dateline' ]);
        $theahrow[ 'from' ] = $thread[ 'mobile' ] ? 'mobile' : 'pc';
        //帖子来源 0：PC 1：安卓 2：iOS
        $client = base_convert(getstatus($topic['status'], 15).getstatus($topic['status'], 14).getstatus($topic['status'], 13), 2, 10);
        $theahrow[ 'client' ] = $client;

        //        if ($modmenu[ 'thread' ] && $_G[ 'forum' ][ 'ismoderator' ] && $_G[ 'group' ][ 'allowdelpost' ]) {
        if ($modmenu[ 'thread' ] && $_G[ 'forum' ][ 'ismoderator' ] ) {
            $theahrow[ 'can_delete' ] = true;
            $theahrow[ 'delete_url' ] = '/mag/circle/v1/forum/threaddelete?fid=' . $topic[ 'fid' ] . '&tid=' . $topic[ 'tid' ];
            $theahrow[ 'can_manage' ] = true;
            //            $theahrow[ 'manage_url' ] = '/mag/circle/v1/forum/threadmanage?tid=' . $topic[ 'tid' ];
            $theahrow[ 'manage_options' ] = array(
                array(
                    'name' => '加精',
                    'type' => 'digest',
                    'child' => array(
                        array(
                            'name' => '解除',
                            'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=digest&value=0'
                        ),
                        array(
                            'name' => '精华1',
                            'child' => array(
                                array(
                                    'name' => '一天',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=digest&value=1&expiration='.date('Y-m-d H:i',time()+3600*24)
                                ),
                                array(
                                    'name' => '一周',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=digest&value=1&expiration='.date('Y-m-d H:i',time()+3600*24*7)
                                ),
                                array(
                                    'name' => '一个月',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=digest&value=1&expiration='.date('Y-m-d H:i',time()+3600*24*30)
                                ),
                                array(
                                    'name' => '三个月',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=digest&value=1&expiration='.date('Y-m-d H:i',time()+3600*24*90)
                                )
                            )
                        ),
                        array(
                            'name' => '精华2',
                            'child' => array(
                                array(
                                    'name' => '一天',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=digest&value=2&expiration='.date('Y-m-d H:i',time()+3600*24)
                                ),
                                array(
                                    'name' => '一周',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=digest&value=2&expiration='.date('Y-m-d H:i',time()+3600*24*7)
                                ),
                                array(
                                    'name' => '一个月',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=digest&value=2&expiration='.date('Y-m-d H:i',time()+3600*24*30)
                                ),
                                array(
                                    'name' => '三个月',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=digest&value=2&expiration='.date('Y-m-d H:i',time()+3600*24*90)
                                )
                            )
                        ),
                        array(
                            'name' => '精华3',
                            'child' => array(
                                array(
                                    'name' => '一天',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=digest&value=3&expiration='.date('Y-m-d H:i',time()+3600*24)
                                ),
                                array(
                                    'name' => '一周',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=digest&value=3&expiration='.date('Y-m-d H:i',time()+3600*24*7)
                                ),
                                array(
                                    'name' => '一个月',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=digest&value=3&expiration='.date('Y-m-d H:i',time()+3600*24*30)
                                ),
                                array(
                                    'name' => '三个月',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=digest&value=3&expiration='.date('Y-m-d H:i',time()+3600*24*90)
                                )
                            )
                        )
                    )
                ),
                array(
                    'name' => '置顶',
                    'type' => 'stick',
                    'child' => array(
                        array(
                            'name' => '解除',
                            'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=stick&value=0'
                        ),
                        array(
                            'name' => '本版置顶',
                            'child' => array(
                                array(
                                    'name' => '一天',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=stick&value=1&expiration='.date('Y-m-d H:i',time()+3600*24)
                                ),
                                array(
                                    'name' => '一周',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=stick&value=1&expiration='.date('Y-m-d H:i',time()+3600*24*7)
                                ),
                                array(
                                    'name' => '一个月',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=stick&value=1&expiration='.date('Y-m-d H:i',time()+3600*24*30)
                                ),
                                array(
                                    'name' => '三个月',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=stick&value=1&expiration='.date('Y-m-d H:i',time()+3600*24*90)
                                )
                            )
                        ),
                        array(
                            'name' => '分类置顶',
                            'child' => array(
                                array(
                                    'name' => '一天',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=stick&value=2&expiration='.date('Y-m-d H:i',time()+3600*24)
                                ),
                                array(
                                    'name' => '一周',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=stick&value=2&expiration='.date('Y-m-d H:i',time()+3600*24*7)
                                ),
                                array(
                                    'name' => '一个月',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=stick&value=2&expiration='.date('Y-m-d H:i',time()+3600*24*30)
                                ),
                                array(
                                    'name' => '三个月',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=stick&value=2&expiration='.date('Y-m-d H:i',time()+3600*24*90)
                                )
                            )
                        ),
                        array(
                            'name' => '全局置顶',
                            'child' => array(
                                array(
                                    'name' => '一天',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=stick&value=3&expiration='.date('Y-m-d H:i',time()+3600*24)
                                ),
                                array(
                                    'name' => '一周',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=stick&value=3&expiration='.date('Y-m-d H:i',time()+3600*24*7)
                                ),
                                array(
                                    'name' => '一个月',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=stick&value=3&expiration='.date('Y-m-d H:i',time()+3600*24*30)
                                ),
                                array(
                                    'name' => '三个月',
                                    'url'  => '/mag/circle/v1/forum/threadAdmin?tid='.$topic['tid'].'&fid='.$topic['fid'].'&operation=stick&value=3&expiration='.date('Y-m-d H:i',time()+3600*24*90)
                                )
                            )
                        )
                    )
                ),
                array(
                    'name' => '移动',
                    'type' => 'move',
                    'child' => thread_move_options($topic[ 'fid' ],$topic[ 'tid' ])
                ),
                array(
                    'name' => '删除',
                    'type' => 'delete',
                    'url' => $theahrow[ 'delete_url' ]
                ),
                array(
                    'name' => '屏蔽',
                    'type' => 'postBan',
                    'child' => array(
                        array(
                            'name' => '屏蔽',
                            'url' => '/mag/circle/v1/forum/postBan?tid='.$topic['tid'].'&fid='.$topic['fid'].'&pid='.$topic['pid'].'&banned=1'
                        ),
                        array(
                            'name' => '解除',
                            'url' => '/mag/circle/v1/forum/postBan?tid='.$topic['tid'].'&fid='.$topic['fid'].'&pid='.$topic['pid'].'&banned=0'
                        )
                    )
                ),
                array(
                    'name' => '编辑',
                    'type' => 'edit',
                    'url' => site_index_url .'/forum.php?mod=post&action=edit&fid='.$topic['fid'].'&tid='.$topic['tid'].'&pid='.$topic['pid']
                ),
                array(
                    'name' => '禁言',
                    'type' => 'bannedUser',
                    'child' => array(
                        array(
                            'name' => '禁言',
                            'url' => '/mag/user/v1/User/bannedUserPost?id='.$topic['authorid']
                        ),
                        array(
                            'name' => '解除',
                            'url' => '/mag/user/v1/User/cancleBannedUserPost?id='.$topic['authorid']
                        )
                    )
                ),
                array(
                    'name' => '打赏设置',
                    'type' => 'reward',
                    'child' => array(
                        array(
                            'name' => '开启',
                            'url' => '/mag/circle/v1/forum/setRewardOpen?tid='.$topic['tid'].'&status=1'
                        ),
                        array(
                            'name' => '关闭',
                            'url' => '/mag/circle/v1/forum/setRewardOpen?tid='.$topic['tid'].'&status=-1'
                        )
                    )
                ),
            );

        } elseif ($theahrow[ 'user_id' ] == $_G[ 'uid' ]) {
            $theahrow[ 'can_delete' ] = true;
            $theahrow[ 'delete_url' ] = '/mag/circle/v1/forum/postdelete?tid=' . $topic[ 'tid' ] . '&pid=' . $topic[ 'pid' ];
            $theahrow[ 'manage_url' ] = false;
        } else {
            $theahrow[ 'can_delete' ] = false;
            $theahrow[ 'can_manage' ] = false;
        }
        $theahrow[ 'content_base_url' ] = '';
        $theahrow[ 'is_top' ] = $thread[ 'displayorder' ];
        $theahrow[ 'digest' ] = $thread[ 'digest' ];
        $theahrow[ 'is_applaud' ] = C::t('forum_memberrecommend')->fetch_by_recommenduid_tid($_G['uid'], $thread[ 'tid' ]) ? true : false ;
        $theahrow[ 'highlight' ] = $thread[ 'highlight' ];
        $theahrow[ 'reply_count' ] = $thread[ 'replies' ];
        $theahrow[ 'forum_option' ] = $forum_option;


        if (!empty($GLOBALS[ 'threadsortshow' ])) {
            $optionlist = array();
            foreach ($GLOBALS[ 'threadsortshow' ][ 'optionlist' ] AS $key => $val) {
                $val[ 'optionid' ] = $key;
                $optionlist[] = $val;
            }
            if (!empty($optionlist)) {
                $GLOBALS[ 'threadsortshow' ][ 'optionlist' ] = $optionlist;
                $GLOBALS[ 'threadsortshow' ][ 'threadsortname' ] = $_G[ 'forum' ][ 'threadsorts' ][ 'types' ][ $thread[ 'sortid' ] ];
            }
        }
        $threadsortshow = getvalues($GLOBALS[ 'threadsortshow' ], array('/^(?!typetemplate).*$/'));
        if (!empty($threadsortshow)) {
            $variable[ 'threadsortshow' ] = $threadsortshow;
        }

        foreach ($GLOBALS[ 'aimgs' ] as $pid => $aids) {
            foreach ($aids as $aid) {
                $variable[ 'imagelist' ][] = $GLOBALS[ 'postlist' ][ $pid ][ 'attachments' ][ $aid ][ 'url' ] . $GLOBALS[ 'postlist' ][ $pid ][ 'attachments' ][ $aid ][ 'attachment' ];
            }
        }

        if (!empty($GLOBALS[ 'polloptions' ])) {
            $variable[ 'special_poll' ][ 'polloptions' ] = $GLOBALS[ 'polloptions' ];
            $variable[ 'special_poll' ][ 'expirations' ] = $GLOBALS[ 'expirations' ];
            $variable[ 'special_poll' ][ 'multiple' ] = $GLOBALS[ 'multiple' ];
            $variable[ 'special_poll' ][ 'maxchoices' ] = $GLOBALS[ 'maxchoices' ];
            $variable[ 'special_poll' ][ 'voterscount' ] = $GLOBALS[ 'voterscount' ];
            $variable[ 'special_poll' ][ 'visiblepoll' ] = $GLOBALS[ 'visiblepoll' ];
            $variable[ 'special_poll' ][ 'allowvote' ] = $_G[ 'group' ][ 'allowvote' ];
            $variable[ 'special_poll' ][ 'remaintime' ] = $thread[ 'remaintime' ];
            foreach ($variable[ 'special_poll' ][ 'polloptions' ] as $key => $value) {
                if ($value[ 'imginfo' ]) {
                    if(strpos($variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'attachment' ],'http') !== false) {
                        $variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'attachment' ] = $variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'attachment' ];
                    } else {
                        $variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'attachment' ] = site_index_url . '/' . $variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'attachment' ];
                    }
                    if(strpos($variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'small' ],'http') !== false) {
                        $variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'small' ] = $variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'small' ];
                    } else {
                        $variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'small' ] = site_index_url . '/' . $variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'small' ];
                    }
                    if(strpos($variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'big' ],'http') !== false) {
                        $variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'big' ] = $variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'big' ];
                    } else {
                        $variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'big' ] = site_index_url . '/' . $variable[ 'special_poll' ][ 'polloptions' ][ $key ][ 'imginfo' ][ 'big' ];
                    }
                }
            }
            if ($_G[ 'group' ][ 'allowvote' ] && $GLOBALS[ 'overt' ]) {
                $variable[ 'special_poll' ][ 'pollmassage' ] = '此为公开投票，其他人可看到您的投票项目';
            } else if (!$GLOBALS[ 'allwvoteusergroup' ]) {
                $variable[ 'special_poll' ][ 'pollmassage' ] = '您所在的用户组没有投票权限';
            } else if (!$GLOBALS[ 'allowvotepolled' ]) {
                $variable[ 'special_poll' ][ 'pollmassage' ] = '您已经投过票，谢谢您的参与';
            } else if (!$GLOBALS[ 'allowvotethread' ]) {
                $variable[ 'special_poll' ][ 'pollmassage' ] = '该投票已经关闭或者过期，不能投票';
            }

            $theahrow[ 'special_poll' ] = $variable[ 'special_poll' ];


        }
        if (!empty($GLOBALS[ 'rewardprice' ])) {
            $variable[ 'special_reward' ][ 'rewardprice' ] = $GLOBALS[ 'rewardprice' ] . ' ' . $_G[ 'setting' ][ 'extcredits' ][ $_G[ 'setting' ][ 'creditstransextra' ][ 2 ] ][ 'title' ];
            $variable[ 'special_reward' ][ 'bestpost' ] = $GLOBALS[ 'bestpost' ];
        }
        if (!empty($GLOBALS[ 'trades' ])) {
            $variable[ 'special_trade' ] = $GLOBALS[ 'trades' ];
        }
        if (!empty($GLOBALS[ 'debate' ])) {
            $variable[ 'special_debate' ] = $GLOBALS[ 'debate' ];
        }
        if (!empty($GLOBALS[ 'activity' ])) {
            $variable[ 'special_activity' ] = $GLOBALS[ 'activity' ];
            $theahrow[ 'special_activity' ] = $variable[ 'special_activity' ];
            $theahrow[ 'special_activity' ][ 'thumb' ] = site_index_url . '/' . $theahrow[ 'special_activity' ][ 'thumb' ];
            $theahrow[ 'special_activity' ][ 'attachurl' ] = site_index_url . '/' . $theahrow[ 'special_activity' ][ 'attachurl' ];
            $theahrow[ 'special_activity' ][ 'ufield' ][ 'userfield' ] = $theahrow[ 'special_activity' ][ 'ufield' ][ 'userfield' ] ? $theahrow[ 'special_activity' ][ 'ufield' ][ 'userfield' ] : array();
            $theahrow[ 'special_activity' ][ 'applylist' ] = $GLOBALS[ 'applylist' ];
            foreach ($theahrow[ 'special_activity' ][ 'applylist' ] as $key => $value) {
                $theahrow[ 'special_activity' ][ 'applylist' ][ $key ][ 'userhead' ] = avatar($value[ 'uid' ], 'small', true);
            }
            //            echo $GLOBALS['aboutmembers'];exit;
            if ($GLOBALS[ 'applied' ] && $GLOBALS[ 'isverified' ] < 2) {
                if (!$GLOBALS[ 'isverified' ]) {
                    //                      activity_wait;
                    $theahrow[ 'special_activity' ][ 'activitymasseage' ] = '您的加入申请已发出，请等待发起者审批';
                } else {
                    //                      activity_join_audit;
                    $theahrow[ 'special_activity' ][ 'activitymasseage' ] = '您已经参加了此活动';
                }
                if (!$GLOBALS[ 'activityclose' ]) {
                    //退出
                    $theahrow[ 'special_activity' ][ 'button' ][ 'text' ] = '取消报名';
                    $theahrow[ 'special_activity' ][ 'button' ][ 'action' ] = 'cancel';
                }
            } elseif (!$GLOBALS[ 'activityclose' ]) {
                if ($GLOBALS[ 'isverified' ] != 2) {
                    if (!$GLOBALS[ 'activity' ][ 'number' ] || $GLOBALS[ 'aboutmembers' ] > 0) {
                        $theahrow[ 'special_activity' ][ 'button' ][ 'text' ] = '我要参加';
                        $theahrow[ 'special_activity' ][ 'button' ][ 'action' ] = 'join';
                        //参加||登录
                    }
                } else {
                    //                    '完善资料';
                    $theahrow[ 'special_activity' ][ 'button' ] = '完善资料';
                    //complete_data
                }

            }


            switch($theahrow[ 'special_activity' ][ 'gender' ]) {
                case 0 :
                    $theahrow[ 'special_activity' ][ 'gender' ] = '不限';
                    break;
                case 1 :
                    $theahrow[ 'special_activity' ][ 'gender' ] = '男';
                    break;
                case 2 :
                    $theahrow[ 'special_activity' ][ 'gender' ] = '女';
                    break;
            }
            if ($GLOBALS[ 'applylistverified' ]) {
                foreach ($GLOBALS[ 'applylistverified' ] as &$applylistverifiedvalue) {
                    $applylistverifiedvalue[ 'ufielddata' ] = unserialize($applylistverifiedvalue[ 'ufielddata' ]);
                }
            }
            $theahrow[ 'special_activity' ][ 'applylistverified' ] = $GLOBALS[ 'applylistverified' ];
            $theahrow[ 'special_activity' ][ 'allapplynum' ] = $GLOBALS[ 'allapplynum' ];
        }

        if (!empty($GLOBALS[ 'rushreply' ])) {
            $theahrow[ 'rushresult' ] = $GLOBALS[ 'rushresult' ];
            $theahrow[ 'rushresult' ][ 'starttimefromdateline' ] = strtotime($theahrow[ 'rushresult' ][ 'starttimefrom' ] . ':00');
            $theahrow[ 'rushresult' ][ 'starttimetodateline' ] = strtotime($theahrow[ 'rushresult' ][ 'starttimeto' ] . ':00');
        }

//        $theahrow['content'] = parseflv($theahrow['content']);
        $variable = array('threadrow' => $theahrow, 'ppp' => $_G[ 'ppp' ],);
        if (!empty($_G[ 'hookscriptmessage' ])) {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {

            success('', $variable);
        }
    }


    public function commentListCommon(){
        global $_G;
        $_G[ 'ppp' ] = $_GET[ 'step' ] ? $_GET[ 'step' ] : $_G[ 'ppp' ];
        //兼容超级活动插件xj_event
        $_SERVER['HTTP_REFERER'] = 'http://wsq.discuz.com';
    }

    public function commentListOutput(){
        global $_G, $thread;
        if ($GLOBALS[ 'hiddenreplies' ]) {
            foreach ($GLOBALS[ 'postlist' ] as $k => $post) {
                if (!$post[ 'first' ] && $_G[ 'uid' ] != $post[ 'authorid' ] && $_G[ 'uid' ] != $_G[ 'forum_thread' ][ 'authorid' ] && !$_G[ 'forum' ][ 'ismoderator' ]) {
                    $GLOBALS[ 'postlist' ][ $k ][ 'message' ] = '<div class="locked">'.lang('plugin/mobile', 'mobile_post_author_visible').'</div>';
                    $GLOBALS[ 'postlist' ][ $k ][ 'attachments' ] = array();
                }
            }
        }
        foreach ($GLOBALS['postlist'] as $k => $post) {
            if (!$_G[ 'forum' ][ 'ismoderator' ] && $_G[ 'setting' ][ 'bannedmessages' ] & 1 && (($post[ 'authorid' ] && !$post[ 'username' ]) || ($_G[ 'thread' ][ 'digest' ] == 0 && ($post[ 'groupid' ] == 4 || $post[ 'groupid' ] == 5 || $post[ 'memberstatus' ] == '-1')))) {
                $message = '<div class="locked">'.lang('forum/template', 'message_banned').'</div>';
            } elseif (!$_G[ 'forum' ][ 'ismoderator' ] && $post[ 'status' ] & 1) {
                $message = '<div class="locked">'.lang('forum/template', 'message_single_banned').'</div>';
            } elseif ($GLOBALS[ 'needhiddenreply' ]) {
                $message = '<div class="locked">'.lang('forum/template', 'message_ishidden_hiddenreplies').'</div>';
            } elseif ($post[ 'first' ] && $_G[ 'forum_threadpay' ]) {
                $message = '<div class="locked">'.lang('forum/template', 'pay_threads') . ' ' . $GLOBALS[ 'thread' ][ 'price' ] . ' ' . $_G[ 'setting' ][ 'extcredits' ][ $_G[ 'setting' ][ 'creditstransextra' ][ 1 ] ][ 'unit' ] . $_G[ 'setting' ][ 'extcredits' ][ $_G[ 'setting' ][ 'creditstransextra' ][ 1 ] ][ 'title' ].'</div>';
            } elseif ($_G[ 'forum_discuzcode' ][ 'passwordlock' ]) {
                $message = '<div class="locked">'.lang('forum/template', 'message_password_exists').'</div>';
                $message = preg_replace( "@<script(.*?)</script>@is", "", $message );
                $message = strip_tags($message);
            } else {
                $message = '';
            }
            if ($message) {
                $GLOBALS[ 'postlist' ][ $k ][ 'message' ] = $message;
            }else{
                if($_G['setting']['bannedmessages'] & 1 && (($post['authorid'] && !$post['username']) || ($_G['thread']['digest'] == 0 && ($post['groupid'] == 4 || $post['groupid'] == 5 || $post['memberstatus'] == '-1')))) {
                    $GLOBALS['postlist'][$k]['message'] = '<div class="locked">' . lang('forum/template', 'admin_message_banned') . '</div>'.$GLOBALS['postlist'][$k]['message'];
                } elseif($post['status'] & 1) {
                    $GLOBALS['postlist'][$k]['message'] = '<div class="locked">'.lang('forum/template', 'admin_message_single_banned').'</div>'.$GLOBALS['postlist'][$k]['message'];
                }
            }
            $GLOBALS['postlist'][$k]['message'] = preg_replace("/<a\shref=\"([^\"]+?)\"\starget=\"_blank\">\[viewimg\]<\/a>/is", "<img src=\"\\1\" />", $GLOBALS['postlist'][$k]['message']);
        }

        $_G[ 'thread' ][ 'lastpost' ] = dgmdate($_G[ 'thread' ][ 'lastpost' ]);
        $thread = $_G[ 'thread' ];
        $thread[ 'mobile' ] = base_convert(getstatus($thread[ 'status' ], 13) . getstatus($thread[ 'status' ], 12) . getstatus($thread[ 'status' ], 11), 2, 10);

        $rushreply = getstatus($_G[ 'forum_thread' ][ 'status' ], 3);
        $allowblockrecommend = $_G[ 'group' ][ 'allowdiy' ] || getstatus($_G[ 'member' ][ 'allowadmincp' ], 4) || getstatus($_G[ 'member' ][ 'allowadmincp' ], 5) || getstatus($_G[ 'member' ][ 'allowadmincp' ], 6);
        if ($_G[ 'setting' ][ 'portalstatus' ]) {
            $allowpostarticle = $_G[ 'group' ][ 'allowmanagearticle' ] || $_G[ 'group' ][ 'allowpostarticle' ] || getstatus($_G[ 'member' ][ 'allowadmincp' ], 2) || getstatus($_G[ 'member' ][ 'allowadmincp' ], 3);
            $allowpusharticle = empty($_G[ 'forum_thread' ][ 'special' ]) && empty($_G[ 'forum_thread' ][ 'sortid' ]) && !$_G[ 'forum_thread' ][ 'pushedaid' ];
        } else {
            $allowpostarticle = $allowpusharticle = false;
        }
        if ($_G[ 'forum_thread' ][ 'displayorder' ] != -4) {
            $modmenu = array('thread' => $_G[ 'forum' ][ 'ismoderator' ] || $allowblockrecommend || $allowpusharticle && $allowpostarticle, 'post' => $_G[ 'forum' ][ 'ismoderator' ] && ($_G[ 'group' ][ 'allowwarnpost' ] || $_G[ 'group' ][ 'allowbanpost' ] || $_G[ 'group' ][ 'allowdelpost' ] || $_G[ 'group' ][ 'allowstickreply' ]) || $_G[ 'forum_thread' ][ 'pushedaid' ] && $allowpostarticle || $_G[ 'forum_thread' ][ 'authorid' ] == $_G[ 'uid' ]);
        } else {
            $modmenu = array();
        }
        if ($_G[ 'page' ] == 1) {
            $topic = array_shift($GLOBALS[ 'postlist' ]);
        }


        $postlist = array();
        foreach ($GLOBALS[ 'postlist' ] as $key => $value) {
            $postrow = array();
            $to_commentrow = array();
            $postrow[ 'id' ] = $value[ 'pid' ];
            $postrow[ 'tid' ] = $value[ 'tid' ];

            //帖子来源 0：PC 1：安卓 2：iOS
            $client = base_convert(getstatus($value['status'], 15).getstatus($value['status'], 14).getstatus($value['status'], 13), 2, 10);
            $postrow[ 'client' ] = $client;

            if(strpos($_G['setting']['version'] ,'X3') === 0){
                $postrow[ 'is_applaud' ] = C::t('forum_hotreply_member')->fetch($value['pid'], $_G['uid']) ? true: false;
                $hotreply_number = C::t('forum_hotreply_number')->fetch($value['pid']) ;
                $postrow[ 'applaud_count' ] = (int)$hotreply_number['support'];
            }
            $postrow[ 'user_head' ] = avatar($value[ 'authorid' ], 'middle', true);
            $postrow[ 'user_id' ] = $value[ 'authorid' ];
            $postrow[ 'user_name' ] = $value[ 'author' ];
            $postrow[ 'user_sex' ] = $value[ 'gender' ];
            $pics = array();
            //          if($key==142){
            preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $value[ 'message' ], $matchaids);
            //          echo json_encode($matchaids);exit;
            //            echo json_encode($value[ 'attachments' ]);exit;
            foreach ($matchaids[ 0 ] as $key2 => $value2) {
                $att = $value[ 'attachments' ][ $matchaids[ 1 ][ $key2 ] ];
                //                  echo $value2;exit;
                //                  print_r($att);exit;
                if ($att[ 'isimage' ]) {
                    $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att[ 'aid' ] . '&size=1024x1024&key=' . md5($att[ 'aid' ] . '|1024|1024');
                    //                      echo '<img src="'.$att['url'].'/'.$att['attachment'].'" />';exit;
                    $imgtb_src = '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att[ 'aid' ] . '&size=1024x1024&key=' . md5($att[ 'aid' ] . '|1024|1024');
                    $value[ 'message' ] = str_replace($value2, '<img src="' . $imgtb_src . '" />', $value[ 'message' ]);
                    //                    $GLOBALS[ 'postlist' ][ $key ][ 'message' ] = str_replace($value2, '<img src="' . $att[ 'url' ] . $att[ 'attachment' ] . '" />', $GLOBALS[ 'postlist' ][ $key ][ 'message' ]);
                } else {
                    $value[ 'message' ] = str_replace($value2, '[附件请在网站中查看]', $value[ 'message' ]);
                }
            }



            preg_match_all('/<img.*?src="(.*?)".*?>/is', $value[ 'message' ], $mt);
            $mt[ 3 ] = array();
            foreach ($mt[ 0 ] as $key2 => $value2) {
                if (strpos($mt[ 1 ][ $key2 ], 'http') !== 0) {
                    //论坛本地上传
                    if (strpos($mt[ 1 ][ $key2 ], 'smiley')) {
                        //表情
                        $mt[ 3 ][ $key2 ] = '<img class="img-emoj" src="' . site_index_url . '/' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                    } else {
                        //                        $imagesinfo = getimagesize(site_index_url . '/' . $mt[ 1 ][ $key2 ]);
                        //                        $imagesscale = number_format($imagesinfo[ 0 ] / $imagesinfo[ 1 ], 3);
                        //                        $mt[ 3 ][ $key2 ] = '<img class="img-posted"  data-scale="' . $imagesscale . '"  style="max-width:' . $imagesinfo[ 0 ] . 'px;" src="" data-original="' . site_index_url . '/' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                        $mt[ 3 ][ $key2 ] = '<img class="img-posted"  src="" data-original="' . site_index_url . '/' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                    }
                } else {
                    //远程图片
                    if ($this ->is_smile_pic($mt[ 1 ][ $key2 ])) {
                        //表情
                        $mt[ 3 ][ $key2 ] = '<img class="img-emoj" src="' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                    } else {
                        $pics[] = $mt[ 1 ][ $key2 ];
                        //                        $imagesinfo = getimagesize($mt[ 1 ][ $key2 ]);
                        //                        $imagesscale = number_format($imagesinfo[ 0 ] / $imagesinfo[ 1 ], 3);
                        //                        $mt[ 3 ][ $key2 ] = '<img class="img-posted" data-scale="' . $imagesscale . '"  style="max-width:' . $imagesinfo[ 0 ] . 'px;" src="" data-original="' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                        $mt[ 3 ][ $key2 ] = '<img class="img-posted"  src="" data-original="' . $mt[ 1 ][ $key2 ] . '" alt="" />';

                    }
                }
            }
            $value[ 'message' ] = str_replace($mt[ 0 ], $mt[ 3 ], $value[ 'message' ]);
            //            echo json_encode($mt);exit;
            //            foreach($mt[ 1 ] as $key => $value){
            //                $value[ 'message' ] = str_replace($value)
            //            }
            $postrow[ 'pics' ] = $pics;

            //              echo $value['message'];exit;
            preg_match('/<div class=\"quote\".*?>.*?<\/div><br \/>/ism', $value[ 'message' ], $mtp);
            //              echo json_encode($mtp);exit;
            $tocommentstring = $mtp[ 0 ];
            if (!empty($tocommentstring)) {
                $postrow[ 'content' ] = str_replace($tocommentstring, '', $value[ 'message' ]);
                //                  preg_match('/<a href="(.*?)" /',$tocommentstring,$mt);
                $mpid = array();
                preg_match_all("/(\w+=\w+)(#\w+)?/i", $tocommentstring, $mpid);
                $tocommentpid = str_replace('pid=', '', $mpid[ 0 ][ 2 ]);
                //                  echo $tocommentpid;exit;
                $to_comment = $post = C::t('forum_post')->fetch('tid:' . $thread[ 'tid' ], $tocommentpid);
                //                  echo json_encode($to_comment);exit;
                $to_commentrow[ 'id' ] = $to_comment[ 'pid' ];
                $to_commentrow[ 'user_head' ] = avatar($to_comment[ 'authorid' ], 'small', true);
                $to_commentrow[ 'user_id' ] = $to_comment[ 'authorid' ];
                $to_commentrow[ 'user_name' ] = $to_comment[ 'author' ];
                $to_commentrow[ 'user_sex' ] = 1;


                $to_commentrow[ 'content' ] = preg_replace("/\[quote\]([\s\S]*)\[\/quote\]/i", '', $to_comment[ 'message' ]);
                $to_commentrow[ 'content' ] = discuzcode($to_commentrow[ 'content' ]);
                $to_commentrow[ 'content' ] = preg_replace("/\[attach\](\d+)\[\/attach\]/i", '', $to_commentrow[ 'content' ]);
                $to_commentrow[ 'content' ] = strip_tags($to_commentrow[ 'content' ]);

                $to_commentrow[ 'is_publisher' ] = $value[ 'authorid' ] == $topic[ 'authorid' ] ? true : false;
                $to_commentrow[ 'time' ] = date('Y-m-d H:i', strip_tags($to_comment[ 'dateline' ]));
                $to_commentrow[ 'floor' ] = $to_comment[ 'position' ];
                //                  echo json_encode($to_commentrow);exit;
            } else {
                $postrow[ 'content' ] = $value[ 'message' ];
            }


            $postrow[ 'is_publisher' ] = $value[ 'authorid' ] == $topic[ 'authorid' ] ? true : false;
            $postrow[ 'time' ] = str_replace('&nbsp;', ' ', strip_tags($value[ 'dateline' ]));
            $postrow[ 'floor' ] = $value[ 'position' ];
            $postrow[ 'is_stick' ] = $value[ 'isstick' ] ? true : false;

            if ($modmenu[ 'thread' ]  ) {


            }
            if ($modmenu[ 'post' ] && $_G[ 'forum' ][ 'ismoderator' ] && !$rushreply){
                $postrow[ 'can_delete' ] = false;
                $postrow[ 'can_manage' ] = true;
                $postrow[ 'delete_url' ] = '/mag/circle/v1/forum/postadmindelete?fid='.$value[ 'fid' ].'&tid=' . $value[ 'tid' ] . '&pid=' . $value[ 'pid' ];
                $postrow[ 'manage_url' ] = '/mag/circle/v1/forum/postManage?fid='.$value[ 'fid' ].'&tid=' . $value[ 'tid' ] . '&pid=' . $value[ 'pid' ].'&user_id='.$postrow[ 'user_id' ];
            } else if ($postrow[ 'user_id' ] == $_G[ 'uid' ] && !$rushreply) {
                $postrow[ 'can_delete' ] = true;
                $postrow[ 'delete_url' ] = '/mag/circle/v1/forum/postdelete?tid=' . $value[ 'tid' ] . '&pid=' . $value[ 'pid' ];
            } else {
                $postrow[ 'can_delete' ] = false;
            }
            $postrow[ 'to_comment' ] = $to_commentrow;


            $postlist[] = $postrow;
        }

        if (!empty($GLOBALS[ 'threadsortshow' ])) {
            $optionlist = array();
            foreach ($GLOBALS[ 'threadsortshow' ][ 'optionlist' ] AS $key => $val) {
                $val[ 'optionid' ] = $key;
                $optionlist[] = $val;
            }
            if (!empty($optionlist)) {
                $GLOBALS[ 'threadsortshow' ][ 'optionlist' ] = $optionlist;
                $GLOBALS[ 'threadsortshow' ][ 'threadsortname' ] = $_G[ 'forum' ][ 'threadsorts' ][ 'types' ][ $thread[ 'sortid' ] ];
            }
        }
        $threadsortshow = getvalues($GLOBALS[ 'threadsortshow' ], array('/^(?!typetemplate).*$/'));
        if (!empty($threadsortshow)) {
            $variable[ 'threadsortshow' ] = $threadsortshow;
        }
        foreach ($variable[ 'postlist' ] as $k => $post) {
            if (!$_G[ 'forum' ][ 'ismoderator' ] && $_G[ 'setting' ][ 'bannedmessages' ] & 1 && (($post[ 'authorid' ] && !$post[ 'username' ]) || ($_G[ 'thread' ][ 'digest' ] == 0 && ($post[ 'groupid' ] == 4 || $post[ 'groupid' ] == 5 || $post[ 'memberstatus' ] == '-1')))) {
                $message = lang('forum/template', 'message_banned');
            } elseif (!$_G[ 'forum' ][ 'ismoderator' ] && $post[ 'status' ] & 1) {
                $message = lang('forum/template', 'message_single_banned');
            } elseif ($GLOBALS[ 'needhiddenreply' ]) {
                $message = lang('forum/template', 'message_ishidden_hiddenreplies');
            } elseif ($post[ 'first' ] && $_G[ 'forum_threadpay' ]) {
                $message = lang('forum/template', 'pay_threads') . ' ' . $GLOBALS[ 'thread' ][ 'price' ] . ' ' . $_G[ 'setting' ][ 'extcredits' ][ $_G[ 'setting' ][ 'creditstransextra' ][ 1 ] ][ 'unit' ] . $_G[ 'setting' ][ 'extcredits' ][ $_G[ 'setting' ][ 'creditstransextra' ][ 1 ] ][ 'title' ];
            } elseif ($_G[ 'forum_discuzcode' ][ 'passwordlock' ]) {
                $message = lang('forum/template', 'message_password_exists');
            } else {
                $message = '';
            }
            if ($message) {
                $variable[ 'postlist' ][ $k ][ 'message' ] = $message;
            }
            if ($post[ 'anonymous' ] && !$_G[ 'forum' ][ 'ismoderator' ]) {
                $variable[ 'postlist' ][ $k ][ 'username' ] = $variable[ 'postlist' ][ $k ][ 'author' ] = $_G[ 'setting' ][ 'anonymoustext' ];
                $variable[ 'postlist' ][ $k ][ 'adminid' ] = $variable[ 'postlist' ][ $k ][ 'groupid' ] = $variable[ 'postlist' ][ $k ][ 'authorid' ] = 0;
                if ($post[ 'first' ]) {
                    $variable[ 'thread' ][ 'authorid' ] = 0;
                }
            }
            if (strpos($variable[ 'postlist' ][ $k ][ 'message' ], '[/tthread]') !== FALSE) {
                $matches = array();
                preg_match('/\[tthread=(.+?),(.+?)\](.*?)\[\/tthread\]/', $variable[ 'postlist' ][ $k ][ 'message' ], $matches);
                $variable[ 'postlist' ][ $k ][ 'message' ] = preg_replace('/\[tthread=(.+?)\](.*?)\[\/tthread\]/', lang('plugin/qqconnect', 'connect_tthread_message', array('username' => $matches[ 1 ], 'nick' => $matches[ 2 ])), $variable[ 'postlist' ][ $k ][ 'message' ]);
            }
        }

        foreach ($GLOBALS[ 'aimgs' ] as $pid => $aids) {
            foreach ($aids as $aid) {
                $variable[ 'imagelist' ][] = $GLOBALS[ 'postlist' ][ $pid ][ 'attachments' ][ $aid ][ 'url' ] . $GLOBALS[ 'postlist' ][ $pid ][ 'attachments' ][ $aid ][ 'attachment' ];
            }
        }

        if ($_G[ 'page' ] > $GLOBALS[ 'totalpage' ]) {
            $postlist = array();
        }
        $variable = array('postlist' => $postlist, 'maxpage' => $GLOBALS[ 'totalpage' ], 'step' => $_G[ 'ppp' ], 'page' => $GLOBALS[ 'page' ], 'count' => $_G[ 'forum_thread' ][ 'replies' ]);
        if (!empty($_G[ 'hookscriptmessage' ])) {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            success('', $variable);
        }

    }


    public function threadAddCommon(){
    }

    public function threadAddOutput(){
        global $_G;
        if (!in_array( $_G[ 'hookscriptmessage' ],array('post_newthread_succeed','post_newthread_mod_succeed'))) {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            //magapp尾巴
            $status = 0;
            $thread = C::t('forum_thread')->fetch($GLOBALS['tid']);
            if($_POST['phone_type'] == 'ios'){
                $status = 8200;
            }else if($_POST['phone_type'] == 'android'){
                $status = 4104;
            }
            if($status){
                $status = setstatus(4, 0, $status);
                $status = setstatus(6, 1, $status);
                C::t('forum_post')->update($thread['posttableid'], $GLOBALS[ 'pid' ], array('status' => $status));
                C::t('forum_thread')->update($GLOBALS['tid'], array('status' => $status));
            }

            //图片模式加封面
            $imglist = array();
            preg_match_all("/(\[img\]|\[img=\d{1,4}[x|\,]\d{1,4}\])\s*([^\[\<\r\n]+?)\s*\[\/img\]/is", $_POST['message'], $imglist, PREG_SET_ORDER);
            $imgurl = $imglist[0][2];
            $aid = 0;
            require_once libfile('function/post');
            if($imgurl) {
                $tid = $GLOBALS[ 'tid' ];
                $pid = $GLOBALS[ 'pid' ];
                setthreadcover($pid, $tid, $aid, 0, $imgurl);
            }


            $msg = '';
            if($_G[ 'hookscriptmessage' ] == 'post_newthread_succeed'){
                $msg = '发布成功';
            }
            success($msg, array('tid'=>$GLOBALS['tid'] ));
        }
    }

    public function threadAddColumnInfoCommon(){
    }


    public function threadAddColumnInfoOutput(){
        global $_G;
        $threadtypes = array();
        $threadtypes[ 'required' ] = $_G[ 'forum' ][ 'threadtypes' ][ 'required' ];
        $threadtypes[ 'types' ] = array();
        if ($_G[ 'forum' ][ 'threadtypes' ][ 'types' ]) {
            foreach ($_G[ 'forum' ][ 'threadtypes' ][ 'types' ] as $key => $value) {
                $type = array();
                $type[ 'name' ] = strip_tags($value);
                $type[ 'id' ] = $key;
                $threadtypes[ 'types' ][] = $type;
            }
        }

        $forum_optionlist = array();
        $forum_optionlist[ 'types' ] = array();
        $forum_optionlist[ 'required' ] = $_G[ 'forum' ][ 'threadsorts' ][ 'required' ];

        if ($_G[ 'forum_optionlist' ]) {
            foreach ($_G[ 'forum_optionlist' ] as $key => $value) {
                $value['title'] = $value['unit'] ? $value['title'].'('.$value['unit'].')': $value['title'];
                $option = $value;
                $option[ 'id' ] = $key;
                switch($value[ 'type' ]) {
                    case 'select' :
                        foreach ($option[ 'choices' ] as $key2 => $value2) {
                            $option[ 'choices' ][ $key2 ][ 'name' ] = $value2[ 'content' ];
                            $option[ 'choices' ][ $key2 ][ 'value' ] = $value2[ 'optionid' ];
                        }
                        $option[ 'choices' ] = array_values($option[ 'choices' ]);
                        $option[ 'choices' ] = list_to_tree($option[ 'choices' ], $pk = 'optionid', $foptionid = 'foptionid', $child = 'children', $root = 0);

                        $option[ 'key' ] = 'typeoption[' . $option[ 'identifier' ] . ']';

                        break;
                    case 'radio' :

                        $option[ 'choices' ] = array();
                        foreach ($value[ 'choices' ] as $key2 => $value2) {
                            $choice = array();
                            $choice[ 'name' ] = $value2;
                            $choice[ 'value' ] = (string)$key2;
                            $option[ 'choices' ] [] = $choice;
                        }
                        $option[ 'key' ] = 'typeoption[' . $option[ 'identifier' ] . ']';

                        break;

                    case 'checkbox' :
                        $option[ 'choices' ] = array();
                        foreach ($value[ 'choices' ] as $key2 => $value2) {
                            $choice = array();
                            $choice[ 'name' ] = $value2;
                            $choice[ 'value' ] = (string)$key2;
                            $option[ 'choices' ] [] = $choice;
                        }
                        $option[ 'key' ] = 'typeoption[' . $option[ 'identifier' ] . ']';
                        $option[ 'valueformat' ] = '[value1,value2]';

                        break;
                    case 'image' :
                        $option[ 'key' ] = 'typeoption[' . $option[ 'identifier' ] . '][url]';
                        break;

                    default:
                        $option[ 'key' ] = 'typeoption[' . $option[ 'identifier' ] . ']';
                }

                $forum_optionlist[ 'types' ][] = $option;
            }
        }
        $variable = array('forum' => $_G[ 'forum' ], 'threadtypes' => $threadtypes, 'forum_optionlist' => $forum_optionlist);
        if (!empty($_G[ 'hookscriptmessage' ])) {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            success('', $variable);
        }
    }

    public function replyAddCommon(){
        if ($_POST[ 'reppid' ]) {
            require_once libfile('function/discuzcode');
            $_GET[ 'reppost' ] = $_POST[ 'reppost' ];
            $tocommentpid = (int)$_POST[ 'reppid' ];
            $to_comment = C::t('forum_post')->fetch('tid:' . $_POST[ 'tid' ], $tocommentpid);
            $to_comment[ 'message' ] = preg_replace("/\[quote\]([\s\S]*)\[\/quote\]/i", '', $to_comment[ 'message' ]);
            $to_comment[ 'message' ] = discuzcode($to_comment[ 'message' ]);
            $to_comment[ 'message' ] = preg_replace("/\[attach\](\d+)\[\/attach\]/i", '', $to_comment[ 'message' ]);
            $to_comment[ 'message' ] = trim(strip_tags($to_comment[ 'message' ]));
            //        echo json_encode($to_comment);exit;
            $_GET[ 'noticeauthor' ] = $_POST[ 'noticeauthor' ] = dhtmlspecialchars(authcode('q|'.$to_comment['authorid'], 'ENCODE'));
            $_GET[ 'noticetrimstr' ] = $_POST[ 'noticetrimstr' ] = '[quote][size=2][url=forum.php?mod=redirect&goto=findpost&pid=' . $tocommentpid . '&ptid=' . $_POST[ 'tid' ] . '][color=#999999]' . $to_comment[ 'author' ] . ' 发表于 ' . date('Y-m-d H:i', $to_comment[ 'dateline' ]) . '[/color][/url][/size]'."\n" . $to_comment[ 'message' ] . '[/quote]';
        }
    }

    public function replyAddOutput(){
        global $_G;
        if ($_G[ 'hookscriptmessage' ] != 'post_reply_succeed') {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            $postrow = array();
            $to_commentrow = array();
            $postrow[ 'id' ] = $GLOBALS[ 'pid' ];
            $postrow[ 'user_head' ] = avatar($_G[ 'uid' ], 'small', true);
            $postrow[ 'user_id' ] = $_G[ 'uid' ];
            $postrow[ 'user_name' ] = $_G[ 'member' ][ 'username' ];
            $postrow[ 'user_sex' ] = 0;
            $postrow[ 'content' ] = discuzcode($_POST[ 'message' ]);
            $postrow['content'] = preg_replace("/<a\shref=\"([^\"]+?)\"\starget=\"_blank\">\[viewimg\]<\/a>/is", "<img src=\"\\1\" />", $postrow['content']);
            preg_match_all('/<img.*?src="(.*?)".*?>/is', $postrow[ 'content' ], $mt);
            $mt[ 3 ] = array();
            foreach ($mt[ 0 ] as $key2 => $value2) {
                if (strpos($mt[ 1 ][ $key2 ], 'http') !== 0) {
                    //论坛本地上传
                    if (strpos($mt[ 1 ][ $key2 ], 'smiley')) {
                        //表情
                        $mt[ 3 ][ $key2 ] = '<img class="img-emoj" src="' . site_index_url . '/' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                    } else {
                        //                        $imagesinfo = getimagesize(site_index_url . '/' . $mt[ 1 ][ $key2 ]);
                        //                        $imagesscale = number_format($imagesinfo[ 0 ] / $imagesinfo[ 1 ], 3);
                        //                        $mt[ 3 ][ $key2 ] = '<img class="img-posted"  data-scale="' . $imagesscale . '"  style="max-width:' . $imagesinfo[ 0 ] . 'px;" src="" data-original="' . site_index_url . '/' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                        $mt[ 3 ][ $key2 ] = '<img class="img-posted" src="" data-original="' . site_index_url . '/' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                    }
                } else {
                    //远程图片
                    if ($this ->is_smile_pic($mt[ 1 ][ $key2 ])) {
                        //表情
                        $mt[ 3 ][ $key2 ] = '<img class="img-emoj" src="' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                    } else {
                        $pics[] = $mt[ 1 ][ $key2 ];
                        //                        $imagesinfo = getimagesize($mt[ 1 ][ $key2 ]);
                        //                        $imagesscale = number_format($imagesinfo[ 0 ] / $imagesinfo[ 1 ], 3);
                        //                        $mt[ 3 ][ $key2 ] = '<img class="img-posted" data-scale="' . $imagesscale . '"  style="max-width:' . $imagesinfo[ 0 ] . 'px;" src="" data-original="' . $mt[ 1 ][ $key2 ] . '" alt="" />';
                        $mt[ 3 ][ $key2 ] = '<img class="img-posted" src="" data-original="' . $mt[ 1 ][ $key2 ] . '" alt="" />';

                    }
                }
            }
            //            echo json_encode($mt);exit;
            $postrow[ 'content' ] = str_replace($mt[ 0 ], $mt[ 3 ], $postrow[ 'content' ]);
            //            echo json_encode($mt);exit;
            //            foreach($mt[ 1 ] as $key => $value){
            //                $value[ 'message' ] = str_replace($value)
            //            }
            $postrow[ 'pics' ] = $pics;

            if (!empty($_POST[ 'reppid' ])) {
                $mpid = array();
                $tocommentpid = $_POST[ 'reppid' ];
                //                  echo $tocommentpid;exit;
                $to_comment = $post = C::t('forum_post')->fetch('tid:' . $_POST[ 'tid' ], $tocommentpid);
                //                  echo json_encode($to_comment);exit;
                $to_commentrow[ 'id' ] = $to_comment[ 'pid' ];
                $to_commentrow[ 'user_head' ] = avatar($to_comment[ 'authorid' ], 'small', true);
                $to_commentrow[ 'user_id' ] = $to_comment[ 'authorid' ];
                $to_commentrow[ 'user_name' ] = $to_comment[ 'author' ];
                $to_commentrow[ 'user_sex' ] = 1;

                $to_commentrow[ 'content' ] = preg_replace("/\[quote\]([\s\S]*)\[\/quote\]/i", '', $to_comment[ 'message' ]);
                $to_commentrow[ 'content' ] = discuzcode($to_commentrow[ 'content' ]);
                $to_commentrow[ 'content' ] = preg_replace("/\[attach\](\d+)\[\/attach\]/i", '', $to_commentrow[ 'content' ]);
                $to_commentrow[ 'content' ] = strip_tags($to_commentrow[ 'content' ]);

                $to_commentrow[ 'time' ] = date_ago($to_comment[ 'dateline' ]);
                $to_commentrow[ 'floor' ] = $to_comment[ 'position' ];
                //                  echo json_encode($to_commentrow);exit;
            }


            $postrow[ 'is_publisher' ] = false;
            $postrow[ 'time' ] = date_ago(time());
            $postrow[ 'floor' ] = '';

            $postrow[ 'can_delete' ] = true;
            if ($postrow[ 'can_delete' ]) {
                $postrow[ 'delete_url' ] = '/mag/circle/v1/forum/postdelete?tid=' . $_POST[ 'tid' ] . '&pid=' . $GLOBALS[ 'pid' ];
            }
            $postrow[ 'to_comment' ] = $to_commentrow;


            //magapp尾巴
            $status = 0;
            $thread = C::t('forum_thread')->fetch($GLOBALS['tid']);
            if($_POST['phone_type'] == 'ios'){
                $status = 8200;
            }else if($_POST['phone_type'] == 'android'){
                $status = 4104;
            }
            if($status){
                $thread = C::t('forum_thread')->fetch($_POST['tid']);
                C::t('forum_post')->update($thread['posttableid'], $GLOBALS[ 'pid' ], array('status' => $status));
            }
            success('', $postrow);
        }
    }


    public function pullCommon(){


    }


    public function pullOutput(){
        global $_G;
        if ($_G[ 'hookscriptmessage' ] != 'thread_poll_succeed') {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            success('', array());
        }
    }

    public function activitySubmitCommon(){

    }


    public function activitySubmitOutput(){
        global $_G;
        if ($_G[ 'hookscriptmessage' ] != 'activity_completion') {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            success('', array());
        }
    }

    public function activityCancelCommon(){

    }

    public function activityCancelOutput(){
        global $_G;
        if ($_G[ 'hookscriptmessage' ] != 'activity_cancel_success') {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            success('', array());
        }
    }

    public function activityOperationCommon(){

    }

    public function activityOperationOutput(){
        global $_G;
        switch($_POST[ 'operation' ]) {
            case 'delete' :
                $successmessageval = 'activity_delete_completion';
                break;
            default :
                $successmessageval = 'activity_auditing_completion';
        }
        if ($_G[ 'hookscriptmessage' ] != $successmessageval) {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            success('', array());
        }
    }


    /**
     * 帖子管理: 加精,置顶
     */
    public function threadAdminCommon(){
        global $_G;
        $_G['group']['reasonpm'] = 2;
    }

    public function threadAdminOutput(){
        global $_G;
        //        echo json_encode($_G);exit;
        //        echo $_G[ 'hookscriptmessage' ];exit;
        $successmessageval  = 'admin_succeed';
        if ($_G[ 'hookscriptmessage' ] != $successmessageval) {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            success('', array());
        }
    }

    /**
     * 屏蔽帖子
     */
    public function postBanCommon(){
        global $_G;
        $_G['group']['reasonpm'] = 2;
    }

    public function postBanOutput(){
        global $_G;
        //        echo json_encode($_G);exit;
        //        echo $_G[ 'hookscriptmessage' ];exit;
        $successmessageval  = 'admin_succeed';
        if ($_G[ 'hookscriptmessage' ] != $successmessageval) {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            success('', array());
        }
    }


    /**
     *用户禁言
     */
    public function userBanCommon(){
        global $_G;
        $member = array();
        $_GET['banexpirynew'] = $_GET['daylimit'];
        $tableext = '';
        lang('admincp_msg');
        if(empty($_GET['uid']) && empty($_GET['username'])) {
            error($_G[ 'lang' ][ 'admincp_msg' ][  'members_nonexistence' ], array(), 'members_nonexistence');
        }
        $member = !empty($_GET['uid']) ? C::t('common_member')->fetch($_GET['uid'], false, 1) : C::t('common_member')->fetch_by_username($_GET['username'], 1);
        if(!$member) {
            error($_G[ 'lang' ][ 'admincp_msg' ][  'members_edit_nonexistence' ], array(), 'members_edit_nonexistence');
        }
        $tableext = isset($member['_inarchive']) ? '_archive' : '';


        $membermf = C::t('common_member_field_forum'.$tableext)->fetch($_GET['uid']);
        $membergroup = C::t('common_usergroup')->fetch($member['groupid']);
        $membergroupfield = C::t('common_usergroup_field')->fetch($member['groupid']);
        $member = array_merge($member, $membermf, $membergroup, $membergroupfield);
        //        echo json_encode($member);exit;
        if(($member['type'] == 'system' && in_array($member['groupid'], array(1, 2, 3, 6, 7, 8))) || $member['type'] == 'special') {
            error( strip_tags( str_replace(array('{grouptitle}','{uid}'),
                array($member['grouptitle'],$member['uid']),
                $_G[ 'lang' ][ 'admincp_msg' ][  'members_edit_illegal' ])), array(), 'members_edit_illegal');
        }

        if($member['allowadmincp']) {
            error($_G[ 'lang' ][ 'admincp_msg' ][  'members_edit_illegal_portal' ], array(), 'members_edit_illegal_portal');
        }

        $member['groupterms'] = dunserialize($member['groupterms']);
        $member['banexpiry'] = !empty($member['groupterms']['main']['time']) && ($member['groupid'] == 4 || $member['groupid'] == 5) ? dgmdate($member['groupterms']['main']['time'], 'Y-n-j') : '';


        if($_GET['banned'] ) {
            $groupidnew =   4  ;
            $_GET['banexpirynew'] = !empty($_GET['banexpirynew']) ? TIMESTAMP + $_GET['banexpirynew'] * 86400 : 0;
            $_GET['banexpirynew'] = $_GET['banexpirynew'] > TIMESTAMP ? $_GET['banexpirynew'] : 0;
            if($_GET['banexpirynew']) {
                $member['groupterms']['main'] = array('time' => $_GET['banexpirynew'], 'adminid' => $member['adminid'], 'groupid' => $member['groupid']);
                $member['groupterms']['ext'][$groupidnew] = $_GET['banexpirynew'];
                $setarr['groupexpiry'] = groupexpiry($member['groupterms']);
            } else {
                $setarr['groupexpiry'] = 0;
            }
            $adminidnew = -1;
            $my_data['expiry'] = groupexpiry($member['groupterms']);
            $postcomment_cache_pid = array();
            foreach(C::t('forum_postcomment')->fetch_all_by_authorid($member['uid']) as $postcomment) {
                $postcomment_cache_pid[$postcomment['pid']] = $postcomment['pid'];
            }
            C::t('forum_postcomment')->delete_by_authorid($member['uid'], false, true);
            if($postcomment_cache_pid) {
                C::t('forum_postcache')->delete($postcomment_cache_pid);
            }
            if(!$member['adminid']) {
                $member_status = C::t('common_member_status')->fetch($member['uid']);
                if($member_status) {
                    //captcha::report($member_status['lastip']);
                }
            }
        } else {
            if(!empty($member['groupterms']['main']['groupid'])) {
                $groupidnew = $member['groupterms']['main']['groupid'];
                $adminidnew = $member['groupterms']['main']['adminid'];
                unset($member['groupterms']['main']);
                unset($member['groupterms']['ext'][$member['groupid']]);
                $setarr['groupexpiry'] = groupexpiry($member['groupterms']);
            }
            $groupnew = C::t('common_usergroup')->fetch_by_credits($member['credits']);
            $groupidnew = $groupnew['groupid'];
            $adminidnew = 0;
        }

        $setarr['adminid'] = $adminidnew;
        $setarr['groupid'] = $groupidnew;
        $setarr['status'] = $_GET['bannew'] == 'status' ? -1 : 0;
        C::t('common_member'.$tableext)->update($member['uid'], $setarr);
        C::t('common_member_field_forum'.$tableext)->update($member['uid'],array('groupterms' => ($member['groupterms'] ? serialize($member['groupterms']) : '')));

        success('');
    }

    public function userBanOutput(){

    }


    public function imageThumbCommon(){
        global $_G;
        if (empty($_GET[ 'aid' ]) || empty($_GET[ 'size' ]) || empty($_GET[ 'key' ])) {
            header('location: ' . site_index_url . 'static/image/common/none.gif');
            exit;
        }
        //$allowsize = array('960x960', '268x380', '266x698', '2000x2000');
        //if(!in_array($_GET['size'], $allowsize)) {
        //    header('location: '.$_G['siteurl'].'static/image/common/none.gif');
        //    exit;
        //}
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        $nocache = !empty($_GET[ 'nocache' ]) ? 1 : 0;
        $daid = intval($_GET[ 'aid' ]);
        //$type = !empty($_GET[ 'type' ]) ? $_GET[ 'type' ] : 'fixwr';
        $type = !empty($_GET[ 'type' ]) ? $_GET[ 'type' ] : 'fixnone';
        list($w, $h) = explode('x', $_GET[ 'size' ]);
        $dw = intval($w);
        $dh = intval($h);
        $thumbfile = 'image/' . $daid . '_' . $dw . '_' . $dh . '.jpg';
        $parse = parse_url($_G[ 'setting' ][ 'attachurl' ]);
        $attachurl = !isset($parse[ 'host' ]) ? $_G[ 'siteurl' ] . $_G[ 'setting' ][ 'attachurl' ] : $_G[ 'setting' ][ 'attachurl' ];
        if (!$nocache && !$_MAG_CONFIG['thumb_url']) {
            if (file_exists($_G[ 'setting' ][ 'attachdir' ] . $thumbfile)) {
                if(strpos($_G[ 'setting' ][ 'attachurl' ],'http') !== 0 ) {
                    dheader('location: ' . site_index_url . '/' . $_G[ 'setting' ][ 'attachurl' ] . $thumbfile);
                } else {
                    dheader('location: '. $_G[ 'setting' ][ 'attachurl' ] . $thumbfile);
                }
            }
        }
        define('NOROBOT', TRUE);

        $id = !empty($_GET[ 'atid' ]) ? $_GET[ 'atid' ] : $daid;
        //if(md5($id.'|'.$dw.'|'.$dh) != $_GET['key']) {
        //    dheader('location: '.site_index_url.'/static/image/common/none.gif');
        //}

        if ($attach = C::t('forum_attachment_n')->fetch('aid:' . $daid, $daid, array(1, -1))) {
            //if(!$dw && !$dh && $attach['tid'] != $id) {
            //   dheader('location: '.site_index_url.'/static/image/common/none.gif');
            //}
            if($_MAG_CONFIG['thumb_url']){
                header('location: '.  $_MAG_CONFIG['thumb_url']. '/forum/' . $attach[ 'attachment' ].(strpos($attach[ 'attachment' ],'?') ===false ? '?':'&'.'x-oss-process=image/resize,m_mfit,w_'.$dw.'&imageView2/3/w/'.$dw.'|imageslim";'));
                exit;
            }
            dheader('Expires: ' . gmdate('D, d M Y H:i:s', TIMESTAMP + 3600) . ' GMT');
            if ($attach[ 'remote' ]) {
                $filename = strpos($attach[ 'attachment' ],'http' ) === 0 ? $attach[ 'attachment' ] : $_G[ 'setting' ][ 'ftp' ][ 'attachurl' ] . 'forum/' . $attach[ 'attachment' ];
            } else {
                $filename = $_G[ 'setting' ][ 'attachdir' ] . 'forum/' . $attach[ 'attachment' ];
            }
            //            echo $filename;exit;
            //            echo $filename;exit;
            $image_info = getimagesize($filename);
            if ($w > $image_info[ '0' ]) {
                $w = $image_info[ '0' ];
            }
            if ($h > $image_info[ '1' ]) {
                $h = $image_info[ '1' ];
            }
            require_once libfile('class/image');
            $img = new image;
            if ($img->Thumb($filename, $thumbfile, $w, $h, $type)) {
                if ($nocache) {
                    dheader('Content-Type: image');
                    @readfile($_G[ 'setting' ][ 'attachdir' ] . $thumbfile);
                    @unlink($_G[ 'setting' ][ 'attachdir' ] . $thumbfile);
                } else {
                    if(strpos($_G[ 'setting' ][ 'attachurl' ],'http') !== 0 ) {
                        dheader('location: ' . site_index_url . '/' . $_G[ 'setting' ][ 'attachurl' ] . $thumbfile);
                    } else {
                        dheader('location: '. $_G[ 'setting' ][ 'attachurl' ] . $thumbfile);
                    }
                    //dheader('location: ' . site_index_url . '/' . $_G[ 'setting' ][ 'attachurl' ] . $thumbfile);
                }
            } else {
                dheader('Content-Type: image');
                @readfile($filename);
            }
        }
        exit;
    }

    public function imageThumbOutput(){
    }

    public function threadToInfoListCommon(){
        global $_G;
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        if(!empty($_MAG_CONFIG['emotion_url_contain'][0])) {
            //echo $_MAG_CONFIG['emotion_url_contain'][0];die;
            $emotion_url_contain = explode(',',$_MAG_CONFIG['emotion_url_contain'][0]);
        }
        $fid = explode(',',$_GET['fids']);
        if(count($fid)==1)$fid = $fid[0];
        $dateline = (int)$_GET['dateline']  ;
        $lastpost = (int)$_GET['lastpost']  ;
        $click = (int)$_GET['click']  ;
        $reply_count = (int)$_GET['reply_count']  ;
        $digest = (int)$_GET['digest']  ;
        $wherearr = array();
        $parameter = array('forum_thread');
        $orderby = 'tid desc';
        if(!empty($fid)) {
            $parameter[] = $fid;
            $wherearr[] = is_array($fid) && $fid ? 'fid IN(%n)' : 'fid=%d';
        }
        if (!empty($click)) {
            $parameter[] = $click;
            $wherearr[] = 'views >= %d';
        }
        if (!empty($reply_count)) {
            $parameter[] = $reply_count;
            $wherearr[] = 'replies >= %d';
        }
        if(!empty($digest)){
            //            $parameter[] = $digest;
            $wherearr[] =  'digest >0';
        }
        if(!empty($dateline)){
            $parameter[] = $dateline;
            $wherearr[] =  'dateline >= %d';
        }
        if(!empty($lastpost)){
            $parameter[] = $lastpost;
            $wherearr[] =  'lastpost >= %d';
        }
        $wheresql = !empty($wherearr) && is_array($wherearr) ? ' WHERE '.implode(' AND ', $wherearr) : '';
        //$total_count = count(DB::fetch_all("SELECT tid FROM %t $wheresql  and displayorder>=0 ", $parameter, ''));
        $data =  DB::fetch_all("SELECT * FROM %t $wheresql  and displayorder>=0 ORDER BY ".$orderby." ".DB::limit(0, 100), $parameter, '');
        $list = array();
        if (is_array($data)) {
            foreach ($data as $key => $threadvalue) {
                $row = array();
                $posttablename = 'forum_post'.($threadvalue['posttableid'] ? "_".$threadvalue['posttableid'] : '');
                $content = DB::fetch_first("SELECT * FROM " . DB::table($posttablename) . " WHERE first=1 and tid=" . $threadvalue[ 'tid' ]);
                if(!$content) continue;
                //          $_G['forum_threadlist'][$k]['_post'] = $content;

                $pics = array();
                preg_match_all("/\[img.*\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/i", $content[ 'message' ], $imgmatchaids);
                if ($imgmatchaids[ 1 ]) {
                    foreach ($imgmatchaids[ 1 ] as $key => $value) {
                        if(!empty($emotion_url_contain)) {
                            foreach($emotion_url_contain as $v) {
                                if (strpos($value, $v) !== false) {
                                    //array_splice($imgmatchaids[1], $key, 1);
                                    continue 2;
                                }
                            }
                            $pics[] = $value;
                            /*$flag = true;
                            foreach($emotion_url_contain as $v) {
                                $flag &= !strpos($value, $v);
                            }
                            if($flag) {
                                $pics[] = $value;
                            }*/
                        }
                    }
                }
                /*从附件表中取缩略图*/
                $attlist = DB::fetch_all("SELECT * FROM " . DB::table('forum_attachment') . " WHERE tid=" . $threadvalue[ 'tid' ]." and pid=".$content[ 'pid' ]);
                if ($attlist) {
                    //              echo json_encode($attmatchaids[1]);exit;
                    foreach ($attlist as $att) {
                        if ($att) {
                            $att2 = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment_' . $att[ 'tableid' ]) . " WHERE aid=" . $att[ 'aid' ]);
                            //                        echo json_encode($att2);exit;
                            if ($att2[ 'isimage' ]) {
                                //$pics[] = site_index_url . '/data/attachment/forum/' . $att2[ 'attachment' ];
                                $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att2[ 'aid' ] . '&size=200x200&key=' . md5($att2[ 'aid' ] . '|200|200');
                            }
                        }
                    }
                }
                /* 从正文中取缩略图
                    preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $content[ 'message' ], $attmatchaids);
                    if ($attmatchaids[ 1 ]) {
                        //              echo json_encode($attmatchaids[1]);exit;
                        foreach ($attmatchaids[ 1 ] as $value) {
                            $att = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment') . " WHERE aid=" . $value);
                            if ($att) {
                                $att2 = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment_' . $att[ 'tableid' ]) . " WHERE aid=" . $att[ 'aid' ]);
                                //                        echo json_encode($att2);exit;
                                if ($att2[ 'isimage' ]) {
                                    //                            $pics[] = site_index_url . '/data/attachment/forum/' . $att2[ 'attachment' ];
                                    $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att2[ 'aid' ] . '&size=200x200&key=' . md5($att2[ 'aid' ] . '|200|200');
                                }
                            }
                        }
                    }*/
                $forum = DB::fetch_first("SELECT * FROM " . DB::table('forum_forum') . " WHERE   fid=" . $threadvalue[ 'fid' ]);

                $row[ 'tid' ] = $threadvalue[ 'tid' ];
                $row[ 'title' ] = $threadvalue[ 'subject' ];
                $row[ 'pics' ] = array_values(array_unique($pics));
                $row[ 'fid' ] = $threadvalue[ 'fid' ];
                $row[ 'forum_name' ] = $forum[ 'name' ];
                $row[ 'user_id' ] = $threadvalue[ 'authorid' ];
                $row[ 'user_name' ] = $threadvalue[ 'author' ];
                $row[ 'user_head' ] =  avatar($threadvalue[ 'authorid' ], 'middle', true) ;
                $row[ 'click' ] = $threadvalue[ 'views' ];
                $row[ 'reply_count' ] = $threadvalue[ 'replies' ];
                $row[ 'create_time' ] = $threadvalue[ 'dateline' ];
                $row[ 'digest' ] = $threadvalue[ 'digest' ];
                if ($_GET[ 'pics' ] && empty($row[ 'pics' ])) {
                    continue;
                }
                $list[] = $row;
            }
        }
        success('', $list);
    }

    public function threadToInfoListOutput(){


    }

    public function threadSearchCommon(){
        global $_G;
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        if(!empty($_MAG_CONFIG['emotion_url_contain'][0])) {
            //echo $_MAG_CONFIG['emotion_url_contain'][0];die;
            $emotion_url_contain = explode(',',$_MAG_CONFIG['emotion_url_contain'][0]);
        }
        $conditions = '1 ';
        if(!$_GET[ 'fid' ]){
            //error('请选择板块');
        }
        if(!$_GET[ 'keywords' ]){
            //            error('请输入关键字');
        }
        if ($_GET[ 'fid' ]) {
            $conditions .= 'and fid  = ' . (int)$_GET[ 'fid' ] . ' ';
        }
        if ($_GET[ 'keywords' ]) {
            $conditions .= "and subject like '%".stripsearchkey($_GET[ 'keywords' ])."%'";
        }
        //        if ($_GET[ 'reply_count' ]) {
        //            $conditions .= 'and replies >= ' . (int)$_GET[ 'reply_count' ] . '  ';
        //        }
        //        if ($_GET[ 'digest' ]) {
        //            $conditions .= 'and digest != 0   ';
        //        }

        $threadtableids = !empty($_G[ 'cache' ][ 'threadtableids' ]) ? $_G[ 'cache' ][ 'threadtableids' ] : array();
        $tableid = $_GET[ 'archiveid' ] && in_array($_GET[ 'archiveid' ], $threadtableids) ? intval($_GET[ 'archiveid' ]) : 0;

        $tableid = intval($tableid);
        $tableName = $tableid ? "forum_thread_$tableid" : 'forum_thread';

        $page = $_GET['p'] <1 || $_GET['p'] >100  ? 1: (int)$_GET['p'];
        $step = (int)$_GET['step'];

        $sql = "SELECT * FROM " . DB::table($tableName) . "  WHERE   " . $conditions . ' and displayorder>=0 order by tid desc ' . DB::limit( ($page-1)*$step, $step);
        //                echo $sql ;exit;
        $data = DB::fetch_all($sql);
        $list = array();
        if (is_array($data)) {
            foreach ($data as $key => $threadvalue) {
                $row = array();
                $posttablename = 'forum_post'.($threadvalue['posttableid'] ? "_".$threadvalue['posttableid'] : '');
                $content = DB::fetch_first("SELECT * FROM " . DB::table($posttablename) . " WHERE first=1 and tid=" . $threadvalue[ 'tid' ]);
                if(!$content) continue;
                //          $_G['forum_threadlist'][$k]['_post'] = $content;

                $pics = array();
                preg_match_all("/\[img.*\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/i", $content[ 'message' ], $imgmatchaids);
                if ($imgmatchaids[ 1 ]) {
                    foreach ($imgmatchaids[ 1 ] as $key => $value) {
                        if(!empty($emotion_url_contain)) {
                            foreach($emotion_url_contain as $v) {
                                if (strpos($value, $v) !== false) {
                                    //array_splice($imgmatchaids[1], $key, 1);
                                    continue 2;
                                }
                            }
                            $pics[] = $value;
                            /*$flag = true;
                            foreach($emotion_url_contain as $v) {
                                $flag &= !strpos($value, $v);
                            }
                            if($flag) {
                                $pics[] = $value;
                            }*/
                        }
                    }
                }
                /*从附件表中取缩略图*/
                $attlist = DB::fetch_all("SELECT * FROM " . DB::table('forum_attachment') . " WHERE tid=" . $threadvalue[ 'tid' ]." and pid=".$content[ 'pid' ]);
                if ($attlist) {
                    //              echo json_encode($attmatchaids[1]);exit;
                    foreach ($attlist as $att) {
                        if ($att) {
                            $att2 = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment_' . $att[ 'tableid' ]) . " WHERE aid=" . $att[ 'aid' ]);
                            //                        echo json_encode($att2);exit;
                            if ($att2[ 'isimage' ]) {
                                //$pics[] = site_index_url . '/data/attachment/forum/' . $att2[ 'attachment' ];
                                $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att2[ 'aid' ] . '&size=200x200&key=' . md5($att2[ 'aid' ] . '|200|200');
                            }
                        }
                    }
                }
                /* 从正文中取缩略图
                    preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $content[ 'message' ], $attmatchaids);
                    if ($attmatchaids[ 1 ]) {
                        //              echo json_encode($attmatchaids[1]);exit;
                        foreach ($attmatchaids[ 1 ] as $value) {
                            $att = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment') . " WHERE aid=" . $value);
                            if ($att) {
                                $att2 = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment_' . $att[ 'tableid' ]) . " WHERE aid=" . $att[ 'aid' ]);
                                //                        echo json_encode($att2);exit;
                                if ($att2[ 'isimage' ]) {
                                    //                            $pics[] = site_index_url . '/data/attachment/forum/' . $att2[ 'attachment' ];
                                    $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att2[ 'aid' ] . '&size=200x200&key=' . md5($att2[ 'aid' ] . '|200|200');
                                }
                            }
                        }
                    }*/
                $forum = DB::fetch_first("SELECT * FROM " . DB::table('forum_forum') . " WHERE   fid=" . $threadvalue[ 'fid' ]);

                define('IMGIR' , '');
                $iconsrc = array();
                if ($threadvalue[ 'displayorder' ]) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/pin_' . $threadvalue[ displayorder ] . '.png';
                }
                if ($threadvalue[ 'digest' ]) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/digest_' . $threadvalue[ digest ] . '.png';
                }
                if ($threadvalue[ 'rushreply' ]) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/rushreply_s.png';
                }
                if ($threadvalue[ 'special' ] == 1) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/pollsmall.png';
                } elseif ($threadvalue[ 'special' ] == 2) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/tradesmall.png';
                } elseif ($threadvalue[ 'special' ] == 3) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/rewardsmall.png';
                } elseif ($threadvalue[ 'special' ] == 4) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/activitysmall.png';
                } elseif ($threadvalue[ 'special' ] == 5) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/debatesmall.png';
                }
                if ($threadvalue[ 'icon' ] > 0) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/' .substr( $_G[ 'cache' ][ 'stamps' ][ $threadvalue[ icon ] ][ 'url' ],0,-3).'png';
                }

                $row[ 'tid' ] = $threadvalue[ 'tid' ];
                $row[ 'typeid' ] = $threadvalue[ 'typeid' ];
                $row[ 'sortid' ] = $threadvalue[ 'sortid' ];
                $row[ 'dateline' ] = date_ago($threadvalue[ 'dateline' ]);
                $row[ 'lastpost' ] = date_ago($threadvalue[ 'lastpost' ]);

                $row['iconsrc'] = $iconsrc;
                $row[ 'subject' ] = $threadvalue[ 'subject' ];
                $row[ 'title' ] = $threadvalue[ 'subject' ].' ';
                if (is_array($row[ 'iconsrc' ])) {
                    foreach ($row[ 'iconsrc' ] as $iconvalue) {
                        $row[ 'title' ] .= '<img src="' . $iconvalue . '"" />';
                    }
                }

                $row[ 'pics' ] = array_values(array_unique($pics));
                $row[ 'fid' ] = $threadvalue[ 'fid' ];
                $row[ 'forum_name' ] = $forum[ 'name' ];
                $row[ 'user_id' ] = $threadvalue[ 'authorid' ];
                $row[ 'user_name' ] = $threadvalue[ 'author' ];
                $row[ 'user_head' ] =  avatar($threadvalue[ 'authorid' ], 'middle', true) ;
                $row[ 'click' ] = $threadvalue[ 'views' ];
                $row[ 'reply_count' ] = $threadvalue[ 'replies' ];
                $row[ 'create_time' ] = $threadvalue[ 'dateline' ];
                $row[ 'last_reply_time' ] = $threadvalue[ 'lastpost' ];
                $row[ 'digest' ] = $threadvalue[ 'digest' ];
                $row[ 'special' ] = $threadvalue[ 'special' ];
                $row[ 'is_top' ] =$threadvalue[ 'displayorder' ] ? 1 : -1;
                $list[] = $row;
            }
        }
        success('', $list);
    }


    public function threadSearchOutput(){


    }

    public function postDeleteCommon(){
        global $_G;
        $_G['group']['reasonpm'] = 2;
    }

    public function postDeleteOutput(){
        global $_G;
        if ($_G[ 'hookscriptmessage' ] != 'post_edit_delete_succeed') {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            success(array(), $_G[ 'hookscriptmessage' ]);
        }
    }


    public function threadDeleteCommon(){
        global $_G;
        $_G['group']['reasonpm'] = 2;
    }

    public function threadDeleteOutput(){
        global $_G;
        if ($_G[ 'hookscriptmessage' ] != 'admin_succeed') {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            success('', $_G[ 'hookscriptmessage' ]);
        }
    }
    public function threadMoveCommon(){
        global $_G;
        $_G['group']['reasonpm'] = 2;
    }

    public function threadMoveOutput(){
        global $_G;
        if ($_G[ 'hookscriptmessage' ] != 'admin_succeed') {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            success('', $_G[ 'hookscriptmessage' ]);
        }
    }

    public function postAdminDeleteCommon(){
        global $_G;
        $_G['group']['reasonpm'] = 2;
    }

    public function postAdminDeleteOutput(){
        global $_G;
        if ($_G[ 'hookscriptmessage' ] != 'admin_succeed') {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            success('', $_G[ 'hookscriptmessage' ]);
        }
    }

    public function threadRecommendCommon(){
        $_GET['hash'] = formhash();
        global $_G;
        $_G['group']['reasonpm'] = 2;
    }

    public function threadRecommendOutput(){
        global $_G;
        if ($_G[ 'hookscriptmessage' ] != 'recommend_succeed' && $_G[ 'hookscriptmessage' ] != 'recommend_daycount_succeed'   ) {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            success('', $_G[ 'hookscriptmessage' ]);
        }
    }
    public function postRecommendCommon(){
        global $_G;
        $_G['group']['reasonpm'] = 2;
        $_GET['hash'] = formhash();
    }

    public function postRecommendOutput(){
        global $_G;
        if($_G[ 'hookscriptmessage' ] == 'undefined_action' ){
            $_G['messageparam'] = '';
            error('该帖不支持点赞');
        }
        if ($_G[ 'hookscriptmessage' ] != 'thread_poll_succeed' ) {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        } else {
            success('', $_G[ 'hookscriptmessage' ]);
        }
    }

    public function threadListByUserIdsCommon(){
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        if(!empty($_MAG_CONFIG['emotion_url_contain'][0])) {
            //echo $_MAG_CONFIG['emotion_url_contain'][0];die;
            $emotion_url_contain = explode(',',$_MAG_CONFIG['emotion_url_contain'][0]);
        }
        $tableid = null;
        global $_G;
        if(!$_GET['user_ids']){
            success('',array());
        }
        $step = $_GET['step'] >0 ? (int)$_GET['step'] : 10;
        $dateline = $_GET['dateline'] > 0 ? (int)$_GET['dateline'] :time();
        $authorid = explode(',',$_GET['user_ids']);
        if(count($authorid)==1)$authorid = $authorid[0];
        $parameter = array('forum_thread');
        $wherearr = array();
        if(!empty($authorid)) {
            $parameter[] = $authorid;
            $wherearr[] = is_array($authorid) && $authorid ? 'authorid IN(%n)' : 'authorid=%d';
        }
        $parameter[] = $dateline;
        $wherearr[] =  'dateline < %d';
        $wheresql = !empty($wherearr) && is_array($wherearr) ? ' WHERE '.implode(' AND ', $wherearr) : '';
        $data =  DB::fetch_all("SELECT * FROM %t $wheresql  and displayorder>=0 ORDER BY dateline DESC ".DB::limit(0, $step), $parameter, '');

        $list = array();
        if (is_array($data)) {
            foreach ($data as $key => $threadvalue) {
                $row = array();
                $posttablename = 'forum_post'.($threadvalue['posttableid'] ? "_".$threadvalue['posttableid'] : '');
                $content = DB::fetch_first("SELECT * FROM " . DB::table($posttablename) . " WHERE first=1 and tid=" . $threadvalue[ 'tid' ]);
                if(!$content) continue;
                //          $_G['forum_threadlist'][$k]['_post'] = $content;

                $pics = array();
                $pics_big = array();
                preg_match_all("/\[img.*\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/i", $content[ 'message' ], $imgmatchaids);
                if ($imgmatchaids[ 1 ]) {
                    foreach ($imgmatchaids[ 1 ] as $key => $value) {
                        if(!empty($emotion_url_contain)) {
                            foreach($emotion_url_contain as $v) {
                                if (strpos($value, $v) !== false) {
                                    //array_splice($imgmatchaids[1], $key, 1);
                                    continue 2;
                                }
                            }
                            $pics[] = $value;
                            $pics_big[] = $value;
                            /*$flag = true;
                            foreach($emotion_url_contain as $v) {
                                $flag &= !strpos($value, $v);
                            }
                            if($flag) {
                                $pics[] = $value;
                            }*/
                        }
                    }
                }
                /*从附件表中取缩略图*/
                $attlist = DB::fetch_all("SELECT * FROM " . DB::table('forum_attachment') . " WHERE tid=" . $threadvalue[ 'tid' ]." and pid=".$content[ 'pid' ]);
                if ($attlist) {
                    //              echo json_encode($attmatchaids[1]);exit;
                    foreach ($attlist as $att) {
                        if ($att) {
                            $att2 = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment_' . $att[ 'tableid' ]) . " WHERE aid=" . $att[ 'aid' ]);
                            //                        echo json_encode($att2);exit;
                            if ($att2[ 'isimage' ]) {
                                if ($att2[ 'remote' ]) {
                                    $pics_big[] = strpos($att2[ 'attachment' ],'http' ) === 0 ? $att2[ 'attachment' ] : $_G[ 'setting' ][ 'ftp' ][ 'attachurl' ] . 'forum/' . $att2[ 'attachment' ];
                                } else {
                                    $pics_big[] = strpos($_G[ 'setting' ][ 'attachurl' ] ,'http' ) === 0 ? $_G[ 'setting' ][ 'attachurl' ] . 'forum/' . $att2[ 'attachment' ]:site_index_url.$_G[ 'setting' ][ 'attachurl' ] . 'forum/' . $att2[ 'attachment' ];
                                }
                                //$pics[] = site_index_url . '/data/attachment/forum/' . $att2[ 'attachment' ];
                                $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att2[ 'aid' ] . '&size=200x200&key=' . md5($att2[ 'aid' ] . '|200|200');
                            }
                        }
                    }
                }
                /* 从正文中取缩略图
                preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $content[ 'message' ], $attmatchaids);
                if ($attmatchaids[ 1 ]) {
                    //              echo json_encode($attmatchaids[1]);exit;
                    foreach ($attmatchaids[ 1 ] as $value) {
                        $att = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment') . " WHERE aid=" . $value);
                        if ($att) {
                            $att2 = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment_' . $att[ 'tableid' ]) . " WHERE aid=" . $att[ 'aid' ]);
                            //                        echo json_encode($att2);exit;
                            if ($att2[ 'isimage' ]) {
                                $pics_big[] = site_index_url . '/data/attachment/forum/' . $att2[ 'attachment' ];
                                $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att2[ 'aid' ] . '&size=200x200&key=' . md5($att2[ 'aid' ] . '|200|200');
                            }
                        }
                    }
                }*/
                $forum = DB::fetch_first("SELECT * FROM " . DB::table('forum_forum') . " WHERE   fid=" . $threadvalue[ 'fid' ]);

                define('IMGIR' , '');
                $iconsrc = array();
                //$_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
                if ($threadvalue[ 'displayorder' ]) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/pin_' . $threadvalue[ displayorder ] . '.png';
                }
                if ($threadvalue[ 'digest' ]) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/digest_' . $threadvalue[ digest ] . '.png';
                }
                if ($threadvalue[ 'rushreply' ]) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/rushreply_s.png';
                }
                if ($threadvalue[ 'special' ] == 1) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/pollsmall.png';
                } elseif ($threadvalue[ 'special' ] == 2) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/tradesmall.png';
                } elseif ($threadvalue[ 'special' ] == 3) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/rewardsmall.png';
                } elseif ($threadvalue[ 'special' ] == 4) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/activitysmall.png';
                } elseif ($threadvalue[ 'special' ] == 5) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/debatesmall.png';
                }
                if ($threadvalue[ 'icon' ] > 0) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/' .substr( $_G[ 'cache' ][ 'stamps' ][ $threadvalue[ icon ] ][ 'url' ],0,-3).'png';
                }

                $row[ 'tid' ] = $threadvalue[ 'tid' ];
                $row[ 'typeid' ] = $threadvalue[ 'typeid' ];
                $row[ 'sortid' ] = $threadvalue[ 'sortid' ];
                $row[ 'dateline' ] = date_ago($threadvalue[ 'dateline' ]);
                $row[ 'lastpost' ] = date_ago($threadvalue[ 'lastpost' ]);

                $row['iconsrc'] = $iconsrc;
                $row[ 'subject' ] = $threadvalue[ 'subject' ];
                $row[ 'title' ] = $threadvalue[ 'subject' ].' ';
                if (is_array($row[ 'iconsrc' ])) {
                    foreach ($row[ 'iconsrc' ] as $iconvalue) {
                        $row[ 'title' ] .= '<img src="' . $iconvalue . '"" />';
                    }
                }

                $row[ 'pics' ] = array_values(array_unique($pics));
                $row[ 'pics_big' ] = array_values(array_unique($pics_big));
                $row[ 'fid' ] = $threadvalue[ 'fid' ];
                $row[ 'forum_name' ] = $forum[ 'name' ];
                $row[ 'user_id' ] = $threadvalue[ 'authorid' ];
                $row[ 'user_name' ] = $threadvalue[ 'author' ];
                $row[ 'user_head' ] =  avatar($threadvalue[ 'authorid' ], 'middle', true) ;
                $row[ 'click' ] = $threadvalue[ 'views' ];
                $row[ 'reply_count' ] = $threadvalue[ 'replies' ];
                $row[ 'create_time' ] = $threadvalue[ 'dateline' ];
                $row[ 'last_reply_time' ] = $threadvalue[ 'lastpost' ];
                $row[ 'digest' ] = $threadvalue[ 'digest' ];
                $row[ 'special' ] = $threadvalue[ 'special' ];
                $row[ 'applaud_count' ] = $threadvalue[ 'recommend_add' ] +$threadvalue[ 'recommend_sub' ] ;
                $row[ 'is_top' ] =$threadvalue[ 'displayorder' ] ? 1 : -1;
                $list[] = $row;
            }
        }
        success('', $list);
    }

    public function threadListByUserIdsOutput(){

    }

    public function threadListByFidsCommon(){
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        if(!empty($_MAG_CONFIG['emotion_url_contain'][0])) {
            //echo $_MAG_CONFIG['emotion_url_contain'][0];die;
            $emotion_url_contain = explode(',',$_MAG_CONFIG['emotion_url_contain'][0]);
        }
        $tableid = null;
        global $_G;
        if(!$_GET['fids']){
            success('',array());
        }
        $step = $_GET['step'] >0 ? (int)$_GET['step'] : 20;
        $dateline = $_GET['dateline'] > 0 ? (int)$_GET['dateline'] :time();
        $fid = explode(',',$_GET['fids']);
        if(count($fid)==1)$fid = $fid[0];
        $parameter = array('forum_thread');
        $wherearr = array();
        if(!empty($fid)) {
            $parameter[] = $fid;
            $wherearr[] = is_array($fid) && $fid ? 'fid IN(%n)' : 'fid=%d';
        }
        $parameter[] = $dateline;
        $wherearr[] =  'dateline < %d';
        $wheresql = !empty($wherearr) && is_array($wherearr) ? ' WHERE '.implode(' AND ', $wherearr) : '';
        $data =  DB::fetch_all("SELECT * FROM %t $wheresql  and displayorder>=0 ORDER BY dateline DESC ".DB::limit(0, $step), $parameter, '');

        $list = array();
        if (is_array($data)) {
            foreach ($data as $key => $threadvalue) {
                $row = array();
                $posttablename = 'forum_post'.($threadvalue['posttableid'] ? "_".$threadvalue['posttableid'] : '');
                $content = DB::fetch_first("SELECT * FROM " . DB::table($posttablename) . " WHERE first=1 and tid=" . $threadvalue[ 'tid' ]);
                if(!$content) continue;
                //          $_G['forum_threadlist'][$k]['_post'] = $content;

                $pics = array();
                $pics_big = array();
                preg_match_all("/\[img.*\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/i", $content[ 'message' ], $imgmatchaids);
                if ($imgmatchaids[ 1 ]) {
                    foreach ($imgmatchaids[ 1 ] as $key => $value) {
                        if(!empty($emotion_url_contain)) {
                            foreach($emotion_url_contain as $v) {
                                if (strpos($value, $v) !== false) {
                                    //array_splice($imgmatchaids[1], $key, 1);
                                    continue 2;
                                }
                            }
                            $pics[] = $value;
                            $pics_big[] = $value;
                        }
                    }
                }

                /*从附件表中取缩略图*/
                $attlist = DB::fetch_all("SELECT * FROM " . DB::table('forum_attachment') . " WHERE tid=" . $threadvalue[ 'tid' ]." and pid=".$content[ 'pid' ]);
                if ($attlist) {
                    //              echo json_encode($attmatchaids[1]);exit;
                    foreach ($attlist as $att) {
                        if ($att) {
                            $att2 = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment_' . $att[ 'tableid' ]) . " WHERE aid=" . $att[ 'aid' ]);
                            //                        echo json_encode($att2);exit;
                            if ($att2[ 'isimage' ]) {
                                if ($att2[ 'remote' ]) {
                                    $pics_big[] = strpos($att2[ 'attachment' ],'http' ) === 0 ? $att2[ 'attachment' ] : $_G[ 'setting' ][ 'ftp' ][ 'attachurl' ] . 'forum/' . $att2[ 'attachment' ];
                                } else {
                                    $pics_big[] = strpos($_G[ 'setting' ][ 'attachurl' ] ,'http' ) === 0 ? $_G[ 'setting' ][ 'attachurl' ] . 'forum/' . $att2[ 'attachment' ]:site_index_url.$_G[ 'setting' ][ 'attachurl' ] . 'forum/' . $att2[ 'attachment' ];
                                }
                                //$pics[] = site_index_url . '/data/attachment/forum/' . $att2[ 'attachment' ];
                                $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att2[ 'aid' ] . '&size=200x200&key=' . md5($att2[ 'aid' ] . '|200|200');
                            }
                        }
                    }
                }
                /* 从正文中取缩略图
                preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $content[ 'message' ], $attmatchaids);
                if ($attmatchaids[ 1 ]) {
                    //              echo json_encode($attmatchaids[1]);exit;
                    foreach ($attmatchaids[ 1 ] as $value) {
                        $att = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment') . " WHERE aid=" . $value);
                        if ($att) {
                            $att2 = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment_' . $att[ 'tableid' ]) . " WHERE aid=" . $att[ 'aid' ]);
                            //                        echo json_encode($att2);exit;
                            if ($att2[ 'isimage' ]) {
                                $pics_big[] = site_index_url . '/data/attachment/forum/' . $att2[ 'attachment' ];
                                $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att2[ 'aid' ] . '&size=200x200&key=' . md5($att2[ 'aid' ] . '|200|200');
                            }
                        }
                    }
                }*/
                $forum = DB::fetch_first("SELECT * FROM " . DB::table('forum_forum') . " WHERE   fid=" . $threadvalue[ 'fid' ]);

                define('IMGIR' , '');
                $iconsrc = array();
                if ($threadvalue[ 'displayorder' ]) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/pin_' . $threadvalue[ displayorder ] . '.png';
                }
                if ($threadvalue[ 'digest' ]) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/digest_' . $threadvalue[ digest ] . '.png';
                }
                if ($threadvalue[ 'rushreply' ]) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/rushreply_s.png';
                }
                if ($threadvalue[ 'special' ] == 1) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/pollsmall.png';
                } elseif ($threadvalue[ 'special' ] == 2) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/tradesmall.png';
                } elseif ($threadvalue[ 'special' ] == 3) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/rewardsmall.png';
                } elseif ($threadvalue[ 'special' ] == 4) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/activitysmall.png';
                } elseif ($threadvalue[ 'special' ] == 5) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/debatesmall.png';
                }
                if ($threadvalue[ 'icon' ] > 0) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/' .substr( $_G[ 'cache' ][ 'stamps' ][ $threadvalue[ icon ] ][ 'url' ],0,-3).'png';
                }

                $row[ 'tid' ] = $threadvalue[ 'tid' ];
                $row[ 'typeid' ] = $threadvalue[ 'typeid' ];
                $row[ 'sortid' ] = $threadvalue[ 'sortid' ];
                $row[ 'dateline' ] = date_ago($threadvalue[ 'dateline' ]);
                $row[ 'lastpost' ] = date_ago($threadvalue[ 'lastpost' ]);

                $row['iconsrc'] = $iconsrc;
                $row[ 'subject' ] = $threadvalue[ 'subject' ];
                $row[ 'title' ] = $threadvalue[ 'subject' ].' ';
                if (is_array($row[ 'iconsrc' ])) {
                    foreach ($row[ 'iconsrc' ] as $iconvalue) {
                        $row[ 'title' ] .= '<img src="' . $iconvalue . '"" />';
                    }
                }

                $row[ 'pics' ] = array_values(array_unique($pics));
                $row[ 'pics_big' ] = array_values(array_unique($pics_big));
                $row[ 'fid' ] = $threadvalue[ 'fid' ];
                $row[ 'forum_name' ] = $forum[ 'name' ];
                $row[ 'user_id' ] = $threadvalue[ 'authorid' ];
                $row[ 'user_name' ] = $threadvalue[ 'author' ];
                $row[ 'user_head' ] =  avatar($threadvalue[ 'authorid' ], 'middle', true) ;
                $row[ 'click' ] = $threadvalue[ 'views' ];
                $row[ 'reply_count' ] = $threadvalue[ 'replies' ];
                $row[ 'create_time' ] = $threadvalue[ 'dateline' ];
                $row[ 'last_reply_time' ] = $threadvalue[ 'lastpost' ];
                $row[ 'digest' ] = $threadvalue[ 'digest' ];
                $row[ 'applaud_count' ] = $threadvalue[ 'recommend_add' ] +$threadvalue[ 'recommend_sub' ] ;
                $row[ 'special' ] = $threadvalue[ 'special' ];
                $row[ 'is_top' ] =$threadvalue[ 'displayorder' ] ? 1 : -1;
                $list[] = $row;
            }
        }
        success('', $list);
    }

    public function threadToInfoPageDataCommon(){
        $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
        if(!empty($_MAG_CONFIG['emotion_url_contain'][0])) {
            //echo $_MAG_CONFIG['emotion_url_contain'][0];die;
            $emotion_url_contain = explode(',',$_MAG_CONFIG['emotion_url_contain'][0]);
        }
        $tableid = null;
        global $_G;
        if(!$_GET['fids']){
            success('',array());
        }
        $step = $_GET['step'] >0 ? (int)$_GET['step'] : 20;
        $p = $_GET['p'] > 0 ? (int)$_GET['p'] : 1;
        $start = ($p - 1)* $step;
        $orderby = $_GET['orderby']  ? $_GET['orderby'] :  'dateline DESC';
        $dateline = (int)$_GET['dateline']  ;
        $lasttime = $_GET['lasttime'] > 0 ? (int)$_GET['lasttime'] :0;
        $lastpost = (int)$_GET['lastpost']  ;
        $digest = (int)$_GET['digest']  ;
        $fid = explode(',',$_GET['fids']);
        if(count($fid)==1)$fid = $fid[0];
        $parameter = array('forum_thread');
        $wherearr = array();
        if(!empty($fid)) {
            $parameter[] = $fid;
            $wherearr[] = is_array($fid) && $fid ? 'fid IN(%n)' : 'fid=%d';
        }
        if(!empty($dateline)){
            $parameter[] = $dateline;
            $wherearr[] =  'dateline >= %d';
        }
        if(!empty($lastpost)){
            $parameter[] = $lastpost;
            $wherearr[] =  'lastpost >= %d';
        }
        if(!empty($digest)){
            //            $parameter[] = $digest;
            $wherearr[] =  'digest >0';
        }
        if(!empty($lasttime)){
            $parameter[] = $lasttime;
            $wherearr[] =  'dateline < %d';
            $p = 1;
            $start = 0;
        }
        $wheresql = !empty($wherearr) && is_array($wherearr) ? ' WHERE '.implode(' AND ', $wherearr) : '';
        $total_count = count(DB::fetch_all("SELECT tid FROM %t $wheresql  and displayorder>=0 ", $parameter, ''));
        $data =  DB::fetch_all("SELECT * FROM %t $wheresql  and displayorder>=0 ORDER BY ".$orderby." ".DB::limit($start, $step), $parameter, '');

        $list = array();
        if (is_array($data)) {
            foreach ($data as $key => $threadvalue) {
                $row = array();
                $posttablename = 'forum_post'.($threadvalue['posttableid'] ? "_".$threadvalue['posttableid'] : '');
                $content = DB::fetch_first("SELECT * FROM " . DB::table($posttablename) . " WHERE first=1 and tid=" . $threadvalue[ 'tid' ]);
                if(!$content) continue;
                //          $_G['forum_threadlist'][$k]['_post'] = $content;

                $pics = array();
                preg_match_all("/\[img.*\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/i", $content[ 'message' ], $imgmatchaids);
                if ($imgmatchaids[ 1 ]) {
                    foreach ($imgmatchaids[ 1 ] as $key => $value) {
                        if(!empty($emotion_url_contain)) {
                            foreach($emotion_url_contain as $v) {
                                if (strpos($value, $v) !== false) {
                                    //array_splice($imgmatchaids[1], $key, 1);
                                    continue 2;
                                }
                            }
                            $pics[] = $value;
                        }

                    }
                    /*$flag = true;
                    foreach($emotion_url_contain as $v) {
                        $flag &= !strpos($value, $v);
                    }
                    if($flag) {
                        $pics[] = $value;
                    }*/
                }

                /*从附件表中取缩略图*/
                $attlist = DB::fetch_all("SELECT * FROM " . DB::table('forum_attachment') . " WHERE tid=" . $threadvalue[ 'tid' ]." and pid=".$content[ 'pid' ]);
                if ($attlist) {
                    //              echo json_encode($attmatchaids[1]);exit;
                    foreach ($attlist as $att) {
                        if ($att) {
                            $att2 = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment_' . $att[ 'tableid' ]) . " WHERE aid=" . $att[ 'aid' ]);
                            //                        echo json_encode($att2);exit;
                            if ($att2[ 'isimage' ]) {
                                //$pics[] = site_index_url . '/data/attachment/forum/' . $att2[ 'attachment' ];
                                $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att2[ 'aid' ] . '&size=200x200&key=' . md5($att2[ 'aid' ] . '|200|200');
                            }
                        }
                    }
                }
                /* 从正文中取缩略图
                preg_match_all("/\[attach\](\d+)\[\/attach\]/i", $content[ 'message' ], $attmatchaids);
                if ($attmatchaids[ 1 ]) {
                    //              echo json_encode($attmatchaids[1]);exit;
                    foreach ($attmatchaids[ 1 ] as $value) {
                        $att = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment') . " WHERE aid=" . $value);
                        if ($att) {
                            $att2 = DB::fetch_first("SELECT * FROM " . DB::table('forum_attachment_' . $att[ 'tableid' ]) . " WHERE aid=" . $att[ 'aid' ]);
                            //                        echo json_encode($att2);exit;
                            if ($att2[ 'isimage' ]) {
                                //                            $pics[] = site_index_url . '/data/attachment/forum/' . $att2[ 'attachment' ];
                                $pics[] = site_index_url . '/source/plugin/magmobileapi/magmobileapi.php?mag_mod=forum&mag_fuc=imageThumb&mod=imageThumb&aid=' . $att2[ 'aid' ] . '&size=200x200&key=' . md5($att2[ 'aid' ] . '|200|200');
                            }
                        }
                    }
                }*/
                $forum = DB::fetch_first("SELECT * FROM " . DB::table('forum_forum') . " WHERE   fid=" . $threadvalue[ 'fid' ]);

                define('IMGIR' , '');
                $iconsrc = array();
                if ($threadvalue[ 'displayorder' ]) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/pin_' . $threadvalue[ displayorder ] . '.png';
                }
                if ($threadvalue[ 'digest' ]) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/digest_' . $threadvalue[ digest ] . '.png';
                }
                if ($threadvalue[ 'rushreply' ]) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/rushreply_s.png';
                }
                if ($threadvalue[ 'special' ] == 1) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/pollsmall.png';
                } elseif ($threadvalue[ 'special' ] == 2) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/tradesmall.png';
                } elseif ($threadvalue[ 'special' ] == 3) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/rewardsmall.png';
                } elseif ($threadvalue[ 'special' ] == 4) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/activitysmall.png';
                } elseif ($threadvalue[ 'special' ] == 5) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/debatesmall.png';
                }
                if ($threadvalue[ 'icon' ] > 0) {
                    $iconsrc[] = $_MAG_CONFIG['MAG_INDEX_URL'].'/public/icons/forum/' .substr( $_G[ 'cache' ][ 'stamps' ][ $threadvalue[ icon ] ][ 'url' ],0,-3).'png';
                }

                $row[ 'tid' ] = $threadvalue[ 'tid' ];
                $row[ 'typeid' ] = $threadvalue[ 'typeid' ];
                $row[ 'sortid' ] = $threadvalue[ 'sortid' ];;
                $row['publish_time']  = $threadvalue['dateline'];
                $row[ 'dateline' ] = date_ago($threadvalue[ 'dateline' ]);
                $row['publish_time_str']  = $row[ 'dateline' ];
                $row[ 'lastpost' ] = date_ago($threadvalue[ 'lastpost' ]);

                $row['iconsrc'] = $iconsrc;
                $row[ 'subject' ] = $threadvalue[ 'subject' ];
                $row[ 'title' ] = $threadvalue[ 'subject' ].' ';
                if (is_array($row[ 'iconsrc' ])) {
                    foreach ($row[ 'iconsrc' ] as $iconvalue) {
                        $row[ 'title' ] .= '<img src="' . $iconvalue . '"" />';
                    }
                }

                $row[ 'pics' ] = array_values(array_unique($pics));
                $row['pic_type']  = 1;
                $row['pics_arr'] = array();
                foreach ($row['pics'] as $picvalue){
                    $row['pics_arr'][] = array(
                        'url' =>   $picvalue,
                        'tburl' =>   $picvalue,
                        'width'=> 0,
                        'height' => 0
                    );
                }
                $row[ 'fid' ] = $threadvalue[ 'fid' ];
                $row[ 'forum_name' ] = $forum[ 'name' ];
                $row[ 'user_id' ] = $threadvalue[ 'authorid' ];
                $row[ 'user_name' ] = $threadvalue[ 'author' ];
                $row[ 'user_head' ] =  avatar($threadvalue[ 'authorid' ], 'middle', true) ;
                $row[ 'click' ] = $threadvalue[ 'views' ];
                $row[ 'reply_count' ] = $threadvalue[ 'replies' ];
                $row[ 'create_time' ] = $threadvalue[ 'dateline' ];
                $row[ 'last_reply_time' ] = $threadvalue[ 'lastpost' ];
                $row[ 'digest' ] = $threadvalue[ 'digest' ];
                $row[ 'special' ] = $threadvalue[ 'special' ];
                $row[ 'is_top' ] =$threadvalue[ 'displayorder' ] ? 1 : -1;
                $list[] = $row;
            }
        }
        success('', array('list' => $list ,'total' => $total_count ));
    }

    public function threadApplaudPageCommon(){
        global $_G;
        $tid = (int)$_GET['tid'];
        $p = $_GET['p'] >0 ? (int)$_GET['p'] : 1;
        $step = $_GET['step'] >0 ? (int)$_GET['step'] : 100;
        if(empty($tid)){
            error('请输入tid');
        }
        $sql =  "select * from ". DB::table('forum_memberrecommend') . " where tid =$tid";
        $applaud_count = count(DB::fetch_all($sql)) ;
        $sql =  $sql." limit ".($p-1)*$step.",". $step;
        //        echo $sql;exit;
        $rs = DB::fetch_all($sql)   ;
        $list = array();
        if(is_array($rs)){
            foreach ($rs as $key => $value){
                $user['user_id'] = $value['recommenduid'];
                $member = getuserbyuid($value['recommenduid'], 1);
                $user['user_name'] = $member['username'];
                $user['user_head'] =  avatar($member[ 'uid' ], 'small', true);
                $list[] = $user;
            }
        }
        success('',array('list' => $list,
            'applaud_count' => $applaud_count,
            'is_applaud' => C::t('forum_memberrecommend')->fetch_by_recommenduid_tid($_G['uid'], $tid)? true : false
        ));
    }


    public function threadsViewsAddCommon(){
        $data = $_POST['data'];
        if(!is_array($data)){
            error('数据格式出错');
        }
        foreach ($data as $key => $value){
            $row = C::t('forum_threadaddviews')->fetch($key);
            C::t('forum_thread')->increase($key, array('views' => $row['addviews']+$value), true);
        }
        success('');
    }

}


function thread_move_options($fid,$tid){
    global $_G;
    require_once libfile('function/discuzcode');
    $catlistall = DB::fetch_all("SELECT fid,fup,`name`,`type` FROM " . DB::table('forum_forum') . " f WHERE status=1 ORDER BY f.type, f.displayorder");
    foreach ($catlistall as &$value) {
        $threadtypesrow = DB::fetch_first("SELECT  threadtypes FROM " . DB::table('forum_forumfield') . " WHERE fid=" . $value[ 'fid' ]);
        $threadtypesrow['threadtypes'] = unserialize($threadtypesrow['threadtypes']);
        $value = array_merge($value, $threadtypesrow);
    }
    $catTree = list_to_tree($catlistall, $pk = 'fid', $pid = 'fup', $child = '_child', $root = 0);
    $optionTree = array();
    foreach ($catTree as $key1 => $value1){
        $optionTree[$key1] = array(
            'name' => strip_tags(discuzcode($value1['name']))
        );
        foreach ($value1['_child'] as $key2 => $value2){

            //** 当前节点 start*/
            $thislevel = array();
            $thislevel['name'] = strip_tags(discuzcode($value2['name']));
            if($value2['threadtypes']){
                if($value2['threadtypes']['types']) {
                    foreach ($value2['threadtypes']['types'] as $keytypes => $valuetypes){
                        $thislevel['child'][] = array(
                            'name' => '(分类)'.strip_tags(discuzcode($valuetypes)),
                            'url'  => '/mag/circle/v1/forum/threadMove?fid='.$fid.'&tid='.$tid.'&moveto='.$value2['fid'].'&typeid='.$keytypes
                        );
                    }
                } else {
                    $thislevel['url'] = '/mag/circle/v1/forum/threadMove?fid='.$fid.'&tid='.$tid.'&moveto='.$value2['fid'].'&typeid=';
                }
            }elseif(!$value2['_child']){
                $thislevel['url'] = '/mag/circle/v1/forum/threadMove?fid='.$fid.'&tid='.$tid.'&moveto='.$value2['fid'].'&typeid=';
            }
            //** 当前节点 end*/
            $optionTree[$key1]['child'][$key2] = $thislevel;
            if($value2['_child']){
                $optionTree[$key1]['child'][$key2]['child'] = array();
                //首项为父节点
                $optionTree[$key1]['child'][$key2]['child'][0] = $thislevel;
                foreach ($value2['_child'] as $key3 => $value3){
                    $thislevel2 = array();
                    $thislevel2['name'] = '(子版)'.strip_tags(discuzcode($value3['name']));
                    if($value3['threadtypes']){

                        foreach ($value3['threadtypes']['types'] as $keytypes => $valuetypes){
                            $thislevel2['child'][] = array(
                                'name' => '(分类)'.strip_tags(discuzcode($valuetypes)),
                                'url'  => '/mag/circle/v1/forum/threadMove?fid='.$fid.'&tid='.$tid.'&moveto='.$value3['fid'].'&typeid='.$keytypes
                            );
                        }
                    }else{
                        $thislevel2['url'] = '/mag/circle/v1/forum/threadMove?fid='.$fid.'&tid='.$tid.'&moveto='.$value3['fid'].'&typeid=';
                    }
                    $optionTree[$key1]['child'][$key2]['child'][$key3+1] = $thislevel2;
                }
            }
        }
    }
    return $optionTree;
    //    echo json_encode($optionTree);exit;
    //    $data = array('catlistall' => $catlistall);
}

/**
 * 把返回的数据集转换成Tree
 * @access public
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0){
    // 创建Tree
    $tree = array();
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[ $data[ $pk ] ] =& $list[ $key ];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[ $pid ];
            if ($root == $parentId) {
                $tree[] =& $list[ $key ];
            } else {
                if (isset($refer[ $parentId ])) {
                    $parent =& $refer[ $parentId ];
                    $parent[ $child ][] =& $list[ $key ];
                }
            }
        }
    }
    return $tree;
}


function click_count_format($count){
    $count = (int)$count;
    if($count<10000) return $count;
    $count = ceil($count/1000);
    $count = $count/10;
    return $count.'万';

}


/**
 * 视频解析
 * @param $url
 * @return string
 */
function magparsemedia($url){
//    echo $url;exit;
    if(empty($url)){
        return '';
    }
    if(strpos($url,'youku.com')){
        if(preg_match_all("/sid\/([^\/]+)(\/.swf|)/i", $url, $matches)) {
//            echo json_encode($matches[1][0]);exit;
            if($matches[1][0]){
                return '<iframe height="100%" width="100%" src="https://player.youku.com/embed/'.$matches[1][0].'" frameborder="0" allowfullscreen></iframe>';
            }
        }
        if(preg_match_all("/id_([^\.]+)(\.html|)/i", $url, $matches)) {
            //            echo json_encode($matches[1][0]);exit;
            if($matches[1][0]){
                return '<iframe height="100%" width="100%" src="https://player.youku.com/embed/'.$matches[1][0].'" frameborder="0" allowfullscreen></iframe>';
            }
        }
    }
    if(strpos($url,'qq.com')){
        if(preg_match_all("/vid=(.+)[&]/i", $url, $matches)) {
//            echo json_encode($matches[1][0]);exit;
            if($matches[1][0]){
                return '<iframe frameborder="0" width="100%" height="100%" src="https://v.qq.com/iframe/player.html?vid='.$matches[1][0].'&tiny=0&auto=0" allowfullscreen></iframe>';
            }
        }
    }
    if(strpos($url,'sohu.com')){
        if(preg_match_all("/id=([^\&]+)/i", $url, $matches)) {
//            echo json_encode($matches[1][0]);exit;
            if($matches[1][0]){
                return '<iframe src="http://tv.sohu.com/upload/static/share/share_play.html#'.$matches[1][0].'" width="320" height="270" frameborder="0" allowfullscreen=""></iframe>';
            }
        }
    }
    return '[该视频或音乐请在电脑版查看]';
}