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
 * ThinkPHP内置的Dispatcher类
 * 完成URL解析、路由和调度
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class Dispatcher extends Base
{//类定义开始

    private static $useRoute = false;
    /**
     +----------------------------------------------------------
     * URL映射到控制器对象
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static function dispatch()
    {
        $urlMode  =  C('URL_MODEL');
        if($urlMode == URL_REWRITE ) {
            //当前项目地址
            $url    =   dirname(_PHP_FILE_);
            if($url == '/' || $url == '\\') {
                $url    =   '';
            }
            define('PHP_FILE',$url);
        }elseif($urlMode == URL_COMPAT){
            define('PHP_FILE',_PHP_FILE_.'?'.C('VAR_PATHINFO').'=');
        }else {
            //当前项目地址
            define('PHP_FILE',_PHP_FILE_);
        }
        if($urlMode == URL_PATHINFO || $urlMode == URL_REWRITE || $urlMode == URL_COMPAT) {
            // 检查PATHINFO
            if(!empty($_GET[C('VAR_PATHINFO')])) {
                // 兼容PATHINFO 参数
                $_SERVER['PATH_INFO']   =   $_GET[C('VAR_PATHINFO')];
                unset($_GET[C('VAR_PATHINFO')]);
			}elseif(!isset($_SERVER["PATH_INFO"]))
			{
				$_SERVER['PATH_INFO'] = "";
			}elseif (empty($_SERVER["PATH_INFO"]))
			{
                // 在FastCGI模式下面 $_SERVER["PATH_INFO"] 为空
                $_SERVER['PATH_INFO'] = str_replace($_SERVER['SCRIPT_NAME'], "", $_SERVER['REQUEST_URI']);
            }

            if (!empty($_GET) && C('URL_AUTO_REDIRECT') && !isset($_GET[C('VAR_PATHINFO')]) && !isset($_GET[C('VAR_ROUTER')])) {
                $_GET  =  array_merge (self :: getPathInfo(),$_GET);
                $_varModule =   C('VAR_MODULE');
                $_varAction =   C('VAR_ACTION');
                $_depr  =   C('PATH_DEPR');
                $_pathModel =   C('PATH_MODEL');
                // 设置默认模块和操作
                if(empty($_GET[$_varModule])) $_GET[$_varModule] = C('DEFAULT_MODULE');
                if(empty($_GET[$_varAction])) $_GET[$_varAction] = C('DEFAULT_ACTION');
                // 组装新的URL地址
                $_URL = '/';
                if($_pathModel==2) {
                    $_URL .= $_GET[$_varModule].$_depr.$_GET[$_varAction].$_depr;
                    unset($_GET[$_varModule],$_GET[$_varAction]);
                }
                foreach ($_GET as $_VAR => $_VAL) {
                    if('' != trim($_GET[$_VAR])) {
                        if($_pathModel==2) {
                            $_URL .= $_VAR.$_depr.rawurlencode($_VAL).$_depr;
                        }else{
                            $_URL .= $_VAR.'/'.rawurlencode($_VAL).'/';
                        }
                    }
                }
                if($_depr==',') $_URL = substr($_URL, 0, -1).'/';
                //重定向成规范的URL格式
                redirect(PHP_FILE.$_URL);
            }
            if(C('ROUTER_ON')) {
                // 检测路由规则
                self::routerCheck();
            }
            //给_GET赋值 以保证可以按照正常方式取_GET值
            $_GET = array_merge(self :: getPathInfo(),$_GET);
            //保证$_REQUEST正常取值
            $_REQUEST = array_merge($_POST,$_GET);

        }else {
            // URL_COMMON 模式
            if(!empty($_SERVER['PATH_INFO']) ) {
                $pathinfo = self :: getPathInfo();
                $_GET = array_merge($_GET,$pathinfo);
                if(!empty($_POST)) {
                    $_POST = array_merge($_POST,$pathinfo);
                }elseif(C('URL_AUTO_REDIRECT')) {
                    // 把pathinfo方式转换成query变量
                    $jumpUrl = PHP_FILE.'?'.http_build_query($_GET);
                    //重定向成规范的URL格式
                    redirect($jumpUrl);
                }
            }else {
                // 正常模式
                if(C('URL_AUTO_REDIRECT')) {
                    // 过滤重复的query_string
                    $query  = explode('&',trim($_SERVER['QUERY_STRING'],'&'));
                    if(count($query) != count($_GET) && count($_GET)>0) {
                        $_URL  =  '';
                        foreach ($_GET as $_VAR => $_VAL) {
                            $_URL .= $_VAR.'='.rawurlencode($_VAL).'&';
                        }
                        $jumpUrl = PHP_FILE.'?'.substr($_URL,0,-1);
                        //重定向成规范的URL格式
                        redirect($jumpUrl);
                    }
                }
                //  检查路由规则
                if(isset($_GET[C('VAR_ROUTER')])) {
                    self::routerCheck();
                }
            }
        }
        //字符转义还原
        //self :: MagicQuote();
    }

    /**
     +----------------------------------------------------------
     * 字符MagicQuote转义过滤
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    private static function MagicQuote()
    {
        if ( get_magic_quotes_gpc() ) {
           $_POST = stripslashes_deep($_POST);
           $_GET = stripslashes_deep($_GET);
           $_COOKIE = stripslashes_deep($_COOKIE);
           $_REQUEST= stripslashes_deep($_REQUEST);
        }
    }

    /**
     +----------------------------------------------------------
     * 路由检测
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    private static function routerCheck() {
        // 搜索路由映射 把路由名称解析为对应的模块和操作
        if(file_exists_case(CONFIG_PATH.'routes.php')) {
            $routes = include CONFIG_PATH.'routes.php';
            if(!is_array($routes)) {
                $routes =   $_routes;
            }
            if(C('HTML_URL_SUFFIX')) {
                $suffix =   substr(C('HTML_URL_SUFFIX'),1);
                $_SERVER['PATH_INFO']   =   preg_replace('/\.'.$suffix.'$/','',$_SERVER['PATH_INFO']);
            }
            if(isset($_GET[C('VAR_ROUTER')])) {
                // 存在路由变量
                $routeName  =   $_GET[C('VAR_ROUTER')];
                unset($_GET[C('VAR_ROUTER')]);
            }else{
                $paths = explode(C('PATH_DEPR'),trim($_SERVER['PATH_INFO'],'/'));
                // 获取路由名称
                $routeName  =   array_shift($paths);
            }
            if(isset($routes[$routeName])) {
                // 读取当前路由名称的路由规则
                // 路由定义格式 routeName=>array(‘模块名称’,’操作名称’,’参数定义’,’额外参数’)
                $route = $routes[$routeName];
                $_GET[C('VAR_MODULE')]  =   $route[0];
                $_GET[C('VAR_ACTION')]  =   $route[1];
                //  获取当前路由参数对应的变量
                if(!isset($_GET[C('VAR_ROUTER')])) {
                    $vars    =   explode(',',$route[2]);
                    for($i=0;$i<count($vars);$i++) {
                        $_GET[$vars[$i]]     =   array_shift($paths);
                    }
                    // 解析剩余的URL参数
                    $res = preg_replace('@(\w+)\/([^,\/]+)@e', '$_GET[\'\\1\']="\\2";', implode('/',$paths));
                }
                if(isset($route[3])) {
                    // 路由里面本身包含固定参数 形式为 a=111&b=222
                    parse_str($route[3],$params);
                    $_GET   =   array_merge($_GET,$params);
                }
                unset($_SERVER['PATH_INFO']);
            }elseif(isset($routes[$routeName.'@'])){
                // 存在泛路由
                // 路由定义格式 routeName@=>array(
                // array('路由正则1',‘模块名称’,’操作名称’,’参数定义’,’额外参数’),
                // array('路由正则2',‘模块名称’,’操作名称’,’参数定义’,’额外参数’),
                // ...)
                $routeItem = $routes[$routeName.'@'];
                $regx = str_replace($routeName,'',trim($_SERVER['PATH_INFO'],'/'));
                foreach ($routeItem as $route){
                    $rule    =   $route[0];             // 路由正则
                    if(preg_match($rule,$regx,$matches)) {
                        // 匹配路由定义
                        $_GET[C('VAR_MODULE')]  =   $route[1];
                        $_GET[C('VAR_ACTION')]  =   $route[2];
                        //  获取当前路由参数对应的变量
                        if(!isset($_GET[C('VAR_ROUTER')])) {
                            $vars    =   explode(',',$route[3]);
                            for($i=0;$i<count($vars);$i++) {
                                $_GET[$vars[$i]]     =   $matches[$i+1];
                            }
                            // 解析剩余的URL参数
                            $res = preg_replace('@(\w+)\/([^,\/]+)@e', '$_GET[\'\\1\']="\\2";', str_replace($matches[0],'',$regx));
                        }
                        if(isset($route[4])) {
                            // 路由里面本身包含固定参数 形式为 a=111&b=222
                            parse_str($route[4],$params);
                            $_GET   =   array_merge($_GET,$params);
                        }
                        //unset($_SERVER['PATH_INFO']);
                        self::$useRoute = true;
                        break;
                    }
                }
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 获得PATH_INFO信息
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    private static function getPathInfo()
    {
        $pathInfo = array();
        if(!empty($_SERVER['PATH_INFO'])) {
            if(C('HTML_URL_SUFFIX')) {
                $suffix =   substr(C('HTML_URL_SUFFIX'),1);
                $_SERVER['PATH_INFO']   =   preg_replace('/\.'.$suffix.'$/','',$_SERVER['PATH_INFO']);
            }
            if(C('PATH_MODEL')==2){
                $paths = explode(C('PATH_DEPR'),trim($_SERVER['PATH_INFO'],'/'));
                $pathInfo[C('VAR_MODULE')] = array_shift($paths);
                $pathInfo[C('VAR_ACTION')] = array_shift($paths);
                for($i = 0, $cnt = count($paths); $i <$cnt; $i++){
                    if(isset($paths[$i+1])) {
                        $pathInfo[$paths[$i]] = (string)$paths[++$i];
                    }elseif($i==0) {
                        $pathInfo[$pathInfo[C('VAR_ACTION')]] = (string)$paths[$i];
                    }
                }
            }
            else {
                $res = preg_replace('@(\w+)'.C('PATH_DEPR').'([^,\/]+)@e', '$pathInfo[\'\\1\']="\\2";', $_SERVER['PATH_INFO']);
            }
        }
        return $pathInfo;
    }

}//类定义结束
?>