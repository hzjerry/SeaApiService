<?php
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('FRAME_PATH', '/SeaApiService/jsonWebService/');
require_once(ROOT_PATH . FRAME_PATH . 'JsonWebService.php');

$oJ = new JsonWebService(
    ROOT_PATH . FRAME_PATH, //框架根
    ROOT_PATH . '/SeaApiService/workgroup/' //工作目录根
);
/**
 * 绑定辅助功能区
 */
require_once(ROOT_PATH . '/SeaApiService/test_write_log.php');
$oJ->bindLogObject(new TestWirteLog()); //TODO 日志接口服务，不绑定就不记录日志服务

require_once(ROOT_PATH . FRAME_PATH .'extends/WSImportSecurity.php');
$oJ->bindImportSecurityObject(new WSImportSecurity(ROOT_PATH . FRAME_PATH .'config/config.json_web_service.php')); //绑定请求入口验证安全层

require_once(ROOT_PATH . '/SeaApiService/test_close_replay.php');
$oJ->bindCloseReplayObject(new TestCloseReplay()); //TODO 绑定截止回放逻辑接口对象

// $oJ->bindTokenSecurityCheckObject(null); //绑定Token安全验证检查对象
// $oJ->bindIoPretreatmentObject(null); //TODO 绑定输入输出流过滤器接口对象
/**
 * 运行接口服务
 */
$oJ->run();