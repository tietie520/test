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
					$(':input[name=sltLanguage]', _form).change(function(){
						Navigation.getSearchData(_g, _form);
					});
					this.read();
				},
				read : function() {
					$(this.grid).dataGrid({
						url      : this.handlerUrl + 'Read',
						//params   : [],
						sortName : 'c.id_tree',
						sortOrder: 'ASC',
						rp : 50,
						buttons  : [
							{ name:'首页显示', dbAction: { action : this.handlerUrl + 'SetFieldStatus', field : 'is_home_display' }, onpress:this.setFieldStatus, margin : true },
							{separator:true},
							{name:'显示状态', dbAction:this.handlerUrl + 'SetDisplay', onpress:this.setDisplay},
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
							{display:'ID', name:'c.category_id', width:30, align:'center', css:'num'},
							{display:'深度', width:30, align:'center'},
							{display:'栏目名称', width:200, name:'c.id_tree', align:'left'},
							{display:'栏目名称(英文)', width:160, align:'left'},
							{display:'栏目属性', width:60, align:'center'},
							{display:'栏目图片', width:60, align:'center'},
							{display:'主副导航', width:60, align:'center'},
							{display:'显示', width:32, align:'center'},
							{display:'首显', width:32, align:'center'},
							{display:'首显顺序', width:60, align:'center'},
							{display:'添加人员', width:60, align:'center'},
							{display:'添加时间', name:'c.add_date', width:120, align:'center', css:'num'},
							{display:'修改时间', name:'c.release_date', width:120, align:'center', css:'num'}
						],
						useSelect : true
						<?= App\Tools\Auxi::getPowerButton('edit', $this->security, $this->pageId, $this->adminPower) ?>
					});
				},
				setDisplay : function(t, action) {
					Tools.doLockingUpdate(t, action, [
						'您确定更改这',
						'个显示状态吗？',
						'显示状态已更改成功',
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
				<?= App\Admin\Helper::getDefaultSearchInfo($this->__LANGUAGE_CONFIG__) ?>
			</form>
		</div>
	</div>
	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="data_grid">
		<tbody>
		</tbody>
	</table>
</div>
