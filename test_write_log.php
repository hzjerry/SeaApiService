<?php
include(ROOT_PATH . FRAME_PATH . 'interface/IJsonWebServiceLog.php');
/**
 * 模拟的测试用例，实现日志接口
 * <li>暂时临时保存在本地</li>
 * <li>根据需要可以编写复杂的日志存储逻辑</li>
 * @author JerryLi
 *
 */
final class TestWirteLog implements IJsonWebServiceLog{
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceLog::createLog()
     */
    public function createLog($aParam){
        $aParam['ip'] = implode('.', JsonWebService::real_ip()); //访问者IP
        $aParam['memory'] = ceil(memory_get_peak_usage()/1000); //kb
        $aParam['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $aParam['date'] = date('Y-m-d H:i:s'); //kb
        if (($hf = fopen(ROOT_PATH . '/SeaApiService/JsonWebServiceLog.log', 'ab')) !== false){
            if (!fwrite($hf, print_r($aParam, true) ."\n")){	//日志写入失败
               //日志写入失败，不做处理
            }
            fclose($hf); //日志成功写入
        }
    }
}