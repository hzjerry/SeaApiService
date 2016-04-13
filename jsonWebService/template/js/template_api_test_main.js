$(function(){
	var aBuf = new Array();
	for(var i=0,iLoop=moRunType.length; i<iLoop; i++){
		aBuf.push('<span class="label label-'+ moRunType[i].type +'" style="margin-right:10px;">'+ moRunType[i].msg +'</span>');
	}
	$('#id_run_type_info').html(aBuf.join(''));
	aBuf = new Array();
	for(var i=0,iLoop=maClientCfgList.length; i<iLoop; i++){
		if (msClientCfgKey == maClientCfgList[i].key){
			aBuf.push('<option value="'+ maClientCfgList[i].key +'" selected>'+ maClientCfgList[i].name +'</option>');
		}else{
			aBuf.push('<option value="'+ maClientCfgList[i].key +'">'+ maClientCfgList[i].name +'</option>');
		}
	}
	$('#id_client_cfg_list').html(aBuf.join(''));
	$('#id_txd').text(moTransmissionByte.txd);
	$('#id_rxd').text(moTransmissionByte.rxd);

	//初始化完成，启动title提示捕获
	$("[data-toggle='tooltip']").tooltip();
});