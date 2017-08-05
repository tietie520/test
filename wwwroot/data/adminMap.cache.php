<?php

if (!defined('IN_PX'))
    exit;

return array(
    'structure' => array(
        'title' => '文档',
        'menu' => array(
            'archives' => array(
                'name' => '文档管理', 'scope' => array('view', 'add', 'edit', 'delete'),
                'class' => 'ico_list'
            ),
            'category' => array(
                'name' => '栏目管理', 'scope' => array('view', 'add', 'edit', 'delete'),
                'class' => 'ico_type'
            ),
//            'footerLink' => array(
//                'name' => '友情链接管理', 'scope' => array('view', 'add', 'edit', 'delete'),
//                'class' => 'ico_link'
//            ),
//            'ad' => array(
//                'name' => '图片广告管理', 'scope' => array('view', 'add', 'edit', 'delete'),
//                'class' => 'ico_ad'
//            ),
        )
    ),
    'setting' => array(
        'title' => '系统',
        'menu' => array(
            'user' => array(
                'name' => '管理员列表', 'scope' => array('view', 'add', 'edit', 'delete'),
                'class' => 'ico_user'
            ),
            'role' => array(
                'name' => '管理角色列表', 'scope' => array('view', 'add', 'edit', 'delete'),
                'class' => 'ico_role'
            ),
            'content' => array(
                'name' => '配置列表', 'scope' => array('edit'), 'class' => 'ico_gear'
            ),
            'action' => array(
                'name' => '操作日志', 'scope' => array('view'), 'class' => 'ico_gear'
            )
        )
    )
);
