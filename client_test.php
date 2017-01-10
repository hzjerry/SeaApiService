<?php
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('FRAME_PATH', '/SeaApiService/jsonWebService/');
require_once(ROOT_PATH . FRAME_PATH . 'CJsonWebServiceClient.php');

header('Content-Type:text/html; charset=gbk');

$oC = new CJsonWebServiceClient(ROOT_PATH, FRAME_PATH, 'config.json_web_service_client.local.php');

//数据构造
$aData = array(
    'name'=>'李坚alert(\'asdf\');',
    'age'=>50
);
$aRet = $oC->exec('advisor.test', 'GET_USER_INFO', $aData, false);
if (false !== $aRet){
    echo '<pre>', print_r($aRet, true) , '</pre>';
}else{
    echo 'null';
}