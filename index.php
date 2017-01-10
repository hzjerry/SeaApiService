<?php
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('FRAME_PATH', '/SeaApiService/jsonWebService/');
require_once(ROOT_PATH . FRAME_PATH . 'JsonWebService.php');

$oJ = new JsonWebService(
    ROOT_PATH . FRAME_PATH, //��ܸ�
    ROOT_PATH . '/SeaApiService/workgroup/' //����Ŀ¼��
);
/**
 * �󶨸���������
 */
require_once(ROOT_PATH . '/SeaApiService/test_write_log.php');
$oJ->bindLogObject(new TestWirteLog()); //TODO ��־�ӿڷ��񣬲��󶨾Ͳ���¼��־����

require_once(ROOT_PATH . FRAME_PATH .'extends/WSImportSecurity.php');
$oJ->bindImportSecurityObject(new WSImportSecurity(ROOT_PATH . FRAME_PATH .'config/config.json_web_service.php')); //�����������֤��ȫ��

require_once(ROOT_PATH . '/SeaApiService/test_close_replay.php');
$oJ->bindCloseReplayObject(new TestCloseReplay()); //TODO �󶨽�ֹ�ط��߼��ӿڶ���

// $oJ->bindTokenSecurityCheckObject(null); //��Token��ȫ��֤������
// $oJ->bindIoPretreatmentObject(null); //TODO ������������������ӿڶ���
/**
 * ���нӿڷ���
 */
$oJ->run();