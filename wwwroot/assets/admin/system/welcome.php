<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<title><?= $this->cfg['site_name'] . '(' . $this->cfg['softCore'] . $this->framework['version'] . ')' ?></title>
<link href="<?= $this->__ASSETS__ ?>css/default.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" type="text/css" href="<?= $this->__ASSETS__ ?>css/flexigrid.css" />
</head>
<body>
<div id="header">
	<div id="navigation">
		<dl>
			<dt id="go_welcome">
				<span class="l"></span>
				<span class="m"><img src="<?= $this->__ASSETS__ ?>images/system_home.png"> 欢迎页</span>
				<span class="r"></span>
			</dt>
		</dl>
		<?php
        list($_menuTop, $_menuLeft) = App\Admin\Helper::getTopAdminMenu($this->adminMap, $this->session->adminUser['id'], $this->adminPower);
        echo $_menuTop;
        ?>
	</div>
	<div class="top_r">
		<dl>
			<dt><a href="<?= $this->__ROOT__ ?>" target="_blank">网站首页</a></dt>
		</dl>
		<dl id="user_info">
			<dt>用户：<?= $this->session->adminUser['name'] ?></dt>
			<dd>
				<div>
					<ol>
						<li>上次登录：<span class="num"><?= $this->session->adminUser['lastLogDate'] ?></span></li>
						<li>上次IP：<span class="num"><?= $this->session->adminUser['lastLogIP'] ?></span></li>
						<li><a href="javascript:;" id="edit_password">修改密码</a></li>
					</ol>
				</div>
			</dd>
		</dl>
		<dl>
			<dt><a href="<?= $this->__ROOT__ ?>handler/Admin.System.Logout" id="logout">安全退出</a></dt>
		</dl>
	</div>
</div>
<div class="main" id="main">
    <div id="sidebar">
        <?= $_menuLeft ?>
    </div>
    <div id="container" class="ml"></div>
	<div id="toggle-left" data-arr="<">&lt;</div>
	<div id="main_copyright">&copy; 2007-<?= date('Y') ?> All rights reserved. <?= $this->cfg['softCore']. '<br>'. $this->framework['version'] ?></div>
</div>
<div id="main_loading">数据加载中，请稍候...</div>


<script src="<?= $this->__STATIC__ ?>js/jquery-1.11.1.min.js"></script>
<script src="<?= $this->__ASSETS__ ?>js/pxdialog.js"></script>
<script src="<?= $this->__ASSETS__ ?>js/grid.js"></script>
<script src="<?= $this->__ASSETS__ ?>js/app.js"></script>
<script src="<?= $this->__ASSETS__ ?>js/calendar.js"></script>
<script src="<?= $this->__VENDOR__ . $this->cfg['editor_dir'] ?>/kindeditor-min.js"></script>
</body>
</html>
