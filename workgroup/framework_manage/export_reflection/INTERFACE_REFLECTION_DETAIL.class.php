<?php
final class INTERFACE_REFLECTION_DETAIL extends CJsonWebServiceLogicBase implements IJsonWebServiceProtocol{
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
            '00001'=>'无效的 ifs_pkg 参数',
            '00002'=>'无效的 ifs_cls 参数',
            '00010'=>'接口文件不存在',
            '00011'=>'类加载失败',
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
            return $this->setResultState('00001'); // 无效的 ifs_pkg 参数
        }elseif (!isset($aIn['ifs_cls']) || empty($aIn['ifs_cls'])){
            return $this->setResultState('00002'); // 无效的 ifs_cls 参数
        }
        
        if (isset($aIn['return_info'])){
            $sReturnInfo = $aIn['return_info'];
        }
        $sCls = trim($aIn['ifs_cls']);
        //生成类文件地址
        $sClassFile = $oJ->getWorkspace() . str_replace('.', '/', $aIn['ifs_pkg']) .'/'. $sCls . '.class.php';
        $sPkgReadme = $oJ->getWorkspace() . str_replace('.', '/', $aIn['ifs_pkg']) .'/readme.txt';
        
        if (!file_exists($sClassFile)){ //检查接口文件是否存在
            return $this->setResultState('00010'); // 接口文件不存在
        }
        //加载接口文件
        require_once ($sClassFile);
        //检查加载是否成功
        if (!class_exists($sCls)){ //加载了类，但是未找到对应类名称定义
            return $this->setResultState('00011');  //加载完成后，未找到类
        }
        
        //加载接口类
        $o = new $sCls();
        //返回值列表
        if (empty($sReturnInfo) || false !== stripos($sReturnInfo, 'ResultList')){
            $this->o('result_list', self::json_encode($o->getStatus()));
        }
        //接口说明
        if (empty($sReturnInfo) || false !== stripos($sReturnInfo, 'ClassExplain')){
            $this->o('class_explain', $o->getClassExplain() );
        }
        //接口使用注意事项
        if (empty($sReturnInfo) || false !== stripos($sReturnInfo, 'AttentionExplain')){
            $this->o('attention_explain', $o->getAttentionExplain() );
        }
        //输入协议
        if (empty($sReturnInfo) || false !== stripos($sReturnInfo, 'InProtocol')){
            $this->o('in_protocol', self::json_encode($o->getInProtocol()));
        }
        //输出协议
        if (empty($sReturnInfo) || false !== stripos($sReturnInfo, 'OutProtocol')){
            $this->o('out_protocol', self::json_encode($o->getOutProtocol()));
        }
        //输出包说明
        if (empty($sReturnInfo) || false !== stripos($sReturnInfo, 'PackageReadme')){
            $sTmp = file_get_contents($sPkgReadme);
            if (false !== $sTmp){
                $this->o('pkg_readme', $sTmp);
            }
        }
        //输出接口是否过期失效
        if (empty($sReturnInfo) || false !== stripos($sReturnInfo, 'DeadLine')){
           $this->o('dead_line', $o->getDeadline());
        }
        
        
        return $this->setResultState('00000'); //不存在对应的数据
    }
    /**
     * json编码(支持本地字符集的json编码)
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
        return 0; //永不过期;
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getClassExplain()
     */
    public function getClassExplain(){
        return '反射指定接口的详情';
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getAttentionExplain()
     */
    public function getAttentionExplain(){
        return 'return_info 可指定返回那些信息，不给默认返回所有信息；信息获取关键字包含：'. "\n".
                'PackageReadme:包的说明'. "\n".
                'ResultList:初始化返回状态值列表'. "\n".
                'ClassExplain:返回当前API类的使用注意事项介绍'. "\n".
                'AttentionExplain:初始化返回状态值列表'. "\n".
                'InProtocol:接口的输入协议格式'. "\n".
                'OutProtocol:接口的出口协议格式'. "\n".
                'DeadLine:接口模式'. "\n";
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getInProtocol()
     */
    public function getInProtocol(){
        return array(
            'ifs_pkg'=>'包路径 [require | string]',
            'ifs_cls'=>'接口类名 [require | string]',
            'return_info'=>'返回的属性（默认返回所有，多个用,分割） [require | string]'
        );
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getOutProtocol()
     */
    public function getOutProtocol(){
        return array(
            'result_list'=>'初始化返回状态值列表(json) [require | string]',
            'class_explain'=>'返回当前API接口类的功能介绍 [require | string]',
            'attention_explain'=>'初始化返回状态值列表 [require | string]',
            'in_protocol'=>'接口的输入协议格式(json) [require | string]',
            'out_protocol'=>'接口的出口协议格式(json) [require | string]',
            'pkg_readme'=>'包功能说明 [require | string]',
            'dead_line'=>'接口的失效时间 unix timestamp [require | int]',
        );
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getUpdaueLog()
     */
    public function getUpdateLog(){
        return array(
            array('date'=>'2016-11-09', 'name'=>'lijian', 'memo'=>'创建新接口'),
        );
    }
}