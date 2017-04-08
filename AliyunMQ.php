<?php

namespace gulltour\aliyun;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Json;

class AliyunMQ extends Component {
	public $endpoint = 'http://publictest-rest.ons.aliyun.com';
    public $topic = '';
    public $accessKeyId = '';
    public $accessKeySecret = '';
    public $producerGroup = '';
    public $consumerGroup = '';
    public $messageReceiver; //接收到消息之后的处理方法,对接收的消息内容进行实际的处理
    public $sentCallback; //消息发送成功之后的回调处理方法，用来更新消息发送之后的状态

	//签名
	private $signature = "Signature";
	private $producerid = "ProducerId";
	//消费者组id
	private $consumerid = "ConsumerId";
	//访问码
	private $ak = "AccessKey";



    public function init() {
        parent::init();
        if (!isset($this->topic)) {
            throw new InvalidConfigException('请先配置topic');
        }
        if (!isset($this->accessKeyId)) {
            throw new InvalidConfigException('请先配置accessKeyId');
        }
        if (!isset($this->accessKeySecret)) {
            throw new InvalidConfigException('请先配置accessKeySecret');
        }
        if (!isset($this->producerGroup)) {
            throw new InvalidConfigException('请先配置producerGroup');
        }
        if (!isset($this->consumerGroup)) {
            throw new InvalidConfigException('请先配置consumerGroup');
        }
    }


    /**
     * 计算签名
     * @param $str
     * @param $key
     * @return string
     */
	private static function calSignatue($str,$key){
		$sign = "";
		if(function_exists("hash_hmac")){
			$sign = base64_encode(hash_hmac("sha1",$str,$key,true));
		}
		else{
			$blockSize = 64;
			$hashfunc = "sha1";
			if(strlen($key) > $blockSize)
			{
				$key = pack('H*',$hashfunc($key));
			}
	
			$key = str_pad($key,$blockSize,chr(0x00));
			$ipad = str_repeat(chr(0x36),$blockSize);
			$opad = str_repeat(chr(0x5c),$blockSize);
			$hmac = pack(
					'H*',$hashfunc(
							($key^$opad).pack(
									'H*',$hashfunc($key^$ipad).$str
							)
					)
			);
	
			$sign = base64_encode($hmac);
		}
		return $sign;
	}
	
	private static function microtime_int(){
		list($usec,$sec) = explode(" ",microtime());
		return number_format((intval($usec)*1000+$sec*1000),0,'','');
	}

    /**
     * 发送http请求
     * @param $type                 HTTP请求类型 GET POST
     * @param $url                  请求URL地址
     * @param $headers              请求头数据
     * @param string $post_body     POST数据
     * @return mixed|string         HTTP请求返回的数据数组
     */
	private function http_request($type, $url, $headers, $post_body=''){
		//初始化网络通信模块
		$ch = curl_init();
		//设置http头部内容
		curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
	
		//设置http请求类型,此处为POST
		curl_setopt($ch,CURLOPT_CUSTOMREQUEST,$type);
	
		//设置http请求的url
		curl_setopt($ch,CURLOPT_URL,$url);
		if ($type == 'POST') {
			//设置http请求的body
			curl_setopt($ch,CURLOPT_POSTFIELDS,$post_body);
		}
	
		//构造执行环境
		ob_start();
		//开始发送http请求
		curl_exec($ch);
		//获取请求应答消息
		$result = ob_get_contents();
		//清理执行环境
		ob_end_clean();
		//关闭连接
		curl_close($ch);
		$result = Json::decode($result,true);
		return $result;
	}
	
	/**
	 * 发送消息内容到消息队列中，设置消息及队列为持久化模式
	 * @param  [type] $message    发送的消息内容
	 */
	public function sendMessage($message){
		$newline = "\n";
		$date = self::microtime_int();
		$postUrl = $this->endpoint."/message/?topic=".$this->topic."&time=".$date."&tag=http&key=http";
		$signString = $this->topic.$newline.$this->producerGroup.$newline.md5($message).$newline.$date;
		//计算签名
		$sign = $this->calSignatue($signString,$this->accessKeySecret);
		//构造签名标记
		$signFlag =$this->signature . ":".$sign;
		//构造密钥标记
		$akFlag = $this->ak . ":".$this->accessKeyId;
		//构造生产者组标记
		$producerFlag = $this->producerid . ":".$this->producerGroup;
		//构造http请求头部内容类型标记
		$contentFlag = "Content-Type:text/html";
		//构造http请求头部
		$headers = array(
				$signFlag,
				$akFlag,
				$producerFlag,
				$contentFlag,
		);
		$result = $this->http_request("POST", $postUrl, $headers, $message);
		if (isset($result['msgId'])) {//消息发送到消息队列之后可以保存到消息产生者相关数据中
			if ($this->sentCallback) {
				call_user_func($this->sentCallback, $message, $result['msgId']);
			}
			return true;
		}
		return false;
	}
	
	/**
	 * 处理并删除消息队列中的消息
	 * @param  array $messages 接收到的消息队列数组
	 */
	private function deleteMessage($messages){
		$newline = "\n";
        Yii::info(var_export($messages, true), __METHOD__);
		//依次遍历每个topic消息
		foreach ((array)$messages as $message){
			if ($this->messageReceiver) {//阿里消息推送过来之后调用所配置的消息消费回调方法进行消费
				call_user_func($this->messageReceiver, $message['body']);
			}
	
			//获取时间戳
			$date = self::microtime_int();
			//构造删除topic消息url
			$delUrl = $this->endpoint."/message/?msgHandle=".$message['msgHandle']."&topic=".$this->topic."&time=".$date;
			//签名字符串
			$signString = $this->topic.$newline.$this->consumerGroup.$newline.$message['msgHandle'].$newline.$date;
			//计算签名
			$sign = $this->calSignatue($signString,$this->accessKeySecret);
			//构造签名标记
			$signFlag = $this->signature.":".$sign;
			//构造密钥标记
			$akFlag = $this->ak.":".$this->accessKeyId;
			//构造消费者组标记
			$consumerFlag = $this->consumerid.":".$this->consumerGroup;
			//构造http请求头部内容类型标记
			$contentFlag = "Content-Type:text/html";
			//构造http请求头部信息
			$delheaders = array(
					$signFlag,
					$akFlag,
					$consumerFlag,
					$contentFlag,
			);
			$result = $this->http_request("DELETE", $delUrl, $delheaders);
		}
	}
	
	/**
	 * 消息消费方法（实际在delete的时候进行消费）
	 */
	public function consumeMessage(){
		$newline = "\n";
		while (true){
			try{
				//构造时间戳
				$date = self::microtime_int();
				//签名字符串
				$signString = $this->topic.$newline.$this->consumerGroup.$newline.$date;
				//计算签名
				$sign = $this->calSignatue($signString,$this->accessKeySecret);
				//构造签名标记
				$signFlag = $this->signature.":".$sign;
				//构造密钥标记
				$akFlag = $this->ak.":".$this->accessKeyId;
				//构造消费者组标记
				$consumerFlag = $this->consumerid.":".$this->consumerGroup;
				//构造http请求发送内容类型标记
				$contentFlag = "Content-Type:text/html";
	
				//构造http头部信息
				$headers = array(
						$signFlag,
						$akFlag,
						$consumerFlag,
						$contentFlag,
				);
	
				//构造http请求url
				$getUrl = $this->endpoint."/message/?topic=".$this->topic."&time=".$date."&num=32";
				$messages = $this->http_request("GET", $getUrl, $headers);
	
				if (count($messages) ==0){ //如果应答信息中的没有包含任何的topic信息,则直接跳过
					continue;
				}
	
				$this->deleteMessage($messages);
			}
			catch (\Exception $e){
				//打印异常信息
				echo $e->getMessage();
			}
		}
	}
}

?>