<?php
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('FRAME_PATH', '/SeaApiService/jsonWebService/');
include(ROOT_PATH . FRAME_PATH . 'CJsonWebServerReflectionView.php');
/**
 * ��������൥��ʵ����
 */
$oC = new CJsonWebServerReflectionView(
    ROOT_PATH . FRAME_PATH, //���������ڵĸ�Ŀ¼ 
    ROOT_PATH . '/SeaApiService/workgroup/', //����Ŀ¼��
    ROOT_PATH . FRAME_PATH .'template', //����ģ���ļ��ķ���λ�ã���������·���� 
    'jsonWebService/template', //ģ���ļ���Web��Դ����·����url��Ը������·����
    ROOT_PATH . FRAME_PATH .'config/'. CJsonWebServerReflectionView::CONFIG_FILE_NAME, //���������ļ�
    ROOT_PATH . FRAME_PATH .'config/' //�ӿ�client�ͻ��˵�ͨ�������ļ�
);
/**
 * �󶨸���������
 */
require_once(ROOT_PATH . FRAME_PATH .'extends/WSImportSecurity.php');
//�����������֤��ȫ�㣨�����򲻳���checksum�ڵ㣩
$oC->bindImportSecurityObject(new WSImportSecurity(ROOT_PATH . FRAME_PATH .'config/config.json_web_service.php')); //�����������֤��ȫ��
/**
 * ���нӿڷ������
*/
$oC->run();