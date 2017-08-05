var commonFun = {
	scrollToSthAddClass:function(tar){
		var target = $(tar.target),
			newClass= tar.newclass,
			offTop = target.offset().top;

		var winH = $(window).height();

		var scroTop = $('body').scrollTop();
		if((scroTop + winH) >= offTop){
			if(!target.hasClass('cur')){
				target.addClass(newClass);
			}
			else{
				return false;
			}
		}
		$(window).scroll(function () {
			var scroTop1 = $('body').scrollTop();
			if((scroTop1 + winH) >= offTop){
				if(!target.hasClass('cur')){
					target.addClass(newClass);
				}
				else{
					return false;
				}
			}
		});
	},
	//tab slide style
	tab1:function(tar){
		var target = $(tar.container),
			trigger = target.find(tar.trigger),
			cont = target.find(tar.cont),
			time = tar.time,
			statue = true;

		$.each(trigger,function(index){
			$(this).click(function(){
				var _this = trigger.eq(index);
				if(statue == true){
					statue = false;
					setTimeout(function(){
						statue = true;
					},time * 2);

					if(!_this.hasClass('cur')){
						cont.removeClass('cur');
						trigger.removeClass('cur').eq(index).addClass('cur');
						cont.bind('transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd',function(){
							cont.eq(index).addClass('cur');
							cont.unbind('transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd');
						})
					}
				}

			});
		});
	},
	checkMoblie: function(id) {
		var _temp = $('#' + id),
			_mobile = /^1[0-9]{10}$/,
			_patternsMobile = /^(([0\+]\d{2,3}-)?(0\d{2,3}))(\d{7,8})(-(\d{3,}))?$/;
		if ($.trim(_temp.val()).length > 0) {
			switch ($.trim(_temp.val()).substring(0, 1)) {
				case '0' : // 小灵通
					if (!_patternsMobile.test(_temp.val())) {
						alert('您输入的小灵通号码不正确');
						_temp.focus();
						return false;
					}
					break;
				case '1' : // 手机
					if (_temp.val().length < 11 || !_mobile.test(_temp.val())) {
						alert('您输入的手机号码不正确');
						_temp.focus();
						return false;
					}
					break;
				default :
					alert('您输入的手机号码不正确');
					_temp.focus();
					return false;
					break;
			}
			return true;
		}
		return false;
	},
	isMobile:function(){
		var winW = $(window).width();
		if(winW <= 780){
			return true;
		}
		else{
			return false;
		}
	}
};

var indexFun = {
	doFun:function(){
		var winW = $(window).width();
		if(winW>=1200){
			indexFun.bannerSth();
		}
		else {
			indexFun.mobileSth();
		}

	},
	bannerSth:function(){
		var count = 0;

		var timer = null;

		var liW = $('.smallimg-list').find('.bg-item').width();

		function smallRun(x){
			  $('.trigger').animate({left:x*liW+'px'},250);
			  $('.bg-item').find('.txt').removeClass('small-on');
			  $('.bg-item').find('.txt').eq(x).addClass('small-on');
			  $('.bigimg-list').find('.ui-item').eq(x).removeClass('on').siblings().addClass('on');
		}

		var autoPlay = function(){
			count++;
			if(count == $('.bigimg-list li').length){
				count=0;
			}
			smallRun(count);
		};

		clearInterval(timer);
		timer = setInterval(autoPlay,5000);

         $('.smallimg-list').find('.bg-item').mouseover(function(e){
			 clearInterval(timer);
			 e.stopPropagation();
			 smallRun($(this).index());
		 });

		$('.smallimg-list').find('.bg-item').mouseout(function(e){
			e.stopPropagation();
			count=$(this).index();
			timer=setInterval(autoPlay,5000);
		});

		$('.trigger').mouseover(function(){
			clearInterval(timer);
		});

		$('.trigger').mouseout(function(){
			timer=setInterval(autoPlay,5000);
		});
	},
	mobileSth: function () {
		var mySwiper = new Swiper('.swiper-container',{
			pagination: '.pagination',
			loop:true,
			grabCursor: true,
			autoplay:7000,
			paginationClickable: true
		});
		$('#banner .prev').on('click', function(e){
			e.preventDefault();
			mySwiper.swipePrev();
		});
		$('#banner .next').on('click', function(e){
			e.preventDefault();
			mySwiper.swipeNext();
		});
	}
};

var header = {
	doFun:function(){
		this.headHide();
		this.menuToggle();
		this.menuFold();
		this.mobileMenu();
	},
	headHide:function () {
		var head = $('.header');
		$(window).scroll(function () {
			var scrollT = $(window).scrollTop();
			if(scrollT>250){
				head.addClass('cur');
			}
			else {
				head.removeClass('cur');
			}
		})
	},
	menuToggle:function(){
		var btn = $('.menu-btn'),
			menu = $('.header').find('.nav-title');

		btn.click(function(){
			if(!btn.hasClass('cur')){
				btn.addClass('cur');
				menu.addClass('cur');
			}
			else{
				btn.removeClass('cur');
				menu.removeClass('cur');
			}
		})
	},
	mobileMenu:function () {
		var moLi = $('.nav-menu').find('.ui-nav-item');

		var statue = true;

		moLi.click(function () {
			var _this = $(this);
			if(statue == true){
				statue = false;
				setTimeout(function () {
					statue = true;
				},300);

				if(_this.find('.sub-menu').css('display')=='none'){
					_this.addClass('curr').siblings().removeClass('curr');
					_this.find('.sub-menu').slideDown(300).parents().siblings().find('.sub-menu').slideUp(300);
					/*_this.siblings().find('.J-sub-menu').slideUp('300');*/
				}
				else {
					_this.find('.sub-menu').slideUp(300);
					_this.removeClass('curr');
				}
			}
		})
	},
	menuFold:function(){
		if(commonFun.isMobile()){
			$('.ui-bg').click(function (e) {
				var btn = $('.menu-btn'),
					menu = $('.header').find('.nav-title');

				if(btn.hasClass('cur')){
					btn.removeClass('cur');
					menu.removeClass('cur');
				}
			});
		}
	}
};

var product = {
	doFun:function () {
		var winW = $(window).width();
		if (winW>510){
			product.scrollMth();
		}
		this.tabSth();
		this.tabTth();
	},
	scrollMth:function () {
		$(function () {
			$('#dowebok').fullpage({
				loopBottom: true,
				anchors: ['page1', 'page2', 'page3', 'page4','page5','page6'],
				menu: '#menu',
				afterRender: function (anchorLink, index) {
					//add active
					$('.section1').addClass('active1');

					//set footer height
					var winH = $(window).height(),
						winW = $(window).width();
					var footH = 120;
					$('.section6').css({ zIndex: '1' });
					if (winW > 510) {
						footH = 120;
					}
					else {
						footH = 62;
					}
					$('.foot').css({
						top: footH - winH + 'px',
						paddingTop: winH - footH + 'px'
					});
					$('.foot .fp-tableCell').height(footH);
				},
				afterLoad: function (anchorLink, index) {
					if (index == 1) {
						$('.section1').addClass('active1');

					} else {
						$('.section1').removeClass('active1');

					}
				},

				onLeave: function (index, nextIndex, direction) {
					var section = $('.section');
					if (index == 5) {
						if (direction == 'down') {
							section.eq(section.length - 2).addClass('active');
							section.eq(section.length - 1).removeClass('active');
						}
					}
					if (index == 1) {
						//$('.section1').addClass('active1');
					} else {
						//$('.section1').removeClass('active1');
					}
				}
			});

			$('.scroll-btn').click(function(){
				$.fn.fullpage.moveSectionDown();
			});
		});
	},
	tabSth:function () {
		var trigger = $('.ui-trigger').find('.trigger-item');
		$.each(trigger,function (index) {
			$(this).click(function () {
				$(this).addClass('cur').siblings().removeClass('cur');
				$('.tab-main').find('.main-img').eq(index).show().siblings().hide();
			})
		})
	},
	tabTth:function () {
		var trigger = $('.tab-title').find('.item-title');
		$.each(trigger,function (index) {
			$(this).click(function () {
				$(this).addClass('cur').siblings().removeClass('cur');
				$('.tab-main').find('.tab-img').eq(index).show().siblings().hide();
			})
		})
	}
};
var phecda = {
	doFun:function () {
		var winW = $(window).width();
		if (winW>510){
			phecda.scrollMth();
		}
	},
	scrollMth:function () {
		$(function () {
			$('#phecda').fullpage({
				loopBottom: true,
				anchors: ['page1', 'page2', 'page3', 'page4','page5','page6','page7','page8','page9'],
				menu: '#menu',
				afterRender: function (anchorLink, index) {
					//add active
					$('.section1').addClass('active1');

					//set footer height
					var winH = $(window).height(),
						winW = $(window).width();
					var footH = 120;
					$('.section6').css({ zIndex: '1' });
					if (winW > 510) {
						footH = 120;
					}
					else {
						footH = 62;
					}
					$('.foot').css({
						top: footH - winH + 'px',
						paddingTop: winH - footH + 'px'
					});
					$('.foot .fp-tableCell').height(footH);
				},
				afterLoad: function (anchorLink, index) {
					if (index == 1) {
						$('.section1').addClass('active1');

					} else {
						$('.section1').removeClass('active1');

					}
				},

				onLeave: function (index, nextIndex, direction) {
					var section = $('.section');
					if (index == 8) {
						if (direction == 'down') {
							section.eq(section.length - 2).addClass('active');
							section.eq(section.length - 1).removeClass('active');
						}
					}
					if (index == 1) {
						//$('.section1').addClass('active1');
					} else {
						//$('.section1').removeClass('active1');
					}
				}
			});

			$('.scroll-btn').click(function(){
				$.fn.fullpage.moveSectionDown();
			});
		});
	}
};
var dubhe = {
	doFun:function () {
		var winW = $(window).width();
		if (winW>510){
			dubhe.scrollMth();
		}
	},
	scrollMth:function () {
		$(function () {
			$('#dubhe').fullpage({
				loopBottom: true,
				anchors: ['page1', 'page2', 'page3', 'page4'],
				menu: '#menu',
				afterRender: function (anchorLink, index) {
					//add active
					$('.section1').addClass('active1');

					//set footer height
					var winH = $(window).height(),
						winW = $(window).width();
					var footH = 120;
					$('.section6').css({ zIndex: '1' });
					if (winW > 510) {
						footH = 120;
					}
					else {
						footH = 62;
					}
					$('.foot').css({
						top: footH - winH + 'px',
						paddingTop: winH - footH + 'px'
					});
					$('.foot .fp-tableCell').height(footH);
				},
				afterLoad: function (anchorLink, index) {
					if (index == 1) {
						$('.section1').addClass('active1');

					} else {
						$('.section1').removeClass('active1');

					}
				},

				onLeave: function (index, nextIndex, direction) {
					var section = $('.section');
					if (index == 3) {
						if (direction == 'down') {
							section.eq(section.length - 2).addClass('active');
							section.eq(section.length - 1).removeClass('active');
						}
					}
					if (index == 1) {
						//$('.section1').addClass('active1');
					} else {
						//$('.section1').removeClass('active1');
					}
				}
			});

			$('.scroll-btn').click(function(){
				$.fn.fullpage.moveSectionDown();
			});
		});
	}
};
var xlong = {
	doFun:function () {
		var winW = $(window).width();
		if (winW>510){
			xlong.scrollMth();
		}
	},
	scrollMth:function () {
		$(function () {
			$('#xlong').fullpage({
				loopBottom: true,
				anchors: ['page1', 'page2', 'page3', 'page4'],
				menu: '#menu',
				afterRender: function (anchorLink, index) {
					//add active
					$('.section1').addClass('active1');

					//set footer height
					var winH = $(window).height(),
						winW = $(window).width();
					var footH = 120;
					$('.section6').css({ zIndex: '1' });
					if (winW > 510) {
						footH = 120;
					}
					else {
						footH = 62;
					}
					$('.foot').css({
						top: footH - winH + 'px',
						paddingTop: winH - footH + 'px'
					});
					$('.foot .fp-tableCell').height(footH);
				},
				afterLoad: function (anchorLink, index) {
					if (index == 1) {
						$('.section1').addClass('active1');

					} else {
						$('.section1').removeClass('active1');

					}
				},

				onLeave: function (index, nextIndex, direction) {
					var section = $('.section');
					if (index == 3) {
						if (direction == 'down') {
							section.eq(section.length - 2).addClass('active');
							section.eq(section.length - 1).removeClass('active');
						}
					}
					if (index == 1) {
						//$('.section1').addClass('active1');
					} else {
						//$('.section1').removeClass('active1');
					}
				}
			});

			$('.scroll-btn').click(function(){
				$.fn.fullpage.moveSectionDown();
			});
		});
	}
};
//超出文字部分省略
var obj = {
	txtLenth: function () {
		this.maxWidth();
	},
	maxWidth: function () {
		$('.news-main').each(function(){
			var txtWidth = 50;
			if($(this).text().length > txtWidth){
				$(this).text($(this).text().substring(0,txtWidth));
				$(this).html($(this).html() + '...');
			}
		})
	}
};
var career = {
	doFun:function () {
		this.tabSth();
		var winW = $(window).width();
		if (winW>1100){
			career.ulSth();
		}
	},
	ulSth:function () {
		var ulTem = $('.cont-ui'),
			liTem = ulTem.find('.item-cont').width(),
			liL = ulTem.find('.item-cont').length,
			liM = parseInt(ulTem.find('.item-cont').eq(0).css('marginRight'));

		ulTem.css({width:liTem*liL+liL*liM});
		
		var btn = $('.career-btn');
		if(liL > 6){
			btn.find('.btn-icon').click(function () {
				ulTem.css({left:-$('.cont-ui').find('.item-cont').outerWidth(true)*6*$(this).index()+'px'});
			})
		}
		else {
			btn.css({opacity:'0'});
		}
	},
	tabSth:function () {
		var tabTrigger = $('.cont-ui').find('.item-cont'),
			tabCont = $('.career-desc').find('.desc-demand');
		$.each(tabTrigger,function (index) {
			var _this = $(this);
			_this.mouseover(function () {
				_this.addClass('cur').siblings().removeClass('cur');
				tabCont.eq(index).show().siblings().hide();
			})
		})
	}
};



