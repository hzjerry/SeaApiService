<?php
/**
 * JsonCliService �ӿڷ����cli���
 * @author JerryLi 2016-08-11
 * @see
 * <li>���÷�ʽʹ��cli��ʽ���ʣ������json��ڲ�������ʹ��base64�������Ϊ�ַ�����������</li>
 * <li>"C:/Program Files/php/5_4_9/php.exe" E:/PHPRoot/test/SeaApiService/test_cli.php eyJwYWNrYWdlIjoiYWR2aXNvci50ZXN0IiwiY2xhc3MiOiJHRVRfVVNFUl9JTkZPIiwibmFtZSI6IjExIiwiYWdlIjoiMTEifQ==</li>
 * @final
 */
final class JsonCliService{
    /**
     * Service�˵������ļ�����
     * @var string
     */
    const CONFIG_FILE_NAME='config.json_web_service.php';
    /**
     * �����ַ���(���ݱ��ؿ��������л�)
     * @var string
     */
    const LOCAL_CHARSET = 'GBK';
    /**
     * �ͻ��˵�ʱ���
     * @var double
     */
    private $_iClientUtcTimestemp = 0;
    /**
     * ��վ�������Ŀ¼
     * @var string
     */
    private $_sRootPath = '';
    /**
     * ��ܵ���Ը�·��
     * @var string
     */
    private $_sFramePath = '';
    /**
     * �ӿڵ�ҵ���߼���Ŀ¼
     * @var string
     */
    private $_sWorkspace = '';
    /**
     * ���������post����
     * @var string
     */
    private $_sInData = null;
    /**
     * ��������������������
     * @var array
     */
    private $_aInJson = null;
    /**
     * ���ؽ����������
     * @var array
     */
    private $_aResultData = array();
    /**
     * ϵͳ��־
     * @var array
     * <li>array('in'=>'�������', 'out'=>'��������', 'pkg'=>'��·����Ϣ', 'cls'=>'�ӿ�����Ϣ', 'status_code'=>'״̬��', 'step'=>'�׶�',
     * 'runtime'=>'����ʱ��ms', 'sign'=>'bodyǩ��')</li>
     * <li>step: [receive:���յ����� | resolve:Json�����ɹ����� | reply:�ӿ������ظ� | app_err:Ӧ�ô���]</li>
     */
    private $_aLog = array('in'=>null, 'out'=>null, 'pkg'=>'', 'cls'=>'', 'status_code'=>'', 'step'=>'', 'sign'=>'');
    /**
     * �ӿڵ�ִ�п�ʼʱ��
     * <li>��λ:΢��</li>
     * @var int
     */
    private $_iStartTime = 0;
    /**
     * ��־�ӿڶ���
     * @var IJsonWebServiceLog
     */
    private $_ifLog = null;
    /**
     * Token��ȫУ�����
     * @var CJsonWebServiceTokenSecurity
     */
    private $_oTokenSecurity = null;

    /**
     * �ӿڵ����������������
     * @var IJsonWebServiceIoPretreatment
     */
    private $_ifIoPretreatment = null;
    /**
     * API�������״ֵ̬�б�
     * <li>�ṹarray(array('code'='״ֵ̬����', 'msg'=>'���ֽ���'),...)</li>
     * <li>codeԼ��: �������ַ���, ϵͳ������:000~999; Ӧ�ü�����:00001~99999; Ӧ�ü�����ֵ:00000</li>
     * @var array
     */
    static public $aResultStateList = array(
        '999'=>'There is no post and get data.(������post��get����)',
        '901'=>'Received protocol packets can not be resolved.(�յ���Э����޷�����)',
        '901'=>'Lack of necessary HEAD parameters.(ȱ�ٱ�Ҫ��HTTP HEAD����)',
        '902'=>'Invalid parameter HTTP HEAD RANDOM.(HTTP HEAD RANDOM������Ч)',
        '903'=>'UTC Time stamp expired.(ʱ�������)',
        '904'=>'Refused to replay the request.(�ܾ��ط�����)',
        '905'=>'Request be Pretreatment to blocked.(����Ԥ�������)',
        '910'=>'Configuration file read failed.(�����ļ���ȡʧ��)',
        '911'=>'Data signature is incorrect.(����ǩ������ȷ)',
        '912'=>'package and class node values do not exist.(package��class�ڵ�ֵ������)',
        '913'=>'checksum value attribute node does not exist.(checksum�ڵ��value���Բ�����)',
        '914'=>'The checksum validation did not pass.(checksumУ��δͨ��)',
        '915'=>'API interface class not found.(api�ӿڷ�����δ�ҵ�)',
        //916 �� CJsonWebServiceLogicBase ��ռ�� //'API interface services no output result set.(api�ӿڷ�������������)'
        '917'=>'checksum check failure.(checksumУ��ʧ��)',
        //920 �� CJsonWebServiceLogicBase ��ռ�� //'The returned value of the unregistered state.(δע��ķ���״ֵ̬)'
        '930'=>'Invalid characters in package.(package�д�����Ч�ַ�)',
        '940'=>'Interface has invalid.(�˽ӿ��Ѿ��ϳ���ֹͣ����)',
        //950��959 Tokenռ�õ�״̬��
    );
    /**
     * ���캯��
     * @param string $sRootPath ��վ���Ը�Ŀ¼
     * @param string $sFramePath ����ļ�����Ը�·��
     */
    public function __construct($sRootPath, $sFramePath){
        ob_end_flush(); //ˢ�����������ر��������
        $this->_iStartTime = microtime(true); //��¼��ʼʱ��
        $this->_sRootPath = $sRootPath;
        $this->_sFramePath = $sFramePath;
        //���ر���Ļ�����
        require_once rtrim($sRootPath, '/') .'/'. rtrim($sFramePath, '/') .'/base/CJsonWebServiceLogicBase.php';
        require_once rtrim($sRootPath, '/') .'/'. rtrim($sFramePath, '/') .'/interface/IJsonWebServiceProtocol.php';
        //ע��CJsonWebServiceLogicBase���е�״̬��
        foreach (CJsonWebServiceLogicBase::$aResultStateList as $sKey => $sVal){
            self::$aResultStateList[strval($sKey)] = $sVal;
        }
        //���������ļ���Ϣ
        if (!$this->_read_config(rtrim($sRootPath, '/') .'/'. rtrim($sFramePath, '/') .'/config/'. self::CONFIG_FILE_NAME)){
            //�����ļ�����ʧ��
            $this->_output(); //�������ֵ
        }
    }
    /**
     * ����
     * <li>����ʱ�����������־����������ִ����־д�����</li>
     */
    public function __destruct(){
        if (!is_null($this->_ifLog)){ //������־����ʱ�����־
            $this->_aLog['runtime'] = intval((microtime(true) - $this->_iStartTime) * 1000 + 0.5);
            $this->_ifLog->createLog($this->_aLog);
        }
    }
    /**
     * ����־��¼�ӿ�
     * @param IJsonWebServiceLog $iflog
     * @param void
     */
    public function bindLogObject(IJsonWebServiceLog $iflog){
        $this->_ifLog = $iflog;
    }
    /**
     * ��Token��ȫ��֤������
     * @param CJsonWebServiceTokenSecurity $oTSC
     * @param void
     */
    public function bindTokenSecurityCheckObject(CJsonWebServiceTokenSecurity $oTSC){
        if (is_null($this->_oTokenSecurity)){//ע��CJsonWebServiceTokenSecurity���е�״̬��
            foreach (CJsonWebServiceTokenSecurity::$aResultStateList as $sKey => $sVal){
                self::$aResultStateList[strval($sKey)] = $sVal;
            }
        }
        $this->_oTokenSecurity = $oTSC;
    }
    /**
     * ������������������ӿڶ���
     * @param IJsonWebServiceLog $iflog
     * @param void
     */
    public function bindIoPretreatmentObject(IJsonWebServiceIoPretreatment $ifTCO){
        $this->_ifIoPretreatment = $ifTCO;
    }
    /**
     * ���нӿڽ�������
     * @return void
     */
    public function run(){
        $this->_execute(); //��ʼִ�н�������
        $this->_output(); //�������ֵ
    }
    /**
     * ִ�н�������
     * <li>�����������ڷ���</li>
     * @return void
     */
    private function _execute(){
        if ($this->getInput()){//��ȡ������������
            $this->_route(); //��ʼ����·�ɷ���
        }
    }
    /**
     * ��ȡ������Ϣ
     * @return void
     */
    protected function _read_config($sFilePath){
        if (file_exists($sFilePath)){ //��������ļ��Ƿ����
            $aCfg = require $sFilePath; //���������ļ�
            $this->_sWorkspace = $aCfg['workgroup'];
            unset($aCfg);
            return true;
        }else{
            $this->_throwState('910'); //�����ļ���ȡʧ��
            return false;
        }
    }
    /**
     * ��ȡ���������
     * @return boolean
     */
    protected function getInput(){
        global $argv;
        if (isset($argv[1]) && !empty($argv[1])){
            $this->_sInData = base64_decode($argv[1]); //��ȡ����
        }
        if (is_null($this->_sInData) || false === $this->_sInData){ //�����������ʧ�ܻ���û���������
            $this->_throwState('999');
            return false;
        }
        if(!is_null($this->_ifLog)){ //��¼��־
            $this->_aLog['in'] = $this->_sInData;
            $this->_aLog['step'] = 'receive';
        }
        //���յ������ݰ�ת��Ϊ����
        $this->_aInJson = json_decode($this->_sInData, true);
        if (is_null($this->_aInJson)){ //����jsonʧ��
            $this->_throwState('901');
            return false;
        }else{ //json�����ɹ�
            if (!is_null($this->_ifIoPretreatment)){ //������������������ַ���Ԥ����
                $this->_ifIoPretreatment->filterInport($this->_aInJson);
            }
            $this->_aInJson = self::convert_encoding('UTF-8', self::LOCAL_CHARSET, $this->_aInJson); //���ַ�ת���ɱ����ַ���
            return true;
        }
    }
    /**
     * ·��
     * @return boolean
     */
    protected function _route(){
        if (!isset($this->_aInJson['package']) || empty($this->_aInJson['package']) ||
            !isset($this->_aInJson['class']) || empty($this->_aInJson['class'])){
            $this->_throwState('912'); //ȱ��package��class������
            return false;
        }
        if(!is_null($this->_ifLog)){ //��¼��־
            $this->_aLog['pkg'] = $this->_aInJson['package'];
            $this->_aLog['cls'] = $this->_aInJson['class'];
            $this->_aLog['step'] = 'resolve';
        }
        //��֤package��ֵ�Ƿ�Ƿ�
        if(!preg_match('/^[a-zA-Z][\w\.?]*[a-zA-Z0-9]$/', $this->_aInJson['package'])){ 
            $this->_throwState('930'); //package��Ч�ַ�
            return false;
        }
        //�ӿڷ���Ԥ������
        $sFile = rtrim($this->_sRootPath, '/') .'/'. str_replace('.', '/', rtrim($this->_sWorkspace, '.')) .
                 substr($this->_aInJson['package'], 0, strpos($this->_aInJson['package'], '.')) . '/ApiPretreatment.php';
        if (file_exists($sFile)){ //Ԥ�����ļ�����
            require_once rtrim($this->_sRootPath, '/') .'/'. rtrim($this->_sFramePath, '/') .'/interface/IJsonWebServiceVisitPretreatment.php';//ע��ӿ�����
            require_once $sFile; //���ذ��ķ���Ԥ������
            $aRunClass = 'ApiPretreatment'; //����Ԥ����������
            if (class_exists($aRunClass, false)){ //�ҵ�����Ԥ������
                $oap = new $aRunClass();
                if (is_a($oap, 'IJsonWebServiceVisitPretreatment')){
                    if ($oap->toDo($this->_aInJson)){
                        $this->_throwState('905'); //�����ʱ�Ԥ����������
                        return false; //tokenУ��δͨ��
                    }
                }
                unset($oap);$oap=null; //������Դ
            }
        }
        $sFile = rtrim($this->_sRootPath, '/') .'/'. str_replace('.', '/', rtrim($this->_sWorkspace, '.')) .
                 str_replace('.', '/', rtrim($this->_aInJson['package'], '.')) .
                 '/'. $this->_aInJson['class'] .'.class.php';
        if (file_exists($sFile)){ //���ļ�����
            require_once $sFile; //������
            $sRunClass = strtoupper($this->_aInJson['class']); //����
            if (class_exists($sRunClass, false)){ //�ҵ���
                $this->_aLog['step'] = 'app_err'; //���Ӧ�ñ����������Ӧ�����ֵ�����ᱻ������
                $oRun =  new $sRunClass();//�ҵ��ഴ������
                $oRun->init(); //��ʼ���ӿڲ���
                if ($oRun->getTokenCheckStatus()){ //�ж��Ƿ�Ҫ����token��֤�߼�
                    if (!$this->_checkToken()){
                        return false; //tokenУ��δͨ��
                    }elseif (!is_null($this->_oTokenSecurity)){ //token��ȫ�������
                        $oRun->setTokenContent($this->_oTokenSecurity->pullContent()); //ע��token�б���ĻỰ��������
                    }
                }
                //���Ӧ�ò㲻��Ҫд��־����ֱ�������־�ӿڶ���
                ($oRun->getDoNotWirteLog()) && $this->_ifLog = null;
                if ($oRun->isDead()){ //�ӿ��Ƿ�ϳ�
                    $this->_throwState('940'); //�ӿ��Ѿ��ϳ�
                }else{ //�ӿڿ���������
                    if ($oRun->isDefenseXXS()){ //�Ƿ���XXS��������
                        $this->_aInJson = self::strip_xss_gpc($this->_aInJson);//��ֹ��վ����
                    }
                    $oRun->run($this->_aInJson);
                    $this->_aResultData = $oRun->getResult(); //ȡ�����к�ķ���ֵ
                    unset($oRun); $oRun = null;
                    return true;
                }
            }else{ //δ�ҵ��ࣨ���ļ���������ȷ��
                $this->_throwState('915'); //δ�ҵ�ִ����
            }
        }else{
            $this->_throwState('915'); //δ�ҵ�ִ���ļ�
        }
        return false;
    }
    /**
     * ���token���ʰ�ȫ
     * @return boolean true:ͨ��token��֤ | false:δͨ��token��֤
     */
    private function _checkToken(){
        if (!is_null($this->_oTokenSecurity)){
            if (!isset($this->_aInJson['token']) || strlen($this->_aInJson['token']) !== 32){
                $this->_throwState('950'); //ȱ�����Ʋ���(��״̬����ͨ��bindTokenSecurityCheckObject()����ע���)
            }
            $sRet = $this->_oTokenSecurity->checkToken($this->_aInJson['token'], $this->_aInJson['package'], $this->_aInJson['class']);
            if (true !== $sRet){
                $this->_throwState($sRet); //token��ȫ��֤ʧ��
                return false;
            }else{
                return true; //ͨ����ȫУ��
            }
        }else{ //δ��token��ȫУ���࣬���԰�ȫУ��
            return true;
        }
    }
    /**
     * �׳�״ֵ̬��������ֹ����
     * @param string $sCode ״̬��
     * @return void
     */
    private function _throwState($sCode){
        $this->_aResultData['status'] = array(
            'code'=>$sCode,
            'msg'=>self::$aResultStateList[$sCode],
            'runtime'=>sprintf('%.4f', (microtime(true) - $this->_iStartTime) * 1000)
        );
    }
    /**
     * �������
     * <li>���������ú󽫻�������ֹ���񣬲��׳�json�����</li>
     * <li>�����json����ΪUTF-8</li>
     */
    private function _output(){
        if (!isset($this->_aResultData['result'])){
            $this->_aResultData['result'] = array();
        }
		ob_end_clean(); //����ڴ�֮ǰ������������
		$this->_aResultData = self::convert_encoding(self::LOCAL_CHARSET, 'UTF-8', $this->_aResultData);
		if (!is_null($this->_ifIoPretreatment)){ ////��������л���json�ַ���ǰ��Ԥ����
		    $this->_ifIoPretreatment->filterOutport($this->_aResultData);
		}
		if (isset($this->_aResultData['status']['runtime'])){ //�������յ�ִ��ʱ��
		    $this->_aResultData['status']['runtime'] = sprintf('%.4f', (microtime(true) - $this->_iStartTime) * 1000);
		}
		$sOutData = self::json_encode($this->_aResultData, empty($this->_aResultData['result'])); //�洢��Ҫ���͵����ݰ�

        echo $sOutData, "\n"; //����Ľ�����ǣ�UTF-8

		if(!is_null($this->_ifLog)){ //��¼��־
		    $this->_aLog['out'] = $sOutData;
		    $this->_aLog['status_code'] = (isset($this->_aResultData['status']['code']) ? $this->_aResultData['status']['code'] : '');
		    $this->_aLog['step'] = 'reply';
		}
    }
    /**
     * ��ȡ��ǰworkspace����Ŀ¼
     * @return string
     */
    public function getWorkspace(){
        return rtrim($this->_sRootPath, '/') .'/'. str_replace('.', '/', rtrim($this->_sWorkspace, '.'));
    }
    /**
     *
     * ʹ���ض�function������������Ԫ��������
     * @deprecated
     * @deprecated
     * @param $array Ҫ������ַ���
     * @param $function Ҫִ�еĺ���
     * @param $apply_to_keys_also �Ƿ�ҲӦ�õ�key��
     */
    static public function array_recursive(&$array, $function, $apply_to_keys_also = false){
        static $recursive_counter = 0;

        if (++$recursive_counter > 1000) {
            die('possible deep recursion attack');
        }
        foreach ($array as $key=>$value){
            if (is_array($value)) {
                self::array_recursive($array[$key], $function, $apply_to_keys_also);
            } else {
                $array[$key] = $function($value);
            }
            if ($apply_to_keys_also && is_string($key)) {
                $new_key = $function($key);
                if ($new_key != $key) {
                    $array[$new_key] = $array[$key];
                    unset($array[$key]);
                }
            }
        }
        $recursive_counter--;
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
    static function json_encode(& $aData, $bObjectType=false){
        list($a, $b, $c) = explode('.', PHP_VERSION); //ȡ���汾��
        if (intval($a) >= 5 && intval($b) >= 4){
            if ($bObjectType){ //���������ת��Ϊ������
                return json_encode($aData, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT); //������ȫ���ַ���
            }else{
                return json_encode($aData, JSON_UNESCAPED_UNICODE); //������ȫ���ַ���
            }
        }else{
            if ($bObjectType){ //���������ת��Ϊ������
                return json_encode($aData, JSON_FORCE_OBJECT);
            }else{
                return json_encode($aData);
            }
        }
    }
    /**
     * ���ؿͻ��˵��ַ���
     * @return false | string: charset_name
     */
    static function getClientCharset(){
        if (isset($_SERVER['CONTENT_TYPE']) && !empty($_SERVER['CONTENT_TYPE'])){
            $sContentType = strtolower($_SERVER['CONTENT_TYPE']);
            $s = substr($sContentType, strpos($sContentType, 'charset=')+8);
            return (empty($s) ? false : $s);
        }else{
            return false;
        }
    }
    /**
     * ����û�����ʵIP��ַ
     * @return array(x.x.x.x)
     * <li>���Ϊip���������и�λ</li>
     */
    static public function real_ip(){
        static $aRealIp = NULL;
        $realip = null;
        if ($aRealIp !== NULL){
            return $aRealIp;
        }
        if (isset($_SERVER)){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                /* ȡX-Forwarded-For�е�һ����unknown����ЧIP�ַ��� */
                foreach ($arr AS $ip){
                    $ip = trim($ip);
                    if ($ip != 'unknown'){
                        $realip = $ip;
                        break;
                    }
                }
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])){
                $realip = $_SERVER['HTTP_CLIENT_IP'];
            }else{
                if (isset($_SERVER['REMOTE_ADDR'])){
                    $realip = $_SERVER['REMOTE_ADDR'];
                }else{
                    $realip = '0.0.0.0';
                }
            }
        }else{
            if (getenv('HTTP_X_FORWARDED_FOR')){
                $realip = getenv('HTTP_X_FORWARDED_FOR');
            }elseif (getenv('HTTP_CLIENT_IP')){
                $realip = getenv('HTTP_CLIENT_IP');
            }else{
                $realip = getenv('REMOTE_ADDR');
            }
        }
        preg_match('/[\d\.]{7,15}/', $realip, $onlineip);
        $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
        $aRealIp = explode('.', $realip);
        foreach ($aRealIp as & $aNode)
            $aNode = intval($aNode);
        unset($aNode, $realip);

        return $aRealIp;
    }
    /**
     * �ݹ鷽ʽ�ĶԱ����еĿ�վ�ű�(js/html)����
     * @static
     * @param mixed $value
     * @return mixed
     */
    static public function strip_xss_gpc($value){
        if (empty($value)){
            return $value;
        }else{
            return is_array($value) ? array_map('self::strip_xss_gpc', $value) : self::strip_xss($value);
        }
    }
    /**
     * ��ֹ��վ����(�ƻ�js�ű��ṹ)
     * <li>ͳһ������ű�/���ԵĿ�վhtml���й��ˣ����ں�̨������/���ţ����ܻ���html/��ʽ���ύ�����ﲻ�ϸ��html/��ʽ����</li>
     * <li>��վ��Σ�������п���������Σ����Ҳ�в����ֱ��Σ����</li>
     * <li>1.��ȡ cookie�����û���Ϣ</li>
     * <li>2.����ű�</li>
     * <li>3.����ҳ�����</li>
     * <li>����������ڣ����ǽ������� htmlentities��strip_tags(���������) ������</li>
     * @param string $str
     */
    static public function strip_xss($str){
        return preg_replace("#<(script|vbscript|i?frame|html|body|title|link|meta)[^>]*?>(.*?)</(script|vbscript|i?frame|html|body|title|link|meta)>#isU", "\\2",$str);
    }
}