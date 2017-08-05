<script>
	$(function(){
		PageAction.currentPageActionClassName = '<?= $_GET['currentPageActionClassName'] ?>';
		PageAction.currentPageAction = '<?= ucfirst($_GET['action']) ?>';

		if (typeof PageAction[PageAction.currentPageActionClassName].doContentAction == 'undefined') {
			PageAction[PageAction.currentPageActionClassName].doContentAction = function() {
				_role_name = $('#role_name').val().trim();
				if (_role_name.length == 0) {
					$.dialog.alert('请输入角色名称', function(){
						$('#role_name').focus();
					});
					return false;
				}
				_synopsis = $('#synopsis').val().trim();
				if (_synopsis.length == 0) {
					$.dialog.alert('请输入角色描述', function(){
						$('#synopsis').focus();
					});
					return false;
				}
				var _bool = false;
				$('.set_power_list :checkbox').each(function(){
					if ($(this).prop('checked')) {
						_bool = true;
						return;
					}
				});
				if (!_bool) {
					$.dialog.alert('请选择权限');
					return false;
				}
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

		var _setPower = <?= $this->setPower ?>;
		if (_setPower.length > 0) {
			$(':checkbox[name="set_power[]"]').each(function(){
				if ($.inArray($(this).val(), _setPower) != -1)
					$(this).prop('checked', true);
			});
		}
		var _tmp = null;
		var _dtCheckbox = $('.set_power_list > dt > :checkbox');
		var _dd = null;
		var _ddSpanCheckbox = $('.set_power_list > dd > span > :checkbox');
		var _ddCheckbox = $('.set_power_list > dd > :checkbox');
		
		_dtCheckbox.click(function(){
			_tmp = $(this).parent().siblings().find(':checkbox');
			_tmp.prop('checked', $(this).prop('checked') ? true : false);
		});
		_ddSpanCheckbox.click(function(){
			_tmp = $(this).parent().siblings(':checkbox');
			_dl = $(this).parent().parent().parent();
			if ($(this).prop('checked')) {//选中
				_tmp.prop('checked', true);
				_dl.find('> dt > :checkbox').prop('checked', true);
			}
			else {
				_tmp.prop('checked', false);
				if (_dl.find('> dd :checkbox:checked').length == 0)
					_dl.find('> dt > :checkbox').prop('checked', false);
			}
		});
		_ddCheckbox.click(function(){
			_dd = $(this).parent();
			_tmp = $('> span > :checkbox', _dd);
			_dl = _dd.parent();
			if ($(this).prop('checked')) {//选中
				_tmp.prop('checked', true);
				_dl.find('> dt > :checkbox').prop('checked', true);
			}
			else {
				if (_dd.find('> :checkbox:checked').length == 0) {
					_tmp.prop('checked', false);
					//检测顶级
					if (_dl.find('> dd :checkbox:checked').length == 0)
						_dl.find('> dt > :checkbox').prop('checked', false);
				}
			}
		});
		Tools.switchDiv('.switch > ul > li', 'slt_switch', '.switch_list');
	});
</script>

<input type="hidden" name="id" id="id" value="<?= $_GET['id'] ?>">
<table width="100%" cellpadding="0" cellspacing="0" class="data_input">
	<tr>
		<th width="15%">语言</th>
		<td width="35%"><?= App\Tools\Html::radio($this->pageControl, 'language', $this->rs, $this->__LANGUAGE_CONFIG__, $this->__LANGUAGE_ID__) ?></td>
		<th width="15%" rowspan="3">角色描述</th>
		<td rowspan="3"><?= App\Tools\Html::textarea($this->pageControl, 'synopsis', $this->rs, null, 3, null, ' onpropertychange="if(value.length>100) value=value.substr(0,100)"') ?></td>
	</tr>
	<tr>
		<th>修改时间</th>
		<td><?= App\Tools\Html::setDate($this->pageControl, 'release_date', App\Tools\Auxi::getTime(time()), '90%') ?></td>
	</tr>
	<tr>
		<th>角色名称</th>
		<td><?= App\Tools\Html::text($this->pageControl, 'role_name', $this->rs) ?></td>
	</tr>
	<tr>
		<th>角色权限</th>
		<td colspan="3" height="300" style="overflow:scroll">
		<?php
		$_temp = '';
		foreach ($this->adminMap as $_k1 => $_v1) {
			$_temp .= '<dl class="set_power_list"><dt><input type="checkbox" name="set_power[]" value="' . $_k1 . '" id="set_power' . $_k1 . '" /><label for="set_power' . $_k1 . '">' . $_v1['title'] . '</label></dt>';
			foreach ($_v1['menu'] as $_k2 => $_v2) {
				$_temp .= '<dd><span><input type="checkbox" name="set_power[]" value="' . $_k1 . '.' . $_k2 . '" id="set_power' . $_k1 . '.' . $_k2 . '" /><label for="set_power' . $_k1 . '.' . $_k2 . '">' . $_v2['name'] . '</label></span>';
				foreach ($_v2['scope'] as $_scope) {
					$_temp .= '<input type="checkbox" name="set_power[]" value="' . $_k1 . '.' . $_k2 . '.' .  $_scope. '" id="set_power' . $_k1 . '.' . $_k2 . '.' .  $_scope. '" /><label for="set_power' . $_k1 . '.' . $_k2 . '.' .  $_scope. '">' . $this->setting['aryScope'][$_scope] . '</label> ';
				}
			}
			$_temp .= '</dl>';
		}
		echo($_temp);
		?>
		</td>
	</tr>
</table>
