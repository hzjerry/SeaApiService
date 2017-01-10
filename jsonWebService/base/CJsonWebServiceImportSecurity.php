<?php
/**
 * JsonWebService�ӿڵ��������ȫ��
 * <li>��ȫ��֤��������ж��尲ȫ��֤����</li>
 * @author JerryLi
 *
 */
abstract class CJsonWebServiceImportSecurity{
    /**
     * �ͻ��˵�ʱ���
     * @var double
     */
    protected $_iClientUtcTimestamp = 0;
    /**
     * ���ӿڷ��ʰ�ȫ��Կ
     * @var array
     */
    protected $_aPackageSecurityPubKey = null;
    /**
     * API�������״ֵ̬�б�
     * <li>�ṹarray('code'=>'���ֽ���',...)</li>
     * <li>codeԼ��: �������ַ���, ImportSecurity��֤״̬�뷶Χ902��914</li>
     * @var array
     */
    static public $aResultStateList = array(
        /*902 ~ 914*/
        '902'=>'Configuration file loading failed.(�����֤��ȫ�������ļ�����ʧ��)',
        '903'=>'UTC Timestamp expired.(ʱ������ڣ���utcʱ���3600��)',
        '904'=>'Invalid parameter HTTP HEAD RANDOM.(HTTP HEAD RANDOM������Ч)',
        '905'=>'Lack of necessary HEAD parameters.(ȱ�ٱ�Ҫ��HTTP HEAD����)',
        '906'=>'Invalid signature parameters.(ǩ��������Ч)',
        '907'=>'Signature verification failed.(ǩ����֤ʧ��)',
        '908'=>'checksum check failure.(checksumУ��ʧ��)',
    );
    /**
     * ���밲ȫ��֤��������ļ�
     * @return null:���ü��سɹ� | string: ���δͨ��ʱ���ص�״̬��
     */
    abstract public function loadCfg();
    /**
     * ������ڵ�ǩ����ȫ��֤�������
     * <li>���ڼ����յ���http�����е���Ҫ�ش��İ�ȫ��֤�������Ƿ���</li>
     * @param string ������������
     * <li>���������õķ�ʽ����</li> 
     * @return null:����ͨ����� | string: ���δͨ��ʱ���ص�״̬��
     */
    abstract public function checkSignSecurity(& $sInData);
    /**
     * ������Ȩ��֤checksum
     * <li>����ӿڵķ��ʰ���</li>
     * <li>���û�����ð��������룬��ú�����������</li>
     * @param array $aJoinData �������ݰ�����
     * <li>�������ݰ���ѹ��json�����Ķ���</li>
     * @return null:����ͨ����� | string: ���δͨ��ʱ���ص�״̬��
     */
    abstract public function checkPackageSecurity(& $aJoinData);
    /**
     * ��ȡ��������Կ������
     * <li>���ڻ�ȡԭ����ܶ���� checksum ��Կ���á�</li>
     * <li>���������˱���ܵİ�ȫ��֤��ȥ��checksum�߼������������ֱ�ӷ���null</li>
     */
    abstract public function getPackageSecurityPubKey();
}