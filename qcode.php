
{include file="page_header"}

		  <script type="text/javascript">


        // var jsondata=$.parseJSON(this.data);
				$(function(){
						var url = window.location.href;
						var jsConfig = {
								debug: false,
								jsApiList: [
										'onMenuShareTimeline',
										'onMenuShareAppMessage',
										'onMenuShareQQ',
										'onMenuShareWeibo',
										'onMenuShareQZone'
								]
						};
						$.post('{url("wechat/jssdk/index")}', {url: url}, function (res) {
								if(res.status == 200){
										jsConfig.appId = res.data.appId;
										jsConfig.timestamp = res.data.timestamp;
										jsConfig.nonceStr = res.data.nonceStr;
										jsConfig.signature = res.data.signature;
										// 配置注入
										wx.config({

										 // 必填，公众号的唯一标识
										 appId: jsConfig.appId,
										 // 必填，生成签名的时间戳
										 timestamp: jsConfig.timestamp,
										 // 必填，生成签名的随机串
										 nonceStr:jsConfig.nonceStr,
										 // 必填，签名
										 signature: jsConfig.signature,
										 // 必填，需要使用的JS接口列表
										 jsApiList: ['checkJsApi', 'scanQRCode']


					// 必填，需要使用的JS接口列表
										 });

										 wx.error(function (res) {
											 alert("出错了：" + res.errMsg);//这个地方的好处就是wx.config配置错误，会弹出窗口哪里错误，然后根据微信文档查询即可。
									 });



										// 事件注入

										wx.ready( setTimeout(function () {
										 wx.checkJsApi({
												 jsApiList: ['scanQRCode'],
												 success: function (res) {

												 }
										 });
												 //点击按钮扫描二维码
									 // document.querySelector('#scanQRCode').onclick = function () {
													wx.scanQRCode({
													 needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
													 scanType: ["qrCode"], // 可以指定扫二维码还是一维码，默认二者都有
													 success: function (res) {
															 var result = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
															 // alert("扫描结果："+result);


															 $.ajax({
																 type: "POST",
																 url: "{url('user/account/getqrcoupon')}",
																 data: {result :result},
																 dataType: "json",
																 success: function(res){
																	var res = JSON.parse(res);
																		 var result = res.resultStr;
																		if(res.message=='1'){


																			// alert(result)

																			// return

																		if(window.confirm('你确定使用优惠劵吗？')){

													                 			 $.ajax({
																		            type: "POST",
																					 url: "{url('user/account/getqrcoupon')}",
																					 data: {result :result,'dete':1},
																					 dataType: "json",
																					 success:function(data){

																					 	var data = JSON.parse(data);

																					 	alert(data.message);
																					 }
													                 			})



																            }


																		}else{
																			alert(res.message);
																		}
																	}
															 });
															 // window.location.href = result;//因为我这边是扫描后有个链接，然后跳转到该页面
													 }
											 });
									 // };
								}),1500);

								}
						}, 'json');
				})







		     </script>


</body>


</html>
