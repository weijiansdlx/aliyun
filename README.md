###阿里云[OSS,DAYU,LOG,MNS]

阿里云官方SDK的Composer封装，支持Yii2。
添加删除文件的功能，修改getUrl（只返回文件外链url，不带其他参数）。


###安装

$ php composer.phar require gulltour/aliyun "~1.0.0"

###OSS Yii2使用

修改config/params.php

添加：
```php
    'oss'=>array(
        'ossServer' => '', //服务器外网地址，深圳为 http://oss-cn-shenzhen.aliyuncs.com
        'ossServerInternal' => '', //服务器内网地址，深圳为 http://oss-cn-shenzhen-internal.aliyuncs.com
        'AccessKeyId' => '', //阿里云给的AccessKeyId
        'AccessKeySecret' => '', //阿里云给的AccessKeySecret
        'Bucket' => '' //创建的空间名
    ),
```

在components中创建Oss.php，内容如下：

```php

namespace app\components;

use gulltour\AliyunOss\AliyunOSS;
use Yii;

class OSS {

    private $ossClient;

    public function __construct($isInternal = false)
    {
        $serverAddress = $isInternal ? Yii::$app->params['oss']['ossServerInternal'] : Yii::$app->params['oss']['ossServer'];
        $this->ossClient = AliyunOSS::boot(
            $serverAddress,
            Yii::$app->params['oss']['AccessKeyId'],
            Yii::$app->params['oss']['AccessKeySecret']
        );
    }

    public static function upload($ossKey, $filePath)
    {
        //$oss = new OSS(true); // 上传文件使用内网，免流量费
        $oss = new OSS();
        $oss->ossClient->setBucket(Yii::$app->params['oss']['Bucket']);
        $oss->ossClient->uploadFile($ossKey, $filePath);
    }

    public static function getUrl($ossKey)
    {
        $oss = new OSS();
        $oss->ossClient->setBucket(Yii::$app->params['oss']['Bucket']);
        return preg_replace('/(.*)\?OSSAccessKeyId=.*/', '$1', $oss->ossClient->getUrl($ossKey, new \DateTime("+1 day")));
    }

    public static function delFile($ossKey)
    {
        $oss = new OSS();
        $oss->ossClient->setBucket(Yii::$app->params['oss']['Bucket']);
        $oss->ossClient->delFile($ossKey);
    }

    public static function createBucket($bucketName)
    {
        $oss = new OSS();
        return $oss->ossClient->createBucket($bucketName);
    }

    public static function getAllObjectKey($bucketName)
    {
        $oss = new OSS();
        return $oss->ossClient->getAllObjectKey($bucketName);
    }

}

```


###使用

```php

use app\components\Oss;

OSS::upload('文件名', '本地路径'); // 上传一个文件

echo OSS::getUrl('某个文件的名称'); // 打印出某个文件的外网链接

OSS::createBucket('一个字符串'); // 新增一个 Bucket。注意，Bucket 名称具有全局唯一性，也就是说跟其他人的 Bucket 名称也不能相同。

OSS::getAllObjectKey('某个 Bucket 名称'); // 获取该 Bucket 中所有文件的文件名，返回 Array。

```

###LOG Yii2使用


```php
 'log'=>[
          'class'=>'aliyun\oss\AliyunLog',
          'logstore'=>'gulltour',
          'project'=>'',
          'AccessKeyId' => '',
          'AccessKeySecret' => '',
          'endpoint'=>'oss-cn-hangzhou.aliyuncs.com',
       ],

```


###MNS Yii2使用

YII2插件-阿里云消息队列SDK
===
配置
---
```php
'mns'=>[
    'class'=>'gulltour\aliyun\aliyunMns',
    'accessId' => '',
    'accessKey' => '',
    'endpoint' => 'http://.mns.cn-beijing.aliyuncs.com/',
],
```
使用示例：
---
```php
// 发送消息到队列
\Yii::$app->mns->sendMessage("QueueName", "content demo");
// 接收队列消息
$messageObject = \Yii::$app->mns->receiveMessage("QueueName");
$data = $messageObject->getMessageBody();
// 删除队列消息
\Yii::$app->mns->->deleteMessage('QueueName', $messageObject);
//publish 消息到主题
\Yii::$app->mns->publishMessage('TopicName', $data);
//订阅主题，在Yii2的 controller 中接收推送过来的数据
public function actionSubscribe()
{
	$message = \Yii::$app->request->getRawBody();
	$data = json_decode($message, true); //如果消息是JSON，PHP中需要转换成数组
｝
```

###License
除 “版权所有（C）阿里云计算有限公司” 的代码文件外，遵循 [MIT license](http://opensource.org/licenses/MIT) 开源。
