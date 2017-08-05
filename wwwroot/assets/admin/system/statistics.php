<script>
    $(function(){
        Navigation.flushContentHeight($('#container_welcome .data_grid_wrapper .data_content_wrapper'));
    });
</script>
<div class="data_grid_wrapper">
    <div class="control_wrapper">
        <div class="tl"></div>
        <div class="top_control">
            <div class="left" id="welcome_text">后台管理 » 欢迎页 统计信息 <span class="fs14 strong"><?= App\Tools\Auxi::sayHi() ?></span></div>
        </div>
        <div class="tr"></div>
    </div>
    <div class="grid_wrapper">
        <div class="data_content_wrapper">
            <table width="100%" cellpadding="0" cellspacing="0" class="data_grid">
                <tbody>
                <tr>
                    <th width="200">文档统计</th>
                    <td class="flush_left"><?= $this->db->table('`#@__@archives`')->count() ?></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>