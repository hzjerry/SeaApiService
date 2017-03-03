<?php
/**
 * JsonWebService接口服务的执行类
 * @author JerryLi 2015-09-04
 * @final
 */
final class JsonWebService{
    /**
     * 本地字符集(根据本地开发环境切换)
     * @var string
     */
    const LOCAL_CHARSET = 'GBK';
    /**
     * 网站的物理根目录
     * @var string
     */
    private $_sRootPath = '';
    /**
     * 框架的绝对根路径
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
     * 保存jsonP的回调函数名
     * @var string
     */
    private $_sJsonP_Func = null;
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
     * JsonWebService接口的入口请求安全层
     * @var CJsonWebServiceImportSecurity
     */
    private $_oImportSecurity = null;
    /**
     * Token安全校验对象
     * @var CJsonWebServiceTokenSecurity
     */
    private $_oTokenSecurity = null;
    /**
     * 截止回放校验的业务逻辑
     * @var IJsonWebServiceCloseReplay
     */
    private $_ifCloseReplay = null;

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
        '998'=>'Invalid Workspace directory.(接口的workspace实例工作目录不存在)',
        '997'=>'Application layer processing timeout.(应用层处理超时)',
        '901'=>'Received protocol packets can not be resolved.(收到的协议包无法解析)',
        //902~914 输入安全验证类占用 CJsonWebServiceImportSecurity
        '915'=>'Refused to replay the request.(拒绝重放请求)',
        '917'=>'package and class node values do not exist.(package或class节点值不存在)',
        '918'=>'API interface class not found.(api接口服务类未找到)',
        '919'=>'API interface file not found.(api接口文件未找到)',
        //920 ~ 929 被 CJsonWebServiceLogicBase 类占用
        '930'=>'Invalid characters in package.(package中存在无效字符)',
        '940'=>'Interface has invalid.(此接口已经废除，停止服务)',
        //950～959 Token占用的状态码
    );
    /**
     * 构造函数
     * @param string $sFramePath 框架文件的相对根路径
     * <li>请使用本框架所在目录的绝对根路径,如：d:/website/jsonWebService/</li>
     * @param string $sWorkspacePath 接口的工作逻辑根目录位置
     * <li>请使用绝对路径,如： d:/website/api/worgroup/</li>
     */
    public function __construct($sFramePath, $sWorkspacePath){
        $this->_iStartTime = microtime(true); //记录起始时间
        $this->_sFramePath = $sFramePath;
        //加载必须的基础类
        require_once rtrim($sFramePath, '/') .'/base/CJsonWebServiceLogicBase.php';
        require_once rtrim($sFramePath, '/') .'/base/CJsonWebServiceImportSecurity.php';
        require_once rtrim($sFramePath, '/') .'/interface/IJsonWebServiceProtocol.php';
        //注入CJsonWebServiceLogicBase类中的状态码
        foreach (CJsonWebServiceLogicBase::$aResultStateList as $sKey => $sVal){
            self::$aResultStateList[strval($sKey)] = $sVal;
        }
        if (file_exists($sWorkspacePath)){ //检查工作目录是否有效
            $this->_sWorkspace = rtrim($sWorkspacePath, '/\\') .'/';
        }else{ //接口实例文件目录不存在
            $this->_throwState('998'); //接口的workspace实例工作目录不存在
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
     * 绑定JsonWebService接口的入口请求安全层
     * @param CJsonWebServiceImportSecurity $oIS
     */
    public function bindImportSecurityObject(CJsonWebServiceImportSecurity $oIS){
        if (is_null($this->_oImportSecurity)){//注入CJsonWebServiceImportSecurity类中的状态码
            foreach (CJsonWebServiceImportSecurity::$aResultStateList as $sKey => $sVal){
                self::$aResultStateList[strval($sKey)] = $sVal;
            }
            $oIS->loadCfg(); //载入配置
        }
        $this->_oImportSecurity = $oIS;
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
     * 绑定截止回放逻辑接口对象
     * @param IJsonWebServiceCloseReplay $ifCRO
     * @param void
     */
    public function bindCloseReplayObject(IJsonWebServiceCloseReplay $ifCRO){
        $this->_ifCloseReplay = $ifCRO;
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
        ob_start(); //开启输出缓存
        if ($this->getInput()){//读取输入请求数据
            $this->_route(); //开始进行路由分析
        }
    }
    /**
     * 读取输入的数据
     * @return boolean
     */
    protected function getInput(){
        if (isset($_GET['b']) && !empty($_GET['b'])){ //如果body通过get方式提交，则取出
            $this->_sInData = $_GET['b'];
            if (isset($_GET['jsonp']) && !empty($_GET['jsonp'])){ //存在jsonp的回调方式
                $this->_sJsonP_Func = $_GET['jsonp'];
            }
        }else{
            $this->_sInData = file_get_contents("php://input");//取post数据
        }
        if(!is_null($this->_ifLog)){ //记录日志
            $this->_aLog['in'] = $this->_sInData;
            $this->_aLog['step'] = 'receive';
        }
        if (empty($this->_sInData)){
            $this->_throwState('999'); //缺少入口数据包
            return false;
        }
        /* 安全验证之 - 入口签名验证*/
        if (!is_null($this->_oImportSecurity)){ //需要进行入口安全验证
            $sErrorCode = $this->_oImportSecurity->checkSignSecurity($this->_sInData); //检查入口签名有效性
            if (!is_null($sErrorCode)){ //入口检查未通过
                $this->_throwState($sErrorCode);
                return false;
            }
            //取出http头部的必要参数
            $sSign = trim($_SERVER['HTTP_SIGNATURE']);
            $this->_aLog['sign'] = $sSign; //保存客户端送来的body签名
        }
        /* 安全验证之 - 截至重放*/
        if (!is_null($this->_ifCloseReplay)){ //判断是否开启重放截止逻辑
            if ($this->_ifCloseReplay->checkReplay($sSign)){ //检查是否存在重放请求
                $this->_throwState('915'); //拒绝重放请求
                return false;
            }
        }

        //将收到的数据包转换为数组
        $this->_aInJson = json_decode($this->_sInData, true);
        if (is_null($this->_aInJson)){ //解析json失败
            $this->_throwState('901'); //收到的协议包无法解析
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
            $this->_throwState('917'); //缺少package与class数据项
            return false;
        }
        if(!is_null($this->_ifLog)){ //记录日志
            $this->_aLog['pkg'] = $this->_aInJson['package'];
            $this->_aLog['cls'] = $this->_aInJson['class'];
            $this->_aLog['step'] = 'resolve';
        }
        //验证package的值是否非法
        if(!preg_match('/^[a-z][\w\.?]*[a-z0-9]$/', $this->_aInJson['package'])){
            $this->_throwState('930'); //package无效字符
            return false;
        }
        //package接口访问安全验证
        if (!is_null($this->_oImportSecurity)){
            $sErrCode = $this->_oImportSecurity->checkPackageSecurity($this->_aInJson); //包访问权验证 checksum
            if (!is_null($sErrCode)){
                $this->_throwState($sErrCode); //抛出 包访问权验证 checksum  错误信息
                return false;
            }
        }
        //接口访问预处理类
        $sFile = $this->_sWorkspace .
                 substr($this->_aInJson['package'], 0, strpos($this->_aInJson['package'], '.')) . '/ApiPretreatment.php';
        if (file_exists($sFile)){ //预处理文件存在
            require_once rtrim($this->_sFramePath, '/') .'/interface/IJsonWebServiceVisitPretreatment.php';//注入接口申明
            require_once $sFile; //加载包的访问预处理类
            $aRunClass = 'ApiPretreatment'; //访问预处理类名称
            if (class_exists($aRunClass, false)){ //找到访问预处理类
                $oap = new $aRunClass();
                if (is_a($oap, 'IJsonWebServiceVisitPretreatment')){
                    $aRet = $oap->toDo($this->_aInJson);
                    if (false !== $aRet){ //阻断执行并返回状态
                        self::$aResultStateList[$aRet['code']] = $aRet['msg'];
                        $this->_throwState($aRet['code']); //包访问被预处理程序阻断
                        return false; //token校验未通过
                    }
                }
                unset($oap);$oap=null; //回收资源
            }
        }
        $sFile = $this->_sWorkspace .
                 str_replace('.', '/', rtrim($this->_aInJson['package'], '.')) .'/'. $this->_aInJson['class'] .'.class.php';
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
                    return false;
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
                $this->_throwState('918'); //未找到执行类
                return false;
            }
        }else{
            $this->_throwState('919'); //未找到执行文件
            return false;
        }
    }
    /**
     * 检查token访问安全
     * @return boolean true:通过token验证 | false:未通过token验证
     */
    private function _checkToken(){
        if (!is_null($this->_oTokenSecurity)){
            if (!isset($this->_aInJson['token'])){
                $this->_throwState('950'); //缺少令牌参数(此状态码是通过bindTokenSecurityCheckObject()函数注入的)
                return false;
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

		ob_start();
		if (!is_null($this->_sJsonP_Func)){ //输出数据需要封装成jsonp方式
		    header('Content-Type: text/javascript; charset=UTF-8'); //默认字符集
		    header('Access-Control-Allow-Origin: *'); //跨站访问
		    header('Content-Length: '. strlen($sOutData) + strlen($this->_sJsonP_Func) + 2); //设置数据包长度
		    echo $this->_sJsonP_Func, '(', $sOutData ,')';
		}else{ //标准的json数据包头格式
		    header('Content-Type: application/json; charset=UTF-8'); //默认字符集
		    if( !headers_sent()  && extension_loaded('zlib')  && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && 
		        false !== stripos( $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')
		      ){ //发现客户端支持gzip，对数据压缩后发送
		        $sGzipData = gzencode($sOutData,9); //压缩输出数据
		        header ('Content-Encoding: gzip'); //使用gzip压缩输出
		        header ('Vary: Accept-Encoding'); //告诉客户端当前内容已经成功编码压缩
		        header ('Content-Length: '.strlen ($sGzipData)); //设置数据包长度
		        echo $sGzipData; //输出压缩数据
		        unset($sGzipData); //释放数据
		    }else{
		        header ('Content-Length: '.strlen ($sOutData)); //设置数据包长度
		        echo $sOutData;
		    }
		}
		ob_end_flush();
		if(!is_null($this->_ifLog)){ //记录日志
		    $this->_aLog['out'] = $sOutData;
		    $this->_aLog['status_code'] = (isset($this->_aResultData['status']['code']) ? $this->_aResultData['status']['code'] : '');
		    $this->_aLog['step'] = 'reply';
		}
    }
    /**
     * 获取workspace的工作目录
     * @return string
     */
    public function getWorkspace(){
        return $this->_sWorkspace;
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
     * @param string|array $mixd 待转换的变量（数组或字符串）
     * @return string|array 完成转换后的结果
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
        if (intval($a) >=6 || (intval($a) >= 5 && intval($b) >= 4)){
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
    /**
     * 获取客户端的HTTP_USER_AGENT信息解析
     * <li>必须按照HTTP协议规范送出HTTP_USER_AGENT，并且符合JSW的规范才能解析出HTTP_USER_AGENT中的参数</li>
     * <li>HTTP_USER_AGENT规范: [appname]/[ver] ([system info])</li>
     * <li>[appname]:应用名称</li>
     * <li>[ver]:版本号，格式：x.x.x ；x为纯数字</li>
     * <li>[system info]：系统信息，如果是iphone或者android，必须带[iphone|android]字样</li>
     * @return array('ver'=>array(x,x,x)版本号数组, 'appname'=>'应用名称', 'client'=>'客户端类型[iphone|android|webserver|other]')
     */
    static public function resloveUserAgentInfo(){
        $aData = array('ver'=>'', 'appname'=>'', 'client'=>'other');
        $sHeaderVer = strtolower($_SERVER['HTTP_USER_AGENT']);
        $aData['appname'] = trim(substr($sHeaderVer, 0, strpos($sHeaderVer, '/')));//取出AppName
        $sVer = trim(substr($sHeaderVer, strpos($sHeaderVer, '/')+1, strpos($sHeaderVer, '(') - strpos($sHeaderVer, '/') -1 ));//取出APP版本号
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

        $aVerCode = explode('.', $sVer); //版本号数组化
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
     * 比较两个版本号的大小
     * <li>版本号格式 x.x.x 为整型数字</li>
     * @param array $aSrcVer 原版本号
     * <li>array(x,x,x)</li>
     * @param array $aCur 比较的当前版本号
     * <li>array(x,x,x)</li>
     * @return int -1:当前版比原版本低 | 0:当前版本与原版本相同 | 1:当前版本比原版本高
     */
    static public function compareVerCode($aSrcVer, $aCur){
        foreach ($aSrcVer as & $sNode)
            $sNode = intval($sNode);
        unset($sNode);
        foreach ($aCur as & $sNode)
            $sNode = intval($sNode);
        unset($sNode);
        for ($i=0; $i<3; $i++){
            if ($aSrcVer[$i] > $aCur[$i]){ //当前版本比原版本高
                return 1;
            }elseif ($aSrcVer[$i] < $aCur[$i]){ //当前版比原版本低
                return -1;
            }
        }
        return 0; //当前版本与原版本相同
    }
}