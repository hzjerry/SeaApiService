<?php
/**
 * JsonWebService�ӿڷ���ϵͳ��־����ӿ�
 * <li>�����¼��������ʱ���ڴ���������ceil(memory_get_peak_usage()/1000) =>kb</li>
 * <li>����ͻ�������HTTP_USER_AGENT��������־��ʶ�������</li>
 * @author JerryLi 2015-09-04
 * @example ��־����
 * 1:createLog(data);
 */
interface IJsonWebServiceLog{
    /**
     * ������־
     * @param array $aParam �������־����
     * <li>array('in'=>'�������', 'out'=>'��������', 'pkg'=>'��·����Ϣ', 'cls'=>'�ӿ�����Ϣ', 'status_code'=>'״̬��',
     *     'step'=>'�׶�[receive:���յ����� | resolve:Json�����ɹ����� | reply:�ӿ������ظ� | app_err:Ӧ�ô���]',
     *     'runtime'=>'����ʱ��ms', 'sign'=>'bodyǩ��')</li>
     * @return void
     */
    public function createLog($aParam);
}