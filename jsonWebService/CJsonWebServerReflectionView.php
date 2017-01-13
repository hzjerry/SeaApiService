<?php
/**
 * JsonWebService�ӿڷ��������ͼ
 * @author JerryLi 2015-09-04
 *
 */
class CJsonWebServerReflectionView{
    /**
     * ���������ͼ�������ļ�
     * @var string
     */
    const CONFIG_FILE_NAME = 'config.json_web_service_reflection.php';
    /**
     * �ӿ����ļ���׺
     * @var string
     */
    const CLASS_SUFFIX = '.class.php';
    /**
     * �ӿڵ�ִ�п�ʼʱ��
     * <li>��λ:΢��</li>
     * @var int
     */
    private $_iStartTime = 0;
    /**
     * �ӿڵ�ҵ���߼��ĸ�·��
     * @var string
     */
    private $_sWorkspace = '';
    /**
     * ��ܵ���Ը�Ŀ¼
     * @var string
     */
    private $_sFramePath = '';
    /**
     * �����ַ���
     * <li>Ĭ��ͨ��JsonWebService::LOCAL_CHARSET ��ȡ</li>
     * @see JsonWebService::LOCAL_CHARSET
     * @var string
     */
    private $_sLocalCharset = null;
    /**
     * ���ӿڷ��ʰ�ȫ��Կ
     * @var array
     */
    private $_aPackageSecurityPubKey = null;
    /**
     * ��Կ������ǩ����
     * @var array
     */
    private $_sPubKey = null;
    /**
     * JsonWebServiceClient�ͻ���ͨ��ʵ������
     * @var CJsonWebServiceClient
     */
    private $_oJwsClient = null;
    /**
     * ����CJsonWebServiceClient�������ļ��б�
     * @var array
     */
    private $_aClientCfg = null;
    /**
     * JsonWebServiceClient�������ļ�·��
     * @var string
     */
    private $_sJwsClientCfgPath = null;
    /**
     * ģ���ļ���Url���ʸ������·����
     * @var string
     */
    private $_sTemplateUrlRoot = null;
    /**
     * ��Ȩ��Ϣ
     * @var string
     */
    private $_sCopyRight = null;
    /**
     * ҳ��ͷ����Ϣ
     * @var string
     */
    private $_sBannerHead = null;

    /**
     * ���캯��
     * @param string $sFramePath ����ļ��ĸ�
     * <li>��������·��</li>
     * @param string $sWorkspacePath �ӿڵĹ����߼���Ŀ¼λ��
     * <li>��ʹ�þ���·��,�磺 d:/website/api/worgroup/</li>
     * @param string $sReflectionTemplateUrlPath ����ģ���url�������·��
     * <li>����վ��URL����ʼ�ķ���·��</li> 
     * @param mixed $mReflectionCfg �����ܵ������ļ�
     * <li>����Ϊ�ַ�����Ϊ�����ļ��ľ�������·��</li>
     * <li>����Ϊ���飻ֱ��Ϊ���������飬���ݸ�ʽ���� CJsonWebServerReflectionView �����ļ��ĸ�ʽ</li>
     * @param string $sJwsClientCfgPath JsonWebServiceClient�������ļ�Ŀ¼
     * <li>��������·���������������ļ���</li>
     * 
     */
    public function __construct($sFramePath, $sWorkspacePath, $sReflectionTemplateUrlPath, $mReflectionCfg, $sJwsClientCfgPath){
        $this->_iStartTime = microtime(true); //��¼��ʼʱ��
        $this->_sTemplateUrlRoot = rtrim($sReflectionTemplateUrlPath, '/') .'/';
        $this->_sJwsClientCfgPath = rtrim($sJwsClientCfgPath, '/\\') .'/';
        $this->_sFramePath = rtrim($sFramePath, '/\\') .'/';
        //���ر���Ļ�����
        require_once $this->_sFramePath .'base/CJsonWebServiceLogicBase.php';
        require_once $this->_sFramePath .'base/CJsonWebServiceTokenSecurity.php';
        require_once $this->_sFramePath .'interface/IJsonWebServiceProtocol.php';
        require_once $this->_sFramePath .'JsonWebService.php'; //ȡ����״ֵ̬��
        require_once $this->_sFramePath .'CJsonWebServiceClient.php'; //ȡ����״ֵ̬��
        $this->_sLocalCharset = JsonWebService::LOCAL_CHARSET; //��ȡ�����ַ���
        
        if (file_exists($sWorkspacePath)){ //��鹤��Ŀ¼�Ƿ���Ч
            $this->_sWorkspace = rtrim($sWorkspacePath, '/\\') .'/';
        }else{
            echo __CLASS__ . ':Invalid workspace working directory.';
            exit;
        }
        if (!file_exists($this->_sJwsClientCfgPath)){ //���JsonWebServiceClient�����ļ�Ŀ¼�Ƿ���Ч
            echo __CLASS__ . ':Invalid JsonWebServiceClient config directory.';
            exit;
        }
        
        $this->_read_config($mReflectionCfg);
    }
    /**
     * ����ڰ�ȫ��֤����������
     * <li>���ʹ��ԭ����ṩ��checksum��ȫ��֤�㣬����Ҫ��CJsonWebServiceImportSecurity�������</li>
     */
    public function bindImportSecurityObject(CJsonWebServiceImportSecurity $oIS){
        if (is_null($this->_aPackageSecurityPubKey)){//ע��CJsonWebServiceImportSecurity���е�״̬��
            $sErrCode = $oIS->loadCfg(); //���������ļ�
            if (!is_null($sErrCode)){ //��������ʧ��
                echo __CLASS__ . ':Failed to load security layer configuration.';
                exit;
            }
        }
        $this->_aPackageSecurityPubKey = $oIS->getPackageSecurityPubKey();
    }
    /**
     * ���нӿڷ���
     */
    public function run(){
        $this->_routePage(); //����ҳ���·���߼�
    }
    /**
     * ��ȡ������Ϣ
     * @param mixed $mixedCfg ������Ϣ
     * <li>����Ϊ�ַ�����Ϊ�����ļ��ľ�������·��</li>
     * <li>����Ϊ���飻ֱ��Ϊ���������飬���ݸ�ʽ���� CJsonWebServerReflectionView �����ļ��ĸ�ʽ</li>
     * @return void
     */
    protected function _read_config($mixedCfg){
        //��ȡ CJsonWebServerReflectionView ϵͳ�ķ����������Ϣ
        if (is_string($mixedCfg)){ //�ļ���ʽ������������Ϣ
            if (file_exists($mixedCfg)){ //��������ļ��Ƿ����
                $aCfg = require $mixedCfg; //���������ļ�
            }else{
                echo __CLASS__ . ':Failed to load the CJsonWebServerReflectionView configuration file.';
                exit;
            }
        }elseif (is_array($mixedCfg)){ //���鷽ʽ����������Ϣ
            $aCfg = $mixedCfg;
        }else{ //���ü���ʧ��
            echo __CLASS__ . ':Invalid configuration information.';
            exit;
        }
        //����������ʽ
        if (!isset($aCfg['disabled_system'])){
            echo __CLASS__ . ':Invaild [disabled_system]  configuration key.';
            exit;
        }
        if (!isset($aCfg['white_ipv4'])){
            echo __CLASS__ . ':Invaild [white_ipv4]  configuration key.';
            exit;
        }
        if (!isset($aCfg['client_config'])){
            echo __CLASS__ . ':Invaild [client_config]  configuration key.';
            exit;
        }else{
            $this->_aClientCfg = $aCfg['client_config']; //��ȡ�ͻ��������ļ�
        }
        if (!isset($aCfg['copyright'])){
            echo __CLASS__ . ':Invaild [copyright]  configuration key.';
            exit;
        }else{
            $this->_sCopyRight = $aCfg['copyright']; //��Ȩ��Ϣ
        }
        if (!isset($aCfg['banner_head'])){
            echo __CLASS__ . ':Invaild [banner_head]  configuration key.';
            exit;
        }else{
            $this->_sBannerHead = $aCfg['banner_head']; //Bannerͷ����
        }
        
        //��ȫ�Թ���
        if (true === $aCfg['disabled_system']){ //ϵͳ�ѱ��رգ��ܾ�����
            $this->_showMsg('�ӿڷ����ĵ�ģ�鱻�رգ�ֹͣ�������');
        }else{ //У��ip������
            $aWhiteIp = $aCfg['white_ipv4'];
            $aSelfIp = JsonWebService::real_ip();
            $bPass = false;
            foreach ($aWhiteIp as $sIP){
                if ($this->compareIPv4($aSelfIp, explode('.', $sIP))){
                    $bPass = true; //�û��������������
                    break;
                }
            }
            if (!$bPass){ //�ǰ������û�
                $this->_showMsg('��δ����Ȩ���ܾ��ṩ���� ip:'. implode('.', $aSelfIp));
            }
        }
        unset($aCfg);
    }
    /**
     * ҳ��·���߼�
     * @return void
     */
    private function _routePage(){
        $sCtl = self::_R('ctl');
        if (empty($sCtl) || 'doc' === $sCtl){ //�ӿ��ĵ��������
            $this->_showReflection();
        }elseif ('test' === $sCtl){ //���߽ӿڵ���
            $this->_apiTest();
        }elseif ('helper' === $sCtl){ //�ӿ�ʹ����
            $this->_showPage('api_helper.html');
        }else{
            $this->_showMsg("��Ч��ctl���ʲ���");
        }
    }
    /**
     * ���з�����ͼ
     * @return void
     */
    private function _showReflection(){
        $aParam = array();
        $sPkg = self::_R('p');
        $sCls = self::_R('c'); //'GET_USER_INFO'
        if (empty($sPkg)){
            $aPkg = array();
            $aParam['{@package_name}'] = '';
        }else{
            $aPkg = explode('.', $sPkg);
            $aParam['{@package_name}'] = $sPkg;
        }

        //���б���Ϣ����
        $aList = $this->_getPackageList($aPkg);
        $aPackageList = array();
        if (false !== $aList){
            foreach ($aList as $sName){
                $sPkgMemo = $this->_getPackageReadme(array_merge($aPkg, array($sName)));
                if (false !== $sPkgMemo){
                    $sTmp = mb_substr($sPkgMemo, 0, 10, $this->_sLocalCharset);
                    if (strlen($sPkgMemo) !== strlen($sTmp))
                        $sPkgMemo = $sTmp . ' ...';
                }else{
                    $sPkgMemo = 'Not set';
                }
                $aPackageList[] = array('name'=>$sName, 'memo'=>$sPkgMemo);
            }
        }else{ //������·����Ч
            $this->_showMsg('��Ч��package������');
        }
        unset($aList);

        $aParam['{@package_path}'] = $this->_encodeJson($aPkg, $this->_sLocalCharset);
        $aParam['{@package_list}'] = $this->_encodeJson($aPackageList, $this->_sLocalCharset); //��ȡ��ǰ·���µİ�·��
        $aParam['{@class_list}'] = $this->_encodeJson($this->_getClassList($aPkg), $this->_sLocalCharset); //��ȡ��ǰ��·���µ�����
        if (!empty($sCls)){
            $aClaInfo = $this->_getClassInfo($aPkg, $sCls);
            if (false === $aClaInfo){
                $this->_showMsg('�ӿ������ʧ�ܣ��ӿڶ�Ӧ�����������ڻ��ļ������ڣ����顣');
            }else{
                unset($aClaInfo['in_protocol'], $aClaInfo['out_protocol']); //ȡ�����ò���
            }
            $aParam['{@package_readme}'] = $this->_getPackageReadme($aPkg); //�ӿڵİ�˵��
            $aParam['{@class_info}'] = $this->_encodeJson($aClaInfo, $this->_sLocalCharset); //��ȡAPI�ӿ���Ϣ
            $aParam['{@class_name}'] = $sCls;
        }else{
            $aParam['{@package_readme}'] = '';
            $aParam['{@class_info}'] = 'false';
            $aParam['{@class_name}'] = '';
        }
        $this->_showPage('api_reflection.html', $aParam);
    }
    /**
     * API��������
     * <li>webҳ��Ϊutf-8��ʽ</li>
     */
    private function _apiTest(){
        $iTransmissionTime = 0; //ͨ��ʱ��
        $aTransmissionByte = array('txd'=>0, 'rxd'=>0); //�շ���������
        $sInPkg = $this->_R('p'); //advisor.test
        $sInCls = $this->_R('c'); //GET_USER_INFO
        $sClientCfg = $this->_R('f'); //local
        $sPostJson = $this->_R('inport_json'); //��Ҫ�ύ��json����
        if (empty($sInPkg) || empty($sInCls)){
            $this->_showMsg('��Ч����ڲ���');
        }

        //��ȡ��Ӧ�ӿڵĲο�����
        $aClassInfo = $this->_getClassInfo(explode('.', $sInPkg), $sInCls);
        if (false === $aClassInfo){
            $this->_showMsg('��Ч��API�ӿ���');
        }
        $aClassInfo = $aClassInfo['in_protocol']; //ȡ�����Э��
        unset($aClassInfo['package'], $aClassInfo['class']); //ɾ��·�ɲ���
        if (isset($aClassInfo['checksum'])){
            unset($aClassInfo['checksum']); //ɾ��checksum����
        }
        $sInTemplate = self::_encodeJson($aClassInfo, $this->_sLocalCharset);

        //��ʼ���ͻ��������ļ�����
        $aClientCfgList = array();
        foreach ($this->_aClientCfg as $sKey => $aVal){
            $aClientCfgList[] = array('key'=>$sKey, 'name'=>$aVal['name']);
        }

        $sRemoteData = ''; //Զ�̽ӿڻظ���ʱ��
        $sRemoteUrl = ''; //Զ�̽ӿڵ�ַ
        $dApiRunTime = 0; //API����ʱ��
        $aRunType = array(); //����״̬
        if (empty($sPostJson)){ //û��json�ύ���ݣ����ȡAPI�ӿ�������������Ϣ
            $sPostJson = self::jsonFormat($sInTemplate);
            $aRunType[] = array('type'=>'default', 'msg'=>'�ȴ��ύ�ӿ�');
        }else{
            if (!isset($this->_aClientCfg[$sClientCfg])){
                $this->_showMsg('�ͻ��������ļ�������');
            }

            $sTmp = strtr(trim($sPostJson), array("\r\n"=>'', "\t"=>''));
            $aPostJson = self::_decodeJson($sTmp, $this->_sLocalCharset);

            if (is_null($aPostJson)){
                $aRunType[] = array('type'=>'default', 'msg'=>'δ����ͨ��');
                $aRunType[] = array('type'=>'danger', 'msg'=>'�������� JSON ���л�ʧ��');
            }else{
                $sPostJson = self::jsonFormat(self::_encodeJson($aPostJson, $this->_sLocalCharset)); //�����û�����Ĳ���ֵ�淶����ʾ

                $oClient = new CJsonWebServiceClient($this->_sJwsClientCfgPath . $this->_aClientCfg[$sClientCfg]['file']); //ʵ�����ӿ�ͨ����
                $sRemoteUrl = $oClient->getRemoteUrl();

                $iTransmissionTime = microtime(true);
                $aRet = $oClient->exec($sInPkg, $sInCls, $aPostJson); //�ύ���ݵ��ӿڽ���Զ�����󣨷�������Ϊ�����ַ�����
                $iTransmissionTime = microtime(true) - $iTransmissionTime;
                if ( is_array($aRet) ){ //ͨ�ųɹ�Json�����ɹ�
                    $sRemoteData = $oClient->getHistory('receive');
                    $aTransmissionByte['txd'] = strlen($oClient->getHistory('sent')); //���㷢�͵�body��С
                    $aTransmissionByte['rxd'] = strlen($sRemoteData); //�����յ���body��С
                    $dApiRunTime = $aRet['status']['runtime'];

                    $aRunType[] = array('type'=>'success', 'msg'=>'ͨ������');
                    $aRunType[] = array('type'=>'success', 'msg'=>'�ӿڷ�������');
                    if ('9' === substr($aRet['status']['code'], 0, 1) && 3 === strlen($aRet['status']['code'])){
                        $aRunType[] = array('type'=>'warning', 'msg'=>'ϵͳ��״̬: ����');
                    }elseif ('00000' === $aRet['status']['code']){
                        $aRunType[] = array('type'=>'info', 'msg'=>'Ӧ�ü�״̬: �ɹ�');
                    }else{
                        $aRunType[] = array('type'=>'warning', 'msg'=>'Ӧ�ü�״̬: �쳣');
                    }
                }elseif(-1 === $aRet){ //ͨ�ųɹ�,json����ʧ��
                    $sRemoteData = $oClient->getHistory('receive'); //���ʧ��ʱ�����ַ���ת��
                    $aTransmissionByte['txd'] = strlen($oClient->getHistory('sent')); //���㷢�͵�body��С
                    $aTransmissionByte['rxd'] = strlen($sRemoteData); //�����յ���body��С
                    $sRunType = '�ӿڷ���״̬�쳣���޷������ӿڷ��ص�JSON��';
                    $aRunType[] = array('type'=>'success', 'msg'=>'ͨ������');
                    $aRunType[] = array('type'=>'danger', 'msg'=>'�ӿڷ�������Json����ʧ��');
                }elseif (0 === $aRet){//ͨ��ʧ��
                    $aRunType[] = array('type'=>'danger', 'msg'=>'ͨ��ʧ��');
                }
                unset($oClient, $aRet); //�ͷ����ݼ�
            }
        }

        $aParam['{@transmission_time}'] = sprintf('%.4f', $iTransmissionTime * 1000);
        $aParam['{@api_runtime}'] = $dApiRunTime;
        $aParam['{@package}'] = $sInPkg;
        $aParam['{@class}'] = $sInCls;
        $aParam['{@inport_template_json}'] = self::jsonFormat($sInTemplate);
        $aParam['{@inport_post_json}'] = $sPostJson;
        $aParam['{@run_type}'] = self::_encodeJson($aRunType, $this->_sLocalCharset);
        $aParam['{@remort_date}'] = self::jsonFormat(JsonWebService::convert_encoding('UTF-8', $this->_sLocalCharset, $sRemoteData));
        $aParam['{@client_cfg_list}'] = self::_encodeJson($aClientCfgList, $this->_sLocalCharset);
        $aParam['{@client_cfg_key}'] = $sClientCfg;
        $aParam['{@remote_url}'] = $sRemoteUrl;
        $aParam['{@real_ip}'] = implode('.', JsonWebService::real_ip());
        $aParam['{@transmission_byte}'] = json_encode($aTransmissionByte);

        $this->_showPage('api_test_main.html', $aParam);
    }
    /**
     * ����Package��ȡһ�����õ���Կ
     * @param array $aPkg
     * @return false:��δ����Կ | string: ����Կ
     */
    private function _getPackageKey($aPkg){
        if (is_null($this->_aPackageSecurityPubKey)){
            return false;
        }
        $sPkgKey = null;
        $aPSP = & $this->_aPackageSecurityPubKey; //ȡ����
        foreach ($aPkg as $sPkgName){
            if (isset($aPSP[$sPkgName])){ //�ҵ�������������
                $sPkgKey = $aPSP[$sPkgName]['_'];
                $aPSP = & $aPSP[$sPkgName]; //�ı䵱ǰ������ָ��
            }else{
                break;
            }
        }
        unset($aPSP);
        return (is_null($sPkgKey) ? false : $sPkgKey);
    }
    /**
     * ��ȡ��ǰ·���µİ�·��
     * @param array $aPkg
     * @return false | array():��ǰ���µ��Ӱ��б�
     */
    private function _getPackageList($aPkg=array()){
        $aSubPkg = array();
        if (empty($aPkg)){
            $sDir = $this->_sWorkspace;
        }else{
            $sDir = $this->_sWorkspace . implode('/', $aPkg) .'/';
        }

        if (($aDir = @scandir($sDir)) === false){
            return false;
        }else{	//ȡ��Ŀ¼�б�
            unset($aDir['.'], $aDir['..']);
            foreach ($aDir as $sSubDir){
                if ('.' == $sSubDir{0}){
                    continue; //����'.'��ͷ��Ŀ¼�����ų�svnĿ¼��
                }elseif (!is_dir($sDir . $sSubDir)){
                    continue; //������Ŀ¼
                }else{	//�ҵ���Ŀ¼ ���ɼ�¼
                    $aSubPkg[] = $sSubDir;
                }
            }
        }
        return $aSubPkg;
    }
    /**
     * ��ȡ��ǰ��·���µ�����
     * @param array $aPkg
     * @return false | array(array('name'=>'����', 'memo'=>'�౸ע'),...):��ǰ���µĽӿ����б�
     */
    private function _getClassList($aPkg=array()){
        $aClsList = array();
        if (empty($aPkg)){
            $sDir = $this->_sWorkspace;
        }else{
          $sDir = $this->_sWorkspace . implode('/', $aPkg) .'/';
        }

        if (($aDir = @scandir($sDir)) === false){
            return false;
        }else{	//ȡ��Ŀ¼�б�
            foreach ($aDir as $sNode){
                if ('.' == $sNode{0}){
                    continue; //����'.'��ͷ��Ŀ¼�����ų�svnĿ¼��
                }elseif (!is_dir($sDir . $sNode)){ //��Ŀ¼
                    if ('ApiPretreatment.php' === $sNode){
                        continue; //��������Ԥ������
                    }
                    if (strtolower(substr($sNode, -10)) === self::CLASS_SUFFIX){
                        $sCls = substr($sNode, 0, -10); //ȡ������
                        if (!class_exists($sCls)){ //��Ϊ���ع�ʱ�ż���
                            require_once ($sDir . $sNode);
                        }
                        if (!class_exists($sCls)){ //�������࣬����δ�ҵ���Ӧ�����ƶ���
                            continue; //�������������
                        }
                        $o = new $sCls();
                        if (is_a($o, 'IJsonWebServiceProtocol') && is_a($o, 'CJsonWebServiceLogicBase')){ //��Ч��API��
                            $aClsList[] = array('name'=>$sCls,  'memo'=>$o->getClassExplain());
                        }
                        unset($o);
                    }
                }
            }
        }
        return $aClsList;
    }
    /**
     * ��ȡ�ӿ������Ϣ
     * @param array $aPkg
     * @param string $sCls
     * @return array() | false:���ļ�������
     */
    private function _getClassInfo($aPkg, $sCls){
        if (empty($aPkg)){
            $sDir = $this->_sWorkspace;
        }else{
            $sDir = $this->_sWorkspace . implode('/', $aPkg) .'/';
        }

        if (true !== file_exists($sDir . $sCls . self::CLASS_SUFFIX)){
            return false;
        }elseif (file_exists($sDir . $sCls . self::CLASS_SUFFIX)){	//�������ļ�
            if (!class_exists($sCls)){ //��Ϊ���ع�ʱ�ż���
                require_once ($sDir . $sCls . self::CLASS_SUFFIX);
            }
            if (!class_exists($sCls)){ //�������࣬����δ�ҵ���Ӧ�����ƶ���
                return false; //������ɺ�δ�ҵ���
            }

            $o = new $sCls();
            $aData = array();
            if (is_a($o, 'IJsonWebServiceProtocol') && is_a($o, 'CJsonWebServiceLogicBase')){
                $aData['class_explain'] = self::toHtmlFormat($o->getClassExplain());
                $aData['attention_explain'] = self::toHtmlFormat($o->getAttentionExplain());
                //��ڲ�������
                $aTmp = $o->getInProtocol();
                $aTmp['package'] = implode('.', $aPkg);
                $aTmp['class'] = $sCls;
                if (false !== $this->_getPackageKey($aPkg)) //����package��Կ��֤
                    $aTmp['checksum'] = 'Package access signature [string | fixed:32]';
                if ($o->getTokenCheckStatus())
                    $aTmp['token'] = 'token code [long]';
                $aData['in_protocol'] = $aTmp;
                $aData['in_protocol_format'] =
                    self::toHtmlFormat(self::jsonFormat($this->_encodeJson($aTmp, $this->_sLocalCharset)));
                //���ڲ�������
                $aTmp = array('result'=>$o->getOutProtocol(),
                    'status'=>array('code'=>'status code [string | min:3 | max:5]',
                                     'msg'=>'status message [string]', 'runtime'=>'api runtime(ms) [double]')
                );
                $aData['out_protocol'] = $aTmp;
                if (empty($aTmp['result'])){ //����ֵΪ��ʱ��{}
                    $aData['out_protocol_format'] =
                        self::toHtmlFormat(
                            self::jsonFormat($this->_encodeJson($aTmp, $this->_sLocalCharset, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT) )
                        );
                }else{ //���ڷ���ֵ
                    $aData['out_protocol_format'] =
                    self::toHtmlFormat( self::jsonFormat($this->_encodeJson($aTmp, $this->_sLocalCharset) ) );
                }
                unset($aTmp);

                $aData['update_log'] = $o->getUpdateLog();
                $aData['sys_status_code'] = JsonWebService::$aResultStateList;
                $iCode = null;
                foreach (CJsonWebServiceLogicBase::$aResultStateList as $sKey => $sVal){//ע��API�ӿڻ����ж����ϵͳ��״̬��
                    $iCode = intval($sKey);
                    if ($iCode >= 900 && $iCode <= 999){ //ֻע��ϵͳ�������
                        $aData['sys_status_code'][strval($sKey)] = $sVal;
                    }
                }
                unset($iCode); $iCode = null;

                if ($o->getTokenCheckStatus()){ //����Token��ȫ��֤�ӿڵķ��ز���
                    foreach (CJsonWebServiceTokenSecurity::$aResultStateList as $sKey => $sVal){
                        $aData['sys_status_code'][strval($sKey)] = $sVal;
                    }
                }
                $aData['api_status_code'] = $o->getStatus();
                foreach ($aData['api_status_code'] as $sKey => $sVal){ //������ǰע���Ӧ��״̬��
                    unset($aData['sys_status_code'][strval($sKey)]);
                }
                if ($o->getDeadline() > 0){
                    $aData['dead_line'] = date('Y-m-d H:i:s', $o->getDeadline());
                }else{ //��������
                    $aData['dead_line'] = 'Never expires';
                }

                $aData['token_security_check'] = ( $o->getTokenCheckStatus() ? 'Y' : 'N' ); //�Ƿ���token���
                $aData['fingerprint'] = md5_file($sDir . $sCls . self::CLASS_SUFFIX); //����ָ��
                $aData['do_not_wirte_log'] = ( $o->getDoNotWirteLog() ? 'Y':'N' ); //�Ƿ�Ҫд��־
            }
            unset($o);
            return $aData;
        }
        return false;
    }
    /**
     * �ӿڵİ�˵��
     * <li>��˵���ļ�Ϊ�����ڰ�·���µ�readme.txt�ļ�����</li>
     * @param array $aPkg
     * @return false | string
     */
    private function _getPackageReadme($aPkg){
        if (!empty($aPkg)){
            $sDir = $this->_sWorkspace . implode('/', $aPkg) .'/';
            if (file_exists($sDir . 'readme.txt')){
                return file_get_contents($sDir . 'readme.txt');
            }else{
                return false;
            }
        }
        return false;
    }
    /**
     * ��ʾģ��ҳ��
     * <li>ע������ģ��ʹ��UTF-8��ʽ</li>
     * @param string $sTemplate ģ������
     * @param array $aParam ģ���key=>val�滻����
     * <li>array('{@key1}'=>'val', '{@key2}'=>'val', ...)</li>
     */
    private function _showPage($sTemplate, $aParam=array()){
        header('Content-Type:text/html; charset=utf-8');
        $sTemplateFile = $this->_sTemplateUrlRoot . $sTemplate;
        if (file_exists($sTemplateFile)){
            $sTmp = file_get_contents($sTemplateFile);
            $aParam = JsonWebService::convert_encoding($this->_sLocalCharset, 'UTF-8', $aParam);
            $aParam['{@web_root}'] = $this->_sTemplateUrlRoot;
            $aParam['{@runtime}'] = sprintf('%.4f', (microtime(true) - $this->_iStartTime) * 1000);
            $aParam['{@local_date}'] = date('Y-m-d H:i:s');
            $aParam['{@utc_date}'] = date('Y-m-d H:i:s', time() - date('Z'));
            $aParam['{@utc_timestemp}'] = time();
            $aParam['{@copyright}'] = $this->_sCopyRight;
            $aParam['{@banner_head}'] = $this->_sBannerHead;
            echo strtr($sTmp, $aParam);
        }else{
            echo 'template not find';
        }
        exit();
    }
    /**
     * json�ַ�������������
     * <li>�����������ʱ���ַ�������һ��</li>
     * @param string $sData json�ַ���
     * @param strng $sNowCharset ����ǰ���ַ���
     * @return array | null
     */
    static private function _decodeJson($sData, $sNowCharset){
        if ('utf-8' === strtolower($sNowCharset)){
            return json_decode($sData, true);
        }else{
            $sTmp = JsonWebService::convert_encoding($sNowCharset, 'UTF-8', $sData);
            if (!is_null($aRet = json_decode($sTmp, true))){
                return JsonWebService::convert_encoding('UTF-8', $sNowCharset, $aRet);
            }else{
                return null;
            }
        }
    }
    /**
     * �������л�json�ַ���
     * <li>�����������ʱ���ַ�������һ��</li>
     * @param array $aData json�ַ���
     * @param strng $sNowCharset ����ǰ���ַ���
     * @param strng $options JSON_UNESCAPED_UNICODE:����uncode��ת��
     * @return string
     */
    static private function _encodeJson($aData, $sNowCharset, $options=JSON_UNESCAPED_UNICODE){
        if ('utf-8' === strtolower($sNowCharset)){
            return json_encode($aData, $options);
        }else{
            $aTmp = JsonWebService::convert_encoding($sNowCharset, 'UTF-8', $aData);
            $sRet = json_encode($aTmp, $options);
            return JsonWebService::convert_encoding('UTF-8', $sNowCharset, $sRet);
        }
    }
    /**
     * ��ʾ��Ϣ��ʾҳ��
     * @param string $sMsg ��ʾ��Ϣ����
     */
    private function _showMsg($sMsg){
        $aParam = array();
        $aParam['{@message}'] = $sMsg;
        $this->_showPage('show_msg.html', $aParam);
    }
    /**
     * ����ҳ��post��get�ύ��key=>val����
     * <li>���Զ���UTF-8ת��Ϊ���ر���</li>
     * @param string $sKey
     * @return string | null
     */
    private function _R($sKey){
        if (isset($_REQUEST[$sKey])){
            if (!empty($_REQUEST[$sKey])){
                return JsonWebService::convert_encoding('UTF-8', $this->_sLocalCharset, $_REQUEST[$sKey]);
            }else{
                return '';
            }
        }else{
            return null;
        }
    }
    /**
     * �Ƚ�����IP�Ƿ���ͬ
     * <li>$aIp2֧��ͨ���*������ֵ����'.'�г�����</li>
     * @param array $aIp1 ip����(Դ)
     * @param array $aIp2 ip����(�Ƚ�ģ�壬֧��*ͨ���)
     * @return boolean
     */
    static public function compareIPv4($aIp1, $aIp2){
        if (count($aIp1) !== 4 || count($aIp2) !== 4){
            return false;
        }
        for($i=0; $i<4; $i++){
            if ('*' === $aIp2[$i]){
                continue; //�����Ƚ�λ
            }elseif (intval($aIp1[$i]) !== intval($aIp2[$i])){
                return false; //����IP����ͬ
            }
        }
        return true;
    }
    /**
     * Json���ݸ�ʽ��
     * @param  Mixed  $data   ����
     * @param  String $indent �����ַ���Ĭ��4���ո�
     * @return JSON
     */
    static public function jsonFormat($data, $indent=null){
        if (empty($data))
            return '';
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
    /**
     * ���ַ��������htmlת���ַ�
     * @param string $sStr
     */
    static public function toHtmlFormat($sStr){
        return strtr($sStr, array("\n"=>'<br/>', ' '=>'&nbsp;', "\t"=>'&nbsp;&nbsp;&nbsp;&nbsp;', '"'=>'&#34;'));
    }
}