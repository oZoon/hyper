<?php
$state = array();

error_reporting(E_ALL);
ini_set('display_errors', 'on');
ini_set('expose_php', 0);
ini_set('request_order', 'EGPCS');
ini_set('variables_order', 'EGPCS');
ini_set('session.use_cookies', 0);
header_remove('x-powered-by');
mb_internal_encoding("UTF-8");

define('DO_SIGN', true);
define('DO_LIST', true);
define('DO_SAVE', true);

$state['mysql'] = new mysqli('localhost', 'hyper', '12345', 'hyper');
$state['allowSymbols'] = '1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
$state['error'] = array('error' => 'bad request');
$state['allowAction'] = array('auth', 'get', 'sign', 'list', 'logout');
$state['length'] = array(6, 20);
$state['timeLife'] = 60;
$state['codeLength'] = 17;
$state['tokenLength'] = 19;
$state['imageNameLength'] = 11;
$state['encodeLength'] = 37;
$state['allowExt'] = array('png', 'jpg', 'gif');
$state['allowMime'] = array('image/gif', 'image/jpeg', 'image/png');

$state['allowParam'] = array(
    'w' => array('type' => 'number', 'min' => 0, 'max' => 20000),
    'h' => array('type' => 'number', 'min' => 0, 'max' => 20000),
    'fm' => array('type' => 'set', 'values' => $state['allowExt']),
    'format' => array('type' => 'set', 'values' => array('raw', 'full', 'regular', 'small', 'thumb', 'meta')),
    'id' => array('type' => 'string', 'length' => $state['imageNameLength'])
);

include_once 'functions.php';
