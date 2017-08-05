<script>
	$(function(){
		PageAction.currentPageActionClassName = '<?= $_GET['currentPageActionClassName'] ?>';
		PageAction.currentPageAction = '<?= ucfirst($_GET['action']) ?>';

		if (typeof PageAction[PageAction.currentPageActionClassName].doContentAction == 'undefined') {
			PageAction[PageAction.currentPageActionClassName].doContentAction = function() {
				var _addUser = function() {
					var _userPwd = $('#user_pwd');
					if (_userPwd.val().trim().length > 0
						&& (_userPwd.val().trim().length < 5
						|| _userPwd.val().trim().length > 12)) {
						$.dialog.alert('请输入5至12位密码', function(){
							_userPwd.focus();
						});
						return false;
					}
					if (PageAction.currentPageAction == 'Add') {
						return Tools.checkNull('user_pwd', '请输入初始密码');
					}
					return true;
				}
				var _test = Tools.checkNull('user_name', '请输入登录用户名') &&
					_addUser() &&
					Tools.checkNull('real_name', '真实姓名不能为空');
				if(_test) {
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
							case 'IS_EXISTS' :
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
		<th width="20%">语言</th>
		<td width="30%"><?= App\Tools\Html::radio($this->pageControl, 'language', $this->rs, $this->__LANGUAGE_CONFIG__, $this->__LANGUAGE_ID__) ?></td>
		<th width="20%">修改时间</th>
		<td><?= App\Tools\Html::setDate($this->pageControl, 'release_date', App\Tools\Auxi::getTime(time()), '90%') ?></td>
	</tr>
	<tr>
		<th>添加时间</th>
		<td><?= App\Tools\Html::setDate($this->pageControl, 'add_date', App\Tools\Auxi::getTime(time()), '90%') ?></td>
		<th>所属角色</th>
		<td><?= $this->userRole ?>
		</td>
	</tr>
	<tr>
		<th>用 户 名</th>
		<td><?= App\Tools\Html::text($this->pageControl, 'user_name', $this->rs) ?></td>
		<th>密码（不改勿填）</th>
		<td><input type="password" name="user_pwd" id="user_pwd" style="width:98%" value=""<?= $this->disabled ?>></td>
	</tr>
	<tr>
		<th>真实姓名</th>
		<td><?= App\Tools\Html::text($this->pageControl, 'real_name', $this->rs) ?></td>
		<th>邮箱地址</th>
		<td><?= App\Tools\Html::text($this->pageControl, 'email', $this->rs) ?></td>
	</tr>

</table>
