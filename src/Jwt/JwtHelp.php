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

namespace Japool\HyperfHelpFunc\Jwt;

use DomainException;
use Exception;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
// 引入jwt
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Hyperf\Config\Annotation\Value;
use InvalidArgumentException;
use UnexpectedValueException;

trait JwtHelp
{
    #[Value('generator.jwt')]
    private $config;

    public function make(array $userInfo, &$expiresIn = null): string
    {
        if ($expiresIn) {
            $start = time();
            $end = $start + $expiresIn;
        } else {
            $start = time();
            $end = $start + $this->config['exp'];
        }

        $expiresIn = $end;

        $payload = [
            // 在这个例子中，http://example.org 表示 JWT 是由该 URL 所标识的实体签发的。
            'iss' => 'http://example.org',
            // http://example.com 表示这个 JWT 是为该 URL 所标识的实体设计的，通常接收并验证 JWT 的服务应该与这个值匹配
            'aud' => 'http://example.com',
            // 这是 JWT 签发的时间戳，表示 JWT 被创建的日期和时间。在这里，1356999524 是一个Unix时间戳，它对应于2012年12月28日的某个时间点
            'iat' => $start,
            // 这个声明指定了 JWT 在什么时间之后才有效。1357000000 也是一个Unix时间戳，表示 JWT 在此时间之后才能被接受和处理。如果在 nbf 时间之前尝试使用 JWT，验证将会失败。
            'nbf' => $start,
            'exp' => $end,
            'info' => $userInfo,
        ];

        $headers = $this->config['x-forwarded-for'];

        // Encode headers in the JWT string
        return JWT::encode($payload, $this->config['secret'],$this->config['algorithm'], null, $headers);
    }

    public function decodeJwtToken($token): array
    {
//        JWT::$leeway = 60; // 当前时间减去60，把时间留点余地

        try {
            $decoded = JWT::decode($token,new Key($this->config['secret'], 'HS256'));

//            $decoded = JWT::decode($token, new Key($this->config['secret'], $this->config['algorithm'])); // HS256方式，这里要和签发的时候对应
//
//            list($headersB64, $payloadB64, $sig) = explode('.', $token);
//            $decoded = json_decode(base64_decode($headersB64), true);

        } catch (InvalidArgumentException $e) {
            // 提供的密钥/密钥数组为空或格式不正确。
//            throw new SignatureInvalidException($e->getMessage());
            return [false,300002];
        } catch (DomainException $e) {
            // 提供的算法不受支持或
            // 提供的密钥无效或
            // openSSL或libsodium or中引发未知错误
            // 需要libsodium，但不可用。
//            throw new DomainException($e->getMessage());
            return [false,300002];
        } catch (SignatureInvalidException $e) {
            // 提供的JWT签名验证失败。
//            throw new SignatureInvalidException($e->getMessage());
            return [false,300002];
        } catch (BeforeValidException $e) {
            // 前提是JWT试图在“nbf”索赔或
            // 前提是JWT试图在“iat”索赔之前使用。
//            throw new BeforeValidException($e->getMessage());
            return [false,300001];
        } catch (ExpiredException $e) {
            // 前提是JWT试图在“exp”索赔后使用。
            return [false,300001];
        } catch (UnexpectedValueException $e) {
            // 前提是JWT格式错误或
            // 假设JWT缺少算法/使用了不受支持的算法OR
            // 提供的JWT算法与提供的密钥OR不匹配
            // 在密钥/密钥数组中提供的密钥ID为空或无效。
            return [false,300002];
        } catch (Exception $e) {  // 其他错误
//            throw new ExpiredException($e->getMessage());
            return [false,300003];
        }

        return [true,(array) $decoded];
    }

    public function whiteRouteList($routeUrl): bool
    {
        if (in_array($routeUrl, $this->config['exclude'])) {
            return true;
        }
        return false;
    }
}
