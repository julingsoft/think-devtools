<?php

declare(strict_types=1);

namespace Juling\DevTools\Commands;

use Juling\DevTools\Support\SchemaTrait;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\View;

class GenServiceCommand extends Command
{
    use SchemaTrait;

    private array $ignoreTables = ['migrations'];

    protected function configure(): void
    {
        $this->setName('gen:service')
            ->setDescription('Generate service class');
    }

    protected function execute(Input $input, Output $output): int
    {
        $this->ensureDirectoryExists([
            app_path() . 'service',
        ]);

        $tables = $this->getTables();
        foreach ($tables as $row) {
            $tableName = implode('', $row);

            if (in_array($tableName, $this->ignoreTables)) {
                continue;
            }

            $className = parse_name($tableName, 1);

            $this->serviceTpl($className, $tableName);
        }

        return 1;
    }

    private function serviceTpl(string $name, string $tableName): void
    {
        $content = file_get_contents(__DIR__ . '/stubs/service/service.stub');
        $content = str_replace([
            '{$name}',
        ], [
            $name,
        ], $content);
        $serviceFile = app_path() . 'service/' . $name . 'Service.php';
        file_put_contents($serviceFile, $content);
        $this->bundleService($name, $tableName);
    }

    private function bundleService(string $name, string $tableName): void
    {
        $data = ['name' => $name];
        $data['groupName'] = $this->getTableGroupName($tableName);
        $dist = app_path().'bundles/' . $data['groupName'] . '/service';
        $this->ensureDirectoryExists($dist);

        $tpl = file_get_contents(__DIR__ . '/stubs/service/bundle.stub');
        $content = View::display($tpl, $data);
        if (! file_exists($dist . '/' . $name . 'BundleService.php')) {
            file_put_contents($dist . '/' . $name . 'BundleService.php', "<?php\n\n" . $content);
        }
    }
}
