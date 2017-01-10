<?php
require_once (ROOT_PATH . FRAME_PATH . 'base/CJsonWebServiceImportSecurity.php');
/**
 * JsonWebService�ӿڵ��������ȫ��ʵ��
 * <li>�˴�ʵ������fapis�Ļ�����ȫ��֤�߼���������Ҫ����д֮����д����Ҫ��CJsonWebServiceClient������Ӧ���޸�</li>
 * <li>������ڲ�ͬ��jaonWebServiceʵ��������رհ�ȫ�㽫��ȫ����ת�Ƶ��ϲ�����أ�ͨ���ӿڵ��������ά��</li>
 * @author JerryLi
 * @version 2017-01-10
 */
final class WSImportSecurity extends CJsonWebServiceImportSecurity{
    /**
     * ��Կ������ǩ����
     * <li>array(array('key'=>'', 'deadline'=>0),...)</li>
     * @var array
     */
    private $_aPubKey = null;
    /**
     * �����ļ����������·��
     * @var string
     */
    private $_sConfigPath = null;
    /**
     * ����
     * @param string $sFilePath �����ļ���ַ
     * <li>����Ϊ������Ե�ַ</li>
     */
    public function __construct($sFilePath){
        $this->_sConfigPath = $sFilePath;
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceImportSecurity::loadCfg()
     */
    public function loadCfg(){
        if (file_exists($this->_sConfigPath)){ //��������ļ��Ƿ����
            $aCfg = require $this->_sConfigPath; //���������ļ�
            $this->_aPubKey = $aCfg['sign_pub_key'];
            $this->_aPackageSecurityPubKey = $aCfg['package_security_pub_key'];
            unset($aCfg);
        }else{
            return 902; //�����ļ�����ʧ��
        }
        return null;
    }
    /**
     * ��������뵽
     * @return multitype:
     */
    public function getPackageSecurityPubKey(){
        return $this->_aPackageSecurityPubKey;
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceImportSecurity::checkSignSecurity()
     */
    public function checkSignSecurity(& $sInData){
        //����ǩ��������ȡ
        if (isset($_SERVER['HTTP_SIGNATURE'])){ //��ȡHTTPͷ�е�ǩ������
            $sSign = $_SERVER['HTTP_SIGNATURE'];
        }else{ //��GET�л�ȡǩ������
            $sSign = isset($_GET['sign']) ? strtolower(trim($_GET['sign'])) : null;
        }
        if (!isset($_SERVER['HTTP_SIGNATURE']) || !isset($_SERVER['HTTP_UTC_TIMESTEMP']) || !isset($_SERVER['HTTP_RANDOM'])){
            return '901';//ȱ�ٱ�Ҫ��HEAD����
        }
        //ȡ��httpͷ���ı�Ҫ����
        $sSign = trim($_SERVER['HTTP_SIGNATURE']);
        $this->_iClientUtcTimestamp = intval($_SERVER['HTTP_UTC_TIMESTEMP']);
        $sRandom = $_SERVER['HTTP_RANDOM'];
        if (strlen($sRandom) !== 8){
            return '904'; //HTTP HEAD RANDOM������Ч
        }elseif(!empty($sSign) && strlen($sSign) !== 40){
            return '906'; //ǩ����ʽ��Ч
        }
        if (abs(time() - $this->_iClientUtcTimestamp) > 3600){ //����ʱ����Ƿ����׼utcʱ���3600��
            return '903'; //ʱ�������
        }

        //���bodyǩ����Ч��
        $bSignFail = true;
        $iTime = time(); //��ǰʱ��
        foreach ($this->_aPubKey as $aNode){
            if ($aNode['deadline'] > 0 && $aNode['deadline'] < $iTime){
                continue; //��Կ�ѹ��ڣ������˹�Կ
            }
            if (sha1($sInData . $this->_iClientUtcTimestamp . $sRandom . $aNode['key']) === $sSign){
                return null; //ͨ�����
            }
        }
        return '907'; //����ǩ����֤ʧ��
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceImportSecurity::checkPackageSecurity()
     */
    public function checkPackageSecurity(& $aJoinData){
        if (empty($this->_aPackageSecurityPubKey)){
            return null; //δ���ýӿڷ�����Կ
        }
        $sPackage = $aJoinData['package'];
        $sClass = $aJoinData['class'];
        $sCheckSum = (isset($aJoinData['checksum']) && strlen($aJoinData['checksum']) === 32) ? $aJoinData['checksum'] : null;
        //����Ƿ���Ҫ��֤������Ȩ��
        $aPkgName = explode('.', $sPackage);
        $aPubKey = null;
        $aPSP = & $this->_aPackageSecurityPubKey; //ȡ����
        foreach ($aPkgName as $sPkgName){
            if (isset($aPSP[$sPkgName])){ //�ҵ�������������
                $aPSP = & $aPSP[$sPkgName]; //�ı䵱ǰ������ָ��
                $aPubKey = $aPSP['_']; //�ȵ�ǰ�ڵ�ĸ���������
            }else{
                break;
            }
        }
        unset($aPSP);
        if (!is_null($aPubKey)){//�ҵ���Կ����
            $iTime = time(); //��ǰʱ��
            foreach ($aPubKey as $aNode){
                if ($aNode['deadline'] > 0 && $aNode['deadline'] < $iTime){
                    continue; //��Կ�ѹ��ڣ������˹�Կ
                }
                if (md5($this->_iClientUtcTimestamp . $sPackage . $sClass . $aNode['key']) === $sCheckSum){
                    return null;//У��ͨ��
                }
            }
            return '908'; //checksumУ��ʧ��
        }
        return null;
    }
}