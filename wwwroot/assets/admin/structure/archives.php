<?php
$_currentPageActionClassName = str_replace(' ', '_', ucwords(str_replace('/', ' ', $this->__REQUEST_MAPPING__)));
$_winParameter = "?parentPageId=$this->pageId&currentPageActionClassName=$_currentPageActionClassName";
?>
<script>
    $(function(){
        PageAction.currentPageActionClassName = '<?= $_currentPageActionClassName ?>';
        if (typeof PageAction[PageAction.currentPageActionClassName] == 'undefined') {
            PageAction[PageAction.currentPageActionClassName] = function() {
                var _addEditPage = PageAction.pack + '<?= $this->__REQUEST_MAPPING__ ?>Content<?= $_winParameter ?>';
                return {
                    grid : '#<?= $_GET['divId'] ?> .data_grid_wrapper .data_grid',
                    addEditPage : _addEditPage,
                    handlerUrl : PageAction.handlerRoot + 'handler/Admin.<?= str_replace('_', '.', $_currentPageActionClassName) . '.' ?>',
                    init : function() {
                        var _g = this.grid;
                        var _form = this.grid.replace(/ \.data_grid$/, '');
                        $(':input[type=submit], :radio[name=is_display]', _form).click(function(){
                            Navigation.getSearchData(_g, _form);
                        });
                        $('select[name=category_id]', _form).change(function(){
                            Navigation.getSearchData(_g, _form);
                        });
                        $(':input[name=sltLanguage]', _form).change(function(){
                            Navigation.getSearchData(_g, _form);
                        });
                        this.read();
                    },
                    read : function() {
                        $(this.grid).dataGrid({
                            url      : this.handlerUrl + 'Read',
                            params   : [ { name:'is_part', value:'0' }, { name : 'is_display', value : '1' } ],
                            sortName : 'a.archives_id',
                            sortOrder: 'DESC',
                            rp : 50,
                            buttons  : [
                                { name:'普通', dbAction: { action : this.handlerUrl + 'SetFieldValue', field : 'is_status', fieldValue : 0 }, onpress:this.setFieldValue },
                                //{ name:'最新', dbAction: { action : this.handlerUrl + 'SetFieldValue', field : 'is_status', fieldValue : 1 }, onpress:this.setFieldValue },
                                //{ name:'热门', dbAction: { action : this.handlerUrl + 'SetFieldValue', field : 'is_status', fieldValue : 2 }, onpress:this.setFieldValue },
                                { name:'置顶', dbAction: { action : this.handlerUrl + 'SetFieldValue', field : 'is_status', fieldValue : 3 }, onpress:this.setFieldValue },
                                //{ name:'首显', dbAction: { action : this.handlerUrl + 'SetFieldStatus', field : 'is_home_display' }, onpress:this.setFieldStatus, margin : true },
                                { name:'是否使用', dbAction: { action : this.handlerUrl + 'SetFieldStatus', field : 'is_display' }, onpress:this.setFieldStatus },
                                {separator:true},
                                <?= App\Tools\Auxi::getPowerButton('add', $this->security, $this->pageId, $this->adminPower, substr($this->adminMap[$this->security]['menu'][$this->pageId]['name'], 0, -6)) ?>
                                {name:'刷 新', bclass:'refresh', onpress:Navigation.getSearchData},
                                {separator:true},
                                {name:'全 选', bclass:'set_all'},
                                {name:'反 选', bclass:'set_inv', margin:true},
                                {separator:true}
                                <?= App\Tools\Auxi::getPowerButton('delete', $this->security, $this->pageId, $this->adminPower, '删 除') ?>
                            ],
                            colModel : [
                                {display:'ID', name:'a.archives_id', width:30, align:'center', css:'num'},
                                {display:'所属类别', width:150, name:'a.category_id', align:'left'},
                                {display:'信息标题',  width:500, name:'a.title', align:'left'},
                                {display:'信息图片', width:60, align:'center'},
                                {display:'状态', name:'a.is_status', width:32, align:'center'},
                                //{display:'首', name:'a.is_home_display', width:20, align:'center'},
                                {display:'排序', width:50, align:'center', css:'num'},
                                {display:'添加时间', name:'a.add_date', width:120, align:'center', css:'num'}
                            ],
                            useSelect : true
                            <?= App\Tools\Auxi::getPowerButton('edit', $this->security, $this->pageId, $this->adminPower) ?>
                        });
                    },
                    setFieldValue : function(t, action) {
                        Tools.doLockingUpdate(t, action, [
                            '您确定更改这',
                            '个文档属性状态吗？',
                            'directRemoveLocking',
                            '数据库出错'
                        ]);
                    },
                    setFieldStatus : function(t, action) {
                        Tools.doLockingUpdate(t, action, [
                            '您确定更改这',
                            '个显示状态吗？',
                            'directRemoveLocking',
                            '数据库出错'
                        ]);
                    },
                    add : function(t) {
                        Tools.addEditPageLoad(_addEditPage + '&action=add');
                    },
                    edit : function(t) {
                        Tools.addEditPageLoad(_addEditPage + '&action=edit&id=' + t);
                    }
                }
            }();
            PageAction[PageAction.currentPageActionClassName].init();
        }
    });
</script>

<div class="data_grid_wrapper">
    <div class="control_wrapper">
        <div class="tl"></div>
        <div class="top_control">
            <div class="left"><?= $this->adminMap[$this->security]['title'] ?> » <?= $this->adminMap[$this->security]['menu'][$this->pageId]['name'] ?></div>
            <div class="control_btn"></div>
        </div>
        <div class="tr"></div>
    </div>
    <div class="grid_wrapper">
        <div class="m_search_field">
            <form name="search_from" onsubmit="return false;">
                <input type="hidden" id="is_part" name="is_part" value="0" />
                <?= App\Admin\Helper::getDefaultSearchInfo($this->__LANGUAGE_CONFIG__) ?>
                <?= $this->sltIDTree ?>
                <?= App\Tools\Html::radio(true, 'is_display', '', $this->setting['aryAnchorStatus'], '1', 'horizontal', 'red b') ?>
            </form>
        </div>
    </div>
    <table width="100%" border="0" cellspacing="0" cellpadding="0" class="data_grid">
        <tbody>
        </tbody>
    </table>
</div>
