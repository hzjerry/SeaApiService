<?php
/**
 * JsonWebService�ӿڷ���Ԥ����
 * <li>�ڽ���Ӧ�ò�֮ǰ��ÿ��package�Ķ����Ŀ¼�����Ҫ�ɴ���ApiPretreatment.php���ļ�������ΪApiPretreatment(ע���Сд)��
 *     ϵͳ���ִ��ڴ��ļ�ʱ����ִ������ļ��ӿ��е�init������Ȼ��Ż��ߵ�Ӧ�ò�
 * </li>
 * <li>������ļ��п��ԶԽӿڵ���ڷ����������Ԥ���������USER_AGENT�еİ汾���������Ĵ���</li>
 * @author JerryLi
 *
 */
interface IJsonWebServiceVisitPretreatment{
    /**
     * ����Ԥ����
     * @param array $aInJson json�����������Ķ���
     * <li>��Ϊ���÷��ʣ����Ҫ����ֵ��Ӧ�ò����ֱ���޸�$aInJson��ֵ</li>
     * @return boolean true:���ִ��(�ӿڷ���905״̬) | false:�ɼ���ִ�У�����Ӧ�ò㣩
     */
    public function toDo(& $aInJson);
}