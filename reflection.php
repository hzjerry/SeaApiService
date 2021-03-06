<?php
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('FRAME_PATH', '/SeaApiService/jsonWebService/');
include(ROOT_PATH . FRAME_PATH . 'CJsonWebServerReflectionView.php');
/**
 * 反射基础类单例实例化
 */
$oC = new CJsonWebServerReflectionView(
    ROOT_PATH . FRAME_PATH, //反射框架所在的根目录 
    ROOT_PATH . '/SeaApiService/workgroup/', //工作目录根
    ROOT_PATH . FRAME_PATH .'template', //反射模板文件的访问位置（绝对物理路径） 
    'jsonWebService/template', //模板文件的Web资源访问路径（url相对根的相对路径）
    ROOT_PATH . FRAME_PATH .'config/'. CJsonWebServerReflectionView::CONFIG_FILE_NAME, //反射配置文件
    ROOT_PATH . FRAME_PATH .'config/' //接口client客户端的通信配置文件
);
/**
 * 绑定辅助功能区
 */
require_once(ROOT_PATH . FRAME_PATH .'extends/WSImportSecurity.php');
//绑定请求入口验证安全层（不绑定则不出现checksum节点）
$oC->bindImportSecurityObject(new WSImportSecurity(ROOT_PATH . FRAME_PATH .'config/config.json_web_service.php')); //绑定请求入口验证安全层
/**
 * 运行接口反射服务
*/
$oC->run();