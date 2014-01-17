/* 
 * 修正sigmagrid只有一条数据时显示"NO_DATA"提示.
 */

Sigma.GridDefault.refreshToolBar = function(pageInfo,doCount){
        pageInfo && ( this.setPageInfo(pageInfo) );
        if (this.over_initToolbar){
                this.navigator.refreshState(pageInfo,doCount);
                this.navigator.refreshNavBar();
                var pageInput= this.navigator.pageInput;
                if (this.pageStateBar){
                        pageInfo=this.getPageInfo();
                        //this.pageStateBar.innerHTML="";
                        Sigma.U.removeNode(this.pageStateBar.firstChild);
                        //this line to fix prompt when only one record;
                        //修正sigmagrid只有一条数据时显示"NO_DATA"提示.
                        if (pageInfo.endRowNum-pageInfo.startRowNum<0) {
                                this.pageStateBar.innerHTML= '<div>'+this.getMsg('NO_DATA')+'</div>';
                        }else{
                                this.pageStateBar.innerHTML= '<div>'+Sigma.$msg( this.getMsg( pageInput?'PAGE_STATE':'PAGE_STATE_FULL') ,
                                        pageInfo.startRowNum,pageInfo.endRowNum,pageInfo.totalPageNum,pageInfo.totalRowNum , pageInfo.pageNum )+'</div>';
                        }
                }
        }

}

/**
 * 1.将显示text修改为显示innerHTML
 * */
Sigma.GridDefault.showCellToolTip    = function(cell,width){
    if (!this.toolTipDiv) {
        this.toolTipDiv=Sigma.$e("div",{className : 'gt-cell-tooltip gt-breakline'});
        this.toolTipDiv.style.display="none";
    }
    this.toolTipDiv.innerHTML=$(cell).find("div.gt-inner").html();
    this.gridDiv.appendChild(this.toolTipDiv);

    this.toolTipDiv.style.left=cell.offsetLeft+ this.bodyDiv.offsetLeft- this.bodyDiv.scrollLeft + ((Sigma.isFF2 || Sigma.isFF1)?0:this.tableMarginLeft)  + 'px';
    this.toolTipDiv.style.top=cell.offsetTop+cell.offsetHeight + this.bodyDiv.offsetTop- this.bodyDiv.scrollTop+ this.toolBarTopHeight+(Sigma.isFF?1:0) +'px';
    width && (this.toolTipDiv.style.width=width +'px');
    this.toolTipDiv.style.display="block";
}

/**
 * 屏蔽数据正在加载时，切换页面，提示的错误信息
 */
Sigma.GridDefault.loadFailure   = function(respD,e){
    //var msg=respD[this.CONST.exception]|| (e?e.message:undefined);
    //alert(' LOAD Failed! '+'\n Exception : \n'+ msg );
}

//ie9下不支持  goto生成，本质不支持：gt_base.js   234行 el = Sigma.doc.createElement(el);  的写法 ，，el为字符串   <input type="text"》
//原始代码中的元素位：$element ,，后来调用的代码使用的是 $e
Sigma.$extend(Sigma , {
    $e : function(el,props){
        if (Sigma.$type(el,'string') ){
            if (Sigma.isIE && props && (props.name || props.type)){
                el = Sigma.doc.createElement(el);
                if(props.name) el.name = props.name;
                if(props.type) el.type = props.type;
                delete props.name;
                delete props.type;
            }else{
                el = Sigma.doc.createElement(el);
            }
        }
        if (props){
            if (props.style){
                Sigma.$extend(el.style,props.style);
                delete props.style;
            }
            Sigma.$extend(el,props);
        }
        return el;
    }
});

/**
 * 重写 gird 的print方法。使用lodop实现打印功能。
 */
Sigma.GridDefault.printGrid = function()
{
    var theGrid=this;
    if(this.navigator && this.navigator.pageInfo && this.navigator.pageInfo.totalRowNum && this.navigator.pageInfo.totalRowNum > 500){
        $.dialog.confirm("要打印的数据为" + this.navigator.pageInfo.totalRowNum + "条，确定要打印！<br \>",function(){printGrid(theGrid)},function(){});
    }else{
        printGrid(theGrid);
    }

    function printGrid(grid){
    //初始化打印，纸张大小等
    var LODOP=getLodop(document.getElementById('LODOP_OB'),document.getElementById('LODOP_EM'));
    if(LODOP==false){
        return false;
    }

    //关闭菜单、显示等待
    grid.closeGridMenu();
    grid.showWaiting();
    LODOP.SET_PRINT_PAPER(40, 0, 800, 734, "");
    LODOP.SET_PRINT_PAGESIZE(2, 0, 0, 'A4');

    $.ajax({
        type : "GET",
        url : URL_URL + "/get_datalist?print=1",
        data : $("#itemAddForm").serialize(),
        success : function(data) {
            var data_list = data['data'];
            var fields = data['fields'];
            var tableHtml = "<table>";
            var tempArray = new Array();
            for ( var i in fields) {
                if(fields[i].width>1000) fields[i].width=200;
                tempArray.push("<th width=\"" + fields[i].width + "px\">" + fields[i].title + "</th>");
            }
            tableHtml += "<tr>" + tempArray.join("") + "</tr>";
            var oneLine = "";
            for ( var i in data_list) {
                tempArray = [];
                oneLine = data_list[i];
                for ( var j in oneLine) {
                    var change = fields[j];
                    if (null != change.valChange) {
                        var valchange = change.valChange;
                        tempArray.push("<td>" + valchange[oneLine[j]] + "</td>");
                    }else{
                        tempArray.push("<td>" + oneLine[j] + "</td>");
                    }
                }
                tableHtml += "<tr>" + tempArray.join("") + "</tr>";
            }
            tableHtml += "</table>";
            //console.log(data);
            //console.log(fields);
            //console.log(data_list);
            //console.log(tableHtml);

            LODOP.PRINT_INIT("大象通信");
            LODOP.ADD_PRINT_TABLE('0', '0', '90%', '989', tableHtml);
            LODOP.PREVIEW(); //预览打印
        },
        dataType : "json"
    });

    Sigma.$thread(function(){
        grid.hideWaiting();
    },1000);
    }
}
