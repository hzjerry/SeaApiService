<?php
/**
 * JsonWebService接口服务的配置文件
 * <li>utc时间戳的生成样例 strtotime('2015-12-30 00:00:00')</li>
 */
return array(
    //业务逻辑类文件目录区
    'workgroup'     =>  'SeaApiService/workgroup/',
    /*
     * 入口校验用的重放时间粒度(0表示无约束, >0表示允许与服务器之间的误差秒数)
     * 即:允许客户端与服务器之间差的误差范围，防止包被截获后重复提交
     * 如果开启了截止回放逻辑bindCloseReplayObject()，此参数为sign的过期时间（建议此时设为3600）
     */
    'replay_time'   =>  300,
    /*
     * 签名用公钥表(deadline=0表示不过期，>0表示失效的时间utc时间戳)
     * 为了优化性能，请尽可能将未失效的key靠前摆放
     */
    'sign_pub_key'  =>  array(
        array('key'=>'e911852bcccf3a9f287909bef1868cfb', 'deadline'=>0),
        array('key'=>'1717c2b2bc8a71436d26669ae50ed75b', 'deadline'=>strtotime('2015-12-30 00:00:00')), //如果要加入密钥失效时间，这样操作
    ),
    /* package访问安全公钥配置表 '_'表示根节点密码
     * key:包访问密钥 | replay_time:时间戳重放时间(s) | deadline:失效时间(utc time)
     */
    'package_security_pub_key' => array(
        'advisor' => array(  //根包的子节点名称
            '_'=>null, //当前根密钥(null表示无密钥约束)
            'test' => array( //包的子节点名称
                '_'=>array( //当前根密钥
                    array('key'=>'67833c132b75951d55c454fcfaf69c82', 'deadline'=>0)
                ),
            ),
            'find_car_task' => array( //包的子节点名称
                '_'=>array( //当前根密钥
                    array('key'=>'9f29c96fb5e7e39c1216b8883c3a982d', 'deadline'=>0)
                ),
            ),
        ),
        'car_pub_attrib' => array( //根包的子节点名称
            '_'=>null,  //当前根密钥(null表示无密钥约束)
        ),
    ),
);