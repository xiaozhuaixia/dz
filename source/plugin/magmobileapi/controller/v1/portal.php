<?php
/**
 * 南京灵衍信息科技有限公司
 * User: wangxiao
 * Date: 2016/11/29
 * Time: 16:24
 */

$_GET['mod'] =  $_GET['mod']? $_GET['mod']:'index';
include_once dirname(__file__).'/../../../../../portal.php';

class portal{

    public function infocatTreeCommon(){

    }

    public function infocatTreeOutput(){
        $catlistall = array();
        if($_GET['checknotice']) {
            $variable = array();
        } else {
            $catlistall = DB::fetch_all("SELECT * FROM " .DB::table('portal_category')." WHERE closed=0 ");
        }
        success('', $catlistall);
    }

    public function infoCatChildCommon(){
        global $_G;
        $_G['makehtml'] =  1;
        $url = parse_url($_G['siteurl']);
        $_G['siteurl'] = $url['scheme'].'://'.$url['host'].'/';
    }

    public function infoCatChildOutput(){
        global $_G;
        $portalcategory = &$_G['cache']['portalcategory'];
        $catid = $_G['catid'];
        $cat = $portalcategory[$catid];
        $cat['subs'] = $cat['children'];
        $wheresql = category_get_wheresql($cat);
        //		echo $wheresql;die;
        $list = category_get_list($cat, $wheresql, $page = $_GET['page']);
        $cat['children'] = array();
        if($cat['subs']){
            foreach($cat['subs'] as $value){
                $cat['children'][] = $portalcategory[$value];
            }
        }
        success('', $cat['children']);
    }

    public function infoCatGetCommon(){
        define('TPL_DEFAULT', true);
        global $_G;
        $_G['makehtml'] =  1;
        $url = parse_url($_G['siteurl']);
        $_G['siteurl'] = $url['scheme'].'://'.$url['host'].'/';
    }

    public function infoCatGetOutput(){
        global $_G;
        $portalcategory = &$_G['cache']['portalcategory'];
        $catid = $_G['catid'];
        $cat = $portalcategory[$catid];
        $cat['subs'] = $cat['children'];
        if($_G['hookscriptmessage'] ){
            error($_G['lang']['message'][$_G['hookscriptmessage']],array(),$_G['hookscriptmessage']);
        }else{
            success('', $cat);
        }
    }
    public function infoListCommon(){
        global $_G;
        define('TPL_DEFAULT', true);
        $_G['makehtml'] =  1;
        $url = parse_url($_G['siteurl']);
        $_G['siteurl'] = $url['scheme'].'://'.$url['host'].'/';
    }

    public function infoListOutput(){
        global $_G;
        $page = $_GET['page']  >0? (int)$_GET['page'] : 1;
        $step = $_GET['step']  >0? (int)$_GET['step'] : 10;
        $portalcategory = &$_G['cache']['portalcategory'];
        $catid = $_G['catid'];
        $cat = $portalcategory[$catid];
        $cat['subs'] = $cat['children'];
        $wheresql = category_get_wheresql($cat);
        //		echo $wheresql;die;
        $list = category_get_list($cat, $wheresql,$page,$step);
        $totalpage = ceil( $list['count'] / $step);
        if($page > $totalpage){
            $list['list']  = array();
        }
        $infolist = array();
        foreach($list['list'] as $key => $value){
            $value['pics'] = array();
            $content = C::t('portal_article_content')->fetch_all($value['aid']);
            //echo json_encode($content);exit;
            foreach ($content as $value2){
                $value['content'] .=$value2['content'];
            }
            preg_match_all('/<img.*?src="(.*?)".*?>/i',$value['content'],$img);
            //			echo json_encode($img);exit;
            foreach($img[1] as $imgvalue){
                if(strpos($imgvalue,'static/image/smiley') === false && strpos($imgvalue,'.gif') === false ){
                    if(strpos($imgvalue,'http') === false){
                        //本地
                        if(strpos($imgvalue,'thumb.jpg') === false){
                            $value['pics'][] = site_index_url.'/'.$imgvalue.'.thumb.jpg';
                        }else{
                            $value['pics'][] = site_index_url.'/'. $imgvalue;
                        }
                    }else{
                        //远程
                        $value['pics'][] = $imgvalue;
                    }
                }
            }
            $row = array();
            $row['id'] = $value['aid'] ;
            $row['cat_id'] = $value['catid'] ;
            $row['cat_name'] = $cat['catname'];
            $row['from'] = $cat['catname'];
            $row['user_id'] = $value['uid'] ;
            $row['user_name'] = $value['username'] ;
            $row['user_head'] = avatar($value[ 'uid' ], 'middle' , true) ;
            $value['create_time'] = strtotime($value['dateline']);
            $row['dateline'] = date_ago($value[ 'create_time' ]);
            $row['title'] =  $value['title'] ;
            $row['pics'] =  array_values(array_unique($value['pics']));
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
            $article_count = C::t('portal_article_count')->fetch($value['aid']);
            if($article_count) $value = array_merge($article_count, $value);
            $row['click']  = (int)$article_count['viewnum'];
            $row['click_show']  = (int)$article_count['viewnum'] .'阅读';
            if($value['idtype'] == 'blogid') {
                $value['commentnum'] = C::t('home_comment')->count_by_id_idtype($value['id'], 'blogid');
            }elseif($value['idtype'] == 'tid') {
                $thread = $firstpost = array();
                require_once libfile('function/discuzcode');
                require_once libfile('function/forum');
                $thread = get_thread_by_tid($value['id']);
                $posttable = empty($thread['posttable']) ? getposttablebytid($value['id']) : $thread['posttable'];
                $value['commentnum'] = getcount($posttable, array('tid'=>$value['id'], 'first'=>'0'));
            }
            $row['reply_count']  = $value['commentnum'];
            $row['create_time']  = $value['create_time'];
            $row['publish_time']  = $value['create_time'];
            $row['publish_time_str']  = date_ago($value[ 'create_time' ]);
//            $row['content']  = $value['content'];
            $infolist[] = $row;
        }

        if($_G['hookscriptmessage'] ){
            error($_G['lang']['message'][$_G['hookscriptmessage']],array(),$_G['hookscriptmessage']);
        }else{
//            success('', $list['list']);
            success('', array('list' => $infolist,'total' => $list['count']) );
        }
    }

    public function infoGetCommon(){
        global $_G;
        define('TPL_DEFAULT', true);
        $_G['makehtml'] =  1;
        $url = parse_url($_G['siteurl']);
        $_G['siteurl'] = $url['scheme'].'://'.$url['host'].'/';
    }
    public function infoGetOutput(){
        global $article ;
        global $_G;
        if($_G['hookscriptmessage'] ) {
            error($_G[ 'lang' ][ 'message' ][ $_G[ 'hookscriptmessage' ] ], array(), $_G[ 'hookscriptmessage' ]);
        }
        $content = C::t('portal_article_content')->fetch_all($article['aid']);
        foreach ($content as $value){
            $article['content'] .=$value['content'];
        }
        $portalcategory = &$_G['cache']['portalcategory'];
        $catid = $_G['catid'];
        $cat = $portalcategory[$catid];
        $article['catname'] = $cat['catname'];
        $article['pics'] = array();
        preg_match_all('/<img.*?src="(.*?)".*?>/is', $article[ 'content' ], $mt);
        foreach($mt[1] as $imgvalue){
            if(strpos($imgvalue,'static/image/smiley') === false && strpos($imgvalue,'.gif') === false ){
                if(strpos($imgvalue,'http') === false){
                    //本地
                    if(strpos($imgvalue,'thumb.jpg') === false){
                        $article['pics'][] = site_index_url.'/'.$imgvalue.'.thumb.jpg';
                    }else{
                        $article['pics'][] = site_index_url.'/'. $imgvalue;
                    }
                }else{
                    //远程
                    $article['pics'][] = $imgvalue;
                }
            }
        }
        $article['pics'] = array_values(array_unique($article['pics']));

        $mt[ 3 ] = array();
        $lazy_pic_count = 0;
        $lazy_pic_limit = 0;//前三张不懒加载
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
                }
            } else {
                //远程图片
                if (strpos($mt[ 1 ][ $key2 ], 'emotion') || strpos($mt[ 1 ][ $key2 ], 'smiley')) {
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

                }
            }
        }
        $article[ 'content' ] = str_replace($mt[ 0 ], $mt[ 3 ], $article[ 'content' ]);

        //过滤图片链接
        preg_match_all('/<a([^>]*?)>(.*?)<\/a>/is', $article[ 'content' ], $mtta);
        // echo json_encode($mtta[0]);exit;
        if($mtta[0]){
            foreach($mtta[0] as $keya => $valuea){
                // echo $valuea;exit;
                if(stripos($valuea, '<img')){
                    $article[ 'content' ] = str_replace($valuea, $mtta[2][$keya], $article[ 'content' ]);
                }
            }
        }
        //过滤text-indent
        $article[ 'content' ]  = str_replace('text-indent:', 'text - indent : ', $article[ 'content' ] );

        $inforow = array();
        $inforow['id'] = $article['aid'];
        $inforow['cat_id'] = $article['catid'];
        $inforow['cat_name'] = $article['catname'];
        $inforow['title'] = $article['title'];
        $inforow['des'] = $article['summary'];
        $inforow['user_id'] = $article['uid'] ;
        $inforow['user_name'] = $article['username'] ;
        $inforow['user_head'] = avatar($value[ 'uid' ], 'middle' ,true) ;
        $inforow['content'] = $article['content'] ;
        $inforow['click'] = $article['viewnum'] ;
        $inforow['pics'] = $article['pics'] ;
        $article['create_time'] = strtotime($article['dateline']);
        $inforow['create_time'] = $article['create_time'];
        $inforow['create_time_ago'] = date_ago($article['create_time']);
        $inforow['reply_count'] = $article['commentnum'] ;
        success('', $inforow);
    }

}