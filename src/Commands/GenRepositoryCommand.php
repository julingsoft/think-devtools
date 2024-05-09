<?php

declare(strict_types=1);

namespace Juling\DevTools\Commands;

use Juling\DevTools\Support\SchemaTrait;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class GenRepositoryCommand extends Command
{
    use SchemaTrait;

    private array $ignoreTables = ['migrations'];

    protected function configure(): void
    {
        $this->setName('gen:dao')
            ->setDescription('Generate repository class');
    }

    protected function execute(Input $input, Output $output): int
    {
        $tables = $this->getTables();
        foreach ($tables as $row) {
            $tableName = implode('', $row);

            if (in_array($tableName, $this->ignoreTables)) {
                continue;
            }

            $className = parse_name($tableName, 1);
            $columns = $this->getTableInfo($tableName);
            $primaryKey = $this->getPrimaryKeyType($columns);

            $this->repositoryTpl($className, $primaryKey);
        }

        return 1;
    }

    private function repositoryTpl(string $name, array $primaryKey): void
    {
        $primaryKeyType = empty($primaryKey) ? 'int' : $primaryKey['Type'];

        $content = file_get_contents(__DIR__.'/stubs/repository/repository.stub');
        $content = str_replace([
            '{$name}',
            '{$primaryKeyType}',
        ], [
            $name,
            $primaryKeyType,
        ], $content);
        file_put_contents(app_path('repository').$name.'Repository.php', $content);
    }
}
