<script>
    $(function(){
        PageAction.UpLoadPageAction.success = function(data, _this) {
            var _uploadName = _this.fileAttribute.uploadName;
            var _imgWidth = _this.fileAttribute.imgWidth;
            var _uploadType = _this.fileAttribute.uploadType;
            var _temp = '';
            if (_this.fileAttribute.mode == 'multiple') {
                var _srcId = data.desc.replace(/[,\.]/g, '');
                var _obj = $('dd > ol', '#return_' + _uploadName);
                _temp = '<li id="multiple_img_' + _srcId + '"><label for="primary_' + _srcId + '">主显图</label><input type="radio" id="primary_' + _srcId + '" name="' + _uploadName + '" value="' + data.desc + '"';
                if ($('li', _obj).length == 0)
                    _temp += ' checked="checked"';
                _temp += '><input type="hidden" name="' + _uploadName + '_multiple[]" value="' + data.desc + '"><img src="<?= $this->__ROOT__ ?>data/tmp/' + data.desc + '" height="' + _imgWidth + '"><input type="button" class="ipt_btn" value="删除" onclick="Tools.delMultipleImg(\'' + data.desc + '\');"></li>';
                _obj.append(_temp);
                _srcId = _obj = null;
            }
            else {
                if (_uploadType == 'img') {
                    $('#return_' + _uploadName).html('<img src="<?= $this->__ROOT__ ?>data/tmp/' + data.desc + '" height="' + _imgWidth + '" onload="if(this.width/this.height > 3){this.width=150;this.removeAttribute(\'height\');}">');
                }
                else {
                    $('#return_' + _uploadName).html('<img src="<?= $this->__ASSETS__ ?>images/file/' + data.desc.substring(data.desc.lastIndexOf('.') + 1, data.desc.length) + '.gif"> <?= $this->__CDN__ . $this->setting['aryUploadPath'][1] ?>/' + data.desc);
                }
                $('#' + _uploadName).val(data.desc);
                $('#' + _uploadName + '-attachment').val(data.rsp);
                $('#add_' + _uploadName).hide();
                $('#del_' + _uploadName).show();
            }
            _temp = _uploadType = _imgWidth = _uploadName = null;
        }
    });
</script>

<iframe name="HiddenUploadFrame" id="HiddenUploadFrame" style="display:none"></iframe>
<form name="uploadForm" id="uploadForm" action="" target="HiddenUploadFrame" method="post" enctype="multipart/form-data">
    <table width="100%" cellpadding="0" cellspacing="0" class="data_input">
        <tr>
            <td style="text-align:center;height:27px;">
                <?php
                $_auto = false;
                $_normal = false;
                $_sys = false;
                if (strcasecmp('auto', $_GET['mode']) == 0) {
                    $_auto = $_normal = $_sys = true;
                } else if (strcasecmp('fileNameNormal', $_GET['mode']) == 0) {
                    $_normal = true;
                } else if (strcasecmp('fileNameSysWrite', $_GET['mode']) == 0) {
                    $_sys = true;
                }
                if ($_normal) :
                    ?>
                    <input name="importMode" id="importModeN" type="radio" value="fileNameNormal"<?= $_normal ? ' checked="checked"' : '' ?>>
                    <label for="importModeN">文件名保持不变(请使用英文命名)</label>
                <?php
                endif;
                if ($_sys) :
                    ?>
                    <input name="importMode" id="importModeSW" type="radio" value="fileNameSysWrite"<?= $_auto || $_sys ? ' checked="checked"' : '' ?>>
                    <label for="importModeSW">使用系统生成文件名</label>
                <?php
                endif;
                ?>
                <input type="hidden" name="uploadType" id="uploadType" value="<?= $_GET['uploadType'] ?>">
                <input type="hidden" name="uploadName" id="uploadName" value="<?= $_GET['uploadName'] ?>">
                <input type="hidden" name="imgWidth" id="imgWidth" value="<?= $_GET['imgWidth'] ?>">
        </tr>
        <tr>
            <td style="text-align:center;height:27px;"><input name="getFileToUpload" id="getFileToUpload" type="file" size="34">
                <img src="<?= $this->__ASSETS__ ?>/images/speedy.gif" id="speedy" class="hide"></td>
        </tr>
        <tr>
            <td style="text-align:center;height:27px;">允许格式：<?= App\Tools\Auxi::allowType($_GET['uploadType'], $this->setting['aryPicExtName'], $this->setting['aryFileExtName']) ?></td>
        </tr>
    </table>
</form>