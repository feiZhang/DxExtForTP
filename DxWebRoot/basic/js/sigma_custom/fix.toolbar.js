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

/**
 * 重写 gird 的print方法。使用lodop实现打印功能。
 */
Sigma.GridDefault.printGrid = function()
{
    var grid=this ,docT;
    //关闭菜单、显示等待
    grid.closeGridMenu();
    grid.showWaiting();
    //初始化打印，纸张大小等
    LODOP=getLodop(document.getElementById('LODOP_OB'),document.getElementById('LODOP_EM'));  
    LODOP.SET_PRINT_PAPER(40, 0, 800, 734, "");
    LODOP.SET_PRINT_PAGESIZE(2, 0, 0, 'A4');
    $.ajax({
        type : "GET",
        url : URL_URL + "/get_datalist",
        data : $("#itemAddForm").serialize(),
        success : function(data) {
            var data_list = data['data'];
            var fields = data['fields'];
            var table = document.createElement("table");
            var row = table.insertRow();//创建一行 
            for ( var i in fields) {
                var cell = row.insertCell();//创建一个单元
                cell.innerHTML = fields[i].title;
            }
            for ( var i in data_list) {
                var row = table.insertRow();//创建一行 
                var item = data_list[i];
                for ( var j in item) {
                    var cell = row.insertCell();//创建一个单元
                    for ( var a in fields) {
                        var change = fields[a];
                        if (null != change.valChange) {
                            var valchange = change.valChange;
                            if (valchange[item[j]] != null)
                                item[j] = valchange[item[j]];
                        }
                    }
                    cell.innerHTML = item[j];
                }
            }
            LODOP.PRINT_INIT("大象通信");
            LODOP.ADD_PRINT_TABLE('0', '0', '90%', '989', table.outerHTML);
            LODOP.PREVIEW(); //预览打印
        },
        dataType : "json"
    });

    Sigma.$thread(    function(){
        grid.hideWaiting();
    },1000);
}
