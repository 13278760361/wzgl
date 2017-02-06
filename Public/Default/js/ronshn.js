var m_ronshn_editors=[];var timer=null;
$(document).ready(function(){
    var a=['.lists tr','[poster]','[geter]','[uploader]','[clicker]','[selectfile]','.poster','.geter','table [order]'];
    var t={'table [order]':{'onselectstart':'return false'},'label':{'onselectstart':'return false'}};
    _needdom();_needprop(t);_needstyle(a);_prerun();
    timer=new _Timer();
});

//跳转链接
$(".lists").delegate("tr",'click',function(){
    if(!$(this).attr('poster')&&!$(this).hasClass('poster'))
    {var act=$(this).attr('action');if(act){redirect(act);}}
    var url=$(this).attr('url');if(url){redirect(url);}
    var href=$(this).attr('href');if(href){redirect(href);}
});

//post多控件提交
//$(document).on("click","[poster]",function(){
$(document).delegate('[poster]','click',function(){
    _geteditorhtml();
	var s=this;$("[checker]").blur();
    var b=$(this).attr('before');
    b=_beforesubmiter(s,b);
	var d=todatas($(this).attr('poster'));
	var u=$(this).attr('action');
    var f=$(this).attr('return');
    var p=$(this).attr('parm');
    var c=$(this).attr('confirm');
    d=$.extend(d,b,true);
    var o={tag:s,type:'post',url:u,data:d,call:f,parm:p,mask:1};
    if(c){
        confirmer({msg:c,call:submiter,parm:o});
    }else{
        submiter(o);
    }
    return false;
});

//post控件提交
//$(document).on("click",".poster",function(){
$(document).delegate('.poster','click',function(){
    var s=this;$("[checker]").blur();
    var b=$(this).attr('before');
    b=_beforesubmiter(s,b);
    var d=todatas(this,'^poster-');
    var u=$(this).attr('action');
    var f=$(this).attr('return');
    var p=$(this).attr('parm');
    var c=$(this).attr('confirm');
    d=$.extend(d,b,true);
    var o={tag:s,type:'post',url:u,data:d,call:f,parm:p,mask:1};
    if(c){
        confirmer({msg:c,call:submiter,parm:o});
    }else{
        submiter(o);
    }
    return false;
});

//get多控件提交
//$(document).on("click","[geter]",function(){
$(document).delegate('[geter]','click',function(){
    _geteditorhtml();
    var s=this;$("[checker]").blur();
    var b=$(this).attr('before');
    b=_beforesubmiter(s,b);
    var d=todatas($(this).attr('geter'));
    var u=$(this).attr('action');
    var f=$(this).attr('return');
    var p=$(this).attr('parm');
    var c=$(this).attr('confirm');
    d=$.extend(d,b,true);
    var o={tag:s,type:'get',url:u,data:d,call:f,parm:p,mask:1};
    if(c){
        confirmer({msg:c,call:submiter,parm:o});
    }else{
        submiter(o);
    }
    return false;
});

//post控件提交
//$(document).on("click",".geter",function(){
$(document).delegate('.geter','click',function(){
    var s=this;$("[checker]").blur();
    var b=$(this).attr('before');
    b=_beforesubmiter(s,b);
    var d=todatas(this,'^geter-');
    var u=$(this).attr('action');
    var f=$(this).attr('return');
    var p=$(this).attr('parm');
    var c=$(this).attr('confirm');
    d=$.extend(d,b,true);
    var o={tag:s,type:'post',url:u,data:d,call:f,parm:p,mask:1};
    if(c){
        confirmer({msg:c,call:submiter,parm:o});
    }else{
        submiter(o);
    }
    return false;
});

//行编辑代录框
$(document).delegate("input[type='text'].editor",'focus',function(){
    var tag=this;var width=$(tag).attr('width');height=$(tag).attr('height');
    width=width?width:tag.offsetWidth;height=height?height:100;
    var val=$(tag).val();var off=$(tag).offset();
    var top=off.top;var left=off.left;var h=tag.offsetHeight;
    var main=$('<div></div>').appendTo('body');
    main.css({'position':'absolute','overflow':'hidden','zIndex':'10','background':'#fff','borderRadius':'5px','border':'1px solid #ddd','padding':'5px'});
    main.css({'left':left+'px','top':(top+h)+'px'});
    main.width(width-12);main.height(height-12);
    var area=$('<textarea style="width:100%;height:100%;border:none;resize:none;">'+val+'</textarea>').appendTo(main);
    area.focus();area.select();
    area.blur(function(){$(tag).val($(this).val());main.remove();});
});

//上传处理
//$(document).on("click","[uploader]",function(){
$(document).delegate('[uploader]','click',function(){
    var s=this;var f=$("[up='file']");var d=todatas(this,'^uploader-');
    var pp=$(s).attr('uploader');var u=$(s).attr('action');
    var c=$(s).attr('success');var p=$(s).attr('parm');
    var bf=$(s).attr('before');
    for(var i in d){f.attr('up-'+i,d[i]);}
    f.attr('before',bf);
    f.attr('prop',pp);f.attr('action',u);
    f.attr('call',c);f.attr('parm',p);
    f.click();
    return false;
});

//选择文件处理
$(document).delegate('[selectfile]','click',function(){
    var s=this;var u=$(s).attr('action');
    var pp=$(s).attr('selectfile');
    var img=$('[img='+pp+']');
    var c=$(s).attr('return');
    var ff = function(l){
    if(c){c=eval('('+c+')');c.apply(s,[l])}
    else{ var val=l.win.getSelectFile();if(img){img.attr('src',val)};$("["+pp+"='val']").val(val);}
    }
    opener({title:'选择文件',content:u,area:['1000px','500px'],btn:['确定','取消'],yes:ff});
    return false;
});

//点击加载
$(document).delegate("[clicker]",'click',function(){
    var p=$(this).attr('clicker');
    $("[pager='"+p+"']").trigger('loading');
});
//滚动加载
$("[scroller]").scroll(function(){
    var p=$(this).attr('scroller');var sc=$(this).scrollTop();
    var st=$(this).attr('myscroll');st=parseInt(st,10)?parseInt(st,10):0;
    if(sc<=st){return false;}
    var mh=$(this).height();var ch=0;
    $(this).children().each(function(){ch+=$(this).height();});
    $(this).attr('myscroll',sc);
    if(ch-mh-sc<100){$("[pager='"+p+"']").trigger('loading');}
});

//加载事件
$(document).delegate("[pager]",'loading',function(){
    var o=this;var u=$(o).attr('action');var f=$(o).attr('return');var boo=true;
    var st=$(o).attr('start');var d=todatas(o,'^pager-');st=parseInt(st,10)?parseInt(st,10):1;
    var apend=function(d){$(o).append(d);};f=f?eval('('+f+')'):apend;
    var ff=function(d){boo=true;st=st+1;$(o).attr('start',st);if(f){f.apply(o,[d]);}};
    if(!boo){return false;}
    loading({tag:o,url:u,data:d,start:st,call:ff});
});

//加载方法
function loading(s)
{
    var o=s['tag']?s['tag']:this;var t=s['type']?s['type']:'post';var d=s['data']?s['data']:{};
    var u=s['url'];var c=s['call'];var p=s['parm'];var st=s['start']?s['start']:1;
    var ff=function(d){unmask();if(c){p=p?($.isArray(p)?p:[p]):[];p.unshift(d);c.apply(o,p);}}
    $.ajax({type:t,url:u,data:d,beforeSend:function(){loader();},error:function(e){unmask();},success:ff});
}

//验证
//$(document).on("blur","[checker]",function(){
$(document).delegate('[checker]','blur',function(){
    var s=this;
    var v=$(s).val();
    var p=$(s).attr('checker');
    var f=$(s).attr('false');
    if(!checker(v,p)){tiper({tag:s,msg:f});$(s).attr('failed','true');}else{$(s).attr('failed','false');}
});

//表格中表头排序
$(document).delegate('table [order]','dblclick',function(){
    var key=$(this).attr('order');
    var val=$(this).attr('_by');
    var form=$(this).parents('form');
    var action=form.attr('action');
    action=action.split('.')[0];
    val=val?val:'asc';val=val=='asc'?val:'desc';
    data='sot::'+key+' '+val;
    action+=action.indexOf('::')>-1?'::':'/';
    action+=data;
    form.attr('method','get');
    form.attr('action',action);
    form.submit();
});
//页面加载执行方法
function _prerun()
{
    $("table[sorter]").each(function(){
        var sb=$(this).attr('sorter');
        sb=sb.replace(/\s+/,' ');
        ss=sb.split(' ');
        if(ss.length<=0){return false;}
        var fn=typeof(ss[1])=='undefined'?'asc':ss[1];
        var by=fn;by=by=='asc'?'desc':'asc';
        fn=fn=='asc'?'up':'down';
        var si='<i class="arrowd icon-caret-'+fn+'"></i>';
        $(this).find("[order]").each(function(){
            var s=$(this).find('i');
            if(s[0]){$(this).remove();}
            if($(this).attr('order')==ss[0]){
                $(this).prepend(si);$(this).attr('_by',by);
            }else{
                $(this).removeAttr('_by');
            }
        });
    });
    
    //实例化编辑器
    if(m_ronshn_editors.length>0){var a=m_ronshn_editors;for(var i=0;i<a.length;i++){if(a[i].n){a[i].o.destroy();a.splice(i,1);}}}
    $("textarea[editor]").each(function(){
        var p=$(this).attr('editor');
        var u=$(this).attr('action');
        var d=$(this).attr('new');
        if(m_ronshn_editors.length)
        {for(var i=0;i<m_ronshn_editors.length;i++){var key=m_ronshn_editors[i].o.key;if(key==p){return true;}}}
        $(this).attr('id',p);d=d?1:0;
        var e=UE.getEditor(p,{elementPathEnabled:false,serverUrl:u});
        m_ronshn_editors.push({o:e,n:d});
    });
}

//提交前置处理
function _beforesubmiter(s,b){var o=s?s:this;var ss={};b=b?($.isFunction(b)?b:eval('('+b+')')):false;if(b){b.apply(o,[ss]);}return ss;}
//提交数据
function submiter(s)
{
    var o=s['tag'];var m=s['mask'];
    var t=s['type']?(typeof(s['type'])=='string'?s['type'].toLowerCase():''):'';
    var u=s['url']?(typeof(s['url'])=='string'?s['url']:''):'';
    var d=s['data']?($.isPlainObject(s['data'])?s['data']:{'r':s['data']}):{};
    var f=s['call'];var p=s['parm'];var er=s['error']?s['error']:function(){};
    if(!u){msg('必须指定一个提交地址！');return false;}
    t=t?(t=='post'?'post':'get'):'get';er=$.isFunction(er)?er:eval('('+er+')');
    o=o?(typeof(o)=='string'?$(o)[0]:(o.$?o[0]:o)):this;
    var fn=f?($.isFunction(f)?f:(isurl(f)?redirect:(f<0?redirect:eval('('+f+')')))):ajaxmsg;
    p=f?($.isFunction(f)?(p?($.isArray(p)?p:[p]):[]):(isurl(f)?[f]:(f<0?[]:(p?($.isArray(p)?p:[p]):[])))):[];
    
    var ff=function(e){
        e=$.isArray(e)?e:($.isPlainObject(e)?e:(/^\{.+\}$/.test(e)?eval('('+e+')'):(/^\[.+\]$/.test(e)?eval('('+e+')'):e)));
        if(e.url){e['parm']=e['url'];e['call']=e.url;}if(!isurl(e)){p.unshift(e);}
        fn.apply(o,p);
    };
    if(m){loader();}
    $.ajax({type:t,url:u,data:d,error:er,success:ff});
}

//ajax返回提示处理
function ajaxmsg(d){if($.isPlainObject(d)){msg(d.info,d.status,d.call,d.parm);}else{msg('出错啦！请联系管理员！');}}
//ajax返回提示跳转或刷新
function ajaxmsggo(d){var f=d.status?d.url:-1;msg(d.info,d.status,f);}
//ajax返回提示
function ajaxmsgstop(d){msg(d.info,d.status);}
//ajax返回跳转或刷新
function ajaxgo(d){var u=d.status?d.url:-1;redirect(u);}
//ajax返回不处理
function ajaxstop(d){}
//重定向页面
function redirect(u){if(u){window.location.href=u;}else{window.location.reload();}}
//提示信息，
function msg(m,s,f,p){messager({msg:m,status:s,call:f,parm:p});}

//通过特定规则获取(键-值)
function todatas(o,n)
{
    if(!o){return false;}
    return typeof(o)=='object'?(n?_bystr(o,n):false):(typeof(o).toLowerCase()=='string'?_byattr(o):false);
}

//通过属性获取键值对
function _byattr(p)
{
	if(!p){return false;}p=p.replace(/\-$/,'').replace(/^\-/,'');if(!p){return false;}
    var reg=new RegExp(p+'(-[a-zA-Z0-9_]+)?\=','g');var d={};
    var a=$('body').html().match(reg);a=a.unique();var e='default';
    var f=[];$(':input').each(function(){f.push(this);});
    if(a.length<=0){return false;}
    for(var i=0;i<a.length;i++)
    {
        var p=a[i].replace(/\=$/,'');var s=p.split('-');
        var g=s.length>1?s[1]:e;d[g]={};
        $("["+p+"]").each(function(){
            var t=$(this);var k=t.attr(p);
            if($(t).attr('failed')=='true'){d=false;return false;}
            if($.inArray(this,f)>=0)
            {
                var v=t.val();
                if(t.attr('type')=='radio'){if(this.checked){d[g][k]=v;}return true;}
                if(t.attr('type')=='checkbox'&&!this.checked){return true;}
                if(d[g][k]){if($.isArray(d[g][k])){d[g][k].push(v);}else{d[g][k]=[d[g][k]];d[g][k].push(v);}}else{d[g][k]=v;}
            }else
            {
                var v=t.text();
                if(d[g][k]){if($.isArray(d[g][k])){d[g][k].push(v);}else{d[g][k]=[d[g][k]];d[g][k].push(v);}}else{d[g][k]=v;}
            }
        });
    }
    $.extend(d,d[e],true);delete d[e];return d;
}

//通过字符串获取键值对
function _bystr(o,n)
{
	if(!o||!n){return false;}
    var d={};var r='';var t=o.attributes;var m="^$%";
    r=n.substr(0,1);n=m.indexOf(r)>-1?n.substr(1,n.length):n;
    switch(r){case '^':r=eval("/^"+n+"/");break;case '$':r=eval("/"+n+"$/");break;case '%':r=eval("/"+n+"/");break;default:r=eval("/^"+n+"$/");break;}
    for(var i=0;i<t.length;i++){var p=t[i].name;var v=t[i].value;if(p=='failed'){return v=='true'?false:true;}if(r.test(p)){p=p.split('-')[1];d[p]=v;}}
    return d;
}

//验证v是否符合p
function checker(v,p)
{
    if(p){
        if(/^\/.+\/$/.test(p)){
            p=p.substr(1,p.length);p=p.substr(0,p.length-1);
            return new RegExp(p).test(v);
        }else if(!/^[\^\$\%]+/.test(p)){
            var p=$.isFunction(p)?p:($.isFunction(eval('('+p+')'))?eval('('+p+')'):p);
            if($.isFunction(p)){p.apply(this,[v]);}else{return v==p;}
        }else{
            var t="^$%";var n=p.substr(0,1);p=t.indexOf(n)>-1?p.substr(1,p.length):p;
            switch(n){
                case '^':p="^"+p;break;case '$':p=p+"$";break;case '%':break;
                default:p="^"+p+"$";break;
            }
            return new RegExp(p).test(v);
        }
    }else{
        return v?true:false;
    }
}

//把编辑器内容写到textarea中
function _geteditorhtml()
{
    if(!m_ronshn_editors.length){return false;}
    for(var i=0;i<m_ronshn_editors.length;i++)
    {
        var k=m_ronshn_editors[i].o.key;
        var e=$("[editor='"+k+"']");
        if(e.size()<=0){continue;}
        e.html(UE.getEditor(k).getContent());
    }
}

//验证字符串是否是链接地址
function isurl(s){return /http(s)?:\/\//.test(s)?true:/\/[\w]+(\/[\w]+)+/.test(s);}

//实例化layer
function _layer(){return window.top==window.self?layer:window.top.layer;}
//layer.open
function opener(s)
{
	var l=_layer();var frame={};
    var o=s['tag'];var m=s['content']?s['content']:'提示信息！';var t=isurl(m)?2:1;
    var su=s['success']?s['success']:'';su=su?($.isFunction(su)?su:eval('('+su+')')):function(){};
    var e=s['end']?s['end']:'';e=e?($.isFunction(e)?e:eval('('+e+')')):function(){};
    var y=s['yes']?s['yes']:'';y=y?($.isFunction(y)?y:eval('('+y+')')):function(){};
    var b=s['b2']?s['b2']:'';b=b?($.isFunction(b)?b:eval('('+b+')')):function(){};
    o=o?(typeof(o)=='string'?$(o)[0]:(o.$?o[0]:o)):this;s['type']=t;
    s['success']=function(a,i){if(t==2){frame['dom']=$(l.getChildFrame('body',i).html());frame['win']=a.find('iframe')[0].contentWindow;}else{frame['dom']=m;frame['win']=window;}su.apply(o,[frame]);};
	s['end']=function(){e.apply(o,[frame]);};s['btn2']=function(i,a){return t==2?b.apply(o,[frame]):b.apply(o,[i,a]);};
    s['yes']=function(i,a){if(t==2){y.apply(o,[frame]);}else{y.apply(o,[i,a])}l.close(i);};
    l.open.apply(o,[s]);
}
//layer.msg
function messager(s)
{
	var l=_layer();
    var o=s['tag'];var m=s['msg'];
    var c=s['status']?1:2;
    var h=s['status']?5:6;
    var f=s['call'];var p=s['parm'];
	var d={icon:c,shade:[0.5,'#000'],time:1000,shift:h};
    o=o?(typeof(o)=='string'?$(o)[0]:(o.$?o[0]:o)):this;
    var fn=f?($.isFunction(f)?f:(isurl(f)?redirect:(f<0?redirect:eval('('+f+')')))):'';
    p=f?($.isFunction(f)?(p?($.isArray(p)?p:[p]):[]):(isurl(f)?[f]:(f<0?[]:(p?($.isArray(p)?p:[p]):[])))):[];
	var ff=function(){l.close.apply();if(fn){fn.apply(o,p);}unmask.apply(o);};
	l.msg.apply(this,[m,d,ff]);
}
//layer.confirm
function confirmer(s)
{
	var l=_layer();
    var o=s['tag'];var m=s['msg']?s['msg']:'提示！';
    var f=s['call'];var p=s['parm'];
    o=o?(typeof(o)=='string'?$(o)[0]:(o.$?o[0]:o)):this;
    var fn=f?($.isFunction(f)?f:(isurl(f)?redirect:(f<0?redirect:eval('('+f+')')))):'';
    p=f?($.isFunction(f)?(p?($.isArray(p)?p:[p]):[]):(isurl(f)?[f]:(f<0?[]:(p?($.isArray(p)?p:[p]):[])))):[];
	var ff=function(){if(fn){fn.apply(o,p);}};
	l.confirm.apply(this,[m,{icon:3,title:'提示'},ff]);
}
//layer.prompt
function prompter(s)
{
	var l=_layer();
    var o=s['tag']?s['tag']:this;
    var t=s['type']?parseInt(s['type'],10):0;
    var v=s['default']?s['default']:'请输入信息！';
    var b=s['title']?s['title']:'输入信息!';
    var f=s['call'];var p=s['parm'];var k=s['key'];
    t=t?(t>0?2:1):3;
	var d={formType:t,value:v,title:b};
    var fn=f?($.isFunction(f)?f:(isurl(f)?redirect:(f<0?redirect:eval('('+f+')')))):'';
    p=f?($.isFunction(f)?(p?($.isArray(p)?p:[p]):[]):(isurl(f)?[f]:(f<0?[]:(p?($.isArray(p)?p:[p]):[])))):[];
    var ff=function(v,i,e){if(p[0]['data']&&k){p[0]['data'][k]=v;}else{p.unshift(v);}l.close.apply(this,[i]);if(fn){fn.apply(o,p);}};
	l.prompt.apply(o,[d,ff]);
}
//laey.tips
function tiper(s)
{
	var l=_layer();
    var o=s['tag'];var m=s['msg']?s['msg']:'提示信息！';
    var t=s['tips']?s['tips']:1;
    var c=s['color']?s['color']:'';
    var tm=s['time']?s['time']:3;
    t=t==1?2:1;tm=tm*1000;var tip=c?[t,c]:t;
    o=o?(typeof(o)=='string'?$(o)[0]:(o.$?o[0]:o)):'body';
	l.tips.apply(this,[m,o,{tips:tip,time:tm,tipsMore:true}]);
}
//layer.alert
function alerter(s)
{
	var l=_layer();
    var o=s['tag'];var m=s['msg'];var c=s['icon']?s['icon']:1;var f=s['call'];var p=s['parm'];
    o=o?(typeof(o)=='string'?$(o)[0]:(o.$?o[0]:o)):this;
    var fn=f?($.isFunction(f)?f:(isurl(f)?redirect:(f<0?redirect:eval('('+f+')')))):'';
    p=f?($.isFunction(f)?(p?($.isArray(p)?p:[p]):[]):(isurl(f)?[f]:(f<0?[]:(p?($.isArray(p)?p:[p]):[])))):[];
    var ff=function(i){l.close.apply(this,[i]);if(fn){fn.apply(o,p);}};
	l.tips.apply(this,m,{icon:c},ff);
}
//layer.load
function loader(s)
{
	var l=_layer();
    var t=s?(s['type']?s['type']:1):1;
    var i=s?(s['alpha']?s['alpha']:0.5):0.5;
    var c=s?(s['color']?s['color']:'#000'):'#000';
	return l.load.apply(this,[t,{shade:[i,c]}]);
}
//layer.closeAll
function unmask(i)
{
    var l=_layer();
    if(i){l.close(i);}else{l.closeAll.call(this);}
}

//写入必须样式
function _needstyle(a)
{
    if(!a){return false;}
    var t=$('head').size()?$('head'):($('body').size()?$('body'):$('html'));
    var s='<style type="text/css">';
    for(var i=0;i<a.length;i++){s=s+a[i]+',';}s=s.substr(0,s.length-1);
    s+='{cursor:pointer;-moz-user-select:none;}</style>';
    t.append(s);
}
//写入必须属性
function _needprop(a)
{
    if(!a){return false;}
    for(var i in a){
        var t=$(i);var p=a[i];
        if($.isPlainObject(p)){for(var j in p){t.each(function(){$(this).attr(j,p[j]);});}}else{p=p.split('=');t.attr(p[0],p[1]);}
    }
}
//写入必须元素
function _needdom()
{
    var html='<form up="form" style="display:none;" method="post" enctype="multipart/form-data">';
    html+='<input up="file" type="file" name="myfile">';
    html+='</form>';
    $('body').append(html);
}

//交互定时器类
function _Timer()
{
    var tim=null;var daley=1000;var nn=0;var r=false;var tms=[];
    
    this.add=function(o){
        var opt={tag:this,url:'',type:'post',data:{},call:'',parm:'',name:'',time:5,num:0,log:0,st:0};
        o=$.extend(opt,o);
        o['name']=o['name']?o['name']:'t'+(parseInt(tms.length,10)+1);
        tms.push(o);if(!r){_start();}
        return this;
    };
    this.remove=function(n){
        if($.type(n)!='string'&&$.type(n)!='number'){return false;}
        if($.type(n)=='string'){for(var i=0;i<tms.length;i++){if(tms[i]['name']==n){n=i;break;}}}
        tms.splice(n,1);
        if(!tms.length){_stop();}
        return this;
    };
    this.getinfo=function(n){
        if($.type(n)!='string'&&$.type(n)!='number'){return false;}
        if($.type(n)=='string'){for(var i=0;i<tms.length;i++){if(tms[i]['name']==n){n=i;break;}}}
        return tms[n];
    };
    var _run=function(){
        if(!r||!tms.length){_stop();return false;}nn++;
        for(var i=0;i<tms.length;i++){
            var g=tms[i]['tag'];var n=tms[i]['num'];var st=tms[i]['st'];var log=tms[i]['log'];
            var m=tms[i]['time'];var c=tms[i]['call'];var p=tms[i]['parm'];
            var u=tms[i]['url'];var t=tms[i]['type'];var d=tms[i]['data'];
            if((nn-st)%m){continue;}
            if(n&&log>=n){tms.splice(i,1);continue;}
            tms[i]['log']++;tms[i]['st']=st?st:nn;
            if(u){
                submiter({tag:g,type:t,url:u,data:d,call:c,parm:p});
            }else{
                p=p?($.isArray(p)?p:[p]):[];c.apply(g,p);
            }
        }
    };
    var _start=function(){r=true;tim=window.setInterval(_run,daley);};
    var _stop=function(){window.clearInterval(tim);tim=null;r=false;nn=0;};
    return this;
}

//数组去重
Array.prototype.unique=function()
{
    var res=[];var json={};
    for(var i=0;i<this.length;i++){
      if(!json[this[i]]){res.push(this[i]);json[this[i]]=1;}
    }
    return res;
}
//数组包含
Array.prototype.has=function(o)
{
    if(this.length&&this.length>1){
        for(var i=0;i<this.length;i++){if(this[i]==o){return this.indexOf(o)+1;}}return 0;
    }else{
        return this.length?this[0]==o?1:0:0;
    }
}
//绑定上传
$(document).delegate("[up='file']",'change',function(){
// $(document).on("change","[up='file']",function(){
    var s=this;var img;var val;var d={};var pp=$(s).attr('prop');var c=$(s).attr('call');
    var p=$(s).attr('parm');var f=$("[up='form']");var o=$("[uploader='"+pp+"']");
    var bf=$(s).attr('before');bf=bf?($.isFunction(bf)?bf:eval('('+bf+')')):'';
    var d1=todatas(this,'^up-');var d2=bf?bf.apply(s,[d1]):{};var li=0;d=$.extend(d,d1,d2);
    img=$("["+pp+"='img']");val=$("["+pp+"='val']");f.attr('action',$(s).attr('action'));
    if(c){c=eval('('+c+')');p=p?($.isArray(p)?p:[p]):[];}
    f.ajaxSubmit({
        dataType:'json',data:d,beforeSend:function(){li=loader();},
        error:function(){messager({msg:'上传失败',call:unmask});},
        success:function(d){
            unmask(li);
            if(d.status){
                if (d.info.status) {
                    ajaxmsg(d.info);
                }
                if(img){img.attr('src',d.url);img.show();}
                if(val){val.val(d.url);}
                if(c){p.unshift(d);c.apply(o,p);}
            }
        }
    });
});