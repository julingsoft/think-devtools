<?php

declare(strict_types=1);

namespace Juling\DevTools\Commands;

use Juling\DevTools\Support\SchemaTrait;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class GenEntityCommand extends Command
{
    use SchemaTrait;

    private array $ignoreTables = ['migrations'];

    protected function configure(): void
    {
        $this->setName('gen:entity')
            ->setDescription('Generate entity objects');
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

            $this->entityTpl($className, $columns);
        }

        return 1;
    }

    private function entityTpl($className, $columns): void
    {
        $fields = "\n";
        foreach ($columns as $column) {
            if ($column['Field'] === 'default') {
                $column['Field'] = 'isDefault';
            }
            if ($column['Field'] === 'id' && empty($column['Comment'])) {
                $column['Comment'] = 'ID';
            }
            $fields .= "    #[OA\Property(property: '{$column['Field']}', description: '{$column['Comment']}', type: '{$column['SwaggerType']}')]\n";
            $fields .= '    private '.$column['BaseType'].' $'.$column['Field'].";\n\n";
        }

        foreach ($columns as $column) {
            $fields .= $this->getSet($column['Field'], $column['BaseType'])."\n\n";
        }

        $fields = rtrim($fields, "\n");

        $content = file_get_contents(__DIR__.'/stubs/entity/entity.stub');
        $content = str_replace([
            '{$className}',
            '{$fields}',
        ], [
            $className,
            $fields,
        ], $content);

        file_put_contents(app_path().'entity/'.$className.'Entity.php', $content);
    }
}
