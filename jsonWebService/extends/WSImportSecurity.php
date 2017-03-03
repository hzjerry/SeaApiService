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
     * ������Ϣ
     * <li>����Ϊ�ַ�����Ϊ�����ļ��ľ�������·��</li>
     * <li>����Ϊ���飻ֱ��Ϊ���������飬���ݸ�ʽ���� config.json_web_service �����ļ��ĸ�ʽ</li>
     * @var mixed
     */
    private $_mCfg = null;
    /**
     * ����
     * @param mixed $mReflectionCfg �����ܵ������ļ�
     * <li>����Ϊ�ַ�����Ϊ�����ļ��ľ�������·��</li>
     * <li>����Ϊ���飻ֱ��Ϊ���������飬���ݸ�ʽ���� config.json_web_service �����ļ��ĸ�ʽ</li>
     */
    public function __construct($mCfg){
        if (is_string($mCfg)){
            if (file_exists($mCfg)){
                $this->_mCfg = require $mCfg;
            }else{
                echo __CLASS__ . ':Failed to load configuration file.'. "\n file:". $mCfg;
                exit;
            }
        }else{
            $this->_mCfg = $mCfg;
        }

        //���������Ϣ�Ƿ���ȷ
        if (!empty($this->_mCfg)){
            if (!isset($this->_mCfg['sign_pub_key'])){
                echo __CLASS__ . ':Invaild [sign_pub_key]  configuration key.';
                exit;
            }
            if (!isset($this->_mCfg['package_security_pub_key'])){
                echo __CLASS__ . ':Invaild [package_security_pub_key]  configuration key.';
                exit;
            }
        }
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceImportSecurity::loadCfg()
     */
    public function loadCfg(){
        if (!empty($this->_mCfg) && is_array($this->_mCfg)){ //���ü��سɹ�
            $this->_aPubKey = $this->_mCfg['sign_pub_key'];
            $this->_aPackageSecurityPubKey = $this->_mCfg['package_security_pub_key'];
            unset($this->_mCfg);$this->_mCfg=null; //������ɺ��ͷ���Դ
        }else{
            echo __CLASS__ . ':Failed to load configuration.';
            exit;
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
        if (!isset($_SERVER['HTTP_SIGNATURE']) || !isset($_SERVER['HTTP_UTC_TIMESTAMP']) || 
            !isset($_SERVER['HTTP_RANDOM']) || !isset($_SERVER['HTTP_ACCOUNT_KEY']) ){
            return '905';//ȱ�ٱ�Ҫ��HEAD����
        }
        //ȡ��httpͷ���ı�Ҫ����
        $sSign = trim($_SERVER['HTTP_SIGNATURE']);
        $sAK = $_SERVER['HTTP_ACCOUNT_KEY'];
        $this->_iClientUtcTimestamp = intval($_SERVER['HTTP_UTC_TIMESTAMP']);
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
        if (!isset($this->_aPubKey[$sAK])){
            return '907'; //����ǩ����֤ʧ��
        }else{
            if ($this->_aPubKey[$sAK]['deadline'] > 0 && $this->_aPubKey[$sAK]['deadline'] < $iTime){
                return '907'; //��Կ�ѹ��ڣ������˹�Կ
            }
            if (sha1($sInData . $this->_iClientUtcTimestamp . $sRandom . $this->_aPubKey[$sAK]['key']) !== $sSign){
                return '907'; //ǩ��ֵ��һ��
            }
        }
        return null; //����ǩ����֤ͨ��
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