<?php
/**
 * JsonWebService接口服务系统日志组件接口
 * <li>建议记录程序运行时的内存消耗量；ceil(memory_get_peak_usage()/1000) =>kb</li>
 * <li>建议客户端设置HTTP_USER_AGENT，便于日志的识别与管理</li>
 * @author JerryLi 2015-09-04
 * @example 日志流程
 * 1:createLog(data);
 */
interface IJsonWebServiceLog{
    /**
     * 创建日志
     * @param array $aParam 保存的日志参数
     * <li>array('in'=>'入口内容', 'out'=>'出口内容', 'pkg'=>'包路径信息', 'cls'=>'接口名信息', 'status_code'=>'状态码',
     *     'step'=>'阶段[receive:接收到数据 | resolve:Json解析成功数据 | reply:接口正常回复 | app_err:应用错误]',
     *     'runtime'=>'运行时间ms', 'sign'=>'body签名')</li>
     * @return void
     */
    public function createLog($aParam);
}