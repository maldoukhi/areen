/**
 * End-of-rest feedback: a buzz and a short tone.
 *
 * DESIGN.md §11 is explicit — vibration, a short tone, one flash of the bar, and
 * no modal. A modal would need dismissing, and the trainee is holding a barbell.
 *
 * The tone is generated with Web Audio rather than shipped as a file: two sine
 * blips are a few lines of code, cost no request, and cannot fail to load on a
 * dead connection in a basement.
 */

let context = null;

function audioContextClass() {
    return window.AudioContext || window.webkitAudioContext || null;
}

export function audioSupported() {
    return audioContextClass() !== null;
}

/**
 * Build (or wake) the audio context. Must be called from inside a user gesture:
 * every mobile browser starts contexts suspended, and one created outside a tap
 * stays silent forever. The rest timer primes it on the tap that starts the rest,
 * which is the last gesture before the tone has to play.
 */
export function primeAudio() {
    const Ctx = audioContextClass();
    if (! Ctx) return false;

    try {
        context ??= new Ctx();

        if (context.state === 'suspended') {
            context.resume().catch(() => {});
        }

        return true;
    } catch {
        context = null;

        return false;
    }
}

/** Two short blips, rising. Loud enough to hear over gym noise, short enough not to annoy. */
export function chime() {
    if (! primeAudio() || ! context) return;

    try {
        const now = context.currentTime;

        // A4 then D5 — a small rising interval reads as "done", not as an alarm.
        [
            [880, 0],
            [1174.66, 0.17],
        ].forEach(([frequency, offset]) => {
            const oscillator = context.createOscillator();
            const gain = context.createGain();

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(frequency, now + offset);

            // Ramped rather than switched, so it does not click.
            gain.gain.setValueAtTime(0.0001, now + offset);
            gain.gain.exponentialRampToValueAtTime(0.3, now + offset + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + offset + 0.15);

            oscillator.connect(gain);
            gain.connect(context.destination);

            oscillator.start(now + offset);
            oscillator.stop(now + offset + 0.16);
        });
    } catch {
        // Silence is an acceptable outcome; the buzz and the flash still land.
    }
}

/** A short double buzz. Unsupported on iOS, where the tone carries it alone. */
export function buzz(pattern = [120, 70, 120]) {
    if (typeof navigator.vibrate !== 'function') return;

    try {
        navigator.vibrate(pattern);
    } catch {
        // Some engines expose the method and refuse the call. Not our problem.
    }
}
