<?php
final class INTERFACE_REFLECTION_LIST extends CJsonWebServiceLogicBase implements IJsonWebServiceProtocol{
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
            '00010'=>'�����ڶ�Ӧ������',
        );
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceLogicBase::run()
     */
    public function run($aIn){
        global $oJ;
        $sWorkspace = $oJ->getWorkspace();
        $aOutBuf = array();
        $this->getTree($sWorkspace, $aOutBuf);
        $this->o('tree', $aOutBuf);
        return $this->setResultState('00000'); //�����ڶ�Ӧ������
    }
    /**
     * ��ȡ���ӿ������ݹ飩
     * @param string $sRoot ��ڸ�·��
     * @param array $aOutBuf �������
     * @param string $s�ݹ�
     */
    private function getTree($sRoot, & $aOutBuf, $sPkg=''){

        if (($aDir = @scandir($sRoot)) === false){
            return false;
        }else{	//ȡ��Ŀ¼�б�
            $aClass = array();
            foreach ($aDir as $sSubDir){
                if ('.' == $sSubDir{0}){
                    continue; //����'.'��ͷ��Ŀ¼�����ų�svnĿ¼��
                }elseif (!is_dir($sRoot . $sSubDir)){ //��Ŀ¼
                    if (substr($sSubDir, -10) === '.class.php'){
                        $sCls = substr($sSubDir, 0, -10); //ȡ����ǰ���µĽӿ��ļ�
                        if (__CLASS__ !== $sCls){ //�ų��Լ�
                            $aClass[] = $sCls;
                        }
                    }
                }else{	//�ҵ���Ŀ¼ ����ݹ�
                    $this->getTree(
                        $sRoot . $sSubDir .'/',
                        $aOutBuf,
                        (empty($sPkg) ? $sSubDir : $sPkg .'.'. $sSubDir) //���ɰ�·��
                    ); //������Ŀ¼�ݹ�
                }
            }
            if (!empty($aClass)){ //��ǰ���´��ڽӿ�
                $aOutBuf[] = array('package'=>$sPkg, 'class'=>$aClass);
            }
        }
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
        return '��������jsonWebService�µ����� package �� class �ӿ�';
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getAttentionExplain()
     */
    public function getAttentionExplain(){
        return 'ר���ڽӿڵ������ϲ����ؿ���ͨ����������ӿڣ���ñ�ʵ��������Щ���õĽӿ�';
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getInProtocol()
     */
    public function getInProtocol(){
        return array(
        );
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getOutProtocol()
     */
    public function getOutProtocol(){
        return array(
            'tree'=>array(
                array(
                    'package'=>'��·�� [require | string]',
                    'class'=>array('�ӿ��ļ�1 [require | string]', '�ӿ��ļ�2 [require | string]'),
                ),
                array(
                    'package'=>'��·�� [require | string]',
                    'class'=>array('�ӿ��ļ�1 [require | string]', '�ӿ��ļ�2 [require | string]'),
                ),
            ),
        );
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getUpdaueLog()
     */
    public function getUpdateLog(){
        return array(
            array('date'=>'2016-04-26', 'name'=>'lijian', 'memo'=>'�����½ӿ�'),
        );
    }
}