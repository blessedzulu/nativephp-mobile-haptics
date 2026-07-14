{{--
    NativePHP Mobile Haptics - Plugin Dev Kit / AI Guidelines
    This file is consumed by the NativePHP Boost AI assistant.
--}}

## Plugin: graymatter/nativephp-mobile-haptics

Provides haptic feedback (vibrations) for NativePHP Mobile apps on iOS and Android.

### Available Methods

| Method | Parameters | Description |
|--------|-----------|-------------|
| `Haptics::impact($style)` | `light`, `medium` (default), `heavy`, `rigid`, `soft` | Triggers an impact haptic. Use for button taps, collisions, or UI emphasis. |
| `Haptics::notification($type)` | `success` (default), `warning`, `error` | Triggers a notification haptic. Use after async operations complete. |
| `Haptics::selection()` | None | Triggers a selection tick. Use for pickers, sliders, and toggles. |
| `Haptics::vibrate($ms)` | Duration in ms (1 - 5000, default 200) | Raw vibration. Native on Android, approximated on iOS. |
| `Haptics::pattern($array)` | Array of ms durations `[vibrate, pause, vibrate, ...]` | Vibration pattern. Native on Android, approximated on iOS. |

### Usage (PHP)

```php
use GrayMatter\NativePHP\Mobile\Haptics\Facades\Haptics;

Haptics::impact('heavy');
Haptics::notification('success');
Haptics::selection();
Haptics::vibrate(300);
Haptics::pattern([100, 50, 200, 50, 100]);
```

### Usage (JavaScript)

```js
import { haptics } from '@graymatter/nativephp-mobile-haptics';

await haptics.impact('heavy');
await haptics.notification('error');
await haptics.selection();
```

### Platform Differences

- **iOS**: Uses `UIFeedbackGenerator` APIs. `vibrate()` and `pattern()` are approximated using repeated impact haptics since iOS has no raw vibration API.
- **Android**: Uses `VibrationEffect` and `VibratorManager`. Full native support for all methods. Requires `VIBRATE` permission (auto-granted). Min API 26, predefined effects require API 29+.

### Best Practices

- Use `impact()` for direct user interactions (button presses, drag snaps).
- Use `notification()` sparingly for meaningful state changes (payment confirmed, upload failed).
- Use `selection()` for continuous feedback in pickers and sliders.
- Avoid excessive haptics - they lose effectiveness and annoy users.
- All methods return `bool` and degrade gracefully (return `false` on simulators or missing hardware).
