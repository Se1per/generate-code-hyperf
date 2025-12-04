<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */


namespace Japool\Genconsole\Jwt;

use App\Constants\CodeConstants;
use DomainException;
use Exception;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Hyperf\Contract\ConfigInterface;
use Japool\Genconsole\Logger\LoggerFactory;
use InvalidArgumentException;
use Monolog\Logger;
use UnexpectedValueException;
use function Hyperf\Support\env;

class JwtHelp
{
    private array $config;
    private Logger $logger;

    public function __construct(ConfigInterface $config, LoggerFactory $loggerFactory)
    {
        $this->config = $config->get('generator.jwt', []);
        $this->logger = $loggerFactory->get('api-error');
        $this->validateConfig();
    }

    /**
     * 验证配置是否完整
     * @throws InvalidArgumentException
     */
    private function validateConfig(): void
    {
        if (empty($this->config['secret'])) {
            throw new InvalidArgumentException('JWT secret is required in config');
        }
        if (empty($this->config['algorithm'])) {
            throw new InvalidArgumentException('JWT algorithm is required in config');
        }
        if (!isset($this->config['exp']) || $this->config['exp'] <= 0) {
            throw new InvalidArgumentException('JWT expiration time must be greater than 0');
        }
    }
    
    /**
     * 生成JWT Token
     * @param array $userInfo 用户信息，必须包含 'id' 字段
     * @param int|null $expiresIn 过期时间（秒），如果为null则使用配置中的默认值
     * @param string|null $audUrl 接收者URL
     * @param array|null $XForwarded 自定义请求头
     * @return array [token, expiresIn]
     * @throws InvalidArgumentException
     */
    public function make(array $userInfo, ?int &$expiresIn = null, ?string $audUrl = null, ?array $XForwarded = null): array
    {
        // 验证用户信息
        if (empty($userInfo['id'])) {
            throw new InvalidArgumentException('User info must contain "id" field');
        }

        $start = time();
        
        if ($expiresIn !== null && $expiresIn > 0) {
            $end = $start + $expiresIn;
            $exp = $expiresIn;
        } else {
            $end = $start + $this->config['exp'];
            $exp = $this->config['exp'];
        }

        $expiresIn = $end;

        $payload = [
            'iss' => env('APP_NAME', 'http://example.com'), // 发行者 (Issuer)
            'aud' => $audUrl ?? env('APP_NAME', 'http://example.com'), // 接收者 (Audience)
            'iat' => $start,  // 签发时间 (Issued At)
            'nbf' => $start,  // 生效时间 (Not Before)
            'exp' => $end,    // 过期时间 (Expiration Time)
            'sub' => $userInfo['id'], // 主题 (Subject)
            'info' => $userInfo,
        ];

        $headers = $XForwarded ?? [];

        try {
            $token = JWT::encode($payload, $this->config['secret'], $this->config['algorithm'], null, $headers);
            return [$token, $exp];
        } catch (Exception $e) {
            $this->logger->error('JWT token generation failed', [
                'error' => $e->getMessage(),
                'user_id' => $userInfo['id'] ?? null,
            ]);
            throw new InvalidArgumentException('Failed to generate JWT token: ' . $e->getMessage());
        }
    }

    /**
     * 解码并验证JWT Token
     * @param string $token JWT Token字符串
     * @return array [status, decoded|errorCode] status为true时返回解码后的数据，false时返回错误码
     */
    public function decodeJwtToken(string $token): array
    {
        // 验证token是否为空
        if (empty($token) || !is_string($token)) {
            return [false, CodeConstants::TOKEN_INVALID];
        }

        // 验证token格式（JWT应该包含两个点分隔符）
        if (substr_count($token, '.') !== 2) {
            return [false, CodeConstants::TOKEN_INVALID];
        }

        try {
            $decoded = JWT::decode(
                $token,
                new Key($this->config['secret'], $this->config['algorithm'])
            );

            return [true, (array) $decoded];
        } catch (InvalidArgumentException $e) {
            // 提供的密钥/密钥数组为空或格式不正确
            $this->logger->warning('JWT decode failed: Invalid argument', [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 20) . '...',
            ]);
            return [false, CodeConstants::TOKEN_INVALID];
        } catch (DomainException $e) {
            // 提供的算法不受支持或密钥无效或OpenSSL/libsodium错误
            $this->logger->warning('JWT decode failed: Domain exception', [
                'error' => $e->getMessage(),
                'algorithm' => $this->config['algorithm'],
            ]);
            return [false, CodeConstants::TOKEN_INVALID];
        } catch (SignatureInvalidException $e) {
            // JWT签名验证失败
            $this->logger->warning('JWT decode failed: Invalid signature', [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 20) . '...',
            ]);
            return [false, CodeConstants::TOKEN_ILLICIT];

        } catch (BeforeValidException $e) {
            // JWT在nbf或iat之前使用
            $this->logger->info('JWT decode failed: Token not yet valid', [
                'error' => $e->getMessage(),
            ]);
            return [false, CodeConstants::TOKEN_TIME_OUT];

        } catch (ExpiredException $e) {
            // JWT已过期
            $this->logger->info('JWT decode failed: Token expired', [
                'error' => $e->getMessage(),
            ]);
            return [false, CodeConstants::TOKEN_TIME_OUT];
        } catch (UnexpectedValueException $e) {
            // JWT格式错误或算法不匹配
            $this->logger->warning('JWT decode failed: Unexpected value', [
                'error' => $e->getMessage(),
                'token_preview' => substr($token, 0, 20) . '...',
            ]);
            return [false, CodeConstants::TOKEN_INVALID];
        } catch (Exception $e) {
            // 其他未知错误
            $this->logger->error('JWT decode failed: Unexpected exception', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'token_preview' => substr($token, 0, 20) . '...',
            ]);
            return [false, CodeConstants::TOKEN_INVALID];
        }
    }

    /**
     * 检查路由是否在白名单中
     * @param string $routeUrl 路由URL
     * @return bool true表示在白名单中，false表示不在
     */
    public function whiteRouteList(string $routeUrl): bool
    {
        $excludeList = $this->config['exclude'] ?? [];
        return in_array($routeUrl, $excludeList, true);
    }
}
