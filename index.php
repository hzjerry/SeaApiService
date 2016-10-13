<?php
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('FRAME_PATH', '/SeaApiService/jsonWebService/');
include(ROOT_PATH . FRAME_PATH . 'JsonWebService.php');

$oJ = new JsonWebService(ROOT_PATH, FRAME_PATH, ROOT_PATH . FRAME_PATH. 'config/');
/**
 * �󶨸���������
 */
include(ROOT_PATH . '/SeaApiService/test_write_log.php');
$oJ->bindLogObject(new TestWirteLog()); //TODO ��־�ӿڷ��񣬲��󶨾Ͳ���¼��־����
// $oJ->bindIoPretreatmentObject(null); //TODO ������������������ӿڶ���
include(ROOT_PATH . '/SeaApiService/test_close_replay.php');
$oJ->bindCloseReplayObject(new TestCloseReplay()); //TODO �󶨽�ֹ�ط��߼��ӿڶ���
// $oJ->bindTokenSecurityCheckObject(null); //��Token��ȫ��֤������
/**
 * ���нӿڷ���
 */
$oJ->run();