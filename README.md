###阿里云[OSS,DAYU,LOG,MNS]

阿里云官方SDK的Composer封装，支持Yii2。
添加删除文件的功能，修改getUrl（只返回文件外链url，不带其他参数）。


###安装

$ php composer.phar require gulltour/aliyun "~1.0.0"

###OSS Yii2使用

修改config/params.php

添加：
```php
'oss'=>[
         'class'=>'gulltour\aliyun\oss\AliyunOss',
         'bucket'=>'',
         'AccessKeyId' => '',
         'AccessKeySecret' => '',
         'ossServer' => '', //服务器外网地址，杭州为 http://oss-cn-hangzhou.aliyuncs.com
         'ossServerInternal' => '', //服务器内网地址，杭州为 http://oss-cn-hangzhou-internal.aliyuncs.com 如果为空则不走内网上传，内网上传会节省流量
         'imageHost' => '' //自定义资源域名 默认为 http://bucket.img-cn-hangzhou.aliyuncs.com/
     ],
```


###使用

```php

Yii::$app->oss->upload2oss($filePath, $ssoPath);

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
