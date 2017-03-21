<?php
namespace gulltour\aliyun;
use yii\base\Component;
use OSS\Core\OssException;
require_once __DIR__.'/aliyun-oss-php-sdk-2.2.2/autoload.php';
/**
 *
 * @author weijian
 * 'oss'=>[
 *          'class'=>'gulltour\aliyun\oss\AliyunOss',
 *          'bucket'=>'',
 *          'AccessKeyId' => '',
 *          'AccessKeySecret' => '',
 *          'endpoint'=>'oss-cn-hangzhou.aliyuncs.com',
 *          'imageHost' => 'http://bucket.img-cn-hangzhou.aliyuncs.com/'
 *      ],
 */
class AliyunOss extends Component
{
    public $bucket = '';
    public $AccessKeyId = '';
    public $AccessKeySecret = '';
    public $endpoint = 'oss-cn-hangzhou.aliyuncs.com';
    public $imageHost = '';

    private $client;
    public function init()
    {
        if (empty($this->imageHost)) {
            $this->imageHost = 'http://'.$this->bucket.'.'.$this->endpoint.'/';
        }
        try {
            $this->client = new \OSS\OssClient($this->AccessKeyId, $this->AccessKeySecret, $this->endpoint);
        } catch (OssException $e) {
            print $e->getMessage();
        }
    }

    /**
     * 上传文件到OSS
     */
    public function upload2oss($tempName, $path=null)
    {
        try {
            $stream  = file_get_contents($tempName);
            if (empty($path)){
                $path = date('Ymd').mb_substr(md5($stream), -8);
            }
            $this->client->putObject($this->bucket, $path, $stream);
            return $path;
        } catch (OssException $ex) {
            throw new \ErrorException( "Error: " . $ex->getMessage() . "\n");
        }
    }

    /**
     * 上传文件流到OSS
     */
    public function uploadStream2oss($stream,$filename)
    {
        try {
            return $this->client->putObject( $this->bucket, $filename, $stream);
        } catch (OSSException $ex) {
            throw new \ErrorException( "Error: " . $ex->getMessage() . "\n");
        }
    }

    /**
     * 获取图片地址
     * @param string $path 路径
     * @param string $style 样式
     */
    public function getImageUrl($path, $style=null)
    {
        if (empty($style)){
            return $this->imageHost.$path;
        }
        return $this->imageHost.$path.'@!'.$style;
    }

    public function __call($method_name, $args)
    {
        if(empty($args['Bucket'])){
            $args['Bucket'] = $this->bucket;
        }
        return call_user_func_array([$this->client, $method_name],$args);
    }
}