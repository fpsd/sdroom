<?php
/**
 * Created by FPHD.COM.
 * User: linmaogan
 * Date: 2017/12/7
 * Time: 上午1:27
 */

namespace app\Controllers;

use app\BaseException;
use Server\CoreBase\Controller;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Server\SwooleMarco;

class BaseController extends Controller
{
    protected $mysqlPool;
    protected $redisPool;
    protected $commonConfig;

    public function __construct()
    {
        parent::__construct();

        // 设置数据库连接池
        $this->setDatabaseAsynPool();
        $this->commonConfig = getModuleConfig('common');
    }

    protected function initialization($controller_name, $method_name)
    {
        parent::initialization($controller_name, $method_name);

        $this->user = [];

        // 设置是否开启调试标记
        $commonConfig = getModuleConfig('common');
        !defined('FP_DEBUG') && define("FP_DEBUG", $commonConfig->get('global.debug'));

        // 安装MySql连接池
        $this->installMysqlAsynPool();
    }

    /**
     * 获取来源地址
     * @return string
     */
    public function getRefererUrl()
    {
        $refererUrl = $this->http_input->getRequestHeader('referer');
        return $refererUrl;
    }

    /**
     * 获取跳转地址
     * @param string $redirectUrlKey
     * @param array $unsetKey
     * @return string
     */
    public function getRedirectUrl($redirectUrlKey = '', $unsetKey = ['channelToken'])
    {
        if (!$redirectUrlKey) {
            $redirectUrl = $this->http_input->get('redirectUrl');
            if (!$redirectUrl) {
                // 删除不需要的参数
                $queryString = $this->http_input->getRequestUri();
                if ($unsetKey && $queryString) {
                    parse_str($queryString, $parameters);
                    foreach ($parameters as $key => $value) {
                        if (in_array($key, $unsetKey)) {
                            unset($parameters[$key]);
                        }
                    }

                    $parameters && $queryString = urldecode(http_build_query($parameters));
                }
                $channelId = $this->getChannelId();
                $redirectUrl = getHttpProtocol($channelId) . '://' . $this->http_input->header('host') . $queryString;
            }
        } else {
            $redirectUrl = yield $this->redis_pool->getCoroutine()->get($redirectUrlKey);
        }

        return $redirectUrl;
    }

    /**
     * 设置跳转地址
     * @return bool|string
     */
    public function setRedirectUrl($url = '')
    {
        $redirectUrl = yield $this->getRedirectUrl();
        $redirectUrlKey = getRandString(8, 'redirectUrlKey');
        yield $this->redis_pool->getCoroutine()->set($redirectUrlKey, $redirectUrl, 300); // 缓存5分钟

        $data = [];
        if ($url) {
            $url .= strpos($url, '?') !== false ? "&redirectUrlKey=$redirectUrlKey" : "?redirectUrlKey=$redirectUrlKey";
            return $url;
        }
        return $data;
    }

    /**
     * 统一输出
     * @param $output
     * @param int $code
     * @param bool $gzip
     * @param bool $return
     * @param array $debug
     * @return string
     */
    protected function end($output, $code = 0, $gzip = true, $return = false, $debug = [], $errorData = [])
    {
        $origin = $this->http_input->header('origin');
        $allowOrigin = $this->commonConfig->get('crossDomainWhitelist');

        // 跨域日志记录
        if ($this->http_input->getPost('isDebug')) {
            $this->log([$origin, $allowOrigin, in_array($origin, $allowOrigin)]);
        }

        if (in_array($origin, $allowOrigin)) {
            // 如果需要设置允许所有域名发起的跨域请求，可以使用通配符 *
            // 如果要发送Cookie，Access-Control-Allow-Origin就不能设为星号，必须指定明确的、与请求网页一致的域名
            $this->http_output->setHeader('Access-Control-Allow-Origin', $origin);
            $this->http_output->setHeader('Access-Control-Allow-Credentials', 'true'); // 是否允许发送Cookie
        } else {
            $this->http_output->setHeader('Access-Control-Allow-Origin', '*');
        }

        // 允许任意域名发起的跨域请求
        $this->http_output->setHeader('Access-Control-Allow-Headers', 'X-Requested-With,X_Requested_With');
        $this->http_output->setHeader('Content-Type', 'application/json; charset=UTF-8');
        $data['code'] = $code;
        if ($code == 0) {
            $data['message'] = 'success';
            $data['data'] = $output;
        } else {
            $data['message'] = $output;
            $errorData && $data['data'] = $errorData;
        }
        if (FP_DEBUG) {
            $debugInfo['info'] = $debug;
            $debugInfo['get'] = $this->http_input->getAllGet();
            $debugInfo['post'] = $this->http_input->getAllPost();
            $debugInfo['header'] = $this->http_input->getAllHeader();

            $data['debug'] = $debugInfo;
        }

        $end = json_encode($data, JSON_UNESCAPED_UNICODE);
//        $date = date("Y-m-d h:i:sa");
        //print_r("=={$date}==============={$this->context['method_name']}====================\n");
        //print_r($end);
        //print_r("\n\n");
        if ($return) {
            return $end;
        } else {
            $this->http_output->end($end, $gzip);
        }
    }

    /**
     * 统一输出Api数据，非浏览器获取压缩数据会失败，所以代码读取的接口不压缩输出
     * @param $output
     * @param $code
     */
    protected function endNoGzip($output, $code = 0)
    {
        $this->end($output, $code, false);
    }

    /**
     * 给接口输出(抛出)异常信息
     * @param $code
     * @param string $output
     * @param array $debug
     * @param int $level
     * @throws BaseException
     */
    protected function endThrow($code, $output = '', $debug = [], $logMessage = '', $level = \Monolog\Logger::WARNING)
    {
        $errorMessage = getErrorByCode($code, $output);
        $code = $errorMessage['code'];
        $output = $errorMessage['message'];
        $message = $this->end($output, $code, false, true, $debug);
        $logMessage = $logMessage ? $logMessage : json_decode($message); // 美化日志
        //throw new BaseException($message, $code, null, $logMessage, $level);
    }

    /**
     * 获取日志
     * @return string
     */
    protected function getLog()
    {
        $log = ' PostGet：' . json_encode($this->http_input->getAllPostGet(), JSON_UNESCAPED_UNICODE);
        $log .= ' RawContent：' . json_encode($this->http_input->getRawContent(), JSON_UNESCAPED_UNICODE);
        return $log;
    }

    /**
     * 打印日志
     * @param $message
     * @param int $level
     * @param string $channel
     */
    protected function log($message, $level = Logger::DEBUG, $channel = '')
    {
        try {
            if (!$channel) {
                $path = $this->http_input->getPathInfo();
                $paths = explode('/', $path);
                $channel = $paths[1];
            }

            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }

            // create a log channel
            $log = new Logger($channel);
            $fileName = date('Y-m-d');
            $log->pushHandler(new StreamHandler(LOG_DIR . "/{$channel}/" . $fileName . '.log', Logger::DEBUG));

            // add records to the log
            $log->addRecord($level, $message, $this->getContext());

            // 严重错误日志汇总到单独文件
            if ($level >= Logger::ERROR) {
                $channel = 'Error';
                $log = new Logger($channel);
                $fileName = date('Y-m-d');
                $log->pushHandler(new StreamHandler(LOG_DIR . "/{$channel}/" . $fileName . '.log', Logger::DEBUG));

                // add records to the log
                $log->addRecord($level, $message, $this->getContext());
            }
        } catch (\Exception $e) {

        }
    }

    private function setDatabaseAsynPool()
    {
        // 设置MySql异步连接池
        if ($this->config->get('mysql.enable', true)) {
            $mySqlConfig = $this->config->get('mysql');
            foreach ($mySqlConfig as $key => $value) {
                $otherKey = ['enable', 'active']; // 不是具体数据库配置的key
                if (!in_array($key, $otherKey) && $value['enable']) {
                    $this->mysqlPool[$key] = get_instance()->getAsynPool($key);
                }

            }
        }

        // 设置Redis异步连接池
        if ($this->config->get('redis.enable', true)) {
            $redisConfig = $this->config->get('redis');
            foreach ($redisConfig as $key => $value) {
                $otherKey = ['enable', 'active']; // 不是具体数据库配置的key
                if (!in_array($key, $otherKey) && $value['enable']) {
                    $this->redisPool[$key] = get_instance()->getAsynPool('redisPool_' . $key);
                }
            }
        }
    }

    private function installMysqlAsynPool()
    {
        // 安装MySql异步连接池
        if ($this->config->get('mysql.enable', true)) {
            $mySqlConfig = $this->config->get('mysql');
            foreach ($mySqlConfig as $key => $value) {
                $otherKey = ['enable', 'active']; // 不是具体数据库配置的key
                if (!in_array($key, $otherKey) && $value['enable']) {
                    if ($this->mysqlPool[$key] != null) {
                        $this->installMysqlPool($this->mysqlPool[$key]);
                    }
                }
            }
        }
    }

    /**
     * 异常的回调(如果需要继承$autoSendAndDestroy传flase)
     * @param \Throwable $e
     * @param callable $handle
     * @return \Generator
     */
    public function onExceptionHandle(\Throwable $e, $handle = null)
    {
        if (FP_DEBUG) {
            yield parent::onExceptionHandle($e, $handle);
        } else {
            yield parent::onExceptionHandle($e, function (\Throwable $e) {
                switch ($this->request_type) {
                    case SwooleMarco::HTTP_REQUEST:
                        $this->http_output->end($e->getMessage(), false);
                        break;
                    case SwooleMarco::TCP_REQUEST:
                        $this->send($e->getMessage());
                        break;
                }
            });
        }
    }

    /**
     * 判断用户是否是在微信环境下
     */
    public function isInWeChat()
    {
        $header = $this->http_input->header('user-agent');
        if (isset($header)) {
            return strpos($header, "MicroMessenger") ? true : false;
        } else {
            return false;
        }
    }
}