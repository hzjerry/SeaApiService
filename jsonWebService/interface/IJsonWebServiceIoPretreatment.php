<?php
/**
 * JsonWebService接口的输入输出预处理
 * <li>对收到的数据包解析json后的数组，或准备输出进行json序列化前的数组 的内容进行替换，一般用于对表情字符的处理</li>
 * <li>本函数处理的数据Value内容字符集为utf-8</li>
 * @author JerryLi
 *
 */
interface IJsonWebServiceIoPretreatment{
    /**
     * 过滤输入流
     * @param array $aData json解析成数组后的对象
     * <li>数组value的字符集为utf-8</li>
     * @return void
     */
    public function filterInport(& $aData);
    /**
     * 过滤输出流
     * @param array $aData 数组
     * <li>数组value的字符集为utf-8</li>
     * @return void
     */
    public function filterOutport(& $aData);
}