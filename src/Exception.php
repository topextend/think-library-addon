<?php

// +----------------------------------------------------------------------
// | Ladmin
// +----------------------------------------------------------------------
// | 官方网站: http://www.ladmin.cn
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://github.com/topextend/ladmin
// +----------------------------------------------------------------------

namespace think\admin;

/**
 * 自定义数据异常
 * Class Exception
 * @package think\admin
 */
class Exception extends \Exception
{
    /**
     * 异常数据对象
     * @var mixed
     */
    protected $data = [];

    /**
     * Exception constructor.
     * @param string $message
     * @param integer $code
     * @param mixed $data
     */
    public function __construct($message = "", $code = 0, $data = [])
    {
        $this->data = $data;
        $this->code = $code;
        $this->message = $message;
        parent::__construct($message, $code);
    }

    /**
     * 设置异常停止数据
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * 获取异常停止数据
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

}