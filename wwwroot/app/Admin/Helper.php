<?php

namespace App\Admin;

if (!defined('IN_PX'))
    exit;

use App\Tools\Html;

class Helper {

    /**
     * 树状类别图片样式
     * @param $level
     * @param $categoryName
     * @param $vp
     * @return string
     */
    public static function getTreeIMG($level, $categoryName, $vp) {
        $_treeShow = '';
        if ($level == 1) {
            $_treeShow = '<img src="' . $vp . 'images/itminus.gif" class="flush">';
        } else {
            for ($i = 1; $i < $level; $i++) {
                if ($i == 1)
                    $_treeShow = '<img src="' . $vp . 'images/i.gif">';
                else
                    $_treeShow .= '<img src="' . $vp . 'images/ii.gif">';
            }
            $_treeShow .= '<img src="' . $vp . 'images/tminus.gif">';
        }
        $_treeShow .= '<span title="' . $categoryName . '">'
            . Html::getLenStr($categoryName, 30) . '</span>';
        return $_treeShow;
    }

    /**
     * @param $cdn
     * @param $vp
     * @param $img
     * @param string $title
     * @param string $path
     * @return string
     */
    public static function createSmallImg(& $cdn, $vp, $img, $title = '', $path = 'pics') {
        if (strcasecmp('user', $path) == 0) {
            $_smallImgSrc = $cdn . $img;
        } else {
            $_smallImgSrc = $cdn . $path . '/s/' . $img;
        }
        $_temp = $img ?
            '<a href="' . $cdn . $path . '/l/' . $img . '" class="mybox" title="'
            . $title . '"><img src="' . $_smallImgSrc
            . '" height="20" onload="if(this.width/this.height>3){this.width=50;this.removeAttribute(\'height\');}"></a>' :
            '<img src="' . $vp . 'images/no_pic.jpg" height="20">';
        return $_temp;
    }

    /**
     * @param $map
     * @param $userId
     * @param $power
     * @return string
     */
    public static function getTopAdminMenu(& $map, $userId, & $power) {
        $_menuList = '';
        $_top = '';
        $_left = '';
        if (intval($userId) != 1) {//非超级管理员验证权限
            foreach ($map as $_k1 => $_v1) {
                if (in_array($_k1, $power, true)) {
                    $_top .= '<dl id="menu_' . $_k1 . '"><dt rel="'
                        . $_k1 . '/' . key($_v1['menu'])
                        . '"><span class="l"></span><span class="m">'
                        . $_v1['title'] . (isset($_v1['menu']) && count($_v1['menu']) > 0 ? ' ▼' : '')
                        . '</span><span class="r"></span></dt></dl>';
                    $_left .= '<div class="sidebar-menu" id="sidebar-' . $_k1 . '">';
                    if (isset($_v1['menu']) && count($_v1['menu']) > 0) {
                        $_left .= '<ul>';
                        foreach ($_v1['menu'] as $_k2 => $_v2) {
                            if (in_array($_k1 . '.' . $_k2, $power, true) &&
                                (!isset($_v2['display']) || false !== $_v2['display'])) {
                                $_left .= '<li><a href="javascript:void(0);"'
                                    . (isset($_v2['class']) ? ' class="' . $_v2['class'] . '"' : '')
                                    . ' rel="' . $_k1 . '/' . $_k2 . '">'
                                    . $_v2['name'] . '</a></li>';
                            }
                        }
                        $_left .= '</ul>';
                    }
                    $_left .= '</div>';
                }
            }
        } else {
            foreach ($map as $_k1 => $_v1) {
                $_top .= '<dl id="menu_' . $_k1 . '"><dt rel="' . $_k1 . '/'
                    . key($_v1['menu'])
                    . '"><span class="l"></span><span class="m">'
                    . $_v1['title'] . (isset($_v1['menu']) && count($_v1['menu']) > 0 ? ' ▼' : '')
                    . '</span><span class="r"></span></dt></dl>';
                $_left .= '<div class="sidebar-menu" id="sidebar-' . $_k1 . '">';
                if (isset($_v1['menu']) && count($_v1['menu']) > 0) {
                    $_left .= '<ul>';
                    foreach ($_v1['menu'] as $_k2 => $_v2) {
                        if (!isset($_v2['display']) || false !== $_v2['display']) {
                            $_left .= '<li><a href="javascript:void(0);"'
                                . (isset($_v2['class']) ? ' class="' . $_v2['class'] . '"' : '')
                                . ' rel="' . $_k1 . '/' . $_k2 . '">'
                                . $_v2['name'] . '</a></li>';
                        }
                    }
                    $_left .= '</ul>';
                }
                $_left .= '</div>';
            }
        }
        return array($_top, $_left);
    }

    /**
     * 创建一个图片或者文件上传
     * @param $upType
     * @param $fieldName
     * @param $fieldValue
     * @param $picExtName
     * @param $fileExtName
     * @param $cdn
     * @param $vp
     * @param string $mode
     * @param int $width
     * @return string
     */
    public static function createUpFile($upType, $fieldName, $fieldValue, $picExtName,
                                        $fileExtName, & $cdn, $vp, $mode = 'auto', $width = 50) {
        $_up = '<dl class="img_file_up">';
        $_up .= '<dt id="return_' . $fieldName . '">';
        if (strcasecmp('img', $upType) == 0) {
            if (!empty($fieldValue)) {
                $_up .= '<img src="' . $cdn . 'pics/s/' . $fieldValue . '" height="' . $width . '" onload="if(this.width/this.height>3){this.width=150;this.removeAttribute(\'height\');}">';
            } else {
                $_up .= '<img src="' . $vp . 'images/no_pic.jpg">';
            }
            $_extName = implode(', ', $picExtName);
        } else {
            if (!empty($fieldValue)) {
                $_up .= '<img src="' . $vp . 'images/file/'
                    . substr(strrchr($fieldValue, '.'), 1)
                    . '.gif"> ' . $cdn . 'files/' . $fieldValue;
            }
            $_extName = implode(', ', $fileExtName);
        }
        $_up .= '</dt>';
        $_up .= '<dd>';
        $_up .= '<div>';

        $_up .= '<input type="button" class="ipt_btn" value="添加" id="add_'
            . $fieldName . '" onclick="Tools.upload({ uploadType : \''
            . $upType . '\', uploadName : \'' . $fieldName . '\', mode : \'' . $mode . '\', imgWidth : \''
            . $width . '\' });"';
        if (!empty($fieldValue)) {
            $_up .= ' style="display:none;"';
        }
        $_up .= '>';

        $_up .= '<input type="button" class="ipt_btn" value="删除" id="del_'
            . $fieldName . '" onclick="Tools.delFile(\''
            . $upType . '\', \'' . $fieldName . '\');"';
        if (empty($fieldValue)) {
            $_up .= ' style="display:none;"';
        }
        $_up .= '>';

        $_up .= '</div>';
        $_up .= '允许格式：' . $_extName;
        $_up .= '<input type="hidden" name="' . $fieldName . '" id="'
            . $fieldName . '" value="' . $fieldValue . '">'
            . '<input type="hidden" name="' . $fieldName . '-attachment" id="'
            . $fieldName . '-attachment" value="">';
        $_up .= '</dd>';
        $_up .= '</dl>';
        return $_up;
    }

    public static function createMultiUpImg($fieldName, $fieldValue, $aryImgSrc,
                                              $picExtName, & $cdn, $width = 100) {
        $_multiple = '<dl id="return_' . $fieldName . '" class="control_multiple_up_img">';
        $_multiple .= '<dt>';
        $_multiple .= '<input type="button" class="ipt_btn" value="点击添加图片" id="add_'
            . $fieldName . '" onclick="Tools.upload({ uploadType : \'img\',
					mode : \'multiple\', uploadName : \''
            . $fieldName . '\', imgWidth : \'' . $width . '\' });"> 允许格式：' . implode(' ', $picExtName);
        $_multiple .= '</dt>';
        $_multiple .= '<dd>';
        $_multiple .= '<ol>';
        if (count($aryImgSrc) > 0) {
            foreach ($aryImgSrc as $_src) {
                $_srcId = str_replace(array(',', '.'), array('', ''), $_src);
                $_multiple .= '<li id="multiple_img_' . $_srcId . '">';
                $_multiple .= '<label for="primary_' . $_srcId . '">主显图</label><input type="radio" id="primary_'
                    . $_srcId . '" name="' . $fieldName . '" value="' . $_src . '"';
                if (strcasecmp($fieldValue, $_src) == 0)
                    $_multiple .= ' checked="checked"';
                $_multiple .= '>';
                $_multiple .= '<img src="' . $cdn . 'pics/s/' . $_src . '" height="' . $width . '">';
                $_multiple .= '<input type="button" class="ipt_btn" value="删除"
				onclick="Tools.delMultipleImg(\'' . $_src . '\');"></li>';
            }
        }
        $_multiple .= '</ol></dd>';
        $_multiple .= '</dl>';
        return $_multiple;
    }

    /**
     * action name
     * @param <type> $action
     * @return string <type>
     */
    public static function getActionName($action) {
        $_currentPageName = '';
        switch ($action) {
            case 'add':
                $_currentPageName = '添加';
                break;
            case 'edit':
                $_currentPageName = '编辑';
                break;
            case 'reply':
                $_currentPageName = '回复';
                break;
            case 'view':
                $_currentPageName = '查看';
                break;
            default:
                $_currentPageName = '编辑';
                break;
        }
        return $_currentPageName;
    }

    /**
     * 获取提交的默认搜索 { 配置参数，时间1，时间2，语言选择，搜索字段 } ★★★★★
     * @param <type> $cfg
     * @param bool $isShowLanguage
     * @param string $sltDateA
     * @param string $sltDateB
     * @param string $strSearchKeyword
     */
    public static function getDefaultSearchInfo($aryLanguage, $isShowLanguage = true,
                                                $sltDateA = '', $sltDateB = '', $strSearchKeyword = '') {
        $_search = '信息检索：';
        if (count($aryLanguage) > 0 && $isShowLanguage) {
            $_search .= '<select name="sltLanguage">';
            foreach ($aryLanguage as $_k => $_v) {
                $_search .= '<option value="' . $_k . '">' . $_v . '</option>';
            }
            $_search .= '</select> ';
        }
        $_search .= '<input type="text" name="sltDateA"'
            . ' onClick="SelectDate(this)" class="set_date"'
            . ' readonly="readonly" size="12" value="'
            . $sltDateA . '" /> - ';
        $_search .= '<input type="text" name="sltDateB"'
            . ' onClick="SelectDate(this)" class="set_date"'
            . ' readonly="readonly" size="12" value="'
            . $sltDateB . '" /> ';
        $_search .= '关键字 <input type="text" name="strSearchKeyword"'
            . ' value="' . $strSearchKeyword . '" /> ';
        $_search .= '<input type="submit" class="ipt_btn"'
            . ' value="搜索" />';
        echo($_search);
    }

}
