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
 * 基于角色的验证类
 +------------------------------------------------------------------------------
 * @category   ORG
 * @package  ORG
 * @subpackage  RBAC
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class RBAC extends Base
{

    //委托身份认证方法
    static function authenticate($map,$model='',$provider='')
    {
        //调用委托管理器进行身份认证
        import("ORG.RBAC.ProviderManager");
        if(empty($provider)) {
            $provider   =   C('USER_AUTH_PROVIDER');
        }
        if(empty($model)) {
            $model	=	C('USER_AUTH_MODEL');
        }
        $authProvider   =   ProviderManager::getInstance($provider);
        //使用给定的Map进行认证
        if($authProvider->authenticate($map,$model)) {
            $authInfo   =   $authProvider->data;
            return $authInfo;
        }else {
            //认证失败
            return false;
        }
    }

    //用于检测用户权限的方法,并保存到Session中
    static function saveAccessList($authId=null)
    {
        // 如果使用普通权限模式，保存当前用户的访问权限列表
        // 对管理员开发所有权限
        if(C('USER_AUTH_TYPE') !=2 && !$_SESSION[C('ADMIN_AUTH_KEY')]) {
            $_SESSION['_ACCESS_LIST']   =   RBAC::getAccessList($authId);
            foreach (array_keys($_SESSION['_ACCESS_LIST']) as $key =>$value)
            if($key=='ADMIN'){
            	 $_SESSION['isAdmin']  =  true;
            }
        }
        return ;
    }

    //取得用户的授权列表
    static function getAccessList($authId=null)
    {
        if(null===$authId) {
            $authId = $_SESSION[C('USER_AUTH_KEY')];
        }
        //获取权限访问列表
        import("ORG.RBAC.AccessDecisionManager");
        $accessManager = new AccessDecisionManager();
        $accessList = $accessManager->getAccessList($authId);
        return $accessList;
    }

    // 取得模块的所属记录访问权限列表 返回有权限的记录ID数组
    static function getRecordAccessList($authId=null,$module='') {
        if(null===$authId) {
            $authId = $_SESSION[C('USER_AUTH_KEY')];
        }
        if(empty($module)) {
            $module =   MODULE_NAME;
        }
        //获取权限访问列表
        import("ORG.RBAC.AccessDecisionManager");
        $accessManager = new AccessDecisionManager();
        $accessList = $accessManager->getModuleAccessList($authId,$module);
        return $accessList;
    }

    //检查当前操作是否需要认证
    static function checkAccess()
    {
        //如果项目要求认证，并且当前模块需要认证，则进行权限认证
        $_module    =   array();
        $_action    =   array();
        if("" != C('REQUIRE_AUTH_MODULE')) {
            //需要认证的模块
            $_module['yes'] = explode(',',strtoupper(C('REQUIRE_AUTH_MODULE')));
        }elseif('' != C('NOT_AUTH_MODULE')){
            //无需认证的模块
            $_module['no'] = explode(',',strtoupper(C('NOT_AUTH_MODULE')));
        }
        if(!empty($_module)) {
            //检查当前模块是否需要认证
            if((!empty($_module['no']) && !in_array(strtoupper(MODULE_NAME),$_module['no'])) || (!empty($_module['yes']) && in_array(strtoupper(MODULE_NAME),$_module['yes']))) {
                if("" != C('REQUIRE_AUTH_ACTION')) {
                    //需要认证的操作
                    $_action['yes'] = explode(',',strtoupper(C('REQUIRE_AUTH_ACTION')));
                }elseif('' != C('NOT_AUTH_ACTION')) {
                    //无需认证的操作
                    $_action['no'] = explode(',',strtoupper(C('NOT_AUTH_ACTION')));
                }
                if(!empty($_action)) {
                    //检查当前操作是否需要认证
                    if((!empty($_action['no']) && !in_array(strtoupper(ACTION_NAME),$_action['no'])) || (!empty($_action['yes']) && in_array(strtoupper(ACTION_NAME),$_action['yes']))) {
                        return true;
                    }else {
                        return false;
                    }
                }else{
                    return true;
                }
            }else {
                return false;
            }
        }else{
            return true;
        }
        return false;
    }

    //权限认证的过滤器方法
    static function AccessDecision($appName=APP_NAME)
    {
        //检查是否需要认证
        if(RBAC::checkAccess()) {
            //检查认证识别号
            if(!$_SESSION[C('USER_AUTH_KEY')]) {
                if(C('GUEST_AUTH_ON')) {
                    // 开启游客授权访问
                    if(!isset($_SESSION['_ACCESS_LIST'])) {
                        // 保存游客权限
                        RBAC::saveAccessList(C('GUEST_AUTH_ID'));
                    }
                }else{
                    // 禁止游客访问跳转到认证网关
					// 如果把默认网关修改为?m=Public&a=login,除了要把兼容PATHINFO模式的?s=做掉
					// 还要考虑到rewrite模式的问题,rewrite的情况下还得把入口文件名称补上
					// 所以干脆把USER_AUTH_GATEWAY中除了第一个/以外的/用PATH_DEPR替换
                    redirect(PHP_FILE."/".str_replace("/",C('PATH_DEPR'),ltrim(C('USER_AUTH_GATEWAY'),'/')));
                }
            }
            //存在认证识别号，则进行进一步的访问决策
            $accessGuid   =   md5($appName.MODULE_NAME.ACTION_NAME);
            if(!$_SESSION[C('ADMIN_AUTH_KEY')]) {//管理员无需认证
                if(C('USER_AUTH_TYPE')==2) {
                    //加强验证和即时验证模式 更加安全 后台权限修改可以即时生效
                    //通过数据库进行访问检查
                    $accessList = RBAC::getAccessList();
                }else {
                    // 如果是管理员或者当前操作已经认证过，无需再次认证
                    if( $_SESSION[$accessGuid]) {
                        return true;
                    }
                    //登录验证模式，比较登录后保存的权限访问列表
                    $accessList = $_SESSION['_ACCESS_LIST'];
                }
                //判断是否为组件化模式，如果是，验证其全模块名
                $module = defined('C_MODULE_NAME')?  C_MODULE_NAME   :   MODULE_NAME;
                //echo '['.strtoupper($appName).'][PUBLIC]['.strtoupper(ACTION_NAME).']';
                if(!isset($accessList[strtoupper($appName)][strtoupper($module)][strtoupper(ACTION_NAME)]) AND isset($accessList[strtoupper($appName)][strtoupper($module)]) AND isset($accessList[strtoupper($appName)]['BASE'][strtoupper(ACTION_NAME)])){
                	return true;
                }
                if(!isset($accessList[strtoupper($appName)][strtoupper($module)][strtoupper(ACTION_NAME)])) {
                    //throw_exception(L('_VALID_ACCESS_'));
                    return false;
                }else {
                    $_SESSION[$accessGuid]  =   true;
                }
            }
        }
        return true;
    }
}//end class
?>