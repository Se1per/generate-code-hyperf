
# 根据文档安装 validation
composer require hyperf/validation
# 根据文档安装 constants 
composer require hyperf/constants
# 根据文档安装 model-cache 
composer require hyperf/model-cache
# [可选]安装 firebase/php-jwt
composer require firebase/php-jwt
# [可选] 自动化测试
composer require hyperf/testing --dev

# 安装生成器代码 generate-code-hyperf
composer require japool/generate-code-hyperf

# 生成配置依赖
php bin/hyperf.php vendor:publish japool/generate-code-hyperf


# 添加中间件 config/autoload/middlewares.php
Hyperf\Validation\Middleware\ValidationMiddleware::class,
# 添加请求日志记录中间件 config/autoload/middlewares.php
Japool\Genconsole\RequestLog\RequestMiddleware::class,

# 添加jwt中间件(可选) config/autoload/middlewares.php
App\Middleware\JwtTokenMiddleware::class,

# 添加验证器异常处理器
App\Exception\Handler\ValidationExceptionHandler::class
# 添加api 接口异常处理器
App\Exception\Handler\ApiExceptionHandler::class, 

# 添加抽象对象注入 config/autoload/dependencies.php
Japool\Genconsole\ReturnCall\JsonCallBackInterface::class => Japool\Genconsole\ReturnCall\JsonCallBack::class

# 生成crud 代码
php bin/hyperf.php generate:crud-code

# 生成 user 表crud 代码
php bin/hyperf.php generate:crud-code user_name


# json 更新地址
https://github.com/WenryXu/ChinaUniversity
https://github.com/modood/Administrative-divisions-of-China/tree/master

# 依赖jwt
https://github.com/firebase/php-jwt

#test 错误 还没改
#版本号区分

# 已添加
自动生成crud 
jwt 引入
模型缓存启用
service 层缓存 模块 

# 代办 
冷热缓存淘汰
缓存更新
待定
.....