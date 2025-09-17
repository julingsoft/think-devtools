<?php

declare(strict_types=1);

namespace Juling\DevTools\Support;

use think\facade\Db as DB;
use think\facade\Filesystem;

trait SchemaTrait
{
    protected function getTables(): array
    {
        return DB::query('show tables;');
    }

    protected function getTableComment($tableName): string
    {
        $database = env('DB_DATABASE');
        $tableInfo = DB::query("SELECT `TABLE_COMMENT` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = '$tableName';");

        return $tableInfo[0]['TABLE_COMMENT'];
    }

    protected function getTableInfo($tableName): array
    {
        $database = env('DB_DATABASE');
        $sql = "SELECT COLUMN_NAME,COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = '$tableName';";
        $result = DB::query($sql);

        $comments = [];
        foreach ($result as $row) {
            $comments[$row['COLUMN_NAME']] = $row['COLUMN_COMMENT'];
        }

        $sql = 'desc `'.$tableName.'`';
        $result = DB::query($sql);

        $columns = [];
        foreach ($result as $row) {
            $row['StudlyField'] = parse_name($row['Field'], 1);
            $row['CamelField'] = parse_name($row['Field'], 1, false);
            $row['Comment'] = $comments[$row['Field']];
            $row['BaseType'] = $this->getFieldType($row['Type']);
            $row['SwaggerType'] = $row['BaseType'] === 'int' ? 'integer' : $row['BaseType'];
            $columns[] = $row;
        }

        return $columns;
    }

    private function getTableGroupName(string $tableName)
    {
        if (str_contains($tableName, '_')) {
            $explode = explode('_', $tableName);

            return $explode[0];
        }

        return $tableName;
    }

    protected function getPrimaryKeyType(array $columns): array
    {
        $primaryKey = [];

        foreach ($columns as $column) {
            if ($column['Key'] === 'PRI') {
                $primaryKey = [
                    'Field' => $column['Field'],
                    'Type' => $this->getFieldType($column['Type']),
                ];
                break;
            }
        }

        return $primaryKey;
    }

    protected function getFieldType($type): string
    {
        preg_match('/(\w+)\(/', $type, $m);
        $type = $m[1] ?? $type;
        $type = str_replace(' unsigned', '', $type);
        if (in_array($type, ['bit', 'int', 'bigint', 'mediumint', 'smallint', 'tinyint', 'enum'])) {
            $type = 'int';
        }
        if (in_array($type, ['varchar', 'char', 'text', 'mediumtext', 'longtext'])) {
            $type = 'string';
        }
        if (in_array($type, ['decimal'])) {
            $type = 'float';
        }
        if (in_array($type, ['date', 'datetime', 'timestamp', 'time'])) {
            $type = 'string';
        }

        return $type;
    }

    protected function ensureDirectoryExists(array|string $dirs): void
    {
        if (is_string($dirs)) {
            $dirs = [$dirs];
        }

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    protected function deleteDirectories(string $directory): void
    {
        Filesystem::deleteDirectory($directory);
    }
}
