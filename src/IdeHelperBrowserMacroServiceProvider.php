<?php

namespace Yamut\LaravelDuskMacroHelper;

use Illuminate\Support\ServiceProvider;
use Yamut\LaravelDuskMacroHelper\Console\IdeHelperBrowserMacroCommand;

class IdeHelperBrowserMacroServiceProvider extends ServiceProvider
{
    public const CONFIG_NAME = 'ide-dusk-browser-macros';
    private const CONFIG_FILENAME = self::CONFIG_NAME . '.php';
    private const LOCAL_CONFIG_DIR = __DIR__ . '/../config/';

    public function boot()
    {
        $this->publishes([
            self::LOCAL_CONFIG_DIR . self::CONFIG_FILENAME => config_path(self::CONFIG_FILENAME),
        ]);
        if ($this->app->runningInConsole()) {
            $this->commands([
                IdeHelperBrowserMacroCommand::class
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(self::LOCAL_CONFIG_DIR . self::CONFIG_FILENAME, pathinfo(self::CONFIG_FILENAME)['filename']);
    }
}