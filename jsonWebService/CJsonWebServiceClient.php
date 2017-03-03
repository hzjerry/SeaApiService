<?php
/**
 * JsonWebService接口的客户端类
 * @author JerryLi 2015-09-04
 *
 */
class CJsonWebServiceClient{
    /**
     * 本地字符集(根据本地开发环境切换)
     * @var string
     */
    const LOCAL_CHARSET = 'GBK';
    /**
     * 版本号
     */
    const VER = '1.10.0';
    /**
     * 客户端信息
     * @var string
     */
    private $_sClientName = null;
    /**
     * 开发模式
     * @var boolean
     */
    private $_bDebug = false;
    /**
     * 远程接口的访问地址
     * @var string
     */
    private $_sJWS_URL = null;
    /**
     * 公钥（用于签名）
     * <li>array(array('key'=>'', 'deadline'=>0),...)</li>
     * @var array
     */
    private $_aPubKey = '';
    /**
     * 系统时间戳
     * @var int
     */
    private $_iUtcTimestamp = 0;
    /**
     * 帐号key
     * @var int
     */
    private $_iAccountKey = 0;
    /**
     * 包接口访问安全密钥
     * @var array
     */
    private $_aPackageSecurityPubKey = null;
    /**
     * 接口的执行开始时间
     * <li>单位:微妙</li>
     * @var int
     */
    private $_iStartTime = 0;
    /**
     * 保存最后一次的历史通信数据
     * @var array('sent'=>null, 'receive'=>null, 'sign'=>null)
     */
    private $_aHistory = array('sent'=>null, 'receive'=>null, 'sign'=>null);
    /**
     * 保存最后一次遇到的curl通信错误
     * @var stirng
     */
    private $_last_curl_error = null;
    /**
     * 构造函数
     * @param mixed $mixedCfg 配置信息
     * <li>类型为字符串；为配置文件的路径</li>
     * <li>类型为数组；直接为配饰项，数据格式参照 Client 配置文件的格式</li>
     */
    public function __construct($mixedCfg){
        $this->_iStartTime = microtime(true); //记录起始时间
        $this->_sClientName = 'sea_api_php/'. self::VER;
        if (isset($_SERVER['SERVER_SOFTWARE'])){ //从服务器过来的请求
            $this->_sClientName .= ' ('. $_SERVER['SERVER_SOFTWARE'] .')';
        }else{ //直接从php过来的请求
            $this->_sClientName .= ' (php/'. PHP_VERSION .')';
        }
        //载入配置文件信息
        $this->_read_config($mixedCfg);
    }
    /**
     * 读取配置信息
     * @param mixed $mixedCfg 配置信息
     * <li>类型为字符串；为配置文件的路径</li>
     * <li>类型为数组；直接为配饰项，数据格式参照 Client 配置文件的格式</li>
     */
    protected function _read_config($mixedCfg){
        if (is_string($mixedCfg)){ //为字符串，加载配置文件
            if (file_exists($mixedCfg)){ //检查配置文件是否存在
                $aCfg = require $mixedCfg; //载入配置文件
            }else{
                echo __CLASS__ . ':Failed to load the configuration file.';
                exit;
            }
        }else{
            $aCfg = $mixedCfg;
        }
        
        if (isset($aCfg['access_key'])){
            $this->_iAccountKey = intval($aCfg['access_key']);
        }else{
            echo __CLASS__ . ':Invaild [access_key]  configuration key.';
            exit;
        }
        if (isset($aCfg['sign_pub_key'])){
            $this->_aPubKey = $aCfg['sign_pub_key'];
        }else{
            echo __CLASS__ . ':Invaild [sign_pub_key]  configuration key.';
            exit;
        }
        if (isset($aCfg['url'])){
            $this->_sJWS_URL = $aCfg['url'];
        }else{
            echo __CLASS__ . ':Invaild [url]  configuration key.';
            exit;
        }
        if (isset($aCfg['package_security_pub_key'])){
            $this->_aPackageSecurityPubKey = $aCfg['package_security_pub_key'];
        }else{
            echo __CLASS__ . ':Invaild [package_security_pub_key] configuration key.';
            exit;
        }
        if (isset($aCfg['debug'])){
            $this->_bDebug = $aCfg['debug'];
        }else{
            echo __CLASS__ . ':Invaild [debug] configuration key.';
            exit;
        }
        unset($aCfg);
        return true;
    }
    /**
     * 析构函数
     * @see CExtModel::__destruct()
     */
    public function __destruct(){
    }
    /**
     * 返回运行时间
     * @return int ms
     */
    public function runtime(){
        return intval((microtime(true) - $this->_iStartTime) * 1000);
    }
    /**
     * 设置自定义的客户端USER_AGENT_HEADER
     * @param string $sStr
     * <li>格式: client_name/vercode (....)</li>
     * <li>例如: chemao_php/1.10.0 (php 5.4 nginx win64)</li>
     */
    public function setUserAgent($sStr){
        $this->_sClientName = $sStr;
    }
    /**
     * 获取最后一次通信数据
     * @param string $sType 类型[sent:发送出的数据 | receive:接收到的数据]
     * @return string | null
     * <li>注意：输出的字符集类型必定为UTF-8</li>
     */
    public function getHistory($sType){
        if ('sent' === $sType){
            return $this->_aHistory['sent'];
        }else{
            return $this->_aHistory['receive'];
        }
    }
    /**
     * 执行接口通信
     * @param string $sPackage 包名
     * @param string $sClass 类名
     * @param array $aParam 需要提交的参数
     * <li>注意：输入内容为本地字符集</li>
     * @param boolean $bDebug 临时开启一次调试模式
     * @return 0:通信失败 | -1:解析Json失败 | array():正常返回
     * <li>注意：输出内容为本地字符集</li>
     */
    public function exec($sPackage, $sClass, $aParam, $bDebug=false){
        $aPkgName = explode('.', $sPackage);
        $sPkgKey = null;
        $aPSP = & $this->_aPackageSecurityPubKey; //取引用
        foreach ($aPkgName as $sPkgName){
            if (isset($aPSP[$sPkgName])){ //找到包密码配置项
                $aPSP = & $aPSP[$sPkgName]; //改变当前根引用指针
                $sPkgKey = $aPSP['_']; //先当前节点的根密码配置
            }else{
                break;
            }
        }
        unset($aPSP, $aPkgName);
        //补全缺失的数据
        $aParam['package'] = $sPackage;
        $aParam['class'] = $sClass;
        $this->_iUtcTimestamp = time(); //生成系统时间戳
        if (!is_null($sPkgKey)){
            $aParam['checksum'] = md5($this->_iUtcTimestamp . $sPackage . $sClass . $sPkgKey);
        }
        $sRet = $this->_transmission($aParam, $this->_aPubKey, $bDebug);
        if (false === $sRet){
            return 0;
        }else{
            if (is_null($aJson = json_decode($sRet, true))){ //解析失败
                return -1;
            }else{ //解析成功
                if ('UTF-8' !== self::LOCAL_CHARSET) //识别如果源字符集非UTF-8则强制转换为UTF-8
                    $aJson = self::convert_encoding('UTF-8', self::LOCAL_CHARSET, $aJson);
                return $aJson;
            }
        }
    }
    /**
     * 获取远程接口地址
     * @return string
     */
    public function getRemoteUrl(){
        return $this->_sJWS_URL;
    }
    /**
     * 数据接口传输层
     * @param array $aData 需要发送的数组对象
     * @param string $sKey 签名公钥
     * @param boolean $bDebug 临时开启一次调试模式
     * @return string | false
     */
    private function _transmission(& $aData, $sKey, $bDebug){

        if ('UTF-8' !== self::LOCAL_CHARSET) //识别如果源字符集非UTF-8则强制转换为UTF-8
            $aData = self::convert_encoding(self::LOCAL_CHARSET, 'UTF-8', $aData);

        $sData = self::json_encode($aData); //数组转换成json对象
        $this->_aHistory['sent'] = $sData; //保存最后一次发送的数据
        $iRandom = rand(10000000, 99999999); //随机数
        $this->_aHistory['sign'] = sha1($sData . $this->_iUtcTimestamp . $iRandom . $sKey); //数据包签名值
        $ch = curl_init();//初始化curl
        $aHeader = array();
        $aHeader[] = 'Connection: close';
        $aHeader[] = 'Content-Type: application/json; charset=utf-8';
        $aHeader[] = 'Content-length: '. strlen($sData);
        $aHeader[] = 'Cache-Control: no-cache';
        $aHeader[] = 'Account-Key: '. $this->_iAccountKey;  //HTTP_ACCOUNT_KEY
        $aHeader[] = 'Signature: '. $this->_aHistory['sign'];
//         $aHeader[] = 'Signature: f947d4e3b400adcfd287b0fb9276e5f237cf969a'; //测试回放攻击
        $aHeader[] = 'UTC-Timestamp: '. $this->_iUtcTimestamp;  //HTTP_UTC_TIMESTEMP
        $aHeader[] = 'Random: '. $iRandom; //HTTP_RANDOM
        $aHeader[] = 'Expect:';
        if (false !== strpos($this->_sJWS_URL, 'https:')){ //加入https专用请求头
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
        }
        curl_setopt($ch,CURLOPT_URL, $this->_sJWS_URL); //接口地址
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0); //强制协议为1.0
        curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);//设置header
        curl_setopt($ch, CURLOPT_USERAGENT, $this->_sClientName);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//将结果保存成字符串
        curl_setopt($ch, CURLOPT_POST, true);//post提交方式
        curl_setopt($ch, CURLOPT_ENCODING ,'gzip'); //加入gzip解析
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 ); //php版本5.3及以上，可关闭IPV6，只使用IPV4
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5 ); //php版本5.2.3及以上，连接超时时间(秒)
        curl_setopt($ch, CURLOPT_TIMEOUT, 6 ); //运行超时(秒)
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sData); //送出post数据
        curl_setopt($ch, CURLOPT_HEADER, true); //获取头信息
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); //自动重定向1次
        $iStart = microtime(true); //记录通信开始时间
        $sResponse = curl_exec($ch);//运行curl
        $aCurlInfo = curl_getinfo($ch);//获取状态信息
        if (200 === $aCurlInfo['http_code']){ //通信成功
            $this->_aHistory['receive'] = substr($sResponse, strpos($sResponse, "\r\n\r\n")+4); //保存最后一次收到的数据
            unset($sResponse);
            $this->_last_curl_error = null;
            if ($bDebug || $this->_bDebug){ //调试模式时，输出
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
        }else{ //通信失败
            $this->_last_curl_error = curl_error($ch);
            if (empty($this->_last_curl_error)){
                $this->_last_curl_error = 'http_code:'. $aCurlInfo['http_code'];
            }
            unset($ch, $aCurlInfo);
            if ($bDebug || $this->_bDebug){ //调试模式时，输出错误信息
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
    static function json_encode(& $aData){
        if (version_compare(PHP_VERSION, '5.4.0', '<')){ //5.4之前的版本不支持 JSON_UNESCAPED_UNICODE
            return json_encode($aData);
        }else{
            return json_encode($aData, JSON_UNESCAPED_UNICODE); //不编码全角字符集;
        }
    }
    /**
     * Json数据格式化
     * @param  Mixed  $data   数据
     * @param  String $indent 缩进字符，默认4个空格
     * @return JSON
     */
    static private function jsonFormat($data, $indent=null){
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
}