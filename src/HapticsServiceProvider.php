<?php

declare(strict_types=1);

namespace BlessedZulu\NativePHP\Mobile\Haptics;

use Illuminate\Support\ServiceProvider;

class HapticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Haptics::class);
    }
}
