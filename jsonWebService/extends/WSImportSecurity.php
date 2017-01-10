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
     * 配置文件的物理绝对路径
     * @var string
     */
    private $_sConfigPath = null;
    /**
     * 构造
     * @param string $sFilePath 配置文件地址
     * <li>必须为物理绝对地址</li>
     */
    public function __construct($sFilePath){
        $this->_sConfigPath = $sFilePath;
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceImportSecurity::loadCfg()
     */
    public function loadCfg(){
        if (file_exists($this->_sConfigPath)){ //检查配置文件是否存在
            $aCfg = require $this->_sConfigPath; //载入配置文件
            $this->_aPubKey = $aCfg['sign_pub_key'];
            $this->_aPackageSecurityPubKey = $aCfg['package_security_pub_key'];
            unset($aCfg);
        }else{
            return 902; //配置文件加载失败
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
        if (!isset($_SERVER['HTTP_SIGNATURE']) || !isset($_SERVER['HTTP_UTC_TIMESTEMP']) || !isset($_SERVER['HTTP_RANDOM'])){
            return '901';//缺少必要的HEAD参数
        }
        //取出http头部的必要参数
        $sSign = trim($_SERVER['HTTP_SIGNATURE']);
        $this->_iClientUtcTimestamp = intval($_SERVER['HTTP_UTC_TIMESTEMP']);
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
        foreach ($this->_aPubKey as $aNode){
            if ($aNode['deadline'] > 0 && $aNode['deadline'] < $iTime){
                continue; //公钥已过期，跳过此公钥
            }
            if (sha1($sInData . $this->_iClientUtcTimestamp . $sRandom . $aNode['key']) === $sSign){
                return null; //通过检查
            }
        }
        return '907'; //数据签名验证失败
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