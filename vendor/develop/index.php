<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

define('ROOT', dirname(__FILE__));
define('LIB_PATH', ROOT . '/php/lib');
define('CACHE_PATH', ROOT . '/php/cache');
define('TMP_PATH', ROOT . '/php/tmp');
define('VIEW_PATH', ROOT . '/view');
define('STATIC_PATH', ROOT . '/static');
define('TEST_PATH', ROOT . '/test');

$rewrite = (array)load(TMP_PATH . '/feather_rewrite.php');
$conf = load(TMP_PATH . '/feather_conf.php');

$suffix = '.' . $conf['template']['suffix'];

$uri = $_SERVER['REQUEST_URI'];
$path = null;

foreach ($rewrite as $key => $value){
    preg_match($key, $uri, $match);

    if(!empty($match)){
        $value = (array)$value;
        $value = $value[rand(0, count($value) - 1)];
        $path = $value;
        break;  
    }
}

$path = $path ? $path : preg_replace('/[\?#].*/', '', $uri);
$path = explode('/', trim($path, '/'));

if(empty($path[0])) $path = array('page');
if($path[0] == 'page' && empty($path[1])) $path[1] = 'index' . $suffix;

$tmpPath = implode('/', $path);
$s = strrchr($tmpPath, '.');

if(($path[0] == 'page' || $path[0] == 'component' || $path[0] == 'pagelet') && ($s === false || $s == $suffix)){
    require LIB_PATH . '/Feather_View.class.php';

    load(TMP_PATH . '/feather_compatible.php');

    //依赖map表测试的版本
    $view = new Feather_View();
    $view->template_dir = array(VIEW_PATH);
    $view->suffix = $suffix;
    $view->plugins_dir = ROOT . '/php/plugins';

    Feather_View_Loader::import('Feather_View_Plugin_Cache_File.class.php');

    $cache = new Feather_View_Plugin_Cache_File(array(
        'cache_dir' => CACHE_PATH
    ));

    if(!$conf['staticMode']){
        $view->registerPlugin('autoload_static', array(
            'domain' => $conf['domain'] ? "http://{$_SERVER['HTTP_HOST']}" : ''
        ));

        $view->registerPlugin('autoload_test_data', array(
            'maps' => glob(ROOT . "/map/**"),
            'data_dir' => TEST_PATH
        ));

        $view->registerPlugin('static_position');
    }

    $path = '/' . preg_replace('/\..+$/', '', implode('/', $path));

    if(!$data = (array)load(TEST_PATH . $path . '.php')){
        $data = array();
    }

    $view->set($data);
    $view->display($path);
}else{
    if($path[0] == 'test'){
        require LIB_PATH . '/MagicData.class.php';
        $_path = TEST_PATH . '/' . implode('/', array_slice($path, 1));

        if(!is_file($_path)){
            header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
            exit;
        }

        $output = MagicData::parseByFile($_path);
        $type = getMime($tmpPath);
        header("Content-type: {$type};");
        echo eval("?>{$output}");
        exit;
    }else{
        $_path = STATIC_PATH . '/' . implode('/', $path);

        if(!is_file($_path)){
            header("{$_SERVER['SERVER_PROTOCOL']} 404 Not Found");
            exit;
        }
        
        $output = file_get_contents($_path);
        $type = getMime($tmpPath);
        header("Content-type: {$type};");
        echo $output;
    }
}

//加载一个文件
function load($file){
    if(file_exists($file)){
        return require $file; 
    }
}

function getMime($path){
    $type = array(
        'bmp' => 'image/bmp',
        'css' => 'text/css',
        'doc' => 'application/msword',
        'dtd' => 'text/xml',
        'gif' => 'image/gif',
        'hta' => 'application/hta',
        'htc' => 'text/x-component',
        'htm' => 'text/html',
        'html' => 'text/html',
        'xhtml' => 'text/html',
        'phtml' => 'text/html',
        'ico' => 'image/x-icon',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'mocha' => 'text/javascript',
        'mp3' => 'audio/mp3',
        'flv' => 'video/flv',
        'swf' => 'video/swf',
        'mp4' => 'video/mpeg4',
        'mpeg' => 'video/mpg',
        'mpg' => 'video/mpg',
        'manifest' => 'text/cache-manifest',
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'ppt' => 'application/vnd.ms-powerpoint',
        'rmvb' => 'application/vnd.rn-realmedia-vbr',
        'rm' => 'application/vnd.rn-realmedia',
        'rtf' => 'application/msword',
        'svg' => 'image/svg+xml',
        'swf' => 'application/x-shockwave-flash',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'txt' => 'text/plain',
        'vml' => 'text/xml',
        'vxml' => 'text/xml',
        'wav' => 'audio/wav',
        'wma' => 'audio/x-ms-wma',
        'wmv' => 'video/x-ms-wmv',
        'woff' => 'image/woff',
        'xml' => 'text/xml',
        'xls' => 'application/vnd.ms-excel',
        'xq' => 'text/xml',
        'xql' => 'text/xml',
        'xquery' => 'text/xml',
        'xsd' => 'text/xml',
        'xsl' => 'text/xml',
        'xslt' => 'text/xml'
    );

    $info = pathinfo($path);
    $type = $type[$info['extension']];

    return $type ? $type : 'text/plain';
}