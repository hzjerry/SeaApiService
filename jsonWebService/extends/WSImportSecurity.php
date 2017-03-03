<?php
require_once (ROOT_PATH . FRAME_PATH . 'base/CJsonWebServiceImportSecurity.php');
/**
 * JsonWebService接口的入口请求安全层实例
 * <li>此处实例化了fapis的基本安全验证逻辑，如有需要可重写之。重写后需要对CJsonWebServiceClient类做相应的修改</li>
 * <li>如果存在不同的jaonWebService实例，建议关闭安全层将安全处理转移到上层的网关，通过接口的治理进行维护</li>
 * @author JerryLi
 * @version 2017-01-10
 */
final class WSImportSecurity extends CJsonWebServiceImportSecurity{
    /**
     * 公钥（用于签名）
     * <li>array(array('key'=>'', 'deadline'=>0),...)</li>
     * @var array
     */
    private $_aPubKey = null;
    /**
     * 配置信息
     * <li>类型为字符串；为配置文件的绝对物理路径</li>
     * <li>类型为数组；直接为配置项数组，数据格式参照 config.json_web_service 配置文件的格式</li>
     * @var mixed
     */
    private $_mCfg = null;
    /**
     * 构造
     * @param mixed $mReflectionCfg 反射框架的配置文件
     * <li>类型为字符串；为配置文件的绝对物理路径</li>
     * <li>类型为数组；直接为配置项数组，数据格式参照 config.json_web_service 配置文件的格式</li>
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

        //检查配置信息是否正确
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
        if (!empty($this->_mCfg) && is_array($this->_mCfg)){ //配置加载成功
            $this->_aPubKey = $this->_mCfg['sign_pub_key'];
            $this->_aPackageSecurityPubKey = $this->_mCfg['package_security_pub_key'];
            unset($this->_mCfg);$this->_mCfg=null; //加载完成后释放资源
        }else{
            echo __CLASS__ . ':Failed to load configuration.';
            exit;
        }
        return null;
    }
    /**
     * 输出已载入到
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
        //数据签名参数获取
        if (isset($_SERVER['HTTP_SIGNATURE'])){ //获取HTTP头中的签名参数
            $sSign = $_SERVER['HTTP_SIGNATURE'];
        }else{ //从GET中获取签名参数
            $sSign = isset($_GET['sign']) ? strtolower(trim($_GET['sign'])) : null;
        }
        if (!isset($_SERVER['HTTP_SIGNATURE']) || !isset($_SERVER['HTTP_UTC_TIMESTAMP']) || 
            !isset($_SERVER['HTTP_RANDOM']) || !isset($_SERVER['HTTP_ACCOUNT_KEY']) ){
            return '905';//缺少必要的HEAD参数
        }
        //取出http头部的必要参数
        $sSign = trim($_SERVER['HTTP_SIGNATURE']);
        $sAK = $_SERVER['HTTP_ACCOUNT_KEY'];
        $this->_iClientUtcTimestamp = intval($_SERVER['HTTP_UTC_TIMESTAMP']);
        $sRandom = $_SERVER['HTTP_RANDOM'];
        if (strlen($sRandom) !== 8){
            return '904'; //HTTP HEAD RANDOM参数无效
        }elseif(!empty($sSign) && strlen($sSign) !== 40){
            return '906'; //签名格式无效
        }
        if (abs(time() - $this->_iClientUtcTimestamp) > 3600){ //计算时间戳是否与标准utc时差超过3600秒
            return '903'; //时间戳过期
        }
        //检查body签名有效性
        $bSignFail = true;
        $iTime = time(); //当前时间
        if (!isset($this->_aPubKey[$sAK])){
            return '907'; //数据签名验证失败
        }else{
            if ($this->_aPubKey[$sAK]['deadline'] > 0 && $this->_aPubKey[$sAK]['deadline'] < $iTime){
                return '907'; //公钥已过期，跳过此公钥
            }
            if (sha1($sInData . $this->_iClientUtcTimestamp . $sRandom . $this->_aPubKey[$sAK]['key']) !== $sSign){
                return '907'; //签名值不一样
            }
        }
        return null; //数据签名验证通过
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceImportSecurity::checkPackageSecurity()
     */
    public function checkPackageSecurity(& $aJoinData){
        if (empty($this->_aPackageSecurityPubKey)){
            return null; //未配置接口访问密钥
        }
        $sPackage = $aJoinData['package'];
        $sClass = $aJoinData['class'];
        $sCheckSum = (isset($aJoinData['checksum']) && strlen($aJoinData['checksum']) === 32) ? $aJoinData['checksum'] : null;
        //检查是否需要验证包访问权限
        $aPkgName = explode('.', $sPackage);
        $aPubKey = null;
        $aPSP = & $this->_aPackageSecurityPubKey; //取引用
        foreach ($aPkgName as $sPkgName){
            if (isset($aPSP[$sPkgName])){ //找到包密码配置项
                $aPSP = & $aPSP[$sPkgName]; //改变当前根引用指针
                $aPubKey = $aPSP['_']; //先当前节点的根密码配置
            }else{
                break;
            }
        }
        unset($aPSP);
        if (!is_null($aPubKey)){//找到密钥配置
            $iTime = time(); //当前时间
            foreach ($aPubKey as $aNode){
                if ($aNode['deadline'] > 0 && $aNode['deadline'] < $iTime){
                    continue; //公钥已过期，跳过此公钥
                }
                if (md5($this->_iClientUtcTimestamp . $sPackage . $sClass . $aNode['key']) === $sCheckSum){
                    return null;//校验通过
                }
            }
            return '908'; //checksum校验失败
        }
        return null;
    }
}