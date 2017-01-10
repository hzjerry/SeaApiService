<?php
/**
 * JsonWebService�ӿ��߼��Ļ���
 * <li>���нӿ��߼�ʵ�ֶ�����̳����������</li>
 * @author JerryLi 2015-09-04
 * @abstract
 *
 */
abstract class CJsonWebServiceLogicBase{
    /**
     * �ӿڵ�ִ�п�ʼʱ��
     * <li>��λ:΢��</li>
     * @var int
     */
    private $_iStartTime = 0;
    /**
     * �ͻ��˵�user_agent_��Ϣ����
     * @var array
     */
    private $_aUserAgentInfo = array();
    /**
     * ���������һ�����õķ���״̬��
     * @var string
     */
    private $_sResultCode = null;
    /**
     * ����Ƿ���Ҫ����token�����֤
     * @var boolean
     */
    private $_bUseTokenCheck = false;
    /**
     * ��ǲ�Ҫ��¼������־
     * @var boolean
     */
    private $_bDontWriteLog = false;

    /**
     * �����JsonWebService��Token������õ���Token������
     * @var array
     * <li>array('������'=>'token����ֵ',...)</li>
     */
    private $_aTokenContent = null;
    /**
     * ����XXS����
     * @var boolean
     */
    private $_bDefenseXXS = true;
    /**
     * ϵͳԤ����ķ���״̬���
     * @var array
     */
    static public $aResultStateList = array(
        '920'=>'The returned value of the unregistered state.(δע��ķ���״ֵ̬)',
        '921'=>'API interface services no output result set.(api�ӿڷ�������������)',
    );
    /**
     * ���ؽ����������
     * @var array
     */
    private $_aResultData = array();
    /**
     * ���캯��
     * <li>ע�⣺�����в�Ҫ����ʼ������<li>
     * @example<pre>
        public function __construct(){
            parent::__construct();
            $this->usedTokenCheck(); //���������֤����
            //�벻Ҫ������������ʼ������������ᱨ��
        }
     * </pre>
     */
    public function __construct(){
        $this->_iStartTime = microtime(true); //��¼��ʼʱ��
        $this->_resloveUserAgentInfo(); //����HttpUserAgentͷ
        //�ϲ�Ӧ�ü�״̬����
        foreach ($this->initResultList() as $sKey=>$sVal){
            self::$aResultStateList[$sKey] = $sVal;
        }
    }
    /**
     * ��������
     */
    public function __destruct(){
        //TODO:�����������
    }
    /**
     * �Ƿ�������XXS����
     * @return boolean
     */
    public function isDefenseXXS(){
        return $this->_bDefenseXXS;
    }
    /**
     * ��Ӧ���߼���ر�XXS������������
     * <li>ע�Ȿ����һ��Ҫ�̳���Ĺ��캯����ʹ�ã�������Ч</li>
     * @return void
     */
    protected function closeDefenseXXS(){
        $this->_bDefenseXXS = false;
    }
    /**
     * �жϽӿ��Ƿ��Ѿ��ϳ�
     * @return boolean true:�ӿ�δʧЧ | false:�ӿ���ʧЧֹͣ�������
     */
    public function isDead(){
        $iUTC = $this->setDeadline();
        if ($iUTC <= 0){
            return false;
        }else{
           if ($iUTC < time()){
               return true; //�Ѿ�����
           }else{
               return false; //δʧЧ����ʹ��
           }
        }
    }
    /**
     * ��ȡ�ӿڵ�ʧЧʱ��
     * @return int utc_timestemp
     */
    public function getDeadline(){
        return $this->setDeadline();
    }
    /**
     * ���÷���ֵ
     * <li>ע�⣺������������ֹ���������ִ�С�</li>
     * <li>����׳�״̬���Ҫ������������������ return $this->setResultState('00000')</li>
     * @param string $sCode
     * @return boolean false:�޷�ʶ��ķ���״ֵ̬ | true:��ʶ��״̬��
     */
    public function setResultState($sCode){
        if (in_array($sCode, array_keys(self::$aResultStateList))){
            $this->_sResultCode = $sCode;
            return true;
        }else{ //δע��ķ���״ֵ̬
            $this->_sResultCode = '920';
            return false;
        }
    }
    /**
     * ��ȡ�ͻ��˵�HTTP_USER_AGENT��Ϣ����
     * <li>���밴��HTTPЭ��淶�ͳ�HTTP_USER_AGENT�����ҷ���JSW�Ĺ淶���ܽ�����HTTP_USER_AGENT�еĲ���</li>
     * <li>HTTP_USER_AGENT�淶: [appname]/[ver] ([system info])</li>
     * <li>[appname]:Ӧ������</li>
     * <li>[ver]:�汾�ţ���ʽ��x.x.x ��xΪ������</li>
     * <li>[system info]��ϵͳ��Ϣ�������iphone����android�������[iphone|android]����</li>
     * @return array('ver'=>array(x,x,x)�汾������, 'appname'=>'Ӧ������', 'client'=>'�ͻ�������[iphone|android|webserver|other]')
     */
    private function _resloveUserAgentInfo(){
        if (class_exists('JsonWebService')){ //cliʱ�������JsonWebService��
            $this->_aUserAgentInfo = JsonWebService::resloveUserAgentInfo();
        }
    }
    /**
     * �ͻ��˰汾��
     * @return array(x,x,x)
     */
    protected function getClientVer(){
        if (isset($this->_aUserAgentInfo['ver'])){
            return $this->_aUserAgentInfo['ver'];
        }else{
            return array();
        }
    }
    /**
     * �ͻ�������
     * @return string:
     */
    protected function getClientAppname(){
        if (isset($this->_aUserAgentInfo['appname'])){
            return $this->_aUserAgentInfo['appname'];
        }else{
            return '';
        }
    }
    /**
     * ��ȡ�ͻ�������
     * @return string [iphone | android | webserver | other]
     */
    protected function getClientType(){
        if (isset($this->_aUserAgentInfo['client'])){
            return $this->_aUserAgentInfo['client'];
        }else{
            return '';
        }
    }
    /**
     * ��������ṹ������
     * @return array
     */
    public function getResult(){
        if (empty($this->_sResultCode)){
            $this->_sResultCode = '921'; //����������
        }elseif (!isset(self::$aResultStateList[$this->_sResultCode])){
            $this->_sResultCode = '920'; //δע���״̬��
        }
        $aStatus = array('status'=>array('code'=>$this->_sResultCode,
            'msg'=>self::$aResultStateList[$this->_sResultCode],
            'runtime'=>sprintf('%.4f', (microtime(true) - $this->_iStartTime) * 1000) ));
        return array_merge(array('result'=>$this->_aResultData), $aStatus);
    }
    /**
     * ��ȡAPI�Զ����״ֵ̬
     * @return array
     */
    public function getStatus(){
        return $this->initResultList();
    }
    /**
     * ��ȡ�Ƿ��tokenУ��
     * @return boolean
     */
    public function getTokenCheckStatus(){
        return $this->_bUseTokenCheck;
    }
    /**
     * ��ȡ�Ƿ�Ҫ�ر���־
     * @return void
     */
    public function getDoNotWirteLog(){
        return $this->_bDontWriteLog;
    }
    /**
     * �������ֵ
     * @param string $sKey �ؼ���
     * @param string | array $aVal ֵ
     * @return void
     */
    protected function o($sKey, $aVal){
        $this->_aResultData[$sKey] = $aVal;
    }
    /**
     * ����token�����֤����
     * <li>�ڽӿڵ�ҵ���߼��п���Token�����֤</li>
     * <li><strong>ע��</strong>������__construct()���캯���е��ã������޷�����У��</li>
     * @return void
     */
    protected function usedTokenCheck(){
        $this->_bUseTokenCheck = true;
    }
    /**
     * �ر���־��¼
     * <li>���ú�ϵͳ�������¼������־</li>
     * @return void
     */
    protected function dontWirteLog(){
        $this->_bDontWriteLog = true;
    }
    /**
     * ����token��ȡ�õ�session��Ϣ
     * @param string $sStr
     * @return void
     */
    public function setTokenContent($sStr){
        $this->_aTokenContent = $sStr;
    }
    /**
     * ��ȡtoken�ж�Ӧ�ĻỰ������Ϣ
     * @return array | null
     * <li>array('������'=>'token����ֵ',...)</li>
     */
    protected function getTokenContent(){
        return $this->_aTokenContent;
    }
    /**
     * ��ʼ������״ֵ̬�б�
     * <li>ע�⣺�������ʵ��������������Ӧ�ü������״ֵ̬����</li>
     * <li>���飺Ӧ�ü��ķ��ش���Ϊ '00000'~'99999'�����ĸ�ʽ���Ա���ͳһ�淶</li>
     * @abstract
     * @return array('code1'=>'msg2', 'code2'=>'msg2', ...)
     */
    abstract protected function initResultList();
    /**
     * �ӿڵ����ִ�з���
     * <li>ע�⣺�������ʵ��������������ӿڱ�����ʱ��Ĭ�ϻ�ִ���������</li>
     * <li>�������run����ǰ���أ�ֱ��ʹ��return;</li>
     * @abstract
     * @param array $aIn �ӿڵ����(jsonӳ����������)
     * @return void
     */
    abstract public function run($aIn);
    /**
     * �ӿڵ�ʧЧʱ��
     * @abstract
     * @return int 0:�������� | >0 �ӿ�ʧЧ��ʱ��(utcʱ���,��λ����)
     * <li>utcʱ������������� strtotime('2015-12-30 00:00:00')</li>
     */
    abstract protected function setDeadline();
    /**
     * ���ʼ������
     * <li>�������Ӧ�õĳ�ʼ�����������������������ɣ���Ҫ�ڹ��캯��������ʼ������</li>
     * @abstract
     * @return void
     */
    abstract public function init();
}