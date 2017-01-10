<?php
/*
 * example
 * "C:/Program Files/php/5_4_9/php.exe" E:/PHPRoot/test/SeaApiService/cli_shell.php eyJwYWNrYWdlIjoiYWR2aXNvci50ZXN0IiwiY2xhc3MiOiJHRVRfVVNFUl9JTkZPIiwibmFtZSI6IjExIiwiYWdlIjoiMTEifQ==
 */
if (substr(php_sapi_name(), 0, 3) !== 'cli') { //强制只能运行于cli模式
    die("This Programe can only be run in CLI mode\n");
}

define('ROOT_PATH', dirname(dirname(__FILE__)));
define('FRAME_PATH', '/SeaApiService/jsonWebService/');
require_once(ROOT_PATH . FRAME_PATH . 'JsonCliService.php');

$oJ = new JsonCliService(
    ROOT_PATH . FRAME_PATH,
    ROOT_PATH .'/workgroup/'
);
/**
 * 绑定辅助功能区
 */
require_once(ROOT_PATH . '/SeaApiService/test_write_log.php');
$oJ->bindLogObject(new TestWirteLog()); //TODO 日志接口服务，不绑定就不记录日志服务
// $oJ->bindIoPretreatmentObject(null); //TODO 绑定输入输出流过滤器接口对象
// $oJ->bindTokenSecurityCheckObject(null); //绑定Token安全验证检查对象
/**
 * 运行接口服务
 */
$oJ->run();