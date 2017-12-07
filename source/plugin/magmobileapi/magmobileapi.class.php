<?php

/**
 * 南京灵衍信息科技有限公司
 * User: wangxiao
 * Date: 2016/11/23
 * Time: 10:28
 */

//ini_set("display_errors", "On");
//error_reporting(E_ALL | ~E_NOTICE); dd
class mobileplugin_magmobileapi {
	function common() {
		if(!defined('IN_MAGMOBILE_API')) {
			return;
		}
		global $_G;
		$_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
		if(($_SERVER['HTTP_MAGDZAPISALT'] != $_MAG_CONFIG['HTTP_MAG_DZ_API_SALT']) && !in_array(strtolower($_GET['mag_fuc']),array('setuserlogin','imagethumb','qqloginconnect'))){
			error('HTTP_MAG_DZ_API_SALT error',array(),'HTTP_MAG_DZ_API_SALT_error');
		}
		if(!in_array(strtolower($_GET['mag_fuc']),array('login','forcelogin','forceregist','password','changepassword','getforum','threadlist','forumtree' ,'forumlist','viewthread','commentlist','forumclassified','phonelogin','register','userbasic','infocatTree','infocatchild','infocatget','infolist','infoget','imagethumb','userbasicbyphone','bindqq','qqlogin','bindweixin','weixinlogin','threadtoinfolist','scoresetting','scorerules','usergroups','setuserlogin','threadapplaudpage','threadsearch','loginbyuserid','threadlistbyuserids','threadlistbyfids','threadtoinfopagedata','scoreadd','threadsviewsadd','usersearch','qqloginconnect','qqloginconnectcheck'))){
			if(empty($_GET['auth']) || empty($_GET['cookiepre']) || empty($_GET['saltkey'])){
				error('请先登录',array(),'token_error');
			}else{
                $_GET['formhash'] = $_POST['formhash'] = formhash();//formhash过滤
				//unset($_GET['auth'] , $_GET['cookiepre'] , $_GET['formhash'], $_GET['saltkey']);
			}
		}else{
			unset($_GET['auth'] , $_GET['cookiepre'] , $_GET['formhash'], $_GET['saltkey']);
		}
		if($_G['charset'] != 'utf-8'){
			//utf8 to gbk
			$post_str = var_export($_POST,true);
			$_POST = eval('return '.iconv('utf-8','gbk//IGNORE',$post_str).';');

			$get_str = var_export($_GET,true);
			$_GET = eval('return '.iconv('utf-8','gbk//IGNORE',$get_str).';');
		}
		//防止云验证码
        $_G['setting']['seccodedata']['cloudip'] = 0;
        $_G['setting']['seccodedata']['type'] = 0;
        $_G['setting']['secqaa'] = array();
        //兼容头像路径
        $_G['setting']['avatarmethod'] = 0;
		//绕过防灌水设置
		$_G['setting']['need_email'] = 0;//关闭邮箱认证
        //关闭验证码
        $_G['setting']['seccodedata']['rule'] = array();
        //关闭防采集
        $_G['setting']['antitheft']['allow'] = 0;

        //登录注册后可看大图
        $_G['setting']['guestviewthumb']['flag'] = 0;

//		$seccode = make_seccode($_GET['sechash']);
//		$_GET['seccodeverify'] = $seccode;
		$magMod = $_GET['mag_mod'];
		$magFuc = $_GET['mag_fuc'];
		$commonFuc = $magFuc.'Common';

		/*$url = parse_url($_G['siteurl']);
		define('site_index_url' ,$url['scheme'].'://'.$url['host'].'');*/
		$url = parse_url($_G['siteurl']);
		if(strpos($_G['setting']['siteurl'],'http') === false) {
			define('site_index_url' ,$url['scheme'].'://'.$_G['setting']['siteurl'].'');
		} else {
			define('site_index_url' ,$_G['setting']['siteurl'].'');
		}
		if(!$_G['setting']['mobile']['allowmobile']) {
			error('手机功能已关闭',array(),'mobile_is_closed');
		}
		if(!empty($_GET['tpp'])) {
			$_G['tpp'] = intval($_GET['tpp']);
		}
		if(!empty($_GET['ppp'])) {
			$_G['ppp'] = intval($_GET['ppp']);
		}
		$_G['pluginrunlist'] = array('magmobileapi', 'qqconnect', 'wechat');
		$_G['siteurl'] = preg_replace('/api\/mobile\/$/', '', $_G['siteurl']);
		$_G['setting']['msgforward'] = '';
		$_G['setting']['cacheindexlife'] = $_G['setting']['cachethreadlife'] = false;

		$magMod = new $magMod();
		return $magMod -> $commonFuc();
	}

	function global_magmobileapi() {
		if(!defined('IN_MOBILE_API')) {
			return;
		}
		if(class_exists('mobileplugin_magmobileapi', false) && method_exists('mobileplugin_magmobileapi', 'output')) {
			mobileplugin_magmobileapi::output();
		}
	}

	function output(){
		$magMod = $_GET['mag_mod'];
		$magFuc = $_GET['mag_fuc'];
		$outputFuc = $magFuc.'Output';
		$magMod = new $magMod();
		return $magMod -> $outputFuc();
	}

    public function deletethread($param){
        if($param['step'] == 'delete'){
            include_once ('config.php');
            $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
            $url = $_MAG_CONFIG['MAG_INDEX_URL']."/mag/circle/v1/forum/threadDeleteCallBack";
            $post_data = array("tids" => $param['param'][0]);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            // post数据
            curl_setopt($ch, CURLOPT_POST, 1);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            $output = curl_exec($ch);
            curl_close($ch);
        }
    }


}
class plugin_magmobileapi {

    public function deletethread($param){
        if($param['step'] == 'delete'){
            include_once ('config.php');
            $_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
            $url = $_MAG_CONFIG['MAG_INDEX_URL']."/mag/circle/v1/forum/threadDeleteCallBack";
            $post_data = array("tids" => $param['param'][0]);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            // post数据
            curl_setopt($ch, CURLOPT_POST, 1);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
            $output = curl_exec($ch);
            curl_close($ch);
        }
    }
}
class plugin_magmobileapi_forum extends mobileplugin_magmobileapi {


	function viewthread_top_output() {
		$return  = $this ->_include_style();
		$return .= $this ->_download_qrcode();
		return $return;
	}

	function viewthread_postbottom_output() {
		global $_G, $postlist,$thread;
//        echo json_encode($_G);exit;
        $url = parse_url($_G['siteurl']);
        if(strpos($_G['setting']['siteurl'],'http') === false) {
            define('site_index_url' ,$url['scheme'].'://'.$_G['setting']['siteurl'].'');
        } else {
            define('site_index_url' ,$_G['setting']['siteurl'].'');
        }
		//print_r($postlist);
		include_once ('config.php');
		$_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
		if(!empty($_MAG_CONFIG['emotion_url_contain'][0])) {
			//echo $_MAG_CONFIG['emotion_url_contain'][0];die;
			$emotion_url_contain = explode(',',$_MAG_CONFIG['emotion_url_contain'][0]);
		}
		$domain = $_MAG_CONFIG['MAG_INDEX_URL'];//$_G['cache']['plugin']['magapp_new']['domain'];
		foreach($postlist as $key => $value) {
			//status 安卓4104;苹果8200
// 			$value['message'] .= "|".$value['status'] ."|".getstatus($value['status'], 15).getstatus($value['status'], 14).getstatus($value['status'], 13);
			$client = base_convert(getstatus($value['status'], 15).getstatus($value['status'], 14).getstatus($value['status'], 13), 2, 10);
			//$client = 1;
//			$value['message'] .= "|".$client;
			if($client == 1){//安卓
				$text = '来自安卓APP客户端';//$_G['cache']['plugin']['magapp_new']['andtext'];
				$value['message'] = $value['message'] .'<div class="mag_viewthread"><a class="mag_text" target="_blank" href="'.$domain.'" >'.$text.'</a><span id="magapp_qrcode_'.$key.'" onmouseover="showMenu({\'showid\':this.id, \'menuid\':\'magapp_qrcode_download\',\'fade\':1, \'pos\':\'34\'})" class="mag_qrcode"></span></div>';
			}else if($client == 2){//苹果
				$text = '来自苹果APP客户端';//$_G['cache']['plugin']['magapp_new']['iostext'];
				$value['message'] = $value['message'] .'<div class="mag_viewthread"><a class="mag_text" target="_blank" href="'.$domain.'" >'.$text.'</a><span id="magapp_qrcode_'.$key.'" onmouseover="showMenu({\'showid\':this.id, \'menuid\':\'magapp_qrcode_download\',\'fade\':1, \'pos\':\'34\'})" class="mag_qrcode"></span></div>';
			}else{
				preg_match_all("/<div align=\"center\"><br \/>\n<br \/>\n(.*)>(.*)<\/a><\/div>/", $value['message'], $matches);
				if($matches[0][0] && $matches[2][0]){
					$text =  $matches[2][0];
					$value['message'] = str_replace($matches[0][0],'', $value['message']);
					$value['message'] = $value['message'] .'<div class="mag_viewthread"><a class="mag_text" target="_blank" href="'.$domain.'" >'.$text.'</a><span id="magapp_qrcode_'.$key.'" onmouseover="showMenu({\'showid\':this.id, \'menuid\':\'magapp_qrcode_download\',\'fade\':1, \'pos\':\'34\'})" class="mag_qrcode"></span></div>';
				}
			}
			$postlist[$key] = $value;
            if($value['first'] && $thread['displayorder'] >= 0){
                $content = DB::fetch_first("SELECT * FROM " . DB::table('forum_post') . " WHERE first=1 and tid=" . $value[ 'tid' ]);
                //			$_G['forum_threadlist'][$k]['_post'] = $content;
                $pics = array();
                preg_match_all("/\[img.*\]\s*([^\[\<\r\n]+?)\s*\[\/img\]/i", $content[ 'message' ], $imgmatchaids);
                if ($imgmatchaids[ 1 ]) {
                    foreach ($imgmatchaids[ 1 ] as $key2 => $value2) {
                        if(!empty($emotion_url_contain)) {
                            foreach($emotion_url_contain as $v) {
                                if (strpos($value2, $v) !== false) {
                                    //array_splice($imgmatchaids[1], $key, 1);
                                    continue 2;
                                }
                            }
                            $pics[] = $value2;

                        }
                    }
                }
                /*从附件表中取缩略图*/
                $attlist = DB::fetch_all("SELECT * FROM " . DB::table('forum_attachment') . " WHERE tid=" . $value[ 'tid' ] ." and pid=".$value['pid']);
                if ($attlist) {
                    //				echo json_encode($attmatchaids[1]);exit;
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
                $threadRow['tid'] = $value['tid'];
                $threadRow['title'] = $value['subject'];
                $threadRow['pics'] = array_values(array_unique($pics));
                $threadRow['fid'] = $value['fid'];
                $threadRow['forum_name'] = $_G[ 'forum' ][ 'name' ];
                $threadRow['click'] = $thread['views'];
                $threadRow['reply_count'] = $thread['replies'];
                $threadRow['digest'] = $thread['digest'];
                $threadRow['user_id'] = $thread['authorid'];
                $threadRow['user_name'] = $thread['author'];
                $threadRow[ 'create_time' ] = $value[ 'dbdateline' ];
                if($_G['charset'] != 'utf-8'){
                    //gbk格式转utf8
                    $array_str = var_export($threadRow,true);
                    $threadRow = eval('return '.iconv('gbk','utf-8',$array_str).';');
                }
				$url =	$_MAG_CONFIG['MAG_INDEX_URL']."/mag/circle/v1/forum/threadViewCallBack";
				$post_data = array("thread" => $threadRow);
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch,CURLOPT_TIMEOUT,3);
				// post数据
				curl_setopt($ch, CURLOPT_POST, 1);
				// post的变量
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
				$output = curl_exec($ch);
				curl_close($ch);
				//打印获得的数据
				if($_GET['test']){
//					echo $output;exit;
				}
			}
		}
		return array();
	}

	function forumdisplay_thread_subject_output($a){
		global $_G, $threadids;
		include_once ('config.php');
		$_MAG_CONFIG = MagConfig::$_MAG_CONFIG;
		$domain = $_MAG_CONFIG['MAG_INDEX_URL'];//$_G['cache']['plugin']['magapp_new']['domain'];
		$return = array();
		foreach($_G['forum_threadlist'] as $key =>&$value){
			$client   = base_convert(getstatus($value['status'], 15).getstatus($value['status'], 14).getstatus($value['status'], 13), 2, 10);
			//$client   = 1;
//			$value['subject'] .= '|'.$value['status'];
			if($client == 1){//安卓
				$imgurl   = 'source/plugin/magmobileapi/static/imgs/phone.png';//$_G['cache']['plugin']['magapp_new']['andimg'];
				if(!empty($domain)) $return[] = $client? '<a target="_blank" href="'.$domain.'"><img src="'.$imgurl.'" align="absmiddle" width="16" /></a>' : '';
				else $return[] = $client? '<img src="'.$imgurl.'" align="absmiddle" width="16" />' : '';
				$value['mobile'] = 0;
			}else if($client == 2){//苹果
				$imgurl   = 'source/plugin/magmobileapi/static/imgs/phone.png';//$_G['cache']['plugin']['magapp_new']['iosimg'];
				if(!empty($domain)) $return[] = $client? '<a target="_blank" href="'.$domain.'"><img src="'.$imgurl.'" align="absmiddle" width="16" /></a>' : '';
				else $return[] = $client? '<img src="'.$imgurl.'" align="absmiddle" width="16" />' : '';
				$value['mobile'] = 0;
			}
			else  $return[] = '';
		}
		return $return;
	}



    function _include_style(){
		global $_G;
		$style  = '';
		$style .= '<link rel="stylesheet" type="text/css" href="source/plugin/magmobileapi/static/magapp.css" />';
		return $style;
	}
	function _download_qrcode(){
		global $_G;
		$qrcode = $_G[ 'siteurl' ].'/source/plugin/magmobileapi/static/imgs/qrcode_big.png';
		$qrtext = $_G['cache']['plugin']['magapp_new']['qrtext'];
		if(!empty($qrtext)) $qrtext = str_replace("\n","<br/>",str_replace("\n\n", "\n", $qrtext));
		return '<div class="magapp-qrcode-download bm z" id="magapp_qrcode_download" style="display: none"><div class="bm_c"><img class="magapp-qrcode-download-img" src="'.$qrcode.'" /><p class="magapp-appdl-note">'.$qrtext.'</p></div></div>';
	}

}