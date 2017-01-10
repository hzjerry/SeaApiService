<?php
/**
 * JsonWebService接口的入口请求安全层
 * <li>安全验证类可以自行定义安全验证规则</li>
 * @author JerryLi
 *
 */
abstract class CJsonWebServiceImportSecurity{
    /**
     * 客户端的时间戳
     * @var double
     */
    protected $_iClientUtcTimestamp = 0;
    /**
     * 包接口访问安全密钥
     * @var array
     */
    protected $_aPackageSecurityPubKey = null;
    /**
     * API服务定义的状态值列表
     * <li>结构array('code'=>'文字解释',...)</li>
     * <li>code约定: 纯数字字符串, ImportSecurity验证状态码范围902～914</li>
     * @var array
     */
    static public $aResultStateList = array(
        /*902 ~ 914*/
        '902'=>'Configuration file loading failed.(入口验证安全层配置文件加载失败)',
        '903'=>'UTC Timestamp expired.(时间戳过期；与utc时差超过3600秒)',
        '904'=>'Invalid parameter HTTP HEAD RANDOM.(HTTP HEAD RANDOM参数无效)',
        '905'=>'Lack of necessary HEAD parameters.(缺少必要的HTTP HEAD参数)',
        '906'=>'Invalid signature parameters.(签名参数无效)',
        '907'=>'Signature verification failed.(签名验证失败)',
        '908'=>'checksum check failure.(checksum校验失败)',
    );
    /**
     * 载入安全验证类的配置文件
     * @return null:配置加载成功 | string: 检查未通过时返回的状态码
     */
    abstract public function loadCfg();
    /**
     * 请求入口的签名安全验证参数检查
     * <li>用于检查接收到的http请求中的需要必传的安全验证参数项是否传入</li>
     * @param string 输入参数结果集
     * <li>数据以引用的方式传入</li> 
     * @return null:正常通过检查 | string: 检查未通过时返回的状态码
     */
    abstract public function checkSignSecurity(& $sInData);
    /**
     * 包访问权验证checksum
     * <li>如果接口的访问包有</li>
     * <li>如果没有配置包访问密码，则该函数不起作用</li>
     * @param array $aJoinData 输入数据包数组
     * <li>输入数据包解压成json数组后的对象</li>
     * @return null:正常通过检查 | string: 检查未通过时返回的状态码
     */
    abstract public function checkPackageSecurity(& $aJoinData);
    /**
     * 获取包访问密钥的配置
     * <li>用于获取原生框架定义的 checksum 密钥配置。</li>
     * <li>如果你改造了本框架的安全验证层去掉checksum逻辑，这个函数请直接返回null</li>
     */
    abstract public function getPackageSecurityPubKey();
}