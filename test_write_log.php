<?php
include(ROOT_PATH . FRAME_PATH . 'interface/IJsonWebServiceLog.php');
/**
 * ģ��Ĳ���������ʵ����־�ӿ�
 * <li>��ʱ��ʱ�����ڱ���</li>
 * <li>������Ҫ���Ա�д���ӵ���־�洢�߼�</li>
 * @author JerryLi
 *
 */
final class TestWirteLog implements IJsonWebServiceLog{
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceLog::createLog()
     */
    public function createLog($aParam){
        $aParam['ip'] = implode('.', JsonWebService::real_ip()); //������IP
        $aParam['memory'] = ceil(memory_get_peak_usage()/1000); //kb
        $aParam['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $aParam['date'] = date('Y-m-d H:i:s'); //kb
        if (($hf = fopen(ROOT_PATH . '/SeaApiService/JsonWebServiceLog.log', 'ab')) !== false){
            if (!fwrite($hf, print_r($aParam, true) ."\n")){	//��־д��ʧ��
               //��־д��ʧ�ܣ���������
            }
            fclose($hf); //��־�ɹ�д��
        }
    }
}