<?php
final class GET_USER_INFO extends CJsonWebServiceLogicBase implements IJsonWebServiceProtocol{
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceLogicBase::__construct()
     */
    public function __construct(){
        parent::__construct();
//         $this->dontWirteLog(); //֪ͨ��ܲ�Ҫ��¼���Ӧ����־
        $this->usedTokenCheck(); //���������֤����
//         $this->closeDefenseXXS();//�ر�XXS��������
        //�벻Ҫ������������ʼ������������ᱨ��
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceLogicBase::__destruct()
     */
    public function __destruct(){
        parent::__destruct();
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceLogicBase::__destruct()
     */
    public function init(){
        //�����ʼ�����ݿ������ȫ�ֶ���
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceLogicBase::initResultList()
     */
    protected function initResultList(){
        return array(
            '00000'=>'����ɹ�',
            '00003'=>'ȱ�ٱ�Ҫ����',
        );
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceLogicBase::run()
     */
    public function run($aIn){
        if (!isset($aIn['name'])){
            return $this->setResultState('00003');
        }
        $this->o('name', $aIn['name']);
        if (isset($aIn['age']) && intval($aIn['age']) > 0){
            $this->o('age', $aIn['age']);
        }
        $this->o('user_agent_ver', implode('.', $this->getClientVer()));
        $this->o('user_agent_appname', $this->getClientAppname());
        $this->o('user_agent_client_type', $this->getClientType());
        $this->o('img', array(array('site'=>1, 'file'=>'qwert.jpg'), array('site'=>2, 'file'=>'dftty.jpg') ));
        return $this->setResultState('00000');
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceLogicBase::setDeadline()
     */
    protected function setDeadline(){
        return strtotime('2020-12-30 00:00:00'); //��������;
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getClassExplain()
     */
    public function getClassExplain(){
        return '�ӿڵĲ��������࣬������ʾ��α�д�ӿڵ�ʵ����ʽ��'."\n\t1�� ����ǻ�����ʾ\n\t2������һ��";
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getAttentionExplain()
     */
    public function getAttentionExplain(){
        return '��ڲ�����name�����ṩ�������޷������û�';
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getInProtocol()
     */
    public function getInProtocol(){
        return array(
            'name'=>'���� [require | string | min:2 | max:8]',
            'age'=>'���� [int | min:1 | max:150]'
        );
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getOutProtocol()
     */
    public function getOutProtocol(){
        return array(
            'name'=>'���� [require | string | min:2 | max:8]',
            'age'=>'���� [int | min:1 | max:150]',
            'user_agent_ver'=>'Ӧ�ð汾�� [string]',
            'user_agent_appname'=>'Ӧ������[require]',
            'user_agent_client_type'=>'�ͻ������� [require | list:iphone,android,webserver,other]',
            'img'=>array(array('site'=>'ͼλ��[require | int]', 'file'=>'ͼƬ�ļ���.jpg[require]')),
        );
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getUpdaueLog()
     */
    public function getUpdateLog(){
        return array(
            array('date'=>'2015-08-12', 'name'=>'lijian', 'memo'=>'�����½ӿ�'),
        );
    }
}