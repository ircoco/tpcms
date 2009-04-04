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

class CommonModel extends Model {
	   /**
     +----------------------------------------------------------
     * 根据条件禁用表数据
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $condition 删除条件
     * @param string $table  数据表名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function forbid($condition,$table='')
    {
        $table = empty($table)?$this->getTableName():$table;
        if(FALSE === $this->db->execute('update '.$table.' set status=0 where status=1 and ('.$condition.')')){
            $this->error =  L('_OPERATION_WRONG_');
            return false;
        }else {
            return True;
        }
    }

    /**
     +----------------------------------------------------------
     * 根据条件禁用表数据
     * 
     +----------------------------------------------------------
     * @access public 
     +----------------------------------------------------------
     * @param mixed $condition 删除条件
     * @param string $table  数据表名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     * @throws FcsException
     +----------------------------------------------------------
     */
    function resume($condition,$table='')
    {
        $table = empty($table)?$this->getTableName():$table;
        if(FALSE === $this->db->execute('update '.$table.' set status=1 where status=0 and ('.$condition.')')){
            $this->error =  L('_OPERATION_WRONG_');
            return false;
        }else {
            return True;
        }
    }
}
?>