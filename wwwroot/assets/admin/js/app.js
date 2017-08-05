$(function() {
  if ($('#loginUserName').length > 0) {
    var _m = $('#loginUserName');
    _m.focus();
    var _loginBtn = $('#loginBtn'), _userPwdHoder = '';
    _loginBtn.unbind('click').attr('disabled', false).click(function() {
      var _loginUserName = $.trim(_m.val());
      var _p = $('#loginUserPwd');
      var _loginUserPwd = $.trim(_p.val());
      if (_userPwdHoder.length > 0 && _userPwdHoder == _loginUserPwd) {
        return false;
      }
      _userPwdHoder = _loginUserPwd;
      if (_loginUserName.length < 2) {
        $.dialog.alert('用户名不能为空', function() {
          _m.focus();
        });
        return false;
      } else if (_loginUserPwd.length < 5 || _loginUserPwd.length > 12) {
        $.dialog.alert('请输入5至12位密码', function() {
          _p.focus();
        });
        return false;
      } else {
        _loginBtn.val('登录中...').attr('disabled', true);
        $.post(PageAction.handlerRoot + 'handler/Admin.System.Login', {
          dbAction: 'login',
          userName: _loginUserName,
          userPwd: _loginUserPwd,
          url: Tools.getParameter('url')
        }, function(data) {
          switch (data.status) {
            case 'SUCCESS' :
              _loginBtn.val('登录成功...');
              window.location.href = data.desc;
              return false;
              break;
            case 'IS_LOCK' :
              $.dialog.alert('账号已被锁定，请在后' + data.desc + '重新尝试');
              break;
            case 'PASSWORD_ERROR' :
              $.dialog.alert('密码错误' + (data.rsp < 3 ? '，还可尝试 ' + data.rsp + ' 次' : ''));
              break;
            case 'NOT_EXISTS' :
              $.dialog.alert('此用户不存在');
              break;
            default :
              $.dialog.alert(data.desc);
              break;
          }
          _loginBtn.val('登　录').attr('disabled', false);
          return false;
        }, 'json');
      }
    });
  }
  if ($('#navigation').length > 0) {
    $('#navigation > dl, #user_info').each(function(i) {
      $(this).on({
        'mouseenter': function() {
          $(this).find('dt').addClass('slt').next().show();
        }, 'mouseleave': function() {
          $(this).find('dt').removeClass('slt').next().hide();
        }
      });
    });
    $('#navigation > dl > dt, .sidebar-menu > ul > li > a').each(function(i) {
      $(this).focus(function() {
        if (this.blur)
          $(this).blur();//去掉虚线框
      });
      if (i > 0)
        $(this).click(function() {
          Navigation.getPage($(this).attr('rel'));
        });
    });
    Navigation.welcome();
    $('#go_welcome').click(function() {
      Navigation.welcome('statistics');
    });
    $('#edit_password').click(function() {
      Tools.addEditPageLoad(PageAction.pack + 'system/password', 500);
    });
  }
  $.ajaxSetup({
    timeout: 30000, //30秒超时
    error: function(xmlHttpRequest, error) {
      if (xmlHttpRequest.responseText.indexOf('(function(){window.top.location.href=') != -1) {
        //error = parsererror jquery提示语法错误
        //eval(xmlHttpRequest.responseText.replace(/<script[^>]+>(.+)<\/script>/ig, '$1'));
        window.top.location.href = xmlHttpRequest.responseText.replace(/.+href='(.+)?url=.+$/ig, '$1') + 'url=' + encodeURIComponent(window.top.location.href);
      }
      else
        $.dialog.locking.alert('error', '与服务器连接超时，请稍候重新尝试');
    }
  });
});
//-------------------------------------=================================JQUERY Documnet=================---------------
var PageAction = {//页面动作单例命名空间
  editor: function() {
    return {
      obj: null,
      _c: [],
      getHtml : function (index) {
        return this._c[index].html();
      },
      setHtml : function (index, html) {
        return this._c[index].html(html);
      },
      create: function(aryId, option) {
        var _this = this;
        window.setTimeout(function() {
          for (var i = 0; i < aryId.length; i++) {
            _this._c[i] = KindEditor.create('#' + aryId[i], option);
          }
        }, 50);
      },
      sync: function() {
        if (this._c.length > 0) {
          for (var i = 0; i < this._c.length; i++) {
            this._c[i].sync().remove();
          }
        }
      },
      sleep: function(n) {//以下在ie报错，停用11-11-28
        var start = new Date().getTime();
        while (true) {
          if (new Date().getTime() - start > n)
            break;
        }
      },
      remove: function() {
        if (this._c.length > 0) {
          for (var i = 0; i < this._c.length; i++) {
            this._c[i].remove();
          }
          this.sleep(100);
        }
      }
    }
  }(),
  root: null,
  pack: null,
  currenPageActionClassName: null,//存放单例名称
  strRepeat: function(str, num) {
    --num;
    if (num == 0)
      return '';
    for (var i = 1; i < num; i++) {
      str += str;
    }
    return str;
  },
  deepColor: function(num) {
    var _temp = '';
    switch (parseInt(num, 10)) {
      case 1:
        _temp = ' style="color:#00c900;"';
        break;
      case 2:
        _temp = ' style="color:blue;"';
        break;
      case 3:
        _temp = ' style="color:gray;"';
        break;
      default:
        _temp = ' style="color:red;"';
        break;
    }
    return _temp;
  }
};
(function(script, i, self) {
  for (; i < script.length; i++) {
    self = !!document.querySelector ?
      script[i].src : script[i].getAttribute('src', 4);

    if (self.substr(self.lastIndexOf('/')).indexOf('function') !== -1)
      break;
  }
  PageAction.view = self.replace(/^(http:\/\/[^\/]*)?(.*\/)js.*$/ig, '$2');
  PageAction.pack = window.location.href.replace(/^(http:\/\/[^\/]*)?(.*\/)system.*$/ig, '$2');

  PageAction.root = PageAction.pack.replace(/^(.*\/)[^\/]*\/$/ig, '$1');
  PageAction.handlerRoot = '/';
})(document.getElementsByTagName('script'), 0);
var Navigation = {
  version: '1.0.2',
  toggleLeft: function () {
    var _left = $('#toggle-left'), _container = $('#container');

    _left.on('click', function () {
      if ($(this).data('arr') == '<') {
        _container.css('margin-left', '16px');
        $(this).css('left', '0px').html('>').data('arr', '>');
      } else {
        _container.css('margin-left', '168px');
        $(this).css('left', '154px').html('<').data('arr', '<');
      }
    });
  },
  welcome: function() {
    var _hash = window.location.hash.replace(/^#/, '');
    if (_hash == 'null' || _hash.length == 0 || arguments.length > 0) {
      var _id = 'container_welcome';
      var _container = $('#container');
      var _obj = $('#' + _id);

      if (_obj.length == 0) {
        _container.prepend('<div id="' + _id + '" class="container_child"></div>');

        _obj = $('#' + _id);

        $('#main_loading').fadeIn(function() {
          _obj.show().load(PageAction.pack + 'system/statistics', function() {
            $('#main_loading').fadeOut();
            _obj.siblings().hide();
            _id = _container = _obj = null;
            window.location.hash = '';
            $('#container').removeClass('ml');
          });
        });
      }
      else {
        _obj.show().siblings().hide();
        _id = _container = _obj = null;
        window.location.hash = '';
        $('#container').removeClass('ml');
      }
      $('#go_welcome').parent().addClass('slt').siblings().removeClass('slt');
    }
    else {
      Navigation.getPage(_hash);
    }
  },
  getPage: function(rel) {
    //alert(i + '-' + j);
    var _aryRel = rel.split('/'),
      _id = 'container_' + rel.replace('/', '_'),
      _container = $('#container'),
      _obj = $('#' + _id);

    if (!$('#container').hasClass('ml')) {
      $('#container').addClass('ml');
    }

    if (_obj.length == 0) {
      _container.prepend('<div id="' + _id + '" class="container_child"></div>');

      _obj = $('#' + _id);

      $('#main_loading').fadeIn(function() {
        _obj.show().load(PageAction.pack + rel + '?divId=' + _id, function() {
          $(this).siblings().hide();
          window.location.hash = rel;
          _id = _container = _obj = null;
        });
      });
    }
    else {
      _obj.show().siblings().hide();
      if (document.documentElement.clientHeight < document.documentElement.scrollHeight) {
        var _dgwObj = $('.data_grid_wrapper', _obj);
        var _dcw = $('.data_content_wrapper', _dgwObj);
        if (_dcw.length > 0) {
          Navigation.flushContentHeight(_dcw);
        }
        else {
          Navigation.getSearchData($('.data_grid', _dgwObj));
        }
        _dgwObj = _dcw = null;
      }
      _id = _container = _obj = null;
      window.location.hash = rel;
    }

    //$('#navigation > dl > dt:gt(0), #navigation > dl > dd > div > ul > li > a')
    //$('#navigation > dl > dd > div > ul > li').removeClass('slt');
    $('#menu_' + _aryRel[0]).find('dt').attr('rel', rel).parent().addClass('slt').siblings().removeClass('slt');
    var _menu = $('#sidebar-' + _aryRel[0]);
    _menu.show().siblings().hide();
    $('> ul > li > a[rel="' + rel + '"]', _menu).parent().addClass('slt').siblings().removeClass('slt');
  },
  getSearchData: function(t, form) {
    $('#main_loading').fadeIn(function() {
      $(t).dataGridOptions({
        params: $('form[name=search_from]', form).serializeArray()
      }).dataGridReload();
    });
  },
  flushContentHeight: function(t) {//刷新内容高度
    var _offset = t.offset();
    if (_$ie6)//ie6
      _offset.top -= 80;
    t.height(_$top.height() - _offset.top);
//		_topPosition = MyBox.getTopPosition(t[0]);
//		if (_ie6)//ie6
//			_topPosition -= 80;
//		t.height(MyBox.getPageSize()[1] - _topPosition - 12);
  },
  doDelete: function(t, action, bind) {
    Tools.doLockingUpdate(t, action, [
      '您确定删除这',
      '条记录吗？',
      'directRemoveLocking',
      '系统繁忙，删除指令未执行'
    ], bind);
  }
};

Navigation.toggleLeft();

var Tools = {
  version: '2.0.6',
  $: function(obj) {
    return document.getElementById(obj);
  },
  isUndefined: function(variable) {
    return typeof variable == 'undefined' ? true : false;
  },
  isArray: function(o) {
    return Object.prototype.toString.call(o) === '[object Array]';
  },
  getDiffNum: function(ary, r, showAry) {//获取一个值不相等的随机数组元素 ary 数组 r:取值范围
    var _temp = Math.floor(Math.random() * r);
    if (Tools.arraySearch(ary, _temp) == -1 && !Tools.isUndefined(showAry[_temp]))
      return _temp;
    return Tools.getDiffNum(ary);
  },
  isIE: function() {
    return -[1, ] ? false : true;
  },
  strlen: function(str) {
    return (Tools.isIE() && str.indexOf('\n') != -1) ? str.replace(/\r?\n/g, '_').length : str.length;
  },
  getExt: function(path) {
    return path.lastIndexOf('.') == -1 ? '' : path.substr(path.lastIndexOf('.') + 1, path.length).toLowerCase();
  },
  getParameter: function(param) {
    if (window.location.href.indexOf('?') != -1) {
      var _href = window.location.href.split('?');
      var reg = new RegExp('(^|&)' + param + '=([^&]*)(&|$)', 'i');
      var r = _href[1].match(reg);
      if (r != null)
        return decodeURIComponent(r[2]);
    }
    return null;
  },
  unique: function(data) {//高效清除数组中相同项
    data = data || [];
    var a = {}, v = null;
    for (var i in data) {
      v = data[i];
      if (typeof a[v] == 'undefined')
        a[v] = 1;
    }
    data.length = 0;
    for (var i in a) {
      data[data.length] = i;
    }
    return data;
  },
  createControl: function(createType, id, options, defaultText) {
    switch (createType) {
      case 'select':
        var control = '<select name="' + id + '" id="' + id + '">';
        if (!Tools.isUndefined(defaultText))
          control += '<option value="">' + defaultText + '</option>';
        if (options && typeof options == 'object') {
          for (var key in options) {
            control += '<option value="' + key + '">' + options[key] + '</option>';
          }
        }
        else if (Tools.isArray(options)) {
          for (var i = 0; i < options.length; i++) {
            control += '<option value="' + i + '">' + options[i] + '</option>';
          }
        }
        control += '</select>';
        document.write(control);
        break;
    }
  },
  checkBoxNeedOne: function(d) {
    var _b = false;
    $(d).each(function() {
      if ($(this).prop('checked'))
        _b = true;
    });
    return _b;
  },
  checkSlt: function(o, d) {
    var _o = o.split(',');
    $(d).each(function() {
      if (Tools.arraySearch(_o, $(this).val()) != -1)
        $(this).prop('checked', true);
    });
  },
  addEditPageLoad: function(url, width) {
    return $.dialog({
      id: 'addEditPageLoader',
      content: 'load:' + url + (url.indexOf('?') == -1 ? '?' : '&') + 'r=' + Math.random(),
      lock: true,
      lwidth: width || (_$top.width() * 0.8),
      height: 50,
      cancel: true,
      wrapForm: true
    });
  },
  addEditPageLoader: function() {
    return $.dialog.list['addEditPageLoader'];
  },
  upload: function(paramer) {
    var _urlParamer = '?uploadType=' + paramer.uploadType + '&uploadName=' + paramer.uploadName
      + '&mode=' + paramer.mode + '&imgWidth=' + paramer.imgWidth;
    $.dialog.locking.pager({
      title: '文件上传',
      lwidth: 400,
      content: 'load:' + PageAction.pack + 'system/upload' + _urlParamer,
      fixed: true,
      lock: true,
      resize: false,
      ok: function() {
        PageAction.UpLoadPageAction.init(paramer);
        return false;
      },
      cancel: true,
      focus: true
    });
  },
  delFile: function(uploadType, uploadName) {
    $.dialog.locking.confirm(null, '您确定删除上传文件吗？', function() {
      $.post(PageAction.handlerRoot + 'handler/Admin.System.DelFile', {
        "delFileName": $('#' + uploadName).val(),
        "uploadType": uploadType
      }, function(data) {
        if (data.status == 'ERROR') {
          $.dialog.locking.alert('error', '系统繁忙，请稍候再试');
        }
        else {
          if (uploadType == 'img') {
            $('#return_' + uploadName).html('<img src="' + PageAction.view + 'images/no_pic.jpg">');
          }
          else {
            $('#return_' + uploadName).html('');
          }
          $('#' + uploadName).val('');
          $('#' + uploadName + '-attachment').val('');
          $('#add_' + uploadName).show();
          $('#del_' + uploadName).hide();
          $.dialog.locking.remove();
        }
      }, 'json');
      return false;
    });
  },
  delMultipleImg: function(delImgSrc) {
    $.dialog.locking.confirm(null, '您确定这张图片吗？', function() {
      $.post(PageAction.handlerRoot + 'handler/Admin.System.DelMultipleFile', {
        "delImgSrc": delImgSrc
      }, function(data) {
        if (data.status == 'SUCCESS') {
          $('#multiple_img_' + delImgSrc.replace(/[,\.]/g, '')).empty().remove();
          $.dialog.locking.remove();
        }
        else {
          $.dialog.locking.alert('error', '系统繁忙，请稍候再试');
        }
      }, 'json');
      return false;
    });
  },
  checkNull: function(id, str) {
    var _temp = $('#' + id);
    if ($.trim(_temp.val()).length == 0) {
      $.dialog.alert(str, function() {
        _temp.focus();
      });
      return false;
    }
    return true;
  },
  checkDigit: function(id, str) {
    var _temp = $('#' + id);
    if (!/^[0-9]+$/.test($.trim(_temp.val()))) {
      $.dialog.alert(str, function() {
        _temp.focus();
      });
      return false;
    }
    return true;
  },
  compare: function(id, obj, str) {
    _temp = $('#' + id);
    if ($.trim(_temp.val()) == obj) {
      $.dialog.alert(str, function() {
        _temp.focus();
      });
      return false;
    }
    return true;
  },
  checkMoblie: function(id) {
    var _temp = $('#' + id),
      _mobile = /^1[0-9]{10}$/,
      _patternsMobile = /^(([0\+]\d{2,3}-)?(0\d{2,3}))(\d{7,8})(-(\d{3,}))?$/;
    if ($.trim(_temp.val()).length > 0) {
      switch ($.trim(_temp.val()).substring(0, 1)) {
        case '0' : // 小灵通
          if (!_patternsMobile.test(_temp.val())) {
            $.dialog.alert('您输入的小灵通号码不正确', function() {
              _temp.focus();
            });
            return false;
          }
          break;
        case '1' : // 手机
          if (_temp.val().length < 11 || !_mobile.test(_temp.val())) {
            $.dialog.alert('您输入的手机号码不正确', function() {
              _temp.focus();
            });
            return false;
          }
          break;
        default :
          $.dialog.alert('您输入的手机号码不正确', function() {
            _temp.focus();
          });
          return false;
          break;
      }
      return true;
    }
    return false;
  },
  doLockingWindowOpen: function(t, action, aryStr) {
    var _obj = $('.tr_selected', t.grid.dataGrid);
    var _total = _obj.length;
    if (_total > 0) {
      $.dialog.locking.confirm('prompt', aryStr[0] + ' ' + _total + ' ' + aryStr[1], function() {
        $.dialog.locking('系统已启动，请稍候。。。');
        var _id = [];
        for (var i = 0; i < _total; i++) {
          _id.push(_obj.eq(i).attr('id').substring(3));
        }
        var _action = null;
        var _postObj = {};
        if (typeof action == 'object') {
          _postObj = action;
          _action = _postObj.action;
        }
        else {
          _action = action;
        }
        _postObj.id = _id.join(',');
        window.open(_action + '&' + $.param(_postObj));
        return true;
      });
    }
    else {
      $.dialog.locking.alert(null, '请选择某行！');
    }
  },
  doLockingUpdate: function(t, action, aryStr, bind) {
    var _obj = $('.tr_selected', t);
    var _total = _obj.length;
    if (_total > 0) {
      $.dialog.locking.confirm('prompt', aryStr[0] + ' ' + _total + ' ' + aryStr[1], function() {
        $.dialog.locking('系统已启动，请稍候。。。');
        var _id = '';
        for (var i = 0; i < _total; i++) {
          if (i > 0)
            _id += ',';
          _id += _obj.eq(i).data('id');
        }
        var _postObj = {};
        if (typeof action == 'object') {
          _postObj = action;
        }
        else {
          _postObj.action = action;
        }
        _postObj.id = _id;
        $.post(_postObj.action, _postObj, function(data) {
          switch (data.status) {
            case 'SUCCESS' :
              if (bind != null)
                $(t).dataGridOptions(bind);
              $(t).dataGridReload();
              if (aryStr[2] == 'directRemoveLocking') {
                $.dialog.locking.remove();
              }
              else {
                $.dialog.locking.alert('success', data.desc.length > 0 ? data.desc : aryStr[2]);
              }
              break;
            case 'NEED' :
              $.dialog.locking.alert('error', '此项无法删除');
              break;
            case 'ERROR' :
              $.dialog.locking.alert('error', aryStr[3]);
              break;
            case 'NO_CHANGES' :
            case 'DB_DELETE_ERR' :
            default :
              $.dialog.locking.alert('error', data.desc);
              break;
          }
        }, 'json');
        return false;
      });
    }
    else {
      $.dialog.locking.alert(null, '请选择某行！');
    }
  },
  switchDiv: function(li, sltClass, switchObj) {
    $(li).each(function(i) {
      $(this).click(function() {
        $(this).addClass(sltClass).siblings().removeClass(sltClass);
        $(switchObj).eq(i).show().siblings(switchObj).hide();
      });
    });
  }
};
PageAction.UpLoadPageAction = function() {
  var _frameId = 'HiddenUploadFrame';
  var _formId = 'uploadForm';
  var _url = 'handler/Admin.System.Upload';
  return {
    fileAttribute: null,
    init: function(fileAttribute) {
      this.fileAttribute = fileAttribute;
      this.fileAttribute.getFileToUpload = $('#getFileToUpload');
      this.fileAttribute.speedy = $('#speedy');
      this._io = document.getElementById(_frameId);
      this.upload();
    },
    uploadCallback: function(_this) {
      var _responseText = null;
      try {
        if (_this._io.contentWindow) {
          _responseText = _this._io.contentWindow.document.body ? _this._io.contentWindow.document.body.innerHTML : '';
        } else if (_this._io.contentDocument) {
          _responseText = _this._io.contentDocument.document.body ? _this._io.contentDocument.document.body.innerHTML : '';
        }
        if (_responseText != null) {
          _responseText = _responseText.replace(/<.+?>/gim, '');
          var _data = $.parseJSON(_responseText);
          if (_data.status == 'ERROR') {
            $.dialog.alert('<span class="red">' + _data.desc + '</span>');
            this.fileAttribute.speedy.hide();
            _this.fileAttribute.getFileToUpload.show();
          } else {
            _this.success(_data, _this);
            $.dialog.locking.remove();
          }
        }
      } catch (e) {
        $.dialog.alert('<span class="red">' + e + '</span>');
        _this.fileAttribute.speedy.hide();
        _this.fileAttribute.getFileToUpload.show();
      }
    },
    upload: function() {
      if (this.fileAttribute.getFileToUpload.val().length == 0) {
        $.dialog.alert('<span class="red">请选择上传文件</span>');
        this.fileAttribute.getFileToUpload.focus();
        return false;
      }
      this.fileAttribute.getFileToUpload.hide();
      this.fileAttribute.speedy.show();
      try {
        var _form = $('#' + _formId);
        $(_form).attr('action', PageAction.root + _url);
        $(_form).submit();
      } catch (e) {
        $.dialog.alert('<span class="red">' + e + '</span>');
        this.fileAttribute.speedy.hide();
        this.fileAttribute.getFileToUpload.show();
      }
      var _this = this;
      if (window.attachEvent) {
        _this._io.attachEvent('onload', function() {
          _this.uploadCallback(_this);
        });
      } else {
        _this._io.addEventListener('load', function() {
          _this.uploadCallback(_this);
        }, false);
      }
    },
    success: function(data, _this) {
    }
  }
}();
String.prototype.trim = function() {
  return this.replace(/(^\s*)|(\s*$)/g, '');
}
/*** 统计指定字符出现的次数 ***/
String.prototype.occurs = function(ch) {
  // var re = eval('/[^' + ch + ']/g');
  // return this.replace(re, "").length;
  return this.split(ch).length - 1;
}
/*** 检查是否由纯数字组成 ***/
String.prototype.isDigit = function() {

  var s = this.trim();
  return (s.replace(/\d/g, "").length == 0);
}
/*** 检查是否由数字字母和下划线组成 ***/
String.prototype.isAlpha = function() {
  return (this.replace(/[\w]/g, "").length == 0);
}
/*** 检查是否为数字 ***/
String.prototype.isNumber = function() {
  var s = this.trim();
  return (s.search(/^[+-]?[0-9.]*$/) >= 0);
}
/*** 返回字节数 ***/
String.prototype.lenb = function() {
  return this.replace(/[^\x00-\xff]/g, "**").length;
}
/*** 检查是否包含汉字 ***/
String.prototype.isInChinese = function() {
  return (this.length != this.replace(/[^\x00-\xff]/g, "**").length);
}
/*** 简单的email检查 ***/
String.prototype.isEmail = function() {
  var _regeMail = /^[\w\.-]{1,}\@([\da-zA-Z-]{1,}\.){1,}[\da-zA-Z-]+$/;
  return _regeMail.test(this);
}
/*** 简单的日期检查，成功返回日期对象 ***/
String.prototype.isDate = function() {
  var p;
  var re1 = /(\d{4})[年./-](\d{1,2})[月./-](\d{1,2})[日]?$/;
  var re2 = /(\d{1,2})[月./-](\d{1,2})[日./-](\d{2})[年]?$/;
  var re3 = /(\d{1,2})[月./-](\d{1,2})[日./-](\d{4})[年]?$/;
  if (re1.test(this)) {
    p = re1.exec(this);
    return new Date(p[1], p[2], p[3]);
  }
  if (re2.test(this)) {
    p = re2.exec(this);
    return new Date(p[3], p[1], p[2]);
  }
  if (re3.test(this)) {
    p = re3.exec(this);
    return new Date(p[3], p[1], p[2]);
  }
  return false;
}
/*** 检查是否有列表中的字符字符 ***/
String.prototype.isInList = function(list) {
  var re = eval('/[' + list + ']/');
  return re.test(this);
}
/*
 前台检测是否有非安全字符 注:提交到数据库后同样要过滤非安全字符
 */
String.prototype.isNotSafe = function() {
  var re = new RegExp(/[';&]/);
  return re.test(this);
}
//-->