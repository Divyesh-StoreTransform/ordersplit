<?php

namespace Storetransform\OrderSplit\Model\Tracking;


class Blueskyexpress 
{
	public $title='track.blueskyexpress.com.au';
   
	/**
     * TrackingBlueSky  http://track.blueskyexpress.com.au:8888/cgi-bin/GInfo.dll?MfcISAPICommand=EmsApiTrack&cno=BM691985
     * @return string
     */
	
	public function getTrackingHtml($trackingNumber){
		$tracking_url='http://track.blueskyexpress.com.au:8888/cgi-bin/GInfo.dll?MfcISAPICommand=EmsApiTrack&cno='.$trackingNumber;
		
		$html="";
		$xml = ($this->curl($tracking_url));
		//sample test data
		/*
		if($trackingNumber=='BM691985'){
		$xml='100
			<EMS_INFO>
			<TRCKING_NBR>BM691985</TRCKING_NBR>
			<NUMBER>BM691985</NUMBER>
			<NUMBER_T>70520907069511</NUMBER_T>
			<EMSKIND>墨尔本广州线</EMSKIND>
			<NUMBER_TT></NUMBER_TT>
			<FROM>墨尔本</FROM>
			<DES>江苏</DES>
			<TRANSKIND>汇通</TRANSKIND>
			<TRANS_NBR>70520907069511</TRANS_NBR>
			<ITEM>1</ITEM>
			<WEIGHT>0.800</WEIGHT>
			<STATE>1</STATE>
			
			</EMS_INFO>
			
			<TRACK_DATA>
			<DATETIME>2016-09-23 19:56</DATETIME><PLACE>墨尔本蓝天快递货物处理中心　　</PLACE><INFO>中心货物已扫描入库　</INFO>
			<DATETIME>2016-09-26 19:15</DATETIME><PLACE>墨尔本蓝天快递货物处理中心</PLACE><INFO>货物装车完毕，运往机场</INFO>
			<DATETIME>2016-09-27 22:20</DATETIME><PLACE>墨尔本国际机场</PLACE><INFO>航班起飞</INFO>
			<DATETIME>2016-09-29 05:20</DATETIME><PLACE>中国</PLACE><INFO>航班抵达</INFO>
			<DATETIME>2016-09-29 10:32</DATETIME><PLACE>中国海关</PLACE><INFO>货物运至海关监管仓，等待节后清关</INFO>
			<DATETIME>2016-10-08 08:00</DATETIME><PLACE>中国海关</PLACE><INFO>货物开始清关</INFO>
			
			</TRACK_DATA>
			';
			
		}*/
		
	
		$EMS_INFO=(substr($xml,strpos($xml,'<EMS_INFO>'),strpos($xml,'</EMS_INFO>')-strpos($xml,'<EMS_INFO>')+11));
		$TRACK_DATA=(substr($xml,strpos($xml,'<TRACK_DATA>'),strpos($xml,'</TRACK_DATA>')-strpos($xml,'<TRACK_DATA>')+13));

		$json= json_decode(json_encode(simplexml_load_string($EMS_INFO)),TRUE);
		if(!isset($json['TRCKING_NBR'])) return $tracking_url;
		$html='<div class="" style="border:1px solid #ccc;margin:10px;padding:10px;">
		运单号：'.$json['TRCKING_NBR'].' <br>
		出发地：'.$json['FROM'].' <br>
		目的地: '.$json['DES'].' <br>
		清关后转运单号：'.$json['TRANSKIND'].'  '.$json['TRANS_NBR'].' <br>';
		
		$json= json_decode(json_encode(simplexml_load_string($TRACK_DATA)),TRUE);
		
		if(is_array($json['DATETIME'])){
			foreach($json['DATETIME'] as $k=>$date){
				$html.= '<div style="border:1px solid #eee;margin:10px;padding:10px;">
					日期: '.$json['DATETIME'][$k].' 地点: '.$json['PLACE'][$k].' 详细内容: '.$json['INFO'][$k].'
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
