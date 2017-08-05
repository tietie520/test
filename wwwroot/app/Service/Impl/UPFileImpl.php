<?php

namespace App\Service\Impl;

if (!defined('IN_PX'))
    exit;

use App\Service\UPFile;
use App\Tools\Auxi;
use Phoenix\Support\File;
use Phoenix\Log\Log4p as logger;

class UPFileImpl implements UPFile {

    const VERSION = '1.1.2';

    private function __Service() {}

    private function __Value($cfg, $setting, $__UPLOAD__, $__CDN__) {}

    private function __Bundle($upload = 'data/upload.cache.php') {}

    private $annexFolder = ''; //附件存放点，默认为：uploads
    private $upFileFolder = 'pics'; //附件存放点，默认为：pics
    private $normalFolder = 'n'; //普通大小的图片
    private $smallFolder = 's'; //缩略图存放路径，注：必须是放在 $annexFolder下的子目录，默认为：smallimg
    private $largeFolder = 'l'; //水印图片存放处 marking
    //上传的类型，默认为：jpg JPG,文件类型可以在此添加和修改
    private $aryPicExtName = null;
    private $aryFileExtName = null;
    private $aryUploadPath = null;
    private $uploadType = 'img';
    private $upPath = '';
    private $fontType; //字体
    private $noneErr = true;
    private $siteName = '';
    private $cdn;

    public function __construct(& $setting, & $cfg, &$__UPLOAD__, &$__CDN__) {
        $this->annexFolder = ROOT_PATH . $__UPLOAD__ . DIRECTORY_SEPARATOR;
        $this->fontType = $this->annexFolder . 'arial.ttf';
        $this->aryPicExtName = & $setting['aryPicExtName'];
        $this->aryFileExtName = & $setting['aryFileExtName'];
        $this->aryUploadPath = & $setting['aryUploadPath'];
        $this->siteName = & $cfg['site_name'];
        $this->cdn = & $__CDN__;
    }

    public function setFolder($uploadType = 'img', $folder = null) {
        if (!is_null($folder)) {
            $this->_createFolder($uploadType, $folder);
        }
        return $this;
    }

    private function _createFolder($uploadType = 'img', $folder = null) {
        if ($this->aryUploadPath) {
            if (strcasecmp('img', $uploadType) == 0) {
                $this->upPath = explode('/',
                    trim(is_null($folder) ? $this->aryUploadPath[0] : $folder,
                        '/'));
                $this->uploadType = 0; //图片
            } else {
                $this->upPath = explode('/', trim($this->aryUploadPath[1], '/'));
                $this->uploadType = 1;
            }
        } else {
            $this->upPath = $this->upFileFolder;
        }
        $this->upFileFolder = $this->annexFolder;
        foreach ($this->upPath as $_v) {
            $this->upFileFolder .= $_v . DIRECTORY_SEPARATOR;
            if (!is_dir($this->upFileFolder)) {
                @mkdir($this->upFileFolder, 0777);
                @chmod($this->upFileFolder, 0777);
            }
        }

        if (!is_dir(TMP_PATH)) {
            @mkdir(TMP_PATH, 0777);
            @chmod(TMP_PATH, 0777);
        }

        if ($this->uploadType == 0 && strlen($this->smallFolder) == 1) {//图片
            $this->smallFolder = $this->upFileFolder . $this->smallFolder . DIRECTORY_SEPARATOR;
            $this->largeFolder = $this->upFileFolder . $this->largeFolder . DIRECTORY_SEPARATOR;
            $this->normalFolder = $this->upFileFolder . $this->normalFolder . DIRECTORY_SEPARATOR;
            if (!is_dir($this->smallFolder)) {
                @mkdir($this->smallFolder, 0777);
                @chmod($this->smallFolder, 0777);
            }
            if (!is_dir($this->largeFolder)) {
                @mkdir($this->largeFolder, 0777);
                @chmod($this->largeFolder, 0777);
            }
            if ($this->upload['mark']['medium'] && !is_dir($this->normalFolder)) {
                @mkdir($this->normalFolder, 0777);
                @chmod($this->normalFolder, 0777);
            }
        }
    }

    public function deleteMerge($userId) {
        $_path = $this->annexFolder . 'u' . DIRECTORY_SEPARATOR . $userId . DIRECTORY_SEPARATOR;
        if (is_file($_path . 'avatar_front.png')) {
            @unlink($_path . 'avatar_front.png');
        }
        if (is_file($_path . 'avatar_rear.png')) {
            @unlink($_path . 'avatar_rear.png');
        }
    }

    public function user($userId, $upFileName, $mode = 'uuid') {
        $_uploadType = 'file';
        $_pathInfo = Auxi::pathInfo($_FILES[$upFileName]['name']);
        if (in_array(strtolower($_pathInfo['EXTENSION']), $this->aryPicExtName)) {
            $_uploadType = 'img';
        }
        $_tmpFileName = $this->_checkFile($upFileName, $mode, $_uploadType);
        if ($this->noneErr) {
            $_resource = array();
            $_time = time();
            $_upFolder = 'u' . DIRECTORY_SEPARATOR . $userId . DIRECTORY_SEPARATOR . date('Y', $_time) . DIRECTORY_SEPARATOR
                . date('md', $_time);
            File::mkdir($_upFolder, $this->annexFolder);
            $_resource = array(
                'name' => $_tmpFileName,
                'type' => $_uploadType,
                'size' => $_FILES[$upFileName]['size'],
                'path' => $_upFolder,
                'cdn' => $this->cdn
            );
            if ($_uploadType == 'img') {
                $_info = getimagesize($_FILES[$upFileName]['tmp_name']);
                $_resource['width'] = $_info[0]; //取得背景图片的宽
                $_resource['height'] = $_info[1]; //取得背景图片的高
            }
            //扔入临时文件夹中
            move_uploaded_file($_FILES[$upFileName]['tmp_name'],
                TMP_PATH . $_tmpFileName);

            return $_resource;
        }
        return $_tmpFileName;
    }

    public function createUserFile(Array $resource, $thumb = true, $whLimit = null, $markImg = false) {
        $_src = $resource['name'];
        if ($_src && is_file(TMP_PATH . $_src)) {
            $_srcPath = TMP_PATH . $_src;
            $_srcInfo = $this->_getInfo($_srcPath);

            if (strcmp('file', $resource['type']) == 0) {
//                rename($_srcPath, ROOT_PATH . $resource['path'] . $_srcInfo['name']);
                copy($_srcPath, $this->annexFolder . $resource['path'] . DIRECTORY_SEPARATOR . $_srcInfo['name']);
                return true;
            } else if (strcmp('img', $resource['type']) == 0) {
                $markImg = is_null($markImg) ? $this->upload['mark']['water'] : $markImg;

                switch ($_srcInfo['type']) {
                    case 1:
                        $_imgHandle = imagecreatefromgif($_srcPath);
                        break;
                    case 2:
                        $_imgHandle = imagecreatefromjpeg($_srcPath);
                        break;
                    case 3:
                        $_imgHandle = imagecreatefrompng($_srcPath);
                        imagesavealpha($_imgHandle, true); //保持透明
                        break;
                    default:
                        $this->noneErr = false;
                        return '未知图片格式';
                        break;
                }

                if (is_null($whLimit)) {
                    $whLimit = array(
                        'width' => $this->upload['source']['width'],
                        'height' => $this->upload['source']['height']
                    );
                }

                //创建大图
                $_largeImg = $this->_createImg($_imgHandle,
                    $this->annexFolder . $resource['path'] . DIRECTORY_SEPARATOR,
                    $_srcInfo,
                    $whLimit['width'], $whLimit['height']);

                if ($thumb) {
                    $_pathInfo = Auxi::pathInfo($_srcInfo['name']);
                    $_srcInfo['name'] = $_pathInfo['FILENAME'] . 'x'
                        . $this->upload['thumb']['width']
                        . '.' . $_pathInfo['EXTENSION'];

                    $_smallImg = $this->_createImg($_imgHandle,
                        $this->annexFolder . $resource['path'] . DIRECTORY_SEPARATOR,
                        $_srcInfo,
                        $this->upload['thumb']['width'], $this->upload['thumb']['height']);
                }


                imagedestroy($_imgHandle);

                //删除源
                @unlink($_srcPath);
                if ($markImg) {
                    //大图水印
                    $_tmp = $this->imageWaterMark($resource['path'] . $_src, $this->upload['mark']['pos'],
                        $this->annexFolder . $this->upload['mark']['larger']);

                    if (!$this->noneErr) {
                        return $_tmp;
                    }
                }
                return true;
            }
        }
        return false;
    }

    private function _checkFile($inputName, $importMode = 'fileNameSysWrite', $uploadType = 'img') {
        if ($_FILES[$inputName]['size'] <= 0) {
            $this->noneErr = false;
            return '请选择您要上传的文件！';
        }
        if ((round($_FILES[$inputName]['size'] / 1024)) > $this->upload['source']['limit']) {
            $this->noneErr = false;
            return '文件不允许超过 ' . $this->upload['source']['limit'] . ' k';
        }
        $_pathInfo = Auxi::pathInfo($_FILES[$inputName]['name']);
        $_fileExt = strtolower($_pathInfo['EXTENSION']);
        if ($this->aryPicExtName && $this->aryFileExtName) {
            if (!in_array($_fileExt, $this->aryPicExtName) && strcasecmp('img', $uploadType) == 0) {
                $this->noneErr = false;
                return '请选择允许的图片格式上传';
            }
            if (!in_array($_fileExt, $this->aryFileExtName) && strcasecmp('file', $uploadType) == 0) {
                $this->noneErr = false;
                return '请选择允许的文件格式上传';
            }
        }
        $_fileExt = '.' . $_fileExt;
        switch ($importMode) {
            case 'fileNameSysWrite' :
                $_tempFileName = date('Ymd,h,i,s') . Auxi::randomNum() . $_fileExt;
                break;
            case 'uuid' :
                $_tempFileName = Auxi::guid() . $_fileExt;
                break;
            default :
                $_tempFileName = $_pathInfo['FILENAME']
                    . '-' . date('Y-n-j-G,i,s') . $_fileExt;
                break;
        }
        if (is_file($this->normalFolder . $_tempFileName)) {
            $this->noneErr = false;
            return '此文件已存在，请重新上传';
        }
        return $_tempFileName;
    }

    /**
     * 上传图片并获取上传之后的图片或文件名
     * @param $inputName
     * @param string $importMode
     * @param string $uploadType
     * @return string
     */
    public function upload($inputName, $importMode = 'fileNameSysWrite', $uploadType = 'img') {
        $_tempFileName = $this->_checkFile($inputName, $importMode, $uploadType);
        if ($this->noneErr) {
            $_resource = array();
            $this->_createFolder($uploadType);
            $_resource = array(
                'name' => $_tempFileName,
                'size' => $_FILES[$inputName]['size']
            );
            $_path = $this->upFileFolder;
            if ($this->uploadType == 0) {
                $_info = getimagesize($_FILES[$inputName]['tmp_name']);
                $_resource['width'] = $_info[0]; //取得背景图片的宽
                $_resource['height'] = $_info[1]; //取得背景图片的高
                $_path = TMP_PATH;
            }
            //扔入临时文件夹中
            move_uploaded_file($_FILES[$inputName]['tmp_name'],
                $_path . $_tempFileName);

            return $_resource;
        }
        return $_tempFileName;
    }

    public function createImg($src, $markImg = null, $textMark = null) {
        if ($src && is_file(TMP_PATH . $src)) {
            $markImg = is_null($markImg) ? $this->upload['mark']['water'] : $markImg;
            $textMark = is_null($textMark) ? $this->upload['mark']['text'] : $textMark;

            $_srcPath = TMP_PATH . $src;
            $_srcInfo = $this->_getInfo($_srcPath);
            //$_photo = $_srcPath; //获得图片源
            switch ($_srcInfo['type']) {
                case 1:
                    $_imgHandle = imagecreatefromgif($_srcPath);
                    break;
                case 2:
                    $_imgHandle = imagecreatefromjpeg($_srcPath);
                    break;
                case 3:
                    $_imgHandle = imagecreatefrompng($_srcPath);
                    imagesavealpha($_imgHandle, true); //保持透明
                    break;
                default:
                    $this->noneErr = false;
                    return '未知图片格式';
                    break;
            }

            $this->_createFolder('img');

            //创建大图
            $_largeImg = $this->_createImg($_imgHandle, $this->largeFolder, $_srcInfo,
                $this->upload['source']['width'], $this->upload['source']['height']);
            if ($this->upload['medium']['create']) {
                $_normalImg = $this->_createImg($_imgHandle, $this->normalFolder, $_srcInfo,
                    $this->upload['medium']['width'], $this->upload['medium']['height']);
            }
            $_smallImg = $this->_createImg($_imgHandle, $this->smallFolder, $_srcInfo,
                $this->upload['thumb']['width'], $this->upload['thumb']['height']);

            imagedestroy($_imgHandle);

            //删除源图
            @unlink($_srcPath);
            if ($markImg) {
                if ($textMark) {
                    $this->imageWaterMark($this->largeFolder . $src, $this->upload['mark']['pos'],
                        $this->siteName, false, 5, '#ff0000');
                } else {
                    //大图水印
                    $_tmp = $this->imageWaterMark($this->largeFolder . $src, $this->upload['mark']['pos'],
                        $this->annexFolder . $this->upload['mark']['larger']);

                    //中图水印
                    if ($this->upload['mark']['medium']) {
                        $_tmp = $this->imageWaterMark($this->normalFolder . $src, $this->upload['mark']['pos'],
                            $this->annexFolder . $this->upload['mark']['small']);
                    }
                    if (!$this->noneErr) {
                        return $_tmp;
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     *
    @param string $imgSrc 背景图片，即需要加水印的图片，暂只支持GIF,JPG,PNG格式；
    @param type $waterPos 水印位置，有10种状态，0为随机位置；
     * 					      1为顶端居左，2为顶端居中，3为顶端居右；
     * 					      4为中部居左，5为中部居中，6为中部居右；
     * 				          7为底端居左，8为底端居中，9为底端居右；
    @param string $waterSrc 图片水印的图片源或者文字水印的文字 文字水印，即把文字作为为水印，支持ASCII码，不支持中文；
    @param type $isUseWaterImage 是否使用水印图片
    @param type $textFont 文字大小，值为1、2、3、4或5，默认为5；
    @param type $textColor 文字颜色，值为十六进制颜色值，默认为#ff0000(红色)；
    @return type
     */
    public function imageWaterMark($imgSrc, $waterPos = 5, $waterSrc = null, $isUseWaterImage = true,
                                   $textFont = 5, $textColor = '#ff0000') {
        //$this->annexFolder . $this->upload['mark']['larger'];
        //读取背景图片
        if (is_file($imgSrc)) {
            $_imgSrcInfo = getimagesize($imgSrc);
            $_groundW = $_imgSrcInfo[0]; //取得背景图片的宽
            $_groundH = $_imgSrcInfo[1]; //取得背景图片的高
            switch ($_imgSrcInfo[2]) { //取得背景图片的格式
                case 1 :
                    $_imgSrcIm = imagecreatefromgif($imgSrc);
                    break;
                case 2 :
                    $_imgSrcIm = imagecreatefromjpeg($imgSrc);
                    break;
                case 3 :
                    $_imgSrcIm = imagecreatefrompng($imgSrc);
                    imagesavealpha($_imgSrcIm, true);
                    break;
                default:
                    $this->noneErr = false;
                    return '背景图片格式未知';
                    break;
            }
        } else {
            $this->noneErr = false;
            return '需要加水印的图片不存在！';
        }
        //读取水印文件
        $_waterInfo = null;
        if (is_null($waterSrc)) {
            $waterSrc = $this->annexFolder . $this->upload['mark']['small'];
            $_waterInfo = getimagesize($waterSrc);
            $_waterW = $_waterInfo[0]; //取得水印图片的宽
            $_waterH = $_waterInfo[1]; //取得水印图片的高
            //如果图片比小水印宽高大两倍以上则使用大水印
            //一般为宽高600像素或者宽度持平但是高度大于500以上图片
            if (($_groundW > $_waterW * 5 && $_groundH > $_waterH * 5) || ($_groundW > $_waterW && $_groundH > $_waterW * 5)) {
                $waterSrc = $this->annexFolder . $this->upload['mark']['larger'];
                unset($_waterInfo);
                $_waterInfo = null;
            }
        }
        if (is_file($waterSrc)) {
            if (is_null($_waterInfo)) {
                $_waterInfo = getimagesize($waterSrc);
                $_waterW = $_waterInfo[0];
                $_waterH = $_waterInfo[1];
            }
            switch ($_waterInfo[2]) { //取得水印图片的格式
                case 1 :
                    $_waterIm = imagecreatefromgif($waterSrc);
                    break;
                case 2 :
                    $_waterIm = imagecreatefromjpeg($waterSrc);
                    break;
                case 3 :
                    $_waterIm = imagecreatefrompng($waterSrc);
                    imagesavealpha($_waterIm, true);
                    break;
                default:
                    $this->noneErr = false;
                    return '水印图片格式未知';
                    break;
            }
        }
        //水印位置
        if ($isUseWaterImage) { //图片水印
            $_w = $_waterW;
            $_h = $_waterH;
            $_label = '图片的';
        } else {   //文字水印
            //取得使用 TrueType 字体的文本的范围
            $temp = imagettfbbox(ceil($textFont * 5.5), 0, $this->fontType, $waterSrc);
            $_w = $temp[2] - $temp[6];
            $_h = $temp[3] - $temp[7];
            unset($temp);
            $_label = '文字区域';
        }
        if ($_groundW < $_w || $_groundH < $_h) {
            $this->noneErr = false;
            return '图片的长宽比水印' . $_label . '还小，请上传宽度大于300像素的图片！';
        }
        switch ($waterPos) {
            case 0://随机
                $posX = rand(0, ($_groundW - $_w));
                $posY = rand(0, ($_groundH - $_h));
                break;
            case 1://1为顶端居左
                $posX = 0;
                $posY = 0;
                break;
            case 2://2为顶端居中
                $posX = ($_groundW - $_w) / 2;
                $posY = 0;
                break;
            case 3://3为顶端居右
                $posX = $_groundW - $_w;
                $posY = 0;
                break;
            case 4://4为中部居左
                $posX = 0;
                $posY = ($_groundH - $_h) / 2;
                break;
            case 5://5为中部居中
                $posX = ($_groundW - $_w) / 2;
                $posY = ($_groundH - $_h) / 2;
                break;
            case 6://6为中部居右
                $posX = $_groundW - $_w;
                $posY = ($_groundH - $_h) / 2;
                break;
            case 7://7为底端居左
                $posX = 0;
                $posY = $_groundH - $_h;
                break;
            case 8://8为底端居中
                $posX = ($_groundW - $_w) / 2;
                $posY = $_groundH - $_h;
                break;
            case 9://9为底端居右
                $posX = $_groundW - $_w;
                $posY = $_groundH - $_h;
                break;
            default://随机
                $posX = rand(0, ($_groundW - $_w));
                $posY = rand(0, ($_groundH - $_h));
                break;
        }
        //设定图像的混色模式
        imagealphablending($_imgSrcIm, true);
        if ($isUseWaterImage) { //图片水印
            imagecopy($_imgSrcIm, $_waterIm, $posX, $posY, 0, 0, $_waterW, $_waterH); //拷贝水印到目标文件
        } else {//文字水印
            if (!empty($textColor) && (strlen($textColor) == 7)) {
                $R = hexdec(substr($textColor, 1, 2));
                $G = hexdec(substr($textColor, 3, 2));
                $B = hexdec(substr($textColor, 5));
            } else {
                $this->noneErr = false;
                imagedestroy($_imgSrcIm);
                return '水印文字颜色格式不正确！';
            }
            imagestring($_imgSrcIm, $textFont, $posX, $posY, $waterSrc,
                imagecolorallocate($_imgSrcIm, $R, $G, $B));
        }
        //生成水印后的图片
        @unlink($imgSrc);
        switch ($_imgSrcInfo[2]) { //取得背景图片的格式
            case 1 :
                imagegif($_imgSrcIm, $imgSrc);
                break;
            case 3 :
                imagepng($_imgSrcIm, $imgSrc);
                break;
            default:
                imagejpeg($_imgSrcIm, $imgSrc, 90); //统一生成jpg格式
                break;
        }
        //释放内存
        if (isset($_waterInfo)) {
            unset($_waterInfo);
        }
        if (isset($_waterIm)) {
            imagedestroy($_waterIm);
        }
        unset($_imgSrcInfo);

        imagedestroy($_imgSrcIm);
    }

    /**
     * @param $fileName
     * @param array $uploadPath
     * @return bool
     */
    public function deleteFile($fileName, $uploadPath = array('pics', 'files')) {
        $_tempPath = '';
        $_uploadType = 'file';
        $_pathInfo = Auxi::pathInfo($fileName);
        if (in_array(strtolower($_pathInfo['EXTENSION']), $this->aryPicExtName)) {
            $_uploadType = 'img';
        }
        if (strcasecmp('img', $_uploadType) == 0) { //如果是图片
            if (is_file(TMP_PATH . $fileName)) {
                @unlink(TMP_PATH . $fileName);
            }
            $_tempPath = $this->annexFolder . str_replace('/', DIRECTORY_SEPARATOR, $uploadPath[0])
                . DIRECTORY_SEPARATOR;
            if (is_file($_tempPath . 's' . DIRECTORY_SEPARATOR . $fileName)) {
                @unlink($_tempPath . 'l' . DIRECTORY_SEPARATOR . $fileName);
                if ($this->upload['mark']['medium']) {
                    @unlink($_tempPath . 'n' . DIRECTORY_SEPARATOR . $fileName);
                }
                @unlink($_tempPath . 's' . DIRECTORY_SEPARATOR . $fileName);
            }
        } else {
            if (is_file($this->annexFolder . $uploadPath[1] . DIRECTORY_SEPARATOR . $fileName)) {
                @unlink($this->annexFolder . $uploadPath[1] . DIRECTORY_SEPARATOR . $fileName);
            }
        }
        return true;
    }

    /**
     * 获取上传的图片的信息放入数组中
     * @param type $src
     * @return type
     */
    private function _getInfo($src) {
        $_imgInfo = getimagesize($src);
        $_aryInfo = array();
        $_aryInfo['src'] = $src;
        $_aryInfo['width'] = $_imgInfo[0];
        $_aryInfo['height'] = $_imgInfo[1];
        $_aryInfo['type'] = $_imgInfo[2];
        $_aryInfo['name'] = basename($src);
        return $_aryInfo;
    }

    /**
     * 将获取的上传图片转成指定大小的缩略图
     * @param type $imgHandle
     * @param type $path
     * @param type $srcInfo
     * @param type $width
     * @param type $height
     * @return type
     */
    private function _createImg($imgHandle, $path, $srcInfo, $width, $height) {
        //$_srcName = $srcInfo['name']; //缩略图片名称
        $_srcW = $srcInfo['width'];
        $_srcH = $srcInfo['height'];
        if ($_srcW < $width && $_srcH < $height) {
            copy($srcInfo['src'], $path . $srcInfo['name']);
        } else {
            $_width = ($width > $_srcW) ? $_srcW : $width;
            $_height = ($height > $_srcH) ? $_srcH : $height;
            if ($_srcW * $_width > $_srcH * $_height) {
                $_height = round($_srcH * $_width / $_srcW);
            } else {
                $_width = round($_srcW * $_height / $_srcH);
            }
            $_tempImg = null;
            if (function_exists('imagecreatetruecolor')) {
                $_tempImg = imagecreatetruecolor($_width, $_height);
                imagealphablending($_tempImg, false);
                imagesavealpha($_tempImg, true);
                imagecopyresampled($_tempImg, $imgHandle, 0, 0, 0, 0, $_width, $_height, $_srcW, $_srcH);
            } else {
                $_tempImg = imagecreate($_width, $_height);
                imagecopyresized($_tempImg, $imgHandle, 0, 0, 0, 0, $_width, $_height, $_srcW, $_srcH);
            }
            if (is_file($path . $_tempImg)) {
                @unlink($path . $_tempImg);
            }

            switch ($srcInfo['type']) { //取得背景图片的格式
                case 1 :
                    imagegif($_tempImg, $path . $srcInfo['name']);
                    break;
                case 3 :
                    imagepng($_tempImg, $path . $srcInfo['name']);
                    break;
                default:
                    imagejpeg($_tempImg, $path . $srcInfo['name'], 90);
                    break;
            }
            imagedestroy($_tempImg);
        }

        return $srcInfo['name'];
    }

    public function __get($name) {
        return $this->$name;
    }

    public function __set($name, $value) {
        $this->$name = $value;
    }

}
