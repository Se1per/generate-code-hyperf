<?php

namespace Japool\Genconsole\Console;

use Hyperf\Command\Annotation\Command;

#[Command]
class MakeRepository extends AbstractCrudGenerator
{
    public function __construct()
    {
        parent::__construct('generate:crud-repository');
    }

    public function configure()
    {
        $this->setDescription('Create a new repository class');
        parent::configure();
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/repository.stub';
    }

    protected function getClassSuffix(): string
    {
        return 'Repository';
    }

    protected function getConfigKey(): string
    {
        return 'repository';
    }

    protected function buildReplacements(array $context): array
    {
        return [
            '{{ class }}' => $context['camelTableName'] . 'Repository',
            '{{ table }}' => $context['camelTableName'],
        ];
    }
}