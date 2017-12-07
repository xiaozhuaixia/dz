<?php
/**
 * 南京灵衍信息科技有限公司
 * User: wangxiao
 * Date: 2017/2/8
 * Time: 16:13
 */

class MagConfig{
    static public $_MAG_CONFIG = array(
        'MAG_INDEX_URL' => 'http://magapp-x.net',
        'HTTP_MAG_DZ_API_SALT' => '65392cbd0b10eadb5d585741e94a4f3d',
        //用户手机关系表
        'user_mobile_relations' => 'user_mobile_relations',
        'user_mobile_relations_id' => 'id',
        'user_mobile_relations_userid' => 'userid',
        'user_mobile_relations_phone' => 'phone',
        'user_mobile_relations_create_time' => 'create_time',
        
        //用户QQ关系表
        'user_qq_relations' => 'common_member_connect',
        'user_qq_relations_id' => '',
        'user_qq_relations_userid' => 'uid',
        'user_qq_relations_openid' => 'conopenid',
        'user_qq_relations_name' => '',
        'user_qq_relations_create_time' => '',

        //用户weixin关系表
        'user_weixin_relations' => 'user_weixin_relations',
        'user_weixin_relations_id' => '',
        'user_weixin_relations_userid' => 'userid',
        'user_weixin_relations_openid' => 'openid',
        'user_weixin_relations_unionid' => 'unionid',
        'user_weixin_relations_name' => 'name',
        'user_weixin_relations_create_time' => 'create_time',


        //远程缩略图
        'thumb_url' => '',
        'thumb_url_end' => '',

        'emotion_url_contain' => array('smiley,emotion,mobcent,gif'),
        'threadlist_step' => 0, //帖子列表分页数量
    );

}



