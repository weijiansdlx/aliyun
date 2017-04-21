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
 *          'ossServer' => '', //服务器外网地址，杭州为 http://oss-cn-hangzhou.aliyuncs.com
 *          'ossServerInternal' => '', //服务器内网地址，杭州为 http://oss-cn-hangzhou-internal.aliyuncs.com 如果为空则不走内网上传，内网上传会节省流量
 *          'imageHost' => '' //自定义资源域名 默认为 http://bucket.img-cn-hangzhou.aliyuncs.com/
 *      ],
 */
class AliyunOss extends Component
{
    public $bucket = '';
    public $AccessKeyId = '';
    public $AccessKeySecret = '';
    public $ossServer = 'oss-cn-hangzhou.aliyuncs.com';
    public $ossServerInternal = '';
    public $imageHost = '';

    private $client;
    public function init()
    {
        if (!isset($this->AccessKeyId)) {
            throw new InvalidConfigException('请先配置AccessKeyId');
        }
        if (!isset($this->AccessKeySecret)) {
            throw new InvalidConfigException('请先配置AccessKeySecret');
        }
        if (!isset($this->bucket)) {
            throw new InvalidConfigException('请先创建并配置bucket');
        }
        if (empty($this->imageHost)) {
            $this->imageHost = 'http://'.$this->bucket.'.'.$this->ossServer.'/';
        }
        $ossServer = empty($this->ossServerInternal) ? $this->ossServer : $this->ossServerInternal ;
        try {
            $this->client = new \OSS\OssClient($this->AccessKeyId, $this->AccessKeySecret, $ossServer);
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
        return $this->imageHost.$path.$style;
    }

    public function __call($method_name, $args)
    {
        if(empty($args['Bucket'])){
            $args['Bucket'] = $this->bucket;
        }
        return call_user_func_array([$this->client, $method_name],$args);
    }
}