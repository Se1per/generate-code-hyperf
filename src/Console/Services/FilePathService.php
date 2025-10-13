<?php

namespace Japool\Genconsole\Console\Services;

/**
 * 文件路径服务
 * 职责：处理文件路径转换和文件操作
 */
class FilePathService
{
    /**
     * 将命名空间转换为文件路径
     */
    public function namespaceToFilePath(string $namespace): string
    {
        $relativePath = str_replace('\\', '/', $namespace);
        $relativePath = str_replace('App/', 'app/', $relativePath);
        return BASE_PATH . '/' . $relativePath . '.php';
    }

    /**
     * 构建完整的类命名空间
     */
    public function buildClassNamespace(
        string $baseNamespace, 
        string $className, 
        ?string $appNamespace = null
    ): string {
        $namespace = $baseNamespace;
        
        if ($appNamespace) {
            $namespace .= '\\' . $appNamespace;
        }
        
        return $namespace . '\\' . $className;
    }

    /**
     * 检查文件是否存在
     */
    public function fileExists(string $namespace): bool
    {
        $filePath = $this->namespaceToFilePath($namespace);
        return file_exists($filePath);
    }

    /**
     * 删除文件
     */
    public function deleteFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }
        
        return unlink($filePath);
    }

    /**
     * 获取文件路径（不检查是否存在）
     */
    public function getFilePath(string $namespace): string
    {
        return $this->namespaceToFilePath($namespace);
    }
}

