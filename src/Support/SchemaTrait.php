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

        return $this->singular($tableName);
    }

    /**
     * 表名转单数类名：users -> User, admin_roles -> AdminRole
     */
    protected function getTableClassName(string $tableName): string
    {
        return parse_name($this->singular($tableName), 1);
    }

    /**
     * 英文复数转单数（常见规则 + 不规则词表）
     */
    private function singular(string $name): string
    {
        $irregular = [
            'children' => 'child',
            'men' => 'man',
            'women' => 'woman',
            'people' => 'person',
            'teeth' => 'tooth',
            'feet' => 'foot',
            'geese' => 'goose',
            'mice' => 'mouse',
        ];

        $lower = strtolower($name);
        if (isset($irregular[$lower])) {
            return $irregular[$lower];
        }

        if (str_ends_with($lower, 'ies') && strlen($lower) > 4) {
            return substr($name, 0, -3).'y';
        }

        // boxes/dishes/buses/statuses -> box/dish/bus/status
        if (preg_match('/(s|x|z|ch|sh)es$/', $lower)) {
            return substr($name, 0, -2);
        }

        // users/orders -> user/order（以 ss 结尾的单词本身是单数）
        if (str_ends_with($lower, 's') && ! str_ends_with($lower, 'ss')) {
            return substr($name, 0, -1);
        }

        return $name;
    }

    /**
     * 中间关联表：联合主键，且除时间戳外全部为 *_id/*_type 字段
     * 如 admin_role_permissions、user_followers、content_article_tags
     */
    protected function isPivotTable(string $tableName): bool
    {
        $result = DB::query('SHOW COLUMNS FROM `'.$tableName.'`');

        $pkCount = 0;
        foreach ($result as $col) {
            if ($col['Key'] === 'PRI') {
                $pkCount++;
            }
        }
        if ($pkCount < 2) {
            return false;
        }

        $timeFields = ['create_time', 'update_time', 'delete_time'];
        foreach ($result as $col) {
            $field = $col['Field'];
            if (in_array($field, $timeFields, true)) {
                continue;
            }
            if (! preg_match('/_(id|type)$/', $field)) {
                return false;
            }
        }

        return true;
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
