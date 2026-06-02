import UIKit
import NativePHP

// MARK: - Haptics.Impact

class HapticsImpact: BridgeFunction {
    override func handle(payload: BridgePayload, completion: @escaping (Any?) -> Void) {
        let style = payload.string("style") ?? "medium"

        let feedbackStyle: UIImpactFeedbackGenerator.FeedbackStyle = {
            switch style {
            case "light": return .light
            case "heavy": return .heavy
            case "rigid": return .rigid
            case "soft": return .soft
            default: return .medium
            }
        }()

        DispatchQueue.main.async {
            let generator = UIImpactFeedbackGenerator(style: feedbackStyle)
            generator.prepare()
            generator.impactOccurred()
            completion(true)
        }
    }
}

// MARK: - Haptics.Notification

class HapticsNotification: BridgeFunction {
    override func handle(payload: BridgePayload, completion: @escaping (Any?) -> Void) {
        let type = payload.string("type") ?? "success"

        let feedbackType: UINotificationFeedbackGenerator.FeedbackType = {
            switch type {
            case "warning": return .warning
            case "error": return .error
            default: return .success
            }
        }()

        DispatchQueue.main.async {
            let generator = UINotificationFeedbackGenerator()
            generator.prepare()
            generator.notificationOccurred(feedbackType)
            completion(true)
        }
    }
}

// MARK: - Haptics.Selection

class HapticsSelection: BridgeFunction {
    override func handle(payload: BridgePayload, completion: @escaping (Any?) -> Void) {
        DispatchQueue.main.async {
            let generator = UISelectionFeedbackGenerator()
            generator.prepare()
            generator.selectionChanged()
            completion(true)
        }
    }
}

// MARK: - Haptics.Vibrate

class HapticsVibrate: BridgeFunction {
    override func handle(payload: BridgePayload, completion: @escaping (Any?) -> Void) {
        let duration = payload.int("duration") ?? 200
        let clampedDuration = max(1, min(5000, duration))

        // iOS does not expose a raw vibration API.
        // Approximate with repeated impact haptics proportional to duration.
        DispatchQueue.main.async {
            let generator = UIImpactFeedbackGenerator(style: .heavy)
            generator.prepare()

            let repetitions = max(1, clampedDuration / 100)
            let interval = Double(clampedDuration) / Double(repetitions) / 1000.0

            for i in 0..<repetitions {
                DispatchQueue.main.asyncAfter(deadline: .now() + interval * Double(i)) {
                    generator.impactOccurred()
                }
            }

            DispatchQueue.main.asyncAfter(deadline: .now() + Double(clampedDuration) / 1000.0) {
                completion(true)
            }
        }
    }
}

// MARK: - Haptics.Pattern

class HapticsPattern: BridgeFunction {
    override func handle(payload: BridgePayload, completion: @escaping (Any?) -> Void) {
        guard let pattern = payload.array("pattern") as? [Int], !pattern.isEmpty else {
            completion(false)
            return
        }

        // Pattern: alternating vibrate/pause durations in ms.
        // Approximate vibrate segments with impact haptics.
        DispatchQueue.main.async {
            let generator = UIImpactFeedbackGenerator(style: .heavy)
            generator.prepare()

            var offset: Double = 0

            for (index, ms) in pattern.enumerated() {
                let clampedMs = max(1, min(5000, ms))
                let isVibrate = index % 2 == 0

                if isVibrate {
                    let repetitions = max(1, clampedMs / 100)
                    let interval = Double(clampedMs) / Double(repetitions) / 1000.0

                    for i in 0..<repetitions {
                        let delay = offset + interval * Double(i)
                        DispatchQueue.main.asyncAfter(deadline: .now() + delay) {
                            generator.impactOccurred()
                        }
                    }
                }

                offset += Double(clampedMs) / 1000.0
            }

            DispatchQueue.main.asyncAfter(deadline: .now() + offset) {
                completion(true)
            }
        }
    }
}
