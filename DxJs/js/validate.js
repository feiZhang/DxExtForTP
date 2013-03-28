/**
 * jQuery validate 添加rangein, function验证方法. 扩展基础验证
 */

(function($) {
	window.rangein = function(field, rules, i, options) {
		var range = rules[i + 2];
		if ($.inArray(field.val(), range.split('|'))) {
			return "数据不合法!";
		}
	};
    
	if($.validationEngineLanguage == undefined || $.validationEngineLanguage.allRules == undefined )
		alert("Please include other-validations.js AFTER the translation file");
	else {
		//后台数据唯一性验证+(($('input[name=id]').size()>0):$('input[name=id]').val():"")
        $.validationEngineLanguage.allRules["checkFieldByUnique"] = {
                "ajaxmethod": "POST",
                "url": (URL_URL+"/checkFieldByUnique"),
                "extraData":{"id":((typeof id=='undefined')?"":id)},
        		"alertText": "此数据不可用!已存在!",
                "alertTextOk": "此数据有效!",
                "alertTextLoad": "正在验证数据!"
        };
        //后台函数验证
        $.validationEngineLanguage.allRules["checkFieldByFunction"] = {
                "ajaxmethod": "POST",
                "url": (URL_URL+"/checkFieldByFunction"),
                "alertText": "此数据不可用!",
                "alertTextLoad": "正在验证数据!"
        };
        $.validationEngineLanguage.allRules["dateTime"] = {
                "regex": /^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])\s+(00|2[0-3]|1[0-9]|0?[1-9]){1}:(0?[1-5]|[0-6][0-9]){1}:(0?[0-6]|[0-6][0-9]){1}$|^(?:(?:(?:0?[13578]|1[02])(\/|-)31)|(?:(?:0?[1,3-9]|1[0-2])(\/|-)(?:29|30)))(\/|-)(?:[1-9]\d\d\d|\d[1-9]\d\d|\d\d[1-9]\d|\d\d\d[1-9])$|^((1[012]|0?[1-9]){1}\/(0?[1-9]|[12][0-9]|3[01]){1}\/\d{2,4}\s+(00|2[0-3]|1[012]|0?[1-9]){1}:(0?[1-5]|[0-6][0-9]){1}:(0?[0-6]|[0-6][0-9]){1})$/,
                "alertText": "* 无效的日期或时间格式",
                "alertText2": "可接受的格式： ",
                "alertText3": "mm/dd/yyyy hh:mm:ss 或 ", 
                "alertText4": "yyyy-mm-dd hh:mm:ss"
        };
	}
})(jQuery);
