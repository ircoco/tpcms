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
 * ThinkPHP 视图输出
 * 支持缓存和页面压缩
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author liu21st <liu21st@gmail.com>
 * @version  $Id$
 +------------------------------------------------------------------------------
 */
class View extends Base
{
    /**
     +----------------------------------------------------------
     * 模板页面显示变量，未经定义的变量不会显示在页面中
     +----------------------------------------------------------
     * @var array
     * @access protected
     +----------------------------------------------------------
     */
    protected $tVar        =  array();

    protected $trace       = array();

    // 使用的模板引擎类型
    protected $type        =   '';

   /**
     +----------------------------------------------------------
     * 取得模板对象实例
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return Template
     +----------------------------------------------------------
     */
    static function getInstance() {
        return get_instance_of(__CLASS__);
    }

    // 构造函数
    public function __construct($type='') {
        if(!empty($type)) {
            $this->type = $type;
        }else{
            $this->type = strtoupper(C('TMPL_ENGINE_TYPE'));
        }
    }

    /**
     +----------------------------------------------------------
     * 模板变量赋值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $name
     * @param mixed $value
     +----------------------------------------------------------
     */
    public function assign($name,$value=''){
        if(is_array($name)) {
            $this->tVar   =  array_merge($this->tVar,$name);
        }elseif(is_object($name)){
            foreach($name as $key =>$val)
            {
                $this->tVar[$key] = $val;
            }
        }else {
            $this->tVar[$name] = $value;
        }
    }

    /**
     +----------------------------------------------------------
     * Trace变量赋值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $name
     * @param mixed $value
     +----------------------------------------------------------
     */
    public function trace($title,$value='') {
        if(is_array($title)) {
            $this->trace   =  array_merge($this->trace,$title);
        }else {
            $this->trace[$title] = $value;
        }
    }

    /**
     +----------------------------------------------------------
     * 取得模板变量的值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function get($name){
        if(isset($this->tVar[$name])) {
            return $this->tVar[$name];
        }else {
            return false;
        }
    }

    public function __set($name,$value) {
        $this->assign($name,$value);
    }

    public function __get($name) {
        return $this->get($name);
    }

    /**
     +----------------------------------------------------------
     * 加载模板和页面输出 可以返回输出内容
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $templateFile 模板文件名 留空为自动获取
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     * @param string $varPrefix 模板变量前缀
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function display($templateFile='',$charset='',$contentType='text/html',$varPrefix='')
    {
        $this->fetch($templateFile,$charset,$contentType,$varPrefix,true);
    }

    /**
     +----------------------------------------------------------
     * 显示运行时间、数据库操作、缓存次数、内存使用信息
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $startTime 开始时间
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    protected function showTime($startTime) {
        if(C('SHOW_RUN_TIME')) {
            // 显示运行时间
            $endTime = microtime(TRUE);
            $total_run_time =   number_format(($endTime - $GLOBALS['_beginTime']), 3);
            $showTime   =   'Process: '.$total_run_time.'s ';
            if(C('SHOW_ADV_TIME')) {
                // 显示详细运行时间
                $_load_time =   number_format(($GLOBALS['_loadTime'] -$GLOBALS['_beginTime'] ), 3);
                $_init_time =   number_format(($GLOBALS['_initTime'] -$GLOBALS['_loadTime'] ), 3);
                $_exec_time =   number_format(($startTime  -$GLOBALS['_initTime'] ), 3);
                $_parse_time    =   number_format(($endTime - $startTime), 3);
                $showTime .= '( Load:'.$_load_time.'s Init:'.$_init_time.'s Exec:'.$_exec_time.'s Template:'.$_parse_time.'s )';
            }
            if(C('SHOW_DB_TIMES') && class_exists('Db',false) ) {
                // 显示数据库操作次数
                $db =   Db::getInstance();
                $showTime .= ' | DB :'.$db->Q().' queries '.$db->W().' writes ';
            }
            if(C('SHOW_CACHE_TIMES') && class_exists('Cache',false)) {
                // 显示数据库操作次数
                $cache  =   Cache::getInstance();
                $showTime .= ' | Cache :'.$cache->Q().' gets '.$cache->W().' writes ';
            }
            if(MEMORY_LIMIT_ON && C('SHOW_USE_MEM')) {
                // 显示内存开销
                $startMem    =  array_sum(explode(' ', $GLOBALS['_startUseMems']));
                $endMem     =  array_sum(explode(' ', memory_get_usage()));
                $showTime .= ' | UseMem:'. number_format(($endMem - $startMem)/1024).' kb';
            }
            return $showTime;
        }
    }

    /**
     +----------------------------------------------------------
     * 输出布局模板
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $layout 指定要输出的布局模板
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     * @param string $varPrefix 模板变量前缀
     * @param string $display 是否直接显示
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function layout($layoutFile='',$charset='',$contentType='text/html',$varPrefix='',$display=true)
    {
        $startTime = microtime(TRUE);
        if(empty($layoutFile)) {
            // 留空获取默认配置的布局模板文件
            $layoutFile  =  C('DEFAULT_LAYOUT');
        }
        if(false === strpos($layoutFile,':')) {
            // 默认获取layout下面的布局文件
            // 如果不是需要另外指定 例如 Public:index
            $layoutFile  =  'Layout:'.$layoutFile;
        }
        // 获取布局模板文件
        $content    =   $this->fetch($layoutFile,$charset,$contentType,$varPrefix,false,false);
        // 查找布局包含的页面
        $find = preg_match_all('/<!-- layout::(.+?)::(.+?) -->/is',$content,$matches);
        if($find) {
            for ($i=0; $i< $find; $i++) {
                // 读取相关的页面模板替换布局单元
                if(0===strpos($matches[1][$i],'$')){
                    // 动态布局
                    $matches[1][$i]  =  $this->get(substr($matches[1][$i],1));
                }
                if(0 != $matches[2][$i] ) {
                    // 设置了布局缓存
                    // 检查布局缓存是否有效
                    $guid =  md5($matches[1][$i]);
                    $cache  =  S($guid);
                    if($cache) {
                        $layoutContent = $cache;
                    }else{
                        $layoutContent = $this->fetch($matches[1][$i],$charset,$contentType,$varPrefix,false,false);
                        S($guid,$layoutContent,$matches[2][$i]);
                    }
                }else{
                    $layoutContent = $this->fetch($matches[1][$i],$charset,$contentType,$varPrefix,false,false);
                }
                $content    =   str_replace($matches[0][$i],$layoutContent,$content);
            }
        }

		if(C('HTML_CACHE_ON')) {
            // 写入静态文件
            HtmlCache::writeHTMLCache($content);
        }

        if($display) {
            $showTime   =   $this->showTime($startTime);
            echo $content;
            if(C('SHOW_RUN_TIME')) {
                echo '<div  id="think_run_time" class="think_run_time">'.$showTime.'</div>';
            }
            $this->showTrace($showTime,$charset);
            return null;
        }else{
            return $content;
        }
    }

    /**
     +----------------------------------------------------------
     * 检查缓存文件是否有效
     * 如果无效则需要重新编译
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $tmplTemplateFile  模板文件名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    protected function checkCache($tmplTemplateFile)
    {
        $tmplCacheFile = CACHE_PATH.md5($tmplTemplateFile).C('CACHFILE_SUFFIX');
        if(!is_file($tmplCacheFile)){
            return false;
        }
        elseif (!C('TMPL_CACHE_ON')){
            return false;
        }elseif (filemtime($tmplTemplateFile) > filemtime($tmplCacheFile)) {
            // 模板文件如果有更新则缓存需要更新
            return false;
        } elseif (C('TMPL_CACHE_TIME') != -1 && time() > filemtime($tmplCacheFile)+C('TMPL_CACHE_TIME')) {
            // 缓存是否在有效期
            return false;
        }
        //缓存有效
        return true;
    }

    /**
     +----------------------------------------------------------
     * 加载模板和页面输出
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $templateFile 模板文件名 留空为自动获取
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     * @param string $varPrefix 模板变量前缀
     * @param integer $mode 0 返回 1 输出 2 下载
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function fetch($templateFile='',$charset='',$contentType='text/html',$varPrefix='',$display=false,$htmlCache=true)
    {
        $startTime = microtime(TRUE);
        if(null===$templateFile) {
            // 使用null参数作为模版名直接返回不做任何输出
            return ;
        }
        if('layout::'==substr($templateFile,0,8)) {
            $this->layout(substr($templateFile,8));
            return ;
        }
        if(empty($charset)) {
            $charset = C('OUTPUT_CHARSET');
        }
        // 网页字符编码
        header("Content-Type:".$contentType."; charset=".$charset);
        header("Cache-control: private");  //支持页面回跳

        // 设置输出缓存
        ini_set('output_buffering',4096);
        $zlibCompress   =  ini_get('zlib.output_compression');
        if(empty($zlibCompress) && function_exists('ini_set')) {
            ini_set( 'zlib.output_compression', 1 );
        }
        $pluginOn   =  C('THINK_PLUGIN_ON');
        if($pluginOn) {
            // 缓存初始化过滤
            apply_filter('ob_init');
        }
        //页面缓存
        ob_start();
        ob_implicit_flush(0);
        if($pluginOn) {
            // 缓存开启后执行的过滤
            apply_filter('ob_start');
            // 模版文件名过滤
            $templateFile = apply_filter('template_file',$templateFile);
        }

        if(!file_exists_case($templateFile)){
            if(''==$templateFile) {
                // 如果模板文件名为空 按照默认规则定位
                $templateFile = C('TMPL_FILE_NAME');
            }elseif(strpos($templateFile,'#')){
                // 引入组件的其他模块的操作模板 例如 User#Info:add
                $templateFile   =   LIB_PATH.str_replace(array('#',':'),array('/'.TMPL_DIR.'/'.TEMPLATE_NAME.'/','/'),$templateFile).C('TEMPLATE_SUFFIX');
            }elseif(strpos($templateFile,'@')){
                // 引入其它主题的操作模板 必须带上模块名称 例如 blue@User:add
                $templateFile   =   TMPL_PATH.str_replace(array('@',':'),'/',$templateFile).C('TEMPLATE_SUFFIX');
            }elseif(strpos($templateFile,':')){
                // 引入其它模块的操作模板
                $templateFile   =   TEMPLATE_PATH.'/'.str_replace(':','/',$templateFile).C('TEMPLATE_SUFFIX');
            }elseif(!is_file($templateFile))    {
                // 引入当前模块的其它操作模板
                $templateFile =  dirname(C('TMPL_FILE_NAME')).'/'.$templateFile.C('TEMPLATE_SUFFIX');
            }
            if(!file_exists_case($templateFile)){
                throw_exception(L('_TEMPLATE_NOT_EXIST_').'['.$templateFile.']');
            }
        }

        if($pluginOn) {
            // 模版变量过滤
            $this->tVar = apply_filter('template_var',$this->tVar);
        }
        $compiler   =   false;
        //根据不同模版引擎进行处理
        switch($this->type) {
            case 'THINK': // 使用内置的ThinkTemplate模板引擎
                if(!$this->checkCache($templateFile)) {
                    // 缓存无效 重新编译
                    $compiler   =   true;
                    import('Think.Template.ThinkTemplate');
                    $tpl = ThinkTemplate::getInstance();
                    // 编译并加载模板文件
                    $tpl->load($templateFile,$charset,$this->tVar,$varPrefix);
                }else{
                    // 缓存有效 直接载入模板缓存
                    // 模板阵列变量分解成为独立变量
                    extract($this->tVar, empty($varPrefix)? EXTR_OVERWRITE : EXTR_PREFIX_ALL,$varPrefix);
                    //载入模版缓存文件
                    include CACHE_PATH.md5($templateFile).C('CACHFILE_SUFFIX');
                }
                break;
            case 'SMARTY':   // Smarty模板引擎
                $templateFile=substr($templateFile,strlen(TMPL_PATH));
                vendor('Smarty.Smarty#class');
                $tpl = new Smarty();
                if(C('TMPL_ENGINE_CONFIG')) {
                    $config  =  C('TMPL_ENGINE_CONFIG');
                    foreach ($config as $key=>$val){
                        $tpl->{$key}   =  $val;
                    }
                }else{
                    $tpl->caching = C('TMPL_CACHE_ON');
                    $tpl->template_dir = TMPL_PATH;
                    $tpl->compile_dir = CACHE_PATH ;
                    $tpl->cache_dir = TEMP_PATH ;
                }
                $tpl->assign($this->tVar);
                $tpl->display($templateFile);
                break;
            case 'SMART': // SmartTemplate 模板引擎
                $templateFile=substr($templateFile,strlen(TMPL_PATH));
                vendor('SmartTemplate.class#smarttemplate');
                $tpl = new SmartTemplate($templateFile);
                if(C('TMPL_ENGINE_CONFIG')) {
                    $config  =  C('TMPL_ENGINE_CONFIG');
                    foreach ($config as $key=>$val){
                        $tpl->{$key}   =  $val;
                    }
                }else{
                    $tpl->caching = C('TMPL_CACHE_ON');
                    $tpl->template_dir = TMPL_PATH;
                    $tpl->temp_dir = CACHE_PATH ;
                    $tpl->cache_dir = TEMP_PATH ;
                }
                $tpl->assign($this->tVar);
                $tpl->output();
                break;
            case 'LITE': // TemplateLite 模板引擎
                $templateFile=substr($templateFile,strlen(TMPL_PATH));
                vendor("TemplateLite.class#template");
                $tpl = new Template_Lite();
                if(C('TMPL_ENGINE_CONFIG')) {
                    $config  =  C('TMPL_ENGINE_CONFIG');
                    foreach ($config as $key=>$val){
                        $tpl->{$key}   =  $val;
                    }
                }else{
                    $tpl->template_dir = TMPL_PATH;
                    $tpl->compile_dir = CACHE_PATH ;
                    $tpl->cache_dir = TEMP_PATH ;
                }
                $tpl->assign($this->tVar);
                $tpl->display($templateFile);
                break;
            case 'EASE': // EaseTemplate 模板引擎
                $templateFile = substr($templateFile,strlen(TMPL_PATH),-5);
                $CacheDir = substr(CACHE_PATH,0,-1);
                $TemplateDir = substr(TMPL_PATH,0,-1);
                vendor('EaseTemplate.template#ease');
                if(C('TMPL_ENGINE_CONFIG')) {
                    $config  =  C('TMPL_ENGINE_CONFIG');
                }else{
                    $config  =                    array(
                    'CacheDir'=>$CacheDir,
                    'TemplateDir'=>$TemplateDir,
                    'TplType'=>'html'
                     );
                }
                $tpl = new EaseTemplate($config);
                $tpl->set_var($this->tVar);
                $tpl->set_file($templateFile);
                $tpl->p();
                break;
            case 'PHP': // PHP模板引擎
            default:
                // 模板阵列变量分解成为独立变量
                extract($this->tVar, empty($varPrefix)? EXTR_OVERWRITE : EXTR_PREFIX_ALL,$varPrefix);
                // 默认使用PHP模版
                include $templateFile;
        }
        // 获取并清空缓存
        $content = ob_get_clean();
        // 输出编码转换
        $content = auto_charset($content,C('TEMPLATE_CHARSET'),$charset);
        if($pluginOn) {
            // 输出过滤
            $content = apply_filter('ob_content',$content);
        }

        if(C('HTML_CACHE_ON') && $htmlCache) {
            // 写入静态文件
            HtmlCache::writeHTMLCache($content);
        }

        if($display) {
            $showTime   =   $this->showTime($startTime);
            echo $content;
            if(C('SHOW_RUN_TIME')) {
                echo '<div  id="think_run_time" class="think_run_time">'.$showTime.'</div>';
            }
            $this->showTrace($showTime,$charset,$compiler);
            return null;
        }else {
            return $content;
        }
    }

    /**
     +----------------------------------------------------------
     * 显示页面Trace信息
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $showTime 运行时间信息
     * @param string $charset 模板输出字符集
     * @param boolean $compiler 是否重新编译
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    protected function showTrace($showTime,$charset,$compiler=true){
        if(C('SHOW_PAGE_TRACE')) {
            // 显示页面Trace信息 读取Trace定义文件
            // 定义格式 return array('当前页面'=>$_SERVER['PHP_SELF'],'通信协议'=>$_SERVER['SERVER_PROTOCOL'],...);
            $traceFile  =   CONFIG_PATH.'trace.php';
             if(file_exists_case($traceFile)) {
                $_trace =   include $traceFile;
             }else{
                $_trace =   array();
             }
             // 系统默认显示信息
            $this->trace('当前页面',    $_SERVER['PHP_SELF']);
            $this->trace('请求方法',    $_SERVER['REQUEST_METHOD']);
            $this->trace('通信协议',    $_SERVER['SERVER_PROTOCOL']);
            $this->trace('请求时间',    date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']));
            $this->trace('用户代理',    $_SERVER['HTTP_USER_AGENT']);
            $this->trace('会话ID'   ,   session_id());
            $this->trace('运行数据',    $showTime);
            $this->trace('输出编码',    $charset);
            $this->trace('加载类库',    count($GLOBALS['import_file']));
            $this->trace('模板编译',    !empty($compiler)?'重新编译':'读取缓存');
            if(isset(Log::$log[SQL_LOG_DEBUG])) {
                $log    =   Log::$log[SQL_LOG_DEBUG];
                $this->trace('SQL记录',is_array($log)?count($log).'条SQL<br/>'.implode('<br/>',$log):'无SQL记录');
            }else{
                $this->trace('SQL记录','无SQL记录');
            }
            if(isset(Log::$log[WEB_LOG_ERROR])) {
                $log    =   Log::$log[WEB_LOG_ERROR];
                $this->trace('错误记录',is_array($log)?count($log).'条错误<br/>'.implode('<br/>',$log):'无错误记录');
            }else{
                $this->trace('错误记录','无错误记录');
            }
            $_trace =   array_merge($_trace,$this->trace);
            $_trace = auto_charset($_trace,'utf-8');
            $_title =   auto_charset('页面Trace信息','utf-8');
            // 调用Trace页面模板
            include THINK_PATH.'/Tpl/PageTrace.tpl.php';
        }
    }
}//
?>