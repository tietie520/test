<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/6 0006
 * Time: 下午 17:46
 */
namespace App\Repository;

interface Archives {
    public function count($categoryId);

    public function find($id);

    public function viewCount($id);

    public function findAll($categoryId, $start = null, $end = null);

    public function prev($date, $categoryId);

    public function next($date, $categoryId);

    /**
     * 可用于循环中[chains()]
     * @return mixed
     */
    public function getAnchorText();

    public function getFooterLink($categoryId = 0, $isHome = false);

    public function getTagsList($id);

    public function getTags();

    public function getAd($language, $typeId = 0);

    public function findLogo();

    public function findHomeArchives($categoryId, $start = null, $end = null, $isHome = false);

    public function findHomeArchivesCount($categoryId);
}