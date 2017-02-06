function Table(s,opt)
{
	var self=this;var tb=null;var tab=null;var head=[];var rows=[];var temp={};var cur=null;var request=null;
	var sub=opt?(opt['submit']?opt['submit']:'table'):'table';var data=opt?opt['data']:'';
	var mdiv=null;var mlist=null;var input=opt?(opt['input']):'';
	var dataw=opt?(opt['datawidth']?opt['datawidth']:'800'):'800';dataw=dataw.indexOf('px')>-1?dataw:dataw+'px';
	var datah=opt?(opt['dataheight']?opt['dataheight']:'150'):'150';datah=datah.indexOf('px')>-1?datah:datah+'px';
	var search=opt?(opt['search']?opt['search']:opt['input']):'';var sel=0;var datatype=opt?opt['datatype']:'post';
	var datafilter=opt?opt['datafilter']:'';
	this.settitle=function(o){
		head=[];for(var i in o){
			head.push({field:o[i]['field'],text:o[i]['text'],nofocus:o[i]['nofocus'],break:o[i]['break'],noinput:o[i]['noinput']});
		}
		return this;
	};
	this.settemp=function(o){temp={};for(var i in o){temp[i]=$(o[i]);}return this;};
	this.addrow=function(){
		num=parseInt(rows.length,10)+1;
		var tr=$('<tr></tr>').appendTo(tab);var arr=[];
		for(var i=0;i<head.length;i++)
		{
			var h=head[i]['field'];var p=head[i]['noinput'];
			var str=temp[h]?temp[h].clone(true):(p?'<td/>':'<td><input /></td>');
			var o=$(str);o.css({'position':'relative'});
			arr.push(o.appendTo(tr));var put=o.find(':input');
			put.attr(sub+'-'+num,head[i]['field']);
			put.focus(function(){cur=$(this).parent();});
			put.keyup(function(e){_keygo.apply(this,[e]);});
		}
		rows.push(arr);
		var index=_getfirst();
		arr[index].find('input').focus();
		return this;
	};
	//最后一行行号
	this.lastnum=function(){return rows.length;}
	this.lastrow=function(){return rows[rows.length-1];}
	this.delrow=function(n){
		var o=$.isPlainObject(n)?n:(parseInt(n,10)?rows[n][0].parent():$(n));
		_hidedata();
		if(tb.find('tr').size()==2){return false;}
		o.remove();
	};
	var _getcurrent=function(){for(var i=0;i<rows.length;i++){for(var j=0;j<rows[i].length;j++){if(rows[i][j].is(cur)){return {x:i,y:j};}}}};
	var _getfirst=function(){for(var i=0;i<head.length;i++){if(head[i]['nofocus']||!temp[head[i]['field']].find('input').size()){continue;}return i;}};
	var _getnext=function()
	{
		var m=_getcurrent();var x=m.x;var y=m.y;
		if(head[y]['break']){
			self.addrow();m=_getcurrent();x=m.x;y=m.y;
		}else{
			if(y==head.length-1){
				x++;y=_getfirst();
			}else{
				for(var i=0;i<head.length;i++){
					if(i<=y||head[i]['noinput']||head[i]['nofocus']||!temp[head[i]['field']].find('input').size()||rows[x][i].find('input').attr('disabled')||rows[x][i].is(':hidden')){continue;}
					y=i;break;
				}
			}
			if(x>rows.length-1){self.addrow();m=_getcurrent();x=m.x;y=m.y;}
		}
		return rows[x][y];
	};
	var _gethead=function(){
		tb=$(s);tab=tb.find('tbody');var tr=tb.find('tr');if(!tr.size()){return false;}
		var tds=tr.first().find('th');tds=tds.size()?tds:tr.first().find('td');
		tds.each(function(i){
			var f=$(this).attr('field');var c=$(this).attr('nofocus');
			var n=$(this).attr('break');var p=$(this).attr('noinput');f=f?f:'f'+i;
			head.push({field:f,text:$(this).text(),nofocus:c,break:n,noinput:p});
		});
	};
	var _gettemp=function()
	{
		var tr=tb.find('tr');if(tr.size()<2){return false;}tr=tr.eq(1);
		var tds=tr.find('td');if(!tds.size()){return false;}
		tds.each(function(i){temp[head[i]['field']]=$(this);});
		tr.remove();
	};
	var _getinfo=function(n)
	{
		var arr=rows[n];
		mdiv.find('tr[current]').find('td').each(function(){
			var k=$(this).attr('key');var v=$(this).text();
			for(var i=0;i<arr.length;i++){if(head[i]['field']==k){arr[i].find('input').val(v);}}
		});
		return mdiv.find('tr[current]').size()?true:false;
	};
	var _hidedata=function(){
		// $('body').append(mdiv);
		mlist.find('tr').show();mdiv.hide();};
	var _keygo=function(e)
	{
		if(e.keyCode==13){
			var cu=_getcurrent();
			if(head[cu.y]['field']==input&&mdiv.is(':visible')){
				if(!_getinfo(cu.x)){return false;}_hidedata();
			}
			var n=_getnext();if(!n){return false;}
			n.find('input').focus();
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
			var cu=_getcurrent();if(head[cu.y]['field']!=input){return false;}
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
				rows[cu.x][cu.y].find('input').focus();
			});
			tr.dblclick(function(){var cu=_getcurrent();_getinfo(cu.x);_hidedata();});
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
		if (data.length>0) {
			for(var i=0;i<data.length;i++)
			{
				var tr=$('<tr style="border:1px solid #000;background-color:#ffc;height:25px;" ></tr>').appendTo(mlist);
				tr.click(function(){
					var sel=$(this).parent().find('tr:visible').index($(this));
					_move(sel);
					var cu=_getcurrent();
					rows[cu.x][cu.y].find('input').focus();
				});
				tr.dblclick(function(){var cu=_getcurrent();_getinfo(cu.x);_hidedata();});
				for(var j in data[i]){tr.append('<td key="'+j+'">'+data[i][j]+'</td>');}
			}
		}else{
			var cu=_getcurrent();
			// var tr=rows[cu.x][cu.y].closest('tr').find('input').val('');
			var tr=rows[cu.x][cu.y].siblings('td').find('input').val('');
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
			console.log(rd);
			_coverdata(rd);
			_search.apply(this,[s,v]);
		}});
	};
	var _isurl=function(s){return /http(s)?:\/\//.test(s)?true:/\/[\w]+(\/[\w]+)+/.test(s);};
	_gethead();
	_gettemp();
	_createdata();
	$(document).click(function(e){var c=$(e.target);if(c.parents('div').is(mdiv)){return false;}_hidedata();});
	return this;
}