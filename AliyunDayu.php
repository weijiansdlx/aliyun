<?php
namespace gulltour\aliyun;

use yii\base\Component;
require_once __DIR__.'/taobao-sdk-PHP-auto/TopSdk.php';

class AliyunDayu extends Component
{

    public $appKey = '';
    public $secretKey = '';
    public $signName = '';
    private $client;
    public function init()
    {
        parent::init();
        if (!isset($this->appKey)) {
            throw new InvalidConfigException('请先配置appKey');
        }
        if (!isset($this->secretKey)) {
            throw new InvalidConfigException('请先配置secretKey');
        }
        if (!isset($this->signName)) {
            throw new InvalidConfigException('请先配置signName');
        }
        $this->client = new \TopClient($this->appKey, $this->secretKey);
    }

    /**
     * 阿里大于短信发送方法
     * @param $mobile 手机号码
     * @param $params 发送信息参数
     * @param $template 阿里大于模板ID
     * @return mixed
     */
    public function smsSend($mobile, $params, $template){
        $req = new \AlibabaAliqinFcSmsNumSendRequest;
        $req->setSmsType("normal");
        $req->setSmsFreeSignName($this->signName);
        $req->setSmsParam($params);
        $req->setRecNum($mobile);
        $req->setSmsTemplateCode($template);
        $resp = $this->client->execute($req);
        return $resp;
    }


}