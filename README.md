# generate-code-hyperf

使用Hyperf框架生成代码的示例项目。该项目包含一个命令行工具，用于生成Hyperf框架下的代码。

php bin/hyperf.php vendor:publish japool/generate-code-hyperf

# 根据文档安装 validation
composer require hyperf/validation

# 添加中间件 config/autoload/middlewares.php
\Hyperf\Validation\Middleware\ValidationMiddleware::class

# 添加验证器异常处理器
\App\Exception\Handler\ValidationExceptionHandler::class
# 添加api(生产) 接口异常处理器
\App\Exception\Handler\ApiExceptionHandler::class, 
# 添加api(测试) 接口异常处理器
\App\Exception\Handler\ApiDeBugExceptionHandler::class,

# [可选] 自动化测试
composer require hyperf/testing --dev

# 添加抽象对象注入 config/autoload/dependencies.php
\Japool\Genconsole\JsonCall\JsonCallBackInterFace::class => \Japool\Genconsole\JsonCall\JsonCallBack::class

# 生成base类文件 (已取消)
# php bin/hyperf.php generate:generateBaseCommons

# 生成crud 代码
php bin/hyperf.php generate:crud-code

# 生成user 表crud 代码
php bin/hyperf.php generate:crud-code user