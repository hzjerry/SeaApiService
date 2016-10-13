<?php
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('FRAME_PATH', '/SeaApiService/jsonWebService/');
include(ROOT_PATH . FRAME_PATH . 'CJsonWebServerReflectionView.php');
$oC = new CJsonWebServerReflectionView(ROOT_PATH, FRAME_PATH, ROOT_PATH . FRAME_PATH .'config' );