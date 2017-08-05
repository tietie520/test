<?php

namespace App\Tools;

if (!defined('IN_PX'))
    exit;


class UrlHelper {

    public static $languageId;
    public static $option;

    public static final function option(Array $option) {
        if (is_null(static::$option)) {
            static::$option = $option;
            static::$languageId = intval(array_search($option['__PACKAGE__'], $option['__LANGUAGE_CONFIG__']));
        }
    }

    public static final function getRoot() {
        return static::$option['__ROOT__'] . (static::$option['cfg']['rewrite'] > 0 ? '' : 'app.php/');
    }

    public static final function getStaticLink(& $strCmp, $text,
                                               $linkAttribute = array(), $disabled = false, $slt = 'slt') {
        $_tmp = '';
        if ($linkAttribute && is_array($linkAttribute) && count($linkAttribute) > 0) {
            $_attr = '';
            if ($disabled) {
                $linkAttribute['href'] = 'javascript:;';
                $linkAttribute['class'] = 'disabled';
            } else {
                if (strpos($linkAttribute['href'], $strCmp) !== false) {
                    $linkAttribute['class'] = (isset($linkAttribute['class']) &&
                        $linkAttribute['class'] != '' ?
                            $linkAttribute['class'] . ' ' :
                            '') . $slt;
                }
                $linkAttribute['href'] = static::getRoot() . $linkAttribute['href'];
            }
            foreach ($linkAttribute as $_k => $_v) {
                if ($_v) {
                    $_attr .= ' ' . $_k . '="' . $_v . '"';
                }
            }
            $_tmp = '<a' . $_attr . '>' . $text . '</a>';
        }
        return $_tmp;
    }

    public static final function getPageUrl(& $obj, $query = null) {
        $_url = '';
        $_isSeoUrl = $obj->seo_url != '' ? true : false;
        if (intval(static::$option['cfg']['is_html_page']) > 0) {
            $_url = static::$option['__ROOT__'] . static::$option['__PACKAGE__'] . '/' . trim($obj->list_dir, '/') . '/'
                . ($_isSeoUrl ? $obj->seo_url : $obj->archives_id)
                . '.html';
        } else {
            $_url = static::getRoot()
                . (intval($obj->language > 0) ? static::$option['__PACKAGE__'] . '/' : '')
                . static::$option['setting']['aryChannelTypeMapping'][static::$option['__PACKAGE__']][$obj->channel_type][3]
                . '/' . ($_isSeoUrl ? $obj->seo_url : $obj->archives_id)
                . (is_null($query) ? '' : '?' . $query);
        }
        return $_url;
    }

    /**
     * getTypeUrl的当前页缓存
     */
    public static $aryTypeUrlCurrentCache = array();

    /**
     *
     * @param type $aryCategoryDataView
     * @param type $aryIdTree
     * @param type $query
     * @return string
     */
    public static final function getTypeUrl(& $aryCategoryDataView, $aryIdTree, $query = null) {
        $_key = '';
        if (is_array($aryIdTree)) {
            $_key = implode('.', $aryIdTree);
        } else {
            $_key = trim($aryIdTree, '.');
            $aryIdTree = explode('.', $_key);
        }
        if (isset(static::$aryTypeUrlCurrentCache[$_key])) {//存在当前页缓存中
            return static::$aryTypeUrlCurrentCache[$_key];
        }

        $_url = '';
        $_rootId = intval(current($aryIdTree));
        //列表目录
        $_rootListDir = $aryCategoryDataView[$_rootId]['list_dir'];
        $_parentPart = null; //父路径类型
        $_selfId = $_rootId; //自身id

        $_selfValue = $aryCategoryDataView;
        $_count = count($aryIdTree);
        if ($_count == 1) {
            $_selfValue = $aryCategoryDataView[$_rootId];
        } else {
            for ($_i = 0; $_i < $_count; $_i++) {
                $_selfValue = $_selfValue[intval($aryIdTree[$_i])];
                if ($_i == $_count - 2) {
                    $_parentPart = $_selfValue['is_part'];
                }
            }
            $_selfId = intval(end($aryIdTree));
        }
        $_selfPart = $_selfValue['is_part']; //自身页面属性
        $_selfLevel = $_selfValue['level'];
        $_i = 0;
        if ($_selfPart == 2) {
            $_url = $_selfValue['list_dir'];
        } else {
            if (intval(static::$option['cfg']['is_html_page']) > 0) {
                $_url = static::$option['__ROOT__'] . trim(static::$option['__PACKAGE__'] . '/' . $_rootListDir, '/') . '/';
                $_selfListDir = $_selfValue['list_dir'];
                if ($_selfLevel > 1) {//非一级栏目
                    $_i = $_selfValue['sort'];
                    if ($_i == 0 && $_parentPart == 1) {//如果自身为index但父目录是单页频道页，则应该显示list_id
                        ++$_i;
                    }

                    //判断子目录并提取真实显示的id
                    $_tmpChildTypeIdListDir = Auxi::getChildTypeIdListDir($_selfValue, $_selfId);
                    $_selfId = $_tmpChildTypeIdListDir[0];
                    $_selfListDir = $_tmpChildTypeIdListDir[1];
                    unset($_tmpChildTypeIdListDir);

                    if ($_i == 0 && $_selfLevel > 2 && !Auxi::isDataViewIndexId(
                            $aryCategoryDataView[$_rootId], $_selfId, $_selfLevel)) {
                        ++$_i;
                    } //2级以后都是以list_id显示
                }

                if ($_i > 0) {
                    $_url .= Auxi::getArchivesListName($_i, $_selfId, $_selfListDir);
                }
            } else {
                $_url = static::getRoot()
                    . (static::$languageId > 0 ? static::$option['__PACKAGE__'] . '/' : '')
                    . Auxi::getDynamicListName(static::$option['setting']['aryChannelTypeMapping'],
                        static::$option['__PACKAGE__'],
                        $_selfValue, $_selfId)
                    . (is_null($query) ? '' : '?' . $query);
            }
        }
        static::$aryTypeUrlCurrentCache[$_key] = $_url;
        return $_url;
    }

    public static final function getSelfUrl() {
        return 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    public static final function getReturnUrl($url, $ruledOut = null) {
        $_returnUrl = $url ? urldecode($url) : static::getSelfUrl();
        //返回值存在排除项的时候 回到首页
        if ($ruledOut && stripos($_returnUrl, $ruledOut) !== false) {
            $_returnUrl = static::$option['__ROOT__'];
        }
        return urlencode($_returnUrl);
    }

    public static final function getTagsUrl($tag) {
        return static::getRoot() . 'tags/' . urlencode($tag);
    }

    public static final function getSearchUrl($q) {
        return static::getRoot() . 'search/' . urlencode($q);
    }

    public static final function compareSelect($id, $level, $idTree, $className) {
        $_aryIdTree = explode('.', trim($idTree, '.'));
        return $id == intval($_aryIdTree[$level - 1]) ? ' class="' . $className . '"' : '';
    }

    public static final function getUserImg($img, $status, & $userId) {
        return static::getUploadImg(($status == 2 ? $img : null), 's',
            array('lazy' => false, 'vml' => false), 'user/' . $userId);
    }

    public static final function getUploadImg($img, $mode = 's',
                                              $imgAttribute = array('lazy' => false), $path = 'pics') {
        $_attr = '';
        if ($mode == 'auto') {
            if ($img == '') {
                return;
            }
            $mode = 's';
        }
        $_src = static::$option['__CDN__'] . (
            $img ? trim($path, '/') . '/' . $mode . '/' . $img : 'no_img.gif');
        if (isset($imgAttribute['lazy']) && $imgAttribute['lazy']) {
            $_attr .= ' class="lazy"';
            $_attr .= ' src="' . static::$option['__CDN__'] . 'gray.gif"';
            $_attr .= ' data-original="' . $_src . '"';
        } else if (isset($imgAttribute['src'])) {
            return $_src;
        } else {
            $_attr .= ' src="' . $_src . '"';
        }
        unset($imgAttribute['lazy']);
        if (count($imgAttribute) > 0) {
            foreach ($imgAttribute as $_k => $_v) {
                if ($_v) {
                    $_attr .= ' ' . $_k . '="' . $_v . '"';
                }
            }
        }
        return '<img' . $_attr . '>';
    }

    public static final function getUploadImgByIdTree(& $idTree,
                                                      & $aryCategoryDataView, $mode = 'l', $path = 'pics') {
        $_aryIdTree = explode('.', trim($idTree, '.'));
        foreach ($_aryIdTree as $_id) {
            $aryCategoryDataView = $aryCategoryDataView[(int) $_id];
        }
        return $aryCategoryDataView['landscape'] ?
            '<img src="' . static::$option['__CDN__']
            . trim($path, '/') . '/' . $mode . '/'
            . $aryCategoryDataView['landscape'] . '">' :
            '';
    }

    public static final function getAssetsImg($imgAttribute = array('lazy' => false), $linkAttribute = array()) {
        $_attr = '';
        $_img = null;
        if ($imgAttribute && is_array($imgAttribute) && $imgAttribute['src']) {
            $_src = static::$option['__ASSETS__'] . 'images/' . $imgAttribute['src'];
            if ($imgAttribute['lazy']) {
                $_attr .= ' class="lazy"';
                $_attr .= ' src="' . static::$option['__CDN__'] . 'gray.gif"';
                $_attr .= ' data-original="' . $_src . '"';
            } else {
                $_attr .= ' src="' . $_src . '"';
            }
            unset($imgAttribute['src']);
            unset($imgAttribute['lazy']);
            if (count($imgAttribute) > 0) {
                foreach ($imgAttribute as $_k => $_v) {
                    if ($_v) {
                        $_attr .= ' ' . $_k . '="' . $_v . '"';
                    }
                }
            }
            $_img = '<img' . $_attr . ' />';
            if ($linkAttribute && is_array($linkAttribute) && count($linkAttribute) > 0) {
                $_attr = '';
                foreach ($linkAttribute as $_k => $_v) {
                    if ($_v) {
                        $_attr .= ' ' . $_k . '="' . $_v . '"';
                    }
                }
                $_img = '<a' . $_attr . '>' . $_img . '</a>';
            }
        }

        return $_img;
    }

}
