<?php
require $this->__RAD__ . 'component/global/head.php';
?>
<body>
<?php
require $this->__RAD__ . 'component/global/header.php';
require $this->__RAD__ . 'component/global/landscape.php';
?>
<div class="link wrapper">
    <?php
    require $this->__RAD__ . 'component/global/menu.php';
    ?>
    <ul class="link-icon fn-clear">
        <?php
        if ($this->footerLink) :
            foreach ($this->footerLink as $m) :
                ?>
                <li class="icon-item">
                    <a target="<?= $m->link_type ? '_self' : '_blank' ?>"
                       href="<?= $m->link_url ? $m->link_url : 'javascript:;' ?>" class="item-logo">
                        <img src="<?= $this->__CDN__ . 'pics/s/' . $m->link_cover ?>">
                        <span class="logo-title"></span>
                    </a>
                </li>
                <?php
            endforeach;
        endif;
        ?>
    </ul>
</div>
<?php
require $this->__RAD__ . 'component/global/footer.php';
?>
</body>
</html>
