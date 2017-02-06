function Sercher(o)
{
	var searchtxt='';
	var _inputs=[];//输入框
	var pop='';//弹出层
	var _cu=null;//input
	var _num=0;//记录goods的数值
	var tabs=[];
	var _temp={};
	var datafilter = o['datafilter'];
	
	var submiter=o['submit'];
	var _box=o['box'];var _sub=o['sub'];var _inp=o['inputer'];
	var _field=o['field']?o['field']:o['inputer'];
	var _type=o['type']?o['type']:'get';var _url=o['url']?o['url']:'';
	
	this.add=function()
	{
		_num++;
		box=_temp.clone(true).appendTo(_box);
		var inp=box.find("[field='"+_inp+"']");
		tabs.push(box);
		_inputs.push(inp);
		inp.keyup(_inpkey);
		box.find("[field]").each(function(){
			var f=$(this).attr('field');
			$(this).attr(submiter+'-'+_num,f);
		});
		return this;
	}
	
	this.del=function(o)
	{
		var box=$(o).parents(".m-sub");
		var goods_num = $('.m-box').children('.m-sub').length;
		if(goods_num==1){
			return;
		}else{
			box.remove();
		}
		box.remove();
		var ci=0;for(var i=0;i<_inputs.length;i++){if(_inputs[i].is($(o))){ci=i;break;}}
		var bi=box.index();
		_inputs.splice(ci,1);
		tabs.splice(bi,1);
		
	}
	
	var _load=function(d)
	{
		d=$.extend(o['data'],d);
		$.ajax({type:_type,url:_url,data:d,success:_loaded});
	};
	
	var _serch=function(str)
	{
		var reg=new RegExp(str,'i');
		pop.find('li').each(function(){
			if(reg.test($(this).text()))
			{
				$(this).show();
			}else
			{
				$(this).hide();
			}
		});
	}
	
	var _loaded=function(d){
		if(!d){return false;}
		var ul = $('<ul></ul>');
		for(var i=0;i<d.length;i++)
		{
			var dt=d[i];
			var li=$('<li></li>');
			for(var j in dt)
			{
				if(j==_field)
				{
					li.append('<span field="'+j+'">'+dt[j]+'</span>');
				}else
				{
					li.append('<span field="'+j+'" style="display:none;">'+dt[j]+'</span>');
				}
			}
			ul.append(li);
		}
		pop.html(ul.html());
		pop.find('li').click(_setval);
		var pos=_cu.position();
		var left=pos.left;
		var top=pos.top+_cu.height()+18+'px';
		pop.show();
		pop.css({'position':'absolute','top':top,'left':left,'zIndex':10});
		pop.appendTo(_cu.parent());
		_serch(searchtxt);
	};
	
	var _setval=function(e){
		var tag=e.currentTarget;
		var box=_cu.parents('.m-sub');
		box.find('input').each(function(){
			var f=$(this).attr('field');
			var v=$(tag).find("span[field='"+f+"']").text();
			$(this).val(v);
		});
		_close();
	};
	
	var _inpkey=function(e)
	{
		var tag=e.target;
		_cu=$(tag);
		searchtxt=$(tag).val();
		_load({field:_field,value:searchtxt,fileter:datafilter});
	}
	
	var _createpop=function()
	{
		pop=$('<ul class="search_html"></ul>').appendTo('body');
	}
	
	var _close=function()
	{
		pop.appendTo('body').hide();
	}
	
	_temp=$(_box).find(_sub).eq(0).clone(true);
	
	$(_box).find(_sub).eq(0).remove();
	
	_createpop();
	
	
	return this;
}