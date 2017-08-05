<?php

namespace App\Handler\Admin\Structure\Category;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;
use App\Service\Archives;
use App\Service\Templates;
use App\Service\UPFile;
use App\Tools\Html;
use Phoenix\Support\MsgHelper;
use Exception;

/**
 * 添加
 */
class Add extends AbstractCommon {

    protected function __Inject($db, $cache, $session, UPFile $upFile,
                                Archives $serviceArchives = null, Templates $toolsTemplates = null) {}

    public function processRequest(Array & $context) {
        try {
            $this->db->beginTransaction();

            $this->_pushSetting();
            $this->_processingParameters();
            $this->_getIdTree();

            //		if ($_POST['is_part'] == 1 && empty($_POST['seo_title']))
            //			$_POST['seo_title'] = $_POST['category_name'] . '_' . $this->cfg['title'];
            //$this->db->debug();
            if ($_POST['list_dir'] != '' && $_POST['is_part'] < 2) {
                $_POST['list_dir'] = $this->_filterToSeoUrl($_POST['list_dir']);
            }
            $_POST['category_name'] = Html::getTextToHtml($_POST['category_name']);
            $_POST['category_name_english'] = Html::getTextToHtml($_POST['category_name_english']);
            $_POST['level'] = $_POST['level'] + 1;
            $_POST['nav_type'] = ($_POST['level'] > 1 && $_POST['nav_type'] != 1 ? 2 : $_POST['nav_type']);
            $_POST['master_id'] = $this->session->adminUser['id'];
            $_POST['add_date'] = time();
            $_POST['release_date'] = time();

//            $this->db->debug();
            $_identity = $this->db->table('`#@__@category`')
                ->row(array(
                    '`category_name`' => '?',
                    '`category_name_english`' => '?',
                    '`landscape`' => '?',                  
                    '`level`' => '?',
                    '`id_tree`' => '?',
                    '`parent_id`' => '?',
                    '`channel_type`' => '?',
                    '`is_part`' => '?',
                    '`list_dir`' => '?',
                    '`target`' => '?',
                    '`sort`' => '?',
                    '`home_sort`' => '?',
                    '`nav_type`' => '?',
                    '`is_display`' => '?',
                    '`master_id`' => '?',
                    '`add_date`' => '?',
                    '`release_date`' => '?',
                    '`language`' => '?'
                ))
                ->bind($_POST)
                ->save();

            if ($_POST['level'] == 1) {
                $_POST['root_id'] = $_identity;
            }

            $_return = $this->db->table('`#@__@category`')
                ->row(array(
                    '`root_id`' => '?',
                    '`id_tree`' => '?'
                ))
                ->where('`category_id` = ?')
                ->bind(array(
                    $_POST['root_id'],
                    $_POST['id_tree'] . str_pad($_identity, 3, '0', STR_PAD_LEFT) . '.',
                    $_identity
                ))
                ->update();

            $this->db->table('`#@__@category_substance`')
                ->row(array(
                    '`category_id`' => '?',
                    '`seo_title`' => '?',
                    '`seo_keywords`' => '?',
                    '`seo_description`' => '?',
                    '`substance`' => '?',
                    '`substance_home`' => '?'
                ))
                ->bind(array(
                    $_identity,
                    $_POST['seo_title'],
                    $_POST['seo_keywords'],
                    $_POST['seo_description'],
                    $this->serviceArchives->addAnchorText($_POST['substance']),
                    $this->serviceArchives->addAnchorText($_POST['substance_home'])
                ))
                ->save();


            $this->_createImg('landscape', false);

            if ((int) $this->cfg['is_html_page'] > 0) {
                $this->toolsTemplates->createColumn($_identity);
            } else {
                $this->cache->delete(array('aryCategoryDataView', 'footerCategoryNavigation'));
            }

            $this->db->commit();
            echo(MsgHelper::json($_return ? 'SUCCESS' : 'DB_ERROR'));
        } catch (Exception $e) {

            $this->db->rollBack();
            echo(MsgHelper::json('DB_ERROR'));
        }
    }

}
