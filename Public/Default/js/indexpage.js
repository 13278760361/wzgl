/**
 * 
 * @authors Liu jin bi (hiworld@sina.cn)
 * @date    2016-08-17 10:12:12
 * @version $Id$
 */

jQuery(document).ready(function ($) {
    window.onhashchange=function(){
        init_menu();
    };
    init_menu();
    $('body').delegate('.loadmaincontent','click',function(){
		window.location.hash = $(this).attr('url');
		return false;
    })
});
function init_menu()
{
	var ahref = window.location.hash.split('#');
	if (ahref.length >= 2) {
		if (/Public\/login.*?/i.test(ahref[1])) {
			window.location.reload();
		}

		var cura = '';
		var url = ahref[1].split('::');
		if (url.length >= 2 ) {
			var macs = url[0].replace(/(\/[\w]+$)+?/,'');
			cura = $('.m-menu[url*="'+macs+'"]');
		}else{
			cura = $('.m-menu[url="'+ahref[1]+'"]');
		}

		var is_topnav = cura.closest('.menu-list');
		if (is_topnav.size() > 0) {
			menuAddActive(cura.closest('.m-menu').index());
		}else{
			// 判断当前下面是否有孩子节点 有则选中第一个
			if ( cura.parent().find('.mian-menu-item').length > 0 ) {
				var url = cura.parent().find('.mian-menu-item').children(":first").find('a').attr('url');
				window.location.hash = url;
				return false;
			}
			cura.parent().addClass('active').siblings().removeClass('active');
			cura.closest('.navi-ul>li').addClass('active').siblings().removeClass('active');
	        var top_navi = cura.closest('.navi');
	        top_navi.addClass('active').siblings().removeClass('active');
	        $('.top-nav > li').eq(top_navi.index()).addClass('active').siblings().removeClass('active');
			$('#main-iframe').attr('src',ahref[1]);
		}
	}else{
        menuAddActive(0);
	}
}
function direct(url){
	window.location.hash = url;
}
$('.top-nav > li').click(function(){
    menuAddActive($(this).index());
    return false;
})
function menuAddActive(ind){
    $('.navi-wrap .navi').eq(ind).addClass('active').siblings('.navi').removeClass('active')
    var fli = $('.navi.active>.navi-ul li:first')
    fli.addClass('active');
    if ($('.navi.active>.navi-ul li:first ul.nav-sub').length > 0) {
        fli.find('ul.nav-sub li:first').addClass('active');
        window.location.hash = fli.find('ul.nav-sub li:first a').attr('url');
    }else{
        window.location.hash = fli.find('a').attr('url');
    }
}
$('.navbar-nav .open-nav').click(function() {
	$('.app-page').toggleClass('app-aside-folded')
	return false;
})

$('.dropdown-menu-li').click(function(){
	$(this).toggleClass('active');
})

$('.navi ul li.liquicks').click(function(){
	$(this).addClass('active').siblings('li').removeClass('active');
	var url = $(this).find('a').attr('action');
	$('#main-iframe').attr('src',url);
})

$('.navi ul li.linav a').click(function(){
	var o = $(this).parent('li');
	if (o.find('.nav-sub').length > 0 ) {
		o.siblings().removeClass('active');
		o.toggleClass('active');
		return false;
	}

	o.siblings().removeClass('active');
	o.addClass('active');
	

	var url = $(this).attr('url');
	url=url?url:$(this).attr('action');
	if (url) {
		var curr = window.location.hash.split('#');
		if (curr[1] == url) {
			window.location.reload();
		}else{
			window.location.hash = url;
		}
	}
	return false;
})

var fli = 0;
var sli = -1;
$('body').delegate('.app-aside-folded .navi ul li','hover',function(event){
	var type = event.type, that = this;
    if(type == "mouseenter"){ //移入  
  		var cc = $(that).find('.nav-sub')
  		fli = $(that).index();
  		if (fli != sli) {
  			$(that).closest('.app-aside').children('ul').remove();
  			if (cc.size() > 0) {
  				c = cc.clone();
	  			var top = $(that).offset().top;
	  			var height = $(that).height();
	  			var cheight = $(c).actual('outerHeight');
	  			var bottom = $(window).height()-top-height;
	  			if (bottom > cheight) {
		  			c[0].style.top = top+'px';	
	  			}else{
		  			c[0].style.bottom = bottom+'px';
	  			}
	  			c.addClass('opshow');
		  		c.appendTo('.app-aside');
	  		}
  		}
    }else{//mouseleave 移出
  		var c = $(that).closest('.app-aside').children('ul');
  		var sli = $(that).index(),sin=false;
  		if (c.size()>0) {
	  		$(c).hover(function(){sin=true},function(){
	  			c.remove();
	  		})
	  	}
	  	setTimeout(function(){if (!sin)c.remove();},100)
    } 
})


$('.quicks_search input').bind('input propertychange', function(e) {searchNavbyName(this);}); 
function searchNavbyName(e){
	var name = $(e).val();
	$('.quicks_navs li').each(function(){
		if (name) {
			var tag = $(this).find('a').text()
			if (tag.toLowerCase().indexOf(name.toLowerCase()) < 0) {$(this).hide();}else{$(this).show()}
		}else{
			$(this).show()
		}
	})
}
