<?php
/**
 * JsonWebService�ӿڷ���������ļ�
 * <li>utcʱ������������� strtotime('2015-12-30 00:00:00')</li>
 */
return array(
    /*
     * ǩ���ù�Կ��(deadline=0��ʾ�����ڣ�>0��ʾʧЧ��ʱ��utcʱ���)
     * Ϊ���Ż����ܣ��뾡���ܽ�δʧЧ��key��ǰ�ڷ�
     * <li>ע��: '1'=>array(.... ����1����Ϊ���֣��Ҳ����ظ�������ȷ��ʹ���ĸ���Կ����ǩ����</li>
     */
    'sign_pub_key'  =>  array(
        '1'=>array('key'=>'e911852bcccf3a9f287909bef1868cfb', 'deadline'=>0),
        '2'=>array('key'=>'1717c2b2bc8a71436d26669ae50ed75b', 'deadline'=>strtotime('2015-12-30 00:00:00')), //���Ҫ������ԿʧЧʱ�䣬��������
    ),
    /* package���ʰ�ȫ��Կ���ñ� '_'��ʾ���ڵ�����
     * key:��������Կ | replay_time:ʱ����ط�ʱ��(s) | deadline:ʧЧʱ��(utc time)
     */
    'package_security_pub_key' => array( //�ϳ���ʹ��
/*        
        'advisor'   => array(  //�������ӽڵ�����
            '_'=>null,
            'test' => array( //�����ӽڵ�����
                '_'=>'67833c132b75951d55c454fcfaf69c82',
            ),
        ),
*/
    ),
);