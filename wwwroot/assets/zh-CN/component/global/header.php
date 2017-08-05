<div class="header">
    <div class="head">
        <div class="wrapper fn-clear">
            <div class="en fn-left">
                <span class="en-txt">welcome to PloughUAV</span>
            </div>
            <div class="cn fn-right">
                <a class="cn-txt cn-first cur" href="javascript:;">中文</a>
                <a class="cn-txt" href="/en">En</a>
            </div>
        </div>
    </div>
    <div class="item-nav">
        <div class="nav wrapper fn-clear">
            <div class="icon-logo fn-left">
                <a class="logo" href="<?= $this->__ROOT__?>">
                    <img src="<?= $this->__STATIC__ ?>images/index/logo.png">
                </a>
            </div>
            <div class="nav-title">
                <div class="ui-bg"></div>
                <ul class="fn-clear nav-menu ui-nav fn-right">
                    <li class="ui-bg"></li>
                    <li class="ui-nav-item <?= $this->__HOMEPAGE__ ? ' cur' : '' ?>">
                        <a class="item-link active-tit" href="<?= $this->__ROOT__ ?>">
                            <span class="cn">首页</span>
                        </a>
                    </li>
                    <?php
                    $_i = 1;
                    foreach ($this->aryCategoryDataView as $_k1 => $_v1):
                        if ($_k1 > 0 && $_v1['nav_type'] == 0 && $_v1['language'] == $this->__LANGUAGE_ID__):
                            $_tmpLink = App\Tools\UrlHelper::getTypeUrl($this->aryCategoryDataView, $_v1['id_tree']);
                            $_hasChild = App\Tools\Auxi::typeHasChild($this->aryCategoryDataView[$_k1]);
                            $_sltMenu = App\Tools\Auxi::compareSelect($_k1, $this->rootId, 'cur', "ui-nav-item" .
                                ($_hasChild ? ' ui-nav-item'.$_i : '' ));
                            ?>
                            <li<?= $_sltMenu ?>>
                                <a class="item-link" href="<?= $_hasChild ? 'javascript:;' : $_tmpLink?>">
                                    <span class="cn"><?= $_v1['category_name'] ?></span>
                                    <i class="cn-icon"></i>
                                </a>
                                <?php
                                if ($_hasChild) :
                                    ?>
                                    <ul class="sub-menu J-sub-menu<?= $_i ?>">
                                        <?php
                                        foreach ($this->aryCategoryDataView[$_k1] as $_k3 => $_v3) :
                                            if (is_array($_v3) && $_v3['language'] == $this->__LANGUAGE_ID__):
                                                $_tmpLink2 = App\Tools\UrlHelper::getTypeUrl($this->aryCategoryDataView, $_v3['id_tree']);
                                                $_sltMenu2 = App\Tools\Auxi::compareSelect($_k3, $this->categoryId, 'dl-last', 'dl');
                                                ?>
                                                <li class="sub-nav-item">
                                                    <a href="<?= $_tmpLink2 ?>"><?= $_v3['category_name'] ?></a>
                                                </li>
                                                <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </ul>
                                    <?php
                                endif;
                                ?>
                            </li>
                            <?php
                            $_i++;
                        endif;
                    endforeach;
                    ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="menu-btn">
        <a href="javascript:void(0)">
            <span class="sp1"></span>
            <span class="sp2"></span>
            <span class="sp3"></span>
        </a>
    </div>
</div>
