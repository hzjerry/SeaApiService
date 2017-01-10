<?php
require_once(ROOT_PATH . FRAME_PATH . 'interface/IJsonWebServiceCloseReplay.php');
/**
 * ģ��Ľ�ֹ�ط�
 * <li>���ݴ洢��memcached��</li>
 * @author JerryLi
 *
 */
final class TestCloseReplay implements IJsonWebServiceCloseReplay{
    /**
     * �ؼ��ָ���ͷ
     * @var string
     */
    const KEY_HEAD = 'JWS_CR_';
    /**
     * ��ֹ�طŵı���ʱ�䣨�룩
     * @var int
     */
    const REPLAY_INTERCEPTION = 900;
    /**
     * Memcache�������
     * @var Memcache
     */
    private static $moMC = null;
    /**
     * ���캯��
     */
    public function __construct(){
        if (is_null(self::$moMC)){
            if (!class_exists('Memcache')){
                echo '<br />Not find class Memcache. <br/>Please install php memcache plugin.';
                exit(0);
            }else{
                self::$moMC = new Memcache();
                if (!self::$moMC->connect('127.0.0.1', 11211, 5))
                    self::$moMC = null; //����memcachedʧ��
            }
        }
    }
    /**
     * ��������
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
            return false; //mmemcached������񲻴���
        }else{
            if (is_null($this->_get(self::KEY_HEAD . $sSignKey))){ //ǩ�������ڣ���������
                $this->_set(self::KEY_HEAD . $sSignKey, 1, self::REPLAY_INTERCEPTION);
                return false; //mmemcachedע��ǩ��
            }else{ //����ǩ�����ڣ���Ϊ�طţ�
                return true; //ʶ��һ���ط�
            }
        }
    }
    /**
     *  ��ȡ����
     */
    private function _get($sKey){
        if (($mVal = self::$moMC->get($sKey)) === false){
            return null;
        }else{
          return $mVal;
        }
    }
    
    /**
     *  ���û���
     */
    private function _set($sKey, $mVal, $iExpire = null){
        $compress = is_bool($mVal) || is_int($mVal) || is_float($mVal) ? false : MEMCACHE_COMPRESSED;
        return self::$moMC->set($sKey, $mVal, $compress, $iExpire);
    }
}