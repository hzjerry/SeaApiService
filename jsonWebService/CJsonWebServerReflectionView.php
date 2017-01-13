<?php
/**
 * JsonWebService接口反射管理视图
 * @author JerryLi 2015-09-04
 *
 */
class CJsonWebServerReflectionView{
    /**
     * 反射管理视图的配置文件
     * @var string
     */
    const CONFIG_FILE_NAME = 'config.json_web_service_reflection.php';
    /**
     * 接口类文件后缀
     * @var string
     */
    const CLASS_SUFFIX = '.class.php';
    /**
     * 接口的执行开始时间
     * <li>单位:微妙</li>
     * @var int
     */
    private $_iStartTime = 0;
    /**
     * 接口的业务逻辑的根路径
     * @var string
     */
    private $_sWorkspace = '';
    /**
     * 框架的相对根目录
     * @var string
     */
    private $_sFramePath = '';
    /**
     * 本地字符集
     * <li>默认通过JsonWebService::LOCAL_CHARSET 获取</li>
     * @see JsonWebService::LOCAL_CHARSET
     * @var string
     */
    private $_sLocalCharset = null;
    /**
     * 包接口访问安全密钥
     * @var array
     */
    private $_aPackageSecurityPubKey = null;
    /**
     * 公钥（用于签名）
     * @var array
     */
    private $_sPubKey = null;
    /**
     * JsonWebServiceClient客户端通信实例对象
     * @var CJsonWebServiceClient
     */
    private $_oJwsClient = null;
    /**
     * 保存CJsonWebServiceClient的配置文件列表
     * @var array
     */
    private $_aClientCfg = null;
    /**
     * JsonWebServiceClient的配置文件路径
     * @var string
     */
    private $_sJwsClientCfgPath = null;
    /**
     * 模板文件的Url访问根（相对路径）
     * @var string
     */
    private $_sTemplateUrlRoot = null;
    /**
     * 版权信息
     * @var string
     */
    private $_sCopyRight = null;
    /**
     * 页面头部信息
     * @var string
     */
    private $_sBannerHead = null;

    /**
     * 构造函数
     * @param string $sFramePath 框架文件的根
     * <li>绝对物理路径</li>
     * @param string $sWorkspacePath 接口的工作逻辑根目录位置
     * <li>请使用绝对路径,如： d:/website/api/worgroup/</li>
     * @param string $sReflectionTemplateUrlPath 反射模板的url访问相对路径
     * <li>从网站的URL根开始的访问路径</li> 
     * @param mixed $mReflectionCfg 反射框架的配置文件
     * <li>类型为字符串；为配置文件的绝对物理路径</li>
     * <li>类型为数组；直接为配置项数组，数据格式参照 CJsonWebServerReflectionView 配置文件的格式</li>
     * @param string $sJwsClientCfgPath JsonWebServiceClient的配置文件目录
     * <li>绝对物理路径，不包含配置文件名</li>
     * 
     */
    public function __construct($sFramePath, $sWorkspacePath, $sReflectionTemplateUrlPath, $mReflectionCfg, $sJwsClientCfgPath){
        $this->_iStartTime = microtime(true); //记录起始时间
        $this->_sTemplateUrlRoot = rtrim($sReflectionTemplateUrlPath, '/') .'/';
        $this->_sJwsClientCfgPath = rtrim($sJwsClientCfgPath, '/\\') .'/';
        $this->_sFramePath = rtrim($sFramePath, '/\\') .'/';
        //加载必须的基础类
        require_once $this->_sFramePath .'base/CJsonWebServiceLogicBase.php';
        require_once $this->_sFramePath .'base/CJsonWebServiceTokenSecurity.php';
        require_once $this->_sFramePath .'interface/IJsonWebServiceProtocol.php';
        require_once $this->_sFramePath .'JsonWebService.php'; //取返回状态值用
        require_once $this->_sFramePath .'CJsonWebServiceClient.php'; //取返回状态值用
        $this->_sLocalCharset = JsonWebService::LOCAL_CHARSET; //获取本地字符集
        
        if (file_exists($sWorkspacePath)){ //检查工作目录是否有效
            $this->_sWorkspace = rtrim($sWorkspacePath, '/\\') .'/';
        }else{
            echo __CLASS__ . ':Invalid workspace working directory.';
            exit;
        }
        if (!file_exists($this->_sJwsClientCfgPath)){ //检查JsonWebServiceClient配置文件目录是否有效
            echo __CLASS__ . ':Invalid JsonWebServiceClient config directory.';
            exit;
        }
        
        $this->_read_config($mReflectionCfg);
    }
    /**
     * 绑定入口安全验证参数的配置
     * <li>如果使用原框架提供的checksum安全验证层，则需要绑定CJsonWebServiceImportSecurity对象进来</li>
     */
    public function bindImportSecurityObject(CJsonWebServiceImportSecurity $oIS){
        if (is_null($this->_aPackageSecurityPubKey)){//注入CJsonWebServiceImportSecurity类中的状态码
            $sErrCode = $oIS->loadCfg(); //载入配置文件
            if (!is_null($sErrCode)){ //载入配置失败
                echo __CLASS__ . ':Failed to load security layer configuration.';
                exit;
            }
        }
        $this->_aPackageSecurityPubKey = $oIS->getPackageSecurityPubKey();
    }
    /**
     * 运行接口反射
     */
    public function run(){
        $this->_routePage(); //进入页面的路由逻辑
    }
    /**
     * 读取配置信息
     * @param mixed $mixedCfg 配置信息
     * <li>类型为字符串；为配置文件的绝对物理路径</li>
     * <li>类型为数组；直接为配置项数组，数据格式参照 CJsonWebServerReflectionView 配置文件的格式</li>
     * @return void
     */
    protected function _read_config($mixedCfg){
        //读取 CJsonWebServerReflectionView 系统的服务端配置信息
        if (is_string($mixedCfg)){ //文件方式将爱在配置信息
            if (file_exists($mixedCfg)){ //检查配置文件是否存在
                $aCfg = require $mixedCfg; //载入配置文件
            }else{
                echo __CLASS__ . ':Failed to load the CJsonWebServerReflectionView configuration file.';
                exit;
            }
        }elseif (is_array($mixedCfg)){ //数组方式加载配置信息
            $aCfg = $mixedCfg;
        }else{ //配置加载失败
            echo __CLASS__ . ':Invalid configuration information.';
            exit;
        }
        //检查配置项格式
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
            $this->_aClientCfg = $aCfg['client_config']; //获取客户端配置文件
        }
        if (!isset($aCfg['copyright'])){
            echo __CLASS__ . ':Invaild [copyright]  configuration key.';
            exit;
        }else{
            $this->_sCopyRight = $aCfg['copyright']; //版权信息
        }
        if (!isset($aCfg['banner_head'])){
            echo __CLASS__ . ':Invaild [banner_head]  configuration key.';
            exit;
        }else{
            $this->_sBannerHead = $aCfg['banner_head']; //Banner头名称
        }
        
        //安全性过滤
        if (true === $aCfg['disabled_system']){ //系统已被关闭，拒绝访问
            $this->_showMsg('接口反射文档模块被关闭，停止对外服务。');
        }else{ //校验ip白名单
            $aWhiteIp = $aCfg['white_ipv4'];
            $aSelfIp = JsonWebService::real_ip();
            $bPass = false;
            foreach ($aWhiteIp as $sIP){
                if ($this->compareIPv4($aSelfIp, explode('.', $sIP))){
                    $bPass = true; //用户存在与白名单内
                    break;
                }
            }
            if (!$bPass){ //非白名单用户
                $this->_showMsg('您未被授权，拒绝提供服务。 ip:'. implode('.', $aSelfIp));
            }
        }
        unset($aCfg);
    }
    /**
     * 页面路由逻辑
     * @return void
     */
    private function _routePage(){
        $sCtl = self::_R('ctl');
        if (empty($sCtl) || 'doc' === $sCtl){ //接口文档反射管理
            $this->_showReflection();
        }elseif ('test' === $sCtl){ //在线接口调试
            $this->_apiTest();
        }elseif ('helper' === $sCtl){ //接口使用向导
            $this->_showPage('api_helper.html');
        }else{
            $this->_showMsg("无效的ctl访问参数");
        }
    }
    /**
     * 运行反射视图
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

        //包列表信息整理
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
        }else{ //包名称路径无效
            $this->_showMsg('无效的package包名。');
        }
        unset($aList);

        $aParam['{@package_path}'] = $this->_encodeJson($aPkg, $this->_sLocalCharset);
        $aParam['{@package_list}'] = $this->_encodeJson($aPackageList, $this->_sLocalCharset); //获取当前路径下的包路径
        $aParam['{@class_list}'] = $this->_encodeJson($this->_getClassList($aPkg), $this->_sLocalCharset); //获取当前包路径下的类名
        if (!empty($sCls)){
            $aClaInfo = $this->_getClassInfo($aPkg, $sCls);
            if (false === $aClaInfo){
                $this->_showMsg('接口类加载失败！接口对应的类名不存在或文件不存在，请检查。');
            }else{
                unset($aClaInfo['in_protocol'], $aClaInfo['out_protocol']); //取出无用参数
            }
            $aParam['{@package_readme}'] = $this->_getPackageReadme($aPkg); //接口的包说明
            $aParam['{@class_info}'] = $this->_encodeJson($aClaInfo, $this->_sLocalCharset); //获取API接口信息
            $aParam['{@class_name}'] = $sCls;
        }else{
            $aParam['{@package_readme}'] = '';
            $aParam['{@class_info}'] = 'false';
            $aParam['{@class_name}'] = '';
        }
        $this->_showPage('api_reflection.html', $aParam);
    }
    /**
     * API测试用例
     * <li>web页面为utf-8格式</li>
     */
    private function _apiTest(){
        $iTransmissionTime = 0; //通信时间
        $aTransmissionByte = array('txd'=>0, 'rxd'=>0); //收发的数据量
        $sInPkg = $this->_R('p'); //advisor.test
        $sInCls = $this->_R('c'); //GET_USER_INFO
        $sClientCfg = $this->_R('f'); //local
        $sPostJson = $this->_R('inport_json'); //需要提交的json数据
        if (empty($sInPkg) || empty($sInCls)){
            $this->_showMsg('无效的入口参数');
        }

        //读取对应接口的参考参数
        $aClassInfo = $this->_getClassInfo(explode('.', $sInPkg), $sInCls);
        if (false === $aClassInfo){
            $this->_showMsg('无效的API接口类');
        }
        $aClassInfo = $aClassInfo['in_protocol']; //取出入口协议
        unset($aClassInfo['package'], $aClassInfo['class']); //删除路由参数
        if (isset($aClassInfo['checksum'])){
            unset($aClassInfo['checksum']); //删除checksum参数
        }
        $sInTemplate = self::_encodeJson($aClassInfo, $this->_sLocalCharset);

        //初始化客户端配置文件数组
        $aClientCfgList = array();
        foreach ($this->_aClientCfg as $sKey => $aVal){
            $aClientCfgList[] = array('key'=>$sKey, 'name'=>$aVal['name']);
        }

        $sRemoteData = ''; //远程接口回复的时间
        $sRemoteUrl = ''; //远程接口地址
        $dApiRunTime = 0; //API运行时间
        $aRunType = array(); //运行状态
        if (empty($sPostJson)){ //没有json提交数据，则读取API接口类的入口配置信息
            $sPostJson = self::jsonFormat($sInTemplate);
            $aRunType[] = array('type'=>'default', 'msg'=>'等待提交接口');
        }else{
            if (!isset($this->_aClientCfg[$sClientCfg])){
                $this->_showMsg('客户端配置文件不存在');
            }

            $sTmp = strtr(trim($sPostJson), array("\r\n"=>'', "\t"=>''));
            $aPostJson = self::_decodeJson($sTmp, $this->_sLocalCharset);

            if (is_null($aPostJson)){
                $aRunType[] = array('type'=>'default', 'msg'=>'未进行通信');
                $aRunType[] = array('type'=>'danger', 'msg'=>'待发数据 JSON 序列化失败');
            }else{
                $sPostJson = self::jsonFormat(self::_encodeJson($aPostJson, $this->_sLocalCharset)); //整理用户输入的参数值规范化显示

                $oClient = new CJsonWebServiceClient($this->_sJwsClientCfgPath . $this->_aClientCfg[$sClientCfg]['file']); //实例化接口通信类
                $sRemoteUrl = $oClient->getRemoteUrl();

                $iTransmissionTime = microtime(true);
                $aRet = $oClient->exec($sInPkg, $sInCls, $aPostJson); //提交数据到接口进行远程请求（返回数组为本地字符集）
                $iTransmissionTime = microtime(true) - $iTransmissionTime;
                if ( is_array($aRet) ){ //通信成功Json解析成功
                    $sRemoteData = $oClient->getHistory('receive');
                    $aTransmissionByte['txd'] = strlen($oClient->getHistory('sent')); //计算发送的body大小
                    $aTransmissionByte['rxd'] = strlen($sRemoteData); //计算收到的body大小
                    $dApiRunTime = $aRet['status']['runtime'];

                    $aRunType[] = array('type'=>'success', 'msg'=>'通信正常');
                    $aRunType[] = array('type'=>'success', 'msg'=>'接口返回正常');
                    if ('9' === substr($aRet['status']['code'], 0, 1) && 3 === strlen($aRet['status']['code'])){
                        $aRunType[] = array('type'=>'warning', 'msg'=>'系统级状态: 错误');
                    }elseif ('00000' === $aRet['status']['code']){
                        $aRunType[] = array('type'=>'info', 'msg'=>'应用级状态: 成功');
                    }else{
                        $aRunType[] = array('type'=>'warning', 'msg'=>'应用级状态: 异常');
                    }
                }elseif(-1 === $aRet){ //通信成功,json解析失败
                    $sRemoteData = $oClient->getHistory('receive'); //输出失败时不做字符集转换
                    $aTransmissionByte['txd'] = strlen($oClient->getHistory('sent')); //计算发送的body大小
                    $aTransmissionByte['rxd'] = strlen($sRemoteData); //计算收到的body大小
                    $sRunType = '接口返回状态异常，无法解析接口返回的JSON包';
                    $aRunType[] = array('type'=>'success', 'msg'=>'通信正常');
                    $aRunType[] = array('type'=>'danger', 'msg'=>'接口返回数据Json解析失败');
                }elseif (0 === $aRet){//通信失败
                    $aRunType[] = array('type'=>'danger', 'msg'=>'通信失败');
                }
                unset($oClient, $aRet); //释放数据集
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
     * 根据Package获取一个可用的密钥
     * @param array $aPkg
     * @return false:包未配密钥 | string: 包密钥
     */
    private function _getPackageKey($aPkg){
        if (is_null($this->_aPackageSecurityPubKey)){
            return false;
        }
        $sPkgKey = null;
        $aPSP = & $this->_aPackageSecurityPubKey; //取引用
        foreach ($aPkg as $sPkgName){
            if (isset($aPSP[$sPkgName])){ //找到包密码配置项
                $sPkgKey = $aPSP[$sPkgName]['_'];
                $aPSP = & $aPSP[$sPkgName]; //改变当前根引用指针
            }else{
                break;
            }
        }
        unset($aPSP);
        return (is_null($sPkgKey) ? false : $sPkgKey);
    }
    /**
     * 获取当前路径下的包路径
     * @param array $aPkg
     * @return false | array():当前包下的子包列表
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
        }else{	//取得目录列表
            unset($aDir['.'], $aDir['..']);
            foreach ($aDir as $sSubDir){
                if ('.' == $sSubDir{0}){
                    continue; //跳过'.'开头的目录名（排除svn目录）
                }elseif (!is_dir($sDir . $sSubDir)){
                    continue; //跳过非目录
                }else{	//找到子目录 生成记录
                    $aSubPkg[] = $sSubDir;
                }
            }
        }
        return $aSubPkg;
    }
    /**
     * 获取当前包路径下的类名
     * @param array $aPkg
     * @return false | array(array('name'=>'类名', 'memo'=>'类备注'),...):当前包下的接口类列表
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
        }else{	//取得目录列表
            foreach ($aDir as $sNode){
                if ('.' == $sNode{0}){
                    continue; //跳过'.'开头的目录名（排除svn目录）
                }elseif (!is_dir($sDir . $sNode)){ //非目录
                    if ('ApiPretreatment.php' === $sNode){
                        continue; //跳过访问预处理类
                    }
                    if (strtolower(substr($sNode, -10)) === self::CLASS_SUFFIX){
                        $sCls = substr($sNode, 0, -10); //取出类名
                        if (!class_exists($sCls)){ //类为加载过时才加载
                            require_once ($sDir . $sNode);
                        }
                        if (!class_exists($sCls)){ //加载了类，但是未找到对应类名称定义
                            continue; //跳过有问题的类
                        }
                        $o = new $sCls();
                        if (is_a($o, 'IJsonWebServiceProtocol') && is_a($o, 'CJsonWebServiceLogicBase')){ //有效的API类
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
     * 获取接口类的信息
     * @param array $aPkg
     * @param string $sCls
     * @return array() | false:类文件不存在
     */
    private function _getClassInfo($aPkg, $sCls){
        if (empty($aPkg)){
            $sDir = $this->_sWorkspace;
        }else{
            $sDir = $this->_sWorkspace . implode('/', $aPkg) .'/';
        }

        if (true !== file_exists($sDir . $sCls . self::CLASS_SUFFIX)){
            return false;
        }elseif (file_exists($sDir . $sCls . self::CLASS_SUFFIX)){	//加载类文件
            if (!class_exists($sCls)){ //类为加载过时才加载
                require_once ($sDir . $sCls . self::CLASS_SUFFIX);
            }
            if (!class_exists($sCls)){ //加载了类，但是未找到对应类名称定义
                return false; //加载完成后，未找到类
            }

            $o = new $sCls();
            $aData = array();
            if (is_a($o, 'IJsonWebServiceProtocol') && is_a($o, 'CJsonWebServiceLogicBase')){
                $aData['class_explain'] = self::toHtmlFormat($o->getClassExplain());
                $aData['attention_explain'] = self::toHtmlFormat($o->getAttentionExplain());
                //入口参数处理
                $aTmp = $o->getInProtocol();
                $aTmp['package'] = implode('.', $aPkg);
                $aTmp['class'] = $sCls;
                if (false !== $this->_getPackageKey($aPkg)) //存在package密钥验证
                    $aTmp['checksum'] = 'Package access signature [string | fixed:32]';
                if ($o->getTokenCheckStatus())
                    $aTmp['token'] = 'token code [long]';
                $aData['in_protocol'] = $aTmp;
                $aData['in_protocol_format'] =
                    self::toHtmlFormat(self::jsonFormat($this->_encodeJson($aTmp, $this->_sLocalCharset)));
                //出口参数处理
                $aTmp = array('result'=>$o->getOutProtocol(),
                    'status'=>array('code'=>'status code [string | min:3 | max:5]',
                                     'msg'=>'status message [string]', 'runtime'=>'api runtime(ms) [double]')
                );
                $aData['out_protocol'] = $aTmp;
                if (empty($aTmp['result'])){ //返回值为空时用{}
                    $aData['out_protocol_format'] =
                        self::toHtmlFormat(
                            self::jsonFormat($this->_encodeJson($aTmp, $this->_sLocalCharset, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT) )
                        );
                }else{ //存在返回值
                    $aData['out_protocol_format'] =
                    self::toHtmlFormat( self::jsonFormat($this->_encodeJson($aTmp, $this->_sLocalCharset) ) );
                }
                unset($aTmp);

                $aData['update_log'] = $o->getUpdateLog();
                $aData['sys_status_code'] = JsonWebService::$aResultStateList;
                $iCode = null;
                foreach (CJsonWebServiceLogicBase::$aResultStateList as $sKey => $sVal){//注入API接口基类中定义的系统级状态码
                    $iCode = intval($sKey);
                    if ($iCode >= 900 && $iCode <= 999){ //只注入系统级错误号
                        $aData['sys_status_code'][strval($sKey)] = $sVal;
                    }
                }
                unset($iCode); $iCode = null;

                if ($o->getTokenCheckStatus()){ //加入Token安全验证接口的返回参数
                    foreach (CJsonWebServiceTokenSecurity::$aResultStateList as $sKey => $sVal){
                        $aData['sys_status_code'][strval($sKey)] = $sVal;
                    }
                }
                $aData['api_status_code'] = $o->getStatus();
                foreach ($aData['api_status_code'] as $sKey => $sVal){ //剥离提前注入的应用状态码
                    unset($aData['sys_status_code'][strval($sKey)]);
                }
                if ($o->getDeadline() > 0){
                    $aData['dead_line'] = date('Y-m-d H:i:s', $o->getDeadline());
                }else{ //永不过期
                    $aData['dead_line'] = 'Never expires';
                }

                $aData['token_security_check'] = ( $o->getTokenCheckStatus() ? 'Y' : 'N' ); //是否开启token检查
                $aData['fingerprint'] = md5_file($sDir . $sCls . self::CLASS_SUFFIX); //生成指纹
                $aData['do_not_wirte_log'] = ( $o->getDoNotWirteLog() ? 'Y':'N' ); //是否不要写日志
            }
            unset($o);
            return $aData;
        }
        return false;
    }
    /**
     * 接口的包说明
     * <li>包说明文件为保存在包路径下的readme.txt文件内容</li>
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
     * 显示模板页面
     * <li>注意所有模板使用UTF-8格式</li>
     * @param string $sTemplate 模板名称
     * @param array $aParam 模板的key=>val替换参数
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
     * json字符串解析成数组
     * <li>输入与输出的时的字符集保持一致</li>
     * @param string $sData json字符串
     * @param strng $sNowCharset 进入前的字符集
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
     * 数组序列化json字符串
     * <li>输入与输出的时的字符集保持一致</li>
     * @param array $aData json字符串
     * @param strng $sNowCharset 进入前的字符集
     * @param strng $options JSON_UNESCAPED_UNICODE:不对uncode做转码
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
     * 显示信息提示页面
     * @param string $sMsg 提示信息内容
     */
    private function _showMsg($sMsg){
        $aParam = array();
        $aParam['{@message}'] = $sMsg;
        $this->_showPage('show_msg.html', $aParam);
    }
    /**
     * 返回页面post或get提交的key=>val数据
     * <li>会自动将UTF-8转换为本地编码</li>
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
     * 比较两个IP是否相同
     * <li>$aIp2支持通配符*，输入值按照'.'切成数组</li>
     * @param array $aIp1 ip数组(源)
     * @param array $aIp2 ip数组(比较模板，支持*通配符)
     * @return boolean
     */
    static public function compareIPv4($aIp1, $aIp2){
        if (count($aIp1) !== 4 || count($aIp2) !== 4){
            return false;
        }
        for($i=0; $i<4; $i++){
            if ('*' === $aIp2[$i]){
                continue; //跳过比较位
            }elseif (intval($aIp1[$i]) !== intval($aIp2[$i])){
                return false; //两个IP不相同
            }
        }
        return true;
    }
    /**
     * Json数据格式化
     * @param  Mixed  $data   数据
     * @param  String $indent 缩进字符，默认4个空格
     * @return JSON
     */
    static public function jsonFormat($data, $indent=null){
        if (empty($data))
            return '';
        // 缩进处理
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
     * 将字符串输出成html转义字符
     * @param string $sStr
     */
    static public function toHtmlFormat($sStr){
        return strtr($sStr, array("\n"=>'<br/>', ' '=>'&nbsp;', "\t"=>'&nbsp;&nbsp;&nbsp;&nbsp;', '"'=>'&#34;'));
    }
}