# generate-code-hyperf

使用Hyperf框架生成代码的示例项目。该项目包含一个命令行工具，用于生成Hyperf框架下的代码。

php bin/hyperf.php vendor:publish japool/generate-code-hyperf

# 根据文档安装 validation
composer require hyperf/validation

# 添加中间件
config/autoload/middlewares.php
\Hyperf\Validation\Middleware\ValidationMiddleware::class

# 添加异常处理器
\App\Exception\Handler\ValidationExceptionHandler::class

# 生成base类文件
php bin/hyperf.php gen:generateBaseCommons

# 生成crud 代码
php bin/hyperf.php gen:crud-code