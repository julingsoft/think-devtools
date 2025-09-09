<?php

declare(strict_types=1);

namespace Juling\DevTools;

use think\Service as ServiceProvider;

class DevToolsServiceProvider extends ServiceProvider
{
    public function register()
    {
        // 服务注册
    }

    public function boot(): void
    {
        $this->commands([
            Commands\GenControllerCommand::class,
            Commands\GenDictCommand::class,
            Commands\GenEntityCommand::class,
            Commands\GenInterfaceCommand::class,
            Commands\GenModelCommand::class,
            Commands\GenRepositoryCommand::class,
            Commands\GenRouteCommand::class,
            Commands\GenServiceCommand::class,
        ]);
    }
}
