<?php
/**
 * Link         :   http://www.phpcorner.net
 * User         :   qingbing<780042175@qq.com>
 * Date         :   2018-12-12
 * Version      :   1.0
 */

namespace Components;

use Abstracts\Store;

defined("CACHE_PATH") or define("CACHE_PATH", dirname(realpath(".")) . "/runtime");

class FileCache extends Store
{
    /* @var string 命名空间 */
    public $namespace = 'pf';
    /* @var string key的前缀 */
    public $prefix = 'pf_';
    /* @var string 缓存文件后缀 */
    public $suffix = 'bat';

    private $_cachePath;
    private $_namespacePath;

    /**
     * 获取存储目录
     * @return mixed
     */
    public function getCachePath()
    {
        if (null === $this->_cachePath) {
            $this->setCachePath(CACHE_PATH);
        }
        return $this->_cachePath;
    }

    /**
     * 设置存储目录
     * @param mixed $cachePath
     */
    public function setCachePath($cachePath)
    {
        $this->_cachePath = $cachePath;
    }

    /**
     * 获取缓存空间存放目录
     * @return mixed
     */
    protected function getNamespacePath()
    {
        return $this->_namespacePath;
    }

    /**
     * 属性赋值后执行函数
     */
    public function init()
    {
        if (empty($this->namespace)) {
            $this->_namespacePath = "{$this->getCachePath()}";
        } else {
            $this->_namespacePath = "{$this->getCachePath()}/{$this->namespace}";
            if (!is_dir($this->_namespacePath)) {
                @mkdir($this->_namespacePath, 0777);
            }
        }
    }

    /**
     * 获取最终的id
     * @param mixed $key
     * @return string
     */
    protected function buildKey($key)
    {
        return md5($this->prefix . (is_string($key) ? $key : json_encode($key)));
    }

    /**
     * 获取缓存文件路径
     * @param string $id
     * @return string
     */
    protected function getFile($id)
    {
        return $this->getNamespacePath() . '/' . $id . '.' . $this->suffix;
    }

    /**
     * 获取 id 的信息
     * @param mixed $id
     * @return mixed
     */
    protected function getValue($id)
    {
        $file = $this->getFile($id);
        if (!file_exists($file)) {
            return null;
        }
        if (filemtime($file) < time()) { // filemtime : 文件最后修改时间
            @unlink($file);
            return null;
        }
        return file_get_contents($file);
    }

    /**
     * 保存 id 的信息
     * @param string $id
     * @param string $value
     * @param int $ttl
     * @return bool
     */
    protected function setValue($id, $value, $ttl)
    {
        $file = $this->getFile($id);
        if (file_put_contents($file, $value, LOCK_EX)) {
            $ttl = $ttl + time();
            @chmod($file, 0777);
            return @touch($file, $ttl);
        }
        return false;
    }

    /**
     * 删除 id 的信息
     * @param string $id
     * @return bool
     */
    protected function deleteValue($id)
    {
        $file = $this->getFile($id);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    /**
     * 清理当前命名空间下的存取信息
     * @return bool
     */
    protected function clearValues()
    {
        $nPath = $this->getNamespacePath();
        $dp = @opendir($nPath);
        while ($file = @readdir($dp)) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            $cur_file = "{$nPath}/{$file}";
            if (is_file($cur_file)) {
                @unlink($cur_file);
            }
        }
        @closedir($dp);
        @rmdir($nPath);
        return true;
    }
}
