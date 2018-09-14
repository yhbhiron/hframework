<?php  !defined('IN_WEB') && exit('Access Deny!');

/**
 * 用于兼容php7及以上版本的session操作
 * @author Administrator
 *
 */
class SessionHandlerExt  implements SessionHandlerInterface{
    
    public  function open($save_path, $session_name){
         session::open($save_path, $session_name);
         return true;
    }
    
    
    /**
     * 写入session
     * @param string $name 键名
     * @param mixed $val 值
     * @param number $time 过期时间
     * @param boolean $delOnGet 是否读取后删除
     * @return boolean|mixed
     */
    public static function set($name,$val,$time=120,$delOnGet=false){
        return session::set($name, $val,$time,$delOnGet);
    }
    
    
    
    /**
     * 读取session
     * @param $name
     */
    public static function get($name){
        return session::get($name);        
    }
    
    
    /**
     * 删除session
     * @param $name
     */
    public static function delete($name){
        return session::delete($name);
    }
    
    
    /**
     * 读取session
     * @param $id
     */
    public  function read($id){
         session::read($id);
         return '';
    }
    
    
    /**
     * 写入session
     * @param $id
     * @param $data
     */
    public  function write($id,$data){
        session::write($id,$data);
        return true;
    }
    
    
    public  function destroy($id)
    {
        return session::destroy($id);
    }
    
    public  function gc($maxlifetime=0){
        return session::gc($maxlifetime);
    }
    
    
    
    public  function close()
    {
        return session::close();
    }
    
    
    
}



