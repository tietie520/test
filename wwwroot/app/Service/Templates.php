<?php
namespace App\Service;

use Phoenix\Exception\FormatException;
/**
 * 模板类
 * v1.2.3 修复了静态页分页及若干bug，提升了运行时效率，加入运行时缓存
 * v1.2.4 将列表风格及文档详细分开
 * v1.2.5 修复若干路径bug
 * v1.2.6 统一通过Page类生成，可直接启用动态页
 * v1.3.2 若干bug 2013-5-5
 * v1.3.3 若干bug 2013-10-10 去掉了未用到的\Service\UrlHelper类注入
 * v1.3.4 使用链式sql访问器
 */
interface Templates {
    /**
     * @return int
     * @throws FormatException
     */
    public function createHomepage();

    /**
     * 站点地图生成，创建所有语言的sitemap
     */
    public function createSitemap();

    /**
     * 创建html文件
     * @param $file
     * @param null $package
     * @param array $paths
     * @return int
     */
    public function createHtml($file, $package = null, $paths = array());

    /**
     * 创建所有栏目
     * @param int $categoryId
     * @param null $limit
     * @return bool
     */
    public function createColumn($categoryId = 0, $limit = null);

    /**
     * 创建文档html
     * @param null $id 指定生成文档id
     * @param string $mode 生成类别下所有文档 one 生成单个文档
     * @param null $limit
     */
    public function createArchives($id = null, $mode = 'type', $limit = null);

    /**
     * 创建类别下的文件，递归生成
     * @param int $categoryId 生成路径，含递归值
     * @param null $limit 生成级别
     * @return bool
     */
    public function createCategory($categoryId = 0, $limit = null);

    /**
     * 创建生成文档的根路径，根据配置文件
     * @return bool
     */
    public function createArchivesDir();

    /**
     * 创建生成文档的根路径，根据配置文件
     * @return bool
     */
    public function deleteArchivesDir();

    public function createByStep();
}