/*点击隐藏提示文字*/
var changePlacehoder = function(k) {
		var text = $(k).val();
		if(text) {
			$(k).next().val($(k).prev().text() ? $(k).prev().text() : $(k).next().val());
			$(k).prev().text('');
		} else {
			$(k).prev().text($(k).next().val());
		}
	}
//点击改变样式  通用
function clickActive(id, activclass) {
	$(id).each(function() {
		$(this).click(function() {
			$(this).addClass(activclass);
			$(this).siblings().removeClass(activclass);

		})
	})
}

/*单选框 选择同意与不同意*/
function radioChecked(name) {
	$("label[name='" + name + "']:not('[disable]')").click(function() {
		$("label[name='" + name + "']").removeClass("label-checked") && $(this).addClass("label-checked");
		$("label[name='" + name + "']").prev().removeAttr("checked") && $(this).prev().attr('checked', 'checked');
	});
}

/*获取现在的时间*/
function getNewTime() {
	var date = new Date();
	var seperator1 = "-";
	var year = date.getFullYear();
	var month = date.getMonth() + 1;
	var strDate = date.getDate();
	if(month >= 1 && month <= 9) {
		month = "0" + month;
	}
	if(strDate >= 0 && strDate <= 9) {
		strDate = "0" + strDate;
	}
	var currentdate = year + seperator1 + month + seperator1 + strDate;
	return currentdate;
}
// /*增加tr行*/
// function addrow() {
// 	var strBe = $("#addList tbody").html();
// 	var strNow = strBe + lastLine;
// 	$("#addList tbody").html(strNow);
// }
/*删除指定行*/
function minusrow(d) {
	$(d).parent().parent().parent("tr").remove();
}
/*点击添加按钮  添加意见行*/
function addOpinion(c,o){
	var h=$($(c).html())
	h.find('select option:first').prop("selected", 'selected');
	var nnn =$("<tr></tr>");
	h.appendTo(nnn);
	nnn.appendTo(o);
}
// function addOpinion(tableid) {
// 	var nnum;
// 	nnum++;
// 	var viweTem = '<td class="order-td-width02">意见</td>' +
// 		'<td>' +
// 		'<div class="">' +
// 		'<div class="position-r">' +
// 		'	<span class="prompt-text position-a fc-7">原因说明</span>' +
// 		'<textarea class="textarea-block bgc-2" rows="3" onkeyup="changePlacehoder(this)"></textarea>' +
// 		'<input type="text" style="display:none;">' +
// 		'</div>' +
// 		'<div class="dtable width-all">' +
// 		'<div class="dtcell">' +
// 		'<span>人事部：</span>' +
// 		'<div class="dsplin signature">' +
// 		'	<span class="signature-mask">自动签章</span>' +
// 		'<input type="" class="signature-input" onkeyup="changePlacehoder(this)" />' +
// 		'<input type="text" style="display:none;">' +
// 		'</div>'

// 	+'</div>' +
// 	'<div class="dtcell audit-person">' +
// 	'<div class="radiocheckitem  fs-16">' +
// 	'	<input type="radio" id="agree02" checked="checked" name="agreeyorn' + nnum + '" value="agree02">' +
// 		'<label name="agreeyorn' + nnum + '" class="label-checked label-agree" for="agree' + 02 + '">同意</label>' +
// 		'</div>' +
// 		'<div class="radiocheckitem fs-16">' +
// 		'<input type="radio" id="disagree' + nnum + '" name="agreeyorn' + nnum + '" value="disagree' + nnum + '">' +
// 		'<label class="label-agree" name="agreeyorn' + nnum + '" for="disagree02">不同意</label>' +
// 		'</div>' +
// 		'<div class="dsplin change-date-box fs-12 fc-2">' +
// 		'<span>日期：</span>' +
// 		'<input type="text" class="change-date"  />' +
// 		'</div>' +
// 		'</div>' +
// 		'</div>' +
// 		'</div>' +
// 		'</td>';

// 	var nnn = "<tr>" + viweTem + "</tr>";
// 	$(tableid).append(nnn);
// 	radioChecked("agreeyorn" + nnum);

// }