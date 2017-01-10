<?php
final class INTERFACE_REFLECTION_DETAIL extends CJsonWebServiceLogicBase implements IJsonWebServiceProtocol{
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceLogicBase::__construct()
     */
    public function __construct(){
        parent::__construct();
        $this->dontWirteLog(); //֪ͨ��ܲ�Ҫ��¼���Ӧ����־
//         $this->usedTokenCheck(); //���������֤����
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
            '00001'=>'��Ч�� ifs_pkg ����',
            '00002'=>'��Ч�� ifs_cls ����',
            '00010'=>'�ӿ��ļ�������',
            '00011'=>'�����ʧ��',
        );
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceLogicBase::run()
     */
    public function run($aIn){
        global $oJ;
        $sReturnInfo = '';
        
        if (!isset($aIn['ifs_pkg']) || empty($aIn['ifs_pkg'])){
            return $this->setResultState('00001'); // ��Ч�� ifs_pkg ����
        }elseif (!isset($aIn['ifs_cls']) || empty($aIn['ifs_cls'])){
            return $this->setResultState('00002'); // ��Ч�� ifs_cls ����
        }
        
        if (isset($aIn['return_info'])){
            $sReturnInfo = $aIn['return_info'];
        }
        $sCls = trim($aIn['ifs_cls']);
        //�������ļ���ַ
        $sClassFile = $oJ->getWorkspace() . str_replace('.', '/', $aIn['ifs_pkg']) .'/'. $sCls . '.class.php';
        $sPkgReadme = $oJ->getWorkspace() . str_replace('.', '/', $aIn['ifs_pkg']) .'/readme.txt';
        
        if (!file_exists($sClassFile)){ //���ӿ��ļ��Ƿ����
            return $this->setResultState('00010'); // �ӿ��ļ�������
        }
        //���ؽӿ��ļ�
        require_once ($sClassFile);
        //�������Ƿ�ɹ�
        if (!class_exists($sCls)){ //�������࣬����δ�ҵ���Ӧ�����ƶ���
            return $this->setResultState('00011');  //������ɺ�δ�ҵ���
        }
        
        //���ؽӿ���
        $o = new $sCls();
        //����ֵ�б�
        if (empty($sReturnInfo) || false !== stripos($sReturnInfo, 'ResultList')){
            $this->o('result_list', self::json_encode($o->getStatus()));
        }
        //�ӿ�˵��
        if (empty($sReturnInfo) || false !== stripos($sReturnInfo, 'ClassExplain')){
            $this->o('class_explain', $o->getClassExplain() );
        }
        //�ӿ�ʹ��ע������
        if (empty($sReturnInfo) || false !== stripos($sReturnInfo, 'AttentionExplain')){
            $this->o('attention_explain', $o->getAttentionExplain() );
        }
        //����Э��
        if (empty($sReturnInfo) || false !== stripos($sReturnInfo, 'InProtocol')){
            $this->o('in_protocol', self::json_encode($o->getInProtocol()));
        }
        //���Э��
        if (empty($sReturnInfo) || false !== stripos($sReturnInfo, 'OutProtocol')){
            $this->o('out_protocol', self::json_encode($o->getOutProtocol()));
        }
        //�����˵��
        if (empty($sReturnInfo) || false !== stripos($sReturnInfo, 'PackageReadme')){
            $sTmp = file_get_contents($sPkgReadme);
            if (false !== $sTmp){
                $this->o('pkg_readme', $sTmp);
            }
        }
        //����ӿ��Ƿ����ʧЧ
        if (empty($sReturnInfo) || false !== stripos($sReturnInfo, 'DeadLine')){
           $this->o('dead_line', $o->getDeadline());
        }
        
        
        return $this->setResultState('00000'); //�����ڶ�Ӧ������
    }
    /**
     * json����(֧�ֱ����ַ�����json����)
     * @param unknown $mixd
     * @return Ambigous <string, unknown, multitype:Ambigous <string, unknown> >
     */
    static private function json_encode($mixd){
        $aTmp = JsonWebService::convert_encoding(JsonWebService::LOCAL_CHARSET, 'UTF-8', $mixd);
        $sTmp = JsonWebService::json_encode($aTmp);
        return JsonWebService::convert_encoding('UTF-8', JsonWebService::LOCAL_CHARSET, $sTmp);
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceLogicBase::setDeadline()
     */
    protected function setDeadline(){
        return 0; //��������;
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getClassExplain()
     */
    public function getClassExplain(){
        return '����ָ���ӿڵ�����';
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getAttentionExplain()
     */
    public function getAttentionExplain(){
        return 'return_info ��ָ��������Щ��Ϣ������Ĭ�Ϸ���������Ϣ����Ϣ��ȡ�ؼ��ְ�����'. "\n".
                'PackageReadme:����˵��'. "\n".
                'ResultList:��ʼ������״ֵ̬�б�'. "\n".
                'ClassExplain:���ص�ǰAPI���ʹ��ע���������'. "\n".
                'AttentionExplain:��ʼ������״ֵ̬�б�'. "\n".
                'InProtocol:�ӿڵ�����Э���ʽ'. "\n".
                'OutProtocol:�ӿڵĳ���Э���ʽ'. "\n".
                'DeadLine:�ӿ�ģʽ'. "\n";
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getInProtocol()
     */
    public function getInProtocol(){
        return array(
            'ifs_pkg'=>'��·�� [require | string]',
            'ifs_cls'=>'�ӿ����� [require | string]',
            'return_info'=>'���ص����ԣ�Ĭ�Ϸ������У������,�ָ [require | string]'
        );
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getOutProtocol()
     */
    public function getOutProtocol(){
        return array(
            'result_list'=>'��ʼ������״ֵ̬�б�(json) [require | string]',
            'class_explain'=>'���ص�ǰAPI�ӿ���Ĺ��ܽ��� [require | string]',
            'attention_explain'=>'��ʼ������״ֵ̬�б� [require | string]',
            'in_protocol'=>'�ӿڵ�����Э���ʽ(json) [require | string]',
            'out_protocol'=>'�ӿڵĳ���Э���ʽ(json) [require | string]',
            'pkg_readme'=>'������˵�� [require | string]',
            'dead_line'=>'�ӿڵ�ʧЧʱ�� unix timestamp [require | int]',
        );
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getUpdaueLog()
     */
    public function getUpdateLog(){
        return array(
            array('date'=>'2016-11-09', 'name'=>'lijian', 'memo'=>'�����½ӿ�'),
        );
    }
}