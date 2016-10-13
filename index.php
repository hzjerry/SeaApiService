<?php
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('FRAME_PATH', '/SeaApiService/jsonWebService/');
include(ROOT_PATH . FRAME_PATH . 'JsonWebService.php');

$oJ = new JsonWebService(ROOT_PATH, FRAME_PATH, ROOT_PATH . FRAME_PATH. 'config/');
/**
 * 绑定辅助功能区
 */
include(ROOT_PATH . '/SeaApiService/test_write_log.php');
$oJ->bindLogObject(new TestWirteLog()); //TODO 日志接口服务，不绑定就不记录日志服务
// $oJ->bindIoPretreatmentObject(null); //TODO 绑定输入输出流过滤器接口对象
include(ROOT_PATH . '/SeaApiService/test_close_replay.php');
$oJ->bindCloseReplayObject(new TestCloseReplay()); //TODO 绑定截止回放逻辑接口对象
// $oJ->bindTokenSecurityCheckObject(null); //绑定Token安全验证检查对象
/**
 * 运行接口服务
 */
$oJ->run();