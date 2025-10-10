<?php

namespace Japool\Genconsole\Console;

use Hyperf\Command\Annotation\Command;

#[Command]
class MakeManager extends AbstractCrudGenerator
{
    public function __construct()
    {
        parent::__construct('generate:crud-manager');
    }

    public function configure()
    {
        $this->setDescription('Create a new Manager class');
        parent::configure();
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/manager.stub';
    }

    protected function getClassSuffix(): string
    {
        return 'Manager';
    }

    protected function getConfigKey(): string
    {
        return 'manager';
    }

    protected function buildReplacements(array $context): array
    {
        return [
            '{{ class }}' => $context['camelTableName'] . 'Manager',
        ];
    }
}