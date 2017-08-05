<?php

namespace App\Tools;

if (!defined('IN_PX'))
    exit;

/**
 * Class Auxi
 * @package Tools
 */
class Auxi {
    
    /**
     * 数据库保留数据
     * @param $id
     * @param $needId
     * @return int|string
     */
    public static function databaseNeedId($id, $needId) {
        if (strpos($id, ',') === false) {
            return intval($id);
        } else {
            $ary = explode(',', $id);
            if (!in_array($needId, $ary))
                return $id;
            for ($i = 0; $i < count($ary); $i++) {
                if (intval($ary[$i]) == $needId)
                    unset($ary[$i]);
            }
            return implode(',', $ary);
        }
    }
    
    /**
     * 获取权限按钮
     * @param $action
     * @param $security
     * @param $pageId
     * @param $power
     * @param string $title
     * @param bool $isDefined
     * @return string
     */
    public static function getPowerButton($action, & $security, & $pageId,
                                          & $power, $title = '', $isDefined = false) {
        $_r = '';
        //$_isSuper = $power == '[999999]' ? true : false;
        $_sp = $security . '.' . $pageId;
        if (in_array($_sp . '.' . $action, (array) $power, true)) {
            switch (strtolower($action)) {
                case 'view':
                    $_r = $isDefined ? $title : ',onRowDblclick : this.view';
                    break;
                case 'add':
                    $_r = $isDefined ? $title : "{name:'添加{$title}', bclass:'add', onpress:this.add},{separator:true},";
                    break;
                case 'edit':
                    $_r = $isDefined ? $title : ',onRowDblclick : this.edit';
                    break;
                case 'delete':
                    $_r = $isDefined ? $title : ",{name:'{$title}', bclass:'delete', dbAction:this.handlerUrl + 'Delete', onpress:Navigation.doDelete}";
                    break;
                default:
                    $_r = $title;
                    break;
            }
        }
        return $_r;
    }

    public static function getPowerButton2($action, & $security, & $pageId,
                                          & $power, $title = '', $isDefined = false) {
        $_r = '';
        //$_isSuper = $power == '[999999]' ? true : false;
        $_sp = $security . '.' . $pageId;
        if (in_array($_sp . '.' . $action, (array) $power, true)) {
            switch (strtolower($action)) {
                case 'view':
                    $_r = $isDefined ? $title : ',onRowDblclick : this.view';
                    break;
                case 'add':

                    $_r = "<a class='pull-right cms-main-addmodule J-app-load' data-name='添加文档".$title."'  data-href='/admin/structure/archivesContent?parentPageId=archives&currentPageActionClassName=structure/archives&action=add&r=0.8307142968397103' href='javascript:;'><i class='glyphicon glyphicon-plus'></i>
        添加
    </a>";

//                    $_r = $isDefined ? $title : "{name:'添加{$title}', bclass:'add', onpress:this.add},{separator:true},";
                    break;
                case 'edit':
                    $_r = $isDefined ? $title : ',onRowDblclick : this.edit';
                    break;
                case 'delete':
                    $_r = $isDefined ? $title : ",{name:'{$title}', bclass:'delete', dbAction:this.handlerUrl + 'Delete', onpress:Navigation.doDelete}";
                    break;
                default:
                    $_r = $title;
                    break;
            }
        }
        return $_r;
    }
    
    /**
     * 允许的文件类型
     * @param $uploadType
     * @param $picExtName
     * @param $fileExtName
     * @return string
     */
    public static function allowType($uploadType, $picExtName, $fileExtName) {
        return strcasecmp('img', $uploadType) == 0 ? implode(' ', $picExtName) : implode(' ', $fileExtName);
    }

    /**
     * 过滤特殊字符
     * @param $str
     * @return mixed|string
     */
    public static function getSafeStr($str) {
        if (isset($str)) {
            $_temp = addslashes(trim($str));
            $_temp = str_replace('_', '\_', $_temp);
            $_temp = str_replace('%', '\%', $_temp);
            return $_temp;
        }
    }

    /**
     * @param $timestamp
     * @return bool|string
     */
    public static function getTime($timestamp) {
        if ($timestamp)
            return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * @param $timestamp
     * @return bool|string
     */
    public static function getShortTime($timestamp) {
        if ($timestamp)
            return date('Y-m-d', $timestamp);
    }
    
    /**
     * 是否启用代理ip访问
     * @return boolean
     */
    public static function isProxyIp() {
        if ($_SERVER['HTTP_X_FORWARDED_FOR'] ||
            $_SERVER['HTTP_X_FORWARDED'] ||
            $_SERVER['HTTP_FORWARDED_FOR'] ||
            $_SERVER['HTTP_CLIENT_IP'] ||
            $_SERVER['HTTP_VIA'] ||
            in_array($_SERVER['REMOTE_PORT'], array(8080, 80, 6588, 8000, 3128, 553, 554)) ||
            @fsockopen($_SERVER['REMOTE_ADDR'], 80, $errno, $errstr, 30)) {
            return true; //caught
        }
        return false;
    }
    
    /**
     * 获取真实IP
     * @return string
     */
    public static function getIP() {
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $_realIp = $_SERVER['HTTP_CLIENT_IP'];
            } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $_realIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $_realIp = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            if (getenv('HTTP_CLIENT_IP')) {
                $_realIp = getenv('HTTP_CLIENT_IP');
            } else if (getenv('HTTP_X_FORWARDED_FOR')) {
                $_realIp = getenv('HTTP_X_FORWARDED_FOR');
            } else {
                $_realIp = getenv('REMOTE_ADDR');
            }
        }
        if (strcmp('::1', $_realIp) == 0 || !$_realIp) {
            $_realIp = '127.0.0.1';
        }
        return $_realIp;
    }
    
    /**
     * @param $text
     * @param $array
     * @return string|void
     */
    public static function getArrayContent($text, $array) {
        if (!$text)
            return;
        $temp = explode(',', $text);
        $return = '';
        foreach ($temp as $v) {
            $return .= $array[intval($v)] . '　';
        }
        return $return;
    }

    /**
     * 随机构造密码或编号
     * @param $length
     * @return string
     */
    public static function randomCode($length) {
        $_hash = '';
        $_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $_max = strlen($_chars) - 1;
        mt_srand((double) microtime() * 1000000);
        for ($i = 0; $i < $length; $i++) {
            $_hash .= $_chars[mt_rand(0, $_max)];
        }
        return $_hash;
    }
    
    /**
     * @return int
     */
    public static function randomNumPassword() {
        mt_srand((double) microtime() * 1000000);
        return intval(90000000 * (mt_rand(0, 10000000) / 10000000)) + 10000000;
    }
    
    /**
     * @param int $min
     * @param int $max
     * @return int
     */
    public static function randomNum($min = 1, $max = 4) {
        mt_srand((double) microtime() * 1000000);
        return intval(mt_rand($min, $max));
    }
    
    /**
     * @return float
     */
    public static function getMicroTime() {
        list($_usec, $_sec) = explode(' ', microtime());
        return ((float) $_usec + (float) $_sec);
    }
    
    /**
     * uuid
     * @return string
     */
    public static function guid() {
        $_uuid = '';
        if (function_exists('com_create_guid')) {
            $_uuid = com_create_guid();
        } else {
            //mt_srand((double) microtime() * 10000); //optional for php 4.2.0 and up.
            $_charid = strtoupper(md5(uniqid(rand(), true)));
            $_hyphen = chr(45); // "-"
            $_uuid = chr(123)// "{"
                . substr($_charid, 0, 8) . $_hyphen
                . substr($_charid, 8, 4) . $_hyphen
                . substr($_charid, 12, 4) . $_hyphen
                . substr($_charid, 16, 4) . $_hyphen
                . substr($_charid, 20, 12)
                . chr(125); // "}"
        }
        return strtolower(trim($_uuid, '{}'));
    }
    
    /**
     * 获取生成文档列表页面的名称
     * @param $i 是否为第一个，命名为index
     * @param $categoryId
     * @param $list_dir 自定义的页面名称
     * @param bool $showIndex
     * @return string
     */
    public static function getArchivesListName($i, $categoryId, $list_dir, $showIndex = true) {
        $_temp = '';
        if ($i == 0) {
            $_temp = ($showIndex ? 'index.html' : '');
        } else {
            $_temp = ($list_dir ? $list_dir : 'list_' . intval($categoryId)) . '.html';
        }
        return $_temp;
    }
    
    /**
     * 打招呼
     * @return string
     */
    public static function sayHi() {
        $_hiHour = date('H', time());
        if ($_hiHour >= 0 && $_hiHour < 6)
            return '现在都凌晨啦，该休息了^.^';
        else if ($_hiHour >= 6 && $_hiHour < 9)
            return '早上好';
        else if ($_hiHour >= 9 && $_hiHour < 12)
            return '上午好';
        else if ($_hiHour >= 12 && $_hiHour < 13)
            return '中午好';
        else if ($_hiHour >= 13 && $_hiHour < 17)
            return '下午好';
        else if ($_hiHour >= 17 && $_hiHour < 19)
            return '傍晚好';
        else if ($_hiHour >= 19 && $_hiHour < 22)
            return '晚上好';
        else
            return '很晚了，早点睡吧';
    }
    
    /**
     * 输出level的颜色区分
     * @param $intLevel
     * @return string
     */
    public static function getDeepColor($intLevel) {
        $_temp = '';
        switch (intval($intLevel)) {
            case 1:
                $_temp = ' style="color:#00c900;"';
                break;
            case 2:
                $_temp = ' style="color:blue;"';
                break;
            case 3:
                $_temp = ' style="color:gray;"';
                break;
            case 4:
                $_temp = ' style="color:gold;"';
                break;
            case 5:
                $_temp = ' style="color:aqua;"';
                break;
            case 6:
                $_temp = ' style="color:olive;"';
                break;
            case 7:
                $_temp = ' style="color:#ba40e3;"';
                break;
            default:
                $_temp = ' style="color:red;"';
                break;
        }
        return $_temp;
    }
    
    public static function getColorLevel(& $ary, $value) {
        $value = intval($value);
        return '<span' . self::getDeepColor($value) . '>' . $ary[$value] . '</span>';
    }
    
    /**
     * 来自PHPMailer::ValidateAddress
     * @param $email
     * @return bool|int
     */
    public static function isValidEmail($email) {
        if (function_exists('filter_var')) { //Introduced in PHP 5.2
            if (filter_var($email, FILTER_VALIDATE_EMAIL) === FALSE) {
                return false;
            } else {
                return true;
            }
        } else {
            return preg_match('/^(?:[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+\.)*'
                . '[\w\!\#\$\%\&\'\*\+\-\/\=\?\^\`\{\|\}\~]+@(?:(?:(?:[a-zA-Z0-9_]'
                . '(?:[a-zA-Z0-9_\-](?!\.)){0,61}[a-zA-Z0-9_-]?\.)+[a-zA-Z0-9_]'
                . '(?:[a-zA-Z0-9_\-](?!$)){0,61}[a-zA-Z0-9_]?)|'
                . '(?:\[(?:(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\.){3}'
                . '(?:[01]?\d{1,2}|2[0-4]\d|25[0-5])\]))$/', $email);
        }
    }
    
    /**
     * @param $ip
     * @return string
     */
    public static function getKillIp($ip) {
        $_tempIp = explode('.', $ip);
        return $_tempIp[0] . '.' . $_tempIp[1];
    }
    
    /**
     * 获取分页传值
     * @param $currentPage
     * @return int
     */
    public static function getCurrentPage($currentPage) {
        $currentPage = intval($currentPage);
        return $currentPage == 0 ? 1 : $currentPage;
    }
    
    /**
     * 比较数字是否相等，然后着色
     * @param $obj1
     * @param $obj2
     * @param string $className
     * @param string $hold 保留样式
     * @return string
     */
    public static function compareSelect($obj1, $obj2, $className = 'current', $hold = '') {
        $obj1 = '|' . trim($obj1, '|') . '|';
        $obj2 = '|' . $obj2 . '|';
        return self::comparePath($obj1, $obj2, $className, $hold);
    }
    
    /**
     * 比较路径，如果包含指定则着色
     * @param $obj1
     * @param $obj2
     * @param string $className
     * @param string $hold 保留样式
     * @return string
     */
    public static function comparePath($obj1, $obj2, $className = 'current', $hold = '') {
        return strpos($obj1, $obj2) !== false ? ' class="' . $className
            . ($hold != '' ? ' ' . $hold : '') . '"' : ($hold != '' ? ' class="' . $hold . '"' : '');
    }
    
    /**
     * 比较bool值，为真则着色
     * @param $bool
     * @param string $className
     * @param string $hold
     * @return string
     */
    public static function compareBool($bool, $className = 'current', $hold = '') {
        return $bool ? ' class="' . $className
            . ($hold != '' ? ' ' . $hold : '') . '"' : ($hold != '' ? ' class="' . $hold . '"' : '');
    }

    /**
     * 递归获取显示id及list_dir
     * @param $value
     * @param $categoryId
     * @return array
     */
    public static function getChildTypeIdListDir(& $value, $categoryId) {
        if (self::typeHasChild($value) && $value['is_part'] == 0) {//自身是列表页但含有子栏目，则显示第一个子栏目
            $categoryId = key($value);
            return self::getChildTypeIdListDir($value[$categoryId], $categoryId);
        }
        return array($categoryId, $value['list_dir']);
    }
    
    /**
     * 获取动态页面的现实id
     * @param $aryChannelTypeMapping
     * @param $package
     * @param $value
     * @param $categoryId
     * @return string
     */
    public static function getDynamicListName(& $aryChannelTypeMapping,
                                              & $package, & $value, $categoryId) {
        if (self::typeHasChild($value) && $value['is_part'] == 0) {
            $categoryId = key($value);
            return self::getDynamicListName($aryChannelTypeMapping, $package, $value[$categoryId], $categoryId);
        }
        if ($value['list_dir'] != '')
            return $value['list_dir'] . '/';
        else
            return $aryChannelTypeMapping[$package][$value['channel_type']][1]
            . '/' . $categoryId;
    }
    
    /**
     * 判断类别数组视图中某一项是否含有子类
     * @param $ary
     * @return bool
     */
    public static function typeHasChild(& $ary) {
        if (!is_array($ary))
            return false;
        reset($ary);
        return is_array(current($ary));
    }
    
    /**
     * 判断第一项的id号，用于无限级分类目录中第一项是否使用index命名
     * @param $ary
     * @param $id
     * @param int $limit
     * @return bool
     */
    public static function isDataViewIndexId($ary, $id, $limit = 3) {
        if (!is_array($ary) || count($ary) <= 0)
            return false;
        //从level = 2开始，实际上是level - 1级，即上一级可获取到第一项id
        reset($ary);
        for ($_i = 2; $_i < $limit; $_i++) {
            while (list(, $_value) = each($ary)) {
                if ($_value['is_part'] < 2) {
                    $ary = $_value;
                    break;
                }
            }
            if (is_array($ary) && count($ary) > 0)
                reset($ary);
        }
        return (is_array($ary) ? key($ary) : 0) == $id;
    }
    
    public static function isIE6() {
        return strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0') !== false ? true : false;
    }

    public static function thumb($thumb, $wh = 200) {
        if ($thumb) {
            return preg_replace('/(\.\w+)$/', 'x' . $wh . '$1', $thumb);
        }
        if ($wh == 200) {
            return 'no_img.png';
        }
        return 'no_img_big.png';
    }

    public static function pathInfo($filePath) {
        $pathParts = array();
        $pathParts['DIRNAME'] = rtrim(substr($filePath, 0, strrpos($filePath, '/')), '/') . '/';
        $pathParts['BASENAME'] = ltrim(substr($filePath, strrpos($filePath, '/')), '/');
        $pathParts['EXTENSION'] = substr(strrchr($filePath, '.'), 1);
        $pathParts['FILENAME'] = ltrim(substr($pathParts['BASENAME'], 0, strrpos($pathParts['BASENAME'], '.')), '/');
        return $pathParts;
    }

    public static function age($birthday, $currentTime = null) {
        if ($birthday > 0) {
            $currentTime = is_null($currentTime) ? time() : $currentTime;
            return date('Y', $currentTime) - date('Y', $birthday);
        }
        return '-';
    }

    public static function readJsonVersion($type = 'framework') {
        $_path =  array(
            'framework' => 'vendor/phoenix/framework/composer.json',
            'cms' => 'composer.json'
        );
        return json_decode(file_get_contents(ROOT_PATH . $_path[$type]), true);
    }
    
    /**
     * 获取分页
     * @param $rows 记录总数
     * @param $uri url地址
     * @param int $page 当前页码
     * @param int $num 指定分页截断
     * @param int $isHtmlPage 动静态分页切换
     * @param bool $control 是否显示跳转
     * @return string
     */
    public static function getPages($rows, $uri, $page = 1, $num = 10, $isHtmlPage = 0, $control = false) {
        if ($rows && $rows > $num) {
            $_isHtml = intval($isHtmlPage);
            if (is_array($uri)) {//如果支持动静态切换
                $uri = $uri[$_isHtml];
            }
            $_pages = ceil($rows / $num);
            if ($page < 1)
                $page = 1;
            else if ($page > $_pages)
                $page = $_pages;
            
            if ($page <= 1) {
                $_startLink = '[首页]';
                $_prevLink = '[前一页]';
            } else {
                $_prev = $page - 1;
                $_startLink = '<a href="' . $uri . '">首页</a>';
                $_prevLink = '<a href="'
                    . ($_prev == 1 ? $uri :
                        ($_isHtml ? str_replace('.html', '_' . $_prev . '.html', $uri) : $uri . '/' . $_prev)
                    )
                    . '">[前一页]</a>';
            }
            if ($page >= $_pages) {
                $_nextLink = '[下一页]';
                $_endLink = '[尾页]';
            } else {
                $_next = $page + 1;
                $_nextLink = '<a href="'
                    . ($_isHtml ? str_replace('.html', '_' . $_next . '.html', $uri) : $uri . '/' . $_next)
                    . '">[下一页]</a>';
                $_endLink = '<a href="'
                    . ($_isHtml ? str_replace('.html', '_' . $_pages . '.html', $uri) : $uri . '/' . $_pages)
                    . '">[尾页]</a>';
            }
            
            $_b = $page - 5;
            $_e = $page + 5;
            if ($_b < 1) {
                $_e = $_e - $_b;
                $_b = 1;
            }
            if ($_e > $_pages) {
                $_b = $_b - ($_e - $_pages);
                $_e = $_pages;
            }
            $_numLink = '';
            for ($i = $_b; $i <= $_e; $i++) {
                if ($i < 1 || $i > $_pages)
                    continue;
                if ($i != $page)
                    $_numLink .= ' <a href="'
                        . ($i == 1 ? $uri :
                            ($_isHtml ? str_replace('.html', '_' . $i . '.html', $uri) : $uri . '/' . $i)
                        )
                        . '">' . $i . '</a>';
                else
                    $_numLink .= ' <span>' . $i . '</span> ';
            }
            
            $_total = '当前第 ' . $page . ' 页/共 ' . $_pages . ' 页　';
            $_extra = '';
            if ($control == true) {
                $_extra .= '　<form class="frm-jump-page" onsubmit="return false;">'
                    . '跳转到 <input type="text" class="ipt-page-jump"'
                    . ' id="ipt-page-jump" value="'
                    . $page . '">'
                    . ' <input type="submit" class="btn-page-go"'
                    . ' value="GO" onclick="Tools.jumpPage(\''
                    . $uri . '\', ' . $_isHtml . ');"></form>';
            }
            return '<div class="page-control">' . $_total . ' ' . $_startLink
            . ' ' . $_prevLink . ' ' . $_numLink . ' '
            . $_nextLink . ' ' . $_endLink . ' ' . $_extra . '</div>';
        }
    }
    
    public static function getAjaxPages($rows, $func, $page = 1, $num = 10) {
        if ($rows && $rows > $num) {
            $_pages = ceil($rows / $num);
            if ($page < 1)
                $page = 1;
            else if ($page > $_pages)
                $page = $_pages;
            
            if ($page <= 1) {
                $_startLink = '[首页]';
                $_prevLink = '[前一页]';
            } else {
                $_prev = $page - 1;
                $_startLink = '<a href="javascript:' . $func . '(\'1\');">[首页]</a>';
                $_prevLink = '<a href="javascript:' . $func . '(\'' . $_prev . '\');">[前一页]</a>';
            }
            if ($page >= $_pages) {
                $_nextLink = '[下一页]';
                $_endLink = '[尾页]';
            } else {
                $_next = $page + 1;
                $_nextLink = '<a href="javascript:' . $func . '(\'' . $_next . '\');">[下一页]</a>';
                $_endLink = '<a href="javascript:' . $func . '(\'' . $_pages . '\');">[尾页]</a>';
            }
            
            $_b = $page - 5;
            $_e = $page + 5;
            if ($_b < 1) {
                $_e = $_e - $_b;
                $_b = 1;
            }
            if ($_e > $_pages) {
                $_b = $_b - ($_e - $_pages);
                $_e = $_pages;
            }
            $_numLink = '';
            for ($i = $_b; $i <= $_e; $i++) {
                if ($i < 1 || $i > $_pages)
                    continue;
                if ($i != $page)
                    $_numLink .= ' <a href="javascript:' . $func . '(\'' . $i . '\');">'
                        . $i . '</a>';
                else
                    $_numLink .= ' <span>' . $i . '</span> ';
            }
            
            $_total = '当前第 ' . $page . ' 页/共 ' . $_pages . ' 页　';
            
            return '<div class="page">' . $_total . ' ' . $_startLink
            . ' ' . $_prevLink . ' ' . $_numLink . ' '
            . $_nextLink . ' ' . $_endLink . '</div>';
        }
    }
    
    //以上为系统函数区
    //==================以下为自定义函数区
    public static function getArrayToRadio($name, & $ary, $slt) {
        $_out = '';
        foreach ($ary as $k => $v) {
            $_out .= '<span class="cb"><input type="radio" id="' . $name . '_' . $k
                . '" name="' . $name . '" value="' . $k . '"'
                . (intval($slt) == $k ? ' checked' : '')
                . '><label for="' . $name . '_' . $k . '">' . $v . '</label></span>';
        }
        return $_out;
    }
    
    public static function getArrayToSelect($name, $class, & $ary, $slt) {
        $_out = '<select id="' . $name . '" name="' . $name . '" class="' . $class . '">';
        foreach ($ary as $_k => $_v) {
            $_out .= '<option value="' . $_k . '"'
                . (intval($slt) == $_k ? ' selected' : '')
                . '>' . $_v . '</option>';
        }
        $_out .= '</select>';
        return $_out;
    }
    
    public static function keywordsColor($keywords, $value) {
        if ($keywords)
            return str_replace($keywords, '<span class="red b fs14">' . $keywords . '</span>', $value);
        return $value;
    }

    /**
     *  验证手机号
     */
    public static function isValidMobile($mobile = '') {

        return preg_match('/^1\d{10}$/', $mobile);
    }

    public static function array_sort($arr, $keys, $type='asc'){
        $keysValue = $new_array = array();
        foreach ($arr as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }
        if ($type == 'asc'){
            asort($keysValue);
        } else {
            arsort($keysValue);
        }
        reset($keysValue);
        foreach ($keysValue as $k => $v){
            $new_array[$k] = $arr[$k];
        }
        return $new_array;
    }
    
}
