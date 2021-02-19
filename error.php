<?php
include_once 'core/config.php';
header('HTTP/1.1 404');
echo json_encode($state['error']);
exit;
