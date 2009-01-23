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
 * ThinkPHP内置模板引擎类
 * 支持XML标签和普通标签的模板解析
 * 编译型模板引擎 支持动态缓存
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Template
 * @author liu21st <liu21st@gmail.com>
 * @version  $Id$
 +------------------------------------------------------------------------------
 */
class  ThinkTemplate extends Base
{//类定义开始

    // 模板页面中引入的标签库列表
    protected $tagLib          =  array();
    // 当前模板文件
    protected $templateFile  =  '';
    // 模板变量
    public $tVar                 = array();

    /**
     +----------------------------------------------------------
     * 取得模板实例对象
     * 静态方法
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return App
     +----------------------------------------------------------
     */
    static function  getInstance()
    {
        return get_instance_of(__CLASS__);
    }

    // 模板变量获取和设置
    public function get($name) {
        if(isset($this->tVar[$name])) {
            return $this->tVar[$name];
        }else {
            return false;
        }
    }

    public function set($name,$value) {
        $this->tVar[$name]= $value;
    }

    // 加载模板
    public function load($templateFile,$charset,$templateVar,$varPrefix) {
        $this->tVar = $templateVar;
        $templateCacheFile  =  $this->loadTemplate($templateFile,$charset);
        // 模板阵列变量分解成为独立变量
        extract($templateVar, empty($varPrefix)? EXTR_OVERWRITE : EXTR_PREFIX_ALL,$varPrefix);
        //载入模版缓存文件
        include $templateCacheFile;
    }

    /**
     +----------------------------------------------------------
     * 加载主模板并缓存
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $tmplTemplateFile 模板文件
     * @param string $varPrefix  模板变量前缀
     * @param string $charset  模板输出字符集
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function loadTemplate ($tmplTemplateFile='',$charset='')
    {
        if(empty($tmplTemplateFile))    $tmplTemplateFile = C('TMPL_FILE_NAME');
        if(empty($charset)) $charset = C('OUTPUT_CHARSET');
        if(!is_file($tmplTemplateFile)){
            $tmplTemplateFile =  dirname(C('TMPL_FILE_NAME')).'/'.$tmplTemplateFile.C('TEMPLATE_SUFFIX');
            if(!is_file($tmplTemplateFile)){
                throw_exception(L('_TEMPLATE_NOT_EXIST_'));
            }
        }
        $this->templateFile    =  $tmplTemplateFile;

        //根据模版文件名定位缓存文件
        $tmplCacheFile = CACHE_PATH.md5($tmplTemplateFile).C('CACHFILE_SUFFIX');
        $tmplContent = '';
        // 检查Cache文件是否需要更新
        if (!$this->checkCache($tmplTemplateFile)) {
            // 需要更新模版 读出原模板内容
            $tmplContent = file_get_contents($tmplTemplateFile);
            //编译模板内容
            $tmplContent = $this->compiler($tmplContent,$charset);
            //重写Cache文件
            if( false === file_put_contents($tmplCacheFile,trim($tmplContent))) {
                throw_exception(L('_CACHE_WRITE_ERROR_'));
            }
        }
        return $tmplCacheFile;
    }

    /**
     +----------------------------------------------------------
     * 重新编译项目全部模版
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $tmplContent 模板内容
     * @param string $charset  模板输出字符集
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function buildAllTemplate($tmplPath=TMPL_PATH)
    {
        // 遍历模版目录
        $themes =  scandir($tmplPath);
        foreach($themes as $key=>$theme) {
            $modules  = scandir($tmplPath.$theme);
            foreach($modules as $key=>$module) {
                $actions = scandir($tmplPath.$theme.'/'.$module);
                foreach($actions as $key=>$file) {
                    //读出原模板内容
                    $tmplTemplateFile =  ($tmplPath.$theme.'/'.$module.'/'.$file);
                    $tmplContent = file_get_contents($tmplTemplateFile);
                    //编译模板内容
                    $tmplContent = $this->compiler($tmplContent);
                    //重写Cache文件
                    $tmplCacheFile = CACHE_PATH.md5($tmplTemplateFile).C('CACHFILE_SUFFIX');
                    if( false === file_put_contents($tmplCacheFile,trim($tmplContent))) {
                        system_out('模版缓存文件'.$tmplCacheFile.'写入失败！');
                    }
                }
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 编译模板文件内容
     * 包括模板解析、同步路径和编码转换
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $tmplContent 模板内容
     * @param string $charset  模板输出字符集
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function compiler ( $tmplContent,$charset='')
    {
        //模板解析
        $tmplContent = $this->parse($tmplContent);

        // 特殊变量替换
        if(empty($charset))  $charset = C('OUTPUT_CHARSET');

        //项目公共目录
        $tmplContent = str_ireplace('../public',APP_PUBLIC_URL,$tmplContent);
        //网站公共目录
        $tmplContent = str_replace('__PUBLIC__',WEB_PUBLIC_URL,$tmplContent);
        // 项目模板目录
        $tmplContent = str_replace('__TMPL__',APP_TMPL_URL,$tmplContent);
        //网站根目录
        $tmplContent = str_replace('__ROOT__',__ROOT__,$tmplContent);
        //当前项目地址
        $tmplContent = str_replace('__APP__',__APP__,$tmplContent);
        //当前模块地址
        $tmplContent = str_replace('__URL__',__URL__,$tmplContent);
        //当前项目操作地址
        $tmplContent = str_replace('__ACTION__',__ACTION__,$tmplContent);
        //当前页面操作地址
        $tmplContent = str_replace('__SELF__',__SELF__,$tmplContent);
        if(ini_get('short_open_tag')) {
            // 开启短标签的情况要将<?标签用echo方式输出 否则无法正常输出xml标识
            $tmplContent = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>'."\n", $tmplContent );
        }
        //编码替换
        if(C('TEMPLATE_CHARSET') != $charset) {
            $tmplContent = str_ireplace('charset='.C('TEMPLATE_CHARSET'), 'charset='.$charset, $tmplContent);
        }
        // 令牌验证
        $tmplContent =  preg_replace('/<\/form(\s*)>/is','<?php if(C("TOKEN_ON")):?><input type="hidden" name="<?php echo C("TOKEN_NAME");?>" value="<?php echo Session::get(C("TOKEN_NAME")); ?>"/><?php endif;?></form>',$tmplContent);
        if(C('THINK_PLUGIN_ON')) {
            // 模版过滤插件调用
            $tmplContent =  apply_filter('tmpl_replace',$tmplContent);
        }

        // 还原被替换的Literal标签
        $tmplContent = preg_replace('/<!--###literal(\d)###-->/eis',"\$this->restoreLiteral('\\1')",$tmplContent);

        return $tmplContent;
    }


    /**
     +----------------------------------------------------------
     * 检查缓存文件是否有效
     * 如果无效则需要重新更新
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $tmplTemplateFile  模板文件名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    protected function checkCache($tmplTemplateFile)
    {
        $tmplCacheFile = CACHE_PATH.md5($tmplTemplateFile).C('CACHFILE_SUFFIX');
        if(!is_file($tmplCacheFile)){
            return false;
        }elseif (!C('TMPL_CACHE_ON')){
            return false;
        }elseif (filemtime($tmplTemplateFile) > filemtime($tmplCacheFile)) {
            // 模板文件如果有更新则缓存需要更新
            return false;
        }elseif (C('TMPL_CACHE_TIME') != -1 && time() > filemtime($tmplCacheFile)+C('TMPL_CACHE_TIME')) {
            // 缓存是否在有效期
            return false;
        }
        //缓存有效
        return true;
    }

    /**
     +----------------------------------------------------------
     * 清除缓存或者静态文件
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $filename  缓存文件名
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function cleanCache($filename)
    {
        if(is_file($filename)){
            unlink($filename);
        }
        return;
    }

    /**
     +----------------------------------------------------------
     * 清除缓存目录下面的文件
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $cacheDir  缓存目录名
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function cleanDir($cacheDir=CACHE_PATH)
    {
        if ( $dir = opendir( $cacheDir ) )
        {
            while ( $file = readdir( $dir ) )
            {
                $check = is_dir( $file );
                if ( !$check )
                    unlink( $cacheDir . $file );
            }
            closedir( $dir );
            return true;
        }
    }

    /**
     +----------------------------------------------------------
     * 模板解析入口
     * 支持普通标签和TagLib解析 支持自定义标签库
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $content 要解析的模板内容
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function parse($content)
    {
        // 首先替换literal标签内容
        $content = preg_replace('/'.C('TAGLIB_BEGIN').'literal'.C('TAGLIB_END').'(.*?)'.C('TAGLIB_BEGIN').'\/literal'.C('TAGLIB_END').'/eis',"\$this->parseLiteral('\\1')",$content);

        // 获取引入的标签库列表
        // 标签库只需要定义一次，允许引入多个一次
        // 一般放在文件的最前面
        // 格式：<taglib name="cx,html" class="Think.Util.TagLib.TagLib_Cx,Think.Util.TagLib.TagLib_Html" />
        $this->getIncludeTagLib($content);
        if(!empty($this->tagLib)) {
            // 如果有引入TagLib库
            // 则对导入的TagLib进行解析
            foreach($this->tagLib as $tagLibName=>$tagLibClass) {
                if(empty($tagLibClass)) {
                    import('Think.Template.TagLib.TagLib'.ucwords(strtolower($tagLibName)));
                }else {
                    import($tagLibClass);
                }
                $this->parseTagLib($tagLibName,$content);
            }
        }
        // 内置了CX标签库支持 无需使用taglib标签导入就可以使用
        // 并且无需添加cx前缀 ，可以直接写成
        // <include file='' />
        // <volist id='' name='' ></volist>
        // 的形式
        import('Think.Template.TagLib.TagLibCx');
        $this->parseTagLib('cx',$content,true);

        //解析普通模板标签 {tagName:}
        $content = preg_replace('/('.C('TMPL_L_DELIM').')(\S.+?)('.C('TMPL_R_DELIM').')/eis',"\$this->parseTag('\\2')",$content);

        return $content;
    }

    /**
     +----------------------------------------------------------
     * 替换页面中的literal标签
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $content  模板内容
     +----------------------------------------------------------
     * @return string|false
     +----------------------------------------------------------
     */
    function parseLiteral($content) {
        if(trim($content)=='') {
            return '';
        }
        $content = stripslashes($content);
        static $_literal = array();
        $i  =   count($_literal);
        $_literal[$i] = $content;
        $parseStr   =   "<!--###literal{$i}###-->";
        $_SESSION["literal{$i}"]    =   $content;
        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * 还原被替换的literal标签
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $tag  literal标签序号
     +----------------------------------------------------------
     * @return string|false
     +----------------------------------------------------------
     */
    function restoreLiteral($tag) {
        // 还原literal标签
        $parseStr   =   $_SESSION['literal'.$tag];
        // 销毁literal记录
        unset($_SESSION['literal'.$tag]);
        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * 搜索模板页面中包含的TagLib库
     * 并返回列表
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $content  模板内容
     +----------------------------------------------------------
     * @return string|false
     +----------------------------------------------------------
     */
    public function getIncludeTagLib(& $content)
    {
        //搜索是否有TagLib标签
        $find = preg_match('/'.C('TAGLIB_BEGIN').'taglib\s(.+?)(\s*?)\/'.C('TAGLIB_END').'\W/is',$content,$matches);
        if($find) {
            //替换TagLib标签
            $content = str_replace($matches[0],'',$content);
            //解析TagLib标签
            $tagLibs = $matches[1];
            $xml =  '<tpl><tag '.$tagLibs.' /></tpl>';
            $xml = simplexml_load_string($xml);
            if(!$xml) {
                throw_exception(L('_XML_TAG_ERROR_'));
            }
            $xml = (array)($xml->tag->attributes());
            $array = array_change_key_case($xml['@attributes']);
            $tagLibName =  explode(',',$array['name']);
            $tagLibClass  =  isset($array['class'])?explode(',',$array['class']):array_fill(0,count($tagLibName),'');
            $tagLibList  = array_combine($tagLibName,$tagLibClass);
            $this->tagLib = $tagLibList;
        }
        return;
    }

    /**
     +----------------------------------------------------------
     * TagLib库解析
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $tagLib 要解析的标签库
     * @param string $content 要解析的模板内容
     * @param boolen $hide 是否隐藏标签库前缀
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function parseTagLib($tagLib,&$content,$hide=false)
    {
        $tLib =  get_instance_of('TagLib'.ucwords(strtolower($tagLib)));
        if($tLib->valid()) {
            //如果标签库有效则取出支持标签列表
            $tagList =  $tLib->getTagList();
            //遍历标签列表进行模板标签解析
            foreach($tagList as $tag) {
                // 实际要解析的标签名称
                if( !$hide) {
                    $startTag = $tagLib.':'.$tag['name'];
                }else {
                    $startTag = $tag['name'];
                }
                // 检查可嵌套标签以及嵌套级别
                if($tag['nested'] && C('TAG_NESTED_LEVEL')>1) {
                    $level   =   C('TAG_NESTED_LEVEL');
                }else{
                    $level   =   1;
                }
                $endTag = $startTag;
                if(false !== stripos($content,C('TAGLIB_BEGIN').$startTag)) {
                    if(empty($tag['attribute'])){
                        // 无属性标签
                        if($tag['content'] !='empty'){
                            for($i=0;$i<$level;$i++) {
                                $content = preg_replace('/'.C('TAGLIB_BEGIN').$startTag.'(\s*?)'.C('TAGLIB_END').'(.*?)'.C('TAGLIB_BEGIN').'\/'.$endTag.'(\s*?)'.C('TAGLIB_END').'/eis',"\$this->parseXmlTag('".$tagLib."','".$tag['name']."','\\1','\\2')",$content);
                            }
                        }else{
                            $content = preg_replace('/'.C('TAGLIB_BEGIN').$startTag.'(\s*?)\/(\s*?)'.C('TAGLIB_END').'/eis',"\$this->parseXmlTag('".$tagLib."','".$tag['name']."','\\1','')",$content);
                        }
                    }elseif($tag['content'] !='empty') {//闭合标签解析
                        for($i=0;$i<$level;$i++) {
                            $content = preg_replace('/'.C('TAGLIB_BEGIN').$startTag.'\s(.*?)'.C('TAGLIB_END').'(.+?)'.C('TAGLIB_BEGIN').'\/'.$endTag.'(\s*?)'.C('TAGLIB_END').'/eis',"\$this->parseXmlTag('".$tagLib."','".$tag['name']."','\\1','\\2')",$content);
                        }
                    }else {//开放标签解析
                        //$content = preg_replace('/'.C('TAGLIB_BEGIN').$startTag.'\s(.*?)'.C('TAGLIB_END').'(.*?)'.C('TAGLIB_BEGIN').'\/'.$endTag.C('TAGLIB_END').'/eis',"\$this->parseXmlTag('".$tagLib."','".$tag['name']."','\\1','\\2')",$content);
                        // 开始标签必须有一个空格
                        $content = preg_replace('/'.C('TAGLIB_BEGIN').$startTag.'\s(.*?)\/(\s*?)'.C('TAGLIB_END').'/eis',"\$this->parseXmlTag('".$tagLib."','".$tag['name']."','\\1','')",$content);
                    }
                }
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 解析标签库的标签
     * 需要调用对应的标签库文件解析类
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $tagLib  标签库名称
     * @param string $tag  标签名
     * @param string $attr  标签属性
     * @param string $content  标签内容
     +----------------------------------------------------------
     * @return string|false
     +----------------------------------------------------------
     */
    public function parseXmlTag($tagLib,$tag,$attr,$content)
    {
        //if (MAGIC_QUOTES_GPC) {
            $attr = stripslashes($attr);
            $content = stripslashes($content);
        //}
        $tLib =  get_instance_of('TagLib'.ucwords(strtolower($tagLib)));
        if($tLib->valid()) {
            $parse = '_'.$tag;
            $content = trim($content);
            return $tLib->$parse($attr,$content);
        }
    }

    /**
     +----------------------------------------------------------
     * 模板标签解析
     * 格式： {TagName:args [|content] }
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $tagStr 标签内容
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function parseTag($tagStr){
        //if (MAGIC_QUOTES_GPC)
            $tagStr = stripslashes($tagStr);
        //还原非模板标签
        if(preg_match('/^[\s|\d]/is',$tagStr)){
            //过滤空格和数字打头的标签
            return C('TMPL_L_DELIM') . $tagStr .C('TMPL_R_DELIM');
        }
        $flag =  substr($tagStr,0,1);
        $name   = substr($tagStr,1);
        if('$' == $flag){
            //解析模板变量 格式 {$varName}
            return $this->parseVar($name);
        }elseif(':' == $flag){
            // 输出某个函数的结果
            return  '<?php echo '.$name.';?>';
        }elseif('~' == $flag){
            // 执行某个函数
            return  '<?php '.$name.';?>';
        }elseif('&' == $flag){
            // 输出配置参数
            return '<?php echo C("'.$name.'");?>';
        }elseif('%' == $flag){
            // 输出语言变量
            return '<?php echo L("'.$name.'");?>';
		}elseif('@' == $flag){
			// 输出SESSION变量
            if(strpos($name,'.')) {
                $array   =  explode('.',$name);
	    		return '<?php echo $_SESSION["'.$array[0].'"]["'.$array[1].'"];?>';
            }else{
    			return '<?php echo $_SESSION["'.$name.'"];?>';
            }
		}elseif('#' == $flag){
			// 输出COOKIE变量
            if(strpos($name,'.')) {
                $array   =  explode('.',$name);
	    		return '<?php echo $_COOKIE["'.$array[0].'"]["'.$array[1].'"];?>';
            }else{
    			return '<?php echo $_COOKIE["'.$name.'"];?>';
            }
		}elseif('.' == $flag){
            // 输出GET变量
            return '<?php echo $_GET["'.$name.'"];?>';
        }elseif('^' == $flag){
            // 输出POST变量
            return '<?php echo $_POST["'.$name.'"];?>';
        }elseif('*' == $flag){
            // 输出常量
            return '<?php echo constant("'.$name.'");?>';
        }

        $tagStr = trim($tagStr);
        if(substr($tagStr,0,2)=='//' || (substr($tagStr,0,2)=='/*' && substr($tagStr,-2)=='*/')){
            //注释标签
            return '';
        }
        //解析其它标签
        //统一标签格式 {TagName:args [|content]}
        $varArray = explode(':',$tagStr);
        //取得标签名称
        $tag = trim(array_shift($varArray));

        //解析标签内容
        $args = explode('|',$varArray[0],2);
        switch(strtoupper($tag)){
            case 'INCLUDE':
                $parseStr = $this->parseInclude(trim($args[0]));
                break;
            //这里扩展其它标签
            //…………
            default:
                //还原非模版标签
                $parseStr = C('TMPL_L_DELIM') . $tagStr .C('TMPL_R_DELIM');
                break;
        }
        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * 模板变量解析,支持使用函数
     * 格式： {$varname|function1|function2=arg1,arg2}
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $varStr 变量数据
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function parseVar($varStr){
        $varStr = trim($varStr);
        static $_varParseList = array();
        //如果已经解析过该变量字串，则直接返回变量值
        if(isset($_varParseList[$varStr])) return $_varParseList[$varStr];
        $parseStr ='';
        $varExists = true;
        if(!empty($varStr)){
            $varArray = explode('|',$varStr);
            //取得变量名称
            $var = array_shift($varArray);
            //非法变量过滤 不允许在变量里面使用 ->
            //TODO：还需要继续完善
            if(preg_match('/->/is',$var)){
                return '';
            }
            if('Think.' == substr($var,0,6)){
                // 所有以Think.打头的以特殊变量对待 无需模板赋值就可以输出
                $name = $this->parseThinkVar($var);
            }
            elseif( false !== strpos($var,'.')) {
                //支持 {$var.property} 方式输出对象的属性或者数组，自动判断
                $vars = explode('.',$var);
                $name = 'is_array($'.$vars[0].')?$'.$vars[0].'["'.$vars[1].'"]:$'.$vars[0].'->'.$vars[1];
                $var  = $vars[0];
            }
            elseif(false !==strpos($var,':')){
                //支持 {$var:property} 方式输出对象的属性
                $vars = explode(':',$var);
                $var  =  str_replace(':','->',$var);
                $name = "$".$var;
                $var  = $vars[0];
            }
            elseif(false !== strpos($var,'[')) {
                //支持 {$var['key']} 方式输出数组
                $name = "$".$var;
                preg_match('/(.+?)\[(.+?)\]/is',$var,$match);
                $var = $match[1];
            }
            else {
                $name = "$$var";
            }
            //对变量使用函数
            if(count($varArray)>0) {
                $name = $this->parseVarFunction($name,$varArray);
            }
            $parseStr = '<?php echo ('.$name.'); ?>';
        }
        $_varParseList[$varStr] = $parseStr;
        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * 对模板变量使用函数
     * 格式 {$varname|function1|function2=arg1,arg2}
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 变量名
     * @param array $varArray  函数列表
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function parseVarFunction($name,$varArray){
        //对变量使用函数
        $length = count($varArray);
        //取得模板禁止使用函数列表
        $template_deny_funs = explode(',',C('TMPL_DENY_FUNC_LIST'));
        for($i=0;$i<$length ;$i++ ){
            $args = explode('=',$varArray[$i]);
            //模板函数过滤
            $args[0] = trim($args[0]);
            switch(strtolower($args[0])) {
            case 'default':  // 特殊模板函数
                $name   = '('.$name.')?('.$name.'):'.$args[1];
                break;
            default:  // 通用模板函数
                if(!in_array($args[0],$template_deny_funs)){
                    if(isset($args[1])){
                        if(strstr($args[1],'###')){
                            $args[1] = str_replace('###',$name,$args[1]);
                            $name = "$args[0]($args[1])";
                        }else{
                            $name = "$args[0]($name,$args[1])";
                        }
                    }else if(!empty($args[0])){
                        $name = "$args[0]($name)";
                    }
                }
            }
        }
        return $name;
    }

    /**
     +----------------------------------------------------------
     * 特殊模板变量解析
     * 格式 以 $Think. 打头的变量属于特殊模板变量
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $varStr  变量字符串
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function parseThinkVar($varStr){
        $vars = explode('.',$varStr);
        $vars[1] = strtoupper(trim($vars[1]));
        $parseStr = '';
        if(count($vars)>=3){
            $vars[2] = trim($vars[2]);
            switch($vars[1]){
                case 'SERVER':
                    $parseStr = '$_SERVER[\''.strtoupper($vars[2]).'\']';break;
                case 'GET':
                    $parseStr = '$_GET[\''.$vars[2].'\']';break;
                case 'POST':
                    $parseStr = '$_POST[\''.$vars[2].'\']';break;
                case 'COOKIE':
                    if(isset($vars[3])) {
                        $parseStr = '$_COOKIE[\''.$vars[2].'\'][\''.$vars[3].'\']';
                    }else{
                        $parseStr = '$_COOKIE[\''.$vars[2].'\']';
                    }break;
                case 'SESSION':
                    if(isset($vars[3])) {
                        $parseStr = '$_SESSION[\''.$vars[2].'\'][\''.$vars[3].'\']';
                    }else{
                        $parseStr = '$_SESSION[\''.$vars[2].'\']';
                    }
                    break;
                case 'ENV':
                    $parseStr = '$_ENV[\''.$vars[2].'\']';break;
                case 'REQUEST':
                    $parseStr = '$_REQUEST[\''.$vars[2].'\']';break;
                case 'CONST':
                    $parseStr = strtoupper($vars[2]);break;
                case 'LANG':
                    $parseStr = 'L("'.$vars[2].'")';break;
				case 'CONFIG':
                    if(isset($vars[3])) {
                        $vars[2] .= '.'.$vars[3];
                    }
                    $parseStr = 'C("'.$vars[2].'")';break;
                default:break;
            }
        }else if(count($vars)==2){
            switch($vars[1]){
                case 'NOW':
                    $parseStr = "date('Y-m-d g:i a',time())";
                    break;
                case 'VERSION':
                    $parseStr = 'THINK_VERSION';
                    break;
                case 'TEMPLATE':
                    $parseStr = 'C("TMPL_FILE_NAME")';
                    break;
                case 'LDELIM':
                    $parseStr = 'C("TMPL_L_DELIM")';
                    break;
                case 'RDELIM':
                    $parseStr = 'C("TMPL_R_DELIM")';
                    break;
                default:
                    if(defined($vars[1]))
                        $parseStr = $vars[1];
            }
        }
        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * 加载公共模板并缓存 和当前模板在同一路径，否则使用相对路径
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $tmplPublicName  公共模板文件名
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function parseInclude($tmplPublicName){
        if(substr($tmplPublicName,0,1)=='$'){
            //支持加载变量文件名
            $tmplPublicName = $this->get(substr($tmplPublicName,1));
        }
        if(is_file($tmplPublicName)) {
            // 直接包含文件
            $parseStr = file_get_contents($tmplPublicName);
        }else {
            $tmplPublicName = trim($tmplPublicName);
            if(strpos($tmplPublicName,'@')){
                // 引入其它模块的操作模板
                $tmplTemplateFile   =   dirname(dirname(dirname($this->templateFile))).'/'.str_replace(array('@',':'),'/',$tmplPublicName);
            }elseif(strpos($tmplPublicName,':')){
                // 引入其它模块的操作模板
                $tmplTemplateFile   =   dirname(dirname($this->templateFile)).'/'.str_replace(':','/',$tmplPublicName);
            }else{
                // 默认导入当前模块下面的模板
                $tmplTemplateFile = dirname($this->templateFile).'/'.$tmplPublicName;
            }
            $tmplTemplateFile .=  C('TEMPLATE_SUFFIX');
            $parseStr = file_get_contents($tmplTemplateFile);
        }
        //再次对包含文件进行模板分析
        return $this->parse($parseStr);
    }

}//类定义结束
?>