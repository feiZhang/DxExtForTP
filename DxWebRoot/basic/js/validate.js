/**
 * jQuery validate 添加rangein, function验证方法. 扩展基础验证
 */

(function($) {
    if($.validationEngineLanguage == undefined || $.validationEngineLanguage.allRules == undefined )
        alert("Please include other-validations.js AFTER the translation file");
    else {
        //函数回调验证
        $.validationEngineLanguage.allRules["remoteValidataField"] = {
            "ajaxmethod": "POST",
            "url": APP_URL +"/Basic/remoteValidataField",
            "extraDataDynamic":"#pkId,#modelName",
            "alertText": "此数据不可用!",
            "alertTextOk": "此数据有效!",
            "alertTextLoad": "正在验证数据!"
        };

        $.validationEngineLanguage.allRules["rangein"] = {
                    "func": function(field, rules, i, options) {
                        var range = rules[i + 2];
                        if ($.inArray(field.val(), range.split('|'))) {
                            return "数据不合法!";
                        }
                    },
                    "alertText": "* Field must equal test"
        };

        $.validationEngineLanguage.allRules["noQuanJiao"] = {
            "regex": /^\S$/i,
            "alertText": "* 请不要输入全角字符!"
        };

        $.validationEngineLanguage.allRules["regex"] = {
                    "func": function(field, rules, i, options) {
                        var ex = field.attr("regex");
                        if(ex==undefined || ex==0) return true;
                        ex = ex.substr(1,ex.length-2); //这个正则为后台格式，前端需要去掉首尾的/符号
                        var pattern = new RegExp(ex);
                        if (!pattern.test(field.val())){
                            return false;
                        }
                        return true;
                    },
                    "alertText": "数据格式不正确！"
        };

    }
})(jQuery);
