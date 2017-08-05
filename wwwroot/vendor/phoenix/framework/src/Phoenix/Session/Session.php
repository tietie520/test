<?php
namespace Phoenix\Session;

/**
 * UI.Session 所有Session访问的入口
 *
 */
interface Session {
    /**
     * 赋值完成后提交session
     */
    public function set();

    /**
     * 更新session中值 参数必须为键值对
     */
    public function update($name, $aryUp = array());

    /**
     * 注销凭据
     * @param $key
     */
    public function destory($key);
}