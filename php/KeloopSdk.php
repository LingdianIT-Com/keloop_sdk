<?php

/**
 * Copyright (C) 2012-2015 成都零点信息技术有限公司.
 * All rights reserved.
 */

/**
 *  类描述: [快跑者SDK]
 *  创建人: [零校网团队]
 */
class KeloopSdk {
    
    const BASE_URL = "http://api.keloop.com/api";
    private $accessKey = "";
    private $accessSec = "";
    
    
    function __construct($key,$sec) {
        if(empty($key) || empty($sec)){
            throw new Exception("accessKey 与密钥不能为空");
        }
        $this->accessKey = $key;
        $this->accessSec = $sec;
    }

    public function getUrl($path,$para=array()){
        $para['expire_time'] = time()+120;
        $para['access_key'] = $this->accessKey;
        $sign = Md5Sign::getSign($para, $this->accessSec);
        $para['sign'] = $sign;
        $url = self::BASE_URL.$path;
        $data = HTTPRequest::getUrl($url,$para);
        if(!empty($data)){
            return json_decode($data,true);
        }else{
            return null;
        }
    }
    
    public function postUrl($path,$para=array()){
        $para['expire_time'] = time()+120;
        $para['access_key'] = $this->accessKey;
        $sign = Md5Sign::getSign($para, $this->accessSec);
        $para['sign'] = $sign;
        $url = self::BASE_URL.$path;
        $data = HTTPRequest::postUrl($url,$para);
        if(!empty($data)){
            return json_decode($data,true);
        }else{
            return null;
        }
    }
    
    /**
     *  检查绑定的id 和token是否存在
     */
    public function isExist(){
        $path = "/order/exist";
        $result = $this->getUrl($path);
        if($result['code'] =="100"){
            return $result;
        }else{
            return false;
        }
    }
    
    /**
     *  向绑定的配送站发送订单
     * @param type $para
     * @return boolean
     */
    public function send($para){
        $path = "/order/send";
        $result = $this->postUrl($path, $para);
        if($result['code'] =="100"){
            return $result;
        }else{
            return false;
        }
    }
    
    public function query($para){
        $path = "/order/query";
        $result = $this->getUrl($path, $para);
        if($result['code'] =="100"){
            return $result;
        }else{
            return false;
        }
    }
    
    public function queryDetail($para){
        
    }
    
    public function cancel($para){
        $path = "/order/cancel";
        $result = $this->getUrl($path,$para);
        if($result['code'] =="100"){
            return $result;
        }else{
            return false;
        }
    }
    
    
    
}

class Md5Sign {
     
    /**
     *  获取签名
     * @param type $para  加密的参数数组
     * @param type $key    加密的key
     * return sign 生产的签名 sign
     */
    public static function getSign($para, $encKey){
        if(empty($para)||empty($encKey)){
            return false;
        }
        //除去待签名参数数组中的空值和签名参数
        $para = self::paraFilter($para);
        $para = self::argSort($para);
        $str = self::createLinkstring($para);
        $sign = self::md5Verify($str, $encKey);
        return $sign;
    }
    
    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    private static function paraFilter($para){
        $para_filter = array();
        while(list ($key, $val) = each($para)){
            if($key=="sign"||$key=="sign_type"||$key=="key"|| (empty($val) && !is_numeric($val))){ //去掉 "",null,保留数字0
                continue;
            }else{
                $para_filter[$key] = $para[$key];
            }
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    private static function argSort($para){
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    private static function createLinkstring($para){
        $arg = "";
        while(list ($key, $val) = each($para)){
            $arg.=$key."=".$val."&";
        }
        //去掉最后一个&字符
        $arg = trim($arg, '&');
        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){
            $arg = stripslashes($arg);
        }
        return $arg;
    }

    /**
     * 生成
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     * return 签名结果
     */
    private static function md5Verify($prestr, $key){
        return md5($prestr.$key);
    }
    
}

class HTTPRequest{

    public static function postUrl($url, $params = array(), $timeout = 30){
//        Log::record("post url: $url");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        // 设置header
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 运行cURL，请求网页
        $data = curl_exec($curl);
//        Log::record("post result:".$data);
        return $data;
    }

    public static function getUrl($url, $param = array()){
        $url = self::buildUrl($url, $param);
        return self::get($url);
    }

    public static function get($url, $timeout = 30){
//        Log::record("get url: $url");
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $resposne = curl_exec($ch);
//        Log::record("get result:".$resposne);
        return $resposne;
    }

    private static function buildUrl($url, $param){
        $url = rtrim(trim($url),"?");
        $url = $url."?";
        $query = "";
        if(!empty($param)){
            $query = http_build_query($param);
        }
        return $url.$query;
    }

}


