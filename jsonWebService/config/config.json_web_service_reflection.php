<?php
/**
 * JsonWebService�ӿڷ���ϵͳģ�������ļ�(�����ӿ��ĵ�����ϵͳ�����ļ�)
 */
return array(
    //�Ƿ�رսӿڷ���ϵͳ
    'disabled_system'=>false,
    //������(���disabled_system������������������Ч����ע�����ð�����)
    //������֧��*ͨ���
    'white_ipv4'=>array('127.0.0.1', '192.168.*.*'),
    //����˽ӿ������ļ�
    'client_config'=>array(
        //���ؽӿڿͻ��������ļ�
        'local'=>array('name'=>'locat develop (���ػ���)', 'file'=>'config.json_web_service_client.local.php'),
        //Զ�̵Ŀͻ��˿������������ļ�
        'develop'=>array('name'=>'remort develop (Զ�̿�������)', 'file'=>'config.json_web_service_client.develop.php'),
    ),
    //Bannerͷ����Ӧ������
    'banner_head'=>'SeaApiService ',
    //��Ȩ��Ϣ
    'copyright'=>'Jerry.Li (lijian@dzs.mobi) 2015',
);