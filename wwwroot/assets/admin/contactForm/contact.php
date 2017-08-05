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
					grid : '#<?= $_GET['divId'] ?> .data_grid_wrapper',
					addEditPage : _addEditPage,
					handlerUrl : PageAction.handlerRoot + 'handler/Admin.<?= str_replace('_', '.', $_currentPageActionClassName) . '.' ?>',
					init : function() {
						var _g = this.grid;
						$(':input[type=submit], :radio[name=is_display]', _g).click(function(){
							Navigation.getSearchData(_g);
						});
						$('select[name=category_id]', _g).change(function(){
							Navigation.getSearchData(_g);
						});
						this.read();
					},
					read : function() {
						$(this.grid).dataGrid({
							url      : this.handlerUrl + 'Read',
							params   : [],
							sortName : 'a.add_date',
							sortOrder: 'DESC',
							rp : 50,
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
								{display:'ID', name:'a.archives_id', width:30, align:'center', css:'num'},
								{display:'表单内容', name : 'a.contact_json'},
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
				<?= App\Admin\Helper::getDefaultSearchInfo($this->__LANGUAGE_CONFIG__) ?>
			</form>
		</div>
	</div>
	<table width="100%" border="0" cellspacing="0" cellpadding="0" class="data_grid">
		<tbody>
		</tbody>
	</table>
</div>
