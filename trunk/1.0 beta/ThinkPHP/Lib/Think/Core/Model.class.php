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

define('HAS_ONE',1);
define('BELONGS_TO',2);
define('HAS_MANY',3);
define('MANY_TO_MANY',4);

define('MUST_TO_VALIDATE',1);    // 必须验证
define('EXISTS_TO_VAILIDATE',0);        // 表单存在字段则验证
define('VALUE_TO_VAILIDATE',2);     // 表单值不为空则验证

/**
 +------------------------------------------------------------------------------
 * ThinkPHP Model模型类 抽象类
 * 实现了ORM和ActiveRecords模式
 +------------------------------------------------------------------------------
 * @category   Think
 * @package  Think
 * @subpackage  Core
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 +------------------------------------------------------------------------------
 */
class Model extends Base  implements IteratorAggregate
{
    // 数据库连接对象列表
    protected $_db = array();

    // 当前数据库操作对象
    protected $db = null;

    // 数据表前缀
    protected $tablePrefix  =   '';

    // 数据表后缀
    protected $tableSuffix = '';

    // 模型名称
    protected $name = '';

    // 数据库名称
    protected $dbName  = '';

    // 数据表名（不包含表前缀）
    protected $tableName = '';

    // 实际数据表名（包含表前缀）
    protected $trueTableName ='';

    // 字段信息
    protected $fields = array();

    // 字段类型信息
    protected $type  =   array();

    // 数据信息
    protected $data =   array();

    // 查询表达式参数
    protected $options  =   array();

    // 数据列表信息
    protected $dataList =   array();

    // 上次错误信息
    protected $error = '';
    // 验证错误信息
    protected $validateError    =   array();

    // 包含的聚合对象
    protected $aggregation = array();
    // 是否为复合对象
    protected $composite = false;
    // 是否为视图模型
    protected $viewModel = false;

    // 乐观锁
    protected $optimLock = 'lock_version';
    // 悲观锁
    protected $pessimisticLock = false;

    protected $autoSaveRelations      = false;        // 自动关联保存
    protected $autoDelRelations        = false;        // 自动关联删除
    protected $autoAddRelations       = false;        // 自动关联写入
    protected $autoReadRelations      = false;        // 自动关联查询
    protected $lazyQuery                =   false;                  // 是否启用惰性查询

    // 自动写入时间戳
    protected $autoCreateTimestamps = array('create_at','create_on','cTime');
    protected $autoUpdateTimestamps = array('update_at','update_on','mTime');
    protected $autoTimeFormat = '';

    protected $blobFields     =   null;
    protected $blobValues    = null;

    /**
     +----------------------------------------------------------
     * 架构函数
     * 取得DB类的实例对象 数据表字段检查
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 要创建的数据对象内容
     +----------------------------------------------------------
     */
    public function __construct($data='')
    {
        // 模型初始化
        $this->_initialize();
        // 模型名称获取
        $this->name =   $this->getModelName();
        // 如果不是复合对象进行数据库初始化操作
        if(!$this->composite) {
            import("Think.Db.Db");
            // 获取数据库操作对象
            if(!empty($this->connection)) {
                // 当前模型有独立的数据库连接信息
                $this->db = Db::getInstance($this->connection);
            }else{
                $this->db = Db::getInstance();
            }
            // 设置数据库的返回数据格式
            $this->db->resultType   =   C('DATA_RESULT_TYPE');
            //为获得ORACLE自增LastID而统一考虑的
            $this->db->tableName = $this->parseName($this->name);
            // 设置默认的数据库连接
            $this->_db[0]   =   &$this->db;
            // 设置表前后缀
            $this->tablePrefix = $this->tablePrefix?$this->tablePrefix:C('DB_PREFIX');
            $this->tableSuffix = $this->tableSuffix?$this->tableSuffix:C('DB_SUFFIX');
            // 数据表字段检测
            $this->_checkTableInfo();
        }
        // 如果有data数据进行实例化，则创建数据对象
        if(!empty($data)) {
            $this->create($data);
        }
    }

    /**
     +----------------------------------------------------------
     * 取得模型实例对象
     +----------------------------------------------------------
     * @static
     * @access public
     +----------------------------------------------------------
     * @return mixed 返回数据模型实例
     +----------------------------------------------------------
     */
    public static function getInstance()
    {
        return get_instance_of(__CLASS__);
    }

    /**
     +----------------------------------------------------------
     * 设置数据对象的值 （魔术方法）
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 名称
     * @param mixed $value 值
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function __set($name,$value) {
        // 设置数据对象属性
        $this->data[$name]  =   $value;
    }

    /**
     +----------------------------------------------------------
     * 获取数据对象的值 （魔术方法）
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 名称
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function __get($name) {
        if(isset($this->data[$name])) {
            return $this->data[$name];
        }elseif(property_exists($this,$name)){
            return $this->$name;
        }else{
            return null;
        }
    }

    /**
     +----------------------------------------------------------
     * 字符串命名风格转换
     * type
     * =0 将Java风格转换为C的风格
     * =1 将C风格转换为Java的风格
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param string $name 字符串
     * @param integer $type 转换类型
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function parseName($name,$type=0) {
        if($type) {
            return preg_replace("/_([a-zA-Z])/e", "strtoupper('\\1')", $name);
        }else{
            $name = preg_replace("/[A-Z]/", "_\\0", $name);
            return strtolower(trim($name, "_"));
        }
    }

    /**
     +----------------------------------------------------------
     * 利用__call方法重载 实现一些特殊的Model方法 （魔术方法）
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $method 方法名称
     * @param mixed $args 调用参数
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function __call($method,$args) {
        if(strtolower(substr($method,0,5))=='getby') {
            // 根据某个字段获取记录
            $field   =   $this->parseName(substr($method,5));
            if(in_array($field,$this->fields,true)) {
                array_unshift($args,$field);
                return call_user_func_array(array(&$this, 'getBy'), $args);
            }
        }elseif(strtolower(substr($method,0,6))=='getsby') {
            // 根据某个字段获取记录
            $field   =   $this->parseName(substr($method,6));
            if(in_array($field,$this->fields,true)) {
                array_unshift($args,$field);
                return call_user_func_array(array(&$this, 'getByAll'), $args);
            }
        }elseif(strtolower(substr($method,0,3))=='get'){
            // getter 模拟 仅针对数据对象
            $field   =   $this->parseName(substr($method,3));
            return $this->__get($field);
        }elseif(strtolower(substr($method,0,3))=='top'){
            // 获取前N条记录
            $count = substr($method,3);
            array_unshift($args,$count);
            return call_user_func_array(array(&$this, 'topN'), $args);
        }elseif(strtolower(substr($method,0,5))=='setby'){
            // 保存记录的某个字段
            $field   =   $this->parseName(substr($method,5));
            if(in_array($field,$this->fields,true)) {
                array_unshift($args,$field);
                return call_user_func_array(array(&$this, 'setField'), $args);
            }
        }elseif(strtolower(substr($method,0,3))=='set'){
            // setter 模拟 仅针对数据对象
            $field   =   $this->parseName(substr($method,3));
            array_unshift($args,$field);
            return call_user_func_array(array(&$this, '__set'), $args);
        }elseif(strtolower(substr($method,0,5))=='delby'){
            // 根据某个字段删除记录
            $field   =   $this->parseName(substr($method,5));
            if(in_array($field,$this->fields,true)) {
                array_unshift($args,$field);
                return call_user_func_array(array(&$this, 'deleteBy'), $args);
            }
        }elseif(strtolower(substr($method,0,3))=='del'){
            // unset 数据对象
            $field   =   $this->parseName(substr($method,3));
            if(in_array($field,$this->fields,true)) {
                if(isset($this->data[$field])) {
                    unset($this->data[$field]);
                }
            }
        }elseif(strtolower(substr($method,0,8))=='relation'){
            $type    =   strtoupper(substr($method,8));
            if(in_array($type,array('ADD','SAVE','DEL'),true)) {
                array_unshift($args,$type);
                return call_user_func_array(array(&$this, 'opRelation'), $args);
            }
        }else{
            throw_exception(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
        }
        return;
    }

    // 回调方法 初始化模型
    protected function _initialize() {}

    /**
     +----------------------------------------------------------
     * 数据库Create操作入口
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @param array $data 要create的数据
     * @param boolean $autoLink 是否关联写入
     * @param boolean $lock 是否加锁
     * @param boolean $fetchSql 是否返回sql
     +----------------------------------------------------------
     * @return false|integer
     +----------------------------------------------------------
     */
    private function _create(&$data,$autoLink=false,$multi=false,$lock=false,$fetchSql=false) {
        // 前置调用
        if(!$this->_before_create($data)) {
            return false;
        }
        $table      =   $this->getTableName();
        if(!empty($this->options)) {
            // 已经有定义的查询表达式
            $data      =   isset($this->options['data'])?      $this->options['data']:     $data;
            $lock      =   isset($this->options['lock'])?      $this->options['lock']:     $lock;
            $autoLink=  isset($this->options['link'])?          $this->options['link']:     $autoLink;
            $table     =   isset($this->options['table'])?     $this->options['table']:    $this->getTableName();
            $fetchSql = isset($this->options['fetch'])?       $this->options['fetch']:    $fetchSql;
            $this->options  =   array();
        }
        if($fetchSql) {
            return $this->db->add($data,$this->getTableName(),$multi,$lock,$fetchSql);
        }
        // 插入数据库
        if(false === $result = $this->db->add($data,$this->getTableName(),$multi)){
            // 数据库插入操作失败
            $this->error = L('_OPERATION_WRONG_');
            return false;
        }else {
            $insertId   =   $this->getLastInsID();
            if($insertId && !isset($data[$this->getPk()])) {
                $data[$this->getPk()]   =    $insertId;
            }
            $this->saveBlobFields($data);
            // 保存关联记录
            if ($this->autoAddRelations || $autoLink){
                $this->opRelation('ADD',$data);
            }
            // 后置调用
            $this->_after_create($data);
            //成功后返回插入ID
            return $insertId ?  $insertId   : $result;
        }
    }
    // Create回调方法 before after
    protected function _before_create(&$data) {return true;}
    protected function _after_create(&$data) {}

    /**
     +----------------------------------------------------------
     * 数据库Update操作入口
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @param array $data 要create的数据
     * @param mixed $where 更新条件
     * @param string $limit limit
     * @param string $order order
     * @param boolean $autoLink 是否关联写入
     * @param boolean $lock 是否加锁
     * @param boolean $fetchSql 是否返回sql
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    private function _update(&$data,$where='',$limit='',$order='',$autoLink=false,$lock=false,$fetchSql=false) {
        $table      =   $this->getTableName();
        if(!empty($this->options)) {
            // 已经有定义的查询表达式
            $where   =   isset($this->options['where'])?     $this->options['where']:    $where;
            $limit      =   isset($this->options['limit'])?     $this->options['limit']:        $limit;
            $order    =   isset($this->options['order'])?     $this->options['order']:    $order;
            $lock      =   isset($this->options['lock'])?      $this->options['lock']:     $lock;
            $autoLink=  isset($this->options['link'])?          $this->options['link']:     $autoLink;
            $table     =   isset($this->options['table'])?     $this->options['table']:    $this->getTableName();
            $fetchSql = isset($this->options['fetch'])?       $this->options['fetch']:    $fetchSql;
            $this->options  =   array();
        }
        // 前置调用
        if(!$this->_before_update($data,$where)) {
            return false;
        }
        $lock    =   ($this->pessimisticLock || $lock);
        if($this->viewModel) {
            $where  =   $this->checkCondition($where);
        }
        if($fetchSql) {
            // 返回SQL
            return $this->db->save($data,$table,$where,$limit,$order,$lock,true);
        }
        if(false ===$this->db->save($data,$table,$where,$limit,$order,$lock) ){
            $this->error = L('_OPERATION_WRONG_');
            return false;
        }else {
            $this->saveBlobFields($data);
            // 保存关联记录
            if ($this->autoSaveRelations || $autoLink){
                $this->opRelation('SAVE',$data);
            }
            // 后置调用
            $this->_after_update($data,$where);
            return true;
        }
    }
    // 更新回调方法
    protected function _before_update(&$data,$where) {return true;}
    protected function _after_update(&$data,$where) {}

    /**
     +----------------------------------------------------------
     * 数据库Read操作入口
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @param mixed $condition 查询条件
     * @param string $fields 查询字段
     * @param boolean $all 是否返回多个数据
     * @param string $order
     * @param string $limit
     * @param string $group
     * @param string $having
     * @param string $join
     * @param boolean $cache 是否查询缓存
     * @param boolean $relation 是否关联查询
     * @param boolean $lazy 是否惰性查询
     * @param boolean $lock 是否加锁
     * @param boolean $fetchSql 是否返回sql
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    private function _read($condition='',$fields='*',$all=false,$order='',$limit='',$group='',$having='',$join='',$cache=false,$relation=false,$lazy=false,$lock=false,$fetchSql=false) {
        $table      =   $this->getTableName();
        if(!empty($this->options)) {
            // 已经有定义的查询表达式
            $condition  =   isset($this->options['where'])?         $this->options['where']:    $condition;
            $table       =   isset($this->options['table'])?         $this->options['table']:    $this->getTableName();
            $fields       =   isset($this->options['field'])?         $this->options['field']:    $fields;
            $limit        =   isset($this->options['limit'])?         $this->options['limit']:        $limit;
            $order      =   isset($this->options['order'])?         $this->options['order']:    $order;
            $group      =   isset($this->options['group'])?         $this->options['group']:    $group;
            $having     =   isset($this->options['having'])?        $this->options['having']:   $having;
            $join         =   isset($this->options['join'])?          $this->options['join']:     $join;
            $cache      =   isset($this->options['cache'])?         $this->options['cache']:    $cache;
            $lock         =   isset($this->options['lock'])?          $this->options['lock']:     $lock;
            $lazy        =   isset($this->options['lazy'])?          $this->options['lazy']: $lazy;
            $relation    =   isset($this->options['link'])?              $this->options['link']:     $relation;
            $fetchSql = isset($this->options['fetch'])?       $this->options['fetch']:    $fetchSql;
            $this->options  =   array();
        }
        // 前置调用
        if(!$this->_before_read($condition)) {
            // 如果返回false 中止
            return false;
        }
        if($cache) {//启用动态数据缓存
            if($all) {
                $identify   = $this->name.'List_'.to_guid_string(func_get_args());
            }else{
                $identify   = $this->name.'_'.to_guid_string($condition);
            }
            $result  =  S($identify);
            if(false !== $result) {
                if(!$all) {
                    $this->cacheLockVersion($result);
                }
                // 后置调用
                $this->_after_read($condition,$result);
                return $result;
            }
        }
        if($this->viewModel) {
            $condition  =   $this->checkCondition($condition);
            $fields =   $this->checkFields($fields);
            $order  =   $this->checkOrder($order);
            $group  =   $this->checkGroup($group);
        }
        $lazy    =   ($this->lazyQuery || $lazy);
        $lock    =   ($this->pessimisticLock || $lock);
        if($fetchSql) {
            return $this->db->find($condition,$table,$fields,$order,$limit,$group,$having,$join,$cache,$lazy,$lock,true);
        }
        $rs = $this->db->find($condition,$table,$fields,$order,$limit,$group,$having,$join,$cache,$lazy,$lock);
        $result =   $this->rsToVo($rs,$all,0,$relation);
        // 后置调用
        $this->_after_read($condition,$result);
        if($result && $cache) {
            S($identify,$result);
        }
        return $result;
    }
    // Read回调方法
    protected function _before_read(&$condition) {return true;}
    protected function _after_read(&$condition,$result) {}

    /**
     +----------------------------------------------------------
     * 数据库Delete操作入口
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @param mixed $data 删除的数据
     * @param mixed $condition 查询条件
     * @param string $limit
     * @param string $order
     * @param boolean $autoLink 是否关联删除
     * @param boolean $lock 是否加锁
     * @param boolean $fetchSql 是否返回sql
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    private function _delete($data,$where='',$limit=0,$order='',$autoLink=false,$lock=false,$fetchSql=false) {
        $table      =   $this->getTableName();
        if(!empty($this->options)) {
            // 已经有定义的查询表达式
            $where      =   isset($this->options['where'])?     $this->options['where']:    $where;
            $table          =   isset($this->options['table'])?     $this->options['table']:    $this->getTableName();
            $limit          =   isset($this->options['limit'])?     $this->options['limit']:        $limit;
            $order      =   isset($this->options['order'])?     $this->options['order']:    $order;
            $lock         =   isset($this->options['lock'])?          $this->options['lock']:     $lock;
            $autoLink   =   isset($this->options['link'])?          $this->options['link']:     $autoLink;
            $fetchSql = isset($this->options['fetch'])?       $this->options['fetch']:    $fetchSql;
            $this->options  =   array();
        }
        // 前置调用
        if(!$this->_before_delete($where)) {
            return false;
        }
        if($this->viewModel) {
            $where  =   $this->checkCondition($where);
        }
        if($fetchSql) {
            return $this->db->remove($where,$table,$limit,$order,$lock,true);
        }
        $result=    $this->db->remove($where,$table,$limit,$order,$lock);
        if(false === $result ){
            $this->error =  L('_OPERATION_WRONG_');
            return false;
        }else {
            // 删除Blob数据
            $this->delBlobFields($data);
            // 删除关联记录
            if ($this->autoDelRelations || $autoLink){
                $this->opRelation('DEL',$data);
            }
            // 后置调用
            $this->_after_delete($where);
            //返回删除记录个数
            return $result;
        }
    }
    // Delete回调方法
    protected function _before_delete(&$where) {return true;}
    protected function _after_delete(&$where) {}

    /**
     +----------------------------------------------------------
     * 数据库Query操作入口(使用SQL语句的Query）
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @param mixed $sql 查询的SQL语句
     * @param boolean $cache 是否使用查询缓存
     * @param boolean $lazy 是否惰性查询
     * @param boolean $lock 是否加锁
     * @param boolean $fetchSql 是否返回sql
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    private function _query($sql='',$cache=false,$lazy=false,$lock=false,$fetchSql=false) {
        if(!empty($this->options)) {
            $sql        =   isset($this->options['sql'])?           $this->options['sql']:      $sql;
            $cache  =   isset($this->options['cache'])?     $this->options['cache']:    $cache;
            $lazy       =   isset($this->options['lazy'])?      $this->options['lazy']: $lazy;
            $lock       =   isset($this->options['lock'])?      $this->options['lock']:     $lock;
            $fetchSql = isset($this->options['fetch'])?       $this->options['fetch']:    $fetchSql;
            $this->options  =   array();
        }
        if(!$this->_before_query($sql)) {
            return false;
        }
        if($cache) {//启用动态数据缓存
            $identify   = md5($sql);
            $result =   S($identify);
            if(false !== $result) {
                return $result;
            }
        }
        $lazy    =   ($this->lazyQuery || $lazy);
        $lock    =   ($this->pessimisticLock || $lock);
        if($fetchSql) {
            return $this->db->query($sql,$cache,$lazy,$lock,$fetchSql);
        }
        $result =   $this->db->query($sql,$cache,$lazy,$lock);
        if($cache)    S($identify,$result);
        $this->_after_query($result);
        return $result;
    }
    // Query回调方法
    protected function _before_query(&$sql) {return true;}
    protected function _after_query(&$result) {}

    /**
     +----------------------------------------------------------
     * 数据表字段检测 并自动缓存
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    private function _checkTableInfo() {
        // 如果不是Model类 自动记录数据表信息
        // 只在第一次执行记录
        if(empty($this->fields) && strtolower(get_class($this))!='model') {
            // 如果数据表字段没有定义则自动获取
            if(C('DB_FIELDS_CACHE')) {
                $identify   =   $this->name.'_fields';
                $this->fields = F($identify);
                if(!$this->fields) {
                    $this->flush();
                }
            }else{
                // 每次都会读取数据表信息
                $this->flush();
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 强制刷新数据表信息
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function flush() {
        // 缓存不存在则查询数据表信息
        if($this->viewModel) {
            // 缓存视图模型的字段信息
            $this->fields = array();
            $this->fields['_autoInc'] = false;
            foreach ($this->viewFields as $name=>$val){
                $k = isset($val['_as'])?$val['_as']:$name;
                foreach ($val as $key=>$field){
                    if(is_numeric($key)) {
                        $this->fields[] =   $k.'.'.$field;
                    }else{
                        $this->fields[] =   $k.'.'.$key;
                    }
                }
            }
        }else{
            $fields =   $this->db->getFields($this->getTableName());
            $this->fields   =   array_keys($fields);
            $this->fields['_autoInc'] = false;
            foreach ($fields as $key=>$val){
                // 记录字段类型
                $this->type[$key]    =   $val['type'];
                if($val['primary']) {
                    $this->fields['_pk']    =   $key;
                    if($val['autoInc']) $this->fields['_autoInc']   =   true;
                }
            }
        }
        // 2008-3-7 增加缓存开关控制
        if(C('DB_FIELDS_CACHE')) {
            // 永久缓存数据表信息
            // 2007-10-31 更改为F方法保存，保存在项目的Data目录，并且始终采用文件形式
            $identify   =   $this->name.'_fields';
            F($identify,$this->fields);
        }
    }

    /**
     +----------------------------------------------------------
     * 获取数据的时候过滤数据字段
     +----------------------------------------------------------
     * @access pubic
     +----------------------------------------------------------
     * @param mixed $result 查询的数据
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function filterFields(&$result) {
        if(!empty($this->_filter)) {
            foreach ($this->_filter as $field=>$filter){
                $fun  =  $filter[1];
                if(isset($filter[2]) && $filter[2]){
                    // 传递整个数据对象作为参数
                    if(is_array($result)) {
                        $result[$field]  =  call_user_func($fun,$result);
                    }else{
                        $result->$field =  call_user_func($fun,$result);
                    }
                }else{
                    // 传递字段的值作为参数
                    if(is_array($result) && isset($result[$field])) {
                        $result[$field]  =  call_user_func($fun,$result[$field]);
                    }elseif(isset($result->$field)){
                        $result->$field =  call_user_func($fun,$result->$field);
                    }
                }
            }
        }
        return $result;
    }

    /**
     +----------------------------------------------------------
     * 获取数据列表的时候过滤数据字段
     +----------------------------------------------------------
     * @access pubic
     +----------------------------------------------------------
     * @param array $resultSet 查询的数据集
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function filterListFields(&$resultSet) {
        if(!empty($this->_filter)) {
            foreach ($resultSet as $key=>$result){
                $resultSet[$key]  =  $this->filterFields($result);
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 获取数据集的文本字段
     +----------------------------------------------------------
     * @access pubic
     +----------------------------------------------------------
     * @param mixed $resultSet 查询的数据
     * @param string $field 查询的字段
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function getListBlobFields(&$resultSet,$field='') {
        if(!empty($this->blobFields)) {
            foreach ($resultSet as $key=>$result){
                $result =   $this->getBlobFields($result,$field);
                $resultSet[$key]    =   $result;
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 获取数据的文本字段
     +----------------------------------------------------------
     * @access pubic
     +----------------------------------------------------------
     * @param mixed $data 查询的数据
     * @param string $field 查询的字段
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function getBlobFields(&$data,$field='') {
        if(!empty($this->blobFields)) {
            $pk =   $this->getPk();
            $id =   is_array($data)?$data[$pk]:$data->$pk;
            if(empty($field)) {
                foreach ($this->blobFields as $field){
                    if($this->viewModel) {
                        $identify   =   $this->masterModel.'_'.$id.'_'.$field;
                    }else{
                        $identify   =   $this->name.'_'.$id.'_'.$field;
                    }
                    if(is_array($data)) {
                        $data[$field]   =   F($identify);
                    }else{
                        $data->$field   =   F($identify);
                    }
                }
                return $data;
            }else{
                $identify   =   $this->name.'_'.$id.'_'.$field;
                return F($identify);
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 保存File方式的字段
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 保存的数据
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function saveBlobFields(&$data) {
        if(!empty($this->blobFields)) {
            foreach ($this->blobValues as $key=>$val){
                if(strpos($key,'@@_?id_@@')) {
                    $key    =   str_replace('@@_?id_@@',$data[$this->getPk()],$key);
                }
                F($key,$val);
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 删除File方式的字段
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 保存的数据
     * @param string $field 查询的字段
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function delBlobFields(&$data,$field='') {
        if(!empty($this->blobFields)) {
            $pk =   $this->getPk();
            $id =   is_array($data)?$data[$pk]:$data->$pk;
            if(empty($field)) {
                foreach ($this->blobFields as $field){
                    $identify   =   $this->name.'_'.$id.'_'.$field;
                    F($identify,null);
                }
            }else{
                $identify   =   $this->name.'_'.$id.'_'.$field;
                F($identify,null);
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 获取Iterator因子
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return Iterate
     +----------------------------------------------------------
     */
    public function getIterator()
    {
        if(!empty($this->dataList)) {
            // 存在数据集则返回数据集
            return new ArrayObject($this->dataList);
        }elseif(!empty($this->data)){
            // 存在数据对象则返回对象的Iterator
            return new ArrayObject($this->data);
        }else{
            // 否则返回字段名称的Iterator
            $fields = $this->fields;
            unset($fields['_pk'],$fields['_autoInc']);
            return new ArrayObject($fields);
        }
    }

    /**
     +----------------------------------------------------------
     * 把数据对象转换成数组
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function toArray()
    {
        if(!empty($this->dataList)) {
            return $this->dataList;
        }elseif (!empty($this->data)){
            return $this->data;
        }
        return false;
    }

    /**
     +----------------------------------------------------------
     * 新增数据 支持数组、HashMap对象、std对象、数据对象
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 数据
     * @param boolean $autoLink 自动关联写入
     * @param boolean $multi 多数据写入
     +----------------------------------------------------------
     * @return int
     +----------------------------------------------------------
     */
    public function add($data=null,$autoLink=false,$multi=false)
    {
        if(empty($data)) {
            // 没有传递数据，获取当前数据对象的值
            if(!empty($this->options['data'])) {
                $data    =   $this->options['data'];
            }elseif(!empty($this->data)) {
                $data    =   $this->data;
            }elseif(!empty($this->dataList)){
                return $this->addAll($this->dataList);
            }else{
                $this->error = L('_DATA_TYPE_INVALID_');
                return false;
            }
        }
        // 对保存到数据库的数据对象进行处理
        $data   =   $this->_facade($data);
        if(!$data) {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }
        // 记录乐观锁
        if($this->optimLock && !isset($data[$this->optimLock]) ) {
            if(in_array($this->optimLock,$this->fields,true)) {
                $data[$this->optimLock]  =   0;
            }
        }
        return $this->_create($data,$autoLink);
    }

    /**
     +----------------------------------------------------------
     * 对保存到数据库的数据对象进行处理
     * 统一使用数组方式到数据库中间层 facade字段支持
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param mixed $data 要操作的数据
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    protected function _facade($data) {
        if(is_instance_of($data,'HashMap')){
            // Map对象转换为数组
            $data = $data->toArray();
        }elseif(is_object($data)) {
            $data    =   get_object_vars($data);
        }elseif(is_string($data)){
            parse_str($data,$data);
        }elseif(!is_array($data)){
            return false;
        }
        // 检查聚合对象
        if(!empty($this->aggregation)) {
            foreach ($this->aggregation as $name){
                if(is_array($name)) {
                    $fields =   $name[1];
                    $name   =   $name[0];
                    if(is_string($fields)) $fields = explode(',',$fields);
                }
                if(!empty($data[$name])) {
                    $combine = (array)$data[$name];
                    if(!empty($fields)) {
                        // 限制聚合对象的字段属性
                        foreach ($fields as $key=>$field){
                            if(is_int($key)) $key = $field;
                            if(isset($combine[$key])) {
                                $data[$field]   =   $combine[$key];
                            }
                        }
                    }else{
                        // 直接合并数据
                        $data = $data+$combine;
                    }
                    unset($data[$name]);
                }
            }
        }
        // 检查非数据字段
        foreach ($data as $key=>$val){
            if(!$this->viewModel && empty($this->_link)) {
                if(!in_array($key,$this->fields,true)) {
                    unset($data[$key]);
                }
            }
        }
        // 检查Blob文件保存字段
        if(!empty($this->blobFields)) {
            foreach ($this->blobFields as $field){
                if(isset($data[$field])) {
                    if(isset($data[$this->getPk()])) {
                        $this->blobValues[$this->name.'_'.$data[$this->getPk()].'_'.$field] =   $data[$field];
                    }else{
                        $this->blobValues[$this->name.'_@@_?id_@@_'.$field] =   $data[$field];
                    }
                    unset($data[$field]);
                }
            }
        }
        // 写入数据的时候检查需要过滤的数据字段
        // $_filter  =  array('field'=>array('fun1','fun2'));
        if(!empty($this->_filter)) {
            foreach ($this->_filter as $field=>$filter){
                if(isset($data[$field])) {
                    $fun              =  $filter[0];
                    if(isset($filter[2]) && $filter[2]) {
                        // 传递整个数据对象作为参数
                        $data[$field]   =  call_user_func($fun,$data);
                    }else{
                        // 传递字段的值作为参数
                        $data[$field]   =  call_user_func($fun,$data[$field]);
                    }
                }
            }
        }
        // 检查字段映射
        if(isset($this->_map)) {
            foreach ($this->_map as $key=>$val){
                if(isset($data[$key]) && $key != $val ) {
                    $data[$val] =   $data[$key];
                    unset($data[$key]);
                }
            }
        }
        return $data;
    }

    /**
     +----------------------------------------------------------
     * 检查条件中的视图字段
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 条件表达式
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function checkCondition($data) {
         if((empty($data) || (is_instance_of($data,'HashMap') && $data->isEmpty())) && !empty($this->viewCondition)) {
             $data = $this->viewCondition;
         }elseif(!is_string($data)) {
            $data    =   $this->_facade($data);
            $baseCondition = empty($this->viewCondition)?array():$this->viewCondition;
            $view   =   array();
            // 检查视图字段
            foreach ($this->viewFields as $key=>$val){
                $k = isset($val['_as'])?$val['_as']:$key;
                foreach ($data as $name=>$value){
                    if(false !== $field = array_search($name,$val)) {
                        // 存在视图字段
                        if(is_numeric($field)) {
                            $_key   =   $k.'.'.$name;
                        }else{
                            $_key   =   $k.'.'.$field;
                        }
                        $view[$_key]    =   $value;
                        unset($data[$name]);
                        if(is_array($baseCondition) && isset($baseCondition[$_key])) {
                            // 组合条件处理
                            $view[$_key.','.$_key]  =   array($value,$baseCondition[$_key]);
                            unset($baseCondition[$_key]);
                            unset($view[$_key]);
                        }
                    }
                }
            }
            //if(!empty($view) && !empty($baseCondition)) {
                $data    =   array_merge($data,$baseCondition,$view);
            //}
         }
        return $data;
    }

    /**
     +----------------------------------------------------------
     * 检查fields表达式中的视图字段
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $fields 字段
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function checkFields($fields) {
        if(empty($fields) || '*'==$fields ) {
            // 获取全部视图字段
            $fields =   array();
            foreach ($this->viewFields as $name=>$val){
                $k = isset($val['_as'])?$val['_as']:$name;
                foreach ($val as $key=>$field){
                    if(is_numeric($key)) {
                        $fields[]    =   $k.'.'.$field.' AS '.$field;
                    }elseif('_' != substr($key,0,1)) {
                        // 以_开头的为特殊定义
                        if( false !== strpos($key,'*') ||  false !== strpos($key,'(') || false !== strpos($key,'.')) {
                            //如果包含* 或者 使用了sql方法 则不再添加前面的表名
                            $fields[]    =   $key.' AS '.$field;
                        }else{
                            $fields[]    =   $k.'.'.$key.' AS '.$field;
                        }
                    }
                }
            }
        }else{
            if(!is_array($fields)) {
                $fields =   explode(',',$fields);
            }
            // 解析成视图字段
            foreach ($this->viewFields as $name=>$val){
                $k = isset($val['_as'])?$val['_as']:$name;
                foreach ($fields as $key=>$field){
                    if(false !== $_field = array_search($field,$val)) {
                        // 存在视图字段
                        if(is_numeric($_field)) {
                            $fields[]    =   $k.'.'.$field.' AS '.$field;
                        }else{
                            if( false !== strpos($_field,'*') ||  false !== strpos($_field,'(') || false !== strpos($_field,'.')) {
                                //如果包含* 或者 使用了sql方法 则不再添加前面的表名
                                $fields[]    =   $_field.' AS '.$field;
                            }else{
                                $fields[]    =   $k.'.'.$_field.' AS '.$field;
                            }
                        }
                    }
                }
            }
        }
        $fields = implode(',',$fields);
        return $fields;
    }

    /**
     +----------------------------------------------------------
     * 检查Order表达式中的视图字段
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $order 字段
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function checkOrder($order) {
         if(!empty($order)) {
            $orders = explode(',',$order);
            $_order = array();
            foreach ($orders as $order){
                $array = explode(' ',$order);
                $field   =   $array[0];
                $sort   =   isset($array[1])?$array[1]:'ASC';
                // 解析成视图字段
                foreach ($this->viewFields as $name=>$val){
                    $k = isset($val['_as'])?$val['_as']:$name;
                    if(false !== $_field = array_search($field,$val)) {
                        // 存在视图字段
                        if(is_numeric($_field)) {
                            $field     =  $k.'.'.$field;
                        }else{
                            $field     =  $k.'.'.$_field;
                        }
                        break;
                    }
                }
                $_order[] = $field.' '.$sort;
            }
            $order = implode(',',$_order);
         }
        return $order;
    }

    /**
     +----------------------------------------------------------
     * 检查Group表达式中的视图字段
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $group 字段
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function checkGroup($group) {
         if(!empty($group)) {
            //$group = $this->getPk();
            $groups = explode(',',$group);
            $_group = array();
            foreach ($groups as $group){
                $array = explode(' ',$group);
                $field   =   $array[0];
                $sort   =   isset($array[1])?$array[1]:'';
                // 解析成视图字段
                foreach ($this->viewFields as $name=>$val){
                    $k = isset($val['_as'])?$val['_as']:$name;
                    if(false !== $_field = array_search($field,$val)) {
                        // 存在视图字段
                        if(is_numeric($_field)) {
                            $field  =  $k.'.'.$field;
                        }else{
                            $field  =  $k.'.'.$_field;
                        }
                        break;
                    }
                }
                $_group[$field] = $field.' '.$sort;
            }
            $group  =   $_group;
         }
        return $group;
    }

    /**
     +----------------------------------------------------------
     * 批量新增数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $dataList 数据列表
     * @param boolean $autoLink 自动关联操作
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function addAll($dataList='',$autoLink=false)
    {
        if(empty($dataList)) {
            $dataList   =   $this->dataList;
        }elseif(!is_array($dataList)) {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }
        return $this->_create($dataList,$autoLink,true);
    }

    /**
     +----------------------------------------------------------
     * 更新数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 要更新的数据
     * @param mixed $where 更新数据的条件
     * @param boolean $autoLink 自动关联操作
     * @param integer $limit 要更新的记录数
     * @param string $order  更新的顺序
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function save($data=null,$where='',$autoLink=false,$limit=0,$order='')
    {
        if(empty($data)) {
            if(!empty($this->options['data'])) {
                $data    =   $this->options['data'];
            }elseif(!empty($this->data)) {
                // 保存当前数据对象
                $data    =   $this->data;
            }elseif(!empty($this->dataList)){
                // 保存当前数据集
                $data    =   $this->dataList;
                $this->startTrans();
                foreach ($data as $val){
                    $result   =  $this->save($val,$where,$autoLink);
                }
                $this->commit();
                return $result;
            }
        }
        $data   =   $this->_facade($data);
        if(!$data) {
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }
        // 检查乐观锁
        if(!$this->checkLockVersion($data,$where)) {
            $this->error = L('_RECORD_HAS_UPDATE_');
            return false;
        }
        $pk   =  $this->getPk();
        if(empty($where) && isset($data[$pk]) && !is_array($data[$pk])) {
            $where  = $pk."=".$data[$pk];
            unset($data[$pk]);
        }
        return $this->_update($data,$where,$limit,$order,$autoLink);
    }

    /**
     +----------------------------------------------------------
     * 检查乐观锁
     +----------------------------------------------------------
     * @access protected
     +----------------------------------------------------------
     * @param array $data  当前数据
     * @param mixed $where 查询条件
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    protected function checkLockVersion(&$data,$where='') {
        $pk   =  $this->getPk();
        if(isset($data[$pk])) {
            $where  = $pk."=".$data[$pk];
            $guid =  $data[$pk];
        }else{
            $guid =  to_guid_string($where);
        }
        // 检查乐观锁
        $identify   = $this->name.'_'.$guid.'_lock_version';
        if($this->optimLock && isset($_SESSION[$identify])) {
            $lock_version = $_SESSION[$identify];
            if(!empty($where)) {
                $vo = $this->find($where,$this->optimLock);
            }else {
                $vo = $this->find($data,$this->optimLock);
            }
            $_SESSION[$identify]     =   $lock_version;
            $curr_version = is_array($vo)?$vo[$this->optimLock]:$vo->{$this->optimLock};
            if(isset($curr_version)) {
                if($curr_version>0 && $lock_version != $curr_version) {
                    // 记录已经更新
                    return false;
                }else{
                    // 更新乐观锁
                    $save_version = $data[$this->optimLock];
                    if($save_version != $lock_version+1) {
                        $data[$this->optimLock]  =   $lock_version+1;
                    }
                    $_SESSION[$identify]     =   $lock_version+1;
                }
            }
        }
        return true;
    }

    /**
     +----------------------------------------------------------
     * 获取返回数据的关联记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $result  返回数据
     * @param string $name  关联名称
     * @param boolean $return 是否返回关联数据
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function getRelation(&$result,$name='',$return=false)
    {
        if(!empty($this->_link)) {
            foreach($this->_link as $key=>$val) {
                    $mappingName =  !empty($val['mapping_name'])?$val['mapping_name']:$key; // 映射名称
                    if(empty($name) || $mappingName == $name) {
                        $mappingType = !empty($val['mapping_type'])?$val['mapping_type']:$val;  //  关联类型
                        $mappingClass  = !empty($val['class_name'])?$val['class_name']:$key;            //  关联类名
                        $mappingFields = !empty($val['mapping_fields'])?$val['mapping_fields']:'*';     // 映射字段
                        $mappingCondition = !empty($val['condition'])?$val['condition']:'1=1';          // 关联条件
                        if(strtoupper($mappingClass)==strtoupper($this->name)) {
                            // 自引用关联 获取父键名
                            $mappingFk   =   !empty($val['parent_key'])? $val['parent_key'] : 'parent_id';
                        }else{
                            $mappingFk   =   !empty($val['foreign_key'])?$val['foreign_key']:strtolower($this->name).'_id';     //  关联外键
                        }
                        // 获取关联模型对象
                        $model = D($mappingClass);
                        switch($mappingType) {
                            case HAS_ONE:
                                $pk   =  is_array($result)?$result[$this->getPk()]:$result->{$this->getPk()};
                                $mappingCondition .= " AND {$mappingFk}='{$pk}'";
                                $relationData   =  $model->find($mappingCondition,$mappingFields,false,false);
                                if(isset($val['as_fields'])) {
                                    // 支持直接把关联的字段值映射成数据对象中的某个字段
                                    $fields =   explode(',',$val['as_fields']);
                                    foreach ($fields as $field){
                                        $fieldAs = explode(':',$field);
                                        if(count($fieldAs)>1) {
                                            $fieldFrom = $fieldAs[0];
                                            $fieldTo    =   $fieldAs[1];
                                        }else{
                                            $fieldFrom   =   $field;
                                            $fieldTo      =   $field;
                                        }
                                        $fieldVal    =   is_array($relationData)?$relationData[$fieldFrom]:$relationData->$fieldFrom;
                                        if(isset($fieldVal)) {
                                            if(is_array($result)) {
                                                $result[$fieldTo]   =   $fieldVal;
                                            }else{
                                                $result->$fieldTo  =   $fieldVal;
                                            }
                                        }
                                    }
                                    unset($relationData);
                                }
                                break;
                            case BELONGS_TO:
                                if(strtoupper($mappingClass)==strtoupper($this->name)) {
                                    // 自引用关联 获取父键名
                                    $mappingFk   =   !empty($val['parent_key'])? $val['parent_key'] : 'parent_id';
                                }else{
                                    $mappingFk   =   !empty($val['foreign_key'])?$val['foreign_key']:strtolower($model->name).'_id';     //  关联外键
                                }
                                $fk   =  is_array($result)?$result[$mappingFk]:$result->{$mappingFk};
                                $mappingCondition .= " AND {$model->getPk()}='{$fk}'";
                                $relationData   =  $model->find($mappingCondition,$mappingFields,false,false);
                                if(isset($val['as_fields'])) {
                                    // 支持直接把关联的字段值映射成数据对象中的某个字段
                                    $fields =   explode(',',$val['as_fields']);
                                    foreach ($fields as $field){
                                        $fieldAs = explode(':',$field);
                                        if(count($fieldAs)>1) {
                                            $fieldFrom = $fieldAs[0];
                                            $fieldTo    =   $fieldAs[1];
                                        }else{
                                            $fieldFrom   =   $field;
                                            $fieldTo      =   $field;
                                        }
                                        $fieldVal    =   is_array($relationData)?$relationData[$fieldFrom]:$relationData->$fieldFrom;
                                        if(isset($fieldVal)) {
                                            if(is_array($result)) {
                                                $result[$fieldTo]   =   $fieldVal;
                                            }else{
                                                $result->$fieldTo   =   $fieldVal;
                                            }
                                        }
                                    }
                                    unset($relationData);
                                }
                                break;
                            case HAS_MANY:
                                $pk   =  is_array($result)?$result[$this->getPk()]:$result->{$this->getPk()};
                                $mappingCondition .= " AND {$mappingFk}='{$pk}'";
                                $mappingOrder =  !empty($val['mapping_order'])?$val['mapping_order']:'';
                                $mappingLimit =  !empty($val['mapping_limit'])?$val['mapping_limit']:'';
                                // 延时获取关联记录
                                $relationData   =  $model->findAll($mappingCondition,$mappingFields,$mappingOrder,$mappingLimit);
                                break;
                            case MANY_TO_MANY:
                                $pk   =  is_array($result)?$result[$this->getPk()]:$result->{$this->getPk()};
                                $mappingCondition = " {$mappingFk}='{$pk}'";
                                $mappingOrder =  $val['mapping_order'];
                                $mappingLimit =  $val['mapping_limit'];
                                $mappingRelationFk = $val['relation_foreign_key']?$val['relation_foreign_key']:$model->name.'_id';
                                $mappingRelationTable  =  $val['relation_table']?$val['relation_table']:$this->getRelationTableName($model);
                                $sql = "SELECT b.{$mappingFields} FROM {$mappingRelationTable} AS a, ".$model->getTableName()." AS b WHERE a.{$mappingRelationFk} = b.{$model->getPk()} AND a.{$mappingCondition}";
                                if(!empty($val['condition'])) {
                                    $sql   .= ' AND '.$val['condition'];
                                }
                                if(!empty($mappingOrder)) {
                                    $sql .= ' ORDER BY '.$mappingOrder;
                                }
                                if(!empty($mappingLimit)) {
                                    $sql .= ' LIMIT '.$mappingLimit;
                                }
                                $relationData   =   $this->_query($sql);
                                break;
                        }
                        if(!$return){
                            if(!isset($val['as_fields'])) {
                                if(is_array($result)) {
                                    $result[$mappingName] = $relationData;
                                }else{
                                    $result->$mappingName = $relationData;
                                }
                            }
                        }else{
                            return $relationData;
                        }
                    }
            }
        }
        return $result;
    }

    /**
     +----------------------------------------------------------
     * 获取返回数据集的关联记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $resultSet  返回数据
     * @param string $name  关联名称
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function getRelations(&$resultSet,$name='') {
        // 获取记录集的主键列表
        foreach($resultSet as $key=>$val) {
            $val  = $this->getRelation($val,$name);
            $resultSet[$key]    =   $val;
        }
        return $resultSet;
    }

    /**
     +----------------------------------------------------------
     * 操作关联数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $opType  操作方式 ADD SAVE DEL
     * @param mixed $data  数据对象
     * @param string $name 关联名称
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function opRelation($opType,$data='',$name='')
    {
        $result =   false;
        // 把HashMap对象转换成数组
        if(is_instance_of($data,'HashMap')){
            $data = $data->toArray();
        }elseif(is_object($data)){
            $data    =   get_object_vars($data);
        }elseif(empty($data) && !empty($this->data)){
            $data = $this->data;
        }elseif(!is_array($data)){
            // 数据无效返回
            return false;
        }
        if(!empty($this->_link)) {
            // 遍历关联定义
            foreach($this->_link as $key=>$val) {
                    // 操作制定关联类型
                    $mappingName =  $val['mapping_name']?$val['mapping_name']:$key; // 映射名称
                    if(empty($name) || $mappingName == $name) {
                        // 操作制定的关联
                        $mappingType = !empty($val['mapping_type'])?$val['mapping_type']:$val;  //  关联类型
                        $mappingClass  = !empty($val['class_name'])?$val['class_name']:$key;            //  关联类名
                        // 当前数据对象主键值
                        $pk =   $data[$this->getPk()];
                        if(strtoupper($mappingClass)==strtoupper($this->name)) {
                            // 自引用关联 获取父键名
                            $mappingFk   =   !empty($val['parent_key'])? $val['parent_key'] : 'parent_id';
                        }else{
                            $mappingFk   =   !empty($val['foreign_key'])?$val['foreign_key']:strtolower($this->name).'_id';     //  关联外键
                        }
                        if(empty($val['condition'])) {
                            $mappingCondition = "{$mappingFk}='{$pk}'";
                        }
                        // 获取关联model对象
                        $model = D($mappingClass);
                        $mappingData    =   $data[$mappingName];
                        if(is_object($mappingData)){
                            $mappingData =   get_object_vars($mappingData);
                        }
                        if(!empty($mappingData) || $opType == 'DEL') {
                            switch($mappingType) {
                                case HAS_ONE:
                                    switch (strtoupper($opType)){
                                        case 'ADD': // 增加关联数据
                                        $mappingData[$mappingFk]    =   $pk;
                                        $result   =  $model->add($mappingData,false);
                                        break;
                                        case 'SAVE':    // 更新关联数据
                                        $result   =  $model->save($mappingData,$mappingCondition,false);
                                        break;
                                        case 'DEL': // 根据外键删除关联数据
                                        $result   =  $model->delete($mappingCondition,'','',false);
                                        break;
                                    }
                                    break;
                                case BELONGS_TO:
                                    break;
                                case HAS_MANY:
                                    switch (strtoupper($opType)){
                                        case 'ADD'   :  // 增加关联数据
                                        $model->startTrans();
                                        foreach ($mappingData as $val){
                                            $val[$mappingFk]    =   $pk;
                                            $result   =  $model->add($val,false);
                                        }
                                        $model->commit();
                                        break;
                                        case 'SAVE' :   // 更新关联数据
                                        $model->startTrans();
                                        $pk   =  $model->getPk();
                                        foreach ($mappingData as $vo){
                                            $mappingCondition   =  "$pk ={$vo[$pk]}";
                                            $result   =  $model->save($vo,$mappingCondition,false);
                                        }
                                        $model->commit();
                                        break;
                                        case 'DEL' :    // 删除关联数据
                                        $result   =  $model->delete($mappingCondition,'','',false);
                                        break;
                                    }
                                    break;
                                case MANY_TO_MANY:
                                    $mappingRelationFk = $val['relation_foreign_key']?$val['relation_foreign_key']:$model->name.'_id';// 关联
                                    $mappingRelationTable  =  $val['relation_table']?$val['relation_table']:$this->getRelationTableName($model);
                                    foreach ($mappingData as $vo){
                                        $relationId[]   =   $vo[$model->getPk()];
                                    }
                                    $relationId =   implode(',',$relationId);
                                    switch (strtoupper($opType)){
                                        case 'ADD': // 增加关联数据
                                        case 'SAVE':    // 更新关联数据
                                        $this->startTrans();
                                        // 删除关联表数据
                                        $this->db->remove($mappingCondition,$mappingRelationTable);
                                        // 插入关联表数据
                                        $sql  = 'INSERT INTO '.$mappingRelationTable.' ('.$mappingFk.','.$mappingRelationFk.') SELECT a.'.$this->getPk().',b.'.$model->getPk().' FROM '.$this->getTableName().' AS a ,'.$model->getTableName()." AS b where a.".$this->getPk().' ='. $pk.' AND  b.'.$model->getPk().' IN ('.$relationId.") ";
                                        $result =   $model->execute($sql);
                                        if($result) {
                                            // 提交事务
                                            $this->commit();
                                        }else {
                                            // 事务回滚
                                            $this->rollback();
                                        }
                                        break;
                                        case 'DEL': // 根据外键删除中间表关联数据
                                        $result =   $this->db->remove($mappingCondition,$mappingRelationTable);
                                        break;
                                    }
                                    break;
                            }
                    }
                }
            }
        }
        return $result;
    }

    /**
     +----------------------------------------------------------
     * 根据主键删除数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param integer $id 主键值
     * @param boolean $autoLink  是否关联删除
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function deleteById($id,$autoLink=false)
    {
        $pk =   $this->getPk();
        return $this->_delete(array($pk=>$id),$pk."='$id'",0,'',$autoLink);
    }

    /**
     +----------------------------------------------------------
     * 根据多个主键删除数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param integer $ids 多个主键值
     * @param integer $limit 要删除的记录数
     * @param string $order  删除的顺序
     * @param boolean $autoLink  是否关联删除
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function deleteByIds($ids,$limit='',$order='',$autoLink=false)
    {
        if(is_array($ids)) {
            $ids    =    implode(',',$ids);
        }
        return $this->_delete(false,$this->getPk()." IN ($ids)",$limit,$order,$autoLink);
    }

    /**
     +----------------------------------------------------------
     * 根据某个字段删除数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field 字段名称
     * @param mixed $value 字段值
     * @param integer $limit 要删除的记录数
     * @param string $order  删除的顺序
     * @param boolean $autoLink  是否关联删除
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    // 根据某个字段的值删除记录
    public function deleteBy($field,$value,$limit='',$order='',$autoLink=false) {
        $condition[$field]  =  $value;
        return $this->_delete(false,$condition,$limit,$order,$autoLink);
    }

    /**
     +----------------------------------------------------------
     * 根据条件删除表数据
     * 如果成功返回删除记录个数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 删除条件
     * @param integer $limit 要删除的记录数
     * @param string $order  删除的顺序
     * @param boolean $autoLink  是否关联删除
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function delete($data=null,$limit='',$order='',$autoLink=false)
    {
        if(preg_match('/^\d+(\,\d+)*$/',$data)) {
            // 如果是数字 直接使用deleteByIds
            return $this->deleteByIds($data,$limit,$order,$autoLink);
        }
        if(empty($data)) {
            $data    =   $this->data;
        }
        $pk   =  $this->getPk();
        if(is_array($data) && isset($data[$pk]) && !is_array($data[$pk])) {
            $data   =   $this->_facade($data);
            $where  = $pk."=".$data[$pk];
        }else {
            $where  =   $data;
        }
        return $this->_delete($data,$where,$limit,$order,$autoLink);
    }

    /**
     +----------------------------------------------------------
     * 根据条件删除数据
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $condition 删除条件
     * @param boolean $autoLink  是否关联删除
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function deleteAll($condition='',$autoLink=false)
    {
        if(is_instance_of($condition,'HashMap')) {
            $condition    = $condition->toArray();
        }elseif(empty($condition) && !empty($this->dataList)){
            $id = array();
            foreach ($this->dataList as $data){
                $data = (array)$data;
                $id[]    =   $data[$this->getPk()];
            }
            $ids = implode(',',$id);
            $condition = $this->getPk().' IN ('.$ids.')';
        }
        return $this->_delete(false,$condition,0,'',$autoLink);
    }

    /**
     +----------------------------------------------------------
     * 根据主键得到一条记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param int $id 主键的值
     * @param string $fields 字段名，默认为*
     * @param boolean $cache 是否缓存
     * @param mixed $relation 是否关联读取
     * @param boolean $lazy 是否惰性查询
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function getById($id,$fields='*',$cache=false,$relation=false,$lazy=false)
    {
        return $this->_read($this->getPk()."='{$id}'",$fields,false,null,null,null,null,null,$cache,$relation,$lazy);
    }

    /**
     +----------------------------------------------------------
     * 根据主键范围得到多个记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $ids 主键的范围 如 1,3,4,7 array(1,2,3)
     * @param string $fields 字段名，默认为*
     * @param boolean $cache 是否缓存
     * @param mixed $relation 是否关联读取
     * @param boolean $lazy 是否惰性查询
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function getByIds($ids,$fields='*',$order='',$limit='',$cache=false,$relation=false,$lazy=false)
    {
        if(is_array($ids)) {
            $ids    =   implode(',',$ids);
        }
        return $this->_read($this->getPk()." IN ({$ids})",$fields,true,$order,$limit,null,null,$cache,$relation,$lazy);
    }

    /**
     +----------------------------------------------------------
     * 根据某个字段得到一条记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field 字段名称
     * @param mixed $value 字段的值
     * @param string $fields 字段名，默认为*
     * @param boolean $cache 是否缓存查询
     * @param mixed $relation 是否关联查询
     * @param boolean $lazy 是否惰性查询
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function getBy($field,$value,$fields='*',$cache=false,$relation=false,$lazy=false)
    {
        $condition[$field]  =  $value;
        return $this->_read($condition,$fields,false,null,null,null,null,null,$cache,$relation,$lazy);
    }

    /**
     +----------------------------------------------------------
     * 根据某个字段获取全部记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field 字段名称
     * @param mixed $value 字段的值
     * @param string $fields 字段名，默认为*
     * @param boolean $cache 是否缓存查询
     * @param mixed $relation 是否关联查询
     * @param boolean $lazy 是否惰性查询
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function getByAll($field,$value,$fields='*',$cache=false,$relation=false,$lazy=true)
    {
        $condition[$field]  =  $value;
        return $this->_read($condition,$fields,true,null,null,null,null,null,$cache,$relation,$lazy);
    }

    /**
     +----------------------------------------------------------
     * 根据条件得到一条记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $condition 条件
     * @param string $fields 字段名，默认为*
     * @param boolean $cache 是否读取缓存
     * @param mixed $relation 是否关联查询
     * @param boolean $lazy 是否惰性查询
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function find($condition='',$fields='*',$cache=false,$relation=false,$lazy=false)
    {
        if(is_numeric($condition)) {
            // 如果是数字 直接使用getById
            return $this->getById($condition,$fields,$cache,$relation,$lazy);
        }
        return $this->_read($condition,$fields,false,null,null,null,null,null,$cache,$relation,$lazy);
    }

    /**
     +----------------------------------------------------------
     * 根据条件得到一条记录
     * 并且返回关联记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $condition 条件
     * @param string $fields 字段名，默认为*
     * @param boolean $cache 是否读取缓存
     * @param boolean $lazy 是否惰性查询
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function xFind($condition='',$fields='*',$cache=false,$lazy=false)
    {
        return $this->find($condition,$fields,$cache,true,$lazy);
    }

    /**
     +----------------------------------------------------------
     * 查找记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $condition  条件
     * @param string $fields  查询字段
     * @param string $order  排序
     * @param string $limit
     * @param string $group
     * @param string $having
     * @param string $join
     * @param boolean $cache 是否读取缓存
     * @param mixed $relation 是否关联查询
     * @param boolean $lazy 是否惰性查询
     +----------------------------------------------------------
     * @return array|ResultIterator
     +----------------------------------------------------------
     */
    public function findAll($condition='',$fields='*',$order='',$limit='',$group='',$having='',$join='',$cache=false,$relation=false,$lazy=false)
    {
        if(is_string($condition) && preg_match('/^\d+(\,\d+)+$/',$condition)) {
            return $this->getByIds($condition,$fields,$order,$limit,$cache,$relation,$lazy);
        }
        return $this->_read($condition,$fields,true,$order,$limit,$group,$having,$join,$cache,$relation,$lazy);
    }

    /**
     +----------------------------------------------------------
     * 查询记录并返回相应的关联记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $condition  条件
     * @param string $fields  查询字段
     * @param string $order  排序
     * @param string $limit
     * @param string $group
     * @param string $having
     * @param string $join
     * @param boolean $cache 是否读取缓存
     * @param boolean $lazy 是否惰性查询
     +----------------------------------------------------------
     * @return array|ResultIterator
     +----------------------------------------------------------
     */
    public function xFindAll($condition='',$fields='*',$order='',$limit='',$group='',$having='',$join='',$cache=false)
    {
        return $this->findAll($condition,$fields,$order,$limit,$group,$having,$join,$cache,true,false);
    }

    /**
     +----------------------------------------------------------
     * 查找前N个记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param integer $count 记录个数
     * @param mixed $condition  条件
     * @param string $fields  查询字段
     * @param string $order  排序
     * @param string $group
     * @param string $having
     * @param string $join
     * @param boolean $cache 是否读取缓存
     * @param mixed $relation 是否关联查询
     * @param boolean $lazy 是否惰性查询
     +----------------------------------------------------------
     * @return array|ResultIterator
     +----------------------------------------------------------
     */
    public function topN($count,$condition='',$fields='*',$order='',$group='',$having='',$join='',$cache=false,$relation=false,$lazy=false) {
        return $this->findAll($condition,$fields,$order,$count,$group,$having,$join,$cache,$relation,$lazy);
    }

    /**
     +----------------------------------------------------------
     * SQL查询
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $sql  SQL指令
     * @param boolean $cache  是否缓存
     * @param boolean $lazy  是否惰性查询
     +----------------------------------------------------------
     * @return array|ResultIterator
     +----------------------------------------------------------
     */
    public function query($sql,$cache=false,$lazy=false)
    {
        if(empty($sql) && !empty($this->options['sql'])) {
            $sql    =   $this->options['sql'];
        }
        if(is_array($sql)) {
            return $this->patchQuery($sql);
        }
        if(!empty($sql)) {
            if(strpos($sql,'__TABLE__')) {
                $sql    =   str_replace('__TABLE__',$this->getTableName(),$sql);
            }
            return $this->_query($sql,$cache,$lazy);
        }else{
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 执行SQL语句
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $sql  SQL指令
     +----------------------------------------------------------
     * @return false | integer
     +----------------------------------------------------------
     */
    public function execute($sql='')
    {
        if(empty($sql) && !empty($this->options['sql'])) {
            $sql    =   $this->options['sql'];
        }
        if(!empty($sql)) {
            if(strpos($sql,'__TABLE__')) {
                $sql    =   str_replace('__TABLE__',$this->getTableName(),$sql);
            }
            $result =   $this->db->execute($sql);
            return $result;
        }else {
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 批处理执行SQL语句
     * 批处理的指令都认为是execute操作
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $sql  SQL批处理指令
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function patchQuery($sql=array()) {
        if(!is_array($sql)) {
            return false;
        }
        // 自动启动事务支持
        $this->startTrans();
        foreach ($sql as $_sql){
            $result   =  $this->execute($_sql);
            if(false === $result) {
                // 发生错误自动回滚事务
                $this->rollback();
                return false;
            }
        }
        // 提交事务
        $this->commit();
        return true;
    }

    /**
     +----------------------------------------------------------
     * 获取一条记录的某个字段值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  查询条件
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function getField($field,$condition='')
    {
        if(empty($condition) && isset($this->options['where'])) {
            $condition   =  $this->options['where'];
        }
        if($this->viewModel) {
            $condition   =   $this->checkCondition($condition);
            $field         =   $this->checkFields($field);
        }
        $rs = $this->db->find($condition,$this->getTableName(),$field);
        return $this->getCol($rs,$field);
    }

    /**
     +----------------------------------------------------------
     * 获取数据集的个别字段值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field 字段名称
     * @param mixed $condition  条件
     * @param string $spea  多字段分割符号
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function getFields($field,$condition='',$sepa=' ')
    {
        if(empty($condition) && isset($this->options['where'])) {
            $condition   =  $this->options['where'];
        }
        if($this->viewModel) {
            $condition   =   $this->checkCondition($condition);
            $field         =   $this->checkFields($field);
        }
        $rs = $this->db->find($condition,$this->getTableName(),$field);
        return $this->getCols($rs,$field,$sepa);
    }

    /**
     +----------------------------------------------------------
     * 设置记录的某个字段值
     * 支持使用数据库字段和方法
     * 例如 setField('score','(score+1)','id=5');
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string|array $field  字段名
     * @param string|array $value  字段值
     * @param mixed $condition  条件
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function setField($field,$value,$condition='',$asString=true) {
        if(empty($condition) && isset($this->options['where'])) {
            $condition   =  $this->options['where'];
        }
        if($this->viewModel) {
            $condition   =   $this->checkCondition($condition);
            $field         =   $this->checkFields($field);
        }
        return $this->db->setField($field,$value,$this->getTableName(),$condition,$asString);
    }

    /**
     +----------------------------------------------------------
     * 字段值增长
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  条件
     * @param integer $step  增长值
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function setInc($field,$condition='',$step=1) {
        if(empty($condition) && isset($this->options['where'])) {
            $condition   =  $this->options['where'];
        }
        if($this->viewModel) {
            $condition   =   $this->checkCondition($condition);
            $field         =   $this->checkFields($field);
        }
        return $this->db->setInc($field,$this->getTableName(),$condition,$step);
    }

    /**
     +----------------------------------------------------------
     * 字段值减少
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  条件
     * @param integer $step  减少值
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function setDec($field,$condition='',$step=1) {
        if(empty($condition) && isset($this->options['where'])) {
            $condition   =  $this->options['where'];
        }
        if($this->viewModel) {
            $condition   =   $this->checkCondition($condition);
            $field         =   $this->checkFields($field);
        }
        return $this->db->setDec($field,$this->getTableName(),$condition,$step);
    }

    /**
     +----------------------------------------------------------
     * 获取查询结果中的某个字段值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $rs  查询结果
     * @param string $field  字段名
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function getCol($rs,$field)
    {
        if(!empty($rs) && count($rs)>0) {
            $result    =   $rs[0];
            $field      =   is_array($result)?$result[$field]:$result->$field;
            return $field;
        }else {
            return null;
        }
    }

    /**
     +----------------------------------------------------------
     * 获取查询结果中的第一个字段值
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @param array $rs  查询结果
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    protected function getFirstCol($rs)
    {
        if(!empty($rs) && count($rs)>0) {
            $result    =   $rs[0];
            if(is_object($result)) {
                $result   =  get_object_vars($result);
            }
            return  reset($result);
        }else {
            return null;
        }
    }

    /**
     +----------------------------------------------------------
     * 获取查询结果中的多个字段值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $rs  查询结果
     * @param string $field  字段名用逗号分割多个
     * @param string $spea  多字段分割符号
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function getCols($rs,$field,$sepa=' ') {
        if(!empty($rs)) {
            $field  =   explode(',',$field);
            $cols    =   array();
            $length  = count($field);
            foreach ($rs as $result){
                if(is_object($result)) $result  =   get_object_vars($result);
                if($length>1) {
                    $cols[$result[$field[0]]]   =   '';
                    for($i=1; $i<$length; $i++) {
                        if($i+1<$length){
                            $cols[$result[$field[0]]] .= $result[$field[$i]].$sepa;
                        }else{
                            $cols[$result[$field[0]]] .= $result[$field[$i]];
                        }
                    }
                }else{
                    $cols[]  =   $result[$field[0]];
                }
            }
            return $cols;
        }
        return null;
    }

    /**
     +----------------------------------------------------------
     * 统计满足条件的记录个数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $condition  条件
     * @param string $field  要统计的字段 默认为*
     +----------------------------------------------------------
     * @return integer
     +----------------------------------------------------------
     */
    public function count($condition='',$field='*')
    {
        $fields = 'count('.$field.') as tpcount';
        if($this->viewModel) {
            $condition  =   $this->checkCondition($condition);
        }
        $rs = $this->db->find($condition,$this->getTableName(),$fields);
        return $this->getFirstCol($rs)|0;
    }

    /**
     +----------------------------------------------------------
     * 取得某个字段的最大值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  条件
     +----------------------------------------------------------
     * @return float
     +----------------------------------------------------------
     */
    public function max($field,$condition='')
    {
        $fields = 'MAX('.$field.') as tpmax';
        if($this->viewModel) {
            $condition  =   $this->checkCondition($condition);
        }
        $rs = $this->db->find($condition,$this->getTableName(),$fields);
        if($rs) {
            return floatval($this->getFirstCol($rs));
        }else{
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 取得某个字段的最小值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  条件
     +----------------------------------------------------------
     * @return float
     +----------------------------------------------------------
     */
    public function min($field,$condition='')
    {
        $fields = 'MIN('.$field.') as tpmin';
        if($this->viewModel) {
            $condition  =   $this->checkCondition($condition);
        }
        $rs = $this->db->find($condition,$this->getTableName(),$fields);
        if($rs) {
            return floatval($this->getFirstCol($rs));
        }else{
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 统计某个字段的总和
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  条件
     +----------------------------------------------------------
     * @return float
     +----------------------------------------------------------
     */
    public function sum($field,$condition='')
    {
        $fields = 'SUM('.$field.') as tpsum';
        if($this->viewModel) {
            $condition  =   $this->checkCondition($condition);
        }
        $rs = $this->db->find($condition,$this->getTableName(),$fields);
        if($rs) {
            return floatval($this->getFirstCol($rs));
        }else{
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 统计某个字段的平均值
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field  字段名
     * @param mixed $condition  条件
     +----------------------------------------------------------
     * @return float
     +----------------------------------------------------------
     */
    public function avg($field,$condition='')
    {
        $fields = 'AVG('.$field.') as tpavg';
        if($this->viewModel) {
            $condition  =   $this->checkCondition($condition);
        }
        $rs = $this->db->find($condition,$this->getTableName(),$fields);
        if($rs) {
            return floatval($this->getFirstCol($rs));
        }else{
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 查询符合条件的第N条记录
     * 0 表示第一条记录 -1 表示最后一条记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param integer $position 记录位置
     * @param mixed $condition 条件
     * @param string $order 排序
     * @param string $fields 字段名，默认为*
     * @param boolean $relation 是否读取关联
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function getN($position=0,$condition='',$order='',$fields='*',$relation=false)
    {
        $table      =   $this->getTableName();
        if(!empty($this->options)) {
            // 已经有定义的查询表达式
            $condition  =   $this->options['where']?            $this->options['where']:    $condition;
            $table          =   $this->options['table']?            $this->options['table']:    $this->getTableName();
            $fields     =   $this->options['filed']?            $this->options['field']:    $fields;
            $limit          =   $this->options['limit']?            $this->options['limit']:        $limit;
            $order      =   $this->options['order']?            $this->options['order']:    $order;
            $relation       =   isset($this->options['link'])?      $this->options['link']:     $relation;
            $this->options  =   array();
        }
        if($this->viewModel) {
            $condition  =   $this->checkCondition($condition);
            $field  =   $this->checkFields($field);
        }
        if($position>=0) {
            $rs = $this->db->find($condition,$table,$fields,$order,$position.',1');
            return $this->rsToVo($rs,false,0,$relation);
        }else{
            $rs = $this->db->find($condition,$this->getTableName(),$fields,$order);
            return $this->rsToVo($rs,false,$position,$relation);
        }
    }

    /**
     +----------------------------------------------------------
     * 获取满足条件的第一条记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $condition 条件
     * @param string $fields 字段名，默认为*
     * @param string $order 排序
     * @param boolean $relation 是否读取关联
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function first($condition='',$order='',$fields='*',$relation=false) {
        return $this->getN(0,$condition,$order,$fields,$relation);
    }

    /**
     +----------------------------------------------------------
     * 获取满足条件的第后一条记录
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $condition 条件
     * @param string $fields 字段名，默认为*
     * @param string $order 排序
     * @param boolean $relation 是否读取关联
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function last($condition='',$order='',$fields='*',$relation=false) {
        return $this->getN(-1,$condition,$order,$fields,$relation);
    }

    /**
     +----------------------------------------------------------
     * 记录乐观锁
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 数据对象
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    protected function cacheLockVersion($data) {
        if($this->optimLock) {
            if(is_object($data))    $data   =   get_object_vars($data);
            if(isset($data[$this->optimLock]) && isset($data[$this->getPk()])) {
                // 只有当存在乐观锁字段和主键有值的时候才记录乐观锁
                $_SESSION[$this->name.'_'.$data[$this->getPk()].'_lock_version']    =   $data[$this->optimLock];
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 把查询结果转换为数据（集）对象
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $resultSet 查询结果记录集
     * @param Boolean $returnList 是否返回记录集
     * @param Integer $position 定位的记录集位置
     * @param boolean $relation 是否获取关联
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function rsToVo($resultSet,$returnList=false,$position=0,$relation='')
    {
        if($resultSet ) {
            if(!$returnList) {
                if(is_instance_of($resultSet,'ResultIterator')) {
                    // 如果是延时查询返回的是ResultIterator对象
                    $resultSet  =   $resultSet->getIterator();
                }
                // 返回数据对象
                if($position<0) {
                    // 逆序查找
                    $position = count($resultSet)-abs($position);
                }
                if(count($resultSet)<= $position) {
                    // 记录集位置不存在
                    $this->error = L('_SELECT_NOT_EXIST_');
                    return false;
                }
                $result  =  $resultSet[$position];
                // 取出数据对象的时候记录乐观锁
                $this->cacheLockVersion($result);
                // 获取Blob数据
                $this->getBlobFields($result);
                // 判断数据过滤
                $this->filterFields($result);
                // 获取关联记录
                if( $this->autoReadRelations || $relation ) {
                    $result  =  $this->getRelation($result,$relation);
                }
                // 对数据对象自动编码转换
                $result  =   auto_charset($result,C('DB_CHARSET'),C('TEMPLATE_CHARSET'));
                // 记录当前数据对象
                $this->data  =   (array)$result;
                return $result;
            }else{
                if(is_instance_of($resultSet,'ResultIterator')) {
                    // 如果是延时查询返回的是ResultIterator对象
                    return $resultSet;
                }
                // 获取Blob数据
                $this->getListBlobFields($resultSet);
                // 判断数据过滤
                $this->filterListFields($resultSet);
                // 返回数据集对象
                if( $this->autoReadRelations || $relation ) {
                    // 获取数据集的关联记录
                    $this->getRelations($resultSet,$relation);
                }
                // 对数据集对象自动编码转换
                $resultSet  =   auto_charset($resultSet,C('DB_CHARSET'),C('TEMPLATE_CHARSET'));
                // 记录数据列表
                $this->dataList =   $resultSet;
                return $resultSet;
            }
        }else {
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 创建数据对象 但不保存到数据库
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $data 创建数据
     * @param boolean $batch 批量创建
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function create($data='',$batch=false)
    {
        if(true === $batch) {
            // 批量创建
            return $this->createAll($data);
        }
        // 如果没有传值默认取POST数据
        if(empty($data)) {
            $data    =   $_POST;
        }
        elseif(is_instance_of($data,'HashMap')){
            $data = $data->toArray();
        }
        elseif(is_instance_of($data,'Model')){
            $data = $data->getIterator();
        }
        elseif(is_object($data)){
            $data   =   get_object_vars($data);
        }
        elseif(!is_array($data)){
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }
        $vo =   $this->_createData($data);
        return $vo;
    }

    /**
     +----------------------------------------------------------
     * 创建数据列表对象 但不保存到数据库
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $dataList 数据列表
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function createAll($dataList='')
    {
        // 如果没有传值默认取POST数据
        if(empty($dataList)) {
            $dataList    =   $_POST;
        }
        elseif(!is_array($dataList)){
            $this->error = L('_DATA_TYPE_INVALID_');
            return false;
        }
        foreach ($dataList as $data){
            $vo =   $this->_createData($data);
            if(false === $vo) {
                return false;
            }else{
                $this->dataList[] = $vo;
            }
        }
        return $this->dataList;
    }

    /**
     +----------------------------------------------------------
     * 创建数据对象 但不保存到数据库
     +----------------------------------------------------------
     * @access private
     +----------------------------------------------------------
     * @param mixed $data 创建数据
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    private function _createData($data) {
        // 2008-2-11 自动判断新增和编辑数据
        $vo = array();
        $type    =   'add';
        if(!$this->composite && isset($data[$this->getPk()])) {
            // 获取数据库的对象
            $value   = $data[$this->getPk()];
            $rs     = $this->db->find($this->getPk()."='{$value}'",$this->getTableName());
            if($rs && count($rs)>0) {
                $type    =   'edit';
                /* 编辑更新的时候不再获取数据 只是获取表单数据来更新
                $vo = $rs[0];
                if(DATA_TYPE_OBJ == C('DATA_RESULT_TYPE')) {
                    // 对象模式
                    $vo =   get_object_vars($vo);
                }*/
            }
        }
        // 对提交数据执行自动验证
        if(!$this->_before_validation($data,$type)) {
            return false;
        }
        if(!$this->autoValidation($data,$type)) {
            return false;
        }
        if(!$this->_after_validation($data,$type)) {
            return false;
        }

        if($this->composite) {
            // 复合对象直接赋值
            foreach ($data as $key=>$val){
                $vo[$key]   =   MAGIC_QUOTES_GPC?    stripslashes($val)  :  $val;
            }
        }else{
            // 检查字段映射
            if(isset($this->_map)) {
                foreach ($this->_map as $key=>$val){
                    if(isset($data[$key])) {
                        $data[$val] =   $data[$key];
                        unset($data[$key]);
                    }
                }
            }
            // 验证完成生成数据对象
            foreach ( $this->fields as $key=>$name){
                if(substr($key,0,1)=='_') continue;
                $val = isset($data[$name])?$data[$name]:null;
                //保证赋值有效
                if(!is_null($val) ){
                    // 首先保证表单赋值
                    $vo[$name] = MAGIC_QUOTES_GPC?   stripslashes($val)  :  $val;
                }elseif(    (strtolower($type) == "add" && in_array($name,$this->autoCreateTimestamps,true)) ||
                (strtolower($type) == "edit" && in_array($name,$this->autoUpdateTimestamps,true)) ){
                    // 自动保存时间戳
                    if(!empty($this->autoTimeFormat)) {
                        // 用指定日期格式记录时间戳
                        $vo[$name] =    date($this->autoTimeFormat);
                    }else{
                        // 默认记录时间戳
                        $vo[$name] = time();
                    }
                }
            }
        }

        // 执行自动处理
        $this->_before_operation($vo);
        $this->autoOperation($vo,$type);
        $this->_after_operation($vo);

        // 赋值当前数据对象
        $this->data =   $vo;

        if(DATA_TYPE_OBJ == C('DATA_RESULT_TYPE')) {
            // 对象模式 强制转换为stdClass对象实例
            $vo =   (object) $vo;
        }
        return $vo;
    }

    /**
     +----------------------------------------------------------
     * 自动表单处理
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $data 创建数据
     * @param string $type 创建类型
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    private function autoOperation(&$data,$type) {
        // 自动填充
        if(!empty($this->_auto)) {
            foreach ($this->_auto as $auto){
                // 填充因子定义格式
                // array('field','填充内容','填充条件','附加规则',[额外参数])
                if($this->composite || in_array($auto[0],$this->fields,true)) {
                    if(empty($auto[2])) $auto[2] = 'ADD';// 默认为新增的时候自动填充
                    else $auto[2]   =   strtoupper($auto[2]);
                    if( (strtolower($type) == "add"  && $auto[2] == 'ADD') ||   (strtolower($type) == "edit"  && $auto[2] == 'UPDATE') || $auto[2] == 'ALL')
                    {
                        switch($auto[3]) {
                            case 'function':    //  使用函数进行填充 字段的值作为参数
                            case 'callback': // 使用回调方法
                                if(isset($auto[4])) {
                                    $args = $auto[4];
                                }else{
                                    $args = array();
                                }
                                array_unshift($args,$data[$auto[0]]);
                                if('function'==$auto[3]) {
                                    $data[$auto[0]]  = call_user_func_array($auto[1], $args);
                                }else{
                                    $data[$auto[0]]  =  call_user_func_array(array(&$this,$auto[1]), $args);
                                }
                                break;
                            case 'field':    // 用其它字段的值进行填充
                                $data[$auto[0]] = $data[$auto[1]];
                                break;
                            case 'string':
                            default: // 默认作为字符串填充
                                $data[$auto[0]] = $auto[1];
                        }
                        if(false === $data[$auto[0]] ) {
                            unset($data[$auto[0]]);
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     +----------------------------------------------------------
     * 自动表单验证
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $data 创建数据
     * @param string $type 创建类型
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    private function autoValidation($data,$type) {
        // 属性验证
        if(!empty($this->_validate)) {
            // 如果设置了数据自动验证
            // 则进行数据验证
            import("ORG.Text.Validation");
            // 是否多字段验证
            $multiValidate  =   C('MULTI_FIELD_VALIDATE');
            // 重置验证错误信息
            $this->validateError    =   array();
            foreach($this->_validate as $key=>$val) {
                // 验证因子定义格式
                // array(field,rule,message,condition,append,when,params)
                // field rule message 必须
                // condition 验证条件：0 存在字段就验证 1 必须验证 2 值不为空的时候验证 默认为0
                // append 附加规则 :function confirm regex equal in unique 默认为regex
                // when 验证时间: all add edit 默认为all
                // params 额外参数 使用数组方式定义 例如 array('var1','var2')
                // 判断是否需要执行验证
                if(empty($val[5]) || $val[5]=='all' || strtolower($val[5])==strtolower($type) ) {
                    if(0==strpos($val[2],'{%') && strpos($val[2],'}')) {
                        // 支持提示信息的多语言 使用 {%语言定义} 方式
                        $val[2]  =  L(substr($val[2],2,-1));
                    }
                    // 判断验证条件
                    switch($val[3]) {
                        case MUST_TO_VALIDATE:   // 必须验证 不管表单是否有设置该字段
                            if(!$this->_validationField($data,$val)){
                                if($multiValidate) {
                                    $this->validateError[$val[0]]   =   $val[2];
                                }else{
                                    $this->error    =   $val[2];
                                    return false;
                                }
                            }
                            break;
                        case VALUE_TO_VAILIDATE:    // 值不为空的时候才验证
                            if('' != trim($data[$val[0]])){
                                if(!$this->_validationField($data,$val)){
                                    if($multiValidate) {
                                        $this->validateError[$val[0]]   =   $val[2];
                                    }else{
                                        $this->error    =   $val[2];
                                        return false;
                                    }
                                }
                            }
                            break;
                        default:    // 默认表单存在该字段就验证
                            if(isset($data[$val[0]])){
                                if(!$this->_validationField($data,$val)){
                                    if($multiValidate) {
                                        $this->validateError[$val[0]]   =   $val[2];
                                    }else{
                                        $this->error    =   $val[2];
                                        return false;
                                    }
                                }
                            }
                    }
                }
            }
        }
        if(!empty($this->validateError)) {
            return false;
        }else{
            // TODO 数据类型验证
            //  判断数据类型是否符合
            return true;
        }
    }

    /**
     +----------------------------------------------------------
     * 返回验证的错误信息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    protected function getValidateError() {
        if(!empty($this->validateError)) {
            return $this->validateError;
        }else{
            return $this->error;
        }
    }

    /**
     +----------------------------------------------------------
     * 根据验证因子验证字段
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $data 创建数据
     * @param string $val 验证规则
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    private function _validationField($data,$val) {
        // 检查附加规则
        switch($val[4]) {
            case 'function':// 使用函数进行验证
            case 'callback':// 调用方法进行验证
                if(isset($val[6])) {
                    $args = $val[6];
                }else{
                    $args = array();
                }
                array_unshift($args,$data[$val[0]]);
                if('function'==$val[4]) {
                    return call_user_func_array($val[1], $args);
                }else{
                    return call_user_func_array(array(&$this, $val[1]), $args);
                }
            case 'confirm': // 验证两个字段是否相同
                if($data[$val[0]] != $data[$val[1]] ) {
                    return false;
                }
                break;
            case 'in': // 验证是否在某个数组范围之内
                if(!in_array($data[$val[0]] ,$val[1]) ) {
                    return false;
                }
                break;
            case 'equal': // 验证是否等于某个值
                if($data[$val[0]] != $val[1]) {
                    return false;
                }
                break;
            case 'unique': // 验证某个值是否唯一
                if(is_string($val[0]) && strpos($val[0],',')) {
                    $val[0]  =  explode(',',$val[0]);
                }
                if(is_array($val[0])) {
                    // 支持多个字段验证
                    $map = array();
                    foreach ($val[0] as $field){
                        $map[$field]   =  $data[$field];
                    }
                    if($this->find($map)) {
                        return false;
                    }
                }else{
                    if($this->getBy($val[0],$data[$val[0]])) {
                        return false;
                    }
                }
                break;
            case 'regex':
                default:    // 默认使用正则验证 可以使用验证类中定义的验证名称
                if( !Validation::check($data[$val[0]],$val[1])) {
                    return false;
                }
        }
        return true;
    }

    // 表单验证回调方法
    protected function _before_validation(&$data,$type) {return true;}
    protected function _after_validation(&$data,$type) {return true;}

    // 表单处理回调方法
    protected function _before_operation(&$data) {}
    protected function _after_operation(&$data) {}

    /**
     +----------------------------------------------------------
     * 得到当前的数据对象名称
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getModelName()
    {
        if(empty($this->name)) {
            $prefix =   C('MODEL_CLASS_PREFIX');
            $suffix =   C('MODEL_CLASS_SUFFIX');
            if(strlen($suffix)>0) {
                $this->name =   substr(substr(get_class($this),strlen($prefix)),0,-strlen($suffix));
            }else{
                $this->name =   substr(get_class($this),strlen($prefix));
            }
        }
        return $this->name;
    }

    /**
     +----------------------------------------------------------
     * 得到完整的数据表名
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getTableName()
    {
        if(empty($this->trueTableName)) {
            if($this->viewModel) {
                $tableName = '';
                foreach ($this->viewFields as $key=>$view){
                    $Model  =   D($key);
                    if($Model) {
                        // 存在模型 获取模型定义的数据表名称
                        $tableName .= $Model->getTableName();
                    }else{
                        // 直接把key作为表名来对待
                        $viewTable  = !empty($this->tablePrefix) ? $this->tablePrefix : '';
                        $viewTable .= $key;
                        $viewTable .= !empty($this->tableSuffix) ? $this->tableSuffix : '';
                        $tableName .= strtolower($viewTable);
                    }
                    if(isset($view['_as'])) {
                        $tableName .= ' '.$view['_as'];
                    }else{
                        $tableName .= ' '.$key;
                    }
                    if(isset($view['_on'])) {
                        // 支持ON 条件定义
                        $tableName .= ' ON '.$view['_on'];
                    }
                    if(!empty($view['_type'])) {
                        // 指定JOIN类型 例如 RIGHT INNER LEFT 下一个表有效
                        $type = $view['_type'];
                    }else{
                        $type = '';
                    }
                    $tableName   .= ' '.strtoupper($type).' JOIN ';
                    $len  =  strlen($type.'_JOIN ');
                }
                $tableName = substr($tableName,0,-$len);
                $this->trueTableName    =   $tableName;
            }else{
                $tableName  = !empty($this->tablePrefix) ? $this->tablePrefix : '';
                if(!empty($this->tableName)) {
                    $tableName .= $this->tableName;
                }elseif(C('TABLE_NAME_IDENTIFY')){
                    // 智能识别表名
                    $tableName .= $this->parseName($this->name);
                }else{
                    $tableName .= $this->name;
                }
                $tableName .= !empty($this->tableSuffix) ? $this->tableSuffix : '';
                if(!empty($this->dbName)) {
                    $tableName    =  $this->dbName.'.'.$tableName;
                }
                $this->trueTableName    =   strtolower($tableName);
            }
        }
        return $this->trueTableName;
    }

    /**
     +----------------------------------------------------------
     * 得到关联的数据表名
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $relation 关联对象
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getRelationTableName($relation)
    {
        $relationTable  = !empty($this->tablePrefix) ? $this->tablePrefix : '';
        $relationTable .= $this->tableName?$this->tableName:$this->name;
        $relationTable .= '_'.$relation->getModelName();
        $relationTable .= !empty($this->tableSuffix) ? $this->tableSuffix : '';
        return strtolower($relationTable);
    }

    /**
     +----------------------------------------------------------
     * 开启惰性查询
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function startLazy()
    {
        $this->lazyQuery = true;
        return ;
    }

    /**
     +----------------------------------------------------------
     * 关闭惰性查询
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function stopLazy()
    {
        $this->lazyQuery = false;
        return ;
    }

    /**
     +----------------------------------------------------------
     * 开启锁机制
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function startLock()
    {
        $this->pessimisticLock = true;
        return ;
    }

    /**
     +----------------------------------------------------------
     * 关闭锁机制
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public function stopLock()
    {
        $this->pessimisticLock = false;
        return ;
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
    public function startTrans()
    {
        $this->commit();
        $this->db->startTrans();
        return ;
    }

    /**
     +----------------------------------------------------------
     * 提交事务
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     +----------------------------------------------------------
     * 事务回滚
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function rollback()
    {
        return $this->db->rollback();
    }

    /**
     +----------------------------------------------------------
     * 得到主键名称
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getPk() {
        return isset($this->fields['_pk'])?$this->fields['_pk']:'id';
    }

    /**
     +----------------------------------------------------------
     * 返回当前错误信息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getError(){
        return $this->error;
    }

    /**
     +----------------------------------------------------------
     * 返回数据库字段信息
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getDbFields(){
        return $this->fields;
    }

    /**
     +----------------------------------------------------------
     * 返回最后插入的ID
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getLastInsID() {
        return $this->db->lastInsID;
    }

    /**
     +----------------------------------------------------------
     * 返回操作影响的记录数
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getAffectRows() {
        return $this->db->numRows;
    }

    /**
     +----------------------------------------------------------
     * 返回最后执行的sql语句
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return string
     +----------------------------------------------------------
     */
    public function getLastSql() {
        return $this->db->getLastSql();
    }

    /**
     +----------------------------------------------------------
     * 增加数据库连接
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $config 数据库连接信息
     * 支持批量添加 例如 array(1=>$config1,2=>$config2)
     * @param mixed $linkNum  创建的连接序号
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function addConnect($config,$linkNum=NULL) {
        if(isset($this->_db[$linkNum])) {
            return false;
        }
        if(NULL === $linkNum && is_array($config)) {
            // 支持批量增加数据库连接
            foreach ($config as $key=>$val){
                $this->_db[$key]            =    Db::getInstance($val);
            }
            return true;
        }
        // 创建一个新的实例
        $this->_db[$linkNum]            =    Db::getInstance($config);
        return true;
    }

    /**
     +----------------------------------------------------------
     * 删除数据库连接
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param integer $linkNum  创建的连接序号
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function delConnect($linkNum) {
        if(isset($this->_db[$linkNum])) {
            $this->_db[$linkNum]->close();
            unset($this->_db[$linkNum]);
            return true;
        }
        return false;
    }

    /**
     +----------------------------------------------------------
     * 关闭数据库连接
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param integer $linkNum  创建的连接序号
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function closeConnect($linkNum) {
        if(isset($this->_db[$linkNum])) {
            $this->_db[$linkNum]->close();
            return true;
        }
        return false;
    }

    /**
     +----------------------------------------------------------
     * 切换数据库连接
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param integer $linkNum  创建的连接序号
     +----------------------------------------------------------
     * @return boolean
     +----------------------------------------------------------
     */
    public function switchConnect($linkNum) {
        if(isset($this->_db[$linkNum])) {
            // 在不同实例直接切换
            $this->db   =   $this->_db[$linkNum];
            return true;
        }else{
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 查询SQL组装 where
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $where
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function where($where) {
        $this->options['where'] =   $where;
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 查询SQL组装 order
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $order
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function order($order) {
        $this->options['order'] =   $order;
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 查询SQL组装 table
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $table
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function table($table) {
        $this->options['table'] =   $table;
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 查询SQL组装 group
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $group
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function group($group) {
        $this->options['group'] =   $group;
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 查询SQL组装 field
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function field($field) {
        $this->options['field'] =   $field;
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 查询SQL组装 limit
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $limit
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function limit($limit) {
        $this->options['limit'] =   $limit;
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 查询SQL组装 join
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $join
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function join($join) {
        if(is_array($join)) {
            $this->options['join'] =  $join;
        }else{
            $this->options['join'][]  =   $join;
        }
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 查询SQL组装 having
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $having
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function having($having) {
        $this->options['having']    =   $having;
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 查询SQL组装 惰性
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param boolean $lazy 惰性查询
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function lazy($lazy) {
        $this->options['lazy']  =   $lazy;
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 查询SQL组装lock
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param boolean $lock 是否锁定
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function lock($lock) {
        $this->options['lock']  =   $lock;
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 查询SQL组装lock
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param boolean $lock 是否锁定
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function cache($cache) {
        $this->options['cache'] =   $cache;
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 查询SQL组装
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $sql sql语句
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function sql($sql) {
        $this->options['sql']   =   $sql;
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 数据SQL组装
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $data 要插入或者保存的数据
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function data($data) {
        $this->options['data']  =   $data;
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 进行关联查询
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param boolean $name 关联名称
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function relation($name) {
        $this->options['link']  =   $name;
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 是否返回执行的SQL
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param boolean $fetch
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function fetchSql($fetch=true) {
        if(in_array(strtolower($fetch),array('find','findall','save','add','delete'))) {
            $this->options['fetch'] =   true;
            return $this->{$fetch}();
        }else{
            $this->options['fetch'] =   $fetch;
        }
        return $this;
    }

    /**
     +----------------------------------------------------------
     * 关联数据获取 仅用于查询后
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $name 关联名称
     +----------------------------------------------------------
     * @return Model
     +----------------------------------------------------------
     */
    public function relationGet($name) {
        if(empty($this->data)) {
            return false;
        }
        $relation   = $this->getRelation($this->data,$name,true);
        return $relation;
    }

    /**
     +----------------------------------------------------------
     * 对查询结果集进行排序
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param string $field 排序的字段名
     * @param array $sortby 排序类型 asc arsort natcaseror
     * @param array $list 查询结果
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function sortBy($field, $sortby='asc', $list='' ) {
       if(empty($list) && !empty($this->dataList)) {
           $list     =   $this->dataList;
       }
       if(is_array($list)){
           $refer = $resultSet = array();
           foreach ($list as $i => $data) {
                if(is_object($data)) {
                    $data    =   get_object_vars($data);
                }
               $refer[$i] = &$data[$field];
           }
           switch ($sortby) {
               case 'asc': // 正向排序
                    asort($refer);
                    break;
               case 'desc':// 逆向排序
                    arsort($refer);
                    break;
               case 'nat': // 自然排序
                    natcasesort($refer);
                    break;
           }
           foreach ( $refer as $key=> $val) {
               $resultSet[] = &$list[$key];
           }
           return $resultSet;
       }
       return false;
    }

    /**
     +----------------------------------------------------------
     * 把返回的数据集转换成Tree
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param array $list 要转换的数据集
     * @param string $id 主键标记字段
     * @param string $pid parent标记字段
     * @param string $child child标记名称
     * @param integer $root 根节点id
     +----------------------------------------------------------
     * @return array
     +----------------------------------------------------------
     */
    public function toTree($list=null, $pk='id',$pid = 'pid',$child = '_child',$root=0)
    {
        if(null === $list) {
            // 默认直接取查询返回的结果集合
            $list   =   &$this->dataList;
        }
        // 创建Tree
        $tree = array();
        if(is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $_key = is_object($data)?$data->$pk:$data[$pk];
                $refer[$_key] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = is_object($data)?$data->$pid:$data[$pid];
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                }else{
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }
};
?>