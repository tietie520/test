<?php
require $this->__RAD__ . 'component/global/head.php';
?>
<body>
<?php
require $this->__RAD__ . 'component/global/header.php';
require $this->__RAD__ . 'component/global/landscape.php';
?>
<div class="po-box">
    <div class="news-detail wrapper">
        <div class="news-title fn-clear">
            <a class="news-btn fn-left fn-clear" href="javascript:history.back()">
                <span class="icon-back icon fn-left"> </span>
             <span class="txt fn-left">
                <span class="cn">返回上级</span>
                <span class="en">Back to higher level</span>
           </span>
            </a>
            <div class="cont-title">
                <span class="title-cn"><?= $this->title ?></span>
                <div>
                    <span class="time txt">Time：<?= date('Y-m-d', strtotime($this->releaseDate)) ?></span>
                    <span class="view-num txt">浏览次数：<?= $this->viewCount ?></span>
                </div>
            </div>
        </div>
        <div class="main-content">
            <div class="content-cont main-txt">
                <?= App\Tools\Html::outputToText($this->substance) ?>
            </div>
            <div class="detail-info">
                <span class="info-title">联系售后</span>
                <div class="info-main">电话：021-61918069</div>
                <div class="info-main">
                    邮箱：<a href="mailto:ploughuav-mkt@hitrobotgroup.com">ploughuav-mkt@hitrobotgroup.com</a>
                </div>
            </div>
        </div>
    </div>
    <?php
    require $this->__RAD__ . 'component/global/footer.php';
    ?>
</div>
<script>
//    Tools.archives.view('<?//= $this->currentArchivesId ?>//');
</script>
</body>
</html>
