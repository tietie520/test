<?php

namespace Phoenix\Support;

if (!defined('IN_PX'))
    exit;

class MsgHelper {

    public static final function get($errCode, $arg = '') {
        $_errList = array(
            '0x00000001' => '系统级错误',
            '0x00000002' => "{$arg} 包中未匹配任何url规则，请检查路由文件",
            '0x00000003' => "未在路由配置中找到 {$arg}",
            '0x00000004' => "{$arg} 模块包裹类入口数据异常",
            '0x00000005' => "未找到拦截栈 -> {$arg}",
            '0x00000006' => "404 未找到页面文件 -> {$arg}",
            '0x00000007' => 'REST application/xml.ResponseBody 必须返回array',
            '0x00000008' => 'REST __ACCEPT__ required',
            '0x00000009' => 'interceptor redirect is array.root("/") index is not found.',
            '0x00000010' => 'route config language setting error.current value type is array,but "id" and "shared" is not found.',
            '0x00001001' => "AOP __Around 未执行 proceed() 方法",
            '0x00002001' => '未指定数据源，无法连接数据库',
            '0x00002002' => '数据源配置不是数组',
            '0x00002101' => '文档生成失败',
            '0x00003001' => 'batch is not found.',
            '0x00004001' => 'cache setting error.',
        );

        return $_errList[$errCode];
    }

    public static final function json($errCode, $errMsg = 'ok', $rsp = null) {
        //header('Content-type: text/plain; charset=utf-8');
        if (is_numeric($errCode)) {
            return Helpers::jsonEncode(self::err($errCode, $errMsg, $rsp));
        }
        return Helpers::jsonEncode(self::aryJson($errCode, $errMsg, $rsp));
    }

    public static final function aryJson($status, $desc = '', $rsp = null) {
        $_desc = array(
            'SUCCESS' => $desc,
            'ERROR' => $desc,
            'INTERCEPTOR' => '未通过拦截器验证',
            'VALIDCODE_ERROR' => '您输入的验证码和系统产生的不一致',
            'VALIDCODE_LOSE' => '请刷新页面重新生成验证码',
            'VALIDCODE_EMPTY' => '验证码不能为空',
            'SESSION_IS_NULL' => '会话状态丢失',
            'USER_EMPTY' => '用户名不能为空',
            'PASS_EMPTY' => '密码不能为空',
            'PASSWORD_ERROR' => '用户名或密码错误',
            'NO_CHANGES' => '未做任何改变',
            'POWER_ERROR' => '您未有访问此资源的权限',
            'DB_ERROR' => '数据库出错',
            'NEED' => '此项无法删除',
            'IS_EXISTS' => '已存在！请重新输入',
            'DB_DELETE_ERR' => "从数据库删除 {$desc} 时出错",
            'SYS_BUSY' => '系统繁忙'
        );
        return array(
            'status' => $status,
            'desc' => isset($_desc[$status]) ? $_desc[$status] : $desc,
            'rsp' => $rsp
        );
    }

    public static final function err($errCode, $errMsg = 'ok', $rsp = null) {
        $_msg = array(
            -1 => 'system busy',            //SYS_BUSY
            0 => 'ok',                      //SUCCESS
            10000 => 'no changes',          //NO_CHANGES
            10001 => 'error',               //ERROR
            10002 => 'interceptor',         //INTERCEPTOR
            10003 => "db delete {$errMsg} err", //DB_DELETE_ERR
            10004 => 'is_exists',               //IS_EXISTS
            10005 => 'need',                    //NEED
            10006 => 'db error',               //DB_ERROR
            10007 => 'seo url is exists',      //seo url重复存在 SEO_URL_IS_EXISTS
            10008 => 'password error',      //PASSWORD_ERROR 密码错误
            10009 => 'no exists',      //用户不存在
            10010 => 'is lock',       //账号已被锁定
            10101 => 'need user name',
            10102 => 'need password',
            10103 => '用户不存在',
            10104 => '参数错误',
            10105 => '不能添加自己',
            10106 => '重复请求或已经是好友',
        );
        $_ret = array(
            'errcode' => $errCode,
            'errmsg' => isset($_msg[$errCode]) ? $_msg[$errCode] : $errMsg
        );
        if (!is_null($rsp)) {
            if (is_array($rsp)) {
                $_ret = array_merge($_ret, $rsp);
            } else {
                $_ret['rsp'] = $rsp;
            }
        }
        return $_ret;
    }

}
