<?php
namespace ank;

use Swoole\Http\Server;

/**
 * ank框架swoole入口类
 */
class Swoole
{
    public static function start()
    {
        global $_SERVER, $_GET, $_POST, $loader;
        $http = new Swoole\Http\Server('0.0.0.0', 9501);
        //worker_num设置启动的worker进程数
        $http->set(
            [
                // 0 =>DEBUG // all the levels of log will be recorded
                // 1 =>TRACE
                // 2 =>INFO
                // 3 =>NOTICE
                // 4 =>WARNING
                // 5 =>ERROR
                'log_level'             => 0,
                'log_file'              => __DIR__ . '/swoole.log',
                'enable_coroutine'      => false, // 是否自动开启协程 默认 true
                'enable_static_handler' => true,
                'document_root'         => './',
                'worker_num'            => 1,
            ]
        );
        $http->on('start', function ($server) {
            // echo "Swoole http server is started at http://127.0.0.1:9501\n";
        });
        $http->on('WorkerStart', function (swoole_server $server, $worker_id) {
            //包含自动加载类
            global $loader;
            $loader = require __DIR__ . '/../vendor/autoload.php';
            define('SITE_ROOT', str_replace('\\', '/', __DIR__));
            define('IS_SWOOLE', true);
            // echo 'loader init!' . "\n";
            //框架入口
            // \ank\App::start();
        });
        $http->on('request', function ($request, $response) {
            // echo 'request' . PHP_EOL;
            global $_SERVER, $_GET, $_POST;

            $_SERVER = [];
            if (isset($request->server)) {
                foreach ($request->server as $k => $v) {
                    $_SERVER[strtoupper($k)] = $v;
                }
            }
            //swoole对于超全局数组并不会释放，所以要先清空一次
            $_GET = [];
            if (isset($request->get)) {
                foreach ($request->get as $k => $v) {
                    $_GET[$k] = $v;
                }
            }

            $_POST = [];
            if (isset($request->post)) {
                foreach ($request->post as $k => $v) {
                    $_POST[$k] = $v;
                }
            }
            // $response->end('<h1>Hello Swoole. #' . rand(1000, 9999) . '</h1>');
            if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
                return $response->end();
            }
            $res = '';
            try {
                $res = \ank\App::start();
                if ($res instanceof \ank\Response) {
                    $headers = $res->getHeader();
                    // echo json_encode($headers);
                    // \ank\Log::write(json_encode($headers), 'swoole');
                    foreach ($headers as $key => $value) {
                        $response->header($key, $value);
                    }
                    $response->status($res->getCode());
                    $response->end($res->getContent());
                } else {
                    $response->status(200);
                    $response->end('');
                }
            } catch (\ank\exception\HttpResponseException $e) {
                $data = $e->getResponse();
                $res .= $e->getMessage() . 'file:' . $e->getfile() . '; line:' . $e->getLine();
                $response->status(200);
                $response->end($data->getContent());
            }
        });
        $http->start();

    }
}
