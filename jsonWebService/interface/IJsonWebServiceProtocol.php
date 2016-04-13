<?php
/**
 * JsonWebService接的口协议反射接口
 * @author JerryLi 2015-09-04
 *
 */
interface IJsonWebServiceProtocol{
    /**
     * 返回当前API接口类的功能介绍
     * <li>支持"\n"换行显示</li>
     * @return string
     */
    public function getClassExplain();
    /**
     * 返回当前API类的使用注意事项介绍
     * <li>支持"\n"换行显示</li>
     * @return null | string
     */
    public function getAttentionExplain();
    /**
     * 接口的输入协议格式
     * <li>输出为数组结构</li>
     * <li>约束规范（类型关键字）string, int, long, double</li>
     * <li>约束规范（范围关键字）max:n, min:n, fixed:n, list:xxx,xxx,xxx</li>
     * <li>约束规范（必填关键字）require</li>
     * @return array
     */
    public function getInProtocol();
    /**
     * 接口的出口协议格式
     * <li>输出为数组结构</li>
     * <li>约束规范（类型关键字）string, int, long, double</li>
     * <li>约束规范（范围关键字）max:n, min:n, fixed:n, list:xxx,xxx,xxx</li>
     * <li>约束规范（必填关键字）require</li>
     * @return array
     */
    public function getOutProtocol();
    /**
     * 接口更新日志
     * @return array array(array('date'=>'接口更新时间', 'name'=>'接口更新人', 'memo'=>'接口更新的内容'),...)
     */
    public function getUpdateLog();
}