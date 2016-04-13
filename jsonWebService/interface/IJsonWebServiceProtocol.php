<?php
/**
 * JsonWebService�ӵĿ�Э�鷴��ӿ�
 * @author JerryLi 2015-09-04
 *
 */
interface IJsonWebServiceProtocol{
    /**
     * ���ص�ǰAPI�ӿ���Ĺ��ܽ���
     * <li>֧��"\n"������ʾ</li>
     * @return string
     */
    public function getClassExplain();
    /**
     * ���ص�ǰAPI���ʹ��ע���������
     * <li>֧��"\n"������ʾ</li>
     * @return null | string
     */
    public function getAttentionExplain();
    /**
     * �ӿڵ�����Э���ʽ
     * <li>���Ϊ����ṹ</li>
     * <li>Լ���淶�����͹ؼ��֣�string, int, long, double</li>
     * <li>Լ���淶����Χ�ؼ��֣�max:n, min:n, fixed:n, list:xxx,xxx,xxx</li>
     * <li>Լ���淶������ؼ��֣�require</li>
     * @return array
     */
    public function getInProtocol();
    /**
     * �ӿڵĳ���Э���ʽ
     * <li>���Ϊ����ṹ</li>
     * <li>Լ���淶�����͹ؼ��֣�string, int, long, double</li>
     * <li>Լ���淶����Χ�ؼ��֣�max:n, min:n, fixed:n, list:xxx,xxx,xxx</li>
     * <li>Լ���淶������ؼ��֣�require</li>
     * @return array
     */
    public function getOutProtocol();
    /**
     * �ӿڸ�����־
     * @return array array(array('date'=>'�ӿڸ���ʱ��', 'name'=>'�ӿڸ�����', 'memo'=>'�ӿڸ��µ�����'),...)
     */
    public function getUpdateLog();
}