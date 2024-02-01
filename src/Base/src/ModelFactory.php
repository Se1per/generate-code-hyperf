<?php
namespace App\Lib\Base\src;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Database\Model\Model;
use Psr\Container\ContainerInterface as PsrContainerInterface;

class ModelFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * 根据数据表名获取对应的模型类
     */
    public function makeModel(string $tableName): ?Model
    {
        $modelName = $this->getModelName($tableName);

        if (! empty($modelName) && class_exists($modelName)) {
            return $this->container->make($modelName);
        }

        return null;
    }

    /**
     * 获取模型类名称
     */
    protected function getModelName(string $tableName): ?string
    {
        // 在此处定义数据表名和模型类的对应关系
        $modelMap = [
            'users' => \App\Models\User::class,
            // 其他数据表对应的模型类
        ];

        return $modelMap[$tableName] ?? null;
    }
}
