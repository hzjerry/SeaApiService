<?php
/**
 * JsonWebService客户端配置表(本地开发环境)
 */
return array(
    //调试模式
    'debug'=>false,
    //接口地址
    'url'=>'http://test.fox.cn:8080/SeaApiService/',
    //签名用公钥
    'sign_pub_key'  => 'e911852bcccf3a9f287909bef1868cfb',
    //包访问密钥表
    'package_security_pub_key' => array(
        'advisor'   => array(  //根包的子节点名称
            '_'=>null,
            'test' => array( //包的子节点名称
                '_'=>'67833c132b75951d55c454fcfaf69c82',
            ),
        ),
        'car_pub_attrib' => array( //根包的子节点名称
            '_'=>null,
        ),
    ),
);