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
 * PDO数据库驱动类
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Db
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class DbPdo extends Db{

    protected $PDOStatement = null;
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
        if ( !class_exists('PDO') ) {
            throw_exception(L('_NOT_SUPPERT_').':PDO');
        }
        if(!empty($config)) {
            $this->config   =   $config;
            if(empty($this->config['params'])) {
                $this->config['params'] =   array();
            }
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
            if(empty($config))  $config =   $this->config;

            if($this->pconnect) {
                $config['params'][constant('PDO::ATTR_PERSISTENT')] = true;
            }
            //add by wyfeng at 2008.12.29
            $config['params'][PDO::ATTR_CASE] = C("DB_CASE_LOWER")?PDO::CASE_LOWER:PDO::CASE_UPPER;
            try{
                $this->linkID[$linkNum] = new PDO( $config['dsn'], $config['username'], $config['password'],$config['params']);
            }catch (PDOException $e) {
                throw_exception($e->getMessage());
            }
            // 因为PDO的连接切换可能导致数据库类型不同，因此重新获取下当前的数据库类型
            $this->dbType = $this->_getDsnType($config['dsn']);
            $this->linkID[$linkNum]->exec('SET NAMES '.C('DB_CHARSET'));
            // 因个别驱动不支持getAttribute方法 暂时注释
            //$this->dbVersion = $this->linkID[$linkNum]->getAttribute(constant("PDO::ATTR_SERVER_INFO"));
            // 标记连接成功
            $this->connected    =   true;
            // 注销数据库连接配置信息
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
        $this->PDOStatement = null;
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
     * @return ArrayObject
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    protected function _query($str='') {
        $this->initConnect(false);
        if ( !$this->_linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        if (!$this->autoCommit && $this->isMainIps($this->queryStr)) {
            //$this->startTrans();
        }else {
            //释放前次的查询结果
            if ( !empty($this->PDOStatement) ) {    $this->free();    }
        }
        $this->Q(1);
        $this->PDOStatement = $this->_linkID->prepare($this->queryStr);
        if(false === $this->PDOStatement) {
            throw_exception($this->error());
        }
        $result =   $this->PDOStatement->execute();
        $this->debug();
        if ( !$result ) {
            if ( $this->debug || C('DEBUG_MODE'))
                throw_exception($this->error());
            else
                return false;
        } else {
            //$this->numCols = $this->PDOStatement->columnCount();
            $this->resultSet = $this->getAll();
            $this->numRows = count( $this->resultSet );
            if ( $this->numRows > 0 ){
                return $this->resultSet;
            }
            return false;
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
    protected function _execute($str='') {
        $this->initConnect(true);
        if ( !$this->_linkID ) return false;
        if ( $str != '' ) $this->queryStr = $str;
        if (!$this->autoCommit && $this->isMainIps($this->queryStr)) {
            $this->startTrans();
        }else {
            //释放前次的查询结果
            if ( !empty($this->PDOStatement) ) {    $this->free();    }
        }
        $this->W(1);
        $this->PDOStatement	=	$this->_linkID->prepare($this->queryStr);
        if(false === $this->PDOStatement) {
            throw_exception($this->error());
        }
        $result	=	$this->PDOStatement->execute();
        $this->debug();
        if ( false === $result) {
            if ( $this->debug || C('DEBUG_MODE'))
                throw_exception($this->error());
            else
                return false;
        } else {
            $this->numRows = $result;
             //modify by wyfeng at 2008.12.30
            preg_match("/^\s*(INSERT\s+INTO|REPLACE\s+INTO)\s+/i", $this->queryStr) && $this->lastInsID = $this->insert_last_id();
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
            $this->_linkID->beginTransaction();
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
            $result = $this->_linkID->commit();
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
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            $result = $this->_linkID->rollback();
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
     * 查询结果放到 result 数组中
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function next() {
        if ( !$this->PDOStatement ) {
            throw_exception($this->error());
            return false;
        }
        if($this->resultType== DATA_TYPE_OBJ){
            // 返回对象集
            $this->result = $this->PDOStatement->fetch(constant('PDO::FETCH_OBJ'));
            $stat = is_object($this->result);
        }else{
            // 返回数组集
            $this->result = $this->PDOStatement->fetch(constant('PDO::FETCH_ASSOC'));
            $stat = is_array($this->result);
        }
        return $stat;
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
     * @return array
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function getRow($sql = null,$seek=0) {
        if (!empty($sql)) $this->_query($sql);
        if ( empty($this->PDOStatement) ) {
            throw_exception($this->error());
            return false;
        }
        if($this->resultType== DATA_TYPE_OBJ){
            //返回对象集
            $result = $this->PDOStatement->fetch(constant('PDO::FETCH_OBJ'),constant('PDO::FETCH_ORI_NEXT'),$seek);
        }else{
            // 返回数组集
            $result = $this->PDOStatement->fetch(constant('PDO::FETCH_ASSOC'),constant('PDO::FETCH_ORI_NEXT'),$seek);
        }
        return $result;
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
        if ( empty($this->PDOStatement) ) {
            throw_exception($this->error());
            return false;
        }
        //返回数据集
        $result = array();
        if(is_null($resultType)){ $resultType   =  $this->resultType ; }
        if($resultType== DATA_TYPE_ARRAY){
            $result =   $this->PDOStatement->fetchAll(constant('PDO::FETCH_ASSOC'));
        }else{
            $result =   $this->PDOStatement->fetchAll(constant('PDO::FETCH_OBJ'));
        }
        return $result;
    }

    /**
     +----------------------------------------------------------
     * 取得数据表的字段信息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function getFields($tableName) {
        $this->initConnect(true);
        if(C('TABLE_DESCRIBE_SQL')) {
            // 定义特殊的字段查询SQL
            $sql   = str_replace('%table%',$tableName,C('TABLE_DESCRIBE_SQL'));
        }else{
            switch($this->dbType) {
                case 'MSSQL':
                    $sql   = "SELECT   column_name as name,   data_type as type,   column_default as default,   is_nullable as null
        FROM    information_schema.tables AS t
        JOIN    information_schema.columns AS c
        ON  t.table_catalog = c.table_catalog
        AND t.table_schema  = c.table_schema
        AND t.table_name    = c.table_name
        WHERE   t.table_name = '$tableName'";
                    break;
                case 'SQLITE':
                    $sql   = 'PRAGMA table_info ('.$tableName.') ';
                    break;
                case 'ORACLE':
                case 'OCI':
                    $sql   = "SELECT a.column_name name,data_type Type,decode(nullable,'Y',0,1) notnull,data_default \"Default\",decode(a.column_name,b.column_name,1,0) pk "
                      ."FROM user_tab_columns a,(SELECT column_name FROM user_constraints c,user_cons_columns col "
                      ."WHERE c.constraint_name=col.constraint_name AND c.constraint_type='P' and c.table_name='".strtoupper($tableName)
                      ."') b where table_name='".strtoupper($tableName)."' and a.column_name=b.column_name(+)";
                    break;
                case 'PGSQL':
                    $sql   = 'select fields_name as "Field",fields_type as "Type",fields_not_null as "Null",fields_key_name as "Key",fields_default as "Default",fields_default as "Extra" from table_msg('.$tableName.');';
                    break;
                case 'IBASE':
                    // 暂时不支持
                    throw_exception(L('_NOT_SUPPORT_DB_').':IBASE');
                    break;
                case 'MYSQL':
                default:
                    $sql   = 'DESCRIBE '.$tableName;
            }
        }
        //modify by wyfeng at 2008.12.29
        $result = $this->_query($sql);;
        $info   =   array();
        foreach ($result as $key => $val) {
            if(is_object($val)) {
                $val = get_object_vars($val);
            }
            $name= strtolower(isset($val['field'])?$val['field']:$val['name']);
            $info[strtolower($name)] = array(
                'name'    => $name ,
                'type'    => $val['type'],
                'notnull' => (bool)(((isset($val['null'])) && ($val['null'] === '')) || ((isset($val['notnull'])) && ($val['notnull'] === ''))), // not null is empty, null is yes
                'default' => isset($val['default'])? $val['default'] :(isset($val['dflt_value'])?$val['dflt_value']:""),
                'primary' => isset($val['key'])?strtolower($val['key']) == 'pri':isset($val['pk'])?$val['pk']:false,
                'autoInc' => isset($val['extra'])?strtolower($val['extra']) == 'auto_increment':isset($val['pk'])?$val['pk']:false,
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
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function getTables($dbName='') {
        if(C('FETCH_TABLES_SQL')) {
            // 定义特殊的表查询SQL
            $sql   = str_replace('%db%',$dnName,C('FETCH_TABLES_SQL'));
        }else{
            switch($this->dbType) {
            case 'ORACLE':
            case 'OCI':
                $sql   = 'SELECT table_name FROM user_tables';
                break;
            case 'MSSQL':
                $sql   = "SELECT TABLE_NAME	FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
                break;
            case 'PGSQL':
                $sql   = "select tablename as Tables_in_test from pg_tables where  schemaname ='public'";
                break;
            case 'IBASE':
                // 暂时不支持
                throw_exception(L('_NOT_SUPPORT_DB_').':IBASE');
                break;
            case 'SQLITE':
                $sql   = "SELECT name FROM sqlite_master WHERE type='table' "
                         . "UNION ALL SELECT name FROM sqlite_temp_master "
                         . "WHERE type='table' ORDER BY name";
                 break;
            case 'MYSQL':
            default:
                if(!empty($dbName)) {
                   $sql    = 'SHOW TABLES FROM '.$dbName;
                }else{
                   $sql    = 'SHOW TABLES ';
                }
            }
        }
        $result = $this->_query($sql);
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
        $this->_linkID = null;
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
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function error() {
        if($this->PDOStatement) {
            $error = $this->PDOStatement->errorInfo();
            $this->error = $error[2];
        }else{
            $this->error = '';
        }
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
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    public function escape_string($str) {
        //modify by wyfeng at 2008.12.30
         switch($this->dbType)
         {
            case 'PGSQL':
            case 'MSSQL':
            case 'IBASE':
            case 'MYSQL':
                return addslashes($str);
            case 'SQLITE':
            case 'ORACLE':
            case 'OCI':
                return str_ireplace("'", "''", $str);
        }
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
            switch($this->dbType){
                case 'PGSQL':
                case 'SQLITE':
                    $limit  =   explode(',',$limit);
                    if(count($limit)>1) {
                        $limitStr .= ' LIMIT '.$limit[1].' OFFSET '.$limit[0].' ';
                    }else{
                        $limitStr .= ' LIMIT '.$limit[0].' ';
                    }
                    break;
                case 'MSSQL':
                    $limit	=	explode(',',$limit);
                    if(count($limit)>1) {
                        $this->offset	=	$limit[0];
                        $limitStr	=	' TOP '.$limit[1].' ';
                    }else{
                        $this->offset	=0;
                        $limitStr = ' TOP '.$limit[0].' ';
                    }
                    break;
                case 'IBASE':
                    // 暂时不支持
                    break;
                case 'ORACLE':
                case 'OCI':
                    $limit = explode(',',$limit);
                    $limitStr = "(numrow>" . $limit[0] . ") AND (numrow<=" . $limit[1] . ")";
                    break;
                case 'MYSQL':
                default:
                    $limitStr .= ' LIMIT '.$limit.' ';
            }
        }
        return $limitStr;
    }

    /**
     +----------------------------------------------------------
     * 获取最后插入id ,仅适用于采用序列+触发器结合生成ID的方式,
     * ORACLE列程
       在config.php中指定
        'DB_TRIGGER_PREFIX'	=>	'tr_',
        'DB_SEQUENCE_PREFIX' =>	'ts_',
     * eg:表 tb_user
       相对tb_user的序列为：
        -- Create sequence
        create sequence TS_USER
        minvalue 1
        maxvalue 999999999999999999999999999
        start with 1
        increment by 1
        nocache;
       相对tb_user,ts_user的触发器为：
        create or replace trigger TR_USER
          before insert on "TB_USER"
          for each row
        begin
            select "TS_USER".nextval into :NEW.ID from dual;
        end;
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $str  序列名称，默认为序列前缀+表名（无前缀）
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     * @throws ThinkExecption
     +----------------------------------------------------------
     */
    //add by wyfeng at 2008.12.22
    public function insert_last_id()
    {
        if(empty($this->tableName))
        {
            return 0;
        }
         switch($this->dbType)
         {
            case 'PGSQL':
            case 'SQLITE':
            case 'MSSQL':
            case 'IBASE':
            case 'MYSQL':
                return $this->_linkID->lastInsertId();
            case 'ORACLE':
            case 'OCI':
                $sequenceName = C("DB_SEQUENCE_PREFIX") . $this->tableName;
                $vo = $this->_query("SELECT {$sequenceName}.currval currval FROM dual");
                return $vo?$vo[0]["currval"]:0;
        }
    }

}//类定义结束
?>