<?php
final class INTERFACE_REFLECTION_LIST extends CJsonWebServiceLogicBase implements IJsonWebServiceProtocol{
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceLogicBase::__construct()
     */
    public function __construct(){
        parent::__construct();
        $this->dontWirteLog(); //通知框架不要记录这个应用日志
//         $this->usedTokenCheck(); //开启身份验证规则
//         $this->closeDefenseXXS();//关闭XXS攻击过滤
        //请不要后续做其他初始化操作，否则会报错
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
        //例如初始化数据库操作的全局对象
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceLogicBase::initResultList()
     */
    protected function initResultList(){
        return array(
            '00000'=>'处理成功',
            '00010'=>'不存在对应的数据',
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
        return $this->setResultState('00000'); //不存在对应的数据
    }
    /**
     * 获取包接口树（递归）
     * @param string $sRoot 入口根路径
     * @param array $aOutBuf 输出数组
     * @param string $s递归
     */
    private function getTree($sRoot, & $aOutBuf, $sPkg=''){

        if (($aDir = @scandir($sRoot)) === false){
            return false;
        }else{	//取得目录列表
            $aClass = array();
            foreach ($aDir as $sSubDir){
                if ('.' == $sSubDir{0}){
                    continue; //跳过'.'开头的目录名（排除svn目录）
                }elseif (!is_dir($sRoot . $sSubDir)){ //非目录
                    if (substr($sSubDir, -10) === '.class.php'){
                        $sCls = substr($sSubDir, 0, -10); //取出当前包下的接口文件
                        if (__CLASS__ !== $sCls){ //排除自己
                            $aClass[] = $sCls;
                        }
                    }
                }else{	//找到子目录 进入递归
                    $this->getTree(
                        $sRoot . $sSubDir .'/',
                        $aOutBuf,
                        (empty($sPkg) ? $sSubDir : $sPkg .'.'. $sSubDir) //生成包路径
                    ); //进入子目录递归
                }
            }
            if (!empty($aClass)){ //当前包下存在接口
                $aOutBuf[] = array('package'=>$sPkg, 'class'=>$aClass);
            }
        }
    }
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceLogicBase::setDeadline()
     */
    protected function setDeadline(){
        return 0; //永不过期;
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getClassExplain()
     */
    public function getClassExplain(){
        return '反射整个jsonWebService下的所有 package 与 class 接口';
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getAttentionExplain()
     */
    public function getAttentionExplain(){
        return '专用于接口的治理，上层网关可以通过调用这个接口，获得本实例下有哪些可用的接口';
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
                    'package'=>'包路径 [require | string]',
                    'class'=>array('接口文件1 [require | string]', '接口文件2 [require | string]'),
                ),
                array(
                    'package'=>'包路径 [require | string]',
                    'class'=>array('接口文件1 [require | string]', '接口文件2 [require | string]'),
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
            array('date'=>'2016-04-26', 'name'=>'lijian', 'memo'=>'创建新接口'),
        );
    }
}