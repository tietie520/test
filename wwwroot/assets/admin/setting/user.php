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
					this.read();
				},
				read : function() {
					$(this.grid).dataGrid({
						url      : this.handlerUrl + 'Read',
						params   : [],
						sortName : 'mu.user_id',
						sortOrder: 'DESC',
						buttons  : [
							<?= App\Tools\Auxi::getPowerButton('add', $this->security, $this->pageId, $this->adminPower, substr($this->adminMap[$this->security]['menu'][$this->pageId]['name'], 0, -6)) ?>
							{name:'刷 新', bclass:'refresh', onpress:Navigation.getSearchData},
							{separator:true},
							{name:'全 选', bclass:'set_all'},
							{name:'反 选', bclass:'set_inv', margin:true},
							{separator:true}
							<?= App\Tools\Auxi::getPowerButton('delete', $this->security, $this->pageId, $this->adminPower, '删 除') ?>
						],
						colModel : [
							{display:'ID', name:'mu.user_id', width:30, align:'center', css:'num'},
							{display:'角色名称', name:'mu.role_name', width:100, align:'left'},
							{display:'管理员名称', name:'mu.user_name', align:'left'},
							{display:'真实姓名', width:80, align:'center'},
							{display:'最后登录IP', width:80, align:'center', css:'num'},
							{display:'登录次数', width:60, align:'center', css:'num'},
							{display:'最后登录', width:120, align:'center', css:'num'},
							{display:'添加时间', name:'mu.add_date', width:120, align:'center', css:'num'}
						],
						useSelect : true
						<?= App\Tools\Auxi::getPowerButton('edit', $this->security, $this->pageId, $this->adminPower) ?>
					});
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
			</form>
		</div>
	</div>
	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="data_grid">
		<thead>
		</thead>
		<tbody>
		</tbody>
	</table>
</div>
