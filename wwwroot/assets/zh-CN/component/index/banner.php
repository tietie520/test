<?php foreach($this->aryAd as $_ad) :?>
    <li class="swiper-slide">
        <a target="<?= $_ad->target ? '_self' : '_blank' ?>" <?= $_ad->ad_url ? 'href="' . $_ad->ad_url . '""' : '' ?>>
            <img src="<?= $this->__CDN__ ?>pics/l/<?= $_ad->ad_img_bg ?>">
        </a>
    </li>
<?php endforeach ?>