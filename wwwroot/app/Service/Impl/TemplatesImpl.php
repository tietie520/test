<?php

namespace App\Service\Impl;

if (!defined('IN_PX'))
    exit;

use App\Service\Templates;
use App\Service\Archives;
use App\Tools\Auxi;
use Phoenix\Routing\Controller;
use Phoenix\Http\RequestMethod;
use Phoenix\Exception\FormatException;use Phoenix\Support\File;
use Phoenix\Log\Log4p as logger;
use Exception;

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
class TemplatesImpl implements Templates {

    //先声明，后构造器注入，否则在实例化之后注入类实体
    private function __Service() {}

    private function __Value($setting) {}

    private function __Inject($db, $cache, Archives $servArc) {}

    const VERSION = '1.3.5';

    private $_mappingChannelType;
    private $_data;

    /**
     * 构造器注入
     * @param array $__InjectData
     * @param $setting
     * @param $cache
     */
    public function __construct(Array & $__InjectData, & $setting, $cache) {
        $this->_data = & $__InjectData;
        $this->_data['setting'] = & $setting;
        //删除缓存
        $cache->delete(array('aryCategoryDataView', 'footerCategoryNavigation'));

        $this->_mappingChannelType = $this->_data['setting']['aryChannelTypeMapping'];

        $this->_data['resetAnchorText'] = isset($_POST['resetAnchorText']) ? intval($_POST['resetAnchorText']) : 0;
        $this->_data['deleteHtmlFolder'] = isset($_POST['deleteHtmlFolder']) ? intval($_POST['deleteHtmlFolder']) : 0;
        $this->_data['createSitemap'] = isset($_POST['createSitemap']) ? intval($_POST['createSitemap']) : 0;
    }

    /**
     * @return int
     * @throws FormatException
     */
    public function createHomepage() {
        try {
            return $this->createHtml(ROOT_PATH . 'index.html');
        } catch (Exception $e) {
            throw new FormatException('create index page error.', '0x00002101');
        }
    }

    /**
     * 站点地图生成，创建所有语言的sitemap
     */
    public function createSitemap() {
        foreach ($this->_data['__LANGUAGE_CONFIG__'] as $_languageId => $_package) {
            $this->createHtml(ROOT_PATH . ($_languageId > 0 ?
                    $_package . DIRECTORY_SEPARATOR : '')
                . 'sitemap.html', $_package, array('sitemap'));
        }
    }

    /**
     * 创建html文件
     * @param $file
     * @param null $package
     * @param array $paths
     * @return int
     */
    public function createHtml($file, $package = null, $paths = array()) {
        logger::debug($package);
        $this->_data['__PACKAGE__'] = $package;
        $this->_data['__PATHS__'] = $paths;
        $this->_data['__METHOD__'] = RequestMethod::GET; //生成的页面模拟get访问
        ob_start();
        Controller::start($this->_data);
        return file_put_contents($file, ob_get_clean(), LOCK_EX);
    }

    /**
     * 创建所有栏目
     * @param int $categoryId
     * @param null $limit
     * @return bool
     */
    public function createColumn($categoryId = 0, $limit = null) {
        try {
            if ($this->createArchivesDir()) {
                $this->createCategory($categoryId, $limit);
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 创建文档html
     * @param null $id 指定生成文档id
     * @param string $mode 生成类别下所有文档 one 生成单个文档
     * @param null $limit
     */
    public function createArchives($id = null, $mode = 'type', $limit = null) {
        if ($this->createArchivesDir()) {
            //die(var_dump($id));
            if (!isset($this->_data['aryCategoryDataView'])) {
                $this->_data['aryCategoryDataView'] = $this->servArc->getAryCategoryDataView();
            }
            $this->_data['isCreateColumn'] = false;
            $_where = '0 = 0';
            $_bind = array();
            if (!is_null($id)) {
                if (strcasecmp('type', $mode) == 0) {
                    $_where = ' AND a.`category_id` = ?';
                } else {
                    $_where = ' AND a.`archives_id` = ?';
                }
                array_push($_bind, $id);
            }
            $_where .= ' AND a.`archives_id` = ars.`archives_id` AND a.`is_display` = 1 AND a.`category_id` = c.`category_id` AND c.`is_display` = 1';

//            $this->db->debug();
            $_rs = $this->db->select('a.*, ars.*, c.`category_name`, c.`level`, c.`id_tree`, c.`parent_id`, c.`root_id`, c.`is_part`, c.`channel_type`, c.`list_dir`, c.`language`')
                ->table('`#@__@archives` a, `#@__@archives_substance` ars, `#@__@category` c')
                ->where($_where)
                ->bind($_bind)
                ->order('a.archives_id')
                ->limit($limit)
                ->findAll();
            if ($_rs) {
                foreach ($_rs as $this->_data['pageContentRs']) {
                    $this->servArc->pageContentRs = $this->_data['pageContentRs'];

                    $this->_data['rootListDir'] = $_listDir = $this->_data['aryCategoryDataView'][$this->_data['pageContentRs']->root_id]['list_dir'];

                    $_package = $this->_data['__LANGUAGE_CONFIG__'][$this->_data['pageContentRs']->language];
                    $_listDir = ROOT_PATH . $_package
                        . DIRECTORY_SEPARATOR . trim($_listDir, '/');
                    if (!is_dir($_listDir)) {
                        @mkdir($_listDir, 0777);
                        @chmod($_listDir, 0777);
                    }

                    $_isSeoUrl = $this->_data['pageContentRs']->seo_url != '' ? true : false;
                    if ($_isSeoUrl) {
                        $_listDir .= DIRECTORY_SEPARATOR
                            . $this->_data['pageContentRs']->seo_url . '.html';
                    } else {
//						$_date = date('Ym', $this->_data['pageContentRs']->add_date);
//						$_listDir .= DIRECTORY_SEPARATOR . $_date;
//						if (!is_dir($_listDir))
//							@mkdir($_listDir, 0777) && @chmod($_listDir, 0777);

                        $_listDir .= DIRECTORY_SEPARATOR
                            . $this->_data['pageContentRs']->archives_id . '.html';
                    }

                    $this->_data['__HOMEPAGE__'] = false;
                    $_tmpChannelTypeMapping = $this->_mappingChannelType[$_package][intval($this->_data['pageContentRs']->channel_type)];

                    $_paths = array();
                    if (isset($_tmpChannelTypeMapping[3])) {
                        $_paths = explode('/', $_tmpChannelTypeMapping[3] . '/'
                            . $this->_data['pageContentRs']->archives_id);
                    } else {
                        $_paths = explode('/', $_tmpChannelTypeMapping[1]);
                        array_push(array_pop($_paths), 'detail'); //没有配置则默认使用show
                        array_push($_paths, $this->_data['pageContentRs']->archives_id);
                    }
                    //die(var_dump($this->_data['pageContentRs']->archives_id));
                    $this->createHtml($_listDir, $_package, $_paths);
                }
            }
        }
    }

    private function _getDataViewByIdTree($aryIdTree, $isGetParent = false) {
        //不能在注入时生成，否则在添加修改类别时会获得修改前的值
        if (!isset($this->_data['aryCategoryDataView'])) {
            $this->_data['aryCategoryDataView'] = $this->servArc->getAryCategoryDataView();
        }
        if ($isGetParent) {
            array_pop($aryIdTree);
        }
        $_aryCategoryDataView = $this->_data['aryCategoryDataView'];
        foreach ($aryIdTree as $_id) {
            $_aryCategoryDataView = $_aryCategoryDataView[(int)$_id];
        }
        return $_aryCategoryDataView;
    }

    /**
     * 创建类别下的文件，递归生成
     * @param int $categoryId 生成路径，含递归值
     * @param null $limit 生成级别
     * @return bool
     */
    public function createCategory($categoryId = 0, $limit = null) {
        $this->_data['isCreateColumn'] = true;
        //$this->db->debug();
        //var_dump($categoryLevel);
        $_bind = array();
        $_where = '0 = 0';
        if ($categoryId > 0) {
            $_where = ' AND a.`category_id` = ?';
            array_push($_bind, intval($categoryId));
        }

        $_where .= ' AND a.`is_part` < 2 AND a.`is_display` = 1 AND a.`category_id` = cs.`category_id`';
        $_tmpChannelTypeMapping = null;
        //$this->db->debug();
        $_rs = $this->db->select('a.*, cs.*')
            ->table('`#@__@category` a, `#@__@category_substance` cs')
            ->where($_where)
            ->bind($_bind)
            ->order('a.sort', 'ASC')
            ->limit($limit)
            ->findAll();
        if ($_rs) {
            foreach ($_rs as $this->_data['currentCategoryRs']) {
                $this->servArc->currentCategoryRs = $this->_data['currentCategoryRs'];

                $_aryIdTree = explode('.', trim($this->_data['currentCategoryRs']->id_tree, '.')); //idTree
                $_selfDataView = $this->_getDataViewByIdTree($_aryIdTree); //获取自身的view
                $_i = 0;
                $_categoryId = $this->_data['currentCategoryRs']->category_id;
                $_selfListDir = $_selfDataView['list_dir'];

                if ($_selfDataView['level'] > 1) {
                    $_i = $_selfDataView['sort'];
                    $_parentDataView = $this->_getDataViewByIdTree($_aryIdTree, true); //获取父节点视图
                    //如果自身为index但父目录是单页频道页，则应该显示list_id
                    if ($_i == 0 && $_parentDataView['is_part'] == 1) {
                        ++$_i;
                    }

                    $_tmpChildTypeIdListDir = Auxi::getChildTypeIdListDir($_selfDataView, $_categoryId);
                    $_categoryId = $_tmpChildTypeIdListDir[0];
                    $_selfListDir = $_tmpChildTypeIdListDir[1];
                    unset($_tmpChildTypeIdListDir);

                    if ($_i == 0 && $_selfDataView['level'] > 2 && !Auxi::isDataViewIndexId(
                            $this->_data['aryCategoryDataView'][$_selfDataView['root_id']],
                            $_categoryId, $_selfDataView['level'])
                    ) {
                        ++$_i;
                    }//2级以后都是以list_id显示
                }

                $this->_data['rootListDir'] = trim($this->_data['aryCategoryDataView'][$_selfDataView['root_id']]['list_dir'], '/');
                $_package = $this->_data['__LANGUAGE_CONFIG__'][$this->_data['currentCategoryRs']->language];
                $_dir = ROOT_PATH . $_package
                    . DIRECTORY_SEPARATOR
                    . $this->_data['rootListDir'] . DIRECTORY_SEPARATOR;
                if (!is_dir($_dir)) {
                    @mkdir($_dir, 0777);
                    @chmod($_dir, 0777);
                }
                //$this->db->debug();
                //读取子类数量
                $_childrenTotal = intval($this->db->table('`#@__@category`')
                    ->where('`parent_id` = ?')
                    ->bind(array($this->_data['currentCategoryRs']->category_id))
                    ->count());
                //如果没有了子分类[ 这个类别单页不生成 ] 或者
                //单页同时又含有子类[ 将子类集中在一个页面展示 ]
                if ($_childrenTotal >= 0) {
                    $_fileName = Auxi::getArchivesListName($_i, $_categoryId, $_selfListDir);
                    $_tmpFile = $_file = $_dir . $_fileName;

                    $_tmpChannelTypeMapping = $this->_mappingChannelType[$_package][intval($this->_data['currentCategoryRs']->channel_type)];
                    $_paths = explode('/', $_tmpChannelTypeMapping[1]);
                    array_push($_paths, $_categoryId);
                    $_tmpPaths = $_paths;
                    if ($this->_data['currentCategoryRs']->is_part == 0) { //列表栏目
                        if ($_childrenTotal == 0) {
                            $_pages = 0;
                            $this->_data['currentCategoryTotal'] = intval($this->db->table('`#@__@archives`')
                                ->where('`category_id` = ? AND `is_display` = 1')
                                ->bind(array($this->_data['currentCategoryRs']->category_id))
                                ->count());
                            $_pages = ceil($this->_data['currentCategoryTotal'] / $_tmpChannelTypeMapping[2]);

                            $this->servArc->currentCategoryTotal = $this->_data['currentCategoryTotal'];

                            $this->_data['fileName'] = $_fileName; //用于分页
                            for ($_page = 1; $_page <= $_pages; $_page++) {
                                if ($_page > 1) {
                                    $_tmpFile = str_replace('.html', '_' . $_page . '.html', $_file);
                                }
                                $_paths = $_tmpPaths;
                                array_push($_paths, $_page);
                                $this->createHtml($_tmpFile, $_package, $_paths);
                            }
                        }
                        continue;
                    }
                    $this->createHtml($_tmpFile, $_package, $_paths);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * 创建生成文档的根路径，根据配置文件
     * @return bool
     */
    public function createArchivesDir() {
        try {
            foreach ($this->_data['__LANGUAGE_CONFIG__'] as $_dir) {
                if (!is_dir(ROOT_PATH . $_dir)) {
                    @mkdir(ROOT_PATH . $_dir, 0777);
                    @chmod(ROOT_PATH . $_dir, 0777);
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 创建生成文档的根路径，根据配置文件
     * @return bool
     */
    public function deleteArchivesDir() {
        try {
            foreach ($this->_data['__LANGUAGE_CONFIG__'] as $_dir) {
                File::rmdir($_dir, ROOT_PATH);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function createByStep() {
        $_aryCreateInfo = explode('-', $_POST['createInfo']);
        if ($this->_data['deleteHtmlFolder'] > 0) {//如果设置了则删除目录
            $this->deleteArchivesDir();
        }
        switch (intval($_aryCreateInfo[1])) {
            case 0:
                //生成首页
                $this->createHomepage();
                $_aryCreateInfo[1] = $_aryCreateInfo[0] == 3 ? 1 : 3;
                break;
            case 1:
                if ($_aryCreateInfo[3] == 0) {
                    $_aryCreateInfo[3] = intval($this->db->table('`#@__@category`')
                        ->where('`is_part` < 2 AND `is_display` = 1')
                        ->count());
                }

                if ($_aryCreateInfo[2] == 0) {
                    $_aryCreateInfo[2] = 1;
                }
                $_limit = $_aryCreateInfo[4] * ($_aryCreateInfo[2] - 1) . ' , ' . $_aryCreateInfo[4];
                $_pages = ceil($_aryCreateInfo[3] / $_aryCreateInfo[4]);
                if ($_aryCreateInfo[2] <= $_pages) {
                    $this->createColumn(0, $_limit);
                    $_aryCreateInfo[1] = 1;
                    $_aryCreateInfo[2]++;
                }
                if ($_aryCreateInfo[2] > $_pages) {
                    $_aryCreateInfo[1] = $_aryCreateInfo[0] == 3 ? 2 : 3; //步进到生成文档
                    $_aryCreateInfo[2] = 0; //页码清零
                    $_aryCreateInfo[3] = 0; //总数清零
                }
                break;
            case 2:
                if ($_aryCreateInfo[3] == 0) {
                    $_aryCreateInfo[3] = intval($this->db->table('`#@__@archives`')
                        ->where('`is_display` = 1')
                        ->count());
                }

                if ($_aryCreateInfo[2] == 0) {
                    $_aryCreateInfo[2] = 1;
                }
                $_limit = $_aryCreateInfo[4] * ($_aryCreateInfo[2] - 1) . ' , ' . $_aryCreateInfo[4];
                $_pages = ceil($_aryCreateInfo[3] / $_aryCreateInfo[4]);
                if ($_aryCreateInfo[2] <= $_pages) {
                    $this->createArchives(null, 'type', $_limit);
                    $_aryCreateInfo[1] = 2;
                    $_aryCreateInfo[2]++;
                }
                if ($_aryCreateInfo[2] > $_pages) {
                    $_aryCreateInfo[1] = 3;
                    $_aryCreateInfo[2] = 0; //页码清零
                    $_aryCreateInfo[3] = 0; //总数清零
                }
                break;
        }
        if ($this->_data['createSitemap'] > 0) {//如果设置了生成sitemp
            $this->createSitemap();
        }
        return implode('-', $_aryCreateInfo);
    }

}
