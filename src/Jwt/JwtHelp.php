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
// 引入jwt
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Hyperf\Config\Annotation\Value;
use InvalidArgumentException;
use UnexpectedValueException;
use function Hyperf\Support\env;
use Hyperf\Contract\ConfigInterface;

class JwtHelp
{
    private $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config->get('generator.jwt');
    }
    
    public function make(array $userInfo, &$expiresIn = null,$audUrl = null,$XForwarded = null): array
    {
        if ($expiresIn) {
            $start = time();
            $end = $start + $expiresIn;
            $exp = $expiresIn;
        } else {
            $start = time();
            $end = $start + $this->config['exp'];
            $exp = $this->config['exp'];
        }

        $expiresIn = $end;

        $payload = [
            // 在这个例子中，http://example.org 表示 JWT 是由该 URL 所标识的实体签发的。
            'iss' => env('APP_NAME','http://example.com'),//发行者 (Issuer)
            'aud' => $audUrl == null ? env('APP_NAME','http://example.com') : $audUrl,//接收者 (Audience)，例如API服务的URL
            'iat' => $start,  //签发时间 (Issued At)
            'nbf' => $start, // 生效时间 (Not Before)
            'exp' => $end,   // 过期时间 (Expiration Time)
            'sub' => $userInfo['id'], // 主题 (Subject)，例如用户的唯一标识符
            'info' => $userInfo,
        ];

        $headers = [];

        if($XForwarded){
            $headers = $XForwarded;
        }

        $token = JWT::encode($payload, $this->config['secret'],$this->config['algorithm'], null, $headers);

        // Encode headers in the JWT string
        return [$token,$exp];
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
            return [false,CodeConstants::TOKEN_INVALID];
        } catch (DomainException $e) {
            // 提供的算法不受支持或
            // 提供的密钥无效或
            // openSSL或libsodium or中引发未知错误
            // 需要libsodium，但不可用。
//            throw new DomainException($e->getMessage());
            return [false,CodeConstants::TOKEN_INVALID];
        } catch (SignatureInvalidException $e) {
            // 提供的JWT签名验证失败。
//            throw new SignatureInvalidException($e->getMessage());
            return [false,CodeConstants::TOKEN_INVALID];

        } catch (BeforeValidException $e) {
            // 前提是JWT试图在“nbf”索赔或
            // 前提是JWT试图在“iat”索赔之前使用。
//            throw new BeforeValidException($e->getMessage());
            return [false,CodeConstants::TOKEN_TIME_OUT];

        } catch (ExpiredException $e) {
            // 前提是JWT试图在“exp”索赔后使用。
            return [false,CodeConstants::TOKEN_TIME_OUT];
        } catch (UnexpectedValueException $e) {
            // 前提是JWT格式错误或
            // 假设JWT缺少算法/使用了不受支持的算法OR
            // 提供的JWT算法与提供的密钥OR不匹配
            // 在密钥/密钥数组中提供的密钥ID为空或无效。
            return [false,CodeConstants::TOKEN_MATCH];
        } catch (Exception $e) {  // 其他错误
//            throw new ExpiredException($e->getMessage());
            return [false,CodeConstants::TOKEN_MATCH];
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
