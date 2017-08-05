<?php

namespace App\Handler\Admin\Structure\Archives;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;
use Phoenix\Support\MsgHelper;
use Exception;

/**
 * 文档删除
 */
class Delete extends AbstractCommon {

    public function processRequest(Array & $context) {
        try {
            $this->db->beginTransaction();

            $this->_pushSetting();
            $this->cache->delete($this->setting['aryArchivesDeleteCacheBindId']);
            if ($this->cfg['is_html_page'] > 0) {
                $_ary = explode(',', $_POST['id']);
                foreach ($_ary as $_id) {
                    //$_POST['db']->debug();
                    $_rs = $this->db->select('a.`seo_url`, a.`add_date`, a.`language`, c2.`list_dir`')
                        ->table('`#@__@archives` a, `#@__@category` c, `#@__@category` c2')
                        ->where('a.`archives_id` = ? AND a.`category_id` = c.`category_id` AND c.`root_id` = c2.`category_id`')
                        ->bind(array($_id))->find();
                    $_htmlPath = ROOT_PATH . $context['__LANGUAGE_CONFIG__'][$_rs->language]
                        . DIRECTORY_SEPARATOR . trim($_rs->list_dir, '/')
                        . DIRECTORY_SEPARATOR;
                    if ($_rs->seo_url != '') {
                        $_htmlPath .= $_rs->seo_url;
                    } else {
                        $_htmlPath .= date('Ymd', $_rs->add_date) . DIRECTORY_SEPARATOR . $_id;
                    }
                    $_htmlPath .= '.html';
                    if (is_file($_htmlPath))
                        unlink($_htmlPath);
                }
            }
            echo($this->_publicDeleteFieldByPostItem($_POST['id'],
                array('`#@__@archives`', '`#@__@archives_substance`'),
                'archives_id', 'cover'));

            $this->db->commit();
        } catch (Exception $e) {

            $this->db->rollBack();
            echo(MsgHelper::json('DB_ERROR'));
        }
    }

}
