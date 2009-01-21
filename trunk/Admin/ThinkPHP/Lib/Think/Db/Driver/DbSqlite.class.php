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
 * Sqlite数据库驱动类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Db
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class DbSqlite extends Db
{//类定义开始

    /**
     +----------------------------------------------------------
     * 架构函数 读取数据库配置信息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $config 数据库配置数组
     +----------------------------------------------------------
     */
    public function __construct($config=''){
        if ( !extension_loaded('sqlite') ) {
            throw_exception(L('_NOT_SUPPERT_').':sqlite');
        }
        if(!empty($config)) {
            if(!isset($config['mode'])) {
                $config['mode']	=	0666;
            }
            $this->config	=	$config;
        }
    }

    /**
     +----------------------------------------------------------
     * 连接数据库方法
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function connect($config='',$linkNum=0) {
        if ( !isset($this->linkID[$linkNum]) ) {
            if(empty($config))	$config	=	$this->config;
            $conn = $this->pconnect ? 'sqlite_popen':'sqlite_open';
            $this->linkID[$linkNum] = $conn($config['database'],$config['mode']);
            if ( !$this->linkID[$linkNum]) {
                throw_exception(sqlite_error_string());
                return false;
            }
            $this->dbVersion = sqlite_libversion();
            // 标记连接成功
            $this->connected	=	true;
            //注销数据库安全信息
            if(1 != C('DB_DEPLOY_TYPE')) unset($this->config);
        }
        return $this->linkID[$linkNum];
    }

    /**
     +----------------------------------------------------------
     * 释放查询结果
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     */
    public function free() {
        unset($this->resultSet);
        $this->queryID = 0;
    }

    /**
     +----------------------------------------------------------
     * 执行查询 主要针对 SELECT, SHOW 等指令
     * 返回数据集
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $str  sql指令
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function _query($str='') {
        $this->initConnect(false);
        if ( !$this->_linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        if (!$this->autoCommit && $this->isMainIps($this->queryStr)) {
            $this->startTrans();
        }else {
            //释放前次的查询结果
            if ( $this->queryID ) {    $this->free();    }
        }
        $this->Q(1);
        $this->queryID = sqlite_query($this->_linkID,$this->queryStr);
        $this->debug();
        if ( !$this->queryID ) {
            if ( $this->debug || C('DEBUG_MODE'))
                throw_exception($this->error());
            else
                return false;
        } else {
            $this->numRows = sqlite_num_rows($this->queryID);
            $this->resultSet = $this->getAll();
            return $this->resultSet;
        }
    }

    /**
     +----------------------------------------------------------
     * 执行语句 针对 INSERT, UPDATE 以及DELETE
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $str  sql指令
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function _execute($str='') {
        $this->initConnect(true);
        if ( !$this->_linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        if (!$this->autoCommit && $this->isMainIps($this->queryStr)) {
            $this->startTrans();
        }else {
            //释放前次的查询结果
            if ( $this->queryID ) {    $this->free();    }
        }
        $this->W(1);
        $this->debug();
        $result	=	sqlite_exec($this->_linkID,$this->queryStr);
        $this->debug();
        if ( false === $result ) {
            if ( $this->debug || C('DEBUG_MODE'))
                throw_exception($this->error());
            else
                return false;
        } else {
            $this->numRows = sqlite_changes($this->_linkID);
            $this->lastInsID = sqlite_last_insert_rowid($this->_linkID);
            return $this->numRows;
        }
    }

    /**
     +----------------------------------------------------------
     * 启动事务
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function startTrans() {
        $this->initConnect(true);
        if ( !$this->_linkID ) return false;
        //数据rollback 支持
        if ($this->transTimes == 0) {
            sqlite_query($this->_linkID,'BEGIN TRANSACTION');
        }
        $this->transTimes++;
        return ;
    }

    /**
     +----------------------------------------------------------
     * 用于非自动提交状态下面的查询提交
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            $result = sqlite_query($this->_linkID,'COMMIT TRANSACTION');
            if(!$result){
                throw_exception($this->error());
                return false;
            }
            $this->transTimes = 0;
        }
        return true;
    }

    /**
     +----------------------------------------------------------
     * 事务回滚
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = sqlite_query($this->_linkID,'ROLLBACK TRANSACTION');
            if(!$result){
                throw_exception($this->error());
                return false;
            }
            $this->transTimes = 0;
        }
        return true;
    }

    /**
     +----------------------------------------------------------
     * 获得下一条查询结果 简易数据集获取方法
     * 查询结果使用 $this->result()方法获取
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function next() {
        if ( !$this->queryID ) {
            throw_exception($this->error());
            return false;
        }
        if($this->resultType== DATA_TYPE_OBJ){
            // 返回对象集
            $this->result = sqlite_fetch_object($this->queryID);
            $stat = is_object($this->result);
        }else{
            // 返回数组集
            $this->result = sqlite_fetch_array($this->queryID,SQLITE_ASSOC);
            $stat = is_array($this->result);
        }
        return $stat;
    }

    /**
     +----------------------------------------------------------
     * 获得当前查询结果
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function result() {
        return $this->result;
    }

    /**
     +----------------------------------------------------------
     * 获得一条查询结果
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param index $seek 指针位置
     * @param string $str  SQL指令
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function getRow($sql = null,$seek=0) {
        if (!empty($sql)) $this->_query($sql);
        if ( !$this->queryID ) {
            throw_exception($this->error());
            return false;
        }
        if($this->numRows >0) {
            if(sqlite_seek($this->queryID,$seek)){
                if($this->resultType== DATA_TYPE_OBJ){
                    //返回对象集
                    $result = sqlite_fetch_object($this->queryID);
                }else{
                    // 返回数组集
                    $result = sqlite_fetch_array($this->queryID,SQLITE_ASSOC );
                }
            }
            return $result;
        }else {
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 获得所有的查询数据
     * 查询结果放到 resultSet 数组中
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $resultType  数据集类型
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function getAll($sql = null,$resultType=null) {
        if (!empty($sql)) $this->_query($sql);
        if ( !$this->queryID ) {
            throw_exception($this->error());
            return false;
        }
        //返回数据集
        $result = array();
        if($this->numRows >0) {
            if(is_null($resultType)){ $resultType   =  $this->resultType ; }
            for($i=0;$i<$this->numRows ;$i++ ){
                if($resultType== DATA_TYPE_OBJ){
                    //返回对象集
                    $result[$i] = sqlite_fetch_object($this->queryID);
                }else{
                    // 返回数组集
                    $result[$i] = sqlite_fetch_array($this->queryID,SQLITE_ASSOC);
                }
            }
            sqlite_seek($this->queryID,0);
        }
        return $result;
    }

    /**
     +----------------------------------------------------------
     * 取得数据表的字段信息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function getFields($tableName) {
        $result =   $this->_query('PRAGMA table_info( '.$tableName.' )');
        $info   =   array();
        foreach ($result as $key => $val) {
            if(is_object($val)) {
                $val	=	get_object_vars($val);
            }
            $info[$val['Field']] = array(
                'name'    => $val['Field'],
                'type'    => $val['Type'],
                'notnull' => (bool) ($val['Null'] === ''), // not null is empty, null is yes
                'default' => $val['Default'],
                'primary' => (strtolower($val['Key']) == 'pri'),
                'autoInc' => (strtolower($val['Extra']) == 'auto_increment'),
            );
        }
        return $info;
    }

    /**
     +----------------------------------------------------------
     * 取得数据库的表信息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function getTables($dbName='') {
        $result =   $this->_query("SELECT name FROM sqlite_master WHERE type='table' "
             . "UNION ALL SELECT name FROM sqlite_temp_master "
             . "WHERE type='table' ORDER BY name");
        $info   =   array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

    /**
     +----------------------------------------------------------
     * 关闭数据库
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function close() {
        if (!sqlite_close($this->_linkID)){
            throw_exception($this->error());
        }
        $this->_linkID = 0;
    }

    /**
     +----------------------------------------------------------
     * 数据库错误信息
     * 并显示当前的SQL语句
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function error() {
        $this->error = sqlite_error_string(sqlite_last_error($this->_linkID));
        if($this->queryStr!=''){
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
        }
        return $this->error;
    }

    /**
     +----------------------------------------------------------
     * SQL指令安全过滤
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $str  SQL指令
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function escape_string($str) {
        return sqlite_escape_string($str);
    }

    /**
     +----------------------------------------------------------
     * limit
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function limit($limit) {
        $limitStr    = '';
        if(!empty($limit)) {
            $limit  =   explode(',',$limit);
            if(count($limit)>1) {
                $limitStr .= ' LIMIT '.$limit[1].' OFFSET '.$limit[0].' ';
            }else{
                $limitStr .= ' LIMIT '.$limit[0].' ';
            }
        }
        return $limitStr;
    }
}//类定义结束
?>