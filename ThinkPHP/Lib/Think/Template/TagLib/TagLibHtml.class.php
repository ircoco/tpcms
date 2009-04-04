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
 * HTML标签库解析类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Template
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
import('Think.Template.TagLib');
class TagLibHtml extends TagLib
{//类定义开始

    /**
     +----------------------------------------------------------
     * editor标签解析 插入可视化编辑器
     * 格式： <html:editor id="editor" name="remark" type="FCKeditor" content="{$vo.remark}" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _editor($attr)
    {
        $tag        =	$this->parseXmlAttr($attr,'editor');
        $id			=	!empty($tag['id'])?$tag['id']: '_editor';
		$name   	=	$tag['name'];
        $style   	    =	$tag['style'];
        $width		=	!empty($tag['width'])?$tag['width']: '100%';
        $height     =	!empty($tag['height'])?$tag['height'] :'320px';
        $content    =   $tag['content'];
        $type       =   $tag['type'] ;
		switch(strtoupper($type)) {
            case 'FCKEDITOR':
            	$parseStr   =	'<!-- 编辑器调用开始 --><script type="text/javascript" src="__ROOT__/Public/Js/FCKeditor/fckeditor.js"></script><textarea id="'.$id.'" name="'.$name.'">'.$content.'</textarea><script type="text/javascript"> var oFCKeditor = new FCKeditor( "'.$id.'","'.$width.'","'.$height.'" ) ; oFCKeditor.BasePath = "__ROOT__/Public/Js/FCKeditor/" ; oFCKeditor.ReplaceTextarea() ;function resetEditor(){setContents("'.$id.'",document.getElementById("'.$id.'").value)}; function saveEditor(){document.getElementById("'.$id.'").value = getContents("'.$id.'");} function InsertHTML(html){ var oEditor = FCKeditorAPI.GetInstance("'.$id.'") ;if (oEditor.EditMode == FCK_EDITMODE_WYSIWYG ){oEditor.InsertHtml(html) ;}else	alert( "FCK必须处于WYSIWYG模式!" ) ;}</script> <!-- 编辑器调用结束 -->';
            	break;
			case 'EWEBEDITOR':
				$parseStr	=	"<!-- 编辑器调用开始 --><script type='text/javascript' src='__ROOT__/Public/Js/eWebEditor/js/edit.js'></script><input type='hidden'  id='{$id}' name='{$name}'  value='{$conent}'><iframe src='__ROOT__/Public/Js/eWebEditor/ewebeditor.htm?id={$name}' frameborder=0 scrolling=no width='{$width}' height='{$height}'></iframe><script type='text/javascript'>function saveEditor(){document.getElementById('{$id}').value = getHTML();} </script><!-- 编辑器调用结束 -->";
				break;
			case 'NETEASE':
                $parseStr   =	'<!-- 编辑器调用开始 --><textarea id="'.$id.'" name="'.$name.'" style="display:none">'.$content.'</textarea><iframe ID="Editor" name="Editor" src="__ROOT__/Public/Js/HtmlEditor/index.html?ID='.$name.'" frameBorder="0" marginHeight="0" marginWidth="0" scrolling="No" style="height:'.$height.';width:'.$width.'"></iframe><!-- 编辑器调用结束 -->';
                break;
            case 'SMART':
            	$parseStr  =  '<div class="smartEditor" style="'.$style.'"><script type="text/javascript" src="__ROOT__/Public/Js/smartEditor/smartEditor.js"></script><div id="tools" ><select name="fontname" style="width:65px" onchange="setFont(options[this.selectedIndex].value)"><option value="">字体</option><option value="Arial">Arial</option><option value="Verdana">Verdana</option><option value="Tahoma">Tahoma</option><option value="System">System</option><option value="黑体">黑体</option><option value="宋体">宋体</option></select> <select onchange=setColor(options[this.selectedIndex].value) style="width:35px" name="color"><option value="" selected>颜色</option><option style="background: skyblue;" value=skyblue></option> <option style="background: royalblue" value=royalblue></option> <option style="background: blue" value=blue></option> <option style="background: darkblue" value=darkblue></option> <option style="background: orange" value=orange></option> <option style="background: orangered" value=orangered></option> <option style="background: crimson" value=crimson></option> <option style="background: red" value=red></option> <option style="background: firebrick" value=firebrick></option> <option style="background: darkred" value=darkred></option> <option style="background: green" value=green></option> <option style="background: limegreen" value=limegreen></option> <option style="background: seagreen" value=seagreen></option> <option style="background: deeppink" value=deeppink></option> <option style="background: tomato" value=tomato></option> <option style="background: coral" value=coral></option> <option style="background: purple" value=purple></option> <option style="background: indigo" value=indigo></option> <option style="background: burlywood" value=burlywood></option> <option style="background: sandybrown" value=sandybrown></option> <option style="background: sienna" value=sienna></option> <option style="background: chocolate" value=chocolate></option> <option style="background: teal" value=teal></option> <option style="background: silver" value=silver></option></select><img SRC="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/bold.gif"  onclick="format(\'bold\')" width="20" height="20" border="0" alt="斜体"><img SRC="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/italic.gif" onclick="format(\'italic\')" width="20" height="20" border="0" alt="粗体"><img SRC="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/underline.gif"  onclick="format(\'underline\')" width="20" height="20" border="0" alt="下划线">	<img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/strikethrough.gif"  onclick="format(\'strikethrough\')" width="20" height="20" border="0" alt="下划线"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/separator.gif"   width="2" height="20" border="0" alt=""><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/justifyleft.gif" onclick="format(\'justifyleft\')" width="20" height="20" border="0" alt="左对齐"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/justifycenter.gif" onclick="format(\'justifycenter\')" width="20" height="20" border="0" alt="中对齐"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/justifyright.gif" onclick="format(\'justifyright\')" width="20" height="20" border="0" alt="右对齐"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/justifyfull.gif" onclick="format(\'justifyfull\')" width="20" height="20" border="0" alt="两端对齐"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/separator.gif"   width="2" height="20" border="0" alt=""><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/numlist.gif" onclick="format(\'Insertorderedlist\')"  width="20" height="20" border="0" alt="数字编号"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/bullist.gif" onclick="format(\'Insertunorderedlist\')" width="20" height="20" border="0" alt="项目编号"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/separator.gif"   width="2" height="20" border="0" alt=""><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/undo.gif" onclick="format(\'Undo\')"  width="20" height="20" border="0" alt="撤销"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/redo.gif" onclick="format(\'Redo\')" width="20" height="20" border="0" alt="重做"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/separator.gif"   width="2" height="20" border="0" alt=""><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/indent.gif" onclick="format(\'Indent\')" width="20" height="20" border="0" alt="增加缩进"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/outdent.gif" onclick="format(\'Outdent\')" width="20" height="20" border="0" alt="减少缩进"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/separator.gif"   width="2" height="20" border="0" alt=""><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/link.gif" onclick="createLink()" width="20" height="20" border="0" alt="添加链接"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/unlink.gif" onclick="format(\'Unlink\')" width="20" height="20" border="0" alt="取消链接"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/image.gif" onclick="selectImage(\''.__APP__.'/Attach/select/module/'.MODULE_NAME.'\')" width="20" height="20" border="0" alt="添加图片"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/separator.gif"   width="2" height="20" border="0" alt=""><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/cut.gif" onclick="format(\'Cut\')" width="20" height="20" border="0" alt="剪切"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/copy.gif" onclick="format(\'Copy\')" width="20" height="20" border="0" alt="拷贝"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/paste.gif" onclick="format(\'Paste\')" width="20" height="20" border="0" alt="粘贴"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/removeformat.gif" onclick="format(\'RemoveFormat\')" width="20" height="20" border="0" alt="清除格式"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/print.gif" onclick="format(\'Print\')" width="20" height="20" border="0" alt="打印"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/code.gif" onclick="setMode()" width="20" height="20" border="0" alt="查看源码"></div><textarea name="'.$name.'" id="sourceEditor" style="border:none;display:none" >'.$content.'</textarea><div style="'.$style.'" contentEditable="true" id="'.$id.'" >'.$content.'</div></div><script LANGUAGE="JavaScript">function saveEditor(){document.getElementById("'.$name.'").value = document.getElementById("'.$id.'").innerHTML;} document.getElementById("'.$id.'").onblur=saveEditor;</script>';
            	break;
            case 'MINI':
            	$parseStr  =  '<div class="smartEditor" style="'.$style.'"><script type="text/javascript" src="__ROOT__/Public/Js/smartEditor/smartEditor.js"></script><div id="tools" ><select onchange=setColor(options[this.selectedIndex].value) style="width:35px" name="color"><option value="" selected>颜色</option><option style="background: skyblue;" value=skyblue></option> <option style="background: royalblue" value=royalblue></option> <option style="background: blue" value=blue></option> <option style="background: darkblue" value=darkblue></option> <option style="background: orange" value=orange></option> <option style="background: orangered" value=orangered></option> <option style="background: crimson" value=crimson></option> <option style="background: red" value=red></option> <option style="background: firebrick" value=firebrick></option> <option style="background: darkred" value=darkred></option> <option style="background: green" value=green></option> <option style="background: limegreen" value=limegreen></option> <option style="background: seagreen" value=seagreen></option> <option style="background: deeppink" value=deeppink></option> <option style="background: tomato" value=tomato></option> <option style="background: coral" value=coral></option> <option style="background: purple" value=purple></option> <option style="background: indigo" value=indigo></option> <option style="background: burlywood" value=burlywood></option> <option style="background: sandybrown" value=sandybrown></option> <option style="background: sienna" value=sienna></option> <option style="background: chocolate" value=chocolate></option> <option style="background: teal" value=teal></option> <option style="background: silver" value=silver></option></select><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/bold.gif"  onclick="format(\'bold\')" width="20" height="20" border="0" alt="斜体"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/italic.gif" onclick="format(\'italic\')" width="20" height="20" border="0" alt="粗体"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/underline.gif"  onclick="format(\'underline\')" width="20" height="20" border="0" alt="下划线">	<img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/strikethrough.gif"  onclick="format(\'strikethrough\')" width="20" height="20" border="0" alt="下划线"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/separator.gif"   width="2" height="20" border="0" alt=""><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/numlist.gif" onclick="format(\'Insertorderedlist\')"  width="20" height="20" border="0" alt="数字编号"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/bullist.gif" onclick="format(\'Insertunorderedlist\')" width="20" height="20" border="0" alt="项目编号"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/separator.gif"   width="2" height="20" border="0" alt=""><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/indent.gif" onclick="format(\'Indent\')" width="20" height="20" border="0" alt="增加缩进"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/outdent.gif" onclick="format(\'Outdent\')" width="20" height="20" border="0" alt="减少缩进"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/separator.gif"   width="2" height="20" border="0" alt=""><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/link.gif" onclick="createLink()" width="20" height="20" border="0" alt="添加链接"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/unlink.gif" onclick="format(\'Unlink\')" width="20" height="20" border="0" alt="取消链接"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/image.gif" onclick="selectImage(\''.__APP__.'/Attach/select/module/'.MODULE_NAME.'\')" width="20" height="20" border="0" alt="添加图片"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/separator.gif"   width="2" height="20" border="0" alt=""><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/cut.gif" onclick="format(\'Cut\')" width="20" height="20" border="0" alt="剪切"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/copy.gif" onclick="format(\'Copy\')" width="20" height="20" border="0" alt="拷贝"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/paste.gif" onclick="format(\'Paste\')" width="20" height="20" border="0" alt="粘贴"><img src="'.WEB_PUBLIC_URL.'/Js/smartEditor/images/removeformat.gif" onclick="format(\'RemoveFormat\')" width="20" height="20" border="0" alt="清除格式"></div><textarea name="'.$name.'" id="sourceEditor" style="border:none;display:none" >'.$content.'</textarea><div style="'.$style.'" contentEditable="true" id="'.$id.'" >'.$content.'</div></div><script LANGUAGE="JavaScript">function saveEditor(){document.getElementById("'.$name.'").value = document.getElementById("'.$id.'").innerHTML;} document.getElementById("'.$id.'").onblur=saveEditor;</script>';
            	break;
            case 'UBB':
				$parseStr	=	'<div style="padding:1px;width:'.$width.';border:1px solid silver;float:left;"><script LANGUAGE="JavaScript"> showTool(); </script></div><div><TEXTAREA id="UBBEditor" name="'.$name.'"  style="clear:both;float:none;width:'.$width.';height:'.$height.'" >'.$content.'</TEXTAREA></div><div style="padding:1px;width:'.$width.';border:1px solid silver;float:left;"><script LANGUAGE="JavaScript">showEmot();  </script></div>';
				break;
            default :
                $parseStr  =  '<textarea id="'.$id.'" style="'.$style.'" name="'.$name.'" >'.$content.'</textarea>';
		}

        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * select标签解析
     * 格式： <html:select options="name" selected="value" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _select($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'select');
        $name       = $tag['name'];
        $options    = $tag['options'];
        $values     = $tag['values'];
        $output     = $tag['output'];
        $multiple   = $tag['multiple'];
        $id         = $tag['id'];
        $size       = $tag['size'];
        $first      = $tag['first'];
        $selected   = $tag['selected'];
        $style      = $tag['style'];
        $ondblclick = $tag['dblclick'];
		$onchange	= $tag['change'];

        if(!empty($multiple)) {
            $parseStr = '<select id="'.$id.'" name="'.$name.'" ondblclick="'.$ondblclick.'" onchange="'.$onchange.'" multiple="multiple" class="'.$style.'" size="'.$size.'" >';
        }else {
        	$parseStr = '<select id="'.$id.'" name="'.$name.'" onchange="'.$onchange.'" ondblclick="'.$ondblclick.'" class="'.$style.'" >';
        }
        if(!empty($first)) {
            $parseStr .= '<option value="" >'.$first.'</option>';
        }
        if(!empty($options)) {
            $parseStr   .= '<?php  foreach($'.$options.' as $key=>$val) { ?>';
            if(!empty($selected)) {
                $parseStr   .= '<?php if(!empty($'.$selected.') && ($'.$selected.' == $key || in_array($key,$'.$selected.'))) { ?>';
                $parseStr   .= '<option selected="selected" value="<?php echo $key ?>"><?php echo $val ?></option>';
                $parseStr   .= '<?php }else { ?><option value="<?php echo $key ?>"><?php echo $val ?></option>';
                $parseStr   .= '<?php } ?>';
            }else {
                $parseStr   .= '<option value="<?php echo $key ?>"><?php echo $val ?></option>';
            }
            $parseStr   .= '<?php } ?>';
        }else if(!empty($values)) {
            $parseStr   .= '<?php  for($i=0;$i<count($'.$values.');$i++) { ?>';
            if(!empty($selected)) {
                $parseStr   .= '<?php if(isset($'.$selected.') && ((is_string($'.$selected.') && $'.$selected.' == $'.$values.'[$i]) || (is_array($'.$selected.') && in_array($'.$values.'[$i],$'.$selected.')))) { ?>';
                $parseStr   .= '<option selected="selected" value="<?php echo $'.$values.'[$i] ?>"><?php echo $'.$output.'[$i] ?></option>';
                $parseStr   .= '<?php }else { ?><option value="<?php echo $'.$values.'[$i] ?>"><?php echo $'.$output.'[$i] ?></option>';
                $parseStr   .= '<?php } ?>';
            }else {
                $parseStr   .= '<option value="<?php echo $'.$values.'[$i] ?>"><?php echo $'.$output.'[$i] ?></option>';
            }
            $parseStr   .= '<?php } ?>';
        }
        $parseStr   .= '</select>';
        return $parseStr;
    }


    /**
     +----------------------------------------------------------
     * checkbox标签解析
     * 格式： <html:checkbox checkboxs="" checked="" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _checkbox($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'checkbox');
        $name       = $tag['name'];
        $checkboxes = $tag['checkboxes'];
        $checked    = $tag['checked'];
        $separator  = $tag['separator'];
        $checkboxes = $this->tpl->get($checkboxes);
        $checked    = $this->tpl->get($checked)?$this->tpl->get($checked):$checked;
        $parseStr   = '';
        foreach($checkboxes as $key=>$val) {
            if($checked == $key  || in_array($key,$checked) ) {
                $parseStr .= '<input type="checkbox" checked="checked" name="'.$name.'[]" value="'.$key.'">'.$val.$separator;
            }else {
                $parseStr .= '<input type="checkbox" name="'.$name.'[]" value="'.$key.'">'.$val.$separator;
            }
        }
        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * mulitSelect标签解析
     * 格式： <html:list datasource="" show="" />
     *
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function _multiCheckBox($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'mulitCheckBox');
        $id         = $tag['id'];                   //表格ID
        $name       = $tag['name'];                 //返回表单值
        $source     = $tag['source'];               //原始数据源
        $size       = $tag['size'];                 //下拉列表size
        $style      = $tag['style'];                //表格样式

        $parseStr	= "<!-- Think 系统多选组件开始 -->\n<div align=\"center\"><table class=\"".$style."\">";
        $parseStr	.= '<tr><td height="5" colspan="3" class="topTd" ></td></tr>';
        $parseStr	.= '<tr><th width="44%" >'.$sourceTitle.'</th><th ></th><th width="44%">'.$targetTitle.'</th></tr>';
        $parseStr	.= '<tr><td ><div class="solid"><html:select id="sourceSelect" options="'.$source.'" dblclick="addItem()" multiple="true" style="multiSelect" size="'.$size.'" /></div></td><td valign="middle"><div style="margin-top:35px"><html:imageBtn value="添加" click="addItem()" style="impBtn vMargin fLeft " /><html:imageBtn type="button" value="全选" click="addAllItem()" style="impBtn vMargin fLeft " /><html:imageBtn value="移除" click="delItem()" style="impBtn vMargin fLeft " /><html:imageBtn  value="全删" click="delAllItem()" style="impBtn vMargin fLeft " /></div></td>	<td ><div class="solid"><html:select name="'.$name.'[]" id="targetSelect" options="'.$target.'" dblclick="delItem()" multiple="true" style="multiSelect" size="'.$size.'" /></div></td></tr><tr><td height="5" colspan="3" class="bottomTd" ></td></tr></table></div>';
        $parseStr	.= "\n<!-- Think 系统多选组件结束 -->\n";
        return $parseStr;
	}

    /**
     +----------------------------------------------------------
     * radio标签解析
     * 格式： <html:radio radios="name" checked="value" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _radio($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'radio');
        $name       = $tag['name'];
        $radios     = $tag['radios'];
        $checked    = $tag['checked'];
        $separator  = $tag['separator'];
        $radios     = $this->tpl->get($radios);
        $checked    = $this->tpl->get($checked)?$this->tpl->get($checked):$checked;
        $parseStr   = '';
        foreach($radios as $key=>$val) {
            if($checked == $key ) {
                $parseStr .= '<input type="radio" checked="checked" name="'.$name.'[]" value="'.$key.'">'.$val.$separator;
            }else {
                $parseStr .= '<input type="radio" name="'.$name.'[]" value="'.$key.'">'.$val.$separator;
            }

        }
        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * link标签解析
     * 格式： <html:link file="" type="" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _link($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'link');
        $file       = $tag['href'];
        $type       = isset($tag['type'])?
                    strtolower($tag['type']):
                    strtolower(substr(strrchr($file, '.'),1));
        if($type=='js') {
            $parseStr = "<script type='text/javascript' src='".$file."'></script> ";
        }elseif($type=='css') {
            $parseStr = "<link rel='stylesheet' type='text/css' href='".$file."' />";
        }
        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * link标签解析
     * 格式： <html:link file="" type="" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _import($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'import');
        $file       = $tag['file'];
        $basepath   = !empty($tag['basepath'])?$tag['basepath']:WEB_PUBLIC_URL;
        $type       = !empty($tag['type'])?  strtolower($tag['type']):'js';
        if($type=='js') {
            $parseStr = "<script type='text/javascript' src='".$basepath.'/'.str_replace(array('.','#'), array('/','.'),$file).'.js'."'></script> ";
        }elseif($type=='css') {
            $parseStr = "<link rel='stylesheet' type='text/css' href='".$basepath.'/'.str_replace(array('.','#'), array('/','.'),$file).'.css'."' />";
        }
        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * imageLink标签解析
     * 格式： <html:imageLink type="" value="" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _imgLink($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'imgLink');
        $name       = $tag['name'];                //名称
        $alt        = $tag['alt'];                //文字
        $id         = $tag['id'];                //ID
        $style      = $tag['style'];                //样式名
        $click      = $tag['click'];                //点击
        $type       = $tag['type'];                //点击
        if(empty($type)) {
            $type = 'button';
        }
       	$parseStr   = '<span class="'.$style.'" ><input title="'.$alt.'" type="'.$type.'" id="'.$id.'"  name="'.$name.'" onmouseover="this.style.filter=\'alpha(opacity=100)\'" onmouseout="this.style.filter=\'alpha(opacity=80)\'" onclick="'.$click.'" align="absmiddle" class="'.$name.' imgLink"></span>';

        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * swf标签解析 插入flash文件
     * 格式： <html:swf type="" value="" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _swf($attr,$content)
    {
        $tag        =	$this->parseXmlAttr($attr,'swf');
        $id			=	$tag['id'];
        $src		=	$tag['src'];
        $width		=	$tag['width'];
		$parm		=	$tag['parm'];
		$vars		=	$tag['vars'];
		$bgcolor	=	$tag['bgcolor'];
        $height     =	$tag['height'];
        $version    =	$tag['version'];
        $autoinstall=	$tag['autoinstall'];

        $parseStr   = '<div id="flashcontent">'.$content.'</div><script type="text/javascript">';
		$parseStr	.='// <![CDATA['."\r\n";
		$parseStr	.= 'var so = new SWFObject("'.$src.'", "'.$id.'", "'.$width.'", "'.$height.'", "'.$version.'", "'.$bgcolor.'","'.$autoinstall.'");'."\r\n";
		$parseStr	.=	'so.addVariable("var", "value");'."\r\n";
		$parseStr	.=	'so.addParam("scale", "noscale");'."\r\n";
		$parseStr	.=	'so.write("flashcontent");'."\r\n";
		$parseStr	.= '// ]]></script>';

        return $parseStr;
    }


    /**
     +----------------------------------------------------------
     * imageBtn标签解析
     * 格式： <html:imageBtn type="" value="" />
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string|void
     +----------------------------------------------------------
     */
    public function _imageBtn($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'imageBtn');
        $name       = $tag['name'];                //名称
        $value      = $tag['value'];                //文字
        $id         = $tag['id'];                //ID
        $style      = $tag['style'];                //样式名
        $click      = $tag['click'];                //点击
        $type       = empty($tag['type'])?'button':$tag['type'];                //按钮类型

        if(!empty($name)) {
            $parseStr   = '<div class="'.$style.'" ><input type="'.$type.'" id="'.$id.'" name="'.$name.'" value="'.$value.'" onclick="'.$click.'" class="'.$name.' imgButton"></div>';
        }else {
        	$parseStr   = '<div class="'.$style.'" ><input type="'.$type.'" id="'.$id.'"  name="'.$name.'" value="'.$value.'" onclick="'.$click.'" class="button"></div>';
        }

        return $parseStr;
    }

    /**
     +----------------------------------------------------------
     * mulitSelect标签解析
     * 格式： <html:list datasource="" show="" />
     *
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function _multiSelect($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'mulitSelect');
        $id         = $tag['id'];                   //表格ID
        $name       = $tag['name'];                 //返回表单值
        $source     = $tag['source'];               //原始数据源
        $target     = $tag['target'];               //目标数据源
        $size       = $tag['size'];                 //下拉列表size
        $style      = $tag['style'];                //表格样式
        $multiple   = $tag['multiple'];             //是否多选
        $sourceTitle   = $tag['sourcetitle'];       //原始标题
        $targetTitle   = $tag['targettitle'];       //目标标题

        $parseStr	= "<!-- Think 系统多选组件开始 -->\n<div align=\"center\"><table class=\"".$style."\">";
        $parseStr	.= '<tr><td height="5" colspan="3" class="topTd" ></td></tr>';
        $parseStr	.= '<tr><th width="44%" >'.$sourceTitle.'</th><th ></th><th width="44%">'.$targetTitle.'</th></tr>';
        $parseStr	.= '<tr><td ><div class=""><html:select id="sourceSelect" options="'.$source.'" dblclick="addItem()" multiple="true" style="multiSelect" size="'.$size.'" /></div></td><td valign="middle"><div style="margin-top:35px"><html:imageBtn value="添 加" click="addItem()" style="button vMargin fLeft " /><html:imageBtn type="button" value="全 选" click="addAllItem()" style="button vMargin fLeft " /><html:imageBtn value="移 除" click="delItem()" style="button vMargin fLeft " /><html:imageBtn  value="全 删" click="delAllItem()" style="button vMargin fLeft " /></div></td>	<td ><div class=""><html:select name="'.$name.'[]" id="targetSelect" options="'.$target.'" dblclick="delItem()" multiple="true" style="multiSelect" size="'.$size.'" /></div></td></tr><tr><td height="5" colspan="3" class="bottomTd" ></td></tr></table></div>';
        $parseStr	.= "\n<!-- Think 系统多选组件结束 -->\n";
        return $parseStr;
	}

	public function _acl($attr) {
        $tag        = $this->parseXmlAttr($attr,'accessSelect');
        $id         = $tag['id'];                   //表单ID
        $name       = $tag['name'];                 //返回表单值
        $title     = $tag['title'];               //标题
		$module	=	$tag['module'];		// 授权模块名称
		$accessList	=	$tag['accesslist'];		// 权限列表
		$selectAccessList	 =	 $tag['selectaccesslist'];	// 已经授权的列表
		$submitMethod	=	$tag['submitmethod'];		// 提交响应方法
		$width	=	$tag['width']?$tag['width']:'260px';
		$size	=	$tag['size']?$tag['size']:15;
		$parseStr	.=	 '<!-- 授权组件开始 --><html:import file="Js.Form.MultiSelect" /><form method=POST id="'.$id.'"><table class="select" style="width:'.$width.'"><tr><td height="5" colspan="3" class="topTd" ></td></tr><tr><th class="tCenter">'.$title.' <html:select name="groupId" style="" change="location.href = \'?groupId=\'+this.options[this.selectedIndex].value;" first="选择组" options="groupList" selected="selectGroupId" /></th></tr><tr><th ></th></tr><tr><td ><html:select id="groupActionId" name="'.$name.'[]" options="'.$accessList.'" selected="'.$selectAccessList.'"  multiple="true" style="multiSelect" size="'.$size.'" /></td></tr><tr><td  class="row tCenter" ><input type="button" onclick="allSelect()" value="全 选" class="submit  ">	<input type="button" onclick="InverSelect()" value="反 选" class="submit  "> <input type="button" onclick="allUnSelect()" value="全 否" class="submit "> <input type="button" onclick="'.$submitMethod.'()" value="保 存" class="submit  "><input type="hidden" name="module" value="'.$module.'"><input type="hidden" name="ajax" value="1"></td></tr><tr><td height="5" class="bottomTd" ></td></tr></table></form><!-- 授权组件结束 -->';
		return $parseStr;
	}

    /**
     +----------------------------------------------------------
     * list标签解析
     * 格式： <html:list datasource="" show="" />
     *
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $attr 标签属性
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function _list($attr)
    {
        $tag        = $this->parseXmlAttr($attr,'list');
        $id         = $tag['id'];                       //表格ID
        $datasource = $tag['datasource'];               //列表显示的数据源VoList名称
        $pk         = empty($tag['pk'])?'id':$tag['pk'];//主键名，默认为id
        $style      = $tag['style'];                    //样式名
        $name       = !empty($tag['name'])?$tag['name']:'vo';                 //Vo对象名
        $action     = $tag['action'];                   //是否显示功能操作
        $checkbox   = $tag['checkbox'];                 //是否显示Checkbox
        if(isset($tag['actionlist'])) {
            $actionlist = explode(',',trim($tag['actionlist']));    //指定功能列表
        }

        if(substr($tag['show'],0,1)=='$') {
            $show   = $this->tpl->get(substr($tag['show'],1));
        }else {
            $show   = $tag['show'];
        }
        $show       = explode(',',$show);                //列表显示字段列表

        //计算表格的列数
        $colNum     = count($show);
        if(!empty($checkbox))   $colNum++;
        if(!empty($action))     $colNum++;

        //显示开始
		$parseStr	= "<!-- Think 系统列表组件开始 -->\n";
        $parseStr  .= '<table id="'.$id.'" class="'.$style.'" cellpadding=0 cellspacing=0 >';
        $parseStr  .= '<tr><td height="5" colspan="'.$colNum.'" class="topTd" ></td></tr>';
        $parseStr  .= '<tr class="row" >';
        //列表需要显示的字段
        $fields = array();
        foreach($show as $key=>$val) {
        	$fields[] = explode(':',$val);
        }
        if(!empty($checkbox) && 'true'==strtolower($checkbox)) {//如果指定需要显示checkbox列
            $parseStr .='<th width="8"><input type="checkbox" id="check" onclick="CheckAll(\''.$id.'\')"></th>';
        }
        foreach($fields as $field) {//显示指定的字段
            $property = explode('|',$field[0]);
            $showname = explode('|',$field[1]);
            if(isset($showname[1])) {
                $parseStr .= '<th width="'.$showname[1].'">';
            }else {
                $parseStr .= '<th>';
            }
            $showname[2] = isset($showname[2])?$showname[2]:$showname[0];
            $parseStr .= '<a href="javascript:sortBy(\''.$property[0].'\',\'{$sort}\',\''.ACTION_NAME.'\')" title="按照'.$showname[2].'{$sortType} ">'.$showname[0].'<equal name="order" value="'.$property[0].'" ><img src="../public/Images/{$sortImg}.gif" width="12" height="17" border="0" align="absmiddle"></equal></a></th>';
        }
        if(!empty($action)) {//如果指定显示操作功能列
            $parseStr .= '<th >操作</th>';
        }

        $parseStr .= '</tr>';
        $parseStr .= '<volist name="'.$datasource.'" id="'.$name.'" ><tr class="row" onmouseover="over(event)" onmouseout="out(event)" onclick="change(event)" >';	//支持鼠标移动单元行颜色变化 具体方法在js中定义

        if(!empty($checkbox)) {//如果需要显示checkbox 则在每行开头显示checkbox
            $parseStr .= '<td><input type="checkbox" name="key"	value="{$'.$name.'.'.$pk.'}"></td>';
        }
        foreach($fields as $field) {
            //显示定义的列表字段
            $parseStr   .=  '<td>';
            if(!empty($field[2])) {
                // 支持列表字段链接功能 具体方法由JS函数实现
                $href = explode('|',$field[2]);
                if(count($href)>1) {
                    // 支持多个字段传递
                    $array = explode('^',$href[1]);
                    if(count($array)>1) {
                        foreach ($array as $a){
                            $temp[] =  '\'{$'.$name.'.'.$a.'|addslashes}\'';
                        }
                        $parseStr .= '<a href="javascript:'.$href[0].'('.implode(',',$temp).')">';
                    }else{
                        $parseStr .= '<a href="javascript:'.$href[0].'(\'{$'.$name.'.'.$href[1].'|addslashes}\')">';
                    }
                }else {
                    //如果没有指定默认传编号值
                    $parseStr .= '<a href="javascript:'.$field[2].'(\'{$'.$name.'.'.$pk.'}\')">';
                }
            }
            $propertys = explode('^',$field[0]);
            foreach ($propertys as $property){
                $unit = explode('|',$property);
                if(count($unit)>1) {
                    $parseStr .= '{$'.$name.'.'.$unit[0].'|'.$unit[1].'} ';
                }else {
                    $parseStr .= '{$'.$name.'.'.$property.'} ';
                }
            }
            if(!empty($field[2])) {
                $parseStr .= '</a>';
            }
            $parseStr .= '</td>';

        }
        if(!empty($action)) {//显示功能操作
            if(!empty($actionlist[0])) {//显示指定的功能项
                $parseStr .= '<td>';
                foreach($actionlist as $val) {
                    // edit:编辑 表示 脚本方法名:显示名称
                    $a = explode(':',$val);
                    $b = explode('|',$a[1]);
                    if(count($b)>1) {
                        $c = explode('|',$a[0]);
                        if(count($c)>1) {
                            $parseStr .= '<a href="javascript:'.$c[1].'(\'{$'.$name.'.'.$pk.'}\')"><?php if(0== (is_array($'.$name.')?$'.$name.'["status"]:$'.$name.'->status)){ ?>'.$b[1].'<?php } ?></a><a href="javascript:'.$c[0].'({$'.$name.'.'.$pk.'})"><?php if(1== (is_array($'.$name.')?$'.$name.'["status"]:$'.$name.'->status)){ ?>'.$b[0].'<?php } ?></a> ';
                        }else {
                            $parseStr .= '<a href="javascript:'.$a[0].'(\'{$'.$name.'.'.$pk.'}\')"><?php if(0== (is_array($'.$name.')?$'.$name.'["status"]:$'.$name.'->status)){ ?>'.$b[1].'<?php } ?><?php if(1== (is_array($'.$name.')?$'.$name.'["status"]:$'.$name.'->status)){ ?>'.$b[0].'<?php } ?></a> ';
                        }

                    }else {
                        $parseStr .= '<a href="javascript:'.$a[0].'(\'{$'.$name.'.'.$pk.'}\')">'.$a[1].'</a> ';
                    }

                }
                $parseStr .= '</td>';
            }else { //显示默认的功能项，包括编辑、删除
                $parseStr .= '<td><a href="javascript:edit({$'.$name.'.'.$pk.'})">编辑</a> <a onfocus="javascript:getTableRowIndex(this)" href="javascript:del({$'.$name.'.'.$pk.'})">删除</a></td>';
            }

        }
        $parseStr	.= '</tr></volist><tr><td height="5" colspan="'.$colNum.'" class="bottomTd"></td></tr></table>';
        $parseStr	.= "\n<!-- Think 系统列表组件结束 -->\n";
        return $parseStr;
    }
    
    public function _ads($attr) {
        $tag        = $this->parseXmlAttr($attr,'ads');
        $place         = $tag['place'];
        $content       = $tag['content'];
        $style       = $tag['style'];
        $align   = $tag['align'];
        $adempty   = $tag['adempty']?$tag['adempty']:'暂无内容';
		$width	=	$tag['width']?$tag['width']:'100%';
		$height	=	$tag['height']?$tag['height']:'100%';
		$dao = D('Ads');
		$ads	=	$dao->findAll('place='."'$place'".' AND status>0','*','rand()',1);
		if($ads){
			$parseStr	=	 '<div align="'.$align.'" class="'.$style.'" style="width:'.$width.';height=:'.$height.';overflow:hidden">'.$ads[0]->content.'</div>';
		}else{
			$parseStr   =    '<div align="center">'.$adempty.'</div>';
		}
		return $parseStr;
	}


}//类定义结束
?>