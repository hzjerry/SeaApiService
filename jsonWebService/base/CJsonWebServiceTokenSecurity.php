<?php
/**
 * JsonWebService�ӿڵ�Token��ȫ�鴦�����
 * @author JerryLi
 *
 */
abstract class CJsonWebServiceTokenSecurity{
    /**
     * token�б����������
     * @var array
     * <li>array('domain key'=>'data val',...)</li>
     */
    protected $_aTokenData = null;
    /**
     * API�������״ֵ̬�б�
     * <li>�ṹarray('code'=>'���ֽ���',...)</li>
     * <li>codeԼ��: �������ַ���, Token��֤״̬�뷶Χ950��959</li>
     * @var array
     */
    static public $aResultStateList = array(
        '950'=>'Missing token token parameter.(ȱ��token���Ʋ���)',
        '951'=>'��¼ʧЧ�������µ�¼',
    );
    /**
     * У��Token���Ƶ���Ч��
     * @param string $sToken ����(16�����ַ���)
     * @param string $sPackage ����
     * @param string $sClass ����
     * @return true:ͨ����֤ | string:δͨ��֤
     * <li>���ص�״̬�����ΪCJsonWebServiceTokenSecurityCheck::$_aResultStateList�ж���Ĵ���</li>
     */
    abstract public function checkToken($sToken, $sPackage, $sClass);
    /**
     * ��ȡtoken�б����ֵ
     * <li>������ʹ��checkToken()���а�ȫУ������ͨ����֤�������ʹ�ñ�����ȡ��toke��Ӧ��ֵ</li>
     * @return array() | array( '��key'=>'token���ֵ��ַ���ֵ'), ... )
     */
    abstract public function pullContent();
}