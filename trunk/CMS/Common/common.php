<?
function showTags($tags)
{
	$tags = explode(' ',$tags);
	$str = '';
	foreach($tags as $key=>$val) {
		$tag =  trim($val);
		$str  .= '<font color="Gray"><a href="'.url('show/module/'.MODULE_NAME.'/name/'.urlencode($tag), 'Tags').'">'.$tag.'</a></font>  ';
	}
	return $str;
}

function getTitleSize($count)
{
	$sc = (ceil($count/10)+11);
	if ($sc>33) $sc=33;
	$size = $sc.'px';
	return $size;
}

function rand_color()
{
	return '#'.rcolor().rcolor().rcolor();
}

function rcolor() {
	$rand = rand(0,255);
	return sprintf("%02X","$rand");
}

function toDate($time,$format='Y年m月d日 H:i:s')
{
	if( empty($time)) {
		return '';
	}
	$format = str_replace('#',':',$format);
	return date(auto_charset($format),$time);
}

function getmenu(){
	//全局应用
	$dao = D('Menu');
	$menu	=	$dao->findAll('','*','order DESC',8);
	dump($menu);
	return $menu;
}

/**
 +----------------------------------------------------------
 * UBB 解析
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function ubb($Text) {
	$Text=trim($Text);
	//$Text=htmlspecialchars($Text);
	//$Text=ereg_replace("\n","<br>",$Text);
	$Text=preg_replace("/\\t/is","  ",$Text);
	$Text=preg_replace("/\[hr\]/is","<hr>",$Text);
	$Text=preg_replace("/\[separator\]/is","<br/>",$Text);
	$Text=preg_replace("/\[h1\](.+?)\[\/h1\]/is","<h1>\\1</h1>",$Text);
	$Text=preg_replace("/\[h2\](.+?)\[\/h2\]/is","<h2>\\1</h2>",$Text);
	$Text=preg_replace("/\[h3\](.+?)\[\/h3\]/is","<h3>\\1</h3>",$Text);
	$Text=preg_replace("/\[h4\](.+?)\[\/h4\]/is","<h4>\\1</h4>",$Text);
	$Text=preg_replace("/\[h5\](.+?)\[\/h5\]/is","<h5>\\1</h5>",$Text);
	$Text=preg_replace("/\[h6\](.+?)\[\/h6\]/is","<h6>\\1</h6>",$Text);
	$Text=preg_replace("/\[center\](.+?)\[\/center\]/is","<center>\\1</center>",$Text);
	//$Text=preg_replace("/\[url=([^\[]*)\](.+?)\[\/url\]/is","<a href=\\1 target='_blank'>\\2</a>",$Text);
	$Text=preg_replace("/\[url\](.+?)\[\/url\]/is","<a href=\"\\1\" target='_blank'>\\1</a>",$Text);
	$Text=preg_replace("/\[url=(http:\/\/.+?)\](.+?)\[\/url\]/is","<a href='\\1' target='_blank'>\\2</a>",$Text);
	$Text=preg_replace("/\[url=(.+?)\](.+?)\[\/url\]/is","<a href=\\1>\\2</a>",$Text);
	$Text=preg_replace("/\[img\](.+?)\[\/img\]/is","<img src=\\1>",$Text);
	$Text=preg_replace("/\[img\s(.+?)\](.+?)\[\/img\]/is","<img \\1 src=\\2>",$Text);
	$Text=preg_replace("/\[color=(.+?)\](.+?)\[\/color\]/is","<font color=\\1>\\2</font>",$Text);
	$Text=preg_replace("/\[colorTxt\](.+?)\[\/colorTxt\]/eis","color_txt('\\1')",$Text);
	$Text=preg_replace("/\[style=(.+?)\](.+?)\[\/style\]/is","<div class='\\1'>\\2</div>",$Text);
	$Text=preg_replace("/\[size=(.+?)\](.+?)\[\/size\]/is","<font size=\\1>\\2</font>",$Text);
	$Text=preg_replace("/\[sup\](.+?)\[\/sup\]/is","<sup>\\1</sup>",$Text);
	$Text=preg_replace("/\[sub\](.+?)\[\/sub\]/is","<sub>\\1</sub>",$Text);
	$Text=preg_replace("/\[pre\](.+?)\[\/pre\]/is","<pre>\\1</pre>",$Text);
	$Text=preg_replace("/\[emot\](.+?)\[\/emot\]/eis","emot('\\1')",$Text);
	$Text=preg_replace("/\[email\](.+?)\[\/email\]/is","<a href='mailto:\\1'>\\1</a>",$Text);
	$Text=preg_replace("/\[i\](.+?)\[\/i\]/is","<i>\\1</i>",$Text);
	$Text=preg_replace("/\[u\](.+?)\[\/u\]/is","<u>\\1</u>",$Text);
	$Text=preg_replace("/\[b\](.+?)\[\/b\]/is","<b>\\1</b>",$Text);
	$Text=preg_replace("/\[quote\](.+?)\[\/quote\]/is","<blockquote>引用:<div style='border:1px solid silver;background:#EFFFDF;color:#393939;padding:5px' >\\1</div></blockquote>", $Text);
	$Text=preg_replace("/\[sig\](.+?)\[\/sig\]/is","<div style='text-align: left; color: darkgreen; margin-left: 5%'><br><br>--------------------------<br>\\1<br>--------------------------</div>", $Text);
	return $Text;
}

function getModuleName($mid)
{
	$dao = D('Module');
	$mcnname	=	$dao->find('id='."'$mid'",'modcnname');
	//dump($mcnname);
	return $mcnname->modcnname;
}

function getModuleId($module)
{
	$dao = D('Module');
	$mid	=	$dao->find('modname='."'$module'",'id');
	//dump($mcnname);
	return $mid->id;
}

function safeEncoding($string,$outEncoding = 'UTF-8')   
{   
    $encoding = "UTF-8";   
    for($i=0;$i<strlen($string);$i++)   
    {   
        if(ord($string{$i})<128)   
            continue;   
  
        if((ord($string{$i})&224)==224)   
        {   
            //第一个字节判断通过   
            $char = $string{++$i};   
            if((ord($char)&128)==128)   
            {   
                //第二个字节判断通过   
                $char = $string{++$i};   
                if((ord($char)&128)==128)   
                {   
                    $encoding = "UTF-8";   
                    break;   
                }   
            }   
        }   
        if((ord($string{$i})&192)==192)   
        {   
            //第一个字节判断通过   
            $char = $string{++$i};   
            if((ord($char)&128)==128)   
            {   
                //第二个字节判断通过   
                $encoding = "GB2312";   
                break;   
            }   
        }   
    }   
       
    if(strtoupper($encoding) == strtoupper($outEncoding))   
        return $string;   
    else  
        return iconv($encoding,$outEncoding,$string);   
}  


function emot($emot){
	return '<img src="'.WEB_PUBLIC_URL.'/Images/emot/'.$emot.'.gif" align="absmiddle" style="border:none;margin:0px 1px">';
}

function byte_format($input, $dec=0)
{
	$prefix_arr = array("B", "K", "M", "G", "T");
	$value = round($input, $dec);
	$i=0;
	while ($value>1024)
	{
		$value /= 1024;
		$i++;
	}
	$return_str = round($value, $dec).$prefix_arr[$i];
	return $return_str;
}

function showExt($ext,$pic=false) {
	static $_extPic = array(
	'dir'=>"folder.gif",
	'doc'=>'msoffice.gif',
	'rar'=>'rar.gif',
	'zip'=>'zip.gif',
	'txt'=>'text.gif',
	'pdf'=>'pdf.gif',
	'html'=>'html.gif',
	'png'=>'image.gif',
	'gif'=>'image.gif',
	'jpg'=>'image.gif',
	'php'=>'text.gif',
	);
	static $_extTxt = array(
	'dir'=>'文件夹',
	'jpg'=>'JPEG图象',
	);
	if($pic) {
		if(array_key_exists(strtolower($ext),$_extPic)) {
			$show = "<IMG SRC='".WEB_PUBLIC_URL."/Images/extension/".$_extPic[strtolower($ext)]."' BORDER='0' alt='' align='absmiddle'>";
		}else{
			$show = "<IMG SRC='".WEB_PUBLIC_URL."/Images/extension/common.gif' WIDTH='16' HEIGHT='16' BORDER='0' alt='文件' align='absmiddle'>";
		}
	}else{
		if(array_key_exists(strtolower($ext),$_extTxt)) {
			$show = $_extTxt[strtolower($ext)];
		}else{
			$show = $ext?$ext:'文件夹';
		}
	}

	return $show;
}
?>