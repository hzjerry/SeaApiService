//构造初始化
$(function(){
	oRfelection.initPackagePath(); //初始化包访问路径
	oRfelection.initPackageList(); //初始化当前包路径下的包列表
	oRfelection.initClassList(); //初始化当前包下的类列表
	oRfelection.initClassInfo(); //初始化当前类文档说明

	//初始化完成，启动title提示捕获
	$("[data-toggle='tooltip']").tooltip();
})

var oRfelection = {
	/**
	 * 初始化包访问路径
	 */
	initPackagePath:function(){
		var aBuf = new Array();
		aBuf.push('<span class="nav_menu" data-toggle="tooltip" data-placement="bottom" title="return to root" onclick=javascript:window.location.href="?ctl=doc">API Root</span>');
		if (maPackagePath.length > 0){
			var aPathName = new Array();
			var sJumpUrl = '', sTitle = '';
			for(var i=0, iLoop=maPackagePath.length; i<iLoop; i++){
				aPathName.push(maPackagePath[i]);
				if (i < iLoop-1){
					sJumpUrl = ' onclick=javascript:window.location.href="?ctl=doc&p=' + aPathName.join('.') +'"';
					sTitle = ' title="to '+ aPathName.join('.') +'"';
				}else{
					sJumpUrl = '';
					sTitle = ' title="'+ aPathName.join('.') +'"';
				}
				aBuf.push('<span class="nav_menu" data-toggle="tooltip" data-placement="bottom"'+ sTitle + sJumpUrl +'>'+ maPackagePath[i] +'</span>');
			}
		}
		$('#id_subtitle').html(aBuf.join('<span class="nav_separator"></span>'));
	},
	/**
	 * 初始化当前包路径下的包列表
	 */
	initPackageList:function(){
		var aBuf = new Array(), sActive='';
		if (maPakageList.length > 0){
			var sTitle = '', aNewPath=new Array();
			aBuf.push('<ul>');
			for(var i=0, iLoop=maPakageList.length; i<iLoop; i++){
				sTitle = ' data-toggle="tooltip" data-placement="top" title="'+ maPakageList[i].memo +'"';
				aNewPath = maPackagePath.concat([maPakageList[i].name]);
				aBuf.push('<li' + sTitle +'><a href="?ctl=doc&p=' + aNewPath.join('.') +'">'+ maPakageList[i].name +'</a></li>');
			}
			aBuf.push('</ul>');
			$('#id_package_list_item').html(aBuf.join(''));
			$('#id_package_list_area').removeClass('hide');
		}
	},
	/**
	 * 初始化当前包下的类列表
	 */
	initClassList:function(){
		var aBuf = new Array(), sActive='';
		if (maClssList.length > 0){
			aBuf.push('<ul>');
			for(var i=0, iLoop=maClssList.length; i<iLoop; i++){
				sActive = ((msClassName === maClssList[i].name)? ' class="selected"' : '');
				aBuf.push('<li'+ sActive +' data-toggle="tooltip" data-placement="right" title="'+ maClssList[i].memo +'">');
				aBuf.push('<a href="?ctl=doc&p=' + maPackagePath.join('.') +'&c='+ maClssList[i].name +'">'+ maClssList[i].name +'</a>');
				aBuf.push('</li>');
			}
			aBuf.push('</ul>');
			$('#id_class_list_item').html(aBuf.join(''));
			$('#id_class_list_area').removeClass('hide');
		}
	},
	/**
	 * 初始化当前类文档说明
	 */
	initClassInfo:function(){
		var aBuf = new Array();
		if (false !== moClassInfo){ //找到接口文档信息
			//API介绍信息

			aBuf = new Array();
			aBuf.push('<dl class="dl-horizontal">');
			aBuf.push('<dt>接&nbsp;&nbsp;口&nbsp;&nbsp;名</dt>');
			aBuf.push('<dd>'+ msClassName +'</dd>');
			aBuf.push('</dl>');

			aBuf.push('<dl class="dl-horizontal">');
			aBuf.push('<dt>功能说明</dt>');
			aBuf.push('<dd>'+ moClassInfo.class_explain +'</dd>');
			aBuf.push('</dl>');

			aBuf.push('<dl class="dl-horizontal">');
			aBuf.push('<dt>使用注意事项</dt>');
			aBuf.push('<dd>'+ moClassInfo.attention_explain +'</dd>');
			aBuf.push('</dl>');

			aBuf.push('<dl class="dl-horizontal">');
			aBuf.push('<dt>访问日志</dt>');
			if ('Y' === moClassInfo.do_not_wirte_log){
				aBuf.push('<dd><span class="label label-info">不写访问日志</span></dd>');
			}else{
				aBuf.push('<dd><span class="label label-danger">记录访问日志</span></dd>');
			}
			aBuf.push('</dl>');

			aBuf.push('<dl class="dl-horizontal">');
			aBuf.push('<dt>接口失效时间</dt>');
			if ('Never expires' === moClassInfo.dead_line){
				aBuf.push('<dd><span class="label label-info">永不失效</span></dd>');
			}else{
				aBuf.push('<dd><span class="label label-danger">'+ moClassInfo.dead_line + '</span></dd>');
			}
			aBuf.push('</dl>');

			aBuf.push('<dl class="dl-horizontal">');
			aBuf.push('<dt>Token验证规则</dt>');
			if ('Y' === moClassInfo.token_security_check){
				aBuf.push('<dd><span class="label label-info">已开启验证</span></dd>');
			}else{
				aBuf.push('<dd><span class="label label-warning">未开启验证</span></dd>');
			}
			aBuf.push('</dl>');
			aBuf.push('<dl class="dl-horizontal">');
			aBuf.push('<dt>接口指纹</dt>');
				aBuf.push('<dd><span class="label label-default" style="letter-spacing:3px;">'+ moClassInfo.fingerprint +'</span></dd>');
			aBuf.push('</dl>');

			//$('#id_api_introduce').html('<div class="well">' + aBuf.join('</div><div class="well">') +'</div>');
			$('#id_api_introduce').html(aBuf.join(''));
			//入口参数约定
			$('#id_inport_param').html(moClassInfo.in_protocol_format);
			//入口参数约定
			$('#id_outport_param').html(moClassInfo.out_protocol_format);
			//返回状态值
			aBuf = new Array();
			for(var sKey in moClassInfo.api_status_code){ //api接口应用状态参数返回值
				aBuf.push(sKey +' &rArr; '+ moClassInfo.api_status_code[sKey]);
			}
			aBuf.push('--------------------------------------------------------------------------------');
			for(var sKey in moClassInfo.sys_status_code){ //系统状态参数返回值
				aBuf.push(sKey +' &rArr; '+ moClassInfo.sys_status_code[sKey]);
			}
			$('#id_result_code_list').html(aBuf.join('<br/>'));
			//接口维护记录
			aBuf = new Array();
			for(var i=0, iLoop=moClassInfo.update_log.length; i<iLoop; i++){
				var aTmp = new Array();
				aTmp.push(moClassInfo.update_log[i].date + ' : ');
				aTmp.push('['+ moClassInfo.update_log[i].name + '] ');
				aTmp.push(moClassInfo.update_log[i].memo);
				aBuf.push(aTmp.join(''));
			}
			$('#id_maintenance_record').html(aBuf.join('<br/>'));

			$('#id_class_info_doc').removeClass('hide');
		}else{
			$('#id_class_info_notic').removeClass('hide');
		}
	},
};