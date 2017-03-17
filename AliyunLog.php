<?php
namespace aliyun\log;
use yii\base\Component;
use yii\base\InvalidConfigException;

require_once __DIR__.'/aliyun-log-php-sdk-master/Log_Autoload.php';
/**
 *
 * @author weijian
 * 'log'=>[
 *          'class'=>'aliyun\oss\AliyunLog',
 *          'logstore'=>'',
 *          'project'=>'',
 *          'AccessKeyId' => '',
 *          'AccessKeySecret' => '',
 *          'endpoint'=>'oss-cn-hangzhou.aliyuncs.com',
 *      ],
 */
class AliyunLog extends Component
{
    public $endpoint = 'cn-hangzhou.sls.aliyuncs.com'; // 选择与上面步骤创建 project 所属区域匹配的 Endpoint
    public $AccessKeyId = 'your_access_key_id';        // 使用你的阿里云访问秘钥 AccessKeyId
    public $AccessKeySecret = 'your_access_key';       // 使用你的阿里云访问秘钥 AccessKeySecret
    public $project = 'your_project';                  // 上面步骤创建的项目名称
    public $logstore = 'your_logstore';                // 上面步骤创建的日志库名称

    private $client;
    public function init()
    {
        parent::init();
        if (!isset($this->AccessKeyId)) {
            throw new InvalidConfigException('请先配置AccessKeyId');
        }
        if (!isset($this->AccessKeySecret)) {
            throw new InvalidConfigException('请先配置AccessKeySecret');
        }
        $this->client = new Aliyun_Log_Client($this->endpoint, $this->AccessKeyId, $this->AccessKeySecret);
    }

    /**
     * 列出当前 project 下的所有日志库名称
     */
    public function listStores()
    {
        $req = new Aliyun_Log_Models_ListLogstoresRequest($this->project);
        return $this->client->listLogstores($req);
    }

    /**
     * 创建 logstore
     */
    public function createStores()
    {
        $req = new Aliyun_Log_Models_CreateLogstoreRequest($this->project,$this->logstore,3,2);
        return $this->client -> createLogstore($req);
    }


    /**
     * 写入日志
     */
    public function putLogs($topic, $source, $logitems=[])
    {
        for ($i = 0; $i < 5; $i++)
        {
            $contents = array('index1'=> strval($i));
            $logItem = new Aliyun_Log_Models_LogItem();
            $logItem->setTime(time());
            $logItem->setContents($contents);
            array_push($logitems, $logItem);
        }
        $req = new Aliyun_Log_Models_PutLogsRequest($this->project, $this->logstore, $topic, $source, $logitems);
        return $this->client->putLogs($req);
    }

}