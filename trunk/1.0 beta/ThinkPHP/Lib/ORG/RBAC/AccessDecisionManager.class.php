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
 * 访问决策管理器
 +------------------------------------------------------------------------------
 * @category   ORG
 * @package  ORG
 * @subpackage  RBAC
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class AccessDecisionManager extends Base
{//类定义开始

    public $roleTable    ;
    public $roleUserTable  ;
    public $roleAccessTable;
    public $roleNodeTable;


    /**
     +----------------------------------------------------------
     * 架构函数
     *
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     */
    public function __construct()
    {
        import("Think.Db.Db");
        $this->roleTable           = C('RBAC_ROLE_TABLE')?  C('RBAC_ROLE_TABLE') :  C('DB_PREFIX').'group';
        $this->roleUserTable     = C('RBAC_USER_TABLE')?  C('RBAC_USER_TABLE') :  C('DB_PREFIX').'groupuser';
        $this->roleAccessTable  = C('RBAC_ACCESS_TABLE')? C('RBAC_ACCESS_TABLE') :   C('DB_PREFIX').'access';
        $this->roleNodeTable    = C('RBAC_NODE_TABLE')? C('RBAC_NODE_TABLE') :  C('DB_PREFIX').'node';
    }

    /**
     +----------------------------------------------------------
     * 决策认证
     * 检查是否具有当前的操作权限
     +----------------------------------------------------------
     * @param integer $authId 认证id
     * @param string $app 项目名
     * @param string $module 模块名
     * @param string $action 操作名
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function decide($authId,$app=APP_NAME,$module=MODULE_NAME,$action=ACTION_NAME)
    {
        //决策认证号是否具有当前模块权限
        $db     =   DB::getInstance();
        $sql    =   "select a.id from ".
                    $this->roleTable." as a,".
                    $this->roleUserTable." as b,".
                    $this->roleAccessTable." as c ,".
                    $this->roleNodeTable." as d ".
                    "where b.userId={$authId} and b.groupId=a.id and ( c.groupId=a.id  or (c.groupId=a.pid and a.pid!=0 ) )  and a.status=1 and c.groupId=a.id and c.nodeId=d.id and ( (d.name='".$module."' and d.level=2) or ( d.name='".$action."' and d.level=3 ) or ( d.name='".$app."' and d.level=1) )";
        $rs =   $db->query($sql);
        if($rs->count()>0) {
            return true;
        }else {
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 取得当前认证号的所有权限列表
     +----------------------------------------------------------
     * @param string $appPrefix 数据库前缀
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function getAccessList($authId)
    {
        // 读取项目权限
        $db     =   DB::getInstance();
        $sql    =   "select d.id,d.name from ".
                    $this->roleTable." as a,".
                    $this->roleUserTable." as b,".
                    $this->roleAccessTable." as c ,".
                    $this->roleNodeTable." as d ".
                    "where b.userId={$authId} and b.groupId=a.id and ( c.groupId=a.id  or (c.groupId=a.pid and a.pid!=0 ) ) and a.status=1 and c.nodeId=d.id and d.level=1 and d.status=1";
        $apps =   $db->query($sql);
        $access =  array();
        foreach($apps as $key=>$app) {
            $app    =   (array)$app;
            $appId  =   $app['id'];
            $appName     =   $app['name'];
            // 读取项目的模块权限
            $access[strtoupper($appName)]   =  array();
            $sql    =   "select d.id,d.name from ".
                        $this->roleTable." as a,".
                        $this->roleUserTable." as b,".
                        $this->roleAccessTable." as c ,".
                        $this->roleNodeTable." as d ".
                        "where b.userId={$authId} and b.groupId=a.id and ( c.groupId=a.id  or (c.groupId=a.pid and a.pid!=0 ) ) and a.status=1 and c.nodeId=d.id and d.level=2 and d.pid={$appId} and d.status=1";
            $modules =   $db->query($sql);
            // 判断是否存在公共模块的权限
            $publicAction  = array();
            foreach($modules as $key=>$module) {
                $module =   (array)$module;
                $moduleId    =   $module['id'];
                $moduleName = $module['name'];
                if('PUBLIC'== strtoupper($moduleName)) {
                    $sql    =   "select d.id,d.name from ".
                                $this->roleTable." as a,".
                                $this->roleUserTable." as b,".
                                $this->roleAccessTable." as c ,".
                                $this->roleNodeTable." as d ".
                                "where b.userId={$authId} and b.groupId=a.id and ( c.groupId=a.id  or (c.groupId=a.pid and a.pid!=0 ) )  and a.status=1 and  c.nodeId=d.id and d.pid={$moduleId} and d.level=3 and d.status=1";
                    $rs =   $db->query($sql);
                    foreach ($rs as $a){
                        $a   =   (array)$a;
                        $publicAction[$a['name']]    =   $a['id'];
                    }
                    unset($modules[$key]);
                    break;
                }
            }
            // 依次读取模块的操作权限
            foreach($modules as $key=>$module) {
                $module =   (array)$module;
                $moduleId    =   $module['id'];
                $moduleName = $module['name'];
                $sql    =   "select d.id,d.name from ".
                            $this->roleTable." as a,".
                            $this->roleUserTable." as b,".
                            $this->roleAccessTable." as c ,".
                            $this->roleNodeTable." as d ".
                            "where b.userId={$authId} and b.groupId=a.id and ( c.groupId=a.id  or (c.groupId=a.pid and a.pid!=0 ) )  and a.status=1 and  c.nodeId=d.id and d.pid={$moduleId} and d.level=3 and d.status=1";
                $rs =   $db->query($sql);
                $action = array();
                foreach ($rs as $a){
                    $a   =   (array)$a;
                    $action[$a['name']]  =   $a['id'];
                }
                // 和公共模块的操作权限合并
                $action += $publicAction;
                $access[strtoupper($appName)][strtoupper($moduleName)]   =  array_change_key_case($action,CASE_UPPER);
            }
        }
        return $access;
    }

    // 读取模块所属的记录访问权限
    public function getModuleAccessList($authId,$module) {
        // 读取模块权限
        $db     =   DB::getInstance();
        $sql    =   "select c.nodeId from ".
                    $this->roleTable." as a,".
                    $this->roleUserTable." as b,".
                    $this->roleAccessTable." as c ".
                    "where b.userId={$authId} and b.groupId=a.id and ( c.groupId=a.id  or (c.groupId=a.pid and a.pid!=0 ) ) and a.status=1 and  c.module='{$module}' and c.status=1";
        $rs =   $db->query($sql);
        $access =   array();
        foreach ($rs as $node){
            $node   =   (array)$node;
            $access[]   =   $node['nodeId'];
        }
        return $access;
    }
}//类定义结束
?>