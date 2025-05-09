<?php

namespace Storetransform\OrderSplit\Model\Tracking;


class Ewe 
{
	public $title='www.ewe.com.au';
    /**
     *
     * @param string  
     */
	
    public function getTrackingHtml($trackingNumber)
    {
		
		$tracking_url='https://www.ewe.com.au/oms/api/tracking/ewe/'.$trackingNumber;
		$data=$this->curl($tracking_url);
		
		//sample test data
		/*if($trackingNumber=='589737297464'){
			$data='{"Payload":[{"IsInput":false,"IdPresent":false,"IsReceived":false,"EweNo":"589737297464","TransferNo":"589737297464","Departure":"Chullora NSW","Destination":null,"CreateDate":"2015-04-28T10:53:27.113","DeliveryDate":null,"TransferName":"顺丰","TransferRoute":"天津宜送","LastInfo":null,"DeliveryStatusCn":"送达","DeliveryStatus":3,"Details":[],"Modified":false,"InPipeline":false,"Reminder":"已送达。感谢您使用EWE的服务。"}],"Total":1,"Status":0,"Message":"","Error":""}';
			{"Payload":[{"IsInput":false,"IdPresent":false,"IsReceived":false,"EweNo":"S158490013S","TransferNo":"9710470522499","Departure":"Chullora NSW","Destination":null,"CreateDate":"2016-06-02T16:08:42.253","DeliveryDate":null,"TransferName":"中国邮政EMS","TransferRoute":"EWE全球快递","LastInfo":"已签收,收发室 代收,投递员：王伟庆 18758835315","DeliveryStatusCn":"送达","DeliveryStatus":3,"Details":[{"DateString":"2016-06-02 16:09","Place":"Sydney NSW Australia","Message":"Picked up by driver.","PositionCode":1},{"DateString":"2016-06-02 19:01","Place":"(NSW) Region processing centre","Message":"Package arrived at warehouse.","PositionCode":3},{"DateString":"2016-06-06 16:14","Place":"In transit","Message":"In transit to airport.","PositionCode":5},{"DateString":"2016-06-07 19:12","Place":"SYDNEY - AUSTRALIA","Message":"Departed Facility in SYDNEY - AUSTRALIA","PositionCode":50},{"DateString":"2016-06-09 09:28","Place":"中国","Message":"包裹信息已提交海关","PositionCode":52},{"DateString":"2016-06-11 15:34","Place":"中国","Message":"正在清关","PositionCode":54},{"DateString":"2016-06-14 11:08","Place":"全国中心长水监管","Message":"【全国中心长水监管】已收寄","PositionCode":100},{"DateString":"2016-06-14 18:13","Place":"昆明","Message":"到达【昆明】","PositionCode":101},{"DateString":"2016-06-15 06:24","Place":"昆明","Message":"离开【昆明】，下一站【上海王港】","PositionCode":102},{"DateString":"2016-06-17 01:08","Place":"上海","Message":"到达【上海】","PositionCode":103},{"DateString":"2016-06-17 22:13","Place":"上海","Message":"离开【上海】，下一站【宁波】","PositionCode":104},{"DateString":"2016-06-18 03:56","Place":"宁波","Message":"到达【宁波】","PositionCode":105},{"DateString":"2016-06-18 11:39","Place":"宁波","Message":"离开【宁波】，下一站【奉化】","PositionCode":106},{"DateString":"2016-06-18 13:07","Place":"奉化","Message":"到达【奉化】","PositionCode":107},{"DateString":"2016-06-18 14:14","Place":"奉化邮政局投递组","Message":"到达【奉化邮政局投递组】","PositionCode":108},{"DateString":"2016-06-18 14:27","Place":"奉化邮政局投递组","Message":"【奉化邮政局投递组】正在投递,投递员：王伟庆 18758835315","PositionCode":109},{"DateString":"2016-06-18 14:34","Place":"","Message":"已签收,收发室 代收,投递员：王伟庆 18758835315","PositionCode":110}],"Modified":false,"InPipeline":false,"Reminder":"已送达。感谢您使用EWE的服务。"}],"Total":1,"Status":0,"Message":"","Error":""}
		}*/
		$json=json_decode($data,true);
		if(!isset($json['Payload'][0])) return $tracking_url;
		$json=$json['Payload'][0];
		$html='<div class="" style="border:1px solid #ccc;margin:10px;padding:10px;">
		运单号：'.$json['EweNo'].' <br>
		出发地：'.$json['Departure'].' <br>
		目的地: '.$json['Destination'].' <br>
		清关后转运单号：'.$json['TransferName'].'  '.$json['TransferNo'].' <br>
		状态: '.$json['DeliveryStatusCn'].'<br>';
		
		if(isset($json['Details']) ){
			foreach($json['Details'] as $detail){
				$html.= '<div style="border:1px solid #eee;margin:10px;padding:10px;">
					日期: '.$detail['DateString'].' 地点: '.$detail['Place'].' 详细内容: '.$detail['Message'].'
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
	
	public function curl( $url,  $javascript_loop = 0, $timeout = 30 ) {
		$url = str_replace( "&amp;", "&", urldecode(trim($url)) );
	
		$ch = curl_init();
	  //  curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/2008070208 Firefox/3.0.1" );
		curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.2) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.27 Safari/525.13" );
		curl_setopt( $ch, CURLOPT_URL, $url );
	  //  curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_ENCODING, "" );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		//curl_setopt( $ch, CURLOPT_BINARYTRANSFER, true );
		curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
		curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
		$content = curl_exec( $ch );
		if($content!="")
		return $content;
		$response = curl_getinfo( $ch );
		curl_close ( $ch );
	
		if ($response['http_code'] == 301 || $response['http_code'] == 302) {
		curl_setopt( $ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.2) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.27 Safari/525.13" );
	
			if ( $headers = get_headers($response['url']) ) {
				foreach( $headers as $value ) {
					if ( substr( strtolower($value), 0, 9 ) == "location:" )
						return curl ( trim( substr( $value, 9, strlen($value) ) ) );
				}
			}
		}
	
		if (    ( preg_match("/>[[:space:]]+window\.location\.replace\('(.*)'\)/i", $content, $value) || preg_match("/>[[:space:]]+window\.location\=\"(.*)\"/i", $content, $value) ) && $javascript_loop < 5) {
			return curl ( $value[1], $javascript_loop+1 );
		} else {
			return $content;
		}
	}
	
}
