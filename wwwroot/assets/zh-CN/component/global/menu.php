<div class="menu fn-clear">
    <ul class="fn-clear fn-left ui-menu">
        <?php
        foreach ($this->aryCategoryDataView[$this->rootId] as $_k => $_v) :
            if (is_array($_v)) :
                $_tmpLink = App\Tools\UrlHelper::getTypeUrl($this->aryCategoryDataView, $_v['id_tree']);
                ?>
                <li class="item-title<?= $this->categoryId == $_k ? ' cur' : '' ?>">
                    <a href="<?= $_tmpLink ?>"><?= $_v['category_name'] ?></a>
                </li>
                <?php
            endif;
        endforeach;
        ?>
    </ul>
    <div class="fn-right nav-right">
        <span class="nav-icon"><i class="icon icon-1"></i></span>
        <?= $this->getBreadcrumb ?>
    </div>
</div>