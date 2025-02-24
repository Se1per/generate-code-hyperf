<?php

declare(strict_types=1);

// namespace App\Base\src;
namespace Japool\Genconsole\Cache\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;
use phpseclib3\Math\PrimeField\Integer;

#[Attribute]
class CacheEventAnnotation extends AbstractAnnotation
{
    public function __construct(
        public string $prefix,
        public string|null $group = null,
        public string|null $listener = null,
        public string|null $drive = 'redis',
        public string|null $settings = 'default',
    ) {

    }
}