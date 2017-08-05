<?php

namespace Phoenix\Cache;

interface Cache {
    /**
     * 设置超时时间，如果需要应用超时设置，get,set方法前都需要使用这个函数
     * 链式操作
     * 如果确定只使用memcache，可以只在set时设置时间
     * 不设置默认不超时
     * @param int $sec
     * @return $this
     */
    public function expires($sec = 0);

    public function set($key, $var);

    public function get($key);

    public function exists($key);

    public function delete($key);

    public function gc();

    /**
     * 指定缓存使用模式：file memcache
     * 如果有大量的缓存碎片不希望占用宝贵的内存空间(与memcache分开缓存)
     * 可在此方法上指定某个缓存类型，如 file(磁盘缓存)
     * @param null $mode
     * @return $this
     */
    public function mode($mode = null);
}