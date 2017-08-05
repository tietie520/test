<div class="banner-wrap">
    <?php
    if ($this->aryCategoryDataView[$this->rootId]['landscape'] != ''):
        echo App\Tools\UrlHelper::getUploadImg($this->aryCategoryDataView[$this->rootId]['landscape'], 'l');
    else:
        echo '<img src="' . $this->__STATIC__ . 'images/about/banner.jpg">';
    endif;
    ?>
</div>

