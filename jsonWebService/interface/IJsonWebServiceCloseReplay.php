<?php
/**
 * JsonWebService接口截止回放处理逻辑
 * <li>如果启用此接口，建议使用memcached作为缓存</li>
 * <li><strong>警告：</strong>如果本接口出错将导致整个API服务瘫痪</li>
 * <li>注意：本接口处于框架的最前端并发量非常大，不建议在里面实现过多的业务逻辑，尽可能不要使用数据库</li>
 * @author JerryLi 2015-09-04
 * @example 日志流程
 * 1:createLog(data);
 */
interface IJsonWebServiceCloseReplay{
    /**
     * 检查是否存在重放访问
     * @param string $sSignKey 签名字符串（sha1，长度40个字符） 
     * @param int $iReplayTime sign过期时间(秒) 
     * @return boolean true:重放访问 | false:非重放访问
     */
    public function checkReplay($sSignKey, $iReplayTime);
}