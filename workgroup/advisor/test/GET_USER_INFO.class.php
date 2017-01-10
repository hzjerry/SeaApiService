<?php
final class GET_USER_INFO extends CJsonWebServiceLogicBase implements IJsonWebServiceProtocol{
    /**
     * (non-PHPdoc)
     * @see CJsonWebServiceLogicBase::__construct()
     */
    public function __construct(){
        parent::__construct();
//         $this->dontWirteLog(); //通知框架不要记录这个应用日志
        $this->usedTokenCheck(); //开启身份验证规则
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
            '00003'=>'缺少必要参数',
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
        return strtotime('2020-12-30 00:00:00'); //永不过期;
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getClassExplain()
     */
    public function getClassExplain(){
        return '接口的测试用例类，用于演示如何编写接口的实例方式。'."\n\t1、 这个是换行演示\n\t2、再来一次";
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getAttentionExplain()
     */
    public function getAttentionExplain(){
        return '入口参数，name必须提供，否则无法创建用户';
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getInProtocol()
     */
    public function getInProtocol(){
        return array(
            'name'=>'名字 [require | string | min:2 | max:8]',
            'age'=>'年龄 [int | min:1 | max:150]'
        );
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getOutProtocol()
     */
    public function getOutProtocol(){
        return array(
            'name'=>'名字 [require | string | min:2 | max:8]',
            'age'=>'年龄 [int | min:1 | max:150]',
            'user_agent_ver'=>'应用版本号 [string]',
            'user_agent_appname'=>'应用名称[require]',
            'user_agent_client_type'=>'客户端类型 [require | list:iphone,android,webserver,other]',
            'img'=>array(array('site'=>'图位号[require | int]', 'file'=>'图片文件名.jpg[require]')),
        );
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceProtocol::getUpdaueLog()
     */
    public function getUpdateLog(){
        return array(
            array('date'=>'2015-08-12', 'name'=>'lijian', 'memo'=>'创建新接口'),
        );
    }
}