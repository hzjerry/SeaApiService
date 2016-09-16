<?php
/**
 * JsonCliService 接口服务的cli外壳
 * @author JerryLi 2016-08-11
 * @see
 * <li>调用方式使用cli方式访问，传入的json入口参数必须使用base64编码后，作为字符串参数传入</li>
 * <li>"C:/Program Files/php/5_4_9/php.exe" E:/PHPRoot/test/SeaApiService/test_cli.php eyJwYWNrYWdlIjoiYWR2aXNvci50ZXN0IiwiY2xhc3MiOiJHRVRfVVNFUl9JTkZPIiwibmFtZSI6IjExIiwiYWdlIjoiMTEifQ==</li>
 * @final
 */
final class JsonCliService{
    /**
     * Service端的配置文件名称
     * @var string
     */
    const CONFIG_FILE_NAME='config.json_web_service.php';
    /**
     * 本地字符集(根据本地开发环境切换)
     * @var string
     */
    const LOCAL_CHARSET = 'GBK';
    /**
     * 客户端的时间戳
     * @var double
     */
    private $_iClientUtcTimestemp = 0;
    /**
     * 网站的物理根目录
     * @var string
     */
    private $_sRootPath = '';
    /**
     * 框架的相对根路径
     * @var string
     */
    private $_sFramePath = '';
    /**
     * 接口的业务逻辑的目录
     * @var string
     */
    private $_sWorkspace = '';
    /**
     * 保存输入的post数据
     * @var string
     */
    private $_sInData = null;
    /**
     * 输入参数解析成数组对象
     * @var array
     */
    private $_aInJson = null;
    /**
     * 返回结果集的数组
     * @var array
     */
    private $_aResultData = array();
    /**
     * 系统日志
     * @var array
     * <li>array('in'=>'入口内容', 'out'=>'出口内容', 'pkg'=>'包路径信息', 'cls'=>'接口名信息', 'status_code'=>'状态码', 'step'=>'阶段',
     * 'runtime'=>'运行时间ms', 'sign'=>'body签名')</li>
     * <li>step: [receive:接收到数据 | resolve:Json解析成功数据 | reply:接口正常回复 | app_err:应用错误]</li>
     */
    private $_aLog = array('in'=>null, 'out'=>null, 'pkg'=>'', 'cls'=>'', 'status_code'=>'', 'step'=>'', 'sign'=>'');
    /**
     * 接口的执行开始时间
     * <li>单位:微妙</li>
     * @var int
     */
    private $_iStartTime = 0;
    /**
     * 日志接口对象
     * @var IJsonWebServiceLog
     */
    private $_ifLog = null;
    /**
     * Token安全校验对象
     * @var CJsonWebServiceTokenSecurity
     */
    private $_oTokenSecurity = null;

    /**
     * 接口的输入输出流过滤器
     * @var IJsonWebServiceIoPretreatment
     */
    private $_ifIoPretreatment = null;
    /**
     * API服务定义的状态值列表
     * <li>结构array(array('code'='状态值代码', 'msg'=>'文字解释'),...)</li>
     * <li>code约定: 纯数字字符串, 系统级错误:000~999; 应用级错误:00001~99999; 应用级正常值:00000</li>
     * @var array
     */
    static public $aResultStateList = array(
        '999'=>'There is no post and get data.(不存在post或get数据)',
        '901'=>'Received protocol packets can not be resolved.(收到的协议包无法解析)',
        '901'=>'Lack of necessary HEAD parameters.(缺少必要的HTTP HEAD参数)',
        '902'=>'Invalid parameter HTTP HEAD RANDOM.(HTTP HEAD RANDOM参数无效)',
        '903'=>'UTC Time stamp expired.(时间戳过期)',
        '904'=>'Refused to replay the request.(拒绝重放请求)',
        '905'=>'Request be Pretreatment to blocked.(请求被预处理阻断)',
        '910'=>'Configuration file read failed.(配置文件读取失败)',
        '911'=>'Data signature is incorrect.(数据签名不正确)',
        '912'=>'package and class node values do not exist.(package或class节点值不存在)',
        '913'=>'checksum value attribute node does not exist.(checksum节点的value属性不存在)',
        '914'=>'The checksum validation did not pass.(checksum校验未通过)',
        '915'=>'API interface class not found.(api接口服务类未找到)',
        //916 被 CJsonWebServiceLogicBase 类占用 //'API interface services no output result set.(api接口服务无输出结果集)'
        '917'=>'checksum check failure.(checksum校验失败)',
        //920 被 CJsonWebServiceLogicBase 类占用 //'The returned value of the unregistered state.(未注册的返回状态值)'
        '930'=>'Invalid characters in package.(package中存在无效字符)',
        '940'=>'Interface has invalid.(此接口已经废除，停止服务)',
        //950～959 Token占用的状态码
    );
    /**
     * 构造函数
     * @param string $sRootPath 网站绝对根目录
     * @param string $sFramePath 框架文件的相对根路径
     */
    public function __construct($sRootPath, $sFramePath){
        ob_end_flush(); //刷出缓冲区并关闭输出缓存
        $this->_iStartTime = microtime(true); //记录起始时间
        $this->_sRootPath = $sRootPath;
        $this->_sFramePath = $sFramePath;
        //加载必须的基础类
        require_once rtrim($sRootPath, '/') .'/'. rtrim($sFramePath, '/') .'/base/CJsonWebServiceLogicBase.php';
        require_once rtrim($sRootPath, '/') .'/'. rtrim($sFramePath, '/') .'/interface/IJsonWebServiceProtocol.php';
        //注入CJsonWebServiceLogicBase类中的状态码
        foreach (CJsonWebServiceLogicBase::$aResultStateList as $sKey => $sVal){
            self::$aResultStateList[strval($sKey)] = $sVal;
        }
        //载入配置文件信息
        if (!$this->_read_config(rtrim($sRootPath, '/') .'/'. rtrim($sFramePath, '/') .'/config/'. self::CONFIG_FILE_NAME)){
            //配置文件加载失败
            $this->_output(); //输出返回值
        }
    }
    /**
     * 析构
     * <li>析构时，如果存在日志操作对象，则执行日志写入操作</li>
     */
    public function __destruct(){
        if (!is_null($this->_ifLog)){ //存在日志对象时输出日志
            $this->_aLog['runtime'] = intval((microtime(true) - $this->_iStartTime) * 1000 + 0.5);
            $this->_ifLog->createLog($this->_aLog);
        }
    }
    /**
     * 绑定日志记录接口
     * @param IJsonWebServiceLog $iflog
     * @param void
     */
    public function bindLogObject(IJsonWebServiceLog $iflog){
        $this->_ifLog = $iflog;
    }
    /**
     * 绑定Token安全验证检查对象
     * @param CJsonWebServiceTokenSecurity $oTSC
     * @param void
     */
    public function bindTokenSecurityCheckObject(CJsonWebServiceTokenSecurity $oTSC){
        if (is_null($this->_oTokenSecurity)){//注入CJsonWebServiceTokenSecurity类中的状态码
            foreach (CJsonWebServiceTokenSecurity::$aResultStateList as $sKey => $sVal){
                self::$aResultStateList[strval($sKey)] = $sVal;
            }
        }
        $this->_oTokenSecurity = $oTSC;
    }
    /**
     * 绑定输入输出流过滤器接口对象
     * @param IJsonWebServiceLog $iflog
     * @param void
     */
    public function bindIoPretreatmentObject(IJsonWebServiceIoPretreatment $ifTCO){
        $this->_ifIoPretreatment = $ifTCO;
    }
    /**
     * 运行接口解析服务
     * @return void
     */
    public function run(){
        $this->_execute(); //开始执行解析服务
        $this->_output(); //输出返回值
    }
    /**
     * 执行解析服务
     * <li>解析服务的入口方法</li>
     * @return void
     */
    private function _execute(){
        if ($this->getInput()){//读取输入请求数据
            $this->_route(); //开始进行路由分析
        }
    }
    /**
     * 读取配置信息
     * @return void
     */
    protected function _read_config($sFilePath){
        if (file_exists($sFilePath)){ //检查配置文件是否存在
            $aCfg = require $sFilePath; //载入配置文件
            $this->_sWorkspace = $aCfg['workgroup'];
            unset($aCfg);
            return true;
        }else{
            $this->_throwState('910'); //配置文件读取失败
            return false;
        }
    }
    /**
     * 读取输入的数据
     * @return boolean
     */
    protected function getInput(){
        global $argv;
        if (isset($argv[1]) && !empty($argv[1])){
            $this->_sInData = base64_decode($argv[1]); //提取参数
        }
        if (is_null($this->_sInData) || false === $this->_sInData){ //输入参数解释失败或者没有输入参数
            $this->_throwState('999');
            return false;
        }
        if(!is_null($this->_ifLog)){ //记录日志
            $this->_aLog['in'] = $this->_sInData;
            $this->_aLog['step'] = 'receive';
        }
        //将收到的数据包转换为数组
        $this->_aInJson = json_decode($this->_sInData, true);
        if (is_null($this->_aInJson)){ //解析json失败
            $this->_throwState('901');
            return false;
        }else{ //json解析成功
            if (!is_null($this->_ifIoPretreatment)){ //输入包解析成数组后的字符串预处理
                $this->_ifIoPretreatment->filterInport($this->_aInJson);
            }
            $this->_aInJson = self::convert_encoding('UTF-8', self::LOCAL_CHARSET, $this->_aInJson); //将字符转换成本地字符集
            return true;
        }
    }
    /**
     * 路由
     * @return boolean
     */
    protected function _route(){
        if (!isset($this->_aInJson['package']) || empty($this->_aInJson['package']) ||
            !isset($this->_aInJson['class']) || empty($this->_aInJson['class'])){
            $this->_throwState('912'); //缺少package与class数据项
            return false;
        }
        if(!is_null($this->_ifLog)){ //记录日志
            $this->_aLog['pkg'] = $this->_aInJson['package'];
            $this->_aLog['cls'] = $this->_aInJson['class'];
            $this->_aLog['step'] = 'resolve';
        }
        //验证package的值是否非法
        if(!preg_match('/^[a-zA-Z][\w\.?]*[a-zA-Z0-9]$/', $this->_aInJson['package'])){ 
            $this->_throwState('930'); //package无效字符
            return false;
        }
        //接口访问预处理类
        $sFile = rtrim($this->_sRootPath, '/') .'/'. str_replace('.', '/', rtrim($this->_sWorkspace, '.')) .
                 substr($this->_aInJson['package'], 0, strpos($this->_aInJson['package'], '.')) . '/ApiPretreatment.php';
        if (file_exists($sFile)){ //预处理文件存在
            require_once rtrim($this->_sRootPath, '/') .'/'. rtrim($this->_sFramePath, '/') .'/interface/IJsonWebServiceVisitPretreatment.php';//注入接口申明
            require_once $sFile; //加载包的访问预处理类
            $aRunClass = 'ApiPretreatment'; //访问预处理类名称
            if (class_exists($aRunClass, false)){ //找到访问预处理类
                $oap = new $aRunClass();
                if (is_a($oap, 'IJsonWebServiceVisitPretreatment')){
                    if ($oap->toDo($this->_aInJson)){
                        $this->_throwState('905'); //包访问被预处理程序阻断
                        return false; //token校验未通过
                    }
                }
                unset($oap);$oap=null; //回收资源
            }
        }
        $sFile = rtrim($this->_sRootPath, '/') .'/'. str_replace('.', '/', rtrim($this->_sWorkspace, '.')) .
                 str_replace('.', '/', rtrim($this->_aInJson['package'], '.')) .
                 '/'. $this->_aInJson['class'] .'.class.php';
        if (file_exists($sFile)){ //类文件存在
            require_once $sFile; //加载类
            $sRunClass = strtoupper($this->_aInJson['class']); //类名
            if (class_exists($sRunClass, false)){ //找到类
                $this->_aLog['step'] = 'app_err'; //如果应用报错或者无响应，这个值将不会被被覆盖
                $oRun =  new $sRunClass();//找到类创建对象
                $oRun->init(); //初始化接口参数
                if ($oRun->getTokenCheckStatus()){ //判断是否要开启token验证逻辑
                    if (!$this->_checkToken()){
                        return false; //token校验未通过
                    }elseif (!is_null($this->_oTokenSecurity)){ //token安全对象存在
                        $oRun->setTokenContent($this->_oTokenSecurity->pullContent()); //注入token中保存的会话保持内容
                    }
                }
                //如果应用层不需要写日志，则直接清除日志接口对象
                ($oRun->getDoNotWirteLog()) && $this->_ifLog = null;
                if ($oRun->isDead()){ //接口是否废除
                    $this->_throwState('940'); //接口已经废除
                }else{ //接口可正常服务
                    if ($oRun->isDefenseXXS()){ //是否开启XXS攻击过滤
                        $this->_aInJson = self::strip_xss_gpc($this->_aInJson);//阻止跨站攻击
                    }
                    $oRun->run($this->_aInJson);
                    $this->_aResultData = $oRun->getResult(); //取出运行后的返回值
                    unset($oRun); $oRun = null;
                    return true;
                }
            }else{ //未找到类（类文件命名不正确）
                $this->_throwState('915'); //未找到执行类
            }
        }else{
            $this->_throwState('915'); //未找到执行文件
        }
        return false;
    }
    /**
     * 检查token访问安全
     * @return boolean true:通过token验证 | false:未通过token验证
     */
    private function _checkToken(){
        if (!is_null($this->_oTokenSecurity)){
            if (!isset($this->_aInJson['token']) || strlen($this->_aInJson['token']) !== 32){
                $this->_throwState('950'); //缺少令牌参数(此状态码是通过bindTokenSecurityCheckObject()函数注入的)
            }
            $sRet = $this->_oTokenSecurity->checkToken($this->_aInJson['token'], $this->_aInJson['package'], $this->_aInJson['class']);
            if (true !== $sRet){
                $this->_throwState($sRet); //token安全验证失败
                return false;
            }else{
                return true; //通过安全校验
            }
        }else{ //未绑定token安全校验类，忽略安全校验
            return true;
        }
    }
    /**
     * 抛出状态值，并且终止服务
     * @param string $sCode 状态码
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
     * 输出数据
     * <li>本函数调用后将会立即终止服务，并抛出json结果集</li>
     * <li>输出的json编码为UTF-8</li>
     */
    private function _output(){
        if (!isset($this->_aResultData['result'])){
            $this->_aResultData['result'] = array();
        }
		ob_end_clean(); //清除在此之前输出缓存的数据
		$this->_aResultData = self::convert_encoding(self::LOCAL_CHARSET, 'UTF-8', $this->_aResultData);
		if (!is_null($this->_ifIoPretreatment)){ ////输出包序列化成json字符串前的预处理
		    $this->_ifIoPretreatment->filterOutport($this->_aResultData);
		}
		if (isset($this->_aResultData['status']['runtime'])){ //加入最终的执行时间
		    $this->_aResultData['status']['runtime'] = sprintf('%.4f', (microtime(true) - $this->_iStartTime) * 1000);
		}
		$sOutData = self::json_encode($this->_aResultData, empty($this->_aResultData['result'])); //存储需要发送的数据包

        echo $sOutData, "\n"; //输出的结果集是：UTF-8

		if(!is_null($this->_ifLog)){ //记录日志
		    $this->_aLog['out'] = $sOutData;
		    $this->_aLog['status_code'] = (isset($this->_aResultData['status']['code']) ? $this->_aResultData['status']['code'] : '');
		    $this->_aLog['step'] = 'reply';
		}
    }
    /**
     * 获取当前workspace工作目录
     * @return string
     */
    public function getWorkspace(){
        return rtrim($this->_sRootPath, '/') .'/'. str_replace('.', '/', rtrim($this->_sWorkspace, '.'));
    }
    /**
     *
     * 使用特定function对数组中所有元素做处理
     * @deprecated
     * @deprecated
     * @param $array 要处理的字符串
     * @param $function 要执行的函数
     * @param $apply_to_keys_also 是否也应用到key上
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
     * 对变量内容的进行字符编码转换
     * @param string $sInCharset 转换前的字符集
     * @param string $sOutCharset 转换后的字符集
     * @param string | array $mixd 待转换的变量（数组或字符串）
     * @return string | array 完成转换后的结果
     */
    static public function convert_encoding($sInCharset, $sOutCharset, & $mixd) {
        if ($sInCharset === $sOutCharset) //字符集相同时不转换
            return $mixd;

        if (is_array($mixd)) {
            $tmp = array();
            foreach ($mixd as $key => $val) {
                $tmp[$key] = self::convert_encoding($sInCharset, $sOutCharset, $val);
            }
            return $tmp;
        } else { //字符集相同时不转换
            return mb_convert_encoding($mixd, $sOutCharset, $sInCharset);
        }
    }
    /**
     * json_encode根据版本号判断是否不编码全角字符集
     * @param array $aData UTF-8字符集的数组对象内容
     * @return string json字符串
     */
    static function json_encode(& $aData, $bObjectType=false){
        list($a, $b, $c) = explode('.', PHP_VERSION); //取出版本号
        if (intval($a) >= 5 && intval($b) >= 4){
            if ($bObjectType){ //加入空数组转换为对象处理
                return json_encode($aData, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT); //不编码全角字符集
            }else{
                return json_encode($aData, JSON_UNESCAPED_UNICODE); //不编码全角字符集
            }
        }else{
            if ($bObjectType){ //加入空数组转换为对象处理
                return json_encode($aData, JSON_FORCE_OBJECT);
            }else{
                return json_encode($aData);
            }
        }
    }
    /**
     * 返回客户端的字符集
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
     * 获得用户的真实IP地址
     * @return array(x.x.x.x)
     * <li>输出为ip整形数组切割位</li>
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
                /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
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
     * 递归方式的对变量中的跨站脚本(js/html)过滤
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
     * 阻止跨站攻击(破坏js脚本结构)
     * <li>统一对输入脚本/明显的跨站html进行过滤，由于后台发帮助/新闻，可能会有html/样式等提交，这里不严格对html/样式过滤</li>
     * <li>跨站的危害：（有可能是入库后危害，也有不入库直接危害）</li>
     * <li>1.获取 cookie，盗用户信息</li>
     * <li>2.恶意脚本</li>
     * <li>3.引起页面混乱</li>
     * <li>部分输入入口，还是建议大家用 htmlentities、strip_tags(如意见建议) 做处理。</li>
     * @param string $str
     */
    static public function strip_xss($str){
        return preg_replace("#<(script|vbscript|i?frame|html|body|title|link|meta)[^>]*?>(.*?)</(script|vbscript|i?frame|html|body|title|link|meta)>#isU", "\\2",$str);
    }
}