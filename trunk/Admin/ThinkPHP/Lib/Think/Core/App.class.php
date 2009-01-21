<?php
// +----------------------------------------------------------------------
// | ThinkPHP
// +----------------------------------------------------------------------
// | Copyright (c) 2008 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

/**
 +------------------------------------------------------------------------------
 * ThinkPHP 应用程序类 执行应用过程管理
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class App extends Base
{//类定义开始

    /**
     +----------------------------------------------------------
     * 应用程序名称
     +----------------------------------------------------------
     * @var string
     * @access protected
     +----------------------------------------------------------
     */
    protected $name ;

    /**
     +----------------------------------------------------------
     * 应用程序标识号
     +----------------------------------------------------------
     * @var string
     * @access protected
     +----------------------------------------------------------
     */
    protected $id;

    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 应用名称
     * @param string $id  应用标识号
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function __construct($name='App',$id='')
    {
        $this->name = $name;
        $this->id   =  $id ;//| create_guid();
    }

    /**
     +----------------------------------------------------------
     * 取得应用实例对象
     * 静态方法
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return App
     +----------------------------------------------------------
     */
    public static function  getInstance()
    {
        return get_instance_of(__CLASS__);
    }

    /**
     +----------------------------------------------------------
     * 应用程序初始化
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function init()
    {
        // 设定错误和异常处理
        set_error_handler(array(&$this,"appError"));
        set_exception_handler(array(&$this,"appException"));

        // 检查项目是否编译过
        // 在部署模式下会自动在第一次执行的时候编译项目
        if(is_file(RUNTIME_PATH.'~app.php') && (!is_file(CONFIG_PATH.'config.php') || filemtime(RUNTIME_PATH.'~app.php')>filemtime(CONFIG_PATH.'config.php'))) {
            // 直接读取编译后的项目文件
            C(include RUNTIME_PATH.'~app.php');
        }else{
            // 预编译项目
            $this->build();
        }

        // 设置系统时区 PHP5支持
        if(function_exists('date_default_timezone_set'))
            date_default_timezone_set(C('TIME_ZONE'));

        if('FILE' != strtoupper(C('SESSION_TYPE'))) {
            // 其它方式Session支持 目前支持Db 通过过滤器方式扩展
            import("Think.Util.Filter");
            Filter::load(ucwords(C('SESSION_TYPE')).'Session');
        }
        // Session初始化
        session_start();

        // 加载插件 必须在Session开启之后加载插件
        if($plugInOn =  C('THINK_PLUGIN_ON')) {
            $this->loadPlugIn();
        }

        // 应用调度过滤器
        // 如果没有加载任何URL调度器
        // 默认只支持 QUERY_STRING 方式
        // 例如 ?m=user&a=add
        if(C('DISPATCH_ON')) {
            if( 'Think'== C('DISPATCH_NAME') ) {
                // 使用内置的ThinkDispatcher调度器
                import('Think.Util.Dispatcher');
                Dispatcher::dispatch();
            }elseif($plugInOn) {
                // 加载第三方调度器
                apply_filter('app_dispatch');
            }
        }

        if(!defined('PHP_FILE')) {
            // PHP_FILE 由内置的Dispacher定义
            // 如果不使用该插件，需要重新定义
            define('PHP_FILE',_PHP_FILE_);
        }

        // 取得模块和操作名称 如果有伪装 则返回真实的名称
        // 可以在Dispatcher中定义获取规则
        if(!defined('MODULE_NAME')) define('MODULE_NAME',   $this->getModule());       // Module名称
        if(!defined('ACTION_NAME')) define('ACTION_NAME',   $this->getAction());        // Action操作

        /* 加载模块配置文件 并自动生成配置缓存文件
        if(is_file(CONFIG_PATH.MODULE_NAME.'_config.php')) {
            C(array_change_key_case(include CONFIG_PATH.MODULE_NAME.'_config.php'));
        }*/

        // 加载模块配置文件 并自动生成配置缓存文件
		if(file_exists(CMSMODULE_PATH.'Conf/'.MODULE_NAME.'.Conf.php')) {
			C(array_change_key_case(include CMSMODULE_PATH.'Conf/'.MODULE_NAME.'.Conf.php'));
		}
		//调用模块函数
		if(file_exists(CMSMODULE_PATH.'Functions/'.MODULE_NAME.'.func.php')) {
			include CMSMODULE_PATH.'Functions/'.MODULE_NAME.'.func.php';
		}
        
        //	启用页面防刷新机制
        if(C('LIMIT_RESFLESH_ON') && (!isset($_REQUEST[C('VAR_RESFLESH')]) || $_REQUEST[C('VAR_RESFLESH')]!="1")) {
            //	启用页面防刷新机制
            $guid	=	md5($_SERVER['PHP_SELF']);
            // 检查页面刷新间隔
            if(Cookie::is_set('_last_visit_time_'.$guid) && Cookie::get('_last_visit_time_'.$guid)>time()-C('LIMIT_REFLESH_TIMES')) {
                // 页面刷新读取浏览器缓存
                header('HTTP/1.1 304 Not Modified');
                exit;
            }else{
                // 缓存当前地址访问时间
                Cookie::set('_last_visit_time_'.$guid,$_SERVER['REQUEST_TIME'],$_SERVER['REQUEST_TIME']+3600);
                header('Last-Modified:'.(date('D,d M Y H:i:s',$_SERVER['REQUEST_TIME']-C('LIMIT_REFLESH_TIMES'))).' GMT');
            }
        }

        // 系统检查
        $this->checkLanguage();     //语言检查
        $this->checkTemplate();     //模板检查

        if(C('HTML_CACHE_ON')) {
            import('Think.Util.HtmlCache');
            HtmlCache::readHTMLCache();
        }
        if($plugInOn) {
            // 应用初始化过滤插件
            apply_filter('app_init');
        }

        // 记录应用初始化时间
        if(C('SHOW_RUN_TIME')){
            $GLOBALS['_initTime'] = microtime(TRUE);
        }

        return ;
    }

    /**
     +----------------------------------------------------------
     * 读取配置信息 编译项目
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    private function build()
    {
        // 加载惯例配置文件
        C(array_change_key_case(include THINK_PATH.'/Common/convention.php'));

        // 加载项目配置文件
        if(file_exists_case(CONFIG_PATH.'config.php')) {
            C(array_change_key_case(include CONFIG_PATH.'config.php'));
        }
        // 加载项目公共文件
        if(file_exists_case(COMMON_PATH.'common.php')) {
            include COMMON_PATH.'common.php';
            if(!C('DEBUG_MODE')) {
                if(defined('STRIP_RUNTIME_SPACE') && STRIP_RUNTIME_SPACE == false ) {
                    $common	= file_get_contents(COMMON_PATH.'common.php');
                }else{
                    $common	= php_strip_whitespace(COMMON_PATH.'common.php');
                }
                if('?>' != substr(trim($common),-2)) {
                    $common .= ' ?>';
                }
            }
        }
        // 如果是调试模式加载调试模式配置文件
        if(C('DEBUG_MODE')) {
            // 加载系统默认的开发模式配置文件
            C(array_change_key_case(include THINK_PATH.'/Common/debug.php'));
            if(file_exists_case(CONFIG_PATH.'debug.php')) {
                // 允许项目增加开发模式配置定义
                C(array_change_key_case(include CONFIG_PATH.'debug.php'));
            }
        }else{
            // 部署模式下面生成编译文件
            // 下次直接加载项目编译文件
            $content  = $common."<?php\nreturn ".var_export(C(),true).";\n?>";
            file_put_contents(RUNTIME_PATH.'~app.php',$content);
        }
        if(C('APP_AUTO_SETUP')) {
            // 开启项目自动安装支持
            if(file_exists_case(COMMON_PATH.'setup.php') && !is_file(APP_PATH.'install.ok')) {
                include COMMON_PATH.'setup.php';
                file_put_contents(APP_PATH.'install.ok','install ok');
            }
        }
        return ;
    }

    /**
     +----------------------------------------------------------
     * 获得实际的模块名称
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    private function getModule()
    {
        $module = isset($_POST[C('VAR_MODULE')]) ?
            $_POST[C('VAR_MODULE')] :
            (isset($_GET[C('VAR_MODULE')])? $_GET[C('VAR_MODULE')]:'');
        // 如果 $module 为空，则赋予默认值
        if (empty($module)) $module = C('DEFAULT_MODULE');
        // 检查组件模块
        if(strpos($module,C('COMPONENT_DEPR'))) {
            // 记录完整的模块名
            define('C_MODULE_NAME',$module);
            $array	=	explode(C('COMPONENT_DEPR'),$module);
            // 实际的模块名称
            $module	=	array_pop($array);
            // 获取组件名称
            if(1==count($array)) {
               define('COMPONENT_NAME',$array[0]);
            }else{
                define('COMPONENT_NAME',implode('/',$array));
            }
        }
        // 检查模块URL伪装
        if(C('MODULE_REDIRECT')) {
            $res = preg_replace('@(\w+):([^,\/]+)@e', '$modules[\'\\1\']="\\2";', C('MODULE_REDIRECT'));
            if(array_key_exists($module,$modules)) {
                // 记录伪装的模块名称
                define('P_MODULE_NAME',$module);
                $module	=	$modules[$module];
            }
        }
        if(C('URL_CASE_INSENSITIVE')) {
            // URL地址不区分大小写
            $module = ucwords(strtolower($module));
        }
        unset($_POST[C('VAR_MODULE')],$_GET[C('VAR_MODULE')]);
        return $module;
    }

    /**
     +----------------------------------------------------------
     * 获得实际的操作名称
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    private function getAction()
    {
        $action   = isset($_POST[C('VAR_ACTION')]) ?
            $_POST[C('VAR_ACTION')] :
            (isset($_GET[C('VAR_ACTION')])?$_GET[C('VAR_ACTION')]:'');
        // 如果 $action 为空，则赋予默认值
        if (empty($action)) $action = C('DEFAULT_ACTION');
        // 检查操作链
        if(strpos($action,C('COMPONENT_DEPR'))) {
            // 记录完整的操作名
            define('C_ACTION_NAME',$action);
            $array	=	explode(C('COMPONENT_DEPR'),$action);
            // 实际的模块名称
            $action	=	array_pop($array);
        }
        // 检查操作URL伪装
        if(C('ACTION_REDIRECT')) {
            $res = preg_replace('@(\w+):([^,\/]+)@e', '$actions[\'\\1\']="\\2";', C('ACTION_REDIRECT'));
            if(array_key_exists($action,$actions)) {
                // 记录伪装的操作名称
                define('P_ACTION_NAME',$action);
                $action	=	$actions[$action];
            }
        }
        unset($_POST[C('VAR_ACTION')],$_GET[C('VAR_ACTION')]);
        return $action;
    }

    /**
     +----------------------------------------------------------
     * 语言检查
     * 检查浏览器支持语言，并自动加载语言包
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    private function checkLanguage()
    {
        if(C('LANG_SWITCH_ON')) {
            // 使用语言包功能
            $defaultLang = C('DEFAULT_LANGUAGE');
            //检测浏览器支持语言
            if(isset($_GET[C('VAR_LANGUAGE')])) {
                // 有在url 里面设置语言
                $langSet = $_GET[C('VAR_LANGUAGE')];
                // 记住用户的选择
                Cookie::set('think_language',$langSet,time()+3600);
            }elseif ( Cookie::is_set('think_language') ) {
                // 获取上次用户的选择
                $langSet = Cookie::get('think_language');
            }else {
                if(C('AUTO_DETECT_LANG') && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                    // 启用自动侦测浏览器语言
                    preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
                    $langSet = $matches[1];
                    Cookie::set('think_language',$langSet,time()+3600);
                }else{
                    // 采用系统设置的默认语言
                    $langSet = $defaultLang;
                }
            }

            // 定义当前语言
            define('LANG_SET',$langSet);
            if(C('LANG_CACHE_ON') && is_file(TEMP_PATH.MODULE_NAME.'_'.LANG_SET.'_lang.php')) {
                // 加载语言包缓存文件
                L(include TEMP_PATH.MODULE_NAME.'_'.LANG_SET.'_lang.php');
            }else{
                // 加载框架语言包
                if (file_exists_case(THINK_PATH.'/Lang/'.LANG_SET.'.php')){
                    L(include THINK_PATH.'/Lang/'.LANG_SET.'.php');
                }else{
                    L(include THINK_PATH.'/Lang/'.$defaultLang.'.php');
                }

                // 读取项目（公共）语言包
                if (file_exists_case(LANG_PATH.LANG_SET.'/common.php'))
                    L(include LANG_PATH.LANG_SET.'/common.php');

                // 读取当前模块的语言包
                if (file_exists_case(LANG_PATH.LANG_SET.'/'.strtolower(MODULE_NAME).'.php'))
                    L(include LANG_PATH.LANG_SET.'/'.strtolower(MODULE_NAME).'.php');

                if(C('LANG_CACHE_ON')) {
                    // 写入语言包缓存文件
                    $content  = "<?php\nreturn ".var_export(L(),true).";\n?>";
                    file_put_contents(TEMP_PATH.MODULE_NAME.'_'.LANG_SET.'_lang.php',$content);
                }
            }
        }else{
            // 不使用语言包功能，仅仅加载框架语言文件
            L(include THINK_PATH.'/Lang/'.C('DEFAULT_LANGUAGE').'.php');
        }
        return ;
    }

    /**
     +----------------------------------------------------------
     * 模板检查，如果不存在使用默认
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    private function checkTemplate()
    {
        if(C('TMPL_SWITCH_ON')) {
            // 启用多模版
            $t = C('VAR_TEMPLATE');
            if ( isset($_GET[$t]) ) {
                $templateSet = $_GET[$t];
                Cookie::set('think_template',$templateSet,time()+3600);
            } else {
                if(Cookie::is_set('think_template')) {
                    $templateSet = Cookie::get('think_template');
                }else {
                    $templateSet =    C('DEFAULT_TEMPLATE');
                    Cookie::set('think_template',$templateSet,time()+3600);
                }
            }
            if (!is_dir(TMPL_PATH.$templateSet)) {
                //模版不存在的话，使用默认模版
                $templateSet =    C('DEFAULT_TEMPLATE');
            }
            //模版名称
            define('TEMPLATE_NAME',$templateSet);
            // 当前模版路径
            define('TEMPLATE_PATH',TMPL_PATH.TEMPLATE_NAME);
            $tmplDir	=	TMPL_DIR.'/'.TEMPLATE_NAME.'/';
        }else{
            // 把模版目录直接放置项目模版文件
            // 该模式下面没有TEMPLATE_NAME常量
            define('TEMPLATE_PATH',TMPL_PATH);
            $tmplDir	=	TMPL_DIR.'/';
        }

        //当前网站地址
        define('__ROOT__',WEB_URL);
        //当前项目地址
        define('__APP__',PHP_FILE);

        $module	=	defined('P_MODULE_NAME')?P_MODULE_NAME:MODULE_NAME;
        $action		=	defined('P_ACTION_NAME')?P_ACTION_NAME:ACTION_NAME;

        //当前页面地址
        define('__SELF__',$_SERVER['PHP_SELF']);
        /*应用URL根目录
        if(C('APP_DOMAIN_DEPLOY')) {
            // 独立域名部署需要指定模板从根目录开始
            $appRoot   =  '/';
        }else{
            $appRoot   =  WEB_URL.'/'.APP_NAME.'/';
        }*/
        // 默认加载的模板文件名
        if(defined('C_MODULE_NAME')) {
            /* 当前模块地址
            define('__URL__',PHP_FILE.'/'.C_MODULE_NAME);
            //当前操作地址
            define('__ACTION__',__URL__.'/'.$action);
            C('TMPL_FILE_NAME',LIB_PATH.COMPONENT_NAME.'/'.TMPL_DIR.'/'.TEMPLATE_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME.C('TEMPLATE_SUFFIX'));
            //项目模板目录
            define('APP_TMPL_URL', $appRoot.LIB_DIR.'/'.COMPONENT_NAME.'/'.TMPL_DIR.'/'.TEMPLATE_NAME.'/');
            define('__CURRENT__', WEB_URL.'/'.APP_NAME.'/'.LIB_DIR.'/'.$tmplDir.str_replace(C('COMPONENT_DEPR'),'/',C_MODULE_NAME));
            //项目模板目录
            define('APP_TMPL_URL', $appRoot.$tmplDir);
            */
            // 当前模块地址
	        define('__URL__',PHP_FILE.'/'.C_MODULE_NAME);
		    //当前操作地址
	        define('__ACTION__',__URL__.'/'.$action);
	        C('TMPL_FILE_NAME',TEMPLATE_PATH.'/'.str_replace(C('COMPONENT_DEPR'),'/',C_MODULE_NAME).'/'.ACTION_NAME.C('TEMPLATE_SUFFIX'));
	        define('__CURRENT__', WEB_URL.'/'.APP_NAME.'/'.$tmplDir.str_replace(C('COMPONENT_DEPR'),'/',C_MODULE_NAME));
        }else{
            // 当前模块地址
            define('__URL__',PHP_FILE.'/'.$module);
            //当前操作地址
            define('__ACTION__',__URL__.'/'.$action);
            C('TMPL_FILE_NAME',TEMPLATE_PATH.'/'.MODULE_NAME.'/'.ACTION_NAME.C('TEMPLATE_SUFFIX'));
            define('__CURRENT__', WEB_URL.'/'.APP_NAME.'/'.$tmplDir.MODULE_NAME);
        }
        //网站公共文件地址
        define('WEB_PUBLIC_URL', WEB_URL.'/Public');
        //项目模板目录
        if(C('APP_DOMAIN_DEPLOY')) {
            // 独立域名部署
            define('APP_TMPL_URL', '/'.$tmplDir);
        }else{
            define('APP_TMPL_URL', WEB_URL.'/'.APP_NAME.'/'.$tmplDir);
        }
        //项目公共文件目录
        define('APP_PUBLIC_URL', APP_TMPL_URL.'Public');

        return ;
    }

    /**
     +----------------------------------------------------------
     * 加载插件
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    private function loadPlugIn()
    {
        // 加载插件必须的函数
        include THINK_PATH.'/Common/plugin.php';
        //加载有效插件文件
        if(is_file(RUNTIME_PATH.'~plugins.php')) {
            include RUNTIME_PATH.'~plugins.php';
        }else{
            // 检查插件数据
            $common_plugins = get_plugins(THINK_PATH.'/PlugIns','Think');// 公共插件
            $app_plugins = get_plugins();// 项目插件
            // 合并插件数据
            $plugins    = array_merge($common_plugins,$app_plugins);
            // 缓存插件数据
            $content	=	'';
            foreach($plugins as $key=>$file) {
                include $file;
                $content	.=	php_strip_whitespace($file);
            }
            file_put_contents(RUNTIME_PATH.'~plugins.php',$content);
        }
        return ;
    }

    /**
     +----------------------------------------------------------
     * 执行应用程序
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function exec()
    {
        // 导入公共类
        $_autoload	=	C('AUTO_LOAD_CLASS');
        if(!empty($_autoload)) {
            $import	=	explode(',',$_autoload);
            foreach ($import as $key=>$class){
                import($class);
            }
        }
        //创建Action控制器实例
        if(defined('C_MODULE_NAME')) {
            $this->initComponent();
            // 使用组件模块
            $module  =  A(C_MODULE_NAME);
        }else{
            $module  =  A(MODULE_NAME);
        }
        if(!$module) {
            // 是否定义Empty模块
            $module	=	A("Empty");
            if(!$module) {
                // 模块不存在 抛出异常
                throw_exception(L('_MODULE_NOT_EXIST_').MODULE_NAME);
            }
        }

        //获取当前操作名
        $action = ACTION_NAME.C('ACTION_SUFFIX');
        if(defined('C_ACTION_NAME')) {
            // 执行操作链 最多只能有一个输出
            $actionList	=	explode(C('COMPONENT_DEPR'),C_ACTION_NAME);
            foreach ($actionList as $action){
                $module->$action();
            }
        }else{
            //如果存在前置操作，首先执行
            if (method_exists($module,'_before_'.$action)) {
                $module->{'_before_'.$action}();
            }
            //执行操作
            $module->{$action}();
            //如果存在后置操作，继续执行
            if (method_exists($module,'_after_'.$action)) {
                $module->{'_after_'.$action}();
            }
        }
        if(C('THINK_PLUGIN_ON')) {
            // 执行应用结束过滤器
            apply_filter('app_end');
        }

        // 写入错误日志
        if(C('WEB_LOG_RECORD'))
            Log::save();

        return ;
    }

    /**
     +----------------------------------------------------------
     * 初始化组件
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    private function initComponent() {
        // 加载组件配置文件
        if(is_file(LIB_PATH.COMPONENT_NAME.'/Conf/config.php'))
            C(array_change_key_case(include LIB_PATH.COMPONENT_NAME.'/Conf/config.php'));
        // 加载组件公共文件
        if(is_file(LIB_PATH.COMPONENT_NAME.'/Common/common.php'))
            include LIB_PATH.COMPONENT_NAME.'/Common/common.php';
        // 加载组件语言文件
        if (is_file(LIB_PATH.COMPONENT_NAME.'/Lang/'.LANG_SET.'.php'))
            L(include LIB_PATH.COMPONENT_NAME.'/Lang/'.LANG_SET.'.php');
    }

    /**
     +----------------------------------------------------------
     * 运行应用实例 入口文件使用的快捷方法
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function run() {
        $this->init();
        $this->exec();
        return ;
    }

    /**
     +----------------------------------------------------------
     * 自定义异常处理
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $e 异常对象
     +----------------------------------------------------------
     */
    public function appException($e)
    {
        halt($e->__toString());
    }

    /**
     +----------------------------------------------------------
     * 自定义错误处理
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function appError($errno, $errstr, $errfile, $errline)
    {
      switch ($errno) {
          case E_ERROR:
          case E_USER_ERROR:
              $errorStr = "错误：[$errno] $errstr ".basename($errfile)." 第 $errline 行.\n";
              if(C('WEB_LOG_RECORD')){
                 Log::record($errorStr);
                 Log::save();
              }
              halt($errorStr);
              break;
          case E_STRICT:
          case E_USER_WARNING:
          case E_USER_NOTICE:
          default:
            $errorStr = "注意：[$errno] $errstr ".basename($errfile)." 第 $errline 行.\n";
            Log::record($errorStr);
             break;
      }
    }

};//类定义结束
?>