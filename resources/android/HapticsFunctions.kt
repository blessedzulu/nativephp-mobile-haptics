package app.nativephp.plugin.haptics

import android.content.Context
import android.os.Build
import android.os.VibrationEffect
import android.os.Vibrator
import android.os.VibratorManager
import androidx.fragment.app.FragmentActivity
import app.nativephp.plugin.BridgeFunction
import app.nativephp.plugin.BridgePayload

// Helper to obtain the Vibrator service across API levels.
private fun getVibrator(activity: FragmentActivity): Vibrator? {
    return if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) {
        val manager = activity.getSystemService(Context.VIBRATOR_MANAGER_SERVICE) as? VibratorManager
        manager?.defaultVibrator
    } else {
        @Suppress("DEPRECATION")
        activity.getSystemService(Context.VIBRATOR_SERVICE) as? Vibrator
    }
}

// MARK: - Haptics.Impact

class HapticsImpact(private val activity: FragmentActivity) : BridgeFunction() {
    override fun handle(payload: BridgePayload, completion: (Any?) -> Unit) {
        val vibrator = getVibrator(activity)
        if (vibrator == null || !vibrator.hasVibrator()) {
            completion(false)
            return
        }

        val style = payload.getString("style") ?: "medium"

        val (duration, amplitude) = when (style) {
            "light"  -> 20L to 40
            "heavy"  -> 40L to 255
            "rigid"  -> 15L to 200
            "soft"   -> 50L to 60
            else     -> 30L to 128 // medium
        }

        val effect = VibrationEffect.createOneShot(duration, amplitude)
        vibrator.vibrate(effect)
        completion(true)
    }
}

// MARK: - Haptics.Notification

class HapticsNotification(private val activity: FragmentActivity) : BridgeFunction() {
    override fun handle(payload: BridgePayload, completion: (Any?) -> Unit) {
        val vibrator = getVibrator(activity)
        if (vibrator == null || !vibrator.hasVibrator()) {
            completion(false)
            return
        }

        val type = payload.getString("type") ?: "success"

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            val effectId = when (type) {
                "warning" -> VibrationEffect.EFFECT_DOUBLE_CLICK
                "error"   -> VibrationEffect.EFFECT_HEAVY_CLICK
                else      -> VibrationEffect.EFFECT_CLICK // success
            }
            vibrator.vibrate(VibrationEffect.createPredefined(effectId))
        } else {
            // Fallback waveform for older API levels
            val (timings, amplitudes) = when (type) {
                "warning" -> longArrayOf(0, 30, 50, 30) to intArrayOf(0, 180, 0, 180)
                "error"   -> longArrayOf(0, 50, 40, 50, 40, 50) to intArrayOf(0, 255, 0, 200, 0, 255)
                else      -> longArrayOf(0, 30) to intArrayOf(0, 128) // success
            }
            vibrator.vibrate(VibrationEffect.createWaveform(timings, amplitudes, -1))
        }

        completion(true)
    }
}

// MARK: - Haptics.Selection

class HapticsSelection(private val activity: FragmentActivity) : BridgeFunction() {
    override fun handle(payload: BridgePayload, completion: (Any?) -> Unit) {
        val vibrator = getVibrator(activity)
        if (vibrator == null || !vibrator.hasVibrator()) {
            completion(false)
            return
        }

        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            vibrator.vibrate(VibrationEffect.createPredefined(VibrationEffect.EFFECT_TICK))
        } else {
            vibrator.vibrate(VibrationEffect.createOneShot(10, 60))
        }

        completion(true)
    }
}

// MARK: - Haptics.Vibrate

class HapticsVibrate(private val activity: FragmentActivity) : BridgeFunction() {
    override fun handle(payload: BridgePayload, completion: (Any?) -> Unit) {
        val vibrator = getVibrator(activity)
        if (vibrator == null || !vibrator.hasVibrator()) {
            completion(false)
            return
        }

        val duration = (payload.getInt("duration") ?: 200).coerceIn(1, 5000).toLong()

        vibrator.vibrate(VibrationEffect.createOneShot(duration, VibrationEffect.DEFAULT_AMPLITUDE))
        completion(true)
    }
}

// MARK: - Haptics.Pattern

class HapticsPattern(private val activity: FragmentActivity) : BridgeFunction() {
    override fun handle(payload: BridgePayload, completion: (Any?) -> Unit) {
        val vibrator = getVibrator(activity)
        if (vibrator == null || !vibrator.hasVibrator()) {
            completion(false)
            return
        }

        val raw = payload.getArray("pattern") ?: run {
            completion(false)
            return
        }

        val pattern = raw.mapNotNull { (it as? Number)?.toLong()?.coerceIn(1, 5000) }
        if (pattern.isEmpty()) {
            completion(false)
            return
        }

        // Android createWaveform expects: [delay, vibrate, pause, vibrate, ...]
        // Input pattern is: [vibrate, pause, vibrate, pause, ...]
        // Prepend 0ms initial delay to align formats.
        val timings = longArrayOf(0) + pattern.toLongArray()

        // Amplitudes: 0 for pauses, DEFAULT_AMPLITUDE for vibrations
        // After prepending 0-delay: index 0 = delay (amp 0), index 1 = vibrate, index 2 = pause, ...
        val amplitudes = IntArray(timings.size) { index ->
            if (index == 0) 0 // initial delay
            else if (index % 2 == 1) VibrationEffect.DEFAULT_AMPLITUDE // vibrate segments
            else 0 // pause segments
        }

        vibrator.vibrate(VibrationEffect.createWaveform(timings, amplitudes, -1))
        completion(true)
    }
}
