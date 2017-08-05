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
					var _form = this.grid.replace(/ \.data_grid$/, '');
					var _g = this.grid;
					$(':input[type=submit]', _form).click(function(){
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
						params   : [],
						sortName : 'fl.footer_link_id',
						sortOrder: 'ASC',
						rp : 50,
						buttons  : [
							{name:'使用状态', dbAction:this.handlerUrl + 'SetDisplay', onpress:this.setDisplay},
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
							{display:'ID', name:'fl.footer_link_id', width:30, align:'center', css:'num'},
							{display:'类别', width:120, name:'fl.category_id', align:'left'},
							{display:'关键字', width:150, name:'fl.keywords', align:'left'},
							{display:'链接', name:'fl.link_url', width:200, align:'left'},
							{display:'图片logo', name:'fl.link_url', width:60, align:'left'},
							{display:'target方式', width:80, align:'center'},
							{display:'状态', width:60, name:'fl.is_status', align:'center'},
							{display:'链接类型', width:80, name:'fl.link_type', align:'center'}
						],
						useSelect : true
						<?= App\Tools\Auxi::getPowerButton('edit', $this->security, $this->pageId, $this->adminPower) ?>
					});
				},
				setDisplay : function(t, action) {
					Tools.doLockingUpdate(t, action, [
						'您确定更改这',
						'个使用状态吗？',
						'使用状态已更改成功',
						'数据库出错'
					]);
				},
				add : function(t) {
					Tools.addEditPageLoad(_addEditPage + '&action=add', 700);
				},
				edit : function(t) {
					Tools.addEditPageLoad(_addEditPage + '&action=edit&id=' + t, 700);
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
				<?= App\Admin\Helper::getDefaultSearchInfo($this->__LANGUAGE_CONFIG__) ?>
				<?= $this->sltIDTree ?>
			</form>
		</div>
	</div>
	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="data_grid">
		<tbody>
		</tbody>
	</table>
</div>
