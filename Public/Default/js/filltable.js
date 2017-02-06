function Filltable(s,opt)
{
	var self=this;var tb=null;var tab=null;var head=[];var rows=[];var temp={};var cur=null;var request=null;
	var sub=opt?(opt['submit']?opt['submit']:'table'):'table';var data=opt?opt['data']:'';
	var mdiv=null;var mlist=null;var input=opt?(opt['input']):'';
	var dataw=opt?(opt['datawidth']?opt['datawidth']:'800'):'800';dataw=dataw.indexOf('px')>-1?dataw:dataw+'px';
	var datah=opt?(opt['dataheight']?opt['dataheight']:'150'):'150';datah=datah.indexOf('px')>-1?datah:datah+'px';
	var search=opt?(opt['search']?opt['search']:opt['input']):'';var sel=0;var datatype=opt?opt['datatype']:'post';
	var datafilter=opt?opt['datafilter']:'';

	var _init=function(){
		$(s).find('input').each(function(i){
			$(this).keyup(function(e){_keygo.apply(this,[e]);});
		});
	};

	var _getcurrent=function(){var c=0;tb.find('input').each(function(i){if($(this).is(cur)){c=i;return false;}});return c;};
	
	var _getnext=function()
	{
		var m=_getcurrent();
		for(var i=0;i<head.length;i++){
			if(i<=m||head[i]['nofocus']||tb.find('input').eq(m+1).attr('disabled')||tb.find('input').eq(m+1).is(':hidden')){continue;}
			m=i;break;
		}
		return tb.find('input').eq(m);
	};
	var _gethead=function(){
		tb=$(s);
		tb.find('input').each(function(i){
			var f=$(this).attr('field');var c=$(this).attr('nofocus');
			$(this).focus(function(){cur=$(this);});
			head.push({field:f,nofocus:c});
		});
	};

	var _getinfo=function()
	{
		var arr=head;
		mdiv.find('tr[current]').find('td').each(function(){
			var k=$(this).attr('key');var v=$(this).text();
			for(var i=0;i<arr.length;i++){if(head[i]['field']==k){tb.find('input[field="'+k+'"]').val(v);}}
		});
		return mdiv.find('tr[current]').size()?true:false;
	};
	var _hidedata=function(){$('body').append(mdiv);mlist.find('tr').show();mdiv.hide();};
	var _keygo=function(e)
	{
		if(e.keyCode==13){
			var cu=_getcurrent();
			if(head[cu]['field']==input&&mdiv.is(':visible')){
				if(!_getinfo()){return false;}_hidedata();
			}
			var n=_getnext();if(!n){return false;}
			n.focus();
		}else if(e.keyCode==38)
		{
			if(mdiv.is(':hidden')){return false;}
			sel--;if(sel==-1){sel=0;return false;}
			_move(sel);
		}else if(e.keyCode==40)
		{
			if(mdiv.is(':hidden')){return false;}
			sel++;if(sel==mlist.find('tr:visible').size()){
				sel=mlist.find('tr:visible').size()-1;return false;
			}
			_move(sel);
		}else if(e.keyCode==27)
		{
			if(mdiv.is(':visible')){_hidedata();}
		}else
		{
			var cu=_getcurrent();
			if(head[cu]['field']!=input){return false;}
			var p=$(this).parent();var val=$(this).val();if(!val){request.abort();_hidedata();return false;}
			p.append(mdiv);
			mdiv.css({'left':$(this).position().left,'top':$(this).position().top+$(this).height()});
			if(data&&_isurl(data))
			{
				_getdata(data,search,val);
			}else
			{
				_search(search,val);
			}
		}
	};
	var _createdata=function()
	{
		mdiv=$('<div style="width:'+dataw+';max-height:'+datah+';overflow-y:scroll;display:none;"/>');
		mlist=$('<table border="0" cellspacing="0" cellpadding="0" style="border-collapse:collapse;" width="100%"></table>').appendTo(mdiv);
		mdiv.css({'position':'absolute','zIndex':100,'border':'1px solid #000','background':'#fff'});
		for(var i=0;i<data.length;i++)
		{
			var tr=$('<tr style="border:1px solid #000;background-color:#ffc;height:25px;" ></tr>').appendTo(mlist);
			tr.click(function(){
				var sel=$(this).parent().find('tr:visible').index($(this));
				_move(sel);
				var cu=_getcurrent();
				tb.find('input').eq(cu).focus();
			});
			tr.dblclick(function(){_getinfo();_hidedata();});
			for(var j in data[i]){tr.append('<td key="'+j+'">'+data[i][j]+'</td>');}
		}
	};
	var _coverbefore=function(){
		mlist.empty();
		mdiv.show();
		$('<tr style="background-color:#ffc;" ><td>加载中...</td></tr>').appendTo(mlist);
	}
	var _coverdata=function(data)
	{
		mlist.empty();
		for(var i=0;i<data.length;i++)
		{
			var tr=$('<tr style="border:1px solid #000;background-color:#ffc;height:25px;" ></tr>').appendTo(mlist);
			tr.click(function(){
				var sel=$(this).parent().find('tr:visible').index($(this));
				_move(sel);
				var cu=_getcurrent();
				tb.find('input').eq(cu).focus();
			});
			tr.dblclick(function(){_getinfo();_hidedata();});
			for(var j in data[i]){tr.append('<td key="'+j+'">'+data[i][j]+'</td>');}
		}
	};
	var _search=function(k,v)
	{
		var reg=new RegExp('^'+v+'.*?','i');var arr=[];
		mlist.find("td[key='"+k+"']").each(function(){
			var i=$(this).parent().index();
			if(reg.test($(this).text())){arr.push(i);}
		});
		mdiv.show();
		// mlist.find('tr').hide();
		// for(var i=0;i<arr.length;i++){mlist.find('tr').eq(arr[i]).show();}
		// 	sel=0;
		// _move(sel);
		if (arr.length>0) {
			mlist.find('tr').hide();
			for(var i=0;i<arr.length;i++){mlist.find('tr').eq(arr[i]).show();}
			sel=0;
			_move(sel);
		}else{
			$('<tr style="background-color:#ffc;" ><td>无数据...</td></tr>').appendTo(mlist);
		}
	};
	var _move=function(n)
	{
		mlist.find('tr:hidden').removeAttr('current');
		mlist.find('tr:visible').each(function(i){
			if(i==n){
				mdiv.scrollTop($(this).position().top);
				$(this).attr('current','true');
				$(this).css({'background':'#ccc'});
			}else{
				$(this).removeAttr('current');
				$(this).css({'background':'#ffc'});
			}
		});
	};
	var _getdata=function(u,s,v)
	{
		if(!u){return false;}var dd={'field':s,'value':v,filter:datafilter};
		if (request!=null) {request.abort();}
		request=$.ajax({type:datatype,url:u,data:dd,beforeSend:function(x){_coverbefore();},success:function(d){
			var rd=d=$.isArray(d)?d:($.isPlainObject(d)?d:(/^\{.+\}$/.test(d)?eval('('+d+')'):(/^\[.+\]$/.test(d)?eval('('+d+')'):d)));
			_coverdata(rd);
			_search.apply(this,[s,v]);
		}});
	};
	var _isurl=function(s){return /http(s)?:\/\//.test(s)?true:/\/[\w]+(\/[\w]+)+/.test(s);};
	_gethead();
	_createdata();
	_init();
	// $('input').keyup(function(e){_keygo.apply(this,[e]);});
	$(document).click(function(e){var c=$(e.target);if(c.parents('div').is(mdiv)){return false;}_hidedata();});
	return this;
}