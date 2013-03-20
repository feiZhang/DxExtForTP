/**
 * 封装sigma grid，根据ThinkPHP的后台Model定义，自动生成前台界面
 * **/
(function($){
	$.dxGrid	= function(){
		var _this				= this;
		var orginDataUrl		= "";		//loadUrl的原始值，在改变查询条件后，url会变化，需要使用原始值生成新的url
		var baseURL				= "";		//action的URL

		/*
		 * 生成Grid展示字段列表，包含字段类型。
		 * 1.设置默认数据
		 * 2.填充字段列表
		 */
		_this.mygrid			= null;
		_this.grid_id			= "theDataOpeGrid";
		_this.parentGridDiv	= "dataListCon";	//grid容器的父容器，因为刷新grid时，要摧毁grid容器，所以需要在父容器中重新创建容器
		_this.dsOption		= {
								fields:[],
								data : [],
								uniqueField : 'id',
								recordType : 'object'
								};
		_this.colsOption		= [];
		_this.gridOption		= {
								id 		: _this.grid_id,
							    width	: "100%",
							    height	: "320",
							    minHeight:'150',
							    loadURL	:	"",
							    container : 'dataList',
							    replaceContainer 	: false, 
							    toolbarContent 		: 'print | xls | state',
							    pageSize 			: 20,
							    loadURL				: "",
							    exportURL			: "",
								remotePaging 		: true,
							  	pageSizeList 		: [100,1000,10000],
							  	showIndexColumn 	: true,
							  	autoLoad 			: true,
							  	selectRowByCheck	: true,
							  	showGridMenu 		: false,//sigmaGrid2添加主菜单
							  	allowCustomSkin		: false,//主菜单添加换肤功能
								allowFreeze			: true,//主菜单添加lock某些列功能
								allowHide			: true,//主菜单添加hide某些列功能
								allowGroup			: true, //主菜单添加group某些列功能
							    onMouseOver : function(value,  record,  cell,  row,  colNo, rowNo,  columnObj,  grid){
							        if (columnObj && columnObj.toolTip) {
							            grid.showCellToolTip(cell,null);
							        }else{
							            //grid.hideCellToolTip();
							        }
							    },
							    onRowClick : function(value,  record,  cell,  row,  colNo, rowNo,  columnObj,  grid){
							        grid.hideCellToolTip();
							    }
							};
		//init
		_this.init	= function(gridArgs){
			if(gridArgs.enablePage=="1"){
				//ie9下不支持  goto生成，本质不支持：gt_base.js   234行 el = Sigma.doc.createElement(el);  的写法 ，，el为字符串   <input type="text"》
				_this.gridOption.toolbarContent	= "nav | goto | pagesize | " + _this.gridOption.toolbarContent;
			}else{
				_this.gridOption.pageSize		= 10000;
			}
			_this.gridOption.columns		= _this.colsOption;
			_this.gridOption.dataset		= _this.dsOption;

			_this.setGridContainer(gridArgs.gridDiv,gridArgs.parentGridDiv);
			_this.setGridFields(gridArgs.gridFields);
			_this.setDataSetFields(gridArgs.datasetFields);
			_this.setData(gridArgs.loadUrl);
		}
		_this.setBaseURL		= function(baseURL){
			_this.baseURL				= baseURL;
			_this.gridOption.loadURL	= baseURL+"/get_datalist";
            _this.orginDataUrl			= baseURL+"/get_datalist";
            _this.gridOption.exportURL	= _this.urladd(_this.gridOption.loadURL,"export=xls");
		};
		_this.setOrginURL		= function(orginURL){
            _this.orginDataUrl			= orginURL;
		};
		_this.setGridContainer	= function(gridDiv,parentGridDiv){
			_this.gridOption.container	= gridDiv;
			_this.parentGridDiv			= parentGridDiv;
		};
		_this.setDataSetFields	= function(dataFields){
			_this.gridOption.dataset.fields	= _this.gridOption.dataset.fields.concat(dataFields);
		};
		_this.setGridFields		= function(gridFields){
			$.each(gridFields,function(id,o){
					if(o.renderer != undefined){
						eval(o.renderer);
						o.renderer	= valChange;
					}
					if(o.width > 1000){
						o.toolTip	= true;
						o.width		= 150;
					}
				});
			_this.gridOption.columns	= _this.gridOption.columns.concat(gridFields);
		};

		_this.setData			= function(data){
			if(typeof data == 'object')
				_this.dsOption.data		= data;
			else if(typeof data == 'string'){
				_this.gridOption.loadURL	= data;
                _this.orginDataUrl			= data;
                _this.gridOption.exportURL	= _this.urladd(data,"export=xls");
            }
		};
		_this.showGrid			= function(excludeHeight){
			var gridOption	= _this.gridOption;
			//如果有头容器，则使用自动以头
			if($("#gridHeader").html().length>100) gridOption.customHead	= "gridHeader";
			
			//自动计算grid的合适高度
            var max	= $(window).height();
            //计算exclude 中指定元素的高度
            Sigma.$each(excludeHeight, function(idn){
            	if (idn.constructor == Number) {
            		max	= max - idn;
            		//console.log(idn);
            	}else{
                    if(Sigma.$(idn)!=null){
                        max		= max - Sigma.U.getHeight(Sigma.$(idn));
                        //console.log(Sigma.U.getHeight(Sigma.$(idn)));
                    }
            	}
            });
            if($.browser.msie && ($.browser.version=="6.0")){
            	max		= max - 20;
            }
            gridOption.height	= max-7;
            //根据高度，自动计算grid的合适行数，，如果已经设定不分页显示，则不再计算每页行数
            if(_this.gridOption.pageSize < 9000){
            	var pageSize	= (gridOption.height-50)/23;
	            if(pageSize>gridOption.pageSize) gridOption.pageSize = parseInt(pageSize);
	            gridOption.pageSizeList.unshift(gridOption.pageSize);
            }
            
            _this.mygrid	= new Sigma.Grid( gridOption );
			_this.mygrid.render();
		};
		//查询数据
        _this.query				= function(para){
            var loadUrl		= _this.orginDataUrl;
            var exportUrl	= loadUrl;
            loadUrl			= _this.urladd(loadUrl,para);
            exportUrl		= _this.urladd(exportUrl,para+"&export=xls");
    		_this.mygrid.loadURL	= loadUrl;
    		_this.mygrid.exportURL	= exportUrl;
    		_this.mygrid.reload();
        };
        _this.urladd	= function(url,para){
        	if(para=="" || para==undefined){
        	}else{
	            if(url.indexOf('?')>-1){
	                url		+= "&"+para;
	            }else{
	                url		+= "?"+para;
	            }
        	}
        	return url;
        }
        _this.reload	= function(){
        	_this.mygrid.reload();
        }
        //grid全刷新，，在列信息改变的情况下，刷新。
		_this.refreshGrid			= function(){
//	    	colsOption.push({id: 'id' , header: "ID" , width :100});
	    	_this.mygrid.destroy();
	    	$("#"+_this.parentGridDiv).append("<div id=\"" + _this.gridOption.container + "\"></div>");
	    	_this.mygrid	= new Sigma.Grid( _this.gridOption );
	    	_this.mygrid.render("dataList");
		};
	}
})(jQuery);
/*
 * 生成Grid展示字段处理规则
 * 功能:字典表数据转换、数据列默认冻结、Html数据格式转换、默认的数据操作（修改、删除、改变状态）
 * 参数:删除数据的URL、修改数据的URL、状态转换的URL
 * 备注:
 * 		1.改变状态有 下拉、Radio列表两种格式
 */