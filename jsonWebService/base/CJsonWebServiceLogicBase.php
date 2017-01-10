<?php
/**
 * JsonWebService接口逻辑的基类
 * <li>所有接口逻辑实现都必须继承这个抽象类</li>
 * @author JerryLi 2015-09-04
 * @abstract
 *
 */
abstract class CJsonWebServiceLogicBase{
    /**
     * 接口的执行开始时间
     * <li>单位:微妙</li>
     * @var int
     */
    private $_iStartTime = 0;
    /**
     * 客户端的user_agent_信息解析
     * @var array
     */
    private $_aUserAgentInfo = array();
    /**
     * 保存着最后一次设置的返回状态码
     * @var string
     */
    private $_sResultCode = null;
    /**
     * 标记是否需要开启token身份验证
     * @var boolean
     */
    private $_bUseTokenCheck = false;
    /**
     * 标记不要记录访问日志
     * @var boolean
     */
    private $_bDontWriteLog = false;

    /**
     * 保存从JsonWebService中Token解析后得到的Token的内容
     * @var array
     * <li>array('数据域'=>'token保持值',...)</li>
     */
    private $_aTokenContent = null;
    /**
     * 防御XXS攻击
     * @var boolean
     */
    private $_bDefenseXXS = true;
    /**
     * 系统预定义的返回状态编号
     * @var array
     */
    static public $aResultStateList = array(
        '920'=>'The returned value of the unregistered state.(未注册的返回状态值)',
        '921'=>'API interface services no output result set.(api接口服务无输出结果集)',
    );
    /**
     * 返回结果集的数组
     * @var array
     */
    private $_aResultData = array();
    /**
     * 构造函数
     * <li>注意：子类中不要做初始化操作<li>
     * @example<pre>
        public function __construct(){
            parent::__construct();
            $this->usedTokenCheck(); //开启身份验证规则
            //请不要后续做其他初始化操作，否则会报错
        }
     * </pre>
     */
    public function __construct(){
        $this->_iStartTime = microtime(true); //记录起始时间
        $this->_resloveUserAgentInfo(); //解析HttpUserAgent头
        //合并应用级状态数据
        foreach ($this->initResultList() as $sKey=>$sVal){
            self::$aResultStateList[$sKey] = $sVal;
        }
    }
    /**
     * 析构函数
     */
    public function __destruct(){
        //TODO:处理输出内容
    }
    /**
     * 是否开启防御XXS攻击
     * @return boolean
     */
    public function isDefenseXXS(){
        return $this->_bDefenseXXS;
    }
    /**
     * 在应用逻辑类关闭XXS攻击防御过滤
     * <li>注意本函数一定要继承类的构造函数中使用，否则无效</li>
     * @return void
     */
    protected function closeDefenseXXS(){
        $this->_bDefenseXXS = false;
    }
    /**
     * 判断接口是否已经废除
     * @return boolean true:接口未失效 | false:接口已失效停止对外服务
     */
    public function isDead(){
        $iUTC = $this->setDeadline();
        if ($iUTC <= 0){
            return false;
        }else{
           if ($iUTC < time()){
               return true; //已经过期
           }else{
               return false; //未失效可以使用
           }
        }
    }
    /**
     * 获取接口的失效时间
     * @return int utc_timestemp
     */
    public function getDeadline(){
        return $this->setDeadline();
    }
    /**
     * 设置返回值
     * <li>注意：函数本身不会终止后续代码的执行。</li>
     * <li>如果抛出状态码后要结束程序，请这样调用 return $this->setResultState('00000')</li>
     * @param string $sCode
     * @return boolean false:无法识别的返回状态值 | true:已识别状态号
     */
    public function setResultState($sCode){
        if (in_array($sCode, array_keys(self::$aResultStateList))){
            $this->_sResultCode = $sCode;
            return true;
        }else{ //未注册的返回状态值
            $this->_sResultCode = '920';
            return false;
        }
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
    private function _resloveUserAgentInfo(){
        if (class_exists('JsonWebService')){ //cli时不会加载JsonWebService类
            $this->_aUserAgentInfo = JsonWebService::resloveUserAgentInfo();
        }
    }
    /**
     * 客户端版本号
     * @return array(x,x,x)
     */
    protected function getClientVer(){
        if (isset($this->_aUserAgentInfo['ver'])){
            return $this->_aUserAgentInfo['ver'];
        }else{
            return array();
        }
    }
    /**
     * 客户端名称
     * @return string:
     */
    protected function getClientAppname(){
        if (isset($this->_aUserAgentInfo['appname'])){
            return $this->_aUserAgentInfo['appname'];
        }else{
            return '';
        }
    }
    /**
     * 获取客户端类型
     * @return string [iphone | android | webserver | other]
     */
    protected function getClientType(){
        if (isset($this->_aUserAgentInfo['client'])){
            return $this->_aUserAgentInfo['client'];
        }else{
            return '';
        }
    }
    /**
     * 返回输出结构化数组
     * @return array
     */
    public function getResult(){
        if (empty($this->_sResultCode)){
            $this->_sResultCode = '921'; //无输出结果集
        }elseif (!isset(self::$aResultStateList[$this->_sResultCode])){
            $this->_sResultCode = '920'; //未注册的状态码
        }
        $aStatus = array('status'=>array('code'=>$this->_sResultCode,
            'msg'=>self::$aResultStateList[$this->_sResultCode],
            'runtime'=>sprintf('%.4f', (microtime(true) - $this->_iStartTime) * 1000) ));
        return array_merge(array('result'=>$this->_aResultData), $aStatus);
    }
    /**
     * 获取API自定义的状态值
     * @return array
     */
    public function getStatus(){
        return $this->initResultList();
    }
    /**
     * 获取是否打开token校验
     * @return boolean
     */
    public function getTokenCheckStatus(){
        return $this->_bUseTokenCheck;
    }
    /**
     * 获取是否要关闭日志
     * @return void
     */
    public function getDoNotWirteLog(){
        return $this->_bDontWriteLog;
    }
    /**
     * 输出返回值
     * @param string $sKey 关键字
     * @param string | array $aVal 值
     * @return void
     */
    protected function o($sKey, $aVal){
        $this->_aResultData[$sKey] = $aVal;
    }
    /**
     * 开启token身份验证规则
     * <li>在接口的业务逻辑中开启Token身份验证</li>
     * <li><strong>注意</strong>必须在__construct()构造函数中调用，否则无法开启校验</li>
     * @return void
     */
    protected function usedTokenCheck(){
        $this->_bUseTokenCheck = true;
    }
    /**
     * 关闭日志记录
     * <li>调用后，系统将不会记录访问日志</li>
     * @return void
     */
    protected function dontWirteLog(){
        $this->_bDontWriteLog = true;
    }
    /**
     * 设置token中取得的session信息
     * @param string $sStr
     * @return void
     */
    public function setTokenContent($sStr){
        $this->_aTokenContent = $sStr;
    }
    /**
     * 获取token中对应的会话保持信息
     * @return array | null
     * <li>array('数据域'=>'token保持值',...)</li>
     */
    protected function getTokenContent(){
        return $this->_aTokenContent;
    }
    /**
     * 初始化返回状态值列表
     * <li>注意：子类必须实现这个函数，输出应用级的输出状态值定义</li>
     * <li>建议：应用级的返回代码为 '00000'~'99999'这样的格式，以便于统一规范</li>
     * @abstract
     * @return array('code1'=>'msg2', 'code2'=>'msg2', ...)
     */
    abstract protected function initResultList();
    /**
     * 接口的入口执行方法
     * <li>注意：子类必须实现这个函数，当接口被访问时，默认会执行这个方法</li>
     * <li>如果想在run中提前返回，直接使用return;</li>
     * @abstract
     * @param array $aIn 接口的入参(json映射的数组对象)
     * @return void
     */
    abstract public function run($aIn);
    /**
     * 接口的失效时间
     * @abstract
     * @return int 0:永不过期 | >0 接口失效的时间(utc时间戳,单位秒数)
     * <li>utc时间戳的生成样例 strtotime('2015-12-30 00:00:00')</li>
     */
    abstract protected function setDeadline();
    /**
     * 类初始化函数
     * <li>请把面向应用的初始化参数都放在这个函数中完成，不要在构造函数中做初始化处理</li>
     * @abstract
     * @return void
     */
    abstract public function init();
}