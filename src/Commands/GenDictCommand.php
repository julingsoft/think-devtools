<?php

declare(strict_types=1);

namespace Juling\DevTools\Commands;

use Juling\DevTools\Support\SchemaTrait;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

class GenDictCommand extends Command
{
    use SchemaTrait;

    private array $ignoreTables = ['migrations'];

    protected function configure(): void
    {
        $this->setName('gen:dict')
            ->setDescription('Generate database dict');
    }

    protected function execute(Input $input, Output $output): int
    {
        $content = "# 数据字典\n\n";

        $tables = $this->getTables();
        foreach ($tables as $row) {
            $tableName = implode('', $row);
            if (in_array($tableName, $this->ignoreTables)) {
                continue;
            }

            $tableComment = $this->getTableComment($tableName);
            $content .= "### {$tableComment}(`$tableName`)\n";

            $columns = $this->getTableInfo($tableName);
            $content .= $this->getContent($columns);
        }

        file_put_contents(public_path('docs').'dict.md', $content);

        return 1;
    }

    public function getContent($columns): string
    {
        $content = "| 列名 | 数据类型 | 索引 | 是否为空 | 描述 |\n";
        $content .= "| ------- | --------- | --------- | --------- | -------------- |\n";
        foreach ($columns as $column) {
            $isNull = $column['Null'] === 'NO' ? '否' : '是';
            $content .= "| {$column['Field']} | {$column['Type']} | {$column['Key']} | $isNull | {$column['Comment']} |\n";
        }
        $content .= "\n";

        return $content;
    }
}
