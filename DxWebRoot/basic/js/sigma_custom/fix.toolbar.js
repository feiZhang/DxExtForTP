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
Sigma.GridDefault.showCellToolTip	= function(cell,width){
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
