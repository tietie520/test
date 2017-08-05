<?php

namespace App\ZhCN\Interceptor;

if (!defined('IN_PX'))
    exit;

use Phoenix\Interceptor\AbstractAdapter;
use App\Tools\Html;
use Phoenix\Support\Purifier;

/**
 * 拦截赋值page seo
 */
class SetPageTitle extends AbstractAdapter {

    public function preHandle(Array & $context) {
        $context['pageSeoTitle'] = null;
        $context['pageSeoDescription'] = null;
        $context['pageSeoKeywords'] = null;
        $context['cfg'] = $this->cfg;
        if (count($_GET) > 0) {
            foreach ($_GET as $_k => $_v) {
                $_GET[$_k] = Purifier::html($_v);
            }
        }
        if (count($_POST) > 0) {
            foreach ($_POST as $_k => $_v) {
                $_POST[$_k] = Html::getTextToHtml(Purifier::html($_v));
            }
        }
        return true;
    }

    public function postHandle(Array & $context) {
        if (!isset($context['pageSeoTitle'])) {
            $context['pageSeoTitle'] = $this->cfg['title'];
        }
        if ($context['__HOMEPAGE__'] && !isset($context['pageSeoDescription'])) {
            $context['pageSeoDescription'] = $this->cfg['description'];
        }
        if ($context['__HOMEPAGE__'] && !isset($context['pageSeoKeywords'])) {
            $context['pageSeoKeywords'] = $this->cfg['keywords'];
        }
        if (isset($context['pageSeoDescription']) && strlen($context['pageSeoDescription']) > 0) {
            $context['pageSeoDescription'] = '<meta name="description" content="' . $context['pageSeoDescription'] . '">' . "\n";
        }
        if (isset($context['pageSeoKeywords']) && strlen($context['pageSeoKeywords']) > 0) {
            $context['pageSeoKeywords'] = '<meta name="keywords" content="' . $context['pageSeoKeywords'] . '">' . "\n";
        }
        return true;
    }

}
