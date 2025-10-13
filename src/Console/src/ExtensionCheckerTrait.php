<?php

namespace Japool\Genconsole\Console\src;

/**
 * 扩展检测 Trait
 * 职责：检测 Hyperf 扩展是否安装
 */
trait ExtensionCheckerTrait
{
    /**
     * 检测是否安装了 Swagger 扩展
     * @return bool
     */
    protected function isSwaggerExtensionInstalled(): bool
    {
        return $this->checkExtension('hyperf/swagger');
    }

    /**
     * 检测是否安装了 hyperf 自动化测试扩展
     * @return bool
     */
    protected function isTestIngExtensionInstalled(): bool
    {
        return $this->checkExtension('hyperf/testing');
    }

    /**
     * 检测是否安装了 Snowflake 扩展
     * @return bool
     */
    protected function isSnowflakeExtensionInstalled(): bool
    {
        return $this->checkExtension('hyperf/snowflake');
    }

    /**
     * 检查扩展是否安装
     */
    private function checkExtension(string $packageName): bool
    {
        static $composerLock = null;
        
        if ($composerLock === null) {
            $lockFile = BASE_PATH . '/composer.lock';
            if (!file_exists($lockFile)) {
                return false;
            }
            $composerLock = file_get_contents($lockFile);
        }
        
        return preg_match('/"name":\s+"' . preg_quote($packageName, '/') . '"/', $composerLock) === 1;
    }
}

