<?php

declare(strict_types=1);

// namespace App\Base\src;
namespace Japool\Genconsole\JsonCall\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute]
class ReturnAnnotation extends AbstractAnnotation
{
    public function __construct(
        public string $mode = 'json',
    ) {

    }
}