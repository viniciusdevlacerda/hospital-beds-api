<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $strict = ! $this->app->isProduction();

        Model::preventAccessingMissingAttributes($strict);
        Model::preventSilentlyDiscardingAttributes($strict);
    }
}
