<script>
	$(function(){
		PageAction.editor.create(['substance'], { width : '100%', height : '320px' });

		PageAction.currentPageActionClassName = '<?= $_GET['currentPageActionClassName'] ?>';
		PageAction.currentPageAction = '<?= ucfirst($_GET['action']) ?>';

		if (typeof PageAction[PageAction.currentPageActionClassName].doContentAction == 'undefined') {
			PageAction[PageAction.currentPageActionClassName].doContentAction = function() {
				var _test = Tools.checkNull('title', '请输入信息标题') && Tools.checkNull('sort', '请输入排序') &&
					Tools.compare('category_id', 0, '请选择所属类别') &&
					Tools.checkDigit('view_count', '点击率只能输入数字');
				if (_test) {
					$.dialog.locking('系统已启动，请稍候。。。');

					PageAction.editor.sync();

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
											+ '&parentId=' + $('#category_id').val()
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
							case 'SEO_URL_IS_EXISTS' :
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
		Tools.switchDiv('.switch > ul > li', 'slt_switch', '.switch_list');

		$('.tags > ol > li > span').click(function(){
			var _archivesTags = $('#archives_tags').val();
			var _tag = $(this).text();

			if (_archivesTags.indexOf(_tag) == -1) {
				if (_archivesTags.trim().length > 0) {
					_archivesTags += ',';
				}
				_archivesTags += _tag;
			}
			$('#archives_tags').val(_archivesTags);
		});
	});
</script>

<input type="hidden" name="id" id="id" value="<?= $_GET['id'] ?>">
<div class="switch">
	<ul>
		<li class="slt_switch">基本信息</li>
		<li>高级功能</li>
	</ul>
	<div class="switch_list">
		<table width="100%" cellpadding="0" cellspacing="0" class="data_input">
			<tr>
				<th width="15%">语言</th>
				<td width="35%"><?= App\Tools\Html::radio($this->pageControl, 'language', $this->rs, $this->__LANGUAGE_CONFIG__, $this->__LANGUAGE_ID__) ?></td>
				<th width="15%">修改时间</th>
				<td><?= App\Tools\Html::setDate($this->pageControl, 'release_date', App\Tools\Auxi::getTime(time()), '90%') ?></td>
			</tr>
			<tr>
				<th>信息标题</th>
				<td><?= App\Tools\Html::text($this->pageControl, 'title', $this->rs) ?></td>
				<th>所属类别</th>
				<td><?= $this->sltIDTree ?></td>
			</tr>
			<tr>
				<th>英文名</th>
				<td><?= App\Tools\Html::text($this->pageControl, 'title_english', $this->rs) ?></td>
				<th>排序<br/>(数值越大越靠前)</th>
				<td><?= App\Tools\Html::text($this->pageControl, 'sort', $this->rs ? $this->rs : $this->getSort) ?></td>
			</tr>
			<tr>
				<th>seo url</th>
				<td colspan="3"><?= App\Tools\Html::text($this->pageControl, 'seo_url', $this->rs) ?></td>
			</tr>
			<tr>
				<th>视频外链地址</th>
				<td colspan="3"><?= App\Tools\Html::text($this->pageControl, 'video_url', $this->rs) ?></td>
			</tr>
			<tr>
				<th>信息描述</th>
				<td><?= App\Tools\Html::textarea($this->pageControl, 'synopsis', $this->rs, null, 5, null, ' onpropertychange="if(value.length>200) value=value.substr(0,200)"') ?></td>
				<th>信息相关图
					<br />
					(缩略图)
					<br/>建议尺寸:
					<br/>
					（180 * 122）
					<br/>(精彩视频：320 * 196)
					<br/>(新闻活动：240 * 150)
					<br/>(精彩图库：小：480 * 240, 大：960 * 480)
				</th>
				<td><?= App\Admin\Helper::createUpFile('img', 'cover', $this->rs ? $this->rs->cover : null,
						$this->setting['aryPicExtName'], $this->setting['aryFileExtName'], $this->__CDN__, $this->__ASSETS__) ?></td>
			</tr>
			<tr>
				<th>上传资料</th>
				<td colspan="3"><?= App\Admin\Helper::createUpFile('file', 'attachment', $this->rs ? $this->rs->attachment : null,
						$this->setting['aryPicExtName'], $this->setting['aryFileExtName'], $this->__CDN__, $this->__ASSETS__) ?></td>
			</tr>
			<tr>
				<th>点击率</th>
				<td><?= App\Tools\Html::text($this->pageControl, 'view_count', $this->rs ? $this->rs : 0) ?></td>
				<th rowspan="3">Seo Description</th>
				<td rowspan="3"><?= App\Tools\Html::textarea($this->pageControl, 'seo_description', $this->rs, null, 4) ?></td>
			</tr>
			<tr>
				<th>Seo Title</th>
				<td><?= App\Tools\Html::text($this->pageControl, 'seo_title', $this->rs) ?></td>
			</tr>
			<tr>
				<th>Seo Keywords</th>
				<td><?= App\Tools\Html::textarea($this->pageControl, 'seo_keywords', $this->rs) ?></td>
			</tr>
		</table>
	</div>
	<div class="switch_list hide">
		<table width="100%" cellpadding="0" cellspacing="0" class="data_input">
			<tr>
				<th width="15%">文档内容</th>
				<td height="320"><?= App\Tools\Html::editor($this->pageControl, 'substance', $this->rs) ?></td>
			</tr>
		</table>
	</div>
</div>
