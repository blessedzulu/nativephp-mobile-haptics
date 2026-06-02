<?php

declare(strict_types=1);

namespace BlessedZulu\NativePHP\Mobile\Haptics\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool impact(string $style = 'medium')
 * @method static bool notification(string $type = 'success')
 * @method static bool selection()
 * @method static bool vibrate(int $duration = 200)
 * @method static bool pattern(array $pattern)
 *
 * @see \BlessedZulu\NativePHP\Mobile\Haptics\Haptics
 */
class Haptics extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \BlessedZulu\NativePHP\Mobile\Haptics\Haptics::class;
    }
}
