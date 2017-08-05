<?php
namespace App\Service;

interface PHPZip
{
    public function visitFile($path);

    public function Zip($dir, $saveName);

    public function ZipAndDownload($dir);

    public function unZip($zipfile, $to, $index = Array(-1));

    public function GetZipInnerFilesInfo($zipfile);

    public function GetZipComment($zipfile);
}