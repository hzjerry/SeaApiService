<?php
/**
 * JsonWebService接口访问预处理
 * <li>在进入应用层之前，每个package的顶层根目录如果需要可创建ApiPretreatment.php类文件，类名为ApiPretreatment(注意大小写)，
 *     系统发现存在此文件时会先执行这个文件接口中的init方法；然后才会走到应用层
 * </li>
 * <li>在这个文件中可以对接口的入口访问做特殊的预处理；例如对USER_AGENT中的版本号做单独的处理</li>
 * @author JerryLi
 *
 */
interface IJsonWebServiceVisitPretreatment{
    /**
     * 访问预处理
     * @param array $aInJson json解析成数组后的对象
     * <li>此为引用访问，如果要传递值给应用层可以直接修改$aInJson的值</li>
     * @return boolean true:阻断执行(接口返回905状态) | false:可继续执行（进入应用层）
     */
    public function toDo(& $aInJson);
}