<?php
/**
 * JsonWebService接口的Token安全验处理基类
 * @author JerryLi
 *
 */
abstract class CJsonWebServiceTokenSecurity{
    /**
     * token中保存的数据域
     * @var array
     * <li>array('domain key'=>'data val',...)</li>
     */
    protected $_aTokenData = null;
    /**
     * API服务定义的状态值列表
     * <li>结构array('code'=>'文字解释',...)</li>
     * <li>code约定: 纯数字字符串, Token验证状态码范围950～959</li>
     * @var array
     */
    static public $aResultStateList = array(
        '950'=>'Missing token token parameter.(缺少token令牌参数)',
        '951'=>'登录失效，请重新登录',
    );
    /**
     * 校验Token令牌的有效性
     * @param string $sToken 令牌(16进制字符串)
     * @param string $sPackage 包名
     * @param string $sClass 类名
     * @return true:通过验证 | string:未通验证
     * <li>返回的状态码必须为CJsonWebServiceTokenSecurityCheck::$_aResultStateList中定义的代码</li>
     */
    abstract public function checkToken($sToken, $sPackage, $sClass);
    /**
     * 拉取token中保存的值
     * <li>必须先使用checkToken()进行安全校验后，如果通过验证，则可以使用本函数取出toke对应的值</li>
     * @return array() | array( '域key'=>'token保持的字符串值'), ... )
     */
    abstract public function pullContent();
}