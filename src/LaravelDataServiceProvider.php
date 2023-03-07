<?php

namespace Spatie\LaravelData;

use Spatie\LaravelData\Commands\DataMakeCommand;
use Spatie\LaravelData\Contracts\BaseData;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\VarDumper\VarDumperManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelDataServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-data')
            ->hasCommand(DataMakeCommand::class)
            ->hasConfigFile('data');
    }

    public function packageRegistered()
    {
        $this->app->singleton(
            DataConfig::class,
            fn () => new DataConfig(config('data'))
        );

        /** @psalm-suppress UndefinedInterfaceMethod */
        $this->app->beforeResolving(BaseData::class, function ($class, $parameters, $app) {
            if ($app->has($class)) {
                return;
            }

            $app->bind(
                $class,
                fn ($container) => $class::from($container['request'])
            );
        });
    }

    public function packageBooted()
    {
        switch (config('data.var_dumper_caster_mode')) {
            case 'enabled':
                $enableVarDumperCaster = true;
                break;
            case 'development':
                $enableVarDumperCaster = $this->app->environment('local', 'testing');
                break;
            default:
                $enableVarDumperCaster = false;
        }

        if ($enableVarDumperCaster) {
            (new VarDumperManager())->initialize();
        }
    }
}
