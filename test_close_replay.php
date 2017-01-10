<?php
require_once(ROOT_PATH . FRAME_PATH . 'interface/IJsonWebServiceCloseReplay.php');
/**
 * 模拟的截止回放
 * <li>数据存储在memcached中</li>
 * @author JerryLi
 *
 */
final class TestCloseReplay implements IJsonWebServiceCloseReplay{
    /**
     * 关键字隔离头
     * @var string
     */
    const KEY_HEAD = 'JWS_CR_';
    /**
     * 截止重放的保持时间（秒）
     * @var int
     */
    const REPLAY_INTERCEPTION = 900;
    /**
     * Memcache缓存对象
     * @var Memcache
     */
    private static $moMC = null;
    /**
     * 构造函数
     */
    public function __construct(){
        if (is_null(self::$moMC)){
            if (!class_exists('Memcache')){
                echo '<br />Not find class Memcache. <br/>Please install php memcache plugin.';
                exit(0);
            }else{
                self::$moMC = new Memcache();
                if (!self::$moMC->connect('127.0.0.1', 11211, 5))
                    self::$moMC = null; //连接memcached失败
            }
        }
    }
    /**
     * 析构函数
     */
    public function __destruct()
    {
        self::$moMC->close();
        self::$moMC = null;
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceCloseReplay::getReplayInterceptionSecond()
     */
    public function getReplayInterceptionSecond(){
        return self::REPLAY_INTERCEPTION;
    }
    /**
     * (non-PHPdoc)
     * @see IJsonWebServiceCloseReplay::checkReplay()
     */
    public function checkReplay($sSignKey){
        if (is_null(self::$moMC)){
            return false; //mmemcached缓存服务不存在
        }else{
            if (is_null($this->_get(self::KEY_HEAD . $sSignKey))){ //签名不存在，建立缓存
                $this->_set(self::KEY_HEAD . $sSignKey, 1, self::REPLAY_INTERCEPTION);
                return false; //mmemcached注册签名
            }else{ //发现签名存在（判为重放）
                return true; //识别到一次重放
            }
        }
    }
    /**
     *  获取缓存
     */
    private function _get($sKey){
        if (($mVal = self::$moMC->get($sKey)) === false){
            return null;
        }else{
          return $mVal;
        }
    }
    
    /**
     *  设置缓存
     */
    private function _set($sKey, $mVal, $iExpire = null){
        $compress = is_bool($mVal) || is_int($mVal) || is_float($mVal) ? false : MEMCACHE_COMPRESSED;
        return self::$moMC->set($sKey, $mVal, $compress, $iExpire);
    }
}