<script>
	$(function(){
		PageAction.currentPageActionClassName = '<?= $_GET['currentPageActionClassName'] ?>';
		PageAction.currentPageAction = '<?= ucfirst($_GET['action']) ?>';

		if (typeof PageAction[PageAction.currentPageActionClassName].doContentAction == 'undefined') {
			PageAction[PageAction.currentPageActionClassName].doContentAction = function() {
				var _test = Tools.checkNull('ad_title', '请输入广告标题') &&
					Tools.checkNull('ad_url', '请输入广告链接');
				if (_test) {
					$.dialog.locking('系统已启动，请稍候。。。');

					var _this = this;
					$.post(this.handlerUrl + PageAction.currentPageAction,
					Tools.addEditPageLoader().DOM.form.serializeArray(),
					function(data) {
						switch (data.status) {
							case 'SUCCESS' :
								$(_this.grid).dataGridReload();

								$.dialog.locking.confirm('success', '编辑成功，您还需要继续添加吗？', function(){

									Tools.addEditPageLoader()
									.resetConfig({
										content : 'load:' + _this.addEditPage + '&action=add'
											+ '&parentId=' + $('#type_id').val()
									}, true);

									return true;
								}, function(){
									Tools.addEditPageLoader().close();
									return true;
								});

								break;
							case 'NO_CHANGES' :
								$.dialog.locking.alert('ndash', data.desc);
								break;
							case 'DB_ERROR' :
							case 'INTERCEPTOR' :
								$.dialog.locking.alert('error', data.desc);
								break;
							default :
								$.dialog.locking.alert('error', '系统繁忙，请稍候再试');
								break;
						}
					}, 'json');
				}
			};
		}

		Tools.addEditPageLoader()
		.title('<?= $this->adminMap[$this->security]['title'] ?> » <?= $this->adminMap[$this->security]['menu'][$_GET['parentPageId']]['name'] ?> » <?= App\Admin\Helper::getActionName($_GET['action']) ?>');

		<?php
		if ($this->pageControl) {
		?>
		Tools.addEditPageLoader().button({
			id : 'ok',
			name : Tools.addEditPageLoader().config.okVal,
			callback : function(){
				
				PageAction[PageAction.currentPageActionClassName]
				.doContentAction
				.call(PageAction[PageAction.currentPageActionClassName]);
				
				return false;
			},
			unshift : true,
			type : 'submit',
			focus: true
		});
		<?php
		}
		?>
	});
</script>

<input type="hidden" name="id" id="id" value="<?= $_GET['id'] ?>">
<table width="100%" cellpadding="0" cellspacing="0" class="data_input">
	<tr>
		<th width="15%">语言</th>
		<td width="35%"><?= App\Tools\Html::radio($this->pageControl, 'language', $this->rs, $this->__LANGUAGE_CONFIG__, $this->__LANGUAGE_ID__) ?></td>

		<th width="15%">属性</th>
		<td><?= App\Tools\Html::checkbox($this->pageControl, 'is_display', $this->rs ? $this->rs->is_display : 1, '是否上架') ?></td>
	</tr>
	<tr>
		<th width="15%">类别</th>
		<td width="35%"><?= App\Tools\Html::select($this->pageControl, 'type_id', $this->rs ? $this->rs->type_id : $_GET['parentId'], $this->setting['aryAd'], '0') ?></td>
		<th>广告标题</th>
		<td><?= App\Tools\Html::text($this->pageControl, 'ad_title', $this->rs) ?></td>
	</tr>
	<tr>
		<th>png文字图<br/>建议尺寸：(1920 * 809)</th>
		<td>
			<?= App\Admin\Helper::createUpFile('img', 'ad_img_bg', $this->rs ? $this->rs->ad_img_bg : null, $this->setting['aryPicExtName'], $this->setting['aryFileExtName'], $this->__CDN__, $this->__ASSETS__) ?></td>
		<th rowspan="2">广告图<br/>建议尺寸：(400 * 160)</th>
		<td rowspan="2"><?= App\Admin\Helper::createUpFile('img', 'ad_img', $this->rs ? $this->rs->ad_img : null, $this->setting['aryPicExtName'], $this->setting['aryFileExtName'], $this->__CDN__, $this->__ASSETS__) ?></td>
	</tr>
	<tr>
		<th>链接</th>
		<td><?= App\Tools\Html::text($this->pageControl, 'ad_url', $this->rs) ?></td>
	</tr>
	<tr>
		<th>开始时间</th>
		<td><?= App\Tools\Html::setDate($this->pageControl, 'start_date', App\Tools\Auxi::getTime($this->rs ? $this->rs->start_date : time()), '90%') ?></td>
		<th>结束时间</th>
		<td><?= App\Tools\Html::setDate($this->pageControl, 'end_date', App\Tools\Auxi::getTime($this->rs ? $this->rs->end_date : time()), '90%') ?></td>
	</tr>
	<tr>
		<th>排序</th>
		<td><?= App\Tools\Html::text($this->pageControl, 'ad_sort', $this->rs ? $this->rs : $this->getSort) ?></td>
		<th>target</th>
		<td><?= App\Tools\Html::radio($this->pageControl, 'target', $this->rs, $this->setting['aryFooterLinkTarget'], '1', 'horizontal') ?></td>
	</tr>
</table>
