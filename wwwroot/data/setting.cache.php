<?php

if (!defined('IN_PX'))
    exit;
return array(
    'aryPicExtName' => array('gif', 'jpg', 'jpeg', 'bmp', 'png'),
    'aryFileExtName' => array('zip'),
    'aryUploadPath' => array('pics', 'files'),
    'aryBool' => array('否', '是'),
    'arySex' => array('女', '男', '保密'),
    'aryAnchorStatus' => array('不使用', '使用'),
    'aryFooterLinkTarget' => array('_blank', '_self'),
    'aryFooterLinkType' => array('当前页', '全站'),
    'aryNavType' => array('主导航', '副导航', '跟随上级'),
    'aryDisplay' => array('不显示', '显示'),
    'aryGoodsDisplay' => array('下架', '上架'),
    'aryPart' => array('列表栏目(允许在本栏目发布文档)', '单页栏目(生成单页，可使用seo及高级功能)', '外部链接(在"文件保存目录"处填写网址)'),
    'aryPartShow' => array('列表栏目', '单页栏目', '外部链接'),
    'aryScope' => array('add' => '添加', 'edit' => '修改', 'delete' => '删除', 'view' => '查看', 'approved' => '审核'),
    'aryChannelTypeMapping' => array(
        'zh-CN' => array(
            // 内容模型名称，内容模型相对路径，列表显示的分页信息数量，列表页若存在列表用于显示详细信息的路径(不存在可为空),'首页的列表显示数量'
            0 => array('案例展示模型', 'case', 8, 'detail', 8) ,
            1 => array('新闻列表模型', 'news', 3, 'detail', 3),
            2 => array('图文单页模型', 'service', 1),
            3 => array('图文单页模型（首页more）', 'about',1),
            4 => array('图片展示模型', 'photo', 6, 'detail'),
            5 => array('图片', 'photos', 6, 'detail')
        )
    ),
    'aryArchivesDeleteCacheBindId' => array(
//		'cacheIndexNotice',
//		'cacheIndexArchivesList',
//		'cacheIndexMedicalEquipment'
    ),
    'aryShopDeleteCacheBindId' => array(
        'cacheHomepageLatestShop',
    ),
    'aryAreaType' => array(
        '直辖市',
        '华北地区',
        '东北地区',
        '华东地区',
        '华中地区',
        '华南地区',
        '西北地区',
        '西南地区',
        '其他地区'
    ),
    'aryMunicipality' => array(
        '0' => '全国',
        '1' => '上海',
        '2' => '北京',
        '3' => '天津',
        '4' => '重庆'
    ),
    'aryArchivesStatus' => array(
        '普通',
        '最新',
        '热门',
        '置顶'
    ),
    'aryGoodsStatus' => array(
        '普通',
        '新品',
        '特价',
        '热卖',
        '人气'
    ),
    'aryAd' => array(
        '首页切换图 1260*376px',
        '首页右侧 229*400'
    ),
    //操作日志使用
    'aryOption' => array(
        'add' => '添加',
        'edit' => '修改',
        'delete' => '删除',
        'view' => '查看',
        'read' => '查看',
        'approved' => '审核',
        'cancel' => '撤销',
        'export' => '导出',
        'setfieldvalue' => '修改状态',
        'setdisplay' => '修改状态',
        'setfieldstatus' => '修改状态',
        'delfile' => '删除文件',
        'delmultiplefile' => '删除文件',
        'upload' => '上传文件',
        'login' => '登陆',
        'logout' => '退出',
        'editpwd' => '修改密码',
        'filemanager' => '文件管理'
    ),
);
