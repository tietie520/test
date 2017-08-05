<?php

namespace App\Service\Impl;

if (!defined('IN_PX'))
    exit;

use App\Service\Archives;
use App\Tools\UrlHelper;
use App\Tools\Auxi;
use App\Repository;

/**
 * 文档表服务类
 * v1.2.8 增加了两个索引 categoryIdMappingRootId, listDirMappingRoodId
 * 			分别为类别id和路径映射rootId
 */
class ArchivesImpl implements Archives {

    const VERSION = '1.3.0';

    //服务层组件
    private function __Service($value = 'archives') {}

    private function __Value($cfg, $setting, $__ROOT__, $__CDN__, $__PACKAGE__, $__LANGUAGE_CONFIG__, $__LANGUAGE_ID__, $__ASSETS__) {}

    private function __Inject($cache, Repository\Category $repoCategory, Repository\Archives $repoArc) {}

    private $_aryCategoryMappingRootId = null;
    private $_aryListDirMappingRoodId = null;

    public $currentCategoryRs = null;//用于静态生成注入数据
    public $currentContentRs = null;
    public $currentCategoryTotal = null;//用于静态生成注入数据
    public $aryCategoryDataView = null;
    public $resetAnchorText = 0; //加入锚文本

    /**
     * 读取类别内容及分页
     * @param $categoryId
     * @param bool $usePagination
     * @return array|null
     */
    public function getCategoryRs($categoryId, $usePagination = false) {
        if ($this->chkCategoryDataView() && is_null($this->currentCategoryRs)) {
            $this->currentCategoryRs = $this->repoCategory->find($categoryId, $this->__LANGUAGE_ID__);

        }
        if ($this->currentCategoryRs) {
            $aryCategory = array();
            $aryCategory['rootId'] = $this->currentCategoryRs->root_id;
            $aryCategory['parentId'] = $this->currentCategoryRs->parent_id;
            $aryCategory['categoryId'] = $this->currentCategoryRs->category_id;
            $aryCategory['categoryName'] = $this->currentCategoryRs->category_name;
            $aryCategory['categoryNameEnglish'] = $this->currentCategoryRs->category_name_english;
            $aryCategory['isPart'] = $this->currentCategoryRs->is_part;
            $_GET['parentId'] = $this->currentCategoryRs->parent_id;
            $aryCategory['categoryLevel'] = $this->currentCategoryRs->level;
            $aryCategory['idTree'] = $this->currentCategoryRs->id_tree;
            $aryCategory['channelType'] = $this->currentCategoryRs->channel_type;

            $aryCategory['getBreadcrumb'] = $this->getBreadcrumb($aryCategory['idTree']);

            $aryCategory['pageSeoTitle'] = $this->currentCategoryRs->seo_title;
            $aryCategory['pageSeoDescription'] = $this->currentCategoryRs->seo_description;
            $aryCategory['pageSeoKeywords'] = $this->currentCategoryRs->seo_keywords;

            $aryCategory['pageFooterLink'] = $this->getFooterLink($this->currentCategoryRs->category_id); //底部链接
            //静态页生成时会默认传入一个total，减少数据库访问
            if ($usePagination) {
                $aryCategory['currentCategoryTotal'] = $this->currentCategoryTotal ?
                    $this->currentCategoryTotal :
                    $this->repoArc->count($aryCategory['categoryId']);

                $aryCategory['currentPageSize'] = $this->setting['aryChannelTypeMapping'][$this->__PACKAGE__][$this->currentCategoryRs->channel_type][2];
            }

            if ($aryCategory['isPart'] == 1) {
                $aryCategory['substance'] = $this->resetAnchorText == 1 ?
                    $this->addAnchorText($this->currentCategoryRs->substance) :
                    $this->currentCategoryRs->substance;
            }

            return $aryCategory;
        }
        return null;
    }

    /**
     * 读取文档页内容
     * @param $id
     * @return null
     */
    public function getSubstance($id) {
        //$this->db->debug();
        if ($this->chkCategoryDataView() && is_null($this->currentContentRs)) {
            $this->currentContentRs = $this->repoArc->find($id);
        }
        if ($this->currentContentRs) {
            $arySubstance = array();
            $arySubstance['currentArchivesId'] = $this->currentContentRs->archives_id;
            $arySubstance['rootId'] = $this->currentContentRs->root_id;
            $arySubstance['categoryId'] = $this->currentContentRs->category_id;
            $arySubstance['title'] = $this->currentContentRs->title;
            $arySubstance['releaseDate'] = Auxi::getShortTime($this->currentContentRs->release_date);
            $arySubstance['isPart'] = $this->currentContentRs->is_part;
            $_GET['parentId'] = $this->currentContentRs->parent_id;
            $arySubstance['categoryLevel'] = $this->currentContentRs->level;
            $arySubstance['idTree'] = $this->currentContentRs->id_tree;
            $arySubstance['viewCount'] = $this->currentContentRs->view_count + 1;
            $arySubstance['channelType'] = $this->currentContentRs->channel_type;

            $arySubstance['pageSeoTitle'] = $this->currentContentRs->seo_title;
            $arySubstance['pageSeoDescription'] = $this->currentContentRs->seo_description;
            $arySubstance['pageSeoKeywords'] = $this->currentContentRs->seo_keywords;

            $arySubstance['getBreadcrumb'] = $this->getBreadcrumb($arySubstance['idTree']);

            $arySubstance['getTagsList'] = $this->getTagsList($arySubstance['currentArchivesId']);
            $arySubstance['getPrevNext'] = $this->getPrevNext($this->currentContentRs->release_date, $arySubstance['categoryId']);

            $arySubstance['substance'] = $this->resetAnchorText == 1 ?
                $this->addAnchorText($this->currentContentRs->substance) :
                $this->currentContentRs->substance;
            return $arySubstance;
        }
        return null;
    }

    public function findAll($categoryId, $start = null, $end = null) {
        return $this->repoArc->findAll($categoryId, $start, $end);
    }

    public function findAllRoot($categoryId, $start = null, $end = null) {
        return $this->repoArc->findHomeArchives($categoryId, $start, $end);
    }

    public function findAllRootCount($categoryId) {
        return $this->repoArc->findHomeArchivesCount($categoryId);
    }
    public function chkCategoryDataView() {
        UrlHelper::option(array(
            'cfg' => $this->cfg,
            'setting' => $this->setting,
            '__ROOT__' => $this->__ROOT__,
            '__CDN__' => $this->__CDN__,
            '__PACKAGE__' => $this->__PACKAGE__,
            '__LANGUAGE_CONFIG__' => $this->__LANGUAGE_CONFIG__,
            '__ASSETS__' => $this->__ASSETS__
        ));
        if (!$this->aryCategoryDataView) {
            $this->aryCategoryDataView = $this->getAryCategoryDataView();
        }
        return !!$this->aryCategoryDataView;
    }

    /**
     * 获取文档页的面包屑导航
     * @param $idTree
     * @return string|void
     */
    public function getBreadcrumb($idTree) {
        if (!$idTree) {
            return;
        }

        $_aryIdTree = explode('.', trim($idTree, '.'));

        if ($this->__LANGUAGE_ID__ == 0) {
            $_out = '<a class="nav-txt" href="' . $this->__ROOT__ . '">'
                . '首页' . '</a>';
        } else if ($this->__LANGUAGE_ID__ == 1) {
            $_out = '<a class="nav-txt" href="' . $this->__ROOT__ . 'en">'
                . 'Home' . '</a>';
        } else {
            $_out = '<a class="nav-txt" href="' . $this->__ROOT__ . 'fa">'
                . 'Home' . '</a>';
        }

        $_aryCategoryDataView = $this->aryCategoryDataView;
        $_index = 1;
        $_cur = '';
        foreach ($_aryIdTree as $_id) {
            $_id = intval($_id);
            $_aryCategoryDataView = $_aryCategoryDataView[$_id];
            if ($_index == count($_aryIdTree)) {
                $_cur = ' cur';
            }

            $_out .= ' <i class="txt-icon">></i><a class="nav-txt' . $_cur . '" href="'
                . UrlHelper::getTypeUrl($this->aryCategoryDataView,
                    $_aryCategoryDataView['id_tree']) . '">'
                . $_aryCategoryDataView['category_name'] . '</a>';
            $_index++;
        }
        return $_out;
    }

    public function getPrevNext($releaseDate, $categoryId) {

        $_out = '<div class="detail-page txt-center">';
        $_prevRs = $this->repoArc->prev($releaseDate, $categoryId);
        if ($_prevRs) {
            $_out .= '<a title="'.$_prevRs->title.'" class="prev has" data-href="' . UrlHelper::getPageUrl($_prevRs) . '"></a>';
        } else {
            $_out .= '<a href="javascript:;" class="prev no"></a>';
        }

        $_nextRs = $this->repoArc->next($releaseDate, $categoryId);
        if ($_nextRs) {
            $_out .= '<a class="next has" title="' . $_nextRs->title . '" data-href="' . UrlHelper::getPageUrl($_nextRs) . '"></a>';
        } else {
            $_out .= '<a href="javascript:;" class="next no"></a>';
        }
        $_out .= '</div>';
        return $_out;
    }

    /**
     * 加载指定页面的底部链接
     * @param int $categoryId
     * @return string
     */
    public function getFooterLink($categoryId = 0) {
        $_r = '';
        //$this->db->debug();
        $_rs = $this->repoArc->getFooterLink($categoryId);
        if (count($_rs) > 0) {
            $_r .= '<div class="friend_link">友情链接：';
            foreach ($_rs as $m) {
                //$_temp = intval($m->link_type);
                //不是全站的链接和全站链接但是不在当前类别的
                $_r .= '<a href="' . $m->link_url . '" title="' . $m->link_url . '"'
                    . (intval($m->target) == 0 ? ' target="_blank"' : '')
                    . '>' . $m->keywords . '</a> ';
            }
            $_r .= '</div>';
        }
        return $_r;
    }

    /**
     * @param $id
     * @return string
     */
    public function getTagsList($id) {
        $_r = '';
        $_tags = $this->repoArc->getTagsList($id);
        if (count($_tags) > 0) {
            $_r .= '<div class="tags">Tag：';
            $_bool = false;
            foreach ($_tags as $_tag) {
                if ($_bool) {
                    $_r .= '，';
                }
                $_r .= '<a href="'. UrlHelper::getTagsUrl($_tag). '">' . $_tag . '</a>';
                $_bool = true;
            }
            $_r .= '</div>';
        }
        return $_r;
    }

    /**
     * 广告
     * @param int $categoryId
     * @return array
     */
    public function getSiteAd($categoryId = 0) {
        $_ad = 'aryAdRotator' . $categoryId;

        $_tmp = array();
        if (!($_tmp = $this->cache->expires(300)->get($_ad))) {
            $_tmp = $this->repoArc->getAd($categoryId);
            $this->cache->expires(300)->set($_ad, $_tmp);
        }
        return $_tmp;
    }

    public function getAryCategoryDataView() {
        $_cacheId = 'aryCategoryDataView';
        if (!($_tmp = $this->cache->get($_cacheId))) {
            $this->_aryCategoryMappingRootId = array();
            $this->_aryListDirMappingRoodId = array();
            $_tmp = $this->_getAryCategoryDataView();
            if (!is_null($_tmp)) {
                $_tmp['categoryIdMappingRootId'] = $this->_aryCategoryMappingRootId;
                $_tmp['listDirMappingRoodId'] = $this->_aryListDirMappingRoodId;
            }
            $this->cache->set($_cacheId, $_tmp);
        }
        return $_tmp;
    }

    /**
     * 获取类别缓存用于生成html中的面包屑导航
     * @param int $categoryId
     * @param int $categoryLevel
     * @param int $deep
     * @return array|null
     */
    private function _getAryCategoryDataView($categoryId = 0, $categoryLevel = 0, $deep = 5) {
        if ($categoryLevel >= $deep) {
            return null;
        }
        $_rs = null;
        $_where = '0 = 0';
        $_bind = array();
        $_aryType = array();
        $_tmp = null;
        if ($categoryId > 0) {
            $_where .= ' AND c.`parent_id` = ?';
            array_push($_bind, $categoryId);
        }
        array_push($_bind, $categoryLevel + 1);
        $_rs = $this->repoCategory->findAll($_where, $_bind);
        if (count($_rs) > 0) {
            $_i = 0;
            foreach ($_rs as $m) {
                $_tmp = $this->_getAryCategoryDataView($m->category_id, $categoryLevel + 1, $deep);
                if (!is_null($_tmp)) {
                    $_aryType[$m->category_id] = $_tmp;
                }

                $this->_aryCategoryMappingRootId[$m->category_id] = $m->root_id;
                if ($m->list_dir != '') {
                    $this->_aryListDirMappingRoodId[$m->list_dir] = $m->root_id;
                }

                $_aryType[$m->category_id]['category_name'] = stripslashes($m->category_name);
                $_aryType[$m->category_id]['category_name_english'] = stripslashes($m->category_name_english);
                $_aryType[$m->category_id]['sort'] = $_i;
                $_aryType[$m->category_id]['landscape'] = $m->landscape;
                $_aryType[$m->category_id]['level'] = $m->level;
                $_aryType[$m->category_id]['id_tree'] = $m->id_tree;
                $_aryType[$m->category_id]['parent_id'] = $m->parent_id;
                $_aryType[$m->category_id]['root_id'] = $m->root_id;
                $_aryType[$m->category_id]['channel_type'] = $m->channel_type;
                $_aryType[$m->category_id]['is_part'] = $m->is_part;
                $_aryType[$m->category_id]['list_dir'] = $m->list_dir;
                $_aryType[$m->category_id]['home_sort'] = $m->home_sort;
                $_aryType[$m->category_id]['target'] = $m->target;
                $_aryType[$m->category_id]['nav_type'] = $m->nav_type;
                $_aryType[$m->category_id]['seo_title'] = $m->seo_title;
                $_aryType[$m->category_id]['seo_keywords'] = $m->seo_keywords;
                $_aryType[$m->category_id]['seo_description'] = $m->seo_description;
                $_aryType[$m->category_id]['is_home_display'] = $m->is_home_display;
                $_aryType[$m->category_id]['substance_home'] = $m->substance_home;
                $_aryType[$m->category_id]['substance'] = $m->substance;
                $_aryType[$m->category_id]['language'] = $m->language;

                if ($m->is_part < 2) {
                    $_i++;
                }
            }
            return $_aryType;
        }
        return null;
    }

    /**
     * 预加载的欲清除链接的锚文本数组
     * @return array <type> string
     */
    public function getAryAnchorText() {
        $_temp = array();
        $_rs = $this->repoArc->getAnchorText();
        if (count($_rs) > 0) {
            foreach ($_rs as $m) {
                array_push($_temp, array($m->text, $m->link_url));
                //$_temp[$m->text] = $m->link_url;
            }
        }
        return $_temp;
    }

    /**
     * 插入锚文本
     * @param $text
     * @param null $aryAnchorText
     * @return mixed|string
     */
    public function addAnchorText(& $text, & $aryAnchorText = null) {
        if ($text != '') {
            if ($aryAnchorText == null)
                $aryAnchorText = $this->getAryAnchorText();
            if (count($aryAnchorText) > 0) {
                $_keyword = null;
                $_linkUrl = null;
                foreach ($aryAnchorText as $_kv) {
                    $_keyword = $_kv[0];
                    $_linkUrl = $_kv[1];
                    if (get_magic_quotes_gpc()) {
                        $text = htmlspecialchars_decode(stripslashes($text));
                    }
                    $_tmpKey = preg_quote($_keyword, '/');
                    $_tmpLinkUrl = preg_quote($_linkUrl, '/');
                    $_pattern = '/<a[^>]*' . $_tmpLinkUrl . '[\/"]?\s[^>]*>' . $_tmpKey . '<\/a>/i'; //已有链接的关键字匹配
                    if (!preg_match($_pattern, $text, $_matches)) {//未匹配
                        $text = preg_replace('/(?<![=">])' . $_tmpKey . '/isU',
                            '<a href="' . $_linkUrl . '" title="' . $_keyword . '">' . $_keyword . '</a>',
                            $text, 1);
                    }
                }
            }
        }
        return $text;
    }

}
