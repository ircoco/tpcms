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
 * 日志处理类
 * 支持下面的日志类型
 * WEB_LOG_DEBUG 调试信息
 * WEB_LOG_ERROR 错误信息
 * SQL_LOG_DEBUG SQL调试
 * 分别对象的默认日志文件为
 * 调试日志文件 systemOut.log
 * 错误日志文件  systemErr.log
 * SQL日志文件  systemSql.log
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Util
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class Log extends Base
{//类定义开始

    static $log =   array();

    /**
     +----------------------------------------------------------
     * 记录日志
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @param string $message 日志信息
     * @param string $type  日志类型
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    static function record($message,$type=WEB_LOG_ERROR) {
        $now = date('[ y-m-d H:i:s ]');
        self::$log[$type][] =   "\r\n$now\r\n$message";
    }

    /**
     +----------------------------------------------------------
     * 日志保存
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @param string $message 日志信息
     * @param string $type  日志类型
     * @param string $file  写入文件 默认取定义日志文件
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    static function save()
    {
        $day    =   date('y_m_d');
        $_type  =   array(
            WEB_LOG_DEBUG   =>  realpath(LOG_PATH).'/'.$day."_systemOut.log",
            SQL_LOG_DEBUG   =>  realpath(LOG_PATH).'/'.$day."_systemSql.log",
            WEB_LOG_ERROR   =>  realpath(LOG_PATH).'/'.$day."_systemErr.log",
            );
        if(!is_writable(LOG_PATH)){
            halt(L('_FILE_NOT_WRITEABLE_').':'.LOG_PATH);
        }
        foreach (self::$log as $type=>$logs){
            //检测日志文件大小，超过配置大小则备份日志文件重新生成
            $destination    =   $_type[$type];
            if(is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination) ){
                  rename($destination,dirname($destination).'/'.time().'-'.basename($destination));
            }
            error_log(implode('',$logs), FILE_LOG,$destination );
        }
        clearstatcache();
    }

    /**
     +----------------------------------------------------------
     * 日志直接写入
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @param string $message 日志信息
     * @param string $type  日志类型
     * @param string $file  写入文件 默认取定义日志文件
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    static function write($message,$type=WEB_LOG_ERROR,$file='')
    {
        $now = date('[ y-m-d H:i:s ]');
        switch($type){
            case WEB_LOG_DEBUG:
                $logType ='[调试]';
                $destination = $file == ''? LOG_PATH.date('y_m_d')."_systemOut.log" : $file;
                break;
            case SQL_LOG_DEBUG:
                // 调试SQL记录
                $logType ='[SQL]';
                $destination = $file == ''? LOG_PATH.date('y_m_d')."_systemSql.log" : $file;
                break;
            case WEB_LOG_ERROR:
                $logType ='[错误]';
                $destination = $file == ''? LOG_PATH.date('y_m_d')."_systemErr.log" : $file;
                break;
        }
        if(!is_writable(LOG_PATH)){
            halt(L('_FILE_NOT_WRITEABLE_').':'.$destination);
        }
        //检测日志文件大小，超过配置大小则备份日志文件重新生成
        if(is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination) ){
              rename($destination,dirname($destination).'/'.time().'-'.basename($destination));
        }
        error_log("$now\r\n$message\r\n", FILE_LOG,$destination );
        clearstatcache();
    }


}//类定义结束
?>