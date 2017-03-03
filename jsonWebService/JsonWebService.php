<?php
/**
 * JsonWebService�ӿڷ����ִ����
 * @author JerryLi 2015-09-04
 * @final
 */
final class JsonWebService{
    /**
     * �����ַ���(���ݱ��ؿ��������л�)
     * @var string
     */
    const LOCAL_CHARSET = 'GBK';
    /**
     * ��վ�������Ŀ¼
     * @var string
     */
    private $_sRootPath = '';
    /**
     * ��ܵľ��Ը�·��
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
     * ����jsonP�Ļص�������
     * @var string
     */
    private $_sJsonP_Func = null;
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
     * JsonWebService�ӿڵ��������ȫ��
     * @var CJsonWebServiceImportSecurity
     */
    private $_oImportSecurity = null;
    /**
     * Token��ȫУ�����
     * @var CJsonWebServiceTokenSecurity
     */
    private $_oTokenSecurity = null;
    /**
     * ��ֹ�ط�У���ҵ���߼�
     * @var IJsonWebServiceCloseReplay
     */
    private $_ifCloseReplay = null;

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
        '998'=>'Invalid Workspace directory.(�ӿڵ�workspaceʵ������Ŀ¼������)',
        '997'=>'Application layer processing timeout.(Ӧ�ò㴦��ʱ)',
        '901'=>'Received protocol packets can not be resolved.(�յ���Э����޷�����)',
        //902~914 ���밲ȫ��֤��ռ�� CJsonWebServiceImportSecurity
        '915'=>'Refused to replay the request.(�ܾ��ط�����)',
        '917'=>'package and class node values do not exist.(package��class�ڵ�ֵ������)',
        '918'=>'API interface class not found.(api�ӿڷ�����δ�ҵ�)',
        '919'=>'API interface file not found.(api�ӿ��ļ�δ�ҵ�)',
        //920 ~ 929 �� CJsonWebServiceLogicBase ��ռ��
        '930'=>'Invalid characters in package.(package�д�����Ч�ַ�)',
        '940'=>'Interface has invalid.(�˽ӿ��Ѿ��ϳ���ֹͣ����)',
        //950��959 Tokenռ�õ�״̬��
    );
    /**
     * ���캯��
     * @param string $sFramePath ����ļ�����Ը�·��
     * <li>��ʹ�ñ��������Ŀ¼�ľ��Ը�·��,�磺d:/website/jsonWebService/</li>
     * @param string $sWorkspacePath �ӿڵĹ����߼���Ŀ¼λ��
     * <li>��ʹ�þ���·��,�磺 d:/website/api/worgroup/</li>
     */
    public function __construct($sFramePath, $sWorkspacePath){
        $this->_iStartTime = microtime(true); //��¼��ʼʱ��
        $this->_sFramePath = $sFramePath;
        //���ر���Ļ�����
        require_once rtrim($sFramePath, '/') .'/base/CJsonWebServiceLogicBase.php';
        require_once rtrim($sFramePath, '/') .'/base/CJsonWebServiceImportSecurity.php';
        require_once rtrim($sFramePath, '/') .'/interface/IJsonWebServiceProtocol.php';
        //ע��CJsonWebServiceLogicBase���е�״̬��
        foreach (CJsonWebServiceLogicBase::$aResultStateList as $sKey => $sVal){
            self::$aResultStateList[strval($sKey)] = $sVal;
        }
        if (file_exists($sWorkspacePath)){ //��鹤��Ŀ¼�Ƿ���Ч
            $this->_sWorkspace = rtrim($sWorkspacePath, '/\\') .'/';
        }else{ //�ӿ�ʵ���ļ�Ŀ¼������
            $this->_throwState('998'); //�ӿڵ�workspaceʵ������Ŀ¼������
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
     * ��JsonWebService�ӿڵ��������ȫ��
     * @param CJsonWebServiceImportSecurity $oIS
     */
    public function bindImportSecurityObject(CJsonWebServiceImportSecurity $oIS){
        if (is_null($this->_oImportSecurity)){//ע��CJsonWebServiceImportSecurity���е�״̬��
            foreach (CJsonWebServiceImportSecurity::$aResultStateList as $sKey => $sVal){
                self::$aResultStateList[strval($sKey)] = $sVal;
            }
            $oIS->loadCfg(); //��������
        }
        $this->_oImportSecurity = $oIS;
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
     * �󶨽�ֹ�ط��߼��ӿڶ���
     * @param IJsonWebServiceCloseReplay $ifCRO
     * @param void
     */
    public function bindCloseReplayObject(IJsonWebServiceCloseReplay $ifCRO){
        $this->_ifCloseReplay = $ifCRO;
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
        ob_start(); //�����������
        if ($this->getInput()){//��ȡ������������
            $this->_route(); //��ʼ����·�ɷ���
        }
    }
    /**
     * ��ȡ���������
     * @return boolean
     */
    protected function getInput(){
        if (isset($_GET['b']) && !empty($_GET['b'])){ //���bodyͨ��get��ʽ�ύ����ȡ��
            $this->_sInData = $_GET['b'];
            if (isset($_GET['jsonp']) && !empty($_GET['jsonp'])){ //����jsonp�Ļص���ʽ
                $this->_sJsonP_Func = $_GET['jsonp'];
            }
        }else{
            $this->_sInData = file_get_contents("php://input");//ȡpost����
        }
        if(!is_null($this->_ifLog)){ //��¼��־
            $this->_aLog['in'] = $this->_sInData;
            $this->_aLog['step'] = 'receive';
        }
        if (empty($this->_sInData)){
            $this->_throwState('999'); //ȱ��������ݰ�
            return false;
        }
        /* ��ȫ��֤֮ - ���ǩ����֤*/
        if (!is_null($this->_oImportSecurity)){ //��Ҫ������ڰ�ȫ��֤
            $sErrorCode = $this->_oImportSecurity->checkSignSecurity($this->_sInData); //������ǩ����Ч��
            if (!is_null($sErrorCode)){ //��ڼ��δͨ��
                $this->_throwState($sErrorCode);
                return false;
            }
            //ȡ��httpͷ���ı�Ҫ����
            $sSign = trim($_SERVER['HTTP_SIGNATURE']);
            $this->_aLog['sign'] = $sSign; //����ͻ���������bodyǩ��
        }
        /* ��ȫ��֤֮ - �����ط�*/
        if (!is_null($this->_ifCloseReplay)){ //�ж��Ƿ����طŽ�ֹ�߼�
            if ($this->_ifCloseReplay->checkReplay($sSign)){ //����Ƿ�����ط�����
                $this->_throwState('915'); //�ܾ��ط�����
                return false;
            }
        }

        //���յ������ݰ�ת��Ϊ����
        $this->_aInJson = json_decode($this->_sInData, true);
        if (is_null($this->_aInJson)){ //����jsonʧ��
            $this->_throwState('901'); //�յ���Э����޷�����
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
            $this->_throwState('917'); //ȱ��package��class������
            return false;
        }
        if(!is_null($this->_ifLog)){ //��¼��־
            $this->_aLog['pkg'] = $this->_aInJson['package'];
            $this->_aLog['cls'] = $this->_aInJson['class'];
            $this->_aLog['step'] = 'resolve';
        }
        //��֤package��ֵ�Ƿ�Ƿ�
        if(!preg_match('/^[a-z][\w\.?]*[a-z0-9]$/', $this->_aInJson['package'])){
            $this->_throwState('930'); //package��Ч�ַ�
            return false;
        }
        //package�ӿڷ��ʰ�ȫ��֤
        if (!is_null($this->_oImportSecurity)){
            $sErrCode = $this->_oImportSecurity->checkPackageSecurity($this->_aInJson); //������Ȩ��֤ checksum
            if (!is_null($sErrCode)){
                $this->_throwState($sErrCode); //�׳� ������Ȩ��֤ checksum  ������Ϣ
                return false;
            }
        }
        //�ӿڷ���Ԥ������
        $sFile = $this->_sWorkspace .
                 substr($this->_aInJson['package'], 0, strpos($this->_aInJson['package'], '.')) . '/ApiPretreatment.php';
        if (file_exists($sFile)){ //Ԥ�����ļ�����
            require_once rtrim($this->_sFramePath, '/') .'/interface/IJsonWebServiceVisitPretreatment.php';//ע��ӿ�����
            require_once $sFile; //���ذ��ķ���Ԥ������
            $aRunClass = 'ApiPretreatment'; //����Ԥ����������
            if (class_exists($aRunClass, false)){ //�ҵ�����Ԥ������
                $oap = new $aRunClass();
                if (is_a($oap, 'IJsonWebServiceVisitPretreatment')){
                    $aRet = $oap->toDo($this->_aInJson);
                    if (false !== $aRet){ //���ִ�в�����״̬
                        self::$aResultStateList[$aRet['code']] = $aRet['msg'];
                        $this->_throwState($aRet['code']); //�����ʱ�Ԥ����������
                        return false; //tokenУ��δͨ��
                    }
                }
                unset($oap);$oap=null; //������Դ
            }
        }
        $sFile = $this->_sWorkspace .
                 str_replace('.', '/', rtrim($this->_aInJson['package'], '.')) .'/'. $this->_aInJson['class'] .'.class.php';
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
                    return false;
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
                $this->_throwState('918'); //δ�ҵ�ִ����
                return false;
            }
        }else{
            $this->_throwState('919'); //δ�ҵ�ִ���ļ�
            return false;
        }
    }
    /**
     * ���token���ʰ�ȫ
     * @return boolean true:ͨ��token��֤ | false:δͨ��token��֤
     */
    private function _checkToken(){
        if (!is_null($this->_oTokenSecurity)){
            if (!isset($this->_aInJson['token'])){
                $this->_throwState('950'); //ȱ�����Ʋ���(��״̬����ͨ��bindTokenSecurityCheckObject()����ע���)
                return false;
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

		ob_start();
		if (!is_null($this->_sJsonP_Func)){ //���������Ҫ��װ��jsonp��ʽ
		    header('Content-Type: text/javascript; charset=UTF-8'); //Ĭ���ַ���
		    header('Access-Control-Allow-Origin: *'); //��վ����
		    header('Content-Length: '. strlen($sOutData) + strlen($this->_sJsonP_Func) + 2); //�������ݰ�����
		    echo $this->_sJsonP_Func, '(', $sOutData ,')';
		}else{ //��׼��json���ݰ�ͷ��ʽ
		    header('Content-Type: application/json; charset=UTF-8'); //Ĭ���ַ���
		    if( !headers_sent()  && extension_loaded('zlib')  && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && 
		        false !== stripos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')
		      ){ //���ֿͻ���֧��gzip��������ѹ������
		        $sGzipData = gzencode($sOutData,9); //ѹ���������
		        header ('Content-Encoding: gzip'); //ʹ��gzipѹ�����
		        header ('Vary: Accept-Encoding'); //���߿ͻ��˵�ǰ�����Ѿ��ɹ�����ѹ��
		        header ('Content-Length: '.strlen ($sGzipData)); //�������ݰ�����
		        echo $sGzipData; //���ѹ������
		        unset($sGzipData); //�ͷ�����
		    }else{
		        header ('Content-Length: '.strlen ($sOutData)); //�������ݰ�����
		        echo $sOutData;
		    }
		}
		ob_end_flush();
		if(!is_null($this->_ifLog)){ //��¼��־
		    $this->_aLog['out'] = $sOutData;
		    $this->_aLog['status_code'] = (isset($this->_aResultData['status']['code']) ? $this->_aResultData['status']['code'] : '');
		    $this->_aLog['step'] = 'reply';
		}
    }
    /**
     * ��ȡworkspace�Ĺ���Ŀ¼
     * @return string
     */
    public function getWorkspace(){
        return $this->_sWorkspace;
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
     * @param string|array $mixd ��ת���ı�����������ַ�����
     * @return string|array ���ת����Ľ��
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
        if (intval($a) >=6 || (intval($a) >= 5 && intval($b) >= 4)){
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
    /**
     * ��ȡ�ͻ��˵�HTTP_USER_AGENT��Ϣ����
     * <li>���밴��HTTPЭ��淶�ͳ�HTTP_USER_AGENT�����ҷ���JSW�Ĺ淶���ܽ�����HTTP_USER_AGENT�еĲ���</li>
     * <li>HTTP_USER_AGENT�淶: [appname]/[ver] ([system info])</li>
     * <li>[appname]:Ӧ������</li>
     * <li>[ver]:�汾�ţ���ʽ��x.x.x ��xΪ������</li>
     * <li>[system info]��ϵͳ��Ϣ�������iphone����android�������[iphone|android]����</li>
     * @return array('ver'=>array(x,x,x)�汾������, 'appname'=>'Ӧ������', 'client'=>'�ͻ�������[iphone|android|webserver|other]')
     */
    static public function resloveUserAgentInfo(){
        $aData = array('ver'=>'', 'appname'=>'', 'client'=>'other');
        $sHeaderVer = strtolower($_SERVER['HTTP_USER_AGENT']);
        $aData['appname'] = trim(substr($sHeaderVer, 0, strpos($sHeaderVer, '/')));//ȡ��AppName
        $sVer = trim(substr($sHeaderVer, strpos($sHeaderVer, '/')+1, strpos($sHeaderVer, '(') - strpos($sHeaderVer, '/') -1 ));//ȡ��APP�汾��
        foreach (array('iphone', 'android', 'apache', 'nginx') as $sSysName){
            if (false !== stripos($sHeaderVer, $sSysName)){
                if (in_array($sSysName, array('apache', 'nginx'))){
                    $aData['client'] = 'webserver';
                }else{
                    $aData['client'] = $sSysName;
                }
                break;
            }
        }

        $aVerCode = explode('.', $sVer); //�汾�����黯
        if (count($aVerCode) === 3){
            foreach ($aVerCode as & $sNode)
                $sNode = intval($sNode);
            unset($sNode);
            foreach ($aVerCode as & $sNode)
                $sNode = intval($sNode);
            unset($sNode);
            $aData['ver'] = $aVerCode;
        }else{
            $aData['ver'] = array(0,0,0);
        }
        return $aData;
    }
    /**
     * �Ƚ������汾�ŵĴ�С
     * <li>�汾�Ÿ�ʽ x.x.x Ϊ��������</li>
     * @param array $aSrcVer ԭ�汾��
     * <li>array(x,x,x)</li>
     * @param array $aCur �Ƚϵĵ�ǰ�汾��
     * <li>array(x,x,x)</li>
     * @return int -1:��ǰ���ԭ�汾�� | 0:��ǰ�汾��ԭ�汾��ͬ | 1:��ǰ�汾��ԭ�汾��
     */
    static public function compareVerCode($aSrcVer, $aCur){
        foreach ($aSrcVer as & $sNode)
            $sNode = intval($sNode);
        unset($sNode);
        foreach ($aCur as & $sNode)
            $sNode = intval($sNode);
        unset($sNode);
        for ($i=0; $i<3; $i++){
            if ($aSrcVer[$i] > $aCur[$i]){ //��ǰ�汾��ԭ�汾��
                return 1;
            }elseif ($aSrcVer[$i] < $aCur[$i]){ //��ǰ���ԭ�汾��
                return -1;
            }
        }
        return 0; //��ǰ�汾��ԭ�汾��ͬ
    }
}