<?php
require $this->__RAD__ . 'component/global/head.php';
?>
<link rel="stylesheet" href="<?= $this->__STATIC__ ?>css/idangerous.swiper.css">
<body>
<?php
require $this->__RAD__ . 'component/global/header.php';
?>
<div class="banner">
    <div class="banner-cont wrapper">
        <div class="bigimg-wrap">
            <ul class="bigimg-list">
                <?php foreach($this->aryAd as $_ad) :?>
                    <li class="ui-item">
                        <a target="<?= $_ad->target ? '_self' : '_blank' ?>" <?= $_ad->ad_url ? 'href="' . $_ad->ad_url . '""' : '' ?>>
                            <img src="<?= $this->__CDN__ ?>pics/l/<?= $_ad->ad_img_bg ?>">
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
    <div class="banner-tab">
        <div class="smallimg-wrap wrapper">
            <div class="trigger"></div>
            <ul class="smallimg-list fn-clear">
                <?php
                $_i = 0;
                foreach($this->aryAd as $_ad)
                    :?>
                    <li class="bg-item">
                        <a target="<?= $_ad->target ? '_self' : '_blank' ?>" <?= $_ad->ad_url ? 'href="' .
                            $_ad->ad_url . '""' : '' ?>>
                            <img src="<?= $this->__CDN__ ?>pics/s/<?= $_ad->ad_img ?>">
                            <span class="txt<?= $_i == 0 ? ' small-on': '' ?>"><?= $_ad->ad_title ?></span>
                        </a>
                    </li>
                    <?php
                    $_i++;
                endforeach
                ?>
            </ul>
        </div>
    </div>
</div>
<div id="banner">
    <div class="banner-content wrapper">
        <div class="swiper-container">
            <ul class="swiper-wrapper">
                <?php
                require $this->__RAD__ . 'component/index/banner.php';
                ?>
            </ul>
        </div>
        <div class="pagination"></div>
        <a class="prev" href="javascript:void(0)"></a>
        <a class="next" href="javascript:void(0)"></a>
    </div>
</div>
<?php  require $this->__RAD__ . 'component/global/footer.php'; ?>
<script src="<?= $this->__STATIC__ ?>js/idangerous.swiper.min.js"></script>
<script>
    $(function(){
        indexFun.doFun();
    })
</script>
</body>
</html>
