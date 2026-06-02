<?php

declare(strict_types=1);

use BlessedZulu\NativePHP\Mobile\Haptics\Facades\Haptics as HapticsFacade;
use BlessedZulu\NativePHP\Mobile\Haptics\Haptics;
use BlessedZulu\NativePHP\Mobile\Haptics\HapticsServiceProvider;

// ─── Manifest ────────────────────────────────────────────────────

test('nativephp.json manifest exists and is valid JSON', function () {
    $path = dirname(__DIR__).'/nativephp.json';

    expect(file_exists($path))->toBeTrue();

    $manifest = json_decode(file_get_contents($path), true);

    expect($manifest)->toBeArray()
        ->and($manifest['namespace'])->toBe('Haptics');
});

test('manifest maps all 5 bridge functions to native classes', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__).'/nativephp.json'), true);

    $byName = collect($manifest['bridge_functions'])->keyBy('name');

    foreach (['Haptics.Impact', 'Haptics.Notification', 'Haptics.Selection', 'Haptics.Vibrate', 'Haptics.Pattern'] as $name) {
        expect($byName)->toHaveKey($name)
            ->and($byName[$name])->toHaveKeys(['ios', 'android']);
    }

    // Android refs must be vendor-namespaced fully-qualified class paths.
    expect($byName['Haptics.Impact']['android'])
        ->toBe('com.blessedzulu.plugins.haptics.HapticsFunctions.Impact')
        ->and($byName['Haptics.Impact']['ios'])->toBe('HapticsFunctions.Impact');
});

test('manifest declares VIBRATE permission for Android', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__).'/nativephp.json'), true);

    expect($manifest['android']['permissions'])
        ->toContain('android.permission.VIBRATE');
});

test('manifest declares an iOS minimum version and no extra permissions', function () {
    $manifest = json_decode(file_get_contents(dirname(__DIR__).'/nativephp.json'), true);

    expect($manifest['ios']['min_version'])->toBe('13.0')
        ->and($manifest['ios']['permissions'] ?? [])->toBeEmpty();
});

// ─── Native files ────────────────────────────────────────────────

test('Swift bridge file exists', function () {
    expect(file_exists(dirname(__DIR__).'/resources/ios/HapticsFunctions.swift'))->toBeTrue();
});

test('Kotlin bridge file exists', function () {
    expect(file_exists(dirname(__DIR__).'/resources/android/HapticsFunctions.kt'))->toBeTrue();
});

test('JavaScript bridge file exists', function () {
    expect(file_exists(dirname(__DIR__).'/resources/js/haptics.js'))->toBeTrue();
});

// ─── PHP classes ─────────────────────────────────────────────────

test('Haptics class has all 5 public methods', function () {
    $reflection = new ReflectionClass(Haptics::class);
    $methods = array_map(
        fn (ReflectionMethod $m) => $m->getName(),
        $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
    );

    expect($methods)
        ->toContain('impact')
        ->toContain('notification')
        ->toContain('selection')
        ->toContain('vibrate')
        ->toContain('pattern');
});

test('Haptics methods return bool when nativephp_call is unavailable', function () {
    $haptics = new Haptics;

    expect($haptics->impact())->toBeFalse()
        ->and($haptics->notification())->toBeFalse()
        ->and($haptics->selection())->toBeFalse()
        ->and($haptics->vibrate())->toBeFalse()
        ->and($haptics->pattern([100, 50, 200]))->toBeFalse();
});

test('Haptics::pattern returns false for empty pattern', function () {
    expect((new Haptics)->pattern([]))->toBeFalse();
});

test('Haptics::impact falls back to medium for invalid style', function () {
    $haptics = new Haptics;

    // Should not throw, just fallback
    expect($haptics->impact('nonexistent'))->toBeFalse();
});

test('Haptics::notification falls back to success for invalid type', function () {
    $haptics = new Haptics;

    expect($haptics->notification('nonexistent'))->toBeFalse();
});

test('HapticsServiceProvider is a valid service provider', function () {
    expect(is_subclass_of(HapticsServiceProvider::class, \Illuminate\Support\ServiceProvider::class))
        ->toBeTrue();
});

test('Haptics facade accessor points to Haptics class', function () {
    $reflection = new ReflectionClass(HapticsFacade::class);
    $method = $reflection->getMethod('getFacadeAccessor');
    $method->setAccessible(true);

    expect($method->invoke(null))->toBe(Haptics::class);
});

// ─── JS exports ──────────────────────────────────────────────────

test('JavaScript file exports all 5 functions', function () {
    $js = file_get_contents(dirname(__DIR__).'/resources/js/haptics.js');

    expect($js)
        ->toContain('export async function impact')
        ->toContain('export async function notification')
        ->toContain('export async function selection')
        ->toContain('export async function vibrate')
        ->toContain('export async function pattern')
        ->toContain('export default haptics');
});

// ─── Boost guidelines ────────────────────────────────────────────

test('boost guidelines file exists', function () {
    expect(file_exists(dirname(__DIR__).'/resources/boost/guidelines/core.blade.php'))->toBeTrue();
});
