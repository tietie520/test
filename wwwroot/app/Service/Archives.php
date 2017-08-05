<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/7 0007
 * Time: 上午 10:39
 */
namespace App\Service;


/**
 * 文档表服务类
 * v1.2.8 增加了两个索引 categoryIdMappingRootId, listDirMappingRoodId
 *            分别为类别id和路径映射rootId
 */
interface Archives {
    /**
     * 读取类别内容及分页
     * @param $categoryId
     * @param bool $usePagination
     * @return array|null
     */
    public function getCategoryRs($categoryId, $usePagination = false);

    /**
     * 读取文档页内容
     * @param $id
     * @return null
     */
    public function getSubstance($id);

    public function findAll($categoryId, $start = null, $end = null);

    public function findAllRoot($categoryId, $start = null, $end = null);

    public function findAllRootCount($categoryId);

    public function chkCategoryDataView();

    /**
     * 获取文档页的面包屑导航
     * @param $idTree
     * @return string|void
     */
    public function getBreadcrumb($idTree);

    public function getPrevNext($releaseDate, $categoryId);

    /**
     * 加载指定页面的底部链接
     * @param int $categoryId
     * @return string
     */
    public function getFooterLink($categoryId = 0);

    /**
     * @param $id
     * @return string
     */
    public function getTagsList($id);

    /**
     * 广告
     * @param int $categoryId
     * @return array
     */
    public function getSiteAd($categoryId = 0);

    public function getAryCategoryDataView();

    /**
     * 预加载的欲清除链接的锚文本数组
     * @return array <type> string
     */
    public function getAryAnchorText();

    /**
     * 插入锚文本
     * @param $text
     * @param null $aryAnchorText
     * @return mixed|string
     */
    public function addAnchorText(&$text, &$aryAnchorText = null);
}