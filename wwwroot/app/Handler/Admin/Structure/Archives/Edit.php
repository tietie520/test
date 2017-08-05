<?php

namespace App\Handler\Admin\Structure\Archives;

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
 * 文档修改
 */
class Edit extends AbstractCommon {

    protected function __Inject($db, $cache, $session, UPFile $upFile,
                                Archives $serviceArchives = null, Templates $toolsTemplates = null) {}

    public function processRequest(Array & $context) {
        try {
            $this->db->beginTransaction();

            $this->_pushSetting();
            $this->_processingParameters();

            //$this->db->debug();
            if (!isset($_POST['seo_url'])) {
                $_POST['seo_url'] = $_POST['title'];
            }

            if (preg_match('/^[a-zA-Z0-9\-\s\x{4e00}-\x{9fa5}]*$/u', $_POST['seo_url'])) {
                $_POST['seo_url'] = $this->_filterToSeoUrl($_POST['seo_url']);
            }
            else {
                $_POST['seo_url'] = '';
            }

            //$this->db->debug();
            if ($_POST['seo_url'] != '' && $this->db->table('`#@__@archives`')
                    ->where('seo_url = ? AND (archives_id > ? OR archives_id < ?)')
                    ->bind(array($_POST['seo_url'], $_POST['id'], $_POST['id']))
                    ->exists()) {//不能有相同的seo url
                echo(MsgHelper::json('SEO_URL_IS_EXISTS', 'seo url重复存在'));
                exit;
            }

            $_POST['title'] = Html::getTextToHtml($_POST['title']);
            $_POST['is_home_display'] = isset($_POST['is_home_display']) ? intval($_POST['is_home_display']) : 0;
            $_POST['view_count'] = isset($_POST['view_count']) ? intval($_POST['view_count']) : 0;
            $_POST['archives_id'] = $_POST['id'];

            //$this->db->debug();
            $_return = $this->db->table('`#@__@archives`')
                ->row(array(
                    '`category_id`' => '?',
                    '`title`' => '?',
                    '`title_english`' => '?',
                    '`seo_url`' => '?',
                    '`synopsis`' => '?',
                    '`sort`' => '?',
                    '`cover`' => '?',
                    '`attachment`' => '?',
                    '`is_home_display`' => '?',
                    '`view_count`' => '?',
                    '`seo_title`' => '?',
                    '`seo_keywords`' => '?',
                    '`seo_description`' => '?',
                    '`release_date`' => '?',
                    '`video_url`' => '?',
                    '`language`' => '?'
                ))
                ->where('`archives_id` = ?')
                ->bind($_POST)
                ->update();

            $_return += $this->db->table('`#@__@archives_substance`')
                ->row(array(
                    '`substance`' => '?'
                ))
                ->where('`archives_id` = ?')
                ->bind(array(
                    $this->serviceArchives->addAnchorText($_POST['substance']),
                    $_POST['archives_id']
                ))
                ->update();

            if (isset($_POST['archives_tags'])) {
                $_return += $this->_updateTags($_POST['archives_tags'], $_POST['archives_id']);
            }

            if ($_return > 0) {
                $this->cache->delete($this->setting['aryArchivesDeleteCacheBindId']);
                $this->_createImg('cover');

                if ((int) $this->cfg['is_html_page'] > 0) {
                    $this->toolsTemplates->createColumn($_POST['category_id']);
                    $this->toolsTemplates->createArchives($_POST['id'], 'archives');
                }
            }

            $this->db->commit();

            echo(MsgHelper::json($_return ? 'SUCCESS' : ($_return == 0 ? 'NO_CHANGES' : 'DB_ERROR')));
        } catch (Exception $e) {

            $this->db->rollBack();
            echo(MsgHelper::json('DB_ERROR'));
        }
    }

}
