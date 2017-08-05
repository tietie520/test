<div class="section foot">
    <div class="footer">
        <span class="footer-txt">
             <?= \App\Tools\Html::outputToText($this->cfg['copyright']) ?>
<!--            Copyright © 2015 XAIRCRAFT Co., Ltd. All Rights Reserved. -->
        </span>
    </div>
</div>
<script src="<?= $this->__STATIC__ ?>js/jquery-1.11.1.min.js"></script>
<script src="<?= $this->__STATIC__ ?>js/app.js"></script>
<script>
    header.doFun();
</script>