<?php
require $this->__RAD__ . 'component/global/head.php';
?>
<body>
<?php
require $this->__RAD__ . 'component/global/header.php';
require $this->__RAD__ . 'component/global/landscape.php';
?>
<div class="news wrapper">
    <?php
    require $this->__RAD__ . 'component/global/menu.php';
    ?>
</div>
<div class="news wrapper mt40">
    <div class="news-cont">
        <ul class="cont-item fn-clear">
            <?php
            if ($this->currentListRs):
                $_i = 0;
                foreach ($this->currentListRs as $_v1):
                    $_tmpLink = App\Tools\UrlHelper::getPageUrl($_v1);
                    ?>
                    <li class="item-ui fn-clear<?= $_i == 0 ? ' item-first' : '' ?>">
                        <a class="item-news" href="<?= $_tmpLink ?>">
                            <span class="ui-img">
                                <img src="<?= $this->__CDN__ . 'pics/s/' . $_v1->cover ?>">
                            </span>
                            <span class="news-txt">
                                <span title="<?= $_v1->title ?>" class="txt-title">
                                    <?= App\Tools\Html::getLenStr($_v1->title, 12) ?>
                                </span>
                                <span class="txt-border"></span>
                                <span class="txt-main"><?= App\Tools\Html::getLenStr($_v1->synopsis, 25) ?></span>
                                <span class="btn-more">more></span>
                            </span>
                        </a>
                    </li>
                    <?php
                $_i++;
                endforeach;
            endif;
            ?>
        </ul>
    </div>
    <div class="page-list">
        <?php
        require $this->__RAD__ . 'component/global/getPages.php';
        ?>
    </div>
</div>
<?php
require $this->__RAD__ . 'component/global/footer.php';
?>
</body>
</html>