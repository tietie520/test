<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/6 0006
 * Time: 下午 16:23
 */
namespace App\Repository;

interface Category {
    public function find($categoryId, $languageId);

    public function findAll($where, $bind);

    public function findHomeCategory($categoryId);
}