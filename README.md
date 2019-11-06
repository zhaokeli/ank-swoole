# ank-swoole
## 使用方法
在站点根目录创建一个server.php入口文件输入下面代码启动
```
global $_SERVER, $_GET, $_POST, $loader;
$loader = require __DIR__ . '/../vendor/autoload.php';
define('SITE_ROOT', str_replace('\\', '/', __DIR__));
define('IS_SWOOLE', true);
\ank\Swoole::start(9501);
```