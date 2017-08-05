<?php
require $this->__RAD__ . 'component/global/head.php';
?>
<body>
<?php
require $this->__RAD__ . 'component/global/header.php';
require $this->__RAD__ . 'component/global/landscape.php';
?>
<div class="contact wrapper">
    <?php
    require $this->__RAD__ . 'component/global/menu.php';
    ?>
</div>
<?php

if (intval($this->isPart) == 1) {
    echo $this->substance;
}
require $this->__RAD__ . 'component/global/footer.php';
?>
</body>
</html>