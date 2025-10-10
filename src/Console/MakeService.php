<?php

namespace Japool\Genconsole\Console;

use Hyperf\Command\Annotation\Command;

#[Command]
class MakeService extends AbstractCrudGenerator
{
    public function __construct()
    {
        parent::__construct('generate:crud-service');
    }

    public function configure()
    {
        $this->setDescription('Create a new service class');
        parent::configure();
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/service.stub';
    }

    protected function getClassSuffix(): string
    {
        return 'Service';
    }

    protected function getConfigKey(): string
    {
        return 'service';
    }

    protected function buildReplacements(array $context): array
    {
        return [
            '{{ class }}' => $context['camelTableName'] . 'Service',
            '{{ smallTable }}' => $context['tableName'],
            '{{ table }}' => $context['camelTableName'],
            '{{ repository }}' => $this->config['general']['repository'],
            '{{ manager }}' => $this->config['general']['manager'],
        ];
    }
}