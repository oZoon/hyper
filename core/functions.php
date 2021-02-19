<?php

///////////////////////////////////////////////////////////////////////////////
// базовые функции ////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////

function request(string &$query)
{
    global $state;
    switch (explode(' ', $query)[0]) {
        case 'SELECT':
            $result = array();
            $qr = $state['mysql']->query($query);
            $result['numRows'] = $qr->num_rows;
            $result['fetchAssoc'] = $qr->fetch_assoc();
            return $result;
        break;
        default:
            $state['mysql']->query($query);
        break;
    }
}

function checkSymbols(string &$checking)
{
    global $state;
    $available = str_split($checking);
    $allow = str_split($state['allowSymbols']);
    $result = true;
    for ($i = 0; $i <= count($available) - 1; $i++) {
        if (!in_array($available[$i], $allow)) {
            $result = false;
            break;
        }
    }
    return $result;
}

function checkLength(string &$checking)
{
    global $state;
    $result = true;
    if (
        $state['length'][0] > mb_strlen($checking) ||
        mb_strlen($checking) > $state['length'][1]
    ) {
        $result = false;
    }
    return $result;
}

function parseQS(string &$source)
{
    global $state;
    $pairs = explode('&', $source);
    for ($i = 0; $i <= count($pairs) - 1; $i++) {
        $pair = explode('=', $pairs[$i]);
        if (!isset($pair[1])) {
            $pair[1] = '';
        }
        if (!isset($pair[0])) {
            $pair[0] = 'error';
        }
        $state['qs'][$pair[0]] = $pair[1];
    }
}

function getImageName(string &$filename)
{
    return pathinfo('./img/' . $filename)['filename'];
}

function checkImageName()
{
    global $state;
    $state['imageList'] = dirToArray('./img');
    for ($i = 0; $i <= count($state['imageList']) - 1; $i++) {
        $filename = getImageName($state['imageList'][$i]);
        $ext = pathinfo('./img/' . $state['imageList'][$i])['extension'];
        if (!checkSymbols($filename) || mb_strlen($filename) != $state['imageNameLength']) {
            $newName = randomString($state['imageNameLength']);
            rename('./img/' . $state['imageList'][$i] . '.' . $ext, './img/' . $newName . '.' . $ext);
            $state['imageList'][$i] = $newName . '.' . $ext;
        }
    }
}

function init()
{
    global $state;
    $state['qs'] = array();
    $state['userId'] = null;
    $state['response'] = array();
    $query = 'set @@session.time_zone = \'+00:00\'';
    request($query);
    checkImageName();
}

function doSendJson()
{
    global $state;
    header('HTTP/1.1 200 Ok');
    header('Content-Type: application/json');
    echo json_encode($state['response']);
    exit;
}

function randomString(&$length)
{
    global $state;
    $result = '';
    $allow = str_split($state['allowSymbols']);
    $randomLength = mt_rand(($length + 6), ($length + 36));
    for ($i = 0; $i < $randomLength; $i++) {
        $result = $result.$allow[mt_rand(0, (count($allow) - 1))];
    }
    return substr(str_shuffle($result), 0, $length);
}

function dirToArray($dir)
{
    global $state;
    $result = array();
    $cdir = scandir($dir);
    foreach ($cdir as $key => $value) {
        pathinfo($dir . DIRECTORY_SEPARATOR . $value)['extension'];
        if (
            !in_array($value, array('.', '..')) &&
            !is_dir($dir . DIRECTORY_SEPARATOR . $value) &&
            in_array(pathinfo($dir . DIRECTORY_SEPARATOR . $value)['extension'], $state['allowExt']) &&
            in_array(getimagesize($dir . DIRECTORY_SEPARATOR . $value)['mime'], $state['allowMime'])
            ) {
            $result[] = $value;
        }
    }
    return $result;
}

function checkToken()
{
    global $state;
    $query = 'SELECT `userId` FROM `tokens` WHERE `token` = \'' . $state['qs']['token'] . '\' LIMIT 1';
    $queryResult = request($query);
    if ($queryResult['numRows'] == 1) {
        return true;
    } else {
        return false;
    }
}

function checkImageId()
{
    global $state;
    for ($i = 0; $i <= count($state['imageList']) - 1; $i++) {
        if ($state['qs']['id'] == getImageName($state['imageList'][$i])) {
            return true;
        }
    }
    return false;
}

function encode(string &$decode)
{
    global $state;
    $query = 'SELECT `encode` FROM `secure` WHERE `decode` = \'' . $decode . '\' LIMIT 1';
    $queryResult = request($query);
    if ($queryResult['numRows'] == 1) {
        return $queryResult['fetchAssoc']['encode'];
    } else {
        $encode = randomString($state['encodeLength']);
        $query = 'INSERT INTO `secure`(`encode`, `decode`) VALUES (\'' . $encode . '\', \'' . $decode . '\')';
        request($query);
        return $encode;
    }
}

function decode(string &$encode)
{
    global $state;
    $query = 'SELECT `decode` FROM `secure` WHERE `encode` = \'' . $encode . '\' LIMIT 1';
    $queryResult = request($query);
    if ($queryResult['numRows'] == 1) {
        return $queryResult['fetchAssoc']['decode'];
    } else {
        return false;
    }
}

function getExif()
{
    global $state;
    for ($i = 0; $i <= count($state['imageList']) - 1; $i++) {
        if ($state['qs']['id'] == getImageName($state['imageList'][$i])) {
            return exif_read_data('./img/' . $state['imageList'][$i], 0, true);
        }
    }
}

function getImageMeta()
{
    global $state;
    $exif = getExif();
    $state['response']['id'] = $state['qs']['id'];
    $state['response']['fileName'] = $state['imageList'][$i];
    $state['response']['fileDateTime'] = $exif['FILE']['FileDateTime'];
    $state['response']['fileSize'] = $exif['FILE']['FileSize'];
    $state['response']['width'] = $exif['COMPUTED']['Width'];
    $state['response']['height'] = $exif['COMPUTED']['Height'];
    doSendJson();
}

function checkParameters(array &$array)
{
    global $state;
    foreach ($array as $key => $value) {
        if (!in_array($key, array_keys($state['allowParam']))) {
            return false;
        }
        switch ($state['allowParam'][$key]['type']) {
            case 'number':
                if ($state['allowParam'][$key]['min'] > $value || $value > $state['allowParam'][$key]['max']) {
                    return false;
                }
            break;
            case 'set':
                if (!in_array($value, $state['allowParam'][$key]['values'])) {
                    return false;
                }
            break;
            case 'string':
                if (mb_strlen($value) != $state['allowParam'][$key]['length']) {
                    return false;
                }
                if (!checkSymbols($value)) {
                    return false;
                    ;
                }
            break;
        }
    }
    return 'true';
}

function makeParameters()
{
    global $state;
    $state['exif'] = getExif();
    $state['image'] = array();
    switch ($state['qs']['format']) {
        case 'raw':
            if (isset($state['qs']['w']) || isset($state['qs']['h'])) {
                $state['image']['fm'] = (isset($state['qs']['fm'])) ? $state['qs']['fm'] : 'jpg';
                if (isset($state['qs']['w'])) {
                    $state['image']['w'] = $state['qs']['w'];
                    $state['image']['h'] = round($state['image']['w'] * $state['exif']['COMPUTED']['Height'] / $state['exif']['COMPUTED']['Width']);
                }
                if (!isset($state['qs']['w']) && isset($state['qs']['h'])) {
                    $state['image']['h'] = $state['qs']['h'];
                    $state['image']['w'] = round($state['image']['h'] * $state['exif']['COMPUTED']['Width'] / $state['exif']['COMPUTED']['Height']);
                }
            } else {
                include_once './error.php';
            }
        break;
        case 'full':
            $state['image']['fm'] =  'jpg';
            $state['image']['w'] = $state['exif']['COMPUTED']['Width'];
            $state['image']['h'] = $state['exif']['COMPUTED']['Height'];
        break;
        case 'regular':
            $state['image']['fm'] =  'jpg';
            $state['image']['w'] = 1080;
            $state['image']['h'] = round($state['image']['w'] * $state['exif']['COMPUTED']['Height'] / $state['exif']['COMPUTED']['Width']);
        break;
        case 'small':
            $state['image']['fm'] =  'jpg';
            $state['image']['w'] = 400;
            $state['image']['h'] = round($state['image']['w'] * $state['exif']['COMPUTED']['Height'] / $state['exif']['COMPUTED']['Width']);
        break;
        case 'thumb':
            $state['image']['fm'] =  'jpg';
            $state['image']['w'] = 200;
            $state['image']['h'] = round($state['image']['w'] * $state['exif']['COMPUTED']['Height'] / $state['exif']['COMPUTED']['Width']);
        break;
    }
}

function makeImage()
{
    global $state;
    switch (pathinfo('./img/' . $state['exif']['FILE']['FileName'])['extension']) {
        case 'jpg':
            $state['image']['resource'] = imageCreateFromJpeg('./img/' . $state['exif']['FILE']['FileName']);
        break;
        case 'png':
            $state['image']['resource'] = imageCreateFromPng('./img/' . $state['exif']['FILE']['FileName']);
            imageSaveAlpha($state['image']['resource'], true);
        break;
        case 'gif':
            $state['image']['resource'] = imageCreateFromGif('./img/' . $state['exif']['FILE']['FileName']);
            imageSaveAlpha($state['image']['resource'], true);
        break;
    }
    $state['image']['result'] = imageCreateTrueColor($state['image']['w'], $state['image']['h']);
    if (
        pathinfo('./img/' . $state['exif']['FILE']['FileName'])['extension'] == 'png' ||
        pathinfo('./img/' . $state['exif']['FILE']['FileName'])['extension'] == 'gif'
        ) {
        imagealphablending($state['image']['result'], true);
        imageSaveAlpha($state['image']['result'], true);
        $transparent = imagecolorallocatealpha($state['image']['result'], 0, 0, 0, 127);
        imagefill($state['image']['result'], 0, 0, $transparent);
        imagecolortransparent($state['image']['result'], $transparent);
    }
    $tw = ceil($state['image']['h'] / ($state['exif']['COMPUTED']['Height'] / $state['exif']['COMPUTED']['Width']));
    $th = ceil($state['image']['w'] / ($state['exif']['COMPUTED']['Width'] / $state['exif']['COMPUTED']['Height']));
    if ($tw < $state['image']['w']) {
        imageCopyResampled($state['image']['result'], $state['image']['resource'], ceil(($state['image']['w'] - $tw) / 2), 0, 0, 0, $tw, $state['image']['h'], $state['exif']['COMPUTED']['Width'], $state['exif']['COMPUTED']['Height']);
    } else {
        imageCopyResampled($state['image']['result'], $state['image']['resource'], 0, ceil(($state['image']['h'] - $th) / 2), 0, 0, $state['image']['w'], $th, $state['exif']['COMPUTED']['Width'], $state['exif']['COMPUTED']['Height']);
    }
}

function doSendImage()
{
    global $state;
    switch ($state['image']['fm']) {
        case 'gif':
            header('Content-Type: image/gif');
            imageGif($state['image']['result']);
            break;
        case 'jpg':
            header('Content-Type: image/jpeg');
            imageJpeg($state['image']['result'], null, 100);
            break;
        case 'png':
            header('Content-Type: image/png');
            imagePng($state['image']['result']);
            break;
    }
    imagedestroy($state['image']['result']);
    exit;
}







///////////////////////////////////////////////////////////////////////////////
// основные функции ///////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////

/**
 * парсинг всех входящих параметров
 * сохранение параметров в глобальной переменной $state['qs']
 * или ошибка
 */
function doParseRequest()
{
    global $state;
    $state['action'] = substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 1);
    if (in_array($state['action'], $state['allowAction'])) {
        parseQS($_SERVER['QUERY_STRING']);
        return;
    }
    $state['encode'] = substr($_SERVER['QUERY_STRING'], 1);
    if (
        mb_strlen($state['encode']) == $state['encodeLength'] &&
        checkSymbols($state['encode'])
        ) {
        $query = 'SELECT `decode` FROM `secure` WHERE `encode` = \'' . $state['encode'] . '\' LIMIT 1';
        $queryResult = request($query);
        if ($queryResult['numRows'] == 1) {
            parseQS($queryResult['fetchAssoc']['decode']);
            return;
        }
    }
    include_once './error.php';
}

/**
 * регистрация нового пользователя
 * без детальной проверки каждого входящего параметра
 * и последующей отправки подробных ошибок -
 * неверная длина логина/пароля, недопустимые символы в логине/пароле, такой логин уже есть и проч.
 * ответ простой - result=true или result=false (успех или ошибка)
 * или ошибка
 */
function doSign()
{
    global $state;
    if (
        isset($state['action']) && $state['action'] == 'sign' &&
        isset($state['qs']['l']) && checkSymbols($state['qs']['l']) &&
        isset($state['qs']['p']) && checkSymbols($state['qs']['p']) &&
        checkLength($state['qs']['l']) && checkLength($state['qs']['p'])
        ) {
        if (DO_SIGN) {
            $state['response']['result'] = false;
            $query = 'SELECT `id` FROM `users` WHERE `login` = \'' . $state['qs']['l'] . '\' LIMIT 1';
            $queryResult = request($query);
            if ($queryResult['numRows'] == 0) {
                $password = password_hash($state['qs']['p'], PASSWORD_DEFAULT);
                $query = 'INSERT INTO `users`(`login`, `password`) VALUES (\'' . $state['qs']['l'] . '\', \'' . $password . '\')';
                request($query);
                $query = 'SELECT `id` FROM `users` WHERE `login` = \'' . $state['qs']['l'] . '\' LIMIT 1';
                $queryResult = request($query);
                if ($queryResult['numRows'] == 1) {
                    $state['response']['result'] = true;
                    doSendJson();
                }
            }
        }
        include_once './error.php';
    }
}

/**
 * идентификация и авторизация пользователя
 * в ответе промежуточный параметр code=[0-9a-zA-Z]{17}
 * или ошибка
 */
function doLogin()
{
    global $state;
    if (
        isset($state['action']) && $state['action'] == 'auth' &&
        isset($state['qs']['l']) && checkSymbols($state['qs']['l']) &&
        isset($state['qs']['p']) && checkSymbols($state['qs']['p']) &&
        is_null($state['userId'])
        ) {
        $query = 'SELECT `id`, `password` FROM `users` WHERE `login` = \'' . $state['qs']['l'] . '\' LIMIT 1';
        $queryResult = request($query);
        if ($queryResult['numRows'] == 1 &&
            password_verify($state['qs']['p'], $queryResult['fetchAssoc']['password'])
            ) {
            $state['userId'] = $queryResult['fetchAssoc']['id'];
            $query = 'SELECT `time`, `code` FROM `codes` WHERE `userId` = ' . $state['userId'] . ' LIMIT 1';
            $queryResult = request($query);


            // login в первый раз, параметр code отсутствует
            // назначение code и времени запроса
            if ($queryResult['numRows'] == 0) {
                $state['response']['code'] = randomString($state['codeLength']);
                $query = 'INSERT INTO `codes`(`userId`, `time`, `code`) VALUES (' . $state['userId'] . ', now(), \'' . $state['response']['code'] . '\')';
                request($query);
                doSendJson();
            }


            // login при существующем параметре code
            // обновление времени запроса или назначение нового параметра code
            if ($queryResult['numRows'] == 1) {
                if (time() - strtotime($queryResult['fetchAssoc']['time']) < $state['timeLife']) {
                    $state['response']['code'] = $queryResult['fetchAssoc']['code'];
                    $query = 'UPDATE `codes` SET `time` = now() WHERE `userId` = ' . $state['userId'] . ' LIMIT 1';
                    request($query);
                } else {
                    $state['response']['code'] = randomString($state['codeLength']);
                    $query = 'UPDATE `codes` SET `time` = now(), `code` = \'' . $state['response']['code'] . '\' WHERE `userId` = ' . $state['userId'] . ' LIMIT 1';
                    request($query);
                }
                doSendJson();
            }
        }
        include_once './error.php';
    }
}

/**
 * параметр token
 * или ошибка
 */
function doCode()
{
    global $state;
    if (
        isset($state['action']) && $state['action'] == 'auth' &&
        isset($state['qs']['code']) && checkSymbols($state['qs']['code'])
        ) {
        $query = 'SELECT `userId`, `time` FROM `codes` WHERE `code` = \'' . $state['qs']['code'] . '\' LIMIT 1';
        $queryResult = request($query);
        if (
            $queryResult['numRows'] == 1 &&
            time() - strtotime($queryResult['fetchAssoc']['time']) < $state['timeLife']
            ) {
            $state['userId'] = $queryResult['fetchAssoc']['userId'];
            $query = 'SELECT `token` FROM `tokens` WHERE `userId` = ' . $state['userId'] . ' LIMIT 1';
            $queryResult = request($query);


            // code в первый раз, параметр token отсутствует
            // назначение token
            if ($queryResult['numRows'] == 0) {
                $state['response']['token'] = randomString($state['tokenLength']);
                $query = 'INSERT INTO `tokens`(`userId`, `token`) VALUES (' . $state['userId'] . ', \'' . $state['response']['token'] . '\')';
                request($query);
                doSendJson();
            }


            // code при существующем параметре token
            if ($queryResult['numRows'] == 1) {
                $state['response']['token'] = $queryResult['fetchAssoc']['token'];
                doSendJson();
            }
        }
        include_once './error.php';
    }
}

/**
 * список доступных изображений
 * или ошибка
 */
function doList()
{
    global $state;
    if (
        isset($state['action']) && $state['action'] == 'list' &&
        isset($state['qs']['token']) && checkSymbols($state['qs']['token'])
        ) {
        if (DO_LIST) {
            if (checkToken()) {
                $state['response'] = array();
                $imageList = dirToArray('./img');
                for ($i = 0; $i <= count($imageList) - 1; $i++) {
                    $state['response'][] = array('id' => pathinfo('./img/' . $imageList[$i])['filename']);
                }
                doSendJson();
            }
        }
        include_once './error.php';
    }
}

/**
 * получение ссылки на изображение или получение метаданных
 * или ошибка
 */
function doGet()
{
    global $state;
    if (
        isset($state['action']) && $state['action'] == 'get' &&
        isset($state['qs']['token']) && checkSymbols($state['qs']['token']) &&
        isset($state['qs']['id']) && checkSymbols($state['qs']['id'])
        ) {
        if (checkToken() && checkImageId()) {
            $state['newQS'] = $state['qs'];
            unset($state['newQS']['token']);
            if (checkParameters($state['newQS']) && isset($state['qs']['format'])) {
                if ($state['qs']['format'] == 'meta') {
                    getImageMeta();
                }
                asort($state['newQS']);
                $state['response']['link'] = encode(http_build_query($state['newQS'], '', '&'));
                doSendJson();
            }
        }
        include_once './error.php';
    }
}

/**
 * формирование и отправка самого изображения
 * или ошибка
 */
function doImage()
{
    makeParameters();
    makeImage();
    doSendImage();
}
