/**
 * 浏览器导出Excel(.xls)文件
 * 可以分别设置后导出(大量数据导出)，也可以一次性导出(简单数据导出)
 * 参数：opt[json对象]
 * ----------name:[字符串],下载文件名、标题名
 * ----------title:[json对象或者'<tr><td><td></tr>'字符串],字符串可以行列合并
 * ----------foot:[json对象或者'<tr><td><td></tr>'字符串],字符串可以行列合并
 *
 *该类只有方法：
 *------exporter(name,data):一次性导出整个data对象或者'table'
 *------head(h)：设置表头信息为h,h可以是json对象，也可以是'<tr><td></td></tr>'
 *------foot(f)：设置表头信息为f,f可以是json对象，也可以是'<tr><td></td></tr>'
 *------rows(d)：添加数据行d，d可以是二维json对象，也可以是'<tr><td></td></tr>',字符串可以包含多个行
 *------exp():执行导出，生成Excel(.xls)文件。
 * 
 * @authors ronshn_cat (505221851@qq.com)
 * @date    2016-12-18 22:31:49
 * @version 1.0
 */

function Excel(opt)
{
	var _name='';var _title={};var _foot={};var _top='';
	var _d={'table':'','title':'','head':'','body':'','foot':''};
	_name=opt['name'];_title=opt['title'];_foot=opt['foot'];
	//判断浏览器是否ie
	var _ie=function(){var a=window.navigator.userAgent;return a.indexOf("MSIE")>=0?true:false;};
	//把字符串转换为base64数据
	var _base64=function(s){return window.btoa(unescape(encodeURIComponent(s)));};
	//导出整个json对象
	this.exporter=function(name,data)
	{
		_name=name;
		this.head().foot().rows(data).exp();
	};
	//设置表头
	this.head=function(h)
	{
		_title=h?h:_title;
		if(typeof(_title)=='string')
		{
			_d['head']=_title;
		}else
		{
			_d['head']='<tr>';
			for(var i in _title){_d['head']+='<td>&nbsp;'+_title[i]+'</td>';}
			_d['head']+='</tr>';
		}
		return this;
	};
	//添加表数据
	this.rows=function(d)
	{
		if(typeof(d)=='string')
		{
			_d['body']+=d;
		}else
		{
			for(var i=0;i<d.length;i++)
			{
				var it=d[i];_d['body']+='<tr>';
				if(typeof(_title)!=='string')
				{
					for(var j in _title){var v=it[j]?it[j]:'';_d['body']+='<td>'+v+'</td>';}
				}else
				{
					for(var j in it){var v=it[j]?it[j]:'';_d['body']+='<td>'+v+'</td>';}
				}
				_d['body']+='</tr>';
			}
		}
		return this;
	};
	//设置表尾
	this.foot=function(f)
	{
		_foot=f?f:_foot;
		if(typeof(_foot))
		{
			_d['foot']=_foot;
		}else
		{
			_d['foot']='<tr>';
			for(var i in _foot)
			{
				_d['foot']+='<td>'+_foot[i]+'</td>';
			}
			_d['foot']+='</tr>';
		}
		return this;
	};
	//导出数据
	this.exp=function()
	{
		_d['head']=_d['head'].replace(/rowspan\=[\'|\"].*?[\'|\"]/,'');
		var n=0;var b=$('<table/>').appendTo('body');
		b.html(_d['head']).find('tr').last().find('td').each(function(){
			var col=$(this).attr('colspan');n+=col?col:1;
		});
		b.remove();_settitle(n);
		_d['table']='<table border="1">'+_d['title']+_d['head']+_d['body']+_d['foot']+'</table>';
		if(_ie()){_ieexp();}else{_otexp();}
	};
	//ie浏览器导出
	var _ieexp=function()
	{
		var win=window.open("","_blank","left=0,top=0,scrollbars=no,width=0,height=0");
		win.document.write(_d['table']);
		win.document.close();  
        win.document.execCommand('Saveas',true,_name+'.xls');  
        win.close();
	};
	//除ie浏览器实现导出
	var _otexp=function()
	{
		var str='<html xmlns:o="urn:schemas-microsoft-com:office:office" ';
		str+='xmlns:x="urn:schemas-microsoft-com:office:excel" ';
		str+='xmlns="http://www.w3.org/TR/REC-html40">';
		str=str+_top+'<body>'+_d['table']+'</body></html>';
		var a=$('<a style="cursor:pointer;display:none;">下载</a>').appendTo('body');
		a.attr('href','data:application/vnd.ms-excel;base64,'+_base64(str));
		a.attr('download',_name+'.xls');
		a[0].click();
	};
	//获取头部信息
	var _gettop=function()
	{
		var str='<head>';
			str+='<meta http-equiv="content-type" content="text/html; charset=UTF-8;" />';
			str+='<xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
				str+='<x:Name>'+_name+'</x:Name>';
			str+='<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>';
			str+='</x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml>';
			str+='<style type="text/css">';
			str+='table{border-collapse:collapse;border:thin solid #999;}';
			str+='table tr{font-size:14px;height:25px;}';
			str+='table td{border:thin solid #999;padding:2px 5px;text-align:center;}';
			str+='</style>';
		str+='</head>';
		return str;
	};
	//设置表标题
	var _settitle=function(n)
	{
		_d['title']='<tr><td colspan="'+n+'" style="font-size:24px;font-weight:blod;text-align:center;">'+_name+'</td></tr>';
		return this;
	};
	_top=_gettop();
	if(_title){this.head();}
	if(_foot){this.foot();}
	return this;
}