<?php
/**
 * JsonWebService接口反射系统模块配置文件(即：接口文档管理系统配置文件)
 */
return array(
    //是否关闭接口反射系统
    'disabled_system'=>false,
    //白名单(如果disabled_system开启，白名单必须生效，请注意配置白名单)
    //白名单支持*通配符
    'white_ipv4'=>array('127.0.0.1', '192.168.*.*'),
    //服务端接口配置文件
    'client_config'=>array(
        //本地接口客户端配置文件
        'local'=>array('name'=>'locat develop (本地环境)', 'file'=>'config.json_web_service_client.local.php'),
        //远程的客户端开发环境配置文件
        'develop'=>array('name'=>'remort develop (远程开发环境)', 'file'=>'config.json_web_service_client.develop.php'),
    ),
    //Banner头部的应用名称
    'banner_head'=>'SeaApiService ',
    //版权信息
    'copyright'=>'Jerry.Li (lijian@dzs.mobi) 2015',
);