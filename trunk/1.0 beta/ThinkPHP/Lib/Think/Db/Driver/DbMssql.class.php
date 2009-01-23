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
 * MSsql数据库驱动类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Db
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class DbMssql extends Db{

    // 初始游标位置
    protected $offset = 0;

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
        if ( !function_exists('mssql_connect') ) {
            throw_exception(L('_NOT_SUPPERT_').':mssql');
        }
        if(!empty($config)) {
            $this->config	=	$config;
        }
        //读取数据结果集类型
        $this->resultType=C('DATA_RESULT_TYPE');
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
            if(empty($config))	$config  =  $this->config;
            $conn = $this->pconnect ? 'mssql_pconnect':'mssql_connect';
            // 处理不带端口号的socket连接情况
            $host = $config['hostname'].($config['hostport']?":{$config['hostport']}":'');
            $this->linkID[$linkNum] = $conn( $host, $config['username'], $config['password']);

            if ( !$this->linkID[$linkNum]) {
                throw_exception($this->error());
                return false;
            }

            if ( !mssql_select_db($config['database'], $this->linkID[$linkNum]) ) {
                throw_exception($this->error());
                return false;
            }
            // 标记连接成功
            $this->connected =  true;
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
        mssql_free_result($this->queryID);
        $this->queryID = 0;
    }

    /**
     +----------------------------------------------------------
     * 执行查询 主要针对 SELECT, SHOW 等指令
     * 返回数据集
     +----------------------------------------------------------
     * @access protected
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
        $this->queryID = @mssql_query($this->queryStr, $this->_linkID);
        $this->debug();
        if ( !$this->queryID ) {
            if ( $this->debug || C('DEBUG_MODE'))
                throw_exception($this->error());
            else
                return false;
        } else {
            $this->numRows = mssql_num_rows($this->queryID);
            $this->resultSet = $this->getAll();
            return $this->resultSet;
        }
    }

    /**
     +----------------------------------------------------------
     * 执行语句 针对 INSERT, UPDATE 以及DELETE
     +----------------------------------------------------------
     * @access protected
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
        $result	=	mssql_query($this->queryStr, $this->_linkID);
        if ( false === $result ) {
            if ( $this->debug || C('DEBUG_MODE'))
                throw_exception($this->error());
            else
                return false;
        } else {
            $this->numRows = mssql_rows_affected($this->_linkID);
            $this->lastInsID = $this->mssql_insert_id();
            return $this->numRows;
        }
    }

    /**
     +----------------------------------------------------------
     * 用于获取最后插入的ID
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     */
    public function mssql_insert_id()
    {
        $query  =   "SELECT @@IDENTITY as last_insert_id";
        $result =   mssql_query($query, $this->_linkID);
        list($last_insert_id)   =   mssql_fetch_row($result);
        mssql_free_result($result);
        return $last_insert_id;
    }

    /**
     +----------------------------------------------------------
     * 启动事务
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function startTrans() {
        $this->initConnect(true);
        if ( !$this->_linkID ) return false;
        //数据rollback 支持
        if ($this->transTimes == 0) {
            mssql_query('BEGIN TRAN', $this->_linkID);
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
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            $result = mssql_query('COMMIT TRAN', $this->_linkID);
            $this->transTimes = 0;
            if(!$result){
                throw_exception($this->error());
                return false;
            }
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
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = mssql_query('ROLLBACK TRAN', $this->_linkID);
            $this->transTimes = 0;
            if(!$result){
                throw_exception($this->error());
                return false;
            }
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
            $this->result = mssql_fetch_object($this->queryID);
            $stat = is_object($this->result);
        }else{
            // 返回数组集
            $this->result = mssql_fetch_assoc($this->queryID);
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
     * @param integer $seek 指针位置
     * @param string $str  SQL指令
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function getRow($sql = null,$seek=0) {
        if (!empty($sql)) $this->_query($sql);
        if ( !$this->queryID ) {
            throw_exception($this->error());
            return false;
        }
        if($this->numRows >0) {
            if(mssql_data_seek($this->queryID,$seek)){
                if($this->resultType== DATA_TYPE_OBJ){
                    //返回对象集
                    $result = mssql_fetch_object($this->queryID);
                }else{
                    // 返回数组集
                    $result = mssql_fetch_assoc($this->queryID);
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
                if($this->offset>0) {
                  // 设置初始游标位置 针对mssql的分页方案
                   mssql_data_seek($this->queryID,$this->offset);
                }
          if(is_null($resultType)){ $resultType   =  $this->resultType ; }
              $fun	=	$resultType==DATA_TYPE_OBJ?'mssql_fetch_object':'mssql_fetch_assoc';
            while($row = $fun($this->queryID)){
                $result[]   =   $row;
            }
            //mssql_data_seek($this->queryID,0);
            //分页偏移后,再将偏移位置复位 剑雷 2008.12.24
            mssql_data_seek($this->queryID,$this->offset);
            $this->offset=0;
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
    function getFields($tableName) {
        $result =   $this->getAll("SELECT   column_name,   data_type,   column_default,   is_nullable
        FROM    information_schema.tables AS t
        JOIN    information_schema.columns AS c
        ON  t.table_catalog = c.table_catalog
        AND t.table_schema  = c.table_schema
        AND t.table_name    = c.table_name
        WHERE   t.table_name = '$tableName'");
        $info   =   array();
        foreach ($result as $key => $val) {
            if(is_object($val)) {
                $val	=	get_object_vars($val);
            }
            $info[$val['column_name']] = array(
                'name'    => $val['column_name'],
                'type'    => $val['data_type'],
                'notnull' => (bool) ($val['is_nullable'] === ''), // not null is empty, null is yes
                'default' => $val['column_default'],
                'primary' => false,
                'autoInc' => false,
            );
        }
        return $info;
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
    function getTables($dbName='') {
        $result   =  $this->getAll("SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_TYPE = 'BASE TABLE'
            ");
        $info   =   array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
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
        $limit	=	explode(',',$limit);
        if(count($limit)>1) {
            $this->offset	=	$limit[0];
            $limitStr	=	' TOP '.$limit[1].' ';
        }else{
            $this->offset	=0;
            $limitStr = ' TOP '.$limit[0].' ';
        }
        return $limitStr;
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
        if (!empty($this->queryID))
            mssql_free_result($this->queryID);
        if (!mssql_close($this->_linkID)){
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
        $this->error = mssql_get_last_message();
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
        return addslashes($str);
    }

}//类定义结束
?>