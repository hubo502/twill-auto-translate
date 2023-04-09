<?php

namespace Xdarko\TwillAutoTranslate;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Xdarko\TwillAutoTranslate\Commands\TwillAutoTranslateCommand;

class TwillAutoTranslateServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('twill-auto-translate')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(TwillAutoTranslateCommand::class);
    }
}
