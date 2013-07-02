function getLodop(oOBJECT,oEMBED){
/**************************
  本函数根据浏览器类型决定采用哪个对象作为控件实例：
  IE系列、IE内核系列的浏览器采用oOBJECT，
  其它浏览器(Firefox系列、Chrome系列、Opera系列、Safari系列等)采用oEMBED。
**************************/
        var strHtml1	= "<br><font color='#FF00FF'>打印控件未安装!点击这里<a href='"+PUBLIC_URL+"/public/Lodop6.010/install_lodop.exe'>执行安装</a>,安装后请刷新页面或重新进入。</font>";
        var strHtml2	= "<br><font color='#FF00FF'>打印控件需要升级!点击这里<a href='"+PUBLIC_URL+"/public/Lodop6.010/install_lodop.exe'>执行升级</a>,升级后请重新进入。</font>";
        var strHtml3	= "<br><br><font color='#FF00FF'>(注：如曾安装过Lodop旧版附件npActiveXPLugin,请在【工具】->【附加组件】->【扩展】中先卸载它)</font>";
        var LODOP		= oEMBED;
        var msg 		= "";
	try{		     
	     if (navigator.appVersion.indexOf("MSIE")>=0) LODOP=oOBJECT;
	     if ((LODOP==null)||(typeof(LODOP.VERSION)=="undefined")) {
			 if (navigator.userAgent.indexOf('Firefox')>=0)
				 msg 	 = strHtml3;
				 //    document.documentElement.innerHTML=strHtml3+document.documentElement.innerHTML;
			 if (navigator.appVersion.indexOf("MSIE")>=0) 
				 msg 	 = strHtml1;
				 // document.write(strHtml1);
			 else
				 msg 	 = strHtml1;
			//	 document.documentElement.innerHTML=strHtml1+document.documentElement.innerHTML;
	     } else if (LODOP.VERSION<"6.0.1.0") {
			 if (navigator.appVersion.indexOf("MSIE")>=0) 
				 msg 	 = strHtml2;
				 //	 document.write(strHtml2); 
			 else
				 msg 	 = strHtml2;
			//	 document.documentElement.innerHTML=strHtml2+document.documentElement.innerHTML; 
	     }
	     if(msg!=""){
	    	  $.dialog({title:'提示',content:msg,esc:true,lock:true});
	  	 }
	   //showDialog('提示',msg);
	     //*****如下空白位置适合调用统一功能:*********	     
		 LODOP.SET_LICENSES("郑州大象通信信息技术有限公司","864567677838688778794958093190","","");

	     //*******************************************
	     return LODOP; 
	} 
	catch(err){
			msg 	 = strHtml1;
	//	    $.dialog({title:'提示',content:msg,esc:true,lock:true});
		//	document.documentElement.innerHTML=""+strHtml1+document.documentElement.innerHTML;
	    return LODOP; 
	}
}
