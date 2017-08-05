<?php
require $this->__RAD__ . 'component/global/head.php';
?>
<body>
<?php
require $this->__RAD__ . 'component/global/header.php';
require $this->__RAD__ . 'component/global/landscape.php';
?>
<div class="download wrapper">
    <?php
    require $this->__RAD__ . 'component/global/menu.php';
    ?>
    <div class="download-main">
        <ul class="ui-main">
            <?php
            if ($this->currentListRs):
                $_i = 0;
                foreach ($this->currentListRs as $m):
                    $_tmpLink = App\Tools\UrlHelper::getPageUrl($m);
                    ?>
                    <li class="main-item">
                        <a class="main-cont fn-clear" href="<?= $_tmpLink ?>">
                            <span class="fn-left cont-txt"><?= $m->title ?></span>
                            <span class="fn-right cont-load service-load">了解更多</span>
                        </a>
                    </li>
                    <?php
                endforeach;
            endif;
            ?>
        </ul>
    </div>
    <div class="page-list wrapper">
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