<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $this->cfg['site_name'] . '(' . $this->cfg['softCore'] . $this->version['phoenix'] . ')' ?></title>
    <link href="<?= $this->__ASSETS__ ?>css/default.css" type="text/css" rel="stylesheet">
    <style type="text/css">
        html, body { height:100%; }
    </style>
</head>
<body>
<div id="login_wrapper">
    <div id="login_container">
        <div id="login_form">
            <form name="frm_login" id="frm_login" action="" onsubmit="return false;">
                <div id="login_form_header">
                    <div class="login_ico"></div>
                    <div class="login_text">网站后台管理系统</div>
                </div>
                <dl class="login_top">
                    <dt>用户名</dt>
                    <dd>
                        <input type="text" id="loginUserName" name="loginUserName" class="login_input" value="">
                    </dd>
                </dl>
                <dl class="login_top">
                    <dt>密　码</dt>
                    <dd>
                        <input type="password" id="loginUserPwd" name="loginUserPwd" class="login_input" value="">
                    </dd>
                </dl>
                <dl id="login_btn">
                    <dt>
                        <input type="submit" id="loginBtn" class="l_btn" value="登　录">
                    </dt>
                    <dd>
                        <input type="reset" class="r_btn" value="重 置">
                    </dd>
                </dl>
                <div id="login_form_list">
                    <ol class="login_list">
                        <li>此为站点管理系统，严禁向外界透露</li>
                        <li>请输入分配的用户名密码登录</li>
                        <li>密码多次错误会被锁定账号</li>
                        <li>忘记密码、账号锁定请联系系统管理员</li>
                        <li>可在锁定提示时间过后再次尝试登录</li>
                    </ol>
                </div>
            </form>
        </div>
        <div id="login_banner"></div>
        <div id="login_copyright">&copy; 2007-<?= date('Y') ?> All rights reserved.</div>
    </div>
</div>
<script src="<?= $this->__STATIC__ ?>js/jquery-1.11.1.min.js"></script>
<script src="<?= $this->__ASSETS__ ?>js/pxdialog.js"></script>
<script src="<?= $this->__ASSETS__ ?>js/app.js"></script>
</body>
</html>
