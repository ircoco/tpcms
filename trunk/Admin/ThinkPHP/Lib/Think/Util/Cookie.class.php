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
 * Cookie管理类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class Cookie extends Base
{
    // 判断Cookie是否存在
    static function is_set($name) {
        return isset($_COOKIE[C('COOKIE_PREFIX').$name]);
    }

    // 获取某个Cookie值
    static function get($name) {
        $value   = $_COOKIE[C('COOKIE_PREFIX').$name];
        if(C('COOKIE_SECRET_KEY')) {
            $value   =  self::_decrypt($value,C('COOKIE_SECRET_KEY'));
        }
        return $value;
    }

    // 设置某个Cookie值
    static function set($name,$value,$expire='',$path='',$domain='') {
        if($expire=='') {
            $expire =   C('COOKIE_EXPIRE');
        }
        if(empty($path)) {
            $path = C('COOKIE_PATH');
        }
        if(empty($domain)) {
            $domain =   C('COOKIE_DOMAIN');
        }
        $expire =   !empty($expire)?    time()+$expire   :  0;
        if(C('COOKIE_SECRET_KEY')) {
            $value   =  self::_encrypt($value,C('COOKIE_SECRET_KEY'));
        }
        setcookie(C('COOKIE_PREFIX').$name, $value,$expire,$path,$domain);
        $_COOKIE[C('COOKIE_PREFIX').$name]  =   $value;
    }

    // 删除某个Cookie值
    static function delete($name) {
        Cookie::set($name,'',time()-3600);
        unset($_COOKIE[C('COOKIE_PREFIX').$name]);
    }

    // 清空Cookie值
    static function clear() {
        unset($_COOKIE);
    }

    static private function _encrypt($value,$key){
       $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
       $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
       $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $value, MCRYPT_MODE_ECB, $iv);
       return trim(base64_encode($crypttext));
    }

    static private function _decrypt($value,$key){
       $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
       $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
       $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($value), MCRYPT_MODE_ECB, $iv);
       return trim($decrypttext);
    }
}
?>