<?php

namespace Storetransform\OrderSplit\Model\Tracking;


class Auexpress 
{
	public $title='aueapi.auexpress.com';
    /**
     *
     * @param string  
     */
	
    public function getTrackingHtml($trackingNumber)
    {
		$tracking_url='http://aueapi.auexpress.com/api/ShipmentOrderTrack?OrderId='.$trackingNumber;
		
	/**
     *
     * MemberId 为代理号
     * Password 为代理号登陆 STS 系统所需的密码
     */
		$MemberId='1111';
		$Password='Auexpress123';
		
		
		$xml='{MemberId: "'.$MemberId.'",Password:"'.$Password.'"}';
		
		$tokenjson=$this->curl($xml, "http://auth.auexpress.com/api/token",array("Content-Type: text/json"));
	
		$tokenarr=json_decode($tokenjson,true);
		$token="";
		if(isset($tokenarr['Token']))
		$token=$tokenarr['Token'];

		$data=$this->curl('',$tracking_url,array("Authorization: Bearer ".$token));
		
		//sample test data
		/*
		if($trackingNumber=='ZH08048000012'){
			$data='{ 
				"Code": 0, 
				"Warning": "", 
				"Destination": "河北省保定市", 
				"LastLocation": "中国处理中心-China", 
				"CurrentTrackTime": "2018-11-21 10:56:55", 
				"CurrentStatus": "清关处理中", 
				"TransferOrderId": "997550800081", 
				"TransferVendor": "中国邮政", 
				"BatchDate": "2018-11-17", 
				"CustomsClearance": false, 
				"Delivered": false, 
				"TrackList": [ 
					{ 
						"Notes": "", 
						"StatusTime": "2018-11-13 16:00:02", 
						"Location": "澳大利亚处理中心-Australia", 
						"id_OrderStatus": 1001, 
						"StatusDetail": "运单已创建, 等待收货" 
					}, 
					{ 
						"Notes": "", 
						"StatusTime": "2018-11-14 18:49:12", 
						"Location": "澳大利亚处理中心-Australia", 
						"id_OrderStatus": 1010, 
						"StatusDetail": "预分配转运单号" 
					},         { 
						"Notes": "", 
						"StatusTime": "2018-11-14 18:49:13", 
						"Location": "澳大利亚处理中心-Australia", 
						"id_OrderStatus": 1004, 
						"StatusDetail": "已分配航班/批次" 
					}, 
					{ 
						"Notes": "", 
						"StatusTime": "2018-11-14 21:30:22", 
						"Location": "澳大利亚处理中心-Australia", 
						"id_OrderStatus": 1103, 
						"StatusDetail": "货物打板装车，准备运往机场" 
					}
				], 
				"Weight": "0.82", 
				"Duration": "6 天 16 小时 13 分钟", 
				"ShipmentMethod": "空运快递", 
				"ReturnResult": "Success", 
				"Message": "" 
				}';
		}*/
	
		$json=json_decode($data,true);
		if(isset($json['Warning']) && $json['Warning']!="") return  $json['Warning'];
		if(!isset($json['TransferOrderId'])) return $tracking_url;
		$html='<div class="" style="border:1px solid #ccc;margin:10px;padding:10px;">
		运单号：'.$json['TransferOrderId'].' <br>
		目的地: '.$json['Destination'].' <br>
		所在地：'.$json['LastLocation'].' <br>
		清关后转运单号：'.$json['TransferVendor'].'  '.$json['TransferOrderId'].' <br>
		状态: '.$json['CurrentStatus'].'<br>
		时间: '.$json['CurrentTrackTime'].'';
		
		if(is_array($json['TrackList']) ){
			foreach($json['TrackList'] as $detail){
				$html.= '<div style="border:1px solid #eee;margin:10px;padding:10px;">
					日期: '.$detail['StatusTime'].' 地点: '.$detail['Location'].' 详细内容: '.$detail['StatusDetail'].'
				</div>';
			}
		}
		$html.='
		</div>
		';
		return $html;
	}
	
	/**
     *
     * @param string 
     */
	
	public function curl($xml, $url,  $headers=array(), $useCert = false, $second = 30,$javascript_loop=0)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch, CURLOPT_URL, $url);
		if(1) {		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}else{
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
		}
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        //post提交方式
		if($xml!=""){
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		}
        //运行curl
        $content = curl_exec( $ch );
		$response = curl_getinfo( $ch );
		curl_close ( $ch );
		if ($response['http_code'] == 301 || $response['http_code'] == 302) {
			ini_set("user_agent", "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1");
	
			if ( $tmheaders = get_headers($response['url']) ) {
				foreach( $tmheaders as $value ) {
					if ( substr( strtolower($value), 0, 9 ) == "location:" )
						return $this->curl( $xml ,trim( substr( $value, 9, strlen($value) ) ),$headers , $useCert , $second );
				}
			}
		}
	
		if (    ( preg_match("/>[[:space:]]+window\.location\.replace\('(.*)'\)/i", $content, $value) || preg_match("/>[[:space:]]+window\.location\=\"(.*)\"/i", $content, $value) ) && $javascript_loop < 5) {
			return $this->curl($xml,$value[1],$headers , $useCert , $second , $javascript_loop+1 );
		} else {
			return $content;
		}
		
    }
	
}
