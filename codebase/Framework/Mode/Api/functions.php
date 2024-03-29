<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

/**
 * Think APIModel library
 */

/**
 * ObtainwithSet upConfiguration parameters Support batch definitions
 * @param string|array $name Configuration Variables
 * @param mixed $value Configuration values
 * @param mixed $default Defaults
 * @return mixed
 */
function C($name = null, $value = null, $default = null)
{
    static $_config = array();
    // noparameterTimeObtainall
    if (empty($name)) {
        return $_config;
    }
    // prioritycarried outSet upObtainOr assignment
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtolower($name);
            if (is_null($value))
                return isset($_config[$name]) ? $_config[$name] : $default;
            $_config[$name] = $value;
            return;
        }
        // Two-dimensionalArraySet upwithObtainstand by
        $name = explode('.', $name);
        $name[0] = strtolower($name[0]);
        if (is_null($value))
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : $default;
        $_config[$name[0]][$name[1]] = $value;
        return;
    }
    // batchSet up
    if (is_array($name)) {
        $_config = array_merge($_config, array_change_key_case($name));
        return;
    }
    return null; // Avoid Illegalparameter
}

/**
 * Load Profile Support format conversion It supports only one configuration
 * @param string $file Configuration file name
 * @param string $parse Configuration analytical method someformatneeduserOwnResolve
 * @return void
 */
function load_config($file, $parse = CONF_PARSE)
{
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    switch ($ext) {
        case 'php':
            return include $file;
        case 'ini':
            return parse_ini_file($file);
        case 'yaml':
            return yaml_parse_file($file);
        case 'xml':
            return (array)simplexml_load_file($file);
        case 'json':
            return json_decode(file_get_contents($file), true);
        default:
            if (function_exists($parse)) {
                return $parse($file);
            } else {
                E(L('_NOT_SUPPORT_') . ':' . $ext);
            }
    }
}

/**
 * Throws Exception Handling
 * @param string $msg Exception Message
 * @param integer $code Exception code The default is0
 * @return void
 */
function E($msg, $code = 0)
{
    throw new Think\Exception($msg, $code);
}

/**
 * recordingwithstatisticstime(Microsecond)And Memoryuse情况
 * Instructions:
 * <code>
 * G('begin'); // recordingStartFlag
 * // ... Interval running code
 * G('end'); // recordingEnd tagPlace
 * echo G('begin','end',6); // statisticsIntervalruntime Accurate to one decimal place6Place
 * echo G('begin','end','m'); // statisticsIntervalRAMuse情况
 * in caseendFlagNodefinition,thenmeetingautomaticWithcurrentAs aFlag
 * among themstatisticsRAMuseneed MEMORY_LIMIT_ON Constant istrueTo be effective
 * </code>
 * @param string $start Start tag
 * @param string $end End tag
 * @param integer|string $dec Decimal places orm
 * @return mixed
 */
function G($start, $end = '', $dec = 4)
{
    static $_info = array();
    static $_mem = array();
    if (is_float($end)) { // recordingtime
        $_info[$start] = $end;
    } elseif (!empty($end)) { // statisticstimeAnd Memoryuse
        if (!isset($_info[$end])) $_info[$end] = microtime(TRUE);
        if (MEMORY_LIMIT_ON && $dec == 'm') {
            if (!isset($_mem[$end])) $_mem[$end] = memory_get_usage();
            return number_format(($_mem[$end] - $_mem[$start]) / 1024);
        } else {
            return number_format(($_info[$end] - $_info[$start]), $dec);
        }

    } else { // recordingtimeAnd Memoryuse
        $_info[$start] = microtime(TRUE);
        if (MEMORY_LIMIT_ON) $_mem[$start] = memory_get_usage();
    }
}

/**
 * ObtainwithSet upLanguagedefinition(not case sensitive)
 * @param string|array $name Linguistic variables
 * @param string $value Language value
 * @return mixed
 */
function L($name = null, $value = null)
{
    static $_lang = array();
    // airparameterreturnalldefinition
    if (empty($name))
        return $_lang;
    // judgmentLanguageObtain(orSet up)
    // If thedoes not exist,Direct returnALL CAPS$name
    if (is_string($name)) {
        $name = strtoupper($name);
        if (is_null($value))
            return isset($_lang[$name]) ? $_lang[$name] : $name;
        $_lang[$name] = $value; // Languagedefinition
        return;
    }
    // batchdefinition
    if (is_array($name))
        $_lang = array_merge($_lang, array_change_key_case($name, CASE_UPPER));
    return;
}

/**
 * Adding and get the pageTracerecording
 * @param string $value variable
 * @param string $label label
 * @param string $level Log Level
 * @param boolean $record Whether logging
 * @return void
 */
function trace($value = '[think]', $label = '', $level = 'DEBUG', $record = false)
{
    return Think\Think::trace($value, $label, $level, $record);
}

/**
 * Compile file
 * @param string $filename filename
 * @return string
 */
function compile($filename)
{
    $content = php_strip_whitespace($filename);
    $content = trim(substr($content, 5));
    // replaceAdvanceCompileinstruction
    $content = preg_replace('/\/\/\[RUNTIME\](.*?)\/\/\[\/RUNTIME\]/s', '', $content);
    if (0 === strpos($content, 'namespace')) {
        $content = preg_replace('/namespace\s(.*?);/', 'namespace \\1{', $content, 1);
    } else {
        $content = 'namespace {' . $content;
    }
    if ('?>' == substr($content, -2))
        $content = substr($content, 0, -2);
    return $content . '}';
}

/**
 * Obtain input parameters Supports filtering and default values
 * Instructions:
 * <code>
 * I('id',0); Obtainidparameter Automatically determine thegetorpost
 * I('post.name','','htmlspecialchars'); Obtain$_POST['name']
 * I('get.'); Obtain$_GET
 * </code>
 * @param string $name The name of the variable Specify the type of support
 * @param mixed $default does not existoftimeDefaults
 * @param mixed $filter Parameter filtering method
 * @param mixed $datas wantObtainofadditionaldatasource
 * @return mixed
 */
function I($name, $default = '', $filter = null, $datas = null)
{
    if (strpos($name, '/')) { // DesignationModifiers
        list($name, $type) = explode('/', $name, 2);
    }
    if (strpos($name, '.')) { // Designationparametersource
        list($method, $name) = explode('.', $name, 2);
    } else { // The default isAutomatically determine the
        $method = 'param';
    }
    switch (strtolower($method)) {
        case 'get'     :
            $input =& $_GET;
            break;
        case 'post'    :
            $input =& $_POST;
            break;
        case 'put'     :
            parse_str(file_get_contents('php://input'), $input);
            break;
        case 'param'   :
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'POST':
                    $input = $_POST;
                    break;
                case 'PUT':
                    parse_str(file_get_contents('php://input'), $input);
                    break;
                default:
                    $input = $_GET;
            }
            break;
        case 'path'    :
            $input = array();
            if (!empty($_SERVER['PATH_INFO'])) {
                $depr = C('URL_PATHINFO_DEPR');
                $input = explode($depr, trim($_SERVER['PATH_INFO'], $depr));
            }
            break;
        case 'request' :
            $input =& $_REQUEST;
            break;
        case 'session' :
            $input =& $_SESSION;
            break;
        case 'cookie'  :
            $input =& $_COOKIE;
            break;
        case 'server'  :
            $input =& $_SERVER;
            break;
        case 'globals' :
            $input =& $GLOBALS;
            break;
        case 'data'    :
            $input =& $datas;
            break;
        default:
            return NULL;
    }
    if ('' == $name) { // ObtainCompletevariable
        $data = $input;
        $filters = isset($filter) ? $filter : C('DEFAULT_FILTER');
        if ($filters) {
            if (is_string($filters)) {
                $filters = explode(',', $filters);
            }
            foreach ($filters as $filter) {
                $data = array_map_recursive($filter, $data); // parameterfilter
            }
        }
    } elseif (isset($input[$name])) { // The valueoperating
        $data = $input[$name];
        $filters = isset($filter) ? $filter : C('DEFAULT_FILTER');
        if ($filters) {
            if (is_string($filters)) {
                $filters = explode(',', $filters);
            } elseif (is_int($filters)) {
                $filters = array($filters);
            }

            foreach ($filters as $filter) {
                if (function_exists($filter)) {
                    $data = is_array($data) ? array_map_recursive($filter, $data) : $filter($data); // parameterfilter
                } elseif (0 === strpos($filter, '/')) {
                    // stand byRegularverification
                    if (1 !== preg_match($filter, (string)$data)) {
                        return isset($default) ? $default : NULL;
                    }
                } else {
                    $data = filter_var($data, is_int($filter) ? $filter : filter_id($filter));
                    if (false === $data) {
                        return isset($default) ? $default : NULL;
                    }
                }
            }
        }
        if (!empty($type)) {
            switch (strtolower($type)) {
                case 's':   // String
                    $data = (string)$data;
                    break;
                case 'a':    // Array
                    $data = (array)$data;
                    break;
                case 'd':    // digital
                    $data = (int)$data;
                    break;
                case 'f':    // floating point
                    $data = (float)$data;
                    break;
                case 'b':    // Boolean
                    $data = (boolean)$data;
                    break;
            }
        }
    } else { // variableDefaults
        $data = isset($default) ? $default : NULL;
    }
    is_array($data) && array_walk_recursive($data, 'think_filter');
    return $data;
}

function array_map_recursive($filter, $data)
{
    $result = array();
    foreach ($data as $key => $val) {
        $result[$key] = is_array($val)
            ? array_map_recursive($filter, $val)
            : call_user_func($filter, $val);
    }
    return $result;
}

/**
 * Set upwithObtainstatisticsdata
 * Instructions:
 * <code>
 * N('db',1); // recordingdatabaseoperatingfrequency
 * N('read',1); // recordingReadfrequency
 * echo N('db'); // Get the currentpageDatabasealloperatingfrequency
 * echo N('read'); // Get the currentpageReadfrequency
 * </code>
 * @param string $key Identify the location
 * @param integer $step Stepping value
 * @return mixed
 */
function N($key, $step = 0, $save = false)
{
    static $_num = array();
    if (!isset($_num[$key])) {
        $_num[$key] = (false !== $save) ? S('N_' . $key) : 0;
    }
    if (empty($step))
        return $_num[$key];
    else
        $_num[$key] = $_num[$key] + (int)$step;
    if (false !== $save) { // Storageresult
        S('N_' . $key, $_num[$key], $save);
    }
}

/**
 * StringNaming StyleChange
 * type 0 willJavaStyle convertedCstyle of 1 willCStyle convertedJavastyle of
 * @param string $name String
 * @param integer $type Conversion Type
 * @return string
 */
function parse_name($name, $type = 0)
{
    if ($type) {
        return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match) {
            return strtoupper($match[1]);
        }, $name));
    } else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}

/**
 * optimizedrequire_once
 * @param string $filename Address file
 * @return boolean
 */
function require_cache($filename)
{
    static $_importFiles = array();
    if (!isset($_importFiles[$filename])) {
        if (file_exists_case($filename)) {
            require $filename;
            $_importFiles[$filename] = true;
        } else {
            $_importFiles[$filename] = false;
        }
    }
    return $_importFiles[$filename];
}

/**
 * Case sensitivedocumentexistjudgment
 * @param string $filename Address file
 * @return boolean
 */
function file_exists_case($filename)
{
    if (is_file($filename)) {
        if (IS_WIN && APP_DEBUG) {
            if (basename(realpath($filename)) != basename($filename))
                return false;
        }
        return true;
    }
    return false;
}

/**
 * Import the required class libraries 同javaofImport This function has a cache function
 * @param string $class Class LibraryNamespacesString
 * @param string $baseUrl Starting path
 * @param string $ext ImportingdocumentExtension
 * @return boolean
 */
function import($class, $baseUrl = '', $ext = EXT)
{
    static $_file = array();
    $class = str_replace(array('.', '#'), array('/', '.'), $class);
    if (isset($_file[$class . $baseUrl]))
        return true;
    else
        $_file[$class . $baseUrl] = true;
    $class_strut = explode('/', $class);
    if (empty($baseUrl)) {
        if ('@' == $class_strut[0] || MODULE_NAME == $class_strut[0]) {
            //loadcurrentModuleofClass Library
            $baseUrl = MODULE_PATH;
            $class = substr_replace($class, '', 0, strlen($class_strut[0]) + 1);
        } elseif (in_array($class_strut[0], array('Think', 'Org', 'Behavior', 'Com', 'Vendor')) || is_dir(LIB_PATH . $class_strut[0])) {
            // systemClass LibraryAnd third-party packagesClass Librarypackage
            $baseUrl = LIB_PATH;
        } else { // loadotherModuleofClass Library
            $baseUrl = APP_PATH;
        }
    }
    if (substr($baseUrl, -1) != '/')
        $baseUrl .= '/';
    $classfile = $baseUrl . $class . $ext;
    if (!class_exists(basename($class), false)) {
        // in caseclassdoes not exist thenImportingClass Libraryfile
        return require_cache($classfile);
    }
}

/**
 * based onNamespacesthe wayImportingfunctionStorehouse
 * load('@.Util.Array')
 * @param string $name functionStorehouseNamespacesString
 * @param string $baseUrl Starting path
 * @param string $ext ImportingdocumentExtension
 * @return void
 */
function load($name, $baseUrl = '', $ext = '.php')
{
    $name = str_replace(array('.', '#'), array('/', '.'), $name);
    if (empty($baseUrl)) {
        if (0 === strpos($name, '@/')) {//loadcurrentModulefunctionStorehouse
            $baseUrl = MODULE_PATH . 'Common/';
            $name = substr($name, 2);
        } else { //loadotherModulefunctionStorehouse
            $array = explode('/', $name);
            $baseUrl = APP_PATH . array_shift($array) . '/Common/';
            $name = implode('/', $array);
        }
    }
    if (substr($baseUrl, -1) != '/')
        $baseUrl .= '/';
    require_cache($baseUrl . $name . $ext);
}

/**
 * fastImportingThird partyframeClass Library allThird partyframeofClass LibraryfileUnitePut systematicVendorDirectory
 * @param string $class Class Library
 * @param string $baseUrl Base directory
 * @param string $ext Library suffix
 * @return boolean
 */
function vendor($class, $baseUrl = '', $ext = '.php')
{
    if (empty($baseUrl))
        $baseUrl = VENDOR_PATH;
    return import($class, $baseUrl, $ext);
}

/**
 * DFunction for instantiatingModel Class format [Resources://][Module/]model
 * @param string $name Resource Locator
 * @param string $layer Model layer name
 * @return Model
 */
function D($name = '', $layer = '')
{
    if (empty($name)) return new Think\Model;
    static $_model = array();
    $layer = $layer ?: C('DEFAULT_M_LAYER');
    if (isset($_model[$name . $layer]))
        return $_model[$name . $layer];
    $class = parse_res_name($name, $layer);
    if (class_exists($class)) {
        $model = new $class(basename($name));
    } elseif (false === strpos($name, '/')) {
        // Automatically loadpublicModulethe followingofmodel
        $class = '\\Common\\' . $layer . '\\' . $name . $layer;
        $model = class_exists($class) ? new $class($name) : new Think\Model($name);
    } else {
        Think\Log::record('DmethodInstantiationdid not findModel Class' . $class, Think\Log::NOTICE);
        $model = new Think\Model(basename($name));
    }
    $_model[$name . $layer] = $model;
    return $model;
}

/**
 * MFunction for instantiatingOneNomodelDocumentsModel
 * @param string $name Modelname Supports the specified base model E.g MongoModel:User
 * @param string $tablePrefix Table Prefix
 * @param mixed $connection Database connection information
 * @return Model
 */
function M($name = '', $tablePrefix = '', $connection = '')
{
    static $_model = array();
    if (strpos($name, ':')) {
        list($class, $name) = explode(':', $name);
    } else {
        $class = 'Think\\Model';
    }
    $guid = (is_array($connection) ? implode('', $connection) : $connection) . $tablePrefix . $name . '_' . $class;
    if (!isset($_model[$guid]))
        $_model[$guid] = new $class($name, $tablePrefix, $connection);
    return $_model[$guid];
}

/**
 * ResolveResource LocatorandImportingClass Libraryfile
 * E.g module/controller addon://module/behavior
 * @param string $name Resource Locator format:[Spread://][Module/]Resources Name
 * @param string $layer Hierarchical name
 * @return string
 */
function parse_res_name($name, $layer, $level = 1)
{
    if (strpos($name, '://')) {// DesignationSpreadResources
        list($extend, $name) = explode('://', $name);
    } else {
        $extend = '';
    }
    if (strpos($name, '/') && substr_count($name, '/') >= $level) { // DesignationModule
        list($module, $name) = explode('/', $name, 2);
    } else {
        $module = MODULE_NAME;
    }
    $array = explode('/', $name);
    $class = $module . '\\' . $layer;
    foreach ($array as $name) {
        $class .= '\\' . parse_name($name, 1);
    }
    // ImportingResourcesClass Library
    if ($extend) { // SpreadResources
        $class = $extend . '\\' . $class;
    }
    return $class . $layer;
}

/**
 * AFunction for instantiatingController format:[Resources://][Module/]Controller
 * @param string $name Resource Locator
 * @param string $layer Control layer name
 * @param integer $level Controller level
 * @return Controller|false
 */
function A($name, $layer = '', $level = '')
{
    static $_action = array();
    $layer = $layer ?: C('DEFAULT_C_LAYER');
    $level = $level ?: ($layer == C('DEFAULT_C_LAYER') ? C('CONTROLLER_LEVEL') : 1);
    if (isset($_action[$name . $layer]))
        return $_action[$name . $layer];
    $class = parse_res_name($name, $layer, $level);
    if (class_exists($class)) {
        $action = new $class();
        $_action[$name . $layer] = $action;
        return $action;
    } else {
        return false;
    }
}

/**
 * 远程transferControllerofoperatingmethod URL Parameter format [Resources://][Module/]Controller/operating
 * @param string $url Call address
 * @param string|array $vars Call parameters Support for strings and arrays
 * @param string $layer wanttransferofControl layer name
 * @return mixed
 */
function R($url, $vars = array(), $layer = '')
{
    $info = pathinfo($url);
    $action = $info['basename'];
    $module = $info['dirname'];
    $class = A($module, $layer);
    if ($class) {
        if (is_string($vars)) {
            parse_str($vars, $vars);
        }
        return call_user_func_array(array(&$class, $action . C('ACTION_SUFFIX')), $vars);
    } else {
        return false;
    }
}

/**
 * The implementation of an action
 * @param string $name Behavior name
 * @param Mixed $params Incoming parameters
 * @return void
 */
function B($name, &$params = NULL)
{
    if (strpos($name, '/')) {
        list($name, $tag) = explode('/', $name);
    } else {
        $tag = 'run';
    }
    return \Think\Hook::exec($name, $tag, $params);
}

/**
 * RemovalCodemiddleBlank andNote
 * @param string $content Code Contents
 * @return string
 */
function strip_whitespace($content)
{
    $stripStr = '';
    //analysisphpSource
    $tokens = token_get_all($content);
    $last_space = false;
    for ($i = 0, $j = count($tokens); $i < $j; $i++) {
        if (is_string($tokens[$i])) {
            $last_space = false;
            $stripStr .= $tokens[$i];
        } else {
            switch ($tokens[$i][0]) {
                //Various filtersPHPNote
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                //Filtering space
                case T_WHITESPACE:
                    if (!$last_space) {
                        $stripStr .= ' ';
                        $last_space = true;
                    }
                    break;
                case T_START_HEREDOC:
                    $stripStr .= "<<<THINK\n";
                    break;
                case T_END_HEREDOC:
                    $stripStr .= "THINK;\n";
                    for ($k = $i + 1; $k < $j; $k++) {
                        if (is_string($tokens[$k]) && $tokens[$k] == ';') {
                            $i = $k;
                            break;
                        } else if ($tokens[$k][0] == T_CLOSE_TAG) {
                            break;
                        }
                    }
                    break;
                default:
                    $last_space = false;
                    $stripStr .= $tokens[$i][1];
            }
        }
    }
    return $stripStr;
}

/**
 * BrowseFRIENDLYofvariableExport
 * @param mixed $var variable
 * @param boolean $echo Whether output The default isTrue Iffalse Output string is returned
 * @param string $label label The default is empty
 * @param boolean $strict Whether rigorous The default istrue
 * @return void|string
 */
function dump($var, $echo = true, $label = null, $strict = true)
{
    $label = ($label === null) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    } else
        return $output;
}

/**
 * URLRedirect
 * @param string $url RedirectedURLaddress
 * @param integer $time RedirectedWait time(second)
 * @param string $msg RedirectbeforeofTips
 * @return void
 */
function redirect($url, $time = 0, $msg = '')
{
    //Multi-lineURLAddress Support
    $url = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg))
        $msg = "System will{$time}After the second jump to automatically{$url}!";
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    } else {
        $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if ($time != 0)
            $str .= $msg;
        exit($str);
    }
}

/**
 * Cache Management
 * @param mixed $name Cache name, IfArrayShowEnterRowCacheSet up
 * @param mixed $value Cache value
 * @param mixed $options Cache parameters
 * @return mixed
 */
function S($name, $value = '', $options = null)
{
	
    static $cache = '';
    if (is_array($options) && empty($cache)) {
        // CacheoperatingofSimultaneouslyinitialization
        $type = isset($options['type']) ? $options['type'] : '';
        $cache = Think\Cache::getInstance($type, $options);
    } elseif (is_array($name)) { // Cacheinitialization
        $type = isset($name['type']) ? $name['type'] : '';
        $cache = Think\Cache::getInstance($type, $name);
        return $cache;
    } elseif (empty($cache)) { // automaticinitialization
        $cache = Think\Cache::getInstance();
    }
    if ('' === $value) { // ObtainCache
        return $cache->get($name);
    } elseif (is_null($value)) { // Delete Cache
        return $cache->rm($name);
    } else { // Cachedata
        if (is_array($options)) {
            $expire = isset($options['expire']) ? $options['expire'] : NULL;
        } else {
            $expire = is_numeric($options) ? $options : NULL;
        }
        return $cache->set($name, $value, $expire);
    }
}

/**
 * fastfiledataReadwithStorage For simple data types Strings, arrays
 * @param string $name Cache name
 * @param mixed $value Cache value
 * @param string $path Cache path
 * @return mixed
 */
function F($name, $value = '', $path = DATA_PATH)
{
    static $_cache = array();
    $filename = $path . $name . '.php';
    if ('' !== $value) {
        if (is_null($value)) {
            // Delete Cache
            if (false !== strpos($name, '*')) {
                return false; // TODO 
            } else {
                unset($_cache[$name]);
                return Think\Storage::unlink($filename, 'F');
            }
        } else {
            Think\Storage::put($filename, serialize($value), 'F');
            // Cachedata
            $_cache[$name] = $value;
            return;
        }
    }
    // ObtainCachedata
    if (isset($_cache[$name]))
        return $_cache[$name];
    if (Think\Storage::has($filename, 'F')) {
        $value = unserialize(Think\Storage::read($filename, 'F'));
        $_cache[$name] = $value;
    } else {
        $value = false;
    }
    return $value;
}

/**
 * according toPHPAll kindsTypes ofvariableFormonlyMarknumber
 * @param mixed $mix variable
 * @return string
 */
function to_guid_string($mix)
{
    if (is_object($mix)) {
        return spl_object_hash($mix);
    } elseif (is_resource($mix)) {
        $mix = get_resource_type($mix) . strval($mix);
    } else {
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 * XMLcoding
 * @param mixed $data data
 * @param string $root rootNode Roll Call
 * @param string $item Digital IndexofchildNode Roll Call
 * @param string $attr The root property
 * @param string $id Digital index of the child nodekeyConversionAttributes Name
 * @param string $encoding Data encoding
 * @return string
 */
function xml_encode($data, $root = 'think', $item = 'item', $attr = '', $id = 'id', $encoding = 'utf-8')
{
    if (is_array($attr)) {
        $_attr = array();
        foreach ($attr as $key => $value) {
            $_attr[] = "{$key}=\"{$value}\"";
        }
        $attr = implode(' ', $_attr);
    }
    $attr = trim($attr);
    $attr = empty($attr) ? '' : " {$attr}";
    $xml = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
    $xml .= "<{$root}{$attr}>";
    $xml .= data_to_xml($data, $item, $id);
    $xml .= "</{$root}>";
    return $xml;
}

/**
 * dataXMLcoding
 * @param mixed $data data
 * @param string $item Digital IndexWhen the nodename
 * @param string $id Digital IndexkeyConverted toofAttributes Name
 * @return string
 */
function data_to_xml($data, $item = 'item', $id = 'id')
{
    $xml = $attr = '';
    foreach ($data as $key => $val) {
        if (is_numeric($key)) {
            $id && $attr = " {$id}=\"{$key}\"";
            $key = $item;
        }
        $xml .= "<{$key}{$attr}>";
        $xml .= (is_array($val) || is_object($val)) ? data_to_xml($val, $item, $id) : $val;
        $xml .= "</{$key}>";
    }
    return $xml;
}

/**
 * sessionManagement Functions
 * @param string|array $name sessionname IfArrayIt meansEnterRowsessionSet up
 * @param mixed $value sessionvalue
 * @return mixed
 */
function session($name, $value = '')
{
    $prefix = C('SESSION_PREFIX');
    if (is_array($name)) { // Session initialization insession_start Before calling
        if (isset($name['prefix'])) C('SESSION_PREFIX', $name['prefix']);
        if (C('VAR_SESSION_ID') && isset($_REQUEST[C('VAR_SESSION_ID')])) {
            session_id($_REQUEST[C('VAR_SESSION_ID')]);
        } elseif (isset($name['id'])) {
            session_id($name['id']);
        }
        if ('common' != APP_MODE) { // othermodeMay not support
            ini_set('session.auto_start', 0);
        }
        if (isset($name['name'])) session_name($name['name']);
        if (isset($name['path'])) session_save_path($name['path']);
        if (isset($name['domain'])) ini_set('session.cookie_domain', $name['domain']);
        if (isset($name['expire'])) ini_set('session.gc_maxlifetime', $name['expire']);
        if (isset($name['use_trans_sid'])) ini_set('session.use_trans_sid', $name['use_trans_sid'] ? 1 : 0);
        if (isset($name['use_cookies'])) ini_set('session.use_cookies', $name['use_cookies'] ? 1 : 0);
        if (isset($name['cache_limiter'])) session_cache_limiter($name['cache_limiter']);
        if (isset($name['cache_expire'])) session_cache_expire($name['cache_expire']);
        if (isset($name['type'])) C('SESSION_TYPE', $name['type']);
        if (C('SESSION_TYPE')) { // Readsessiondrive
            $type = C('SESSION_TYPE');
            $class = strpos($type, '\\') ? $type : 'Think\\Session\\Driver\\' . ucwords(strtolower($type));
            $hander = new $class();
            session_set_save_handler(
                array(&$hander, "open"),
                array(&$hander, "close"),
                array(&$hander, "read"),
                array(&$hander, "write"),
                array(&$hander, "destroy"),
                array(&$hander, "gc"));
        }
        // start upsession
        if (C('SESSION_AUTO_START')) session_start();
    } elseif ('' === $value) {
        if (0 === strpos($name, '[')) { // session operating
            if ('[pause]' == $name) { // time outsession
                session_write_close();
            } elseif ('[start]' == $name) { // start upsession
                session_start();
            } elseif ('[destroy]' == $name) { // destroysession
                $_SESSION = array();
                session_unset();
                session_destroy();
            } elseif ('[regenerate]' == $name) { // AgainFormid
                session_regenerate_id();
            }
        } elseif (0 === strpos($name, '?')) { // an examinationsession
            $name = substr($name, 1);
            if (strpos($name, '.')) { // stand byArray
                list($name1, $name2) = explode('.', $name);
                return $prefix ? isset($_SESSION[$prefix][$name1][$name2]) : isset($_SESSION[$name1][$name2]);
            } else {
                return $prefix ? isset($_SESSION[$prefix][$name]) : isset($_SESSION[$name]);
            }
        } elseif (is_null($name)) { // Clearsession
            if ($prefix) {
                unset($_SESSION[$prefix]);
            } else {
                $_SESSION = array();
            }
        } elseif ($prefix) { // Obtainsession
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$prefix][$name1][$name2]) ? $_SESSION[$prefix][$name1][$name2] : null;
            } else {
                return isset($_SESSION[$prefix][$name]) ? $_SESSION[$prefix][$name] : null;
            }
        } else {
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$name1][$name2]) ? $_SESSION[$name1][$name2] : null;
            } else {
                return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
            }
        }
    } elseif (is_null($value)) { // deletesession
        if ($prefix) {
            unset($_SESSION[$prefix][$name]);
        } else {
            unset($_SESSION[$name]);
        }
    } else { // Set upsession
        if ($prefix) {
            if (!is_array($_SESSION[$prefix])) {
                $_SESSION[$prefix] = array();
            }
            $_SESSION[$prefix][$name] = $value;
        } else {
            $_SESSION[$name] = $value;
        }
    }
}

/**
 * Cookie Setting, get, delete
 * @param string $name cookiename
 * @param mixed $value cookievalue
 * @param mixed $options cookieparameter
 * @return mixed
 */
function cookie($name, $value = '', $option = null)
{
    // defaultSet up
    $config = array(
        'prefix' => C('COOKIE_PREFIX'), // cookie Name Prefix
        'expire' => C('COOKIE_EXPIRE'), // cookie Storagetime
        'path' => C('COOKIE_PATH'), // cookie save route
        'domain' => C('COOKIE_DOMAIN'), // cookie effectiveareaname
    );
    // parameterSet up(meetingcoverSummerside recognitionSet up)
    if (!is_null($option)) {
        if (is_numeric($option))
            $option = array('expire' => $option);
        elseif (is_string($option))
            parse_str($option, $option);
        $config = array_merge($config, array_change_key_case($option));
    }
    // RemoveDesignationPrefixofallcookie
    if (is_null($name)) {
        if (empty($_COOKIE))
            return;
        // wantdeleteofcookiePrefix,不DesignationthendeleteconfigSet upofDesignationPrefix
        $prefix = empty($value) ? $config['prefix'] : $value;
        if (!empty($prefix)) {// in casePrefixforairStringWill not bedeal withDirect return
            foreach ($_COOKIE as $key => $val) {
                if (0 === stripos($key, $prefix)) {
                    setcookie($key, '', time() - 3600, $config['path'], $config['domain']);
                    unset($_COOKIE[$key]);
                }
            }
        }
        return;
    }
    $name = $config['prefix'] . $name;
    if ('' === $value) {
        if (isset($_COOKIE[$name])) {
            $value = $_COOKIE[$name];
            if (0 === strpos($value, 'think:')) {
                $value = substr($value, 6);
                return array_map('urldecode', json_decode(MAGIC_QUOTES_GPC ? stripslashes($value) : $value, true));
            } else {
                return $value;
            }
        } else {
            return null;
        }
    } else {
        if (is_null($value)) {
            setcookie($name, '', time() - 3600, $config['path'], $config['domain']);
            unset($_COOKIE[$name]); // deleteDesignationcookie
        } else {
            // Set upcookie
            if (is_array($value)) {
                $value = 'think:' . json_encode(array_map('urlencode', $value));
            }
            $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
            setcookie($name, $value, $expire, $config['path'], $config['domain']);
            $_COOKIE[$name] = $value;
        }
    }
}

/**
 * Dynamic load the file extension
 * @return void
 */
function load_ext_file($path)
{
    // loadfromdefinitionOutsidefile
    if (C('LOAD_EXT_FILE')) {
        $files = explode(',', C('LOAD_EXT_FILE'));
        foreach ($files as $file) {
            $file = $path . 'Common/' . $file . '.php';
            if (is_file($file)) include $file;
        }
    }
    // loadfromdefinitionofDynamic  Configuration file 
    if (C('LOAD_EXT_CONFIG')) {
        $configs = C('LOAD_EXT_CONFIG');
        if (is_string($configs)) $configs = explode(',', $configs);
        foreach ($configs as $key => $config) {
            $file = $path . 'Conf/' . $config . '.php';
            if (is_file($file)) {
                is_numeric($key) ? C(include $file) : C($key, include $file);
            }
        }
    }
}

/**
 * Obtaining a ClientIPaddress
 * @param integer $type Return Type 0 returnIPaddress 1 returnIPV4Address numbers
 * @return mixed
 */
function get_client_ip($type = 0)
{
    $type = $type ? 1 : 0;
    static $ip = NULL;
    if ($ip !== NULL) return $ip[$type];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $pos = array_search('unknown', $arr);
        if (false !== $pos) unset($arr[$pos]);
        $ip = trim($arr[0]);
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IPAddress legal verification
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? array($ip, $long) : array($ip, 0);
    return $ip[$type];
}

/**
 * sendHTTPstatus
 * @param integer $code status code
 * @return void
 */
function send_http_status($code)
{
    static $_status = array(
        // Success 2xx
        200 => 'OK',
        // Redirection 3xx
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily ',  // 1.1
        // Client Error 4xx
        400 => 'Bad Request',
        403 => 'Forbidden',
        404 => 'Not Found',
        // Server Error 5xx
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    );
    if (isset($_status[$code])) {
        header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
        // make sureFastCGI mode normal
        header('Status:' . $code . ' ' . $_status[$code]);
    }
}

// not case sensitiveofin_arrayachieve
function in_array_case($value, $array)
{
    return in_array(strtolower($value), array_map('strtolower', $array));
}

function think_filter(&$value)
{
    // TODO Other security filtering

    // filterInquirespecialcharacter
    if (preg_match('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|NOT IN|IN)$/i', $value)) {
        $value .= ' ';
    }
}