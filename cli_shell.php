<?php
/*
 * example
 * "C:/Program Files/php/5_4_9/php.exe" E:/PHPRoot/test/SeaApiService/cli_shell.php eyJwYWNrYWdlIjoiYWR2aXNvci50ZXN0IiwiY2xhc3MiOiJHRVRfVVNFUl9JTkZPIiwibmFtZSI6IjExIiwiYWdlIjoiMTEifQ==
 */
if (substr(php_sapi_name(), 0, 3) !== 'cli') { //ǿ��ֻ��������cliģʽ
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
 * �󶨸���������
 */
require_once(ROOT_PATH . '/SeaApiService/test_write_log.php');
$oJ->bindLogObject(new TestWirteLog()); //TODO ��־�ӿڷ��񣬲��󶨾Ͳ���¼��־����
// $oJ->bindIoPretreatmentObject(null); //TODO ������������������ӿڶ���
// $oJ->bindTokenSecurityCheckObject(null); //��Token��ȫ��֤������
/**
 * ���нӿڷ���
 */
$oJ->run();