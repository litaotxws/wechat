<?php
set_time_limit(0);
//require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "../LTphp.php";
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "wx2_class.php";
//use Common\{myfunction,Db,myRedis,wx2,weixin};
//use Config\{myConfig,myredisConfig,mysqlConfig,weixinConfig};
//$check=new myfunction(myConfig::load());
//$weixin=new weixin(weixinConfig::load());
//$sql=new DB(mysqlConfig::load());
//$redis=new myRedis(myredisConfig::load());
$wx=new wx2();
$info=$_POST['info']??null;
$id=$_GET['id']??null;
$uuid=$_POST['uuid']??null;
$statusinfo=$_POST['statusinfo']??null;
$sendText=$_POST['sendText']??null;
if($info=='wxcode'){
	if(!empty($uuid)){
		if(empty($_SESSION['wxinfo'])){
			$loginInfo = $wx->login($uuid);
			if (!empty($loginInfo['code']) && $loginInfo['code'] == 200) {
				//获取登录成功回调
				$callback = $wx->get_uri($uuid);
				//获取post数据
				$post = $wx->post_self($callback);
				//初始化数据json格式
				$initInfo = $wx->wxinit($post);
				//获取MsgId,参数post，初始化数据initInfo
				//$msgInfo = $wx->wxstatusnotify($post,$initInfo,$callback['post_url_header']);
				//获取联系人
				$contactInfo = $wx->webwxgetcontact($post, $callback['post_url_header']);
				$word = urlencode($sendText);
				$contactArr = json_decode($contactInfo, true);
				$contactName = $wx->anewarray($contactArr['MemberList'], 'UserName');	
				//获取群信息
				$batchcontactArr=json_decode($initInfo,true);
				$batchcontactName=$wx->anewarray($batchcontactArr['ContactList']);
				$contactName=array_merge($contactName,$batchcontactName);	
				if($statusinfo==1){
					$_SESSION['wxinfo']=array('post'=>$post,'initInfo'=>json_decode($initInfo,true),'post_url_header'=>$callback['post_url_header']);
				}	
				if(!empty($sendText)){
					foreach($contactName as $k=>$v){
						$info = $wx->webwxsendmsg($post, $initInfo, $callback['post_url_header'], $v, $word);	
						usleep(mt_rand(100000,500000));
						$res[]=$info;	
					}
					$status=0;
					$msg=$res;

				}else{
					$synccheck=$wx->synccheck($_SESSION['wxinfo']['post'], $_SESSION['wxinfo']['initInfo']['SyncKey']);
					$status=1;
					if($synccheck['ret']==0){
						$msg='缺少发送内容,再次点击确认将进入机器人模式!';
					}else{
						$msg=$synccheck;
					}
				}
			}
			else{
				$status=408;
				$msg='用户未登录!';
			}
		}else{
		/*
		$filepath=dirname(__FILE__) . DIRECTORY_SEPARATOR . "taobao.txt";
				$filepath1=dirname(__FILE__) . DIRECTORY_SEPARATOR . "taobaoimg/";
				$datainfos=json_decode(file_get_contents($filepath),true);
				foreach($datainfos as $k=>$v){
				$infos=$wx->webwxuploadimg($_SESSION['wxinfo']['post'],$filepath1.$v['pic']);
				$info = $wx->webwxsendimg($_SESSION['wxinfo']['post'], $_SESSION['wxinfo']['initInfo'], $_SESSION['wxinfo']['post_url_header'], 'filehelper',$infos['MediaId']);
				$info1 = $wx->webwxsendmsg($_SESSION['wxinfo']['post'], $_SESSION['wxinfo']['initInfo'], $_SESSION['wxinfo']['post_url_header'], 'filehelper',urlencode($v['tkl']));
				$info=json_decode($info,true);
				$info1=json_decode($info1,true);
				$msg['img'][]=$info;$msg['msg'][]=$info1;
				if($info['BaseResponse']['Ret']!=0 || $info1['BaseResponse']['Ret']!=0){
					unset($_SESSION['wxinfo']);
					break;
				}
					usleep(mt_rand(3000000,7000000));
				
				}
				$status=1;*/

				$synccheck=$wx->synccheck($_SESSION['wxinfo']['post'],$_SESSION['wxinfo']['SyncKey']??$_SESSION['wxinfo']['initInfo']['SyncKey']);
				do{

					if($synccheck['ret']==0 && $synccheck['sel']!=7){
						$msginfo=$wx->webwxsync($_SESSION['wxinfo']['post'],$_SESSION['wxinfo']['post_url_header'],$_SESSION['wxinfo']['SyncKey']??$_SESSION['wxinfo']['initInfo']['SyncKey']);
						if($msginfo['BaseResponse']['Ret']!=0){
							unset($_SESSION['wxinfo']);unset($_SESSION['wxcookie']);
							$status=7;$msg='获取新消息出错,请重新登录!';
						}else{
							if(isset($msginfo['SyncKey']['Count'])){
								$_SESSION['wxinfo']['SyncKey']=$msginfo['SyncKey'];
							}
							if($msginfo['AddMsgCount']>0){

								foreach($msginfo['AddMsgList'] as $k=>$v){
									if($v['ToUserName']=='filehelper'){
										if(!empty($v['Content'])){
											if(preg_match('/^robot:/', urldecode($v['Content']))){
												$msg=str_replace('robot:','',urldecode($v['Content']));
												if($msg=='exit'){
													$info=$wx->webwxsendmsg($_SESSION['wxinfo']['post'], $_SESSION['wxinfo']['initInfo'], $_SESSION['wxinfo']['post_url_header'], $v['ToUserName'],urlencode('已中止自动回复!'));
													$info=$wx->wxloginout($_SESSION['wxinfo']['post'],$_SESSION['wxinfo']['post_url_header']);
													$status=11;$msg=$info;
												}
												if($msg=='stop'){
													$info=$wx->webwxsendmsg($_SESSION['wxinfo']['post'], $_SESSION['wxinfo']['initInfo'], $_SESSION['wxinfo']['post_url_header'], $v['ToUserName'],urlencode('已停止自动回复!'));
													$status=22;$msg=$info;
													break 2;
												}
												else{
													$info=$wx->webwxsendmsg($_SESSION['wxinfo']['post'], $_SESSION['wxinfo']['initInfo'], $_SESSION['wxinfo']['post_url_header'], $v['ToUserName'],urlencode('指令'.$msg.'有误!'));
													$status=13;$msg=$info;
										//break 2;
												}
											}else{
											//	$status=998;$msg=$msginfo;
												break;
											}
										}else{
										//	$status=996;$msg=$msginfo;
											break;
										}
									}else{
										//$status=998;$msg=$msginfo;
										break;
									}
								}	
								//$status=999;$msg=$msginfo;
							}
								//else{$status=969;$msg=$msginfo;}
						}	
						//	$status=123;$msg=$msginfo;
					}else{
						if($synccheck['ret']==0){
							if($synccheck['sel']==7){
								unset($_SESSION['wxinfo']);unset($_SESSION['wxcookie']);
								$status=5;$msg=$synccheck;
								break;
							}else{
								$msginfo=$wx->webwxsync($_SESSION['wxinfo']['post'],$_SESSION['wxinfo']['post_url_header'],$_SESSION['wxinfo']['SyncKey']??$_SESSION['wxinfo']['initInfo']['SyncKey']);
								if(isset($msginfo['SyncKey']['Count'])){
									$_SESSION['wxinfo']['SyncKey']=$msginfo['SyncKey'];
								}
				//	$status=4;$msg=$msginfo;	
							}
						}else{
							unset($_SESSION['wxinfo']);unset($_SESSION['wxcookie']);
							$status=3;$msg=$synccheck;
							break;
						}

					}
					ob_flush();flush(); 
		sleep(27);//等一会儿
		$synccheck=$wx->synccheck($_SESSION['wxinfo']['post'],$_SESSION['wxinfo']['SyncKey']??$_SESSION['wxinfo']['initInfo']['SyncKey']);//再次执行查询

	}while($synccheck['ret']==0);

	if($synccheck['ret']!=0){
		unset($_SESSION['wxinfo']);unset($_SESSION['wxcookie']);
		$status=3;$msg='故障代码：'.$synccheck['ret'].',请重新登录';
	}	
}
}else{
	$status=1;$msg='未获取到uuid,请刷新重试!';				
}
echo json_encode(array('status'=>$status,'msg'=>$msg));
}
?>