# Generate Code Hyperf

<p align="center">
    <a href="https://packagist.org/packages/japool/generate-code-hyperf"><img src="https://img.shields.io/packagist/v/japool/generate-code-hyperf.svg" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/japool/generate-code-hyperf"><img src="https://img.shields.io/packagist/dt/japool/generate-code-hyperf.svg" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/japool/generate-code-hyperf"><img src="https://img.shields.io/packagist/l/japool/generate-code-hyperf.svg" alt="License"></a>
    <a href="https://packagist.org/packages/japool/generate-code-hyperf"><img src="https://img.shields.io/packagist/php-v/japool/generate-code-hyperf.svg" alt="PHP Version"></a>
</p>

## 简介

`japool/generate-code-hyperf` 是一个为 Hyperf 3.1+ 框架设计的强大代码生成器和开发辅助工具包。它提供了完整的 CRUD 代码自动生成、日志监控、缓存管理、JWT 认证等企业级开发所需的核心功能。

### 核心特性

- 🚀 **智能 CRUD 生成器** - 一键生成 Controller、Manager、Service、Repository、Request、Model 完整分层架构
- 📊 **性能监控系统** - 方法执行时间监控、慢查询自动捕获、调用链追踪
- 💾 **注解式缓存** - 基于注解的缓存管理，支持缓存事件监听和自动更新
- 🔐 **JWT 认证** - 开箱即用的 JWT Token 认证中间件
- 📝 **多通道日志** - API、业务、SQL、支付等多场景日志分类管理
- 🛠️ **丰富的工具集** - AES 加密、地理数据、Excel 处理、身份证验证等常用工具

## 环境要求

- PHP >= 8.1
- Hyperf >= 3.1
- Redis 扩展
- MySQL/PostgreSQL

## 安装

### 1. 安装依赖包

```bash
# 安装核心依赖
composer require hyperf/validation
composer require hyperf/constants
composer require hyperf/model-cache
composer require firebase/php-jwt

# [可选] 安装测试工具
composer require hyperf/testing --dev

# 安装代码生成器
composer require japool/generate-code-hyperf
```

### 2. 发布配置文件

```bash
php bin/hyperf.php vendor:publish japool/generate-code-hyperf
```

该命令将自动生成以下文件：

- `config/autoload/generator.php` - 生成器配置文件
- `app/Base/` - 基础类目录（BaseController、BaseService、BaseRepository 等）
- `app/Exception/Handler/` - 异常处理器
- `app/Middleware/` - 中间件（请求日志、JWT 认证）
- `app/Constants/CodeConstants.php` - 常量定义
- `app/Listener/ValidatorFactoryResolvedListener.php` - 验证器监听器

### 3. 配置中间件

在 `config/autoload/middlewares.php` 中添加：

```php
<?php
return [
    'http' => [
        // 请求日志中间件（必需）
        App\Middleware\RequestMiddleware::class,
        
        // JWT Token 认证中间件（可选）
        App\Middleware\JwtTokenMiddleware::class,
        
        // 验证中间件（必需）
        Hyperf\Validation\Middleware\ValidationMiddleware::class,
    ],
];
```

### 4. 配置异常处理器

在 `config/autoload/exceptions.php` 中添加：

```php
<?php
return [
    'handler' => [
        'http' => [
            // 验证异常处理器
            App\Exception\Handler\ValidationExceptionHandler::class,
            // API 异常处理器
            App\Exception\Handler\ApiExceptionHandler::class,
            // 框架默认异常处理器
            Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler::class,
        ],
    ],
];
```

### 5. 配置依赖注入

在 `config/autoload/dependencies.php` 中添加：

```php
<?php
return [
    Japool\Genconsole\ReturnCall\JsonCallBackInterface::class => Japool\Genconsole\ReturnCall\JsonCallBack::class,
];
```

## 使用指南

### CRUD 代码生成

#### 批量生成所有表

```bash
php bin/hyperf.php generate:crud-code
```
该命令会：
1. 扫描数据库中所有表
2. 显示可选择的表列表
3. 自动生成完整的六层架构代码

#### 生成指定表

```bash
php bin/hyperf.php generate:crud-code user
```
#### 删除已生成的代码
```bash
php bin/hyperf.php generate:del-crud-code user
```

### 生成的代码结构
```php
use Japool\Genconsole\Logger\Annotation\MonitorExecutionAnnotation;

class UserService extends BaseService
{
    // 监控执行时间，超过 100ms 记录为慢查询
    #[MonitorExecutionAnnotation(threshold: 100, name: "用户查询")]
    public function getUserList(array $params): array
    {
        // 业务逻辑...
    }
    
    // 总是记录日志，不设阈值
    #[MonitorExecutionAnnotation(alwaysLog: true, name: "支付处理")]
    public function processPayment(array $data): bool
    {
        // 支付逻辑...
    }
}
```

**参数说明：**
- `threshold`: 慢执行阈值（毫秒），默认 100ms
- `name`: 监控名称，用于日志识别
- `alwaysLog`: 是否总是记录，默认 false
- `level`: 日志级别（info/warning/error），默认 warning

#### 2. 数据库慢查询监控

自动捕获超过阈值的 SQL 查询，配置文件 `config/autoload/generator.php`：

```php
return [
    // 慢查询阈值（毫秒）
    'slow_query_threshold' => 100,
];
```

慢查询日志将自动记录到 `runtime/logs/slow-execution/` 目录。

#### 3. 多通道日志

在 `config/autoload/generator.php` 中配置：

```php
'logger' => [
    'api' => [
        'level' => Logger::INFO,
        'max_files' => 14,
        'dir' => BASE_PATH . '/runtime/logs/api',
    ],
    'sql' => [
        'level' => Logger::INFO,
        'max_files' => 7,
        'dir' => BASE_PATH . '/runtime/logs/sql',
        'use_json' => true,
    ],
    'execution' => [
        'dir' => BASE_PATH . '/runtime/logs/execution',
        'max_files' => 30,
    ],
    'slow-execution' => [
        'dir' => BASE_PATH . '/runtime/logs/slow-execution',
        'use_json' => true,
    ],
    'payment' => [
        'max_files' => 365,  // 支付日志保留 1 年
        'dir' => BASE_PATH . '/runtime/logs/payment',
        'use_json' => true,
    ],
],
```

**使用示例：**

```php
use Japool\Genconsole\Logger\LoggerFactory;

$logger = LoggerFactory::get('payment');
$logger->info('支付成功', [
    'order_id' => '123456',
    'amount' => 99.00,
]);
```

### 注解式缓存系统（增强功能）

#### 1. 基础缓存注解

```php
use Japool\Genconsole\Cache\Annotation\CacheAnnotation;

class UserService extends BaseService
{
    // 基础缓存配置
    #[CacheAnnotation(prefix: 'user', ttl: 3600)]
    public function getUserInfo(int $userId): array
    {
        return $this->repository->find($userId);
    }
    
    // 分组缓存
    #[CacheAnnotation(prefix: 'user:list', group: 'users', ttl: 1800)]
    public function getUserList(array $params): array
    {
        return $this->repository->getList($params);
    }
    
    // 自定义驱动和连接
    #[CacheAnnotation(
        prefix: 'user:hot', 
        drive: 'redis', 
        settings: 'cache',
        ttl: 7200
    )]
    public function getHotUsers(): array
    {
        return $this->repository->getHotUsers();
    }
}
```

**参数说明：**
- `prefix`: 缓存键前缀（必需）
- `group`: 缓存分组，用于批量清理
- `ttl`: 缓存时间（秒），默认 3600
- `drive`: 缓存驱动（redis/memory），默认 redis
- `settings`: Redis 连接配置名，默认 default
- `listener`: 缓存事件监听器

#### 2. 缓存事件注解

```php
use Japool\Genconsole\Cache\Annotation\CacheEventAnnotation;

class UserService extends BaseService
{
    // 更新数据时自动清理相关缓存
    #[CacheEventAnnotation(prefix: 'user', action: 'delete')]
    public function updateUser(int $userId, array $data): bool
    {
        return $this->repository->update($userId, $data);
    }
    
    // 删除数据时清理分组缓存
    #[CacheEventAnnotation(prefix: 'user:list', group: 'users', action: 'flush')]
    public function deleteUser(int $userId): bool
    {
        return $this->repository->delete($userId);
    }
}
```

### JWT 认证

#### 1. 配置

在 `config/autoload/generator.php` 中配置：

```php
'jwt' => [
    'secret' => env('JWT_SECRET', 'your-secret-key'),
    'algorithm' => 'HS256',
    'exp' => env('JWT_TOKEN_TIME_OUT', 3600),
    // 排除的路由（不需要验证）
    'exclude' => [
        '/api/login/loginApi',
        '/api/register',
    ],
    // 信任的代理 IP
    'x-forwarded-for' => [
        '127.0.0.1'
    ]
],
```

#### 2. 生成 Token

```php
use Japool\Genconsole\Jwt\JwtHelp;

$payload = [
    'user_id' => 1001,
    'username' => 'admin',
    'role' => 'admin',
];

$token = JwtHelp::generateToken($payload);
```

#### 3. 验证 Token

```php
use Japool\Genconsole\Jwt\JwtHelp;

try {
    $payload = JwtHelp::verifyToken($token);
    $userId = $payload['user_id'];
} catch (\Exception $e) {
    // Token 无效或已过期
}
```

#### 4. 获取当前用户信息

```php
use Japool\Genconsole\Jwt\JwtHelp;

// 在控制器或服务中
$userInfo = JwtHelp::getPayload();
$userId = $userInfo['user_id'];
```

### 统一返回格式（ReturnCall）

#### 使用注解

```php
use Japool\Genconsole\ReturnCall\Annotation\ReturnAnnotation;

class UserController extends BaseController
{
    #[ReturnAnnotation(mode: 'json')]
    public function index()
    {
        $data = $this->service->getUserList();
        
        // 直接返回数组，自动包装为标准格式
        return [
            'list' => $data,
            'total' => count($data),
        ];
    }
}
```

#### 手动调用

```php
use Japool\Genconsole\ReturnCall\JsonCallBack;

class UserController extends BaseController
{
    public function index()
    {
        $data = $this->service->getUserList();
        
        // 成功响应
        return JsonCallBack::success($data, '获取成功');
        
        // 失败响应
        return JsonCallBack::error('数据不存在', 404);
    }
}
```

**标准返回格式：**

```json
{
    "code": 200,
    "message": "success",
    "data": {
        "list": [],
        "total": 10
    },
}
```

### 辅助工具集

#### 1. AES 加密解密

```php
use Japool\Genconsole\Help\src\AesTrait;

class SomeClass
{
    use AesTrait;
    
    public function test()
    {
        $key = 'your-secret-key';
        $encrypted = $this->aesEncrypt('hello', $key);
        $decrypted = $this->aesDecrypt($encrypted, $key);
    }
}
```

#### 2. 地理数据工具

```php
use Japool\Genconsole\Help\src\GeographyTrait;

class LocationService
{
    use GeographyTrait;
    
    public function getDistance()
    {
        // 计算两点间距离（公里）
        $distance = $this->getDistanceBetweenPoints(
            39.9042, 116.4074,  // 北京坐标
            31.2304, 121.4737   // 上海坐标
        );
    }
}
```

#### 3. 日期时间工具

```php
use Japool\Genconsole\Help\src\DateTimeTrait;

class ReportService
{
    use DateTimeTrait;
    
    public function generate()
    {
        // 获取今天开始和结束时间戳
        [$start, $end] = $this->getTodayRange();
        
        // 获取本周、本月范围
        [$weekStart, $weekEnd] = $this->getWeekRange();
        [$monthStart, $monthEnd] = $this->getMonthRange();
    }
}
```

#### 4. 数组工具

```php
use Japool\Genconsole\Help\src\ArrayTrait;

class DataService
{
    use ArrayTrait;
    
    public function process()
    {
        $data = [...];
        
        // 数组转树形结构
        $tree = $this->arrayToTree($data, 'id', 'parent_id');
        
        // 多维数组按字段排序
        $sorted = $this->arraySortByField($data, 'created_at', SORT_DESC);
    }
}
```

#### 5. Redis 分布式锁

```php
use Japool\Genconsole\Help\RedisLock;

class OrderService
{
    public function createOrder(array $data)
    {
        $lock = new RedisLock('order:create:' . $data['user_id']);
        
        try {
            // 获取锁，等待 5 秒，锁持有 10 秒
            if ($lock->acquire(5000, 10000)) {
                // 业务逻辑...
                return true;
            }
            throw new \Exception('操作太频繁，请稍后再试');
        } finally {
            $lock->release();
        }
    }
}
```

#### 6. Excel 导出

```php
use Japool\Genconsole\Help\XlsWriteMain;

class ExportService
{
    public function exportUsers()
    {
        $data = [
            ['name' => '张三', 'age' => 25, 'email' => 'zhangsan@example.com'],
            ['name' => '李四', 'age' => 30, 'email' => 'lisi@example.com'],
        ];
        
        $headers = ['姓名', '年龄', '邮箱'];
        
        $excel = new XlsWriteMain();
        $excel->export($data, $headers, '用户列表.xlsx');
    }
}
```

#### 7. 身份证验证

```php
use Japool\Genconsole\Help\IdCardHelp;

$idCard = '110101199001011234';

// 验证身份证号
if (IdCardHelp::validate($idCard)) {
    // 获取生日
    $birthday = IdCardHelp::getBirthday($idCard);
    
    // 获取性别
    $gender = IdCardHelp::getGender($idCard);  // male/female
    
    // 获取年龄
    $age = IdCardHelp::getAge($idCard);
}
```
## 版本更新说明
#### 新增功能
- ✅ **Manager 业务层** - 新增业务逻辑编排层，实现更清晰的分层架构
- ✅ **性能监控系统** - 方法执行时间监控、慢查询自动捕获
- ✅ **抽象生成器** - AbstractCrudGenerator 抽象类，统一代码生成逻辑
- ✅ **多通道日志** - 支持 API、SQL、执行、支付等多种日志分类
- ✅ **缓存事件** - CacheEventAnnotation 支持自动缓存更新和清理
- ✅ **Repository 接口** - RepositoryPackageInterface 规范数据层
- ✅ **BaseManager** - 业务层基类，提供通用业务方法

#### 改进功能
- 🔄 **代码生成器** - 重构为统一的抽象类架构，易于扩展
- 🔄 **缓存系统** - 增强缓存注解功能，支持分组和事件
- 🔄 **日志系统** - 支持 JSON 格式日志，便于日志分析
- 🔄 **配置文件** - 更完善的配置选项和文档

#### 保持功能
- ✓ CRUD 代码自动生成
- ✓ JWT Token 认证
- ✓ 模型缓存支持
- ✓ Service 层缓存模块
- ✓ 统一返回格式处理
- ✓ 辅助工具集

### v1.x - 原始版本
- CRUD 代码生成
- JWT 认证
- 基础缓存
- 辅助工具

## 后续规划
 - 后面再说

### 社区建议

欢迎提交 Issue 和 Pull Request，你的建议和贡献将帮助项目变得更好！

## 常见问题

### Q: 如何自定义生成的代码模板？
A: 模板文件位于 `src/Console/stubs/` 目录，你可以修改 `.stub` 文件来自定义生成的代码格式。

### Q: 生成的代码是否支持 Swagger 文档？
A: 是的，在 `controllerSwagger.stub` 模板中已包含 Swagger 注解支持。

### Q: 如何处理中间表（关联表）？
A: 在 `config/autoload/generator.php` 的 `intermediate_table` 配置中添加中间表名称，生成器会自动跳过这些表。

### Q: 缓存如何手动清理？
A: 可以使用 Redis 命令或通过代码调用缓存驱动的清理方法。

### Q: 支持哪些数据库？
A: 目前支持 MySQL 和 PostgreSQL，其他数据库需要测试验证。

## 参考资源

- [Hyperf 官方文档](https://hyperf.wiki/)
- [Firebase PHP-JWT](https://github.com/firebase/php-jwt)
- [中国大学数据](https://github.com/WenryXu/ChinaUniversity)
- [中国行政区划数据](https://github.com/modood/Administrative-divisions-of-China)

## 许可证
本项目采用 Proprietary 许可证。

## 作者
Japool

## 贡献
欢迎提交 Pull Request 或 Issue！

## 更新日志

### 2024-10-10
- 重构代码生成器架构
- 新增 Manager 业务层
- 新增性能监控系统
- 完善日志系统
- 更新文档

---

如有问题或建议，请提交 [Issue](https://github.com/yourusername/generate-code-hyperf/issues)