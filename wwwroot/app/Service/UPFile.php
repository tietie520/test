<?php
namespace App\Service;

interface UPFile {
    public function setFolder($uploadType = 'img', $folder = null);

    public function deleteMerge($userId);

    public function user($userId, $upFileName, $mode = 'uuid');

    public function createUserFile(Array $resource, $thumb = true, $whLimit = null, $markImg = false);

    /**
     * 上传图片并获取上传之后的图片或文件名
     * @param $inputName
     * @param string $importMode
     * @param string $uploadType
     * @return string
     */
    public function upload($inputName, $importMode = 'fileNameSysWrite', $uploadType = 'img');

    public function createImg($src, $markImg = null, $textMark = null);

    /**
     *
     * @param string $imgSrc 背景图片，即需要加水印的图片，暂只支持GIF,JPG,PNG格式；
     * @param type $waterPos 水印位置，有10种状态，0为随机位置；
     *                          1为顶端居左，2为顶端居中，3为顶端居右；
     *                          4为中部居左，5为中部居中，6为中部居右；
     *                          7为底端居左，8为底端居中，9为底端居右；
     * @param string $waterSrc 图片水印的图片源或者文字水印的文字 文字水印，即把文字作为为水印，支持ASCII码，不支持中文；
     * @param type $isUseWaterImage 是否使用水印图片
     * @param type $textFont 文字大小，值为1、2、3、4或5，默认为5；
     * @param type $textColor 文字颜色，值为十六进制颜色值，默认为#ff0000(红色)；
     * @return type
     */
    public function imageWaterMark($imgSrc, $waterPos = 5, $waterSrc = null, $isUseWaterImage = true, $textFont = 5, $textColor = '#ff0000');

    /**
     * @param $fileName
     * @param array $uploadPath
     * @return bool
     */
    public function deleteFile($fileName, $uploadPath = array('pics', 'files'));
}