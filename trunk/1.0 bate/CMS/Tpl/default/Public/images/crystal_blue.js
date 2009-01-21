//-------常用的JS
//判断浏览器类型
var qgExploer = navigator.appName;
var qgIE;
if(qgExploer == "Microsoft Internet Explorer")
{
	qgIE = "IE";
	if(navigator.appVersion.match(/7./i)!='7.')
	{
		qgIE = "IE6";
	}
}
else
{
	qgIE = "FF";
	document.write("<style type='text/css'>body{overflow-y:scroll;}</style>");
}

var qgbody = (document.documentElement) ? document.documentElement : document.body


//设为首页
function sethome(obj,url)
{
	try
	{
		obj.style.behavior='url(#default#homepage)';
		obj.sethomepage(url);
	}
	catch(e)
	{
		if(window.netscape)
		{
			try
			{
				netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
			}
			catch(e)
			{
				alert("感谢您光临本站\n\n\t您正在使用的浏览器无法正确添加到代到设为主页上\n\n\t请您手动进行设置！给您带来不便还请见谅...");
				return false;
			}
			var prefs = Components.classes['@mozilla.org/preferences-service;1'].getService(Components.interfaces.nsIPrefBranch);
			prefs.setCharPref('browser.startup.homepage',url);
		}
	}
	return false;
}

//加入收藏
function setfav(url,sitename)
{
	try
	{
		window.external.AddFavorite(url,sitename)
	}
	catch (e)
	{
		try
        {
            window.sidebar.addPanel(sitename,url,"");
        }
        catch (e)
        {
            alert("感谢您光临本站\n\n\t您好，您的操作: 加入收藏 失败，请您使用Ctrl+D进行添加");
			return false;
        }
	}
	return true;
}

//document.getElementById的简写
function $(id)
{
	return document.getElementById(id);
}

//网页跳转
function tourl(url)
{
	window.location.href=url;
}

//设定多长时间运行某个动作脚本
function timeset(time,act)
{
	time = parseInt(time);
	if(time < 1)
	{
		return false;
	}
	else
	{
		if(time < 10)
		{
			time = time*1000;
		}
		window.setTimeout(act,time);
	}
}

//邮箱检测
function checkemail(email)
{
	if(email.search(/^\w+((-\w+)|(\.\w+))*\@\w+((-\w+)|(\.\w+))*\.\w+$/) != -1)
	{
		return true;
	}
	else
	{
		return false;
	}
}

//图片滚动代码
//div_1是最外面的包围的样式，且必须overflow:hidden;
//div_2和div_3是同一个级别的DIV
function marquee(div_1,div_2,div_3,mt)
{
	var speed=40;
	var FGDemo=$(div_1);
	var FGDemo1=$(div_2);
	var FGDemo2=$(div_3);
	FGDemo2.innerHTML=FGDemo1.innerHTML
	function Marquee1()
	{
		if(FGDemo2.offsetHeight-FGDemo.scrollTop<=0)
		{
			FGDemo.scrollTop-=FGDemo1.offsetHeight;
		}
		else
		{
			FGDemo.scrollTop++;
		}
	}
	var MyMar1=setInterval(Marquee1,speed);
	FGDemo.onmouseover=function()
	{
		clearInterval(MyMar1);
	}
	FGDemo.onmouseout=function()
	{
		MyMar1=setInterval(Marquee1,speed);
	}
}
