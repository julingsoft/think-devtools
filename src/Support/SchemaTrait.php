<?php

declare(strict_types=1);

namespace Juling\DevTools\Support;

use think\facade\Db;

trait SchemaTrait
{
    protected function getTableInfo($database, $tableName): array
    {
        $sql = "SELECT COLUMN_NAME,COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '$database' AND TABLE_NAME = '$tableName'";
        $result = Db::query($sql);

        $comments = [];
        foreach ($result as $row) {
            $comments[$row['COLUMN_NAME']] = $row['COLUMN_COMMENT'];
        }

        $sql = 'desc `'.$tableName.'`';
        $result = Db::query($sql);

        $columns = [];
        foreach ($result as $row) {
            $row['Comment'] = $comments[$row['Field']];
            $row['BaseType'] = $this->getFieldType($row['Type']);
            $row['SwaggerType'] = $row['BaseType'] === 'int' ? 'integer' : $row['BaseType'];
            $columns[] = $row;
        }

        return $columns;
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

    protected function getSet($field, $type): string
    {
        $capitalName = parse_name($field, 1);

        return <<<EOF
    public function get{$capitalName}(): $type
    {
        return \$this->$field;
    }

    public function set{$capitalName}($type \${$field}): void
    {
        \$this->$field = \${$field};
    }
EOF;
    }
}
