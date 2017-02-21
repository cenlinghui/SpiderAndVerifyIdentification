<?php
/**
 * @author CenLingHui
 * @version 1.0
 */
 
error_reporting(E_ERROR);
$postparam = [  //查询参数
	'text_dyzh'=>$_REQUEST['text_dyzh'],       //导游证号
	'text_dykh'=>$_REQUEST['text_dykh'],       //资格证号
	//'text_dysfzh'=>$_REQUEST['text_dysfzh'], //身份证号
	'text_dysfzh'=>'370830199012303970',       
];
$obj = new FetchGuide;
$data = $obj->run($postparam);
echo json_encode($data);

class FetchGuide{
	const authcode_url = 'http://daoyou-chaxun.cnta.gov.cn/single_info/validatecode.asp'; //验证码地址
	const targetUrl    = 'http://daoyou-chaxun.cnta.gov.cn/single_info/selectlogin_1.asp'; //提交的目标地址
	
	const cookieFile   = '/tmp/cookie.tmp';
	const verifyAddr   = './tmp/verify.jpeg';
	/**
	 * 加载目标网站图片验证码
	 * @param string $authcode_url 目标网站验证码地址
	 */
	protected function showAuthcode($authcode_url){
		$ch = curl_init($authcode_url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_COOKIEJAR,dirname(__FILE__).self::cookieFile); // 把返回来的cookie信息保存在文件中
		$cont = curl_exec($ch);
		curl_close($ch);
		file_put_contents(self::verifyAddr,$cont);
	}
	/**
	 * 模拟登录
	 * @param string $url 提交到的地址
	 * @param string $param 提交时要post的参数
	 * @return string $content 返回的内容
	 */
	protected function curlLogin($url, $param)
	{
		$ch = curl_init($url);
		curl_setopt($ch,CURLOPT_COOKIEFILE, dirname(__FILE__).self::cookieFile); //同时发送Cookie
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($param)); //提交查询信息
		curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$content = curl_exec($ch);
		curl_close($ch);
		
		return $content;
	}
	protected function get_authcode(){
		include ('./Valite.php');
		$img = self::verifyAddr;
		$valite = new Valite();
		$valite->setImage($img);
		$valite->getHec();
		return $ert = $valite->run();
	}
	public function run($postparam){
		$this->showAuthcode(self::authcode_url);
		$postparam['vcode'] = $this->get_authcode();
		$content = $this->curlLogin(self::targetUrl,$postparam);
		$content = iconv("GBK//IGNORE","UTF-8" , $content);
		//echo htmlentities($content,ENT_QUOTES,"UTF-8");
		preg_match("/td_left5'>(.*?)<\/td>.*\n.*td_left5'>(.*?)<.*\n.*td_left5'>(.*?)<[\s\S]*性别.*td_00'>(.*?)<[\s\S]*?资格证号[\s\S]*?td_00'>(.*)<\/td>[\s\S]*?等级[\s\S]*?td_00'>(.*)<\/td>[\s\S]*?导游卡号[\s\S]*?td_00'>(.*)<\/td>[\s\S]*?学历[\s\S]*?td_00'>(.*)<\/td>[\s\S]*?身份证号[\s\S]*?td_00'>(.*)<\/td>[\s\S]*?语种[\s\S]*?td_00'>(.*)<\/td>[\s\S]*?区域名称[\s\S]*?td_00'>(.*)<\/td>[\s\S]*?民族[\s\S]*?td_00'>(.*)<\/td>[\s\S]*?发证日期[\s\S]*?td_00'>(.*)<\/td>[\s\S]*?分值[\s\S]*?td_00'>(.*)<\/td>[\s\S]*?旅&nbsp;行&nbsp;社[\s\S]*?td_00'>(.*)<\/td>[\s\S]*?联系电话[\s\S]*?td_00'>(.*)<\/td>/",$content,$match);
		$data = [
			'no'=>$match[1],
			'name'=>$match[2],
			'language'=>$match[3],
			'sex'=>$match[4],
			'cert_number'=>$match[5],
			'level'=>$match[6],
			'card_no'=>$match[7],
			'education'=>$match[8],
			'id_number'=>$match[9],
			/* 'name'=>$match[9], */
			'area'=>$match[11],
			'nation'=>$match[12],
			'cert_publish_date'=>$match[13],
			'score'=>$match[14],
			'travel_agency'=>$match[15],
			'mobile'=>$match[16],
		];
		return $data;
	}
}
?>