<?php
header('X-Content-Type-Options: nosniff');
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
use Think\Exception;

if (M_DEBUG == 1) {
    header('X-Powered-By: ' . SHORT_NAME);
}

function cleaninput($input)
{
    $search = array(
        '@<script[^>]*?>.*?</script>@si', // Strip out javascript
        '@<[/!]*?[^<>]*?>@i', // Strip out HTML tags
        '@<style[^>]*?>.*?</style>@siU', // Strip style tags properly
        '@<![\s\S]*?--[ \t\n\r]*>@' // Strip multi-line comments
    );

    return preg_replace($search, '', $input);
}

function sanitize($input)
{
    $output = [];
    if (is_array($input)) {
        foreach ($input as $var => $val) {
            $output[$var] = sanitize($val);
        }
    } else {
        $input = str_replace('"', "", $input);
        $input = str_replace("'", "", $input);
        $input = cleaninput($input);
        $output = htmlentities($input, ENT_QUOTES);
    }
    return $output;
}

function executeSecurity()
{

    $_POST = sanitize($_POST);
    $_GET = sanitize($_GET);
    $_REQUEST = sanitize($_REQUEST);
    $_COOKIE = sanitize($_COOKIE);
    if (isset($_SESSION)) {
        $_SESSION = sanitize($_SESSION);
    }

    $query_string = @$_SERVER['QUERY_STRING'];

    $patterns = array(
        "codono_",
        "union",
        "coockie",
        "cookie",
        "session",
        "concat",
        "alter",
        "table",
        "where",
        "exec",
        "shell",
        "wget",
        "**/",
        "/**",
        "0x3a",
        "null",
        "DR/**/OP/",
        "/*",
        "*/",
        "*",
        "--",
        ";",
        "||",
        "'",
        "' #",
        "or 1=1",
        "'1'='1",
        "BUN",
        "S@BUN",
        "char ",
        "OR%",
        "`",
        "[",
        "]",
        "<",
        ">",
        "++",
        "script",
        "select",
        "1,1",
        "substring",
        "ascii",
        "sleep(",
        "&&",
        "insert",
        "between",
        "values",
        "truncate",
        "benchmark",
        "sql",
        "mysql",
        "%27",
        "%22",
        "(",
        ")",
        "<?",
        "<?php",
        "?>",
        "../",
        "/localhost",
        "127.0.0.1",
        "loopback",
        ":",
        "%0A",
        "%0D",
        "%3C",
        "%3E",
        "%00",
        "%2e%2e",
        "input_file",
        "execute",
        "mosconfig",
        "environ",
        "scanner",
        "path=.",
        "mod=.",
        "eval\(",
        "javascript:",
        "base64_",
        "boot.ini",
        "etc/passwd",
        "self/environ",
        "md5",
        "echo.*kae",
        "=%27$"
    );
	$query_allowed_length=350;
	$query_length=strlen($query_string);
    foreach ($patterns as $pattern) {
        if ($query_length > $query_allowed_length or strpos(strtolower($query_string), strtolower($pattern)) !== false) {
            if($query_length> $query_allowed_length){
				$pattern="Query string is too long $query_length allowed is $query_allowed_length";
			}
			if($pattern=='select' && $query_string=='s=/Public/template/js/bootstrap-select.js' ){
				
				continue;
			}
			$activity = json_encode(['pattern_found' => $pattern,'query'=>$query_string, 'post' => $_POST, 'get' => $_GET, 'request' => $_REQUEST, 'url' => $_SERVER['REQUEST_URI'], 'IP' => hacker_ip(), 'time' => date('d-m-Y H:i:s')]);
            $new_filename = date('d-m-Y') . "_" . 'suspicious_activity';
            file_put_contents('Public/Log/' . $new_filename . '.log', $activity, FILE_APPEND);
            echo "IP RECORDED and ACTIVITY BLOCKED";
            exit(1);
        }
    }

}

function hacker_ip()
{
    if (getenv('HTTP_CLIENT_IP'))
        $ipaddress = getenv('HTTP_CLIENT_IP');
    else if (getenv('HTTP_X_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
    else if (getenv('HTTP_X_FORWARDED'))
        $ipaddress = getenv('HTTP_X_FORWARDED');
    else if (getenv('HTTP_FORWARDED_FOR'))
        $ipaddress = getenv('HTTP_FORWARDED_FOR');
    else if (getenv('HTTP_FORWARDED'))
        $ipaddress = getenv('HTTP_FORWARDED');
    else if (getenv('REMOTE_ADDR'))
        $ipaddress = getenv('REMOTE_ADDR');
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

executeSecurity();
// **PREVENTING SESSION HIJACKING**
// Prevents javascript XSS attacks aimed to steal the session ID
ini_set('session.cookie_httponly', 1);

// **PREVENTING SESSION FIXATION**
// Session ID cannot be passed through URLs
ini_set('session.use_only_cookies', 1);

// Uses a secure connection (HTTPS) if possible
//ini_set('session.cookie_secure', 1);

if ( M_DEBUG == 0) {
    define('APP_DEBUG', 0);
    require dirname(__FILE__) . DIRECTORY_SEPARATOR.'Bootstrap.php';
} else {

    define('APP_DEBUG', 1);
    if (isset($_GET['debug']) && $_GET['debug'] === ADMIN_KEY) {
        setcookie('ADBUG', 'codono', time() + 60 * 3600);
        exit('ok');
    }

    if (isset($_COOKIE['ADBUG']) && $_COOKIE['ADBUG'] == ADMIN_KEY) {
        // Open debugging mode
        require dirname(__FILE__) .DIRECTORY_SEPARATOR. 'Bootstrap.php';

    } else {
        // Open debugging mode
        if (!defined('APP_DEBUG')) {
            define('APP_DEBUG', 0);
        }

        try {
            require dirname(__FILE__) . DIRECTORY_SEPARATOR.'Bootstrap.php';
        } catch (Exception $exception) {

                if($exception->xdebug_message)
                {echo '<table>'.$exception->xdebug_message.'</table>';}

            send_http_status(404);
            $string = file_get_contents('.'.DIRECTORY_SEPARATOR.'Public'.DIRECTORY_SEPARATOR.'inline-error.html');
            if (M_DEBUG) {
                $string = str_replace('$error', $exception->getMessage(), $string);
            } else {
                clog('exception', [$exception->getMessage(), $exception]);
                $string = str_replace('$error', 'Please try in sometime', $string);
            }
            $string = str_replace('SITE_URL', SITE_URL, $string);
            $string = str_replace('SITE_NAME', SHORT_NAME, $string);
            echo $string;
        }
    }
}