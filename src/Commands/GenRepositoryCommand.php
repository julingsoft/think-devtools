<?php

declare(strict_types=1);

namespace Juling\DevTools\Commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class GenRepositoryCommand extends Command
{
    private array $ignoreTables = ['migrations'];

    protected function configure(): void
    {
        $this->setName('gen:dao')
            ->setDescription('Generate repository layer');
    }

    protected function execute(Input $input, Output $output): int
    {
        $tables = Db::query('show tables;');

        foreach ($tables as $row) {
            $tableName = implode('', $row);

            if (in_array($tableName, $this->ignoreTables)) {
                continue;
            }

            $className = parse_name($tableName, 1);

            $this->repositoryTpl($className);
        }

        return 1;
    }

    private function repositoryTpl(string $name): void
    {
        $content = file_get_contents(dirname(__DIR__, 2).'/stubs/repository.stub');
        $content = str_replace([
            '{$name}',
        ], [
            $name,
        ], $content);
        file_put_contents(app_path('repository').$name.'Repository.php', $content);
    }
}
