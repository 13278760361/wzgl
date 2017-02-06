/**
 * 
 * @authors ronshn_cat (505221851@qq.com)
 * @date    2016-12-22 14:04:47
 * @version $Id$
 */

function Report(opt)
{
	var $o={'name':'ronshn统计报表','padding':1,'space':1};
	$o=$.extend($o,opt);var $colum=null;var $field=null;
	var $name=$o['name'];$pad=$o['padding'];$spa=$o['space'];
	var $title=[];var $data=[];var $foot=[];var $head=[];
	var $rows=[];var $cols=[];var $ccount=0;var $rcount=0;
	var $dt={'rows':[],'cols':[]};var $value={'field':'','round':'0'};
	var $tb={'name':'','title':'','head':'','body':'','foot':''};
	//添加数据
	this.addrows=function(s){typeof(s)=='string'?_gets('body',s):_geto('body',s);return this;};
	//添加标题
	this.title=function(o){_gets('title',o);return this;};
	//添加grid标题
	this.head=function(o){typeof(o)=='string'?_gets('head',o):_geto('head',o);return this;};
	//添加表尾
	this.foot=function(o){_gets('foot',o);return this;};
	//生成交叉报表
	this.cross=function(r,c,v)
	{
		if(!r||!c||!v){return false;}
		r=typeof(r)=='string'?r.split(','):r;c=typeof(c)=='string'?c.split(','):c;
		if(typeof(v)=='string'){var vs=v.split(',');v={'field':vs[0],'round':vs[1]?vs[1]:'0'}}
		var row=[];var col=[];$rows=r;$cols=c;$value=v;
		for(var i=0;i<r.length;i++){row.push(_getc(r[i]));}
		for(var i=0;i<c.length;i++){col.push(_getc(c[i]));}
		$dt['rows']=_zuhe(row);$dt['cols']=_zuhe(col);
		_getcross();
		return this;
	};
	//生成普通报表
	this.grid=function(f,v)
	{
		if(!f){return false;}
		if(v){$value['round']=v;}
		typeof(f)=='string'?_gets('field',f):_geto('field',f);
		$colum=$field;for(var i in $field){$ccount++;}
		_getgrid();
		return this;
	};
	//展示表格到页面
	this.show=function(o){$(o).html(_gettable());_end();return this;};
	this.append=function(o){$(o).append(_gettable());_end();return this;}
	//导出表格到xls
	this.exporter=function(){_ie()?_ieout(_gettable()):_obout(_gettable());_end();return this;};
	//获取普通报表
	var _getgrid=function()
	{
		$tb['body']='';var str='';
		for(var i=0;i<$data.length;i++){
			var d=$data[i];var tr='<tr>';var n=0;
			for(var j in $colum){
				var v='';var al='';
				if(_num(d[j])){v=_format(d[j]);tr+='<td class="isnum">'+v+'</td>';}else{v=d[j];tr+='<td style="text-align:left">'+v+'</td>';}
				n++;
			}
			tr+='</tr>';str+=n>0?tr:'';
		}
		$tb['body']=str;
	};
	//获取grid副标题
	var _gethead=function()
	{
		if(!$field){return false;}
		var arr=[];
		if($head.length)
		{
			var ff=$head.reverse();
			for(var i=0;i<ff.length;i++)
			{
				var o={};var fs=ff[i];var n=0;
				for(var j in $field)
				{
					n--;
					if(n>0){continue;}
					if(fs[j])
					{
						o[j]=fs[j];n=parseInt(fs[j]['col'],10);
					}else
					{
						if(i==ff.length-1)
						{
							o[j]={'val':$field[j]['val'],'row':ff.length+1,'col':1};
							delete $field[j];
						}
					}
				}
				arr.push(o);
			}
		}
		
		arr.unshift($field);arr.reverse();
		$tb['head']='';
		for(var i=0;i<arr.length;i++)
		{
			var h=arr[i];var tr='<tr>';
			for(var j in h)
			{
				var td='<td rowspan="'+h[j]['row']+'" colspan="'+h[j]['col']+'" style="text-align:center;">'+h[j]['val']+'</td>';
				tr+=td;
			}
			tr+='</tr>';
			$tb['head']+=tr;
		}
	};
	//生成cross表格
	var _crossmap=function()
	{
		var str='';var r=$dt['rows'];var c=$dt['cols'];$ccount=c.length+$rows.length;var $rcount=r.length+$cols.length;
		for(var i=0;i<$rcount;i++){
			str+='<tr row="r'+i+'">';
			if(i<$cols.length){
				for(var j=0;j<$ccount;j++){
					var m=j-$rows.length;var p=j<$rows.length?'':$dt['cols'][m];var ps=p.split('_');var t=ps[i]?ps[i]:'';
					str+='<td row="r'+i+'" col="c'+j+'" style="text-align:center;">'+t+'</td>';
				}
			}else{
				for(var j=0;j<$ccount;j++){
					var m=i-$cols.length;var n=j-$rows.length;var p=j<$rows.length?$dt['rows'][m]:'';var ps=p.split('_');var t=ps[j]?ps[j]:'';
					if(!t){
						var o={};var rp=r[m].split('_');var cp=c[n].split('_');
						for(var k=0;k<rp.length;k++){o[$rows[k]]=rp[k];}for(var k=0;k<cp.length;k++){o[$cols[k]]=cp[k];}
						t=_getv(o);
					}
					var st=j<$rows.length?'text-align:center;':'text-align:right;';
					str+='<td row="r'+i+'" col="c'+j+'" style="'+st+'">'+t+'</td>';
				}
			}
			str+='</tr>';
		}
		return str;
	};
	//处理cross表格
	var _getcross=function()
	{
		var s=_crossmap();var tb=$('<table style="display:none;"></table>').appendTo('body');
		var box=$('<tbody></tbody>').appendTo(tb).append(s);
		//总计列
		box.find('tr').each(function(i){
			if(i<$cols.length){
				if(!i){$(this).append('<td rowspan="'+$cols.length+'" row="r'+i+'" col="c'+$ccount+'" style="text-align:center;font-weight:bold;">总计&nbsp;</td>');}
			}else{
				var num=0.00;
				$(this).find("td[row='r"+i+"']").each(function(j){if(j<$rows.length){return true;}num+=parseFloat($(this).text());});
				$(this).append('<td row="r'+i+'" col="c'+$ccount+'" style="text-align:right;font-weight:bold;" class="isnum">'+_format(num)+'</td>');
			}
		});$ccount++;
		//合计行
		var str='<tr>';
		for(var i=0;i<$ccount;i++){
			var td='';
			if(i<$rows.length){
				td=!i?'<td colspan="'+$rows.length+'" style="text-align:center;font-weight:bold;">合计：</td>':'';
			}else{
				var num=0.00;
				box.find('tr').each(function(j){if(j<$cols.length){return true;}num+=parseFloat($(this).find("td[col='c"+i+"']").text());});
				td='<td style="text-align:right;font-weight:bold;" class="isnum">'+_format(num)+'</td>';
			}
			str+=td;
		}
		str+='</tr>';box.append(str);$rcount++;
		//合并行标题
		for(var i=0;i<$rows.length;i++)
		{
			var rh='';var rn=0;
			box.find("td[col='c"+i+"']").each(function(j){
				if(j<$cols.length){return true;}
				var rtx=$(this).text();
				if(rh==rtx){
					var p=box.find('tr').eq(rn).find("td[col='c"+i+"']");var rsp=p.attr('rowspan');rsp=rsp?parseInt(rsp,10):1;$(this).remove();p.attr('rowspan',rsp+1);
				}else{
					rn=$(this).parent().index();
				}
				rh=rtx;
			});
		}
		//合并列标题
		for(var i=0;i<$cols.length;i++){
			var ch='';
			box.find("td[row='r"+i+"']").each(function(j){
				if(j<$rows.length){return true;}
				var ctx=$(this).text();
				if(ch==ctx){var p=$(this).prev();var csp=p.attr('colspan');csp=csp?parseInt(csp,10):1;$(this).remove();p.attr('colspan',csp+1);}
				ch=ctx;
			});
		}
		//交叉空白
		box.find('td').first().attr('rowspan',$cols.length).attr('colspan',$rows.length).html('&nbsp');
		box.find('tr').each(function(i){
			if(i>=$cols.length){return true;}
			$(this).find('td').each(function(j){
				if(j>=$rows.length||(!i&&!j)){return true;}$(this).remove();
			});
		});
		//取内容，丢壳
		$tb['body']=box.html();tb.remove();
	};
	//合并数据表格
	var _gettable=function()
	{
		$tb['head']='';
		_getname();_gettitle();_gethead();_getfoot();
		return '<table border="1" cellpadding="'+$pad+'" cellspacing="'+$spa+'"><tbody>'+$tb['name']+$tb['title']+$tb['head']+$tb['body']+$tb['foot']+'</tbody></table>';
	};
	//生成报表名称
	var _getname=function(){$tb['name']=$name?'<tr><td style="font-size:26px;font-weight:bold;text-align:center;" colspan="'+$ccount+'">'+$name+'</td></tr>':'';};
	//生成报表标题
	var _gettitle=function()
	{
		var str='';
		if($title.length){
			for(var i=0;i<$title.length;i++){
				str+='<tr><td colspan="'+$ccount+'" style="text-align:center;">'+$title[i]+'</td></tr>';
			}
		}
		$tb['title']=str;
	};
	//生成报表尾
	var _getfoot=function()
	{
		var str='';
		if($foot.length){
			for(var i=0;i<$foot.length;i++){
				str+='<tr><td colspan="'+$ccount+'" style="text-align:center;">'+$foot[i]+'</td></tr>';
			}
		}
		$tb['foot']=str;
	};
	//获取cross数据标题
	var _getc=function(k){var v='';var arr=[];var d=_sort(k);for(var i=0;i<d.length;i++){if(d[i][k]!=v){arr.push(d[i][k]);}v=d[i][k];}return arr;};
	//获取cross数据值
	var _getv=function(a)
	{
		var res=[];var val=0.00;
		for(var i=0;i<$data.length;i++){
			var d=$data[i];var b=false;
			for(var j in a){b=d[j]==a[j];if(!b){break;}}
			if(b){res.push(d[$value['field']]);}
		}
		if(res.length){for(i=0;i<res.length;i++){val+=res[i];}}else{val=0.00;}
		return _format(val);
	};
	//标签字符串转为对象
	var _gets=function(t,s)
	{
		var tb=$('<table style="display:none;"></table>').appendTo('body');
		var box=$('<tbody></tbody>').appendTo(tb);
		if(t=='field'||t=='head'||t=='body')
		{
			if(!_ok(s,'tr')||!_ok(s,'td')){return false;}
			box.append(s).children('tr').each(function(){
				var o={};$(this).children('td').each(function(){
					var k=$(this).attr('field');var v=$(this).text();
					var r=$(this).attr('rowspan');var c=$(this).attr('colspan');
					if(!k){return true;}v=v?v:'';r=r?r:1;c=c?c:1;
					o[k]=t=='head'||t=='field'?{'val':v,'row':r,'col':c}:v;
				});
				t=='field'?$field=o:(t=='head'?$head.push(o):$data.push(o));
			});tb.remove();
		}else
		{
			t=='foot'?$foot.push(s):$title.push(s);
		}
		return true;
	};
	//对象格式化
	var _geto=function(t,s)
	{
		s=$.isArray(s)?s:[s];
		for(var i=0;i<s.length;i++){
			var o={};for(var j in s[i]){
				o[j]=t=='field'?($.isPlainObject(s[i][j])?s[i][j]:{'val':s[i][j]}):s[i][j];
			}t=='field'?$field=o:(t=='head'?$head.push(o):$data.push(o));
		}
		return true;
	};
	//组合cross标题长度
	var _zuhe=function(a){var h=a[0];for(var i=1;i<a.length;i++){if(a[i].length){h=_merg(h,a[i]);}}return h;};
	//ie浏览器导出表格
	var _ieout=function(tb)
	{
		var str='<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
		str=str+_top()+'<body>'+tb+'</body></html>';
		var w=window.open("","_blank","left=0,top=0,scrollbars=no,width=0,height=0");
		w.document.write(str);w.document.close();
        w.document.execCommand('Saveas',true,$name+'.xls');w.close();
        return true;
	};
	//其他浏览器导出表格
	var _obout=function(tb)
	{
		var str='<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
		str=str+_top()+'<body>'+tb+'</body></html>';
		if(_size(str,'utf-8')<1024*3){alert('文件太小，不能导出！');return false;}
		var a=$('<a style="cursor:pointer;display:none;">下载</a>').appendTo('body');
		a.attr('href','data:application/vnd.ms-excel;base64,'+_base64(str));
		a.attr('download',$name+'.xls');a[0].click();a.remove();
		return true;
	};
	//格式化数据
	var _format=function(v){if(!_num(v)){return v;}var f=$value['round'];f=f.split('.');var p=f[1];return p?v.toFixed(p.length):Math.round(v);}
	var _end=function(){$title=[];$head=[];$colum=null;$field=null;$foot=[];};
	//判断是否数值
	var _num=function(v){return /^[0-9]*(\.[0-9]+)?$/.test(v);}
	//检测成对
	var _ok=function(s,t){var p1=s.match(new RegExp('<'+t+'\\s*.*?>'))||[];var p2=s.match(new RegExp('</'+t+'>'))||[];return p1.length==p2.length;};
	//获取非ie导出文件头
	var _top=function(){
		var str='<head><!--[if gte mso 9]><meta http-equiv="content-type" content="text/html; charset=UTF-8;" /><xml>';
		str+='<x:DocumentProperties><x:Created></x:Created><x:LastSaved></x:LastSaved><x:Version>1.0</x:Version></x:DocumentProperties>';
 		str+='<OfficeDocumentSettings xmlns="urn:schemas-microsoft-com:office:office"><RemovePersonalInformation/></OfficeDocumentSettings>';
 		str+='<Styles><Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Bottom"/><Borders/><Font ss:FontName="宋体" x:CharSet="134" ss:Size="12"/><NumberFormat/><Protection/></Style><Style ss:ID="s23"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/></Style></Styles>';
 		str+'<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><Selected/><Panes><Pane><Number>3</Number><ActiveRow>2</ActiveRow><ActiveCol>1</ActiveCol></Pane></Panes><ProtectObjects>False</ProtectObjects><ProtectScenarios>False</ProtectScenarios></WorksheetOptions>';
 		str+='<x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>'+$name+'</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook>';
 		str+='</xml><style type="text/css">table{border-collapse:collapse;border:thin solid #999;}table tr{font-size:14px;height:25px;}table td{border:thin solid #999;padding:2px 5px;text-align:center;}table td.isnum{mso-number-format:'+$value['round']+';text-align:right;}</style><![endif]--></head>';
 		return str;
 	};
	//判断浏览器是否ie
	var _ie=function(){var a=window.navigator.userAgent;return a.indexOf("MSIE")>=0?true:false;};
	//把字符串转换为base64数据
	var _base64=function(s){return window.btoa(unescape(encodeURIComponent(s)));};
	//json对象排序
	var _sort=function(k){return $data.sort(function(a,b){return ((a[k]<b[k])?-1:((a[k]>b[k])?1:0));});};
	//合并数组
	var _merg=function(h,c){var r=[];for(var i=0;i<h.length;i++){for(var j=0;j<c.length;j++){r.push(h[i]+'_'+c[j]);}}return r;};
	//获取文件内容大小
	var _size=function(s,h)
	{
	    var t=0,c,i;h=h?h.toLowerCase():'';
	    if(h==='utf-16'||h==='utf16'){
	        for(i=0;i<s.length;i++){c=s.charCodeAt(i);t+=c<=0xffff?2:4;}
	    }else{
	        for(i=0;i<s.length;i++){c=s.charCodeAt(i);t+=(c<=0x007f)?1:((c<=0x07ff)?2:((c<=0xffff)?3:4));}
	    }
	    return t;
	}
	return this;
}