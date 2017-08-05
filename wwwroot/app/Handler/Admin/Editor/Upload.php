<?php

namespace App\Handler\Admin\Editor;

if (!defined('IN_PX'))
    exit;

use Phoenix\Routing\IHttpHandler;
use App\Service\UPFile;

/**
 * 编辑器图片上传
 */
class Upload implements IHttpHandler {

    private function __Handler() {}

//    private function __Value($__UPLOAD__) {}

    private function __Inject(UPFile $upFile) {}

    private function __Bundle($upload = 'data/upload.cache.php') {}

    public function processRequest(Array & $context) {
        $phpUrl = dirname($_SERVER['PHP_SELF']) . '/';

        //文件保存目录路径
        $savePath = ROOT_PATH . $context['__UPLOAD__'] . DIRECTORY_SEPARATOR . 'editor' . DIRECTORY_SEPARATOR;
        if (!is_dir($savePath)) {
            @mkdir($savePath, 0777);
            @chmod($savePath, 0777);
        }
        //文件保存目录URL
        $saveUrl = $phpUrl . '../../../' . $context['__UPLOAD__'] . '/editor/';
        //定义允许上传的文件扩展名
        $extArr = array(
            'image' => array('gif', 'jpg', 'jpeg', 'png', 'bmp'),
            'flash' => array('swf', 'flv'),
            'media' => array('swf', 'flv', 'mp3', 'wav', 'wma', 'wmv', 'mid', 'avi', 'mpg', 'asf', 'rm', 'rmvb'),
            'file' => array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2', 'pdf'),
        );
        //最大文件大小
        $maxSize = $this->upload['source']['limit'] * 1024;

        //有上传文件时
        if (empty($_FILES) === false) {
            //原文件名
            $fileName = $_FILES['imgFile']['name'];
            //服务器上临时文件名
            $tmpName = $_FILES['imgFile']['tmp_name'];
            //文件大小
            $fileSize = $_FILES['imgFile']['size'];
            //检查文件名
            if (!$fileName) {
                $this->_editorAlert('请选择文件。');
            }
            //检查目录
            if (@is_dir($savePath) === false) {
                $this->_editorAlert('上传目录不存在。');
            }
            //检查目录写权限
            if (@is_writable($savePath) === false) {
                $this->_editorAlert('上传目录没有写权限。');
            }
            //检查是否已上传
            if (@is_uploaded_file($tmpName) === false) {
                $this->_editorAlert('临时文件可能不是上传文件。');
            }
            //检查文件大小
            if ($fileSize > $maxSize) {
                $this->_editorAlert('上传文件大小超过限制。');
            }
            //检查目录名
            $dirName = empty($_GET['dir']) ? 'image' : trim($_GET['dir']);
            if (empty($extArr[$dirName])) {
                $this->_editorAlert('目录名不正确。');
            }
            //获得文件扩展名
            $tempArr = explode('.', $fileName);
            $fileExt = array_pop($tempArr);
            $fileExt = trim($fileExt);
            $fileExt = strtolower($fileExt);
            //检查扩展名
            if (in_array($fileExt, $extArr[$dirName]) === false) {
                $this->_editorAlert("上传文件扩展名是不允许的扩展名。\n只允许" . implode(',', $extArr[$dirName]) . '格式。');
            }
            //创建文件夹
            if ($dirName !== '') {
                $savePath .= $dirName . '/';
                $saveUrl .= $dirName . '/';
                if (!file_exists($savePath)) {
                    @mkdir($savePath, 0777);
                    @chmod($savePath, 0777);
                }
            }
            $ymd = date('Ymd');
            $savePath .= $ymd . '/';
            $saveUrl .= $ymd . '/';
            if (!file_exists($savePath)) {
                @mkdir($savePath, 0777);
                @chmod($savePath, 0777);
            }
            //新文件名
            $newFileName = date('YmdHis') . '_' . rand(10000, 99999) . '.' . $fileExt;
            //移动文件
            $filePath = $savePath . $newFileName;
            if (move_uploaded_file($tmpName, $filePath) === false) {
                $this->_editorAlert('上传文件失败。');
            }
            if (strcasecmp('image', $dirName) == 0 && $this->upload['mark']['editor']) {
                //加入中图水印
                $this->upFile->imageWaterMark($filePath, $this->upload['mark']['pos']);
            }
            @chmod($filePath, 0777);
            $fileUrl = $saveUrl . $newFileName;

            echo json_encode(array('error' => 0, 'url' => $fileUrl));
            exit;
        }
    }

    protected function _editorAlert($msg) {
        echo(json_encode(array('error' => 1, 'message' => $msg)));
        exit;
    }

}
