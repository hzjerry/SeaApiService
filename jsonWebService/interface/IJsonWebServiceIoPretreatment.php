<?php
/**
 * JsonWebService�ӿڵ��������Ԥ����
 * <li>���յ������ݰ�����json������飬��׼���������json���л�ǰ������ �����ݽ����滻��һ�����ڶԱ����ַ��Ĵ���</li>
 * <li>���������������Value�����ַ���Ϊutf-8</li>
 * @author JerryLi
 *
 */
interface IJsonWebServiceIoPretreatment{
    /**
     * ����������
     * @param array $aData json�����������Ķ���
     * <li>����value���ַ���Ϊutf-8</li>
     * @return void
     */
    public function filterInport(& $aData);
    /**
     * ���������
     * @param array $aData ����
     * <li>����value���ַ���Ϊutf-8</li>
     * @return void
     */
    public function filterOutport(& $aData);
}