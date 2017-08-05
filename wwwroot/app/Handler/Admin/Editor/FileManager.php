<?php

namespace App\Handler\Admin\Editor;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;

/**
 * 编辑器图片上传
 */
class FileManager extends AbstractCommon {

    protected function __Value($cfg, $setting, $__UPLOAD__ = null) {}

    public function processRequest(Array & $context) {
        $phpUrl = dirname($_SERVER['PHP_SELF']) . '/';

        //根目录路径，可以指定绝对路径，比如 /var/www/attached/
        $savePath = ROOT_PATH . $context['__UPLOAD__'] . DIRECTORY_SEPARATOR . 'editor' . DIRECTORY_SEPARATOR;
        //根目录URL，可以指定绝对路径，比如 http://www.yoursite.com/attached/
        $rootListDir = $phpUrl . '../../../uploads/editor/';
        //图片扩展名
        $extArr = array('gif', 'jpg', 'jpeg', 'png', 'bmp');

        //目录名
        $dirName = empty($_POST['dir']) ? '' : trim($_POST['dir']);
        if (!in_array($dirName, array('', 'image', 'flash', 'media', 'file'))) {
            echo 'Invalid Directory name.';
            exit;
        }

        $rootPath = '';
        if ($dirName !== '') {
            $rootPath .= $dirName . '/';
            $rootListDir .= $dirName . '/';
            if (!file_exists($rootPath)) {
                @mkdir($rootPath, 0777);
                @chmod($rootPath, 0777);
            }
        }

        //根据path参数，设置各路径和URL
        if (empty($_POST['path'])) {
            $currentPath = realpath($rootPath) . '/';
            $currentUrl = $rootListDir;
            $currentDirPath = '';
            $moveupDirPath = '';
        } else {
            $currentPath = realpath($rootPath) . '/' . $_POST['path'];
            $currentUrl = $rootListDir . $_POST['path'];
            $currentDirPath = $_POST['path'];
            $moveupDirPath = preg_replace('/(.*?)[^\/]+\/$/', '$1', $currentDirPath);
        }
        echo realpath($rootPath);
        //排序形式，name or size or type
        $order = empty($_POST['order']) ? 'name' : strtolower($_POST['order']);

        //不允许使用..移动到上一级目录
        if (preg_match('/\.\./', $currentPath)) {
            echo 'Access is not allowed.';
            exit;
        }
        //最后一个字符不是/
        if (!preg_match('/\/$/', $currentPath)) {
            echo 'Parameter is not valid.';
            exit;
        }
        //目录不存在或不是目录
        if (!file_exists($currentPath) || !is_dir($currentPath)) {
            echo 'Directory does not exist.';
            exit;
        }

        //遍历目录取得文件信息
        $fileList = array();
        if ($handle = opendir($currentPath)) {
            $i = 0;
            while (false !== ($filename = readdir($handle))) {
                if ($filename{0} == '.')
                    continue;
                $file = $currentPath . $filename;
                if (is_dir($file)) {
                    $fileList[$i]['is_dir'] = true; //是否文件夹
                    $fileList[$i]['has_file'] = (count(scandir($file)) > 2); //文件夹是否包含文件
                    $fileList[$i]['filesize'] = 0; //文件大小
                    $fileList[$i]['is_photo'] = false; //是否图片
                    $fileList[$i]['filetype'] = ''; //文件类别，用扩展名判断
                } else {
                    $fileList[$i]['is_dir'] = false;
                    $fileList[$i]['has_file'] = false;
                    $fileList[$i]['filesize'] = filesize($file);
                    $fileList[$i]['dir_path'] = '';
                    $file_ext = strtolower(array_pop(explode('.', trim($file))));
                    $fileList[$i]['is_photo'] = in_array($file_ext, $extArr);
                    $fileList[$i]['filetype'] = $file_ext;
                }
                $fileList[$i]['filename'] = $filename; //文件名，包含扩展名
                $fileList[$i]['datetime'] = date('Y-m-d H:i:s', filemtime($file)); //文件最后修改时间
                $i++;
            }
            closedir($handle);
        }

        //排序
        usort($fileList, function ($a, $b) {
            global $order;
            if ($a['is_dir'] && !$b['is_dir']) {
                return -1;
            } else if (!$a['is_dir'] && $b['is_dir']) {
                return 1;
            } else {
                if ($order == 'size') {
                    if ($a['filesize'] > $b['filesize']) {
                        return 1;
                    } else if ($a['filesize'] < $b['filesize']) {
                        return -1;
                    } else {
                        return 0;
                    }
                } else if ($order == 'type') {
                    return strcmp($a['filetype'], $b['filetype']);
                } else {
                    return strcmp($a['filename'], $b['filename']);
                }
            }
        });

        $result = array();
        //相对于根目录的上一级目录
        $result['moveup_dir_path'] = $moveupDirPath;
        //相对于根目录的当前目录
        $result['current_dir_path'] = $currentDirPath;
        //当前目录的URL
        $result['current_url'] = $currentUrl;
        //文件数
        $result['total_count'] = count($fileList);
        //文件列表数组
        $result['file_list'] = $fileList;

        echo(json_encode($result));
    }

}
