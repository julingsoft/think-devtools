<?php

declare(strict_types=1);

namespace Juling\DevTools\Commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class GenServiceCommand extends Command
{
    private array $ignoreTables = ['migrations'];

    protected function configure(): void
    {
        $this->setName('gen:service')
            ->setDescription('Generate service layer');
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
            $this->serviceTpl($className);
        }

        return 1;
    }

    private function serviceTpl(string $name): void
    {
        $content = file_get_contents(dirname(__DIR__, 2).'/stubs/service.stub');
        $content = str_replace([
            '{$name}',
        ], [
            $name,
        ], $content);
        $serviceFile = app_path('service').$name.'Service.php';
        if (! file_exists($serviceFile)) {
            file_put_contents($serviceFile, $content);
        }
    }
}
