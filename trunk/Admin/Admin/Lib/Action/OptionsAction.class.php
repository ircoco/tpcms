<?php
class OptionsAction extends BaseAction
{
	/**
     +----------------------------------------------------------
     * 数据库与文件配置列表
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     */
	function index()
	{
		$web_config = require "web_config.php";
		foreach($web_config as $key=>$value)
		{
			if($value===true)
			$web_config[$key] = "true";
			if($value===false)
			$web_config[$key] = "false";
		}
		$dir = CMS_PATH."/Lib/Module/Conf/";
		$moduleList = array();
		if (is_dir($dir)){
			if ($dh = opendir($dir)){
				while (($file = readdir($dh)) !== false){
					if($file != "." && $file != ".." && filetype($dir.$file) == "dir")
					$moduleList[$file] = $file;
				}
				closedir($dh);
			}
		}

		$this->assign("moduleList",$moduleList);
		$this->assign("web_config",$web_config);
		$this->display();
	}

	/**
     +----------------------------------------------------------
     * 保存配置到数据库和config文件
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     */
	function save()
	{
		//db_siteurl和EXTRA_UPLOAD_PATH这两个值如果用户在最后写上了/,把/去掉
		if(substr($_POST["SITE_HOST"],strlen($_POST["SITE_HOST"])-1) == '/')
		$_POST["SITE_HOST"] = substr($_POST["SITE_HOST"],0,strlen($_POST["SITE_HOST"])-1);

		$web_config = "<?php\r\nif (!defined('THINK_PATH')) exit();\r\nreturn array(\r\n";
		foreach($_POST as $key=>$value)
		{
			if(strtolower($value)=="true" || strtolower($value)=="false" || is_numeric($value))
			$web_config .= "\t'".$key."'=>$value,\r\n";
			else
			$web_config .= "\t'".$key."'=>'$value',\r\n";
		}
		$web_config .= ");\r\n?>";

		file_put_contents(ROOT_PATH."/web_config.php",$web_config);
		//清空前台缓存
		clearCMSCache();
		//清空后台缓存
		clearCache(1);

		$this->assign("message",'修改网站配置成功！');
		$this->assign("jumpUrl",__URL__.'/index/');
		$this->forward();
	}

	/**
     +----------------------------------------------------------
     * 可选主题列表
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     */
	function themes()
	{
		$templateDir = CMS_PATH."/Tpl/";
		$templateList = array();
		if (is_dir($templateDir)) {
			if ($dh = opendir($templateDir)) {
				while (($template = readdir($dh)) !== false) {
					if($template != "." && $template != ".." && filetype($templateDir.$template) == "dir" && file_exists_case($templateDir.$template."/screenshot.png"))
					$templateList[$template] = $template;
				}
				closedir($dh);
			}
		}

		$this->assign("templateList",$templateList);
		$this->assign("web_config",require "web_config.php");
		$this->assign("pageFile","Options:themes");
		$this->display();
	}

	/**
     +----------------------------------------------------------
     * 保存选用的模板
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     */
	function configTheme()
	{
		$web_config = require "web_config.php";
		$web_config["DEFAULT_TEMPLATE"] = $_POST["DEFAULT_TEMPLATE"];

		$config = "<?php\r\nif (!defined('THINK_PATH')) exit();\r\nreturn array(\r\n";
		foreach($web_config as $key=>$value)
		{
			if($value === true)
			$config .= "\t'".$key."'=>true,\r\n";
			else if($value === false)
			$config .= "\t'".$key."'=>false,\r\n";
			else if(is_numeric($value))
			$config .= "\t'".$key."'=>$value,\r\n";
			else
			$config .= "\t'".$key."'=>'$value',\r\n";
		}
		$config .= ");\r\n?>";

		file_put_contents("web_config.php",$config);
		//清空前台缓存
		clearCMSCache();
		$this->assign("message",'修改网站配置成功！');
		$this->assign("jumpUrl",__URL__.'/themes/');
		$this->forward();
	}

	function cache()
	{
		$this->assign("cache",array(
		"Admin"=>array("Cache"=>"./Admin/Cache","Temp"=>"./Admin/Temp","Logs"=>"./Admin/Logs","Data"=>"./Admin/Data"),
		"CMS"=>array("Cache"=>"./Data/Cache","Temp"=>"./Data/Temp","Logs"=>"./Data/Logs","Data"=>"./Data/Data"),
		"Runtime"=>array("Admin_Runtime"=>"./Data/Admin_Runtime","CMS_Runtime"=>"./Data/CMS_Runtime")
		));
		$this->display();
	}

	function clearCache()
	{
		$dirs = $_POST['dir'];
		foreach($dirs as $value)
		{
			$current_dir = @opendir($value);
			while($entryname = readdir($current_dir)){
				if(is_file($value."/".$entryname))
				{
					@unlink($value."/".$entryname);
				}
			}
			@closedir($current_dir);
		}
		$this->success("缓存清理成功!");
	}
}
?>