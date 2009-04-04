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
 * Think公共函数库
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Common
 * @author   liu21st <liu21st@gmail.com>
 * @version  $Id$
 +------------------------------------------------------------------------------
 */

function get_client_ip(){
   if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
       $ip = getenv("HTTP_CLIENT_IP");
   else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
       $ip = getenv("HTTP_X_FORWARDED_FOR");
   else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
       $ip = getenv("REMOTE_ADDR");
   else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
       $ip = $_SERVER['REMOTE_ADDR'];
   else
       $ip = "unknown";
   return($ip);
}

/**
 +----------------------------------------------------------
 * URL组装 支持不同模式和路由
 +----------------------------------------------------------
 * @param string $action 操作名
 * @param string $module 模块名
 * @param string $app 项目名
 * @param string $route 路由名
 * @param array $params 其它URL参数
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function url($action=ACTION_NAME,$module=MODULE_NAME,$route='',$app=APP_NAME,$params=array()) {
    if(C('DISPATCH_ON') && C('URL_MODEL')>0) {
        switch(C('PATH_MODEL')) {
            case 1:// 普通PATHINFO模式
                $str    =   '/';
                foreach ($params as $var=>$val)
                    $str .= $var.'/'.$val.'/';
                $str = substr($str,0,-1);
                if(!empty($route)) {
                    $url    =   str_replace(APP_NAME,$app,__APP__).'/'.C('VAR_ROUTER').'/'.$route.'/'.$str;
                }else{
                    $url    =   str_replace(APP_NAME,$app,__APP__).'/'.C('VAR_MODULE').'/'.$module.'/'.C('VAR_ACTION').'/'.$action.$str;
                }
                break;
            case 2:// 智能PATHINFO模式
                $depr   =   C('PATH_DEPR');
                $str    =   $depr;
                foreach ($params as $var=>$val)
                    $str .= $var.$depr.$val.$depr;
                $str = substr($str,0,-1);
                if(!empty($route)) {
                    $url    =   str_replace(APP_NAME,$app,__APP__).'/'.$route.$str;
                }else{
                    $url    =   str_replace(APP_NAME,$app,__APP__).'/'.$module.$depr.$action.$str;
                }
                break;
        }
        if(C('HTML_URL_SUFFIX')) {
            $url .= C('HTML_URL_SUFFIX');
        }
    }else{
        $params =   http_build_query($params);
        if(!empty($route)) {
            $url    =   str_replace(APP_NAME,$app,__APP__).'?'.C('VAR_ROUTER').'='.$route.'&'.$params;
        }else{
            $url    =   str_replace(APP_NAME,$app,__APP__).'?'.C('VAR_MODULE').'='.$module.'&'.C('VAR_ACTION').'='.$action.'&'.$params;
        }
    }
    return $url;
}

/**
 +----------------------------------------------------------
 * 错误输出
 * 在调试模式下面会输出详细的错误信息
 * 否则就定向到指定的错误页面
 +----------------------------------------------------------
 * @param mixed $error 错误信息 可以是数组或者字符串
 * 数组格式为异常类专用格式 不接受自定义数组格式
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function halt($error) {
    $e = array();
    if(C('DEBUG_MODE') && strtoupper(C( 'TMPL_ENGINE_TYPE' )) == 'THINK'){
        //调试模式下输出错误信息
        if(!is_array($error)) {
            $trace = debug_backtrace();
            $e['message'] = $error;
            $e['file'] = $trace[0]['file'];
            $e['class'] = $trace[0]['class'];
            $e['function'] = $trace[0]['function'];
            $e['line'] = $trace[0]['line'];
            $traceInfo='';
            $time = date("y-m-d H:i:m");
            foreach($trace as $t)
            {
                $traceInfo .= '['.$time.'] '.$t['file'].' ('.$t['line'].') ';
                $traceInfo .= $t['class'].$t['type'].$t['function'].'(';
                $traceInfo .= implode(', ', $t['args']);
                $traceInfo .=")<br/>";
            }
            $e['trace']  = $traceInfo;
        }else {
            $e = $error;
        }
        if(C('EXCEPTION_TMPL_FILE')) {
            // 定义了异常页面模板
            include C('EXCEPTION_TMPL_FILE');
        }else{
            // 使用默认的异常模板文件
            include THINK_PATH.'/Tpl/ThinkException.tpl.php';
        }
    }
    else
    {
        //否则定向到错误页面
        $error_page =   C('ERROR_PAGE');
        if(!empty($error_page)){
            redirect($error_page);
        }else {
            if(C('SHOW_ERROR_MSG')) {
                $e['message'] =  is_array($error)?$error['message']:$error;
            }else{
                $e['message'] = C('ERROR_MESSAGE');
            }
            if(C('EXCEPTION_TMPL_FILE')) {
                // 定义了异常页面模板
                include C('EXCEPTION_TMPL_FILE');
            }else{
                // 使用默认的异常模板文件
                include THINK_PATH.'/Tpl/ThinkException.tpl.php';
            }
        }
    }
    exit;
}

/**
 +----------------------------------------------------------
 * URL重定向
 +----------------------------------------------------------
 * @static
 * @access public
 +----------------------------------------------------------
 * @param string $url  要定向的URL地址
 * @param integer $time  定向的延迟时间，单位为秒
 * @param string $msg  提示信息
 +----------------------------------------------------------
 */
function redirect($url,$time=0,$msg='')
{
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if(empty($msg)) {
        $msg    =   "系统将在{$time}秒之后自动跳转到{$url}！";
    }
    if (!headers_sent()) {
        // redirect
        header("Content-Type:text/html; charset=".C('OUTPUT_CHARSET'));
        if(0===$time) {
            header("Location: ".$url);
        }else {
            header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit();
    }else {
        $str    = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if($time!=0) {
            $str   .=   $msg;
        }
        exit($str);
    }
}

/**
 +----------------------------------------------------------
 * 自定义异常处理
 +----------------------------------------------------------
 * @param string $msg 错误信息
 * @param string $type 异常类型 默认为ThinkException
 * 如果指定的异常类不存在，则直接输出错误信息
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function throw_exception($msg,$type='ThinkException',$code=0)
{
    if(isset($_REQUEST[C('VAR_AJAX_SUBMIT')])) {
        header("Content-Type:text/html; charset=utf-8");
        exit($msg);
    }
    if(class_exists($type,false)){
        throw new $type($msg,$code,true);
    }else {
        // 异常类型不存在则输出错误信息字串
        halt($msg);
    }
}

/**
 +----------------------------------------------------------
 *  区间调试开始
 +----------------------------------------------------------
 * @param string $label  标记名称
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function debug_start($label='')
{
    $GLOBALS[$label]['_beginTime'] = microtime(TRUE);
    if ( MEMORY_LIMIT_ON )  $GLOBALS[$label]['memoryUseStartTime'] = memory_get_usage();
}

/**
 +----------------------------------------------------------
 *  区间调试结束，显示指定标记到当前位置的调试
 +----------------------------------------------------------
 * @param string $label  标记名称
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function debug_end($label='')
{
    $GLOBALS[$label]['_endTime'] = microtime(TRUE);
    echo '<div style="text-align:center;width:100%">Process '.$label.': Times '.number_format($GLOBALS[$label]['_endTime']-$GLOBALS[$label]['_beginTime'],6).'s ';
    if ( MEMORY_LIMIT_ON )  {
        $GLOBALS[$label]['memoryUseEndTime'] = memory_get_usage();
        echo ' Memories '.number_format(($GLOBALS[$label]['memoryUseEndTime']-$GLOBALS[$label]['memoryUseStartTime'])/1024).' k';
    }
    echo '</div>';
}

/**
 +----------------------------------------------------------
 * 系统调试输出 Log::record 的一个调用方法
 +----------------------------------------------------------
 * @param string $msg 调试信息
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function system_out($msg)
{
    if(!empty($msg))
        Log::record($msg,WEB_LOG_DEBUG);
}

/**
 +----------------------------------------------------------
 * 变量输出
 +----------------------------------------------------------
 * @param string $var 变量名
 * @param string $label 显示标签
 * @param string $echo 是否显示
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function dump($var, $echo=true,$label=null, $strict=true)
{
    $label = ($label===null) ? '' : rtrim($label) . ' ';
    if(!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = "<pre>".$label.htmlspecialchars($output,ENT_QUOTES,C('OUTPUT_CHARSET'))."</pre>";
        } else {
            $output = $label . " : " . print_r($var, true);
        }
    }else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if(!extension_loaded('xdebug')) {
            $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
            $output = '<pre>'
                    . $label
                    . htmlspecialchars($output, ENT_QUOTES,C('OUTPUT_CHARSET'))
                    . '</pre>';
        }
    }
    if ($echo) {
        echo($output);
        return null;
    }else {
        return $output;
    }
}

/**
 +----------------------------------------------------------
 * 自动转换字符集 支持数组转换
 * 需要 iconv 或者 mb_string 模块支持
 * 如果 输出字符集和模板字符集相同则不进行转换
 +----------------------------------------------------------
 * @param string $fContents 需要转换的字符串
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function auto_charset($fContents,$from='',$to=''){
    if(empty($from)) $from = C('TEMPLATE_CHARSET');
    if(empty($to))  $to =   C('OUTPUT_CHARSET');
    $from   =  strtoupper($from)=='UTF8'? 'utf-8':$from;
    $to       =  strtoupper($to)=='UTF8'? 'utf-8':$to;
    if( strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents)) ){
        //如果编码相同或者非字符串标量则不转换
        return $fContents;
    }
    if(is_string($fContents) ) {
        if(function_exists('mb_convert_encoding')){
            return mb_convert_encoding ($fContents, $to, $from);
        }elseif(function_exists('iconv')){
            return iconv($from,$to,$fContents);
        }else{
            halt(L('_NO_AUTO_CHARSET_'));
            return $fContents;
        }
    }
    elseif(is_array($fContents)){
        foreach ( $fContents as $key => $val ) {
            $_key =     auto_charset($key,$from,$to);
            $fContents[$_key] = auto_charset($val,$from,$to);
            if($key != $_key ) {
                unset($fContents[$key]);
            }
        }
        return $fContents;
    }
    elseif(is_object($fContents)) {
        $vars = get_object_vars($fContents);
        foreach($vars as $key=>$val) {
            $fContents->$key = auto_charset($val,$from,$to);
        }
        return $fContents;
    }
    else{
        //halt('系统不支持对'.gettype($fContents).'类型的编码转换！');
        return $fContents;
    }
}

/**
 +----------------------------------------------------------
 * 取得对象实例 支持调用类的静态方法
 +----------------------------------------------------------
 * @param string $className 对象类名
 * @param string $method 类的静态方法名
 +----------------------------------------------------------
 * @return object
 +----------------------------------------------------------
 */
function get_instance_of($className,$method='',$args=array())
{
    static $_instance = array();
    if(empty($args)) {
        $identify   =   $className.$method;
    }else{
        $identify   =   $className.$method.to_guid_string($args);
    }
    if (!isset($_instance[$identify])) {
        if(class_exists($className)){
            $o = new $className();
            if(method_exists($o,$method)){
                if(!empty($args)) {
                    $_instance[$identify] = call_user_func_array(array(&$o, $method), $args);
                }else {
                    $_instance[$identify] = $o->$method();
                }
            }
            else
                $_instance[$identify] = $o;
        }
        else
            halt(L('_CLASS_NOT_EXIST_'));
    }
    return $_instance[$identify];
}

/**
 +----------------------------------------------------------
 * 系统自动加载ThinkPHP基类库和当前项目的model和Action对象
 * 并且支持配置自动加载路径
 +----------------------------------------------------------
 * @param string $classname 对象类名
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function __autoload($classname)
{
    // 自动加载当前项目的Actioon类和Model类
    if(substr($classname,-5)=="Model") {
        if(!import('@.Model.'.$classname)){
            // 如果加载失败 尝试加载组件Model类库
            import("@.*.Model.".$classname);
        }
    }elseif(substr($classname,-6)=="Action"){
        if(!import('@.Action.'.$classname)) {
            // 如果加载失败 尝试加载组件Action类库
            import("@.*.Action.".$classname);
        }
    }else {
        // 根据自动加载路径设置进行尝试搜索
        if(C('AUTO_LOAD_PATH')) {
            $paths  =   explode(',',C('AUTO_LOAD_PATH'));
            foreach ($paths as $path){
                if(import($path.$classname)) {
                    // 如果加载类成功则返回
                    return ;
                }
            }
        }
    }
    return ;
}

/**
 +----------------------------------------------------------
 * 反序列化对象时自动回调方法
 +----------------------------------------------------------
 * @param string $classname 对象类名
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function unserialize_callback($classname)
{
    // 根据自动加载路径设置进行尝试搜索
    if(C('CALLBACK_LOAD_PATH')) {
        $paths  =   explode(',',C('CALLBACK_LOAD_PATH'));
        foreach ($paths as $path){
            if(import($path.$classname)) {
                // 如果加载类成功则返回
                return ;
            }
        }
    }
}

$GLOBALS['import_file'] =  array();
/**
 +----------------------------------------------------------
 * 优化的include_once
 +----------------------------------------------------------
 * @param string $filename 文件名
 +----------------------------------------------------------
 * @return boolen
 +----------------------------------------------------------
 */
function include_cache($filename)
{
    if (!isset($GLOBALS['import_file'][$filename])) {
        if(file_exists_case($filename)){
            include $filename;
            $GLOBALS['import_file'][$filename] = true;
        }
        else
        {
            $GLOBALS['import_file'][$filename] = false;
        }
    }
    return $GLOBALS['import_file'][$filename];
}

/**
 +----------------------------------------------------------
 * 优化的require_once
 +----------------------------------------------------------
 * @param string $filename 文件名
 +----------------------------------------------------------
 * @return boolen
 +----------------------------------------------------------
 */
function require_cache($filename)
{
    if (!isset($GLOBALS['import_file'][$filename])) {
        if(file_exists_case($filename)){
            require $filename;
            $GLOBALS['import_file'][$filename] = true;
        }
        else
        {
            $GLOBALS['import_file'][$filename] = false;
        }
    }
    return $GLOBALS['import_file'][$filename];
}

// 区分大小写的文件存在判断
function file_exists_case($filename) {
    if(is_file($filename)) {
        if(IS_WIN && C('CHECK_FILE_CASE')) {
            if(basename(realpath($filename)) != basename($filename)) {
                return false;
            }
        }
        return true;
    }
    return false;
}

/**
 +----------------------------------------------------------
 * 导入所需的类库 支持目录和* 同java的Import
 * 本函数有缓存功能
 +----------------------------------------------------------
 * @param string $class 类库命名空间字符串
 * @param string $baseUrl 起始路径
 * @param string $appName 项目名
 * @param string $ext 导入的文件扩展名
 * @param string $subdir 是否导入子目录 默认false
 +----------------------------------------------------------
 * @return boolen
 +----------------------------------------------------------
 */
function import($class,$baseUrl = '',$ext='.class.php',$subdir=false)
{
      //echo('<br>'.$class.$baseUrl);
      static $_file = array();
      static $_class = array();
      $class    =   str_replace(array('.','#'), array('/','.'), $class);
      if(isset($_file[strtolower($class.$baseUrl)]))
            return true;
      else
            $_file[strtolower($class.$baseUrl)] = true;
      //if (preg_match('/[^a-z0-9\-_.*]/i', $class)) throw_exception('Import非法的类名或者目录！');
      if( 0 === strpos($class,'@'))     $class =  str_replace('@',APP_NAME,$class);
      if(empty($baseUrl)) {
            // 默认方式调用应用类库
            $baseUrl   =  dirname(LIB_PATH);
      }else {
            //相对路径调用
            $isPath =  true;
      }
      $class_strut = explode("/",$class);
      if('*' == $class_strut[0] || isset($isPath) ) {
        //多级目录加载支持
        //用于子目录递归调用
      }
      elseif(APP_NAME == $class_strut[0]) {
          //加载当前项目应用类库
          $class =  str_replace(APP_NAME.'/',LIB_DIR.'/',$class);
      }
      elseif(in_array(strtolower($class_strut[0]),array('think','org','com'))) {
          //加载ThinkPHP基类库或者公共类库
          // think 官方基类库 org 第三方公共类库 com 企业公共类库
          $baseUrl =  THINK_PATH.'/'.LIB_DIR.'/';
      }else {
          // 加载其他项目应用类库
          $class    =   substr_replace($class, '', 0,strlen($class_strut[0])+1);
          $baseUrl =  APP_PATH.'/../'.$class_strut[0].'/'.LIB_DIR.'/';
      }
      if(substr($baseUrl, -1) != "/")    $baseUrl .= "/";
      $classfile = $baseUrl . $class . $ext;
      if(false !== strpos($classfile,'*') || false !== strpos($classfile,'?') ) {
            // 导入匹配的文件
            $match  =   glob($classfile);
            if($match) {
               foreach($match as $key=>$val) {
                   if(is_dir($val)) {
                       if($subdir) import('*',$val.'/',$ext,$subdir);
                   }else{
                       if($ext == '.class.php') {
                            // 冲突检测
                            $class = basename($val,$ext);
                            if(isset($_class[$class])) {
                                throw_exception($class.L('_CLASS_CONFLICT_'));
                            }
                            $_class[$class] = $val;
                       }
                        //导入类库文件
                        require_cache($val);
                   }
               }
               return true;
            }else{
               return false;
            }
      }else{
          if($ext == '.class.php' && is_file($classfile)) {
                // 冲突检测
                $class = basename($classfile,$ext);
                if(isset($_class[strtolower($class)])) {
                    throw_exception(L('_CLASS_CONFLICT_').':'.$_class[strtolower($class)].' '.$classfile);
                }
                $_class[strtolower($class)] = $classfile;
          }
            //导入目录下的指定类库文件
            return require_cache($classfile);
      }
}

/**
 +----------------------------------------------------------
 * import方法的别名
 +----------------------------------------------------------
 * @param string $package 包名
 * @param string $baseUrl 起始路径
 * @param string $ext 导入的文件扩展名
 * @param string $subdir 是否导入子目录 默认false
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function using($class,$baseUrl = LIB_PATH,$ext='.class.php',$subdir=false)
{
    return import($class,$baseUrl,$ext,$subdir);
}

// 快速导入第三方框架类库
// 所有第三方框架的类库文件统一放到 基类库Vendor目录下面
// 并且默认都是以.php后缀导入
function vendor($class,$baseUrl = '',$ext='.php',$subdir=false)
{
    if(empty($baseUrl)) {
        $baseUrl    =   VENDOR_PATH;
    }
    return import($class,$baseUrl,$ext,$subdir);
}

/**
 +----------------------------------------------------------
 * 根据PHP各种类型变量生成唯一标识号
 +----------------------------------------------------------
 * @param mixed $mix 变量
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function to_guid_string($mix)
{
    if(is_object($mix) && function_exists('spl_object_hash')) {
        return spl_object_hash($mix);
    }elseif(is_resource($mix)){
        $mix = get_resource_type($mix).strval($mix);
    }else{
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 +----------------------------------------------------------
 * 判断是否为对象实例
 +----------------------------------------------------------
 * @param mixed $object 实例对象
 * @param mixed $className 对象名
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function is_instance_of($object, $className)
{
	if (!is_object($object) && !is_string($object)) {
		return false;
	}
    return $object instanceof $className;
}

/**
 +----------------------------------------------------------
 * 字符串截取，支持中文和其他编码
 +----------------------------------------------------------
 * @static
 * @access public
 +----------------------------------------------------------
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true)
{
	if($suffix)
		$suffixStr = "…";
	else
		$suffixStr = "";

    if(function_exists("mb_substr"))
        return mb_substr($str, $start, $length, $charset).$suffixStr;
    elseif(function_exists('iconv_substr')) {
        return iconv_substr($str,$start,$length,$charset).$suffixStr;
    }
    $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("",array_slice($match[0], $start, $length));
    return $slice.$suffixStr;
}

/**
 +----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
 +----------------------------------------------------------
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function rand_string($len=6,$type='',$addChars='') {
    $str ='';
    switch($type) {
        case 0:
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.$addChars;
            break;
        case 1:
            $chars= str_repeat('0123456789',3);
            break;
        case 2:
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ'.$addChars;
            break;
        case 3:
            $chars='abcdefghijklmnopqrstuvwxyz'.$addChars;
            break;
        case 4:
            $chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借".$addChars;
            break;
        default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            $chars='ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'.$addChars;
            break;
    }
    if($len>10 ) {//位数过长重复字符串一定次数
        $chars= $type==1? str_repeat($chars,$len) : str_repeat($chars,5);
    }
    if($type!=4) {
        $chars   =   str_shuffle($chars);
        $str     =   substr($chars,0,$len);
    }else{
        // 中文随机字
        for($i=0;$i<$len;$i++){
          $str.= msubstr($chars, floor(mt_rand(0,mb_strlen($chars,'utf-8')-1)),1);
        }
    }
    return $str;
}

/**
 +----------------------------------------------------------
 * 获取登录验证码 默认为4位数字
 +----------------------------------------------------------
 * @param string $fmode 文件名
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function build_verify ($length=4,$mode=1) {
    return rand_string($length,$mode);
}

/**
 +----------------------------------------------------------
 * stripslashes扩展 可用于数组
 +----------------------------------------------------------
 * @param mixed $value 变量
 +----------------------------------------------------------
 * @return mixed
 +----------------------------------------------------------
 */
if(!function_exists('stripslashes_deep')) {
    function stripslashes_deep($value) {
       $value = is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
       return $value;
    }
}

/**
 +----------------------------------------------------------
 * D函数用于实例化Model
 +----------------------------------------------------------
 * @param string className Model名称
 * @param string appName Model所在项目
 +----------------------------------------------------------
 * @return Model
 +----------------------------------------------------------
 */
function D($className='',$appName='')
{
    static $_model = array();
    if(empty($className)) {
        return new  Model();
    }
    if(empty($appName)) {
        $appName =  C('DEFAULT_MODEL_APP');
    }
    if(isset($_model[$appName.$className])) {
        return $_model[$appName.$className];
    }
    $OriClassName = $className;
    if(strpos($className,C('COMPONENT_DEPR'))) {
        $array  =   explode(C('COMPONENT_DEPR'),$className);
        $className = array_pop($array);
        $className =  C('MODEL_CLASS_PREFIX').$className.C('MODEL_CLASS_SUFFIX');
        if(C('COMPONENT_TYPE')==1) {
            import($appName.'.'.implode('.',$array).'.Model.'.$className);
        }else{
            import($appName.'.Model.'.implode('.',$array).'.'.$className);
        }
    }else{
        $className =  C('MODEL_CLASS_PREFIX').$className.C('MODEL_CLASS_SUFFIX');
        if(!import($appName.'.Model.'.$className)) {
            // 如果加载失败 尝试自动匹配
            if(C('COMPONENT_TYPE')==1) {
                import($appName.'.*.Model.'.$className);
            }else{
                import($appName.'.Model.*.'.$className);
            }
        }
    }
    if(class_exists($className)) {
        $model = new $className();
        $_model[$appName.$OriClassName] =  $model;
        return $model;
    }else {
        throw_exception($className.L('_MODEL_NOT_EXIST_'));
        return false;
    }
}

/**
 +----------------------------------------------------------
 * A函数用于实例化Action
 +----------------------------------------------------------
 * @param string className Action名称
 * @param string appName Model所在项目
 +----------------------------------------------------------
 * @return Action
 +----------------------------------------------------------
 */
function A($className,$appName='@')
{
    static $_action = array();
    if(isset($_action[$appName.$className])) {
        return $_action[$appName.$className];
    }
    $OriClassName = $className;
    if(strpos($className,C('COMPONENT_DEPR'))) {
        $array  =   explode(C('COMPONENT_DEPR'),$className);
        $className = array_pop($array);
        $className =  C('CONTR_CLASS_PREFIX').$className.C('CONTR_CLASS_SUFFIX');
        if(C('COMPONENT_TYPE')==1) {
            import($appName.'.'.implode('.',$array).'.Action.'.$className);
        }else{
            import($appName.'.Action.'.implode('.',$array).'.'.$className);
        }
    }else{
        $className =  C('CONTR_CLASS_PREFIX').$className.C('CONTR_CLASS_SUFFIX');
        if(!import($appName.'.Action.'.$className)) {
            // 如果加载失败 尝试加载组件类库
            if(C('COMPONENT_TYPE')==1) {
                import($appName.'.*.Action.'.$className);
            }else{
                import($appName.'.Action.*.'.$className);
            }
        }
    }
    if(class_exists($className)) {
        $action = new $className();
        $_action[$appName.$OriClassName] = $action;
        return $action;
    }else {
        return false;
    }
}

// 获取语言定义
function L($name='',$value=null) {
    static $_lang = array();
    if(!is_null($value)) {
        $_lang[strtolower($name)]   =   $value;
        return;
    }
    if(empty($name)) {
        return $_lang;
    }
    if(is_array($name)) {
        $_lang = array_merge($_lang,array_change_key_case($name));
        return;
    }
    if(isset($_lang[strtolower($name)])) {
        return $_lang[strtolower($name)];
    }else{
        return false;
    }
}

// 获取配置值
function C($name='',$value=null) {
    static $_config = array();
    if(!is_null($value)) {
        if(strpos($name,'.')) {
            $array   =  explode('.',strtolower($name));
            $_config[$array[0]][$array[1]] =   $value;
        }else{
            $_config[strtolower($name)] =   $value;
        }
        return ;
    }
    if(empty($name)) {
        return $_config;
    }
    // 缓存全部配置值
    if(is_array($name)) {
        $_config = array_merge($_config,array_change_key_case($name));
        return $_config;
    }
    if(strpos($name,'.')) {
        $array   =  explode('.',strtolower($name));
        return $_config[$array[0]][$array[1]];
    }elseif(isset($_config[strtolower($name)])) {
        return $_config[strtolower($name)];
    }else{
        return NULL;
    }
}

// 全局缓存设置和读取
function S($name,$value='',$expire='',$type='') {
    static $_cache = array();
    import('Think.Util.Cache');
    //取得缓存对象实例
    $cache  = Cache::getInstance($type);
    if('' !== $value) {
        if(is_null($value)) {
            // 删除缓存
            $result =   $cache->rm($name);
            if($result) {
                unset($_cache[$type.'_'.$name]);
            }
            return $result;
        }else{
            // 缓存数据
            $cache->set($name,$value,$expire);
            $_cache[$type.'_'.$name]     =   $value;
        }
        return ;
    }
    if(isset($_cache[$type.'_'.$name])) {
        return $_cache[$type.'_'.$name];
    }
    // 获取缓存数据
    $value      =  $cache->get($name);
    $_cache[$type.'_'.$name]     =   $value;
    return $value;
}

// 快速文件数据读取和保存 针对简单类型数据 字符串、数组
function F($name,$value='',$expire=-1,$path=DATA_PATH) {
    static $_cache = array();
    $filename   =   $path.$name.'.php';
    if('' !== $value) {
        if(is_null($value)) {
            // 删除缓存
            $result =   unlink($filename);
            if($result) {
                unset($_cache[$name]);
            }
            return $result;
        }else{
            // 缓存数据
            $content   =   "<?php\nif (!defined('THINK_PATH')) exit();\n//".sprintf('%012d',$expire)."\nreturn ".var_export($value,true).";\n?>";
            $result  =   file_put_contents($filename,$content);
            $_cache[$name]   =   $value;
        }
        return ;
    }
    if(isset($_cache[$name])) {
        return $_cache[$name];
    }
    // 获取缓存数据
    if(is_file($filename) && false !== $content = file_get_contents($filename)) {
        $expire  =  (int)substr($content,44, 12);
        if($expire != -1 && time() > filemtime($filename) + $expire) {
            //缓存过期删除缓存文件
            unlink($filename);
            return false;
        }
        $str       = substr($content,57,-2);
        $value    = eval($str);
        $_cache[$name]   =   $value;
    }else{
        $value  =   false;
    }
    return $value;
}

// 快速创建一个对象实例
function I($class,$baseUrl = '',$ext='.class.php') {
    static $_class = array();
    if(isset($_class[$baseUrl.$class])) {
        return $_class[$baseUrl.$class];
    }
    $class_strut = explode(".",$class);
    $className  =   array_pop($class_strut);
    if($className != '*') {
        import($class,$baseUrl,$ext,false);
        if(class_exists($className)) {
            $_class[$baseUrl.$class] = new $className();
            return $_class[$baseUrl.$class];
        }else{
            return false;
        }
    }else {
        return false;
    }
}

// xml编码
function xml_encode($data,$encoding='utf-8',$root="think") {
    $xml = '<?xml version="1.0" encoding="'.$encoding.'"?>';
    $xml.= '<'.$root.'>';
    $xml.= data_to_xml($data);
    $xml.= '</'.$root.'>';
    return $xml;
}

function data_to_xml($data) {
    if(is_object($data)) {
        $data = get_object_vars($data);
    }
    $xml = '';
    foreach($data as $key=>$val) {
        is_numeric($key) && $key="item id=\"$key\"";
        $xml.="<$key>";
        $xml.=(is_array($val)||is_object($val))?data_to_xml($val):$val;
        list($key,)=explode(' ',$key);
        $xml.="</$key>";
    }
    return $xml;
}

function mk_dir($dir, $mode = 0755)
{
  if (is_dir($dir) || @mkdir($dir,$mode)) return true;
  if (!mk_dir(dirname($dir),$mode)) return false;
  return @mkdir($dir,$mode);
}

// 清除缓存目录
function clearCache($type=0,$path=NULL) {
        if(is_null($path)) {
            switch($type) {
            case 0:// 模版缓存目录
                $path = CACHE_PATH;
                break;
            case 1:// 数据缓存目录
                $path   =   TEMP_PATH;
                break;
            case 2://  日志目录
                $path   =   LOG_PATH;
                break;
            case 3://  数据目录
                $path   =   DATA_PATH;
            }
        }
        import("ORG.Io.Dir");
        Dir::del($path);
    }
?>