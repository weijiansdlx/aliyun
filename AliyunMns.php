<?php
namespace aliyun\mns;

require_once __DIR__.'/aliyun-mns-php-sdk-1.3.3/mns-autoloader.php';
use yii\base\Component;
use AliyunMNS\Client;
use AliyunMNS\Requests\PublishMessageRequest;
use AliyunMNS\Requests\CreateQueueRequest;
use AliyunMNS\Model\SubscriptionAttributes;
use AliyunMNS\Requests\SendMessageRequest;
use AliyunMNS\Exception\MnsException;
/**
 * ==============================================
 * Copy right 2016-2017
 * ==============================================
 * 阿里云MQ SDK
 * @param unknowtype
 * @return return_type
 * @author: weijian
 */
class AliyunMns extends Component
{
    public $accessId = '';
    public $accessKey = '';
    public $endpoint = 'http://.mns.cn-beijing.aliyuncs.com/';
    private $client;

    public function init(){
        $this->client = new Client($this->endpoint, $this->accessId, $this->accessKey);
        return $this->client;
    }

    public function __call($method_name, $args)
    {
        return call_user_func_array([$this->client, $method_name],$args);
    }

    /**
     * ====================================================================
     * 以下提供一些便捷方法，也可以通过官方SDK的方法做同样的处理
     * --------------------------------------------------------------------
     */


    /**
     * 发送消息到队列
     * @param string $queueName 队列名称
     * @param string $messageBody 消息内容
     * @return \AliyunMNS\SendMessageResponse:
     * @example sendMessage("boss-dev", "content demo");
     */
    public function createQueue($queueName)
    {
        $request = new CreateQueueRequest($queueName);
        try
        {
            return $this->client->createQueue($request);
        }
        catch (MnsException $e)
        {
            echo "CreateQueueFailed: " . $e;
            return;
        }
    }

    /**
     * 发送消息到队列
     * @param string $queueName 队列名称
     * @param string $messageBody 消息内容
     * @return \AliyunMNS\SendMessageResponse:
     * @example sendMessage("boss-dev", "content demo");
     */
    public function sendMessage($queueName, $messageBody)
    {
        if (!is_string($messageBody)){
            $messageBody = json_encode($messageBody);
        }
        $queue = $this->client->getQueueRef($queueName);
        try
        {
            $request = new SendMessageRequest($messageBody);
            return $queue->sendMessage($request);
        }
        catch (MnsException $e)
        {
            echo "SendMessage Failed: " . $e;
            return;
        }

    }

    /**
     * 从队列里获取消息
     * @param string $queueName 队列名称
     * @return \AliyunMNS\ReceiveMessageResponse:
     * @example receiveMessage("boss-dev");
     */
    public function receiveMessage($queueName)
    {
        $receiptHandle = NULL;
        $queue = $this->client->getQueueRef($queueName);
        try
        {
            // when receiving messages, it's always a good practice to set the waitSeconds to be 30.
            // it means to send one http-long-polling request which lasts 30 seconds at most.
            return $queue->receiveMessage(30);
        }
        catch (MnsException $e)
        {
            echo "ReceiveMessage Failed: " . $e;
            return;
        }
    }

    /**
     * 删除消息
     * @param object $messageObject 消息实体
     * @return \AliyunMNS\Responses\ReceiveMessageResponse
     */
    public function deleteMessage($queueName, $messageObject)
    {
        $queue = $this->client->getQueueRef($queueName);
        $receiptHandle = $messageObject->getReceiptHandle();
        return $queue->deleteMessage($receiptHandle);
    }

    /**
     * 发布消息到Topic
     * @param string $topicName Toppic名称
     * @param string $messageBody 消息内容
     * @example publishMessage('customer-login-dev', 'test content');
     * @return \AliyunMNS\Responses\BaseResponse
     */
    public function publishMessage($topicName, $messageBody)
    {
        if (!is_string($messageBody)){
            $messageBody = json_encode($messageBody);
        }
        $topic = $this->client->getTopicRef($topicName);
        $request = new PublishMessageRequest($messageBody);

        return $topic->publishMessage($request);
    }

    /**
     * 添加订阅器
     * @param string $topicName Topic名称
     * @param string $subscriptionName 订阅器名称
     * @param url $endPoint 接收内容的URL，包含 http://,其它方式看阿里云的文档
     * @return \AliyunMNS\Responses\BaseResponse
     */
    public function subscribeTopic($topicName, $subscriptionName, $endPoint)
    {
        $topic = $this->client->getTopicRef($topicName);
        $attributes = new SubscriptionAttributes($subscriptionName, $endPoint);

        return $topic->subscribe($attributes);
    }
}