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

// 输入数据管理类
// 使用方法
//  $Input = Input::getInstance();
//  $Input->get('name','md5','0');
//  $Input->session('memberId','','0');
class Input extends Base {

    private $filter =   null;   // 输入过滤
    private static $_input  =   array('get','post','request','env','server','cookie','session','globals','config','lang','call');

    static public function getInstance() {
        return get_instance_of(__CLASS__);
    }

    /**
     +----------------------------------------------------------
     * 魔术方法 有不存在的操作的时候执行
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $type 输入数据类型
     * @param array $args 参数 array(key,filter,default)
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function __call($type,$args=array()) {
        $type    =   strtolower(trim($type));
        if(in_array($type,self::$_input,true)) {
            switch($type) {
                case 'get':      $input      =& $_GET;break;
                case 'post':     $input      =& $_POST;break;
                case 'request': $input      =& $_REQUEST;break;
                case 'env':      $input      =& $_ENV;break;
                case 'server':   $input      =& $_SERVER;break;
                case 'cookie':   $input      =& $_COOKIE;break;
                case 'session':  $input      =& $_SESSION;break;
                case 'globals':   $input      =& $GLOBALS;break;
                case 'files':      $input      =& $_FILES;break;
                case 'call':       $input      =   'call';break;
                case 'config':    $input      =   C();break;
                case 'lang':      $input      =   L();break;
                default:return NULL;
            }
            if('call' === $input) {
                // 呼叫其他方式的输入数据
                $callback    =   array_shift($args);
                $params  =   array_shift($args);
                $data    =   call_user_func_array($callback,$params);
                if(count($args)===0) {
                    return $data;
                }
                $filter =   isset($args[0])?$args[0]:$this->filter;
                if(!empty($filter)) {
                    $data    =   call_user_func_array($filter,$data);
                }
            }else{
                if(0==count($args) || empty($args[0]) ) {
                    return $input;
                }elseif(array_key_exists($args[0],$input)) {
                    // 系统变量
                    $data	 =	 $input[$args[0]];
                    $filter	=	isset($args[1])?$args[1]:$this->filter;
                    if(!empty($filter)) {
                        $data	 =	 call_user_func_array($filter,$data);
                    }
                }else{
                    // 不存在指定输入
                    $data	 =	 isset($args[2])?$args[2]:NULL;
                }
            }
            return $data;
        }
    }

    /**
     +----------------------------------------------------------
     * 设置数据过滤方法
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @param mixed $filter 过滤方法
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function filter($filter) {
        $this->filter   =   $filter;
        return $this;
    }

}
?>