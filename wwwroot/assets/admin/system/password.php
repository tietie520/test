<script>
	$(function(){
		if (typeof PageAction.chgPassword == 'undefined') {
			PageAction.chgPassword = function() {
				var _user_pwdX = $('#user_pwdX')
				, _user_pwd = $('#user_pwd');
				if (_user_pwdX.val().trim().length == 0) {
					$.dialog.alert('请输入密码', function(){
						_user_pwdX.focus();
					});
					return false;
				}
				else if (_user_pwd.val().trim().length == 0) {
					$.dialog.alert('请再输入一次密码', function(){
						_user_pwd.focus();
					});
					return false;
				}
				else if (_user_pwd.val().length < 5 || _user_pwd.val().length > 12) {
					$.dialog.alert('请输入5至12位密码', function(){
						_user_pwd.focus();
					});
					return false;
				}
				else if (_user_pwdX.val() != _user_pwd.val()) {
					$.dialog.alert('您前后输入密码不一致', function(){
						_user_pwd.focus();
					});
					return false;
				}
				else {
					$.dialog.locking('系统已启动，请稍候。。。');
	
					$.post(PageAction.handlerRoot + 'handler/Admin.System.EditPwd'
					, Tools.addEditPageLoader().DOM.form.serializeArray()
					, function(data) {
						switch (data.status) {
							case 'SUCCESS' :
								$.dialog.locking.alert('success', data.desc, function(){
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
			}
		}
		Tools.addEditPageLoader().title('修改我的密码');
		$('#user_pwdX').focus();
		
		Tools.addEditPageLoader().button({
			id : 'ok'
			, name : Tools.addEditPageLoader().config.okVal
			, callback : function(){
				
				PageAction.chgPassword();
				
				return false;
			}
			, unshift : true
			, type : 'submit'
			, focus: true
		});
	});
</script>

<table width="100%" height="160" cellpadding="0" cellspacing="0" class="data_input">
	<tr>
		<th width="30%" height="80">密　　码</th>
		<td><input type="password" name="user_pwdX" id="user_pwdX" style="width:98%" value="" /></td>
	</tr>
	<tr>
		<th height="80">确认密码</th>
		<td><input type="password" name="user_pwd" id="user_pwd" style="width:98%" value="" /></td>
	</tr>
</table>
