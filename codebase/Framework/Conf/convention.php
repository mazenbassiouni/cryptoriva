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

defined('THINK_PATH') or exit();
$cookie_lang="en";
if(isset($_COOKIE['lang'])){
if (preg_match("/^[A-Za-z0-9-]+$/", $_COOKIE['lang'])) {
    // contains only letters, numbers and hyphens
    $cookie_lang=$_COOKIE['lang'];
}
}

return array(
    /* Application settings */
    'APP_USE_NAMESPACE' => true,    // application Class Library use or not Namespaces
    'APP_SUB_DOMAIN_DEPLOY' => false,   // whether Open child area name deploy
    'APP_SUB_DOMAIN_RULES' => array(), // child area name deploy rule
    'APP_DOMAIN_SUFFIX' => '', // area name suffix if com net 
    'ACTION_SUFFIX' => '', // operating method suffix
    'MULTI_MODULE' => true, // whether allow Multi-module If false then have to Set up DEFAULT_MODULE
    'MODULE_DENY_LIST' => array('Common', 'Runtime'),
    'CONTROLLER_LEVEL' => 1,
    'APP_AUTOLOAD_LAYER' => 'Controller,Model', // Automatically loadofapplicationClass LibraryFloor shut downAPP_USE_NAMESPACEAfter the effective
    'APP_AUTOLOAD_PATH' => '', // Automatically loadofpath shut downAPP_USE_NAMESPACEAfter the effective

    /* CookieSet up */
    'COOKIE_EXPIRE' => 0,       // CookieValidity
    'COOKIE_DOMAIN' => '',      // Cookieeffectiveareaname
    'COOKIE_PATH' => '/',     // Cookiepath
    'COOKIE_PREFIX' => '',      // CookiePrefix avoid confict
    'COOKIE_SECURE' => false,   // CookieSecure transmission
    'COOKIE_HTTPONLY' => '',      // Cookie httponlySet up

    /* Default settings */
    'DEFAULT_M_LAYER' => 'Model', // defaultofModel layer name
    'DEFAULT_C_LAYER' => 'Controller', // defaultofControllerFloorname
    'DEFAULT_V_LAYER' => 'View', // defaultofView layername
    'LANG_LIST' => 'en,nl', // List of languages enabled
	'DEFAULT_LANG' => $cookie_lang, // 
	
	'VAR_LANGUAGE' => 'l', // variable to change language using get
    'DEFAULT_THEME' => '',    // defaultTemplate Themename
    'DEFAULT_MODULE' => 'Home',  // defaultModule
    'DEFAULT_CONTROLLER' => 'Index', // defaultControllername
    'DEFAULT_ACTION' => 'index', // defaultoperatingname
    'DEFAULT_CHARSET' => 'utf-8', // defaultOutput encoding
    'DEFAULT_TIMEZONE' => 'UTC',    // defaultTime zone
    'DEFAULT_AJAX_RETURN' => 'JSON',  // defaultAJAX datareturnformat,OptionalJSON XML ...
    'DEFAULT_JSONP_HANDLER' => 'jsonpReturn', // defaultJSONPformatreturndeal withmethod
    'DEFAULT_FILTER' => 'htmlspecialchars', // The default parametersFiltration method ForIfunction...

    /* Database Settings */
    'DB_TYPE' => '',     // database tyoe
    'DB_HOST' => 'localhost', // host
    'DB_NAME' => 'btccoin7',          // db name
    'DB_USER' => '1',      // username
    'DB_PWD' => '1',          // password
    'DB_PORT' => '',        // port
    'DB_PREFIX' => '',    // databaseTable Prefix
    'DB_PARAMS' => array(), // Database Connectivityparameter    
    'DB_DEBUG' => TRUE, // Database Debuggingmode After opening can be recordedSQLJournal
    'DB_FIELDS_CACHE' => true,        // EnableFieldCache
    'DB_CHARSET' => 'utf8',      // databasecodingdefaultuseutf8
    'DB_DEPLOY_TYPE' => 0, // databasedeploythe way:0 centralized(singleserver),1 distributed(Master-slaveserver)
    'DB_RW_SEPARATE' => false,       // databaseRead and writewhetherSeparate Master-slave effective
    'DB_MASTER_NUM' => 1, // After the separate read and write Primary serverQuantity
    'DB_SLAVE_NO' => '', // DesignationFromserverNo.

    /* Data cache settings */
    'DATA_CACHE_TIME' => 0,      // dataCache Expiration 0Permanent representationCache
    'DATA_CACHE_COMPRESS' => true,   // dataCachewhethercompressionCache
    'DATA_CACHE_CHECK' => true,   // dataCachewhethercheckCache
    'DATA_CACHE_PREFIX' => CRON_KEY.'_',     // CachePrefix unique prefix for security you can change it 
    'DATA_CACHE_TYPE' => 'File',  // dataCache type,stand by:File|Db|Apc|Memcache|Shmop|Sqlite|Xcache|Apachenote|Eaccelerator
    'DATA_CACHE_PATH' => TEMP_PATH,// Cache pathSet up (OnlyFilethe wayCacheeffective)
    'DATA_CACHE_KEY' => CRON_KEY.'_',    // Cache file KEY (valid only for File mode cache)
    'DATA_CACHE_SUBDIR' => true,    // Use subdirectory cache (automatically create subdirectories based on the hash of the cache ID)
    'DATA_PATH_LEVEL' => 0,        // childtable of ContentsCachelevel
	'REDIS_PASSWORD' => REDIS_PASSWORD,        // childtable of ContentsCachelevel

    /* Error settings */
    'ERROR_MESSAGE' => 'Page Fault! Please try again later ~',//errordisplayinformation,Non-debugmodeeffective
    'ERROR_PAGE' => '',    // errorsettopage
    'SHOW_ERROR_MSG' => false,    // displayError Messages
    'TRACE_MAX_RECORD' => 100,    // EachLevelsError Messages maximumrecordingnumber

    /* Log Settings */
    'LOG_RECORD' => true,   // default Logging
    'LOG_TYPE' => 'File', // JournalrecordingTypes of The default isfilethe way
    'LOG_LEVEL' => 'EMERG,ALERT,CRIT,ERR,WARN',// allowrecordingofLog Level
    'LOG_FILE_SIZE' => 2097152,    // JournalFile sizelimit
    'LOG_EXCEPTION_RECORD' => false,    // whetherrecordingabnormalinformationJournal

    /* SESSIONSet up */
    'SESSION_AUTO_START' => true,    // whetherAutomatically openSession
    'SESSION_OPTIONS' => array(), // session ConfigurationArray stand bytype name id path expire domain Waitparameter
    'SESSION_TYPE' => '', // session handerTypes of No need to set default unlessSpreadThesession handerdrive
    'SESSION_PREFIX' => '', // session Prefix
    //'VAR_SESSION_ID'      =>  'session_id',     //sessionIDSubmission variables

    /* Template engine settings */
    'TMPL_CONTENT_TYPE' => 'text/html', // defaulttemplateOutput Type
    'TMPL_ACTION_ERROR' => THINK_PATH . 'Tpl'.DIRECTORY_SEPARATOR.'dispatch_jump.tpl', // defaulterrorJumpcorrespondingTemplate files
    'TMPL_ACTION_SUCCESS' => THINK_PATH . 'Tpl'.DIRECTORY_SEPARATOR.'dispatch_jump.tpl', // defaultsuccessJumpcorrespondingTemplate files
    'TMPL_EXCEPTION_FILE' => THINK_PATH . 'Tpl'.DIRECTORY_SEPARATOR.'think_exception.tpl',// abnormalpageofTemplate files
    'TMPL_DETECT_THEME' => false,       // automaticDetectionTemplate Theme
    'TMPL_TEMPLATE_SUFFIX' => '.html',     // defaultTemplate filessuffix
    'TMPL_FILE_DEPR' => '/', //Template filesCONTROLLER_NAMEversusACTION_NAMEbetweenofDelimiter
    // layoutSet up
    'TMPL_ENGINE_TYPE' => 'Think',     // defaultTemplate engine With下Set upOnlyuseThinkTemplate engineeffective
    'TMPL_CACHFILE_SUFFIX' => '.php',      // defaulttemplateCachesuffix
    'TMPL_DENY_FUNC_LIST' => 'echo,exit',    // Template engineDisablefunction
    'TMPL_DENY_PHP' => false, // defaultTemplate enginewhetherDisablePHPPrimevalCode
    'TMPL_L_DELIM' => '{',            // Template engineordinarylabelStartmark
    'TMPL_R_DELIM' => '}',            // Template engineordinarylabelEndmark
    'TMPL_VAR_IDENTIFY' => 'array',     // Template variablesRecognition。Leave blankAutomatically determine the,Parameters'obj'Said objects
    'TMPL_STRIP_SPACE' => true,       // whetherRemovalTemplate filesinsidehtmlSpaces and line breaks
    'TMPL_CACHE_ON' => true,        // whetherOpentemplateCompileCache,SetfalseThen every timeAgainCompile
    'TMPL_CACHE_PREFIX' => '',         // Prefix template cacheMark, Can be changed dynamically
    'TMPL_CACHE_TIME' => 0,         // templateCache Expiration 0 Permanent,(WithdigitalThe value,unit:second)
    'TMPL_LAYOUT_ITEM' => '{__CONTENT__}', // layouttemplateContentreplaceMark
    'LAYOUT_ON' => false, // whetherEnablelayout
    'LAYOUT_NAME' => 'layout', // currentLayout name The default islayout

    // ThinkTemplate engineTag LibraryRelatedset up
    'TAGLIB_BEGIN' => '<',  // Tag LibrarylabelStartmark
    'TAGLIB_END' => '>',  // Tag LibrarylabelEndmark
    'TAGLIB_LOAD' => true, // use or notInternalTag LibraryOutside theotherTag Library,defaultautomaticDetect
    'TAGLIB_BUILD_IN' => 'cx', // InternalName tag library(labeluseNeed notDesignationName tag library),By commasSeparated noteResolveorder
    'TAGLIB_PRE_LOAD' => '',   // needadditionalloads MarkStorehouse(MustDesignationName tag library),MoreBy commasSeparated 

    /* URLSet up */
    'URL_CASE_INSENSITIVE' => true,   // defaultfalse ShowURLCase sensitive trueIt meansnot case sensitive
    'URL_MODEL' => 1,       // URLAccess mode,Optional parameters0,1,2,3,Representatives of the following fourmode：
    // 0 (Normal mode); 1 (PATHINFO mode); 2 (REWRITE  mode); 3 (Compatibility Mode)  The default isPATHINFO mode
    'URL_PATHINFO_DEPR' => '/',    // PATHINFO mode ,eachparameterbetweenofseparator
    'URL_PATHINFO_FETCH' => 'ORIG_PATH_INFO,REDIRECT_PATH_INFO,REDIRECT_URL', // ForcompatiblejudgmentPATH_INFO ParametersSERVERSubstitutevariableList
    'URL_REQUEST_URI' => 'REQUEST_URI', // Get the currentpageaddressofSystem Variables The default isREQUEST_URI
    'URL_HTML_SUFFIX' => 'html',  // URLPseudo-staticsuffixSet up
    'URL_DENY_SUFFIX' => 'ico|png|gif|jpg', // URLNo AccessofsuffixSet up
    'URL_PARAMS_BIND' => true, // URLVariable is bound toActionMethod parameters
    'URL_PARAMS_BIND_TYPE' => 0, // URLType of variable bindings 0 Bind variables by name 1 Bind by variable order
    'URL_PARAMS_FILTER' => false, // URLVariable binding filter
    'URL_PARAMS_FILTER_TYPE' => '', // URLVariable bindings filtration method If it is empty transfer DEFAULT_FILTER
    'URL_ROUTER_ON' => false,   // whetherOpenURLrouting
    'URL_ROUTE_RULES' => array(), // defaultroutingrule againstModule
    'URL_MAP_RULES' => array(), // URLMapping definition rules

    /* The system variable name settings */
    'VAR_MODULE' => 'm',     // defaultModuleObtainvariable
    'VAR_ADDON' => 'addon',     // defaultPlug-inControllerNamespacesvariable
    'VAR_CONTROLLER' => 'c',    // defaultControllerObtainvariable
    'VAR_ACTION' => 'a',    // defaultoperatingObtainvariable
    'VAR_AJAX_SUBMIT' => 'ajax',  // defaultofAJAXsubmitvariable
    'VAR_JSONP_HANDLER' => 'callback',
    'VAR_PATHINFO' => 's',    // Compatibility ModePATHINFOGet variables such as ?s=/module/action/id/1 The latter parameter dependsURL_PATHINFO_DEPR
    'VAR_TEMPLATE' => 't',    // defaulttemplateSwitching variable
    'VAR_AUTO_STRING' => false,    // entervariablewhetherautomaticCastforString If you turnthenArrayvariableneedManuallyIncoming variablesModifiersObtainvariable

    'HTTP_CACHE_CONTROL' => 'private',  // Network Page CacheControl
    'CHECK_APP_DIR' => true,       // whetheran examinationApplication Directorywhethercreate
    'FILE_UPLOAD_TYPE' => 'Local',    // fileUploadthe way
    'DATA_CRYPT_TYPE' => 'Think',    // dataencryptionthe way


    'THINK_EMAIL' => array(
        'SMTP_HOST' => 'someemail.com', //SMTP SERVER
        'SMTP_PORT' => '25', //PORT
        'SMTP_USER' => 'testmail@someemail.com', //EMAIL
        'SMTP_PASS' => 'password', //PASSWORD
        'FROM_EMAIL' => 'testmail@someemail.com', //FROM SENDER HEADER
        'FROM_NAME' => 'BTC TESTING', //FROM NAME HEADER
        'REPLY_EMAIL' => '', //Reply email (leave blank for sender EMAIL)
        'REPLY_NAME' => '', //Reply name (Leave blank for sender name)
    ),

);
