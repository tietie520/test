<?php

namespace App\ZhCN;

if (!defined('IN_PX'))
    exit;

use App\Service;
use App\Repository;
use App\Tools\Auxi;
use Phoenix\Log\Log4p as logger;

/**
 * 首页
 */
class Index {

    private function __Controller() {}

    private function __Value($__PACKAGE__, $__ROOT__, $__RM__, $setting, $cfg, $__LANGUAGE_ID__) {}

    protected function __Inject($session, Service\Archives $servArc, Repository\Archives $repoArc,
                                Repository\Category $category) {}

    public function index() {
        if ($this->servArc->chkCategoryDataView()) {
            $_model['aryCategoryDataView'] = $this->servArc->aryCategoryDataView;
            $_model['aryAd'] = $this->repoArc->getAd($this->__LANGUAGE_ID__);
            $_model['contactHash'] = md5(time() . 'zy_contact_hash');
//            $this->session->contactHash = array('hash' => $_model['contactHash'], 'time' => 0);

            //查出每个栏目下首页显示的内容
            foreach ($_model['aryCategoryDataView'] as $_k => $_v) {
                foreach ($_model['aryCategoryDataView'][$_k] as $_k2 => $_v2) {
                    if (isset($_v2['level']) && intval($_v2['level']) == 2 && $_v2['is_home_display'] && $_v2['language'] == $this->__LANGUAGE_ID__) {
                        switch (intval($_v2['channel_type'])) {
                            case 2: //列表
                            case 12:
                            case 13:
                                $_limit = isset($this->setting['aryChannelTypeMapping'][$this->__PACKAGE__][$_v2['channel_type']][4]) ?
                                    $this->setting['aryChannelTypeMapping'][$this->__PACKAGE__][$_v2['channel_type']][4] : 1;
                                $_model['rs'][$_k2] = $this->repoArc->findHomeArchives($_k2, 0, $_limit, true);
                                break;
                            case 3: //单页
                            case 5:
                                $_model['rs'][$_k2] = $this->category->findHomeCategory($_k2);
                                break;
                            case 14:
                                $_model['footerLink'] = $this->repoArc->getFooterLink($this->__LANGUAGE_ID__, true);
                                break;
                        }
                        $_model['rs'][$_k2]['id_tree'] = $_v2['id_tree'];
                        $_model['rs'][$_k2]['is_part'] = $_v2['is_part'];
                        $_model['rs'][$_k2]['home_sort'] = $_v2['home_sort'];
                        $_model['rs'][$_k2]['channel_type'] = $_v2['channel_type'];
                        $_model['rs'][$_k2]['category_name'] = $_v2['category_name'];
                        $_model['rs'][$_k2]['category_name_english'] = $_v2['category_name_english'];
                        $_model['rs'][$_k2]['list_dir'] = $_v2['list_dir'];
                        $_model['rs'][$_k2]['substance_home'] = $_v2['substance_home'];
                    }
                }
            }

//            $_model['rs'] = Auxi::array_sort($_model['rs'], 'home_sort');
            //首页底部链接
            return array (
                'model' => $_model,
                'view' => true
            );
        }
        return 404;
    }

    public function category($__Route = array('/*/{aliasId}/{page:\d*}', '/category/{aliasId}/{page}')) {
        if (!is_null(($_model = $this->servArc->getCategoryRs($this->aliasId, true)))) {
            $_model['aliasId'] = $this->aliasId;
            $_model['aryCategoryDataView'] = $this->servArc->aryCategoryDataView;

            if ($_model['channelType'] == 14) {
                $_model['footerLink'] = $this->repoArc->getFooterLink($this->__LANGUAGE_ID__); //底部链接
            }

            if (!isset($this->page)) {
                $this->page = 1;
            }
            $_model['currentPage'] = $this->page;

            //根目录下的总条数
            $_model['rootCategoryTotal'] = $this->servArc->findAllRootCount($_model['rootId']);
            if (!$_model['isPart'] && $_model['currentPage'] > ceil($_model['rootCategoryTotal'] / $_model['currentPageSize'])) {
                return 404;
            }
            if ($_model['categoryLevel'] == 1 && in_array($_model['channelType'], array(0, 1, 2, 4))) { //列表
                $_model['currentCategoryTotal'] = $_model['rootCategoryTotal'];
                $_model['currentListRs'] = $this->servArc->findAllRoot($_model['categoryId'],
                    $_model['currentPageSize'] * ($_model['currentPage'] - 1),
                    $_model['currentPageSize']);
            } else {
                $_model['currentListRs'] = $this->servArc->findAll($_model['categoryId'],
                    $_model['currentPageSize'] * ($_model['currentPage'] - 1),
                    $_model['currentPageSize']);
            }

            return array(
                'model' => $_model,
                'view' => 'archives/'
                    . $this->setting['aryChannelTypeMapping'][$this->__PACKAGE__][$_model['channelType']][1]
            );
        }
        return 404;
    }

    public function channel($__Route = '/{aliasId}/{page:\d*}') {
        if (!is_null(($_model = $this->servArc->getCategoryRs($this->aliasId, true)))) {
            $_model['aliasId'] = $this->aliasId;
            $_model['aryCategoryDataView'] = $this->servArc->aryCategoryDataView;

            $_aryChannelList = array();
            foreach ($_model['aryCategoryDataView'][$_model['rootId']] as $_k2 => $_v2) {
                if ($_k2 > 0) {
                    $_aryChannelList[$_k2] = $this->repoArc->findAll($_k2, 10);
                }
            }

            $_model['aryChannelList'] = $_aryChannelList;

            return array(
                'model' => $_model,
                'view' => 'archives/'
                    . $this->setting['aryChannelTypeMapping'][$this->__PACKAGE__][$_model['channelType']][1]
            );
        }
        return 404;
    }

    public function detail($__Route = '/{id:\d+}') {
        if (!is_null(($_model = $this->servArc->getSubstance($this->id)))) {

            $_model['aryCategoryDataView'] = $this->servArc->aryCategoryDataView;
            return array(
                'model' => $_model,
                'view' => 'archives/'
                    . $this->setting['aryChannelTypeMapping'][$this->__PACKAGE__][$_model['channelType']][3]
            );
        }
        return 404;
    }

}
