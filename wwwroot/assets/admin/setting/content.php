<?php
$this->pageControl = true;
$_currentPageActionClassName = str_replace(' ', '_', ucwords(str_replace('/', ' ', $this->__REQUEST_MAPPING__)));
?>
<script>
	$(function(){
		if (typeof PageAction.siteSetting == 'undefined') {
			PageAction.siteSetting = function() {
				var _grid = '#<?= $_GET['divId'] ?> .data_grid_wrapper';
				var _url = PageAction.handlerRoot + 'handler/Admin.<?= ucfirst($this->currentFolder) . '.' ?>' + 'Action';
				return {
					init : function() {
						$('#main_loading').fadeOut();
						Navigation.flushContentHeight($('.data_content_wrapper', _grid));
						var _this = this;
						$('.save', _grid).unbind('click').click(_this.doAction);
					},
					doAction : function() {
						$.dialog.locking.confirm('prompt', '站点配置不能有空值，确定提交吗？', function(){
							$.dialog.locking('系统已启动，请稍候。。。');
							$.post(_url,
								$('form[name=list_post_form]', _grid).serializeArray(),
								function(data) {
									switch (data.status) {
										case 'SUCCESS' :
											$.dialog.locking.alert('success', '保存成功');
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
							return false;
						});
					}
				}
			}();
			PageAction.siteSetting.init();
		}
	});
</script>

<div class="data_grid_wrapper">
	<div class="control_wrapper">
		<div class="tl"></div>
		<div class="top_control">
			<div class="left"><?= $this->adminMap[$this->security]['title'] ?> » <?= $this->adminMap[$this->security]['menu'][$this->pageId]['name'] ?></div>
			<div class="control_btn">
				<?= App\Tools\Auxi::getPowerButton('edit', $this->security, $this->pageId, $this->adminPower, '<div class="save color_btn" title="保存数据"><span>保 存</span></div>', true) ?>
				<div class="separator"></div>
				<div class="load_state"></div>
			</div>
		</div>
		<div class="tr"></div>
	</div>
	<div class="grid_wrapper">
		<form action="" name="list_post_form" method="post" onsubmit="return false;">
			<div class="data_content_wrapper">
				<table width="100%" height="100%" cellpadding="0" cellspacing="0" class="data_input">
					<?php
					foreach ($this->rs as $m) {
						$_type = trim($m->field_type);
						?>
						<tr>
							<th width="220"><?= $m->field_desc ?></th>
							<td>
								<?php
								if (strpos($_type, 'radio') !== false) {
									$_temp = explode(':', $_type);
									echo(App\Tools\Html::radio($this->pageControl, $m->field_name, $m->synopsis, $this->setting[$_temp[1]], 0));
								} else {
									echo(App\Tools\Html::$_type($this->pageControl, $m->field_name, $m->synopsis, null, (strcasecmp('textarea', $_type) == 0 ? 3 : null)));
								}
								?></td>
						</tr>
						<?php
					}
					?>
				</table>
			</div>
		</form>
	</div>
</div>
