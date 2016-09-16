<?php
/**
 * JsonWebService�ӿڵĿͻ�����
 * @author JerryLi 2015-09-04
 *
 */
class CJsonWebServiceClient{
    /**
     * �����ַ���(���ݱ��ؿ��������л�)
     * @var string
     */
    const LOCAL_CHARSET = 'GBK';
    /**
     * �ͻ�����Ϣ
     * @var string
     */
    private $_sClientName = null;
    /**
     * ����ģʽ
     * @var boolean
     */
    private $_bDebug = false;
    /**
     * Զ�̽ӿڵķ��ʵ�ַ
     * @var string
     */
    private $_sJWS_URL = null;
    /**
     * ��Կ������ǩ����
     * <li>array(array('key'=>'', 'deadline'=>0),...)</li>
     * @var array
     */
    private $_aPubKey = '';
    /**
     * ϵͳʱ���
     * @var int
     */
    private $_iUtcTimestemp = 0;
    /**
     * ���ӿڷ��ʰ�ȫ��Կ
     * @var array
     */
    private $_aPackageSecurityPubKey = null;
    /**
     * ��վ�������Ŀ¼
     * @var string
     */
    private $_sRootPath = '';
    /**
     * �ӿڵ�ִ�п�ʼʱ��
     * <li>��λ:΢��</li>
     * @var int
     */
    private $_iStartTime = 0;
    /**
     * �������һ�ε���ʷͨ������
     * @var array('sent'=>null, 'receive'=>null, 'sign'=>null)
     */
    private $_aHistory = array('sent'=>null, 'receive'=>null, 'sign'=>null);
    /**
     * �������һ��������curlͨ�Ŵ���
     * @var stirng
     */
    private $_last_curl_error = null;

    /**
     * ���캯��
     * @param string $sRootPath ��վ���Ը�Ŀ¼
     * @param string $sFramePath ����ļ�����Ը�·��
     * @param string $sConfigFile �����ļ�����
     */
    public function __construct($sRootPath, $sFramePath, $sConfigFile){
        $this->_iStartTime = microtime(true); //��¼��ʼʱ��
        $this->_sClientName = 'sea_api_php/1.10.0 ('. $_SERVER['SERVER_SOFTWARE'] .')';
        $this->_sRootPath = $sRootPath;
        //���������ļ���Ϣ
        $this->_read_config(rtrim($sRootPath, '/') .'/'. rtrim($sFramePath, '/') .'/config/'. $sConfigFile);
    }
    /**
     * ��ȡ������Ϣ
     */
    protected function _read_config($sFilePath){
        if (file_exists($sFilePath)){ //��������ļ��Ƿ����
            $aCfg = require $sFilePath; //���������ļ�
            $this->_aPubKey = $aCfg['sign_pub_key'];
            $this->_sJWS_URL = $aCfg['url'];
            $this->_aPackageSecurityPubKey = $aCfg['package_security_pub_key'];
            $this->_bDebug = $aCfg['debug'];
            unset($aCfg);
            return true;
        }else{
            echo __CLASS__ . ':Failed to load the configuration file.';
            exit;
        }
    }
    /**
     * ��������
     * @see CExtModel::__destruct()
     */
    public function __destruct(){
    }
    /**
     * ��������ʱ��
     * @return int ms
     */
    public function runtime(){
        return intval((microtime(true) - $this->_iStartTime) * 1000);
    }
    /**
     * �����Զ���Ŀͻ���USER_AGENT_HEADER
     * @param string $sStr
     * <li>��ʽ: client_name/vercode (....)</li>
     * <li>����: chemao_php/1.10.0 (php 5.4 nginx win64)</li>
     */
    public function setUserAgent($sStr){
        $this->_sClientName = $sStr;
    }
    /**
     * ��ȡ���һ��ͨ������
     * @param string $sType ����[sent:���ͳ������� | receive:���յ�������]
     * @return string | null
     * <li>ע�⣺������ַ������ͱض�ΪUTF-8</li>
     */
    public function getHistory($sType){
        if ('sent' === $sType){
            return $this->_aHistory['sent'];
        }else{
            return $this->_aHistory['receive'];
        }
    }
    /**
     * ִ�нӿ�ͨ��
     * @param string $sPackage ����
     * @param string $sClass ����
     * @param array $aParam ��Ҫ�ύ�Ĳ���
     * <li>ע�⣺��������Ϊ�����ַ���</li>
     * @param boolean $bDebug ��ʱ����һ�ε���ģʽ
     * @return 0:ͨ��ʧ�� | -1:����Jsonʧ�� | array():��������
     * <li>ע�⣺�������Ϊ�����ַ���</li>
     */
    public function exec($sPackage, $sClass, $aParam, $bDebug=false){
        $aPkgName = explode('.', $sPackage);
        $sPkgKey = null;
        $aPSP = & $this->_aPackageSecurityPubKey; //ȡ����
        foreach ($aPkgName as $sPkgName){
            if (isset($aPSP[$sPkgName])){ //�ҵ�������������
                $aPSP = & $aPSP[$sPkgName]; //�ı䵱ǰ������ָ��
                $sPkgKey = $aPSP['_']; //�ȵ�ǰ�ڵ�ĸ���������
            }else{
                break;
            }
        }
        unset($aPSP, $aPkgName);
        //��ȫȱʧ������
        $aParam['package'] = $sPackage;
        $aParam['class'] = $sClass;
        $this->_iUtcTimestemp = time(); //����ϵͳʱ���
        if (!is_null($sPkgKey)){
            $aParam['checksum'] = md5($this->_iUtcTimestemp . $sPackage . $sClass . $sPkgKey);
        }
        $sRet = $this->_transmission($aParam, $this->_aPubKey, $bDebug);
        if (false === $sRet){
            return 0;
        }else{
            if (is_null($aJson = json_decode($sRet, true))){ //����ʧ��
                return -1;
            }else{ //�����ɹ�
                if ('UTF-8' !== self::LOCAL_CHARSET) //ʶ�����Դ�ַ�����UTF-8��ǿ��ת��ΪUTF-8
                    $aJson = self::convert_encoding('UTF-8', self::LOCAL_CHARSET, $aJson);
                return $aJson;
            }
        }
    }
    /**
     * ��ȡԶ�̽ӿڵ�ַ
     * @return string
     */
    public function getRemoteUrl(){
        return $this->_sJWS_URL;
    }
    /**
     * ���ݽӿڴ����
     * @param array $aData ��Ҫ���͵��������
     * @param string $sKey ǩ����Կ
     * @param boolean $bDebug ��ʱ����һ�ε���ģʽ
     * @return string | false
     */
    private function _transmission(& $aData, $sKey, $bDebug){

        if ('UTF-8' !== self::LOCAL_CHARSET) //ʶ�����Դ�ַ�����UTF-8��ǿ��ת��ΪUTF-8
            $aData = self::convert_encoding(self::LOCAL_CHARSET, 'UTF-8', $aData);

        $sData = self::json_encode($aData, JSON_UNESCAPED_UNICODE); //����ת����json����
        $this->_aHistory['sent'] = $sData; //�������һ�η��͵�����
        $iRandom = rand(10000000, 99999999); //�����
        $this->_aHistory['sign'] = sha1($sData . $this->_iUtcTimestemp . $iRandom . $sKey); //���ݰ�ǩ��ֵ
        $ch = curl_init();//��ʼ��curl
        $aHeader = array();
        $aHeader[] = 'Connection: close';
        $aHeader[] = 'Content-Type: application/json; charset=utf-8';
        $aHeader[] = 'Content-length: '. strlen($sData);
        $aHeader[] = 'Cache-Control: no-cache';
        $aHeader[] = 'Signature: '. $this->_aHistory['sign'];
//         $aHeader[] = 'Signature: f947d4e3b400adcfd287b0fb9276e5f237cf969a'; //���ԻطŹ���
        $aHeader[] = 'UTC-Timestemp: '. $this->_iUtcTimestemp;  //HTTP_UTC_TIMESTEMP
        $aHeader[] = 'Random: '. $iRandom; //HTTP_RANDOM
        $aHeader[] = 'Expect:';
        if (false !== strpos($this->_sJWS_URL, 'https:')){ //����httpsר������ͷ
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ����֤����
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // ��֤���м��SSL�����㷨�Ƿ����
        }
        curl_setopt($ch,CURLOPT_URL, $this->_sJWS_URL); //�ӿڵ�ַ
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0); //ǿ��Э��Ϊ1.0
        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);//����header
        curl_setopt($ch, CURLOPT_USERAGENT, $this->_sClientName);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//�����������ַ���
        curl_setopt($ch, CURLOPT_POST, true);//post�ύ��ʽ
        curl_setopt($ch, CURLOPT_ENCODING ,'gzip'); //����gzip����
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 ); //php�汾5.3�����ϣ��ɹر�IPV6��ֻʹ��IPV4
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5 ); //php�汾5.2.3�����ϣ����ӳ�ʱʱ��(��)
        curl_setopt($ch, CURLOPT_TIMEOUT, 60 ); //���г�ʱ(��)
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sData); //�ͳ�post����
        curl_setopt($ch, CURLOPT_HEADER, true); //��ȡͷ��Ϣ
        $iStart = microtime(true); //��¼ͨ�ſ�ʼʱ��
        $sResponse = curl_exec($ch);//����curl
        $aCurlInfo = curl_getinfo($ch);//��ȡ״̬��Ϣ
        if (200 === $aCurlInfo['http_code']){ //ͨ�ųɹ�
            $this->_aHistory['receive'] = substr($sResponse, strpos($sResponse, "\r\n\r\n")+4); //�������һ���յ�������
            unset($sResponse);
            $this->_last_curl_error = null;
            if ($bDebug || $this->_bDebug){ //����ģʽʱ�����
                echo '<pre>', 'Local charset: ', self::LOCAL_CHARSET, "\n",
                'Interface url: ', $this->_sJWS_URL, "\n",
                'Transmission time(ms): ', sprintf('%.4f', (microtime(true) - $iStart)*1000), "\n",
                'Send data:', "\n", self::jsonFormat(self::convert_encoding('UTF-8', self::LOCAL_CHARSET, $sData)), "\n",
                'Receive data:', "\n", self::jsonFormat(self::convert_encoding('UTF-8', self::LOCAL_CHARSET, $this->_aHistory['receive'])), "\n",
                '</pre>', "\n";
            }
            curl_close($ch);
            unset($ch, $aCurlInfo);
            return $this->_aHistory['receive'];
        }else{ //ͨ��ʧ��
            $this->_last_curl_error = curl_error($ch);
            if (empty($this->_last_curl_error)){
                $this->_last_curl_error = 'http_code:'. $aCurlInfo['http_code'];
            }
            unset($ch, $aCurlInfo);
            if ($bDebug || $this->_bDebug){ //����ģʽʱ�����������Ϣ
                echo '<pre>', 'Local charset: ', self::LOCAL_CHARSET, "\n",
                'Interface url: ', $this->_sJWS_URL, "\n",
                'Transmission time(ms): ', sprintf('%.4f', (microtime(true) - $iStart)*1000), "\n",
                'Send data:', "\n", self::jsonFormat(self::convert_encoding('UTF-8', self::LOCAL_CHARSET, $sData)), "\n",
                'error info:', "\n", $this->_last_curl_error, "\n",
                '</pre>', "\n";
            }
            return false;
        }
    }
    /**
     * �Ա������ݵĽ����ַ�����ת��
     * @param string $sInCharset ת��ǰ���ַ���
     * @param string $sOutCharset ת������ַ���
     * @param string | array $mixd ��ת���ı�����������ַ�����
     * @return string | array ���ת����Ľ��
     */
    static public function convert_encoding($sInCharset, $sOutCharset, & $mixd) {
        if ($sInCharset === $sOutCharset) //�ַ�����ͬʱ��ת��
            return $mixd;

        if (is_array($mixd)) {
            $tmp = array();
            foreach ($mixd as $key => $val) {
                $tmp[$key] = self::convert_encoding($sInCharset, $sOutCharset, $val);
            }
            return $tmp;
        } else { //�ַ�����ͬʱ��ת��
            return mb_convert_encoding($mixd, $sOutCharset, $sInCharset);
        }
    }
    /**
     * json_encode���ݰ汾���ж��Ƿ񲻱���ȫ���ַ���
     * @param array $aData UTF-8�ַ����������������
     * @return string json�ַ���
     */
    static function json_encode(& $aData){
        list($a, $b, $c) = explode('.', PHP_VERSION); //ȡ���汾��
        if (intval($a) >= 5 && intval($b) >= 4){
            return json_encode($aData, JSON_UNESCAPED_UNICODE); //������ȫ���ַ���
        }else{
            return json_encode($aData);
        }
    }
    /**
     * Json���ݸ�ʽ��
     * @param  Mixed  $data   ����
     * @param  String $indent �����ַ���Ĭ��4���ո�
     * @return JSON
     */
    static private function jsonFormat($data, $indent=null){
        // ��������
        $aOutBuf = array();
        $pos = 0;
        $length = strlen($data);
        $indent = isset($indent)? $indent : '    ';
        $newline = "\n";
        $prevchar = '';
        $outofquotes = true;
        for($i=0; $i<=$length; $i++){
            $char = substr($data, $i, 1);
            if($char=='"' && $prevchar!='\\'){
                $outofquotes = !$outofquotes;
            }elseif(($char=='}' || $char==']') && $outofquotes){
                $aOutBuf[] = $newline;
                $pos --;
                for($j=0; $j<$pos; $j++){
                    $aOutBuf[] = $indent;
                }
            }
            $aOutBuf[] = $char;
            if(($char==',' || $char=='{' || $char=='[') && $outofquotes){
                $aOutBuf[] = $newline;
                if($char=='{' || $char=='['){
                    $pos ++;
                }
                for($j=0; $j<$pos; $j++){
                    $aOutBuf[] = $indent;
                }
            }
            $prevchar = $char;
        }
        return implode($aOutBuf);
    }
}