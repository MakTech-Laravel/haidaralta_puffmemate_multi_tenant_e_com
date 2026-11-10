export type DeviceOs = 'android' | 'ios' | 'windows' | 'mac' | 'other';

export type LocationProblem =
    | 'denied'
    | 'system'
    | 'unavailable'
    | 'timeout'
    | 'inaccurate'
    | 'old'
    | 'jump'
    | 'network';

export type LocationHelp = {
    headline: string;
    steps: string[];
};

/** Works out which kind of device the driver is on, so the help matches their screen. */
export function detectDeviceOs(): DeviceOs {
    if (typeof navigator === 'undefined') {
        return 'other';
    }

    const agent = navigator.userAgent;

    if (/android/i.test(agent)) {
        return 'android';
    }

    // iPadOS in desktop mode reports itself as a Mac but has a touch screen.
    if (
        /iphone|ipad|ipod/i.test(agent) ||
        (/macintosh/i.test(agent) && navigator.maxTouchPoints > 1)
    ) {
        return 'ios';
    }

    if (/windows/i.test(agent)) {
        return 'windows';
    }

    if (/macintosh|mac os x/i.test(agent)) {
        return 'mac';
    }

    return 'other';
}

const DENIED: Record<DeviceOs, string[]> = {
    android: [
        'Tap the lock icon (or the settings icon) next to the web address.',
        'Tap Permissions, then Location, and choose Allow.',
        'Come back to this page and press Try again.',
    ],
    ios: [
        'Open the Settings app, then Privacy & Security, then Location Services, and make sure it is On.',
        'Scroll to Safari Websites (or your browser) and choose While Using the App.',
        'Back in the browser, tap the "aA" icon, then Website Settings, then Location, and choose Allow.',
        'Press Try again.',
    ],
    windows: [
        'Click the lock icon (or settings icon) on the left of the web address.',
        'Set Location to Allow.',
        'Press Try again.',
    ],
    mac: [
        'Click the lock icon (or settings icon) on the left of the web address.',
        'Set Location to Allow.',
        'Press Try again.',
    ],
    other: [
        'Click the lock icon on the left of the web address.',
        'Set Location to Allow.',
        'Press Try again.',
    ],
};

const DEVICE_BLOCKED: Record<DeviceOs, string[]> = {
    android: [
        'Swipe down from the top of the screen and make sure Location is On.',
        'Open Settings, then Location, and turn on precise location (Google Location Accuracy).',
        'Press Try again.',
    ],
    ios: [
        'Open Settings, then Privacy & Security, then Location Services, and turn it On.',
        'Open Safari Websites (or your browser) in that list, choose While Using the App, and turn on Precise Location.',
        'Press Try again.',
    ],
    windows: [
        'Open Settings, then Privacy & security, then Location.',
        'Turn on Location services and "Let desktop apps access your location".',
        'Press Set default under Default location and enter your address.',
        'Close and reopen the browser, then press Try again.',
    ],
    mac: [
        'Open System Settings, then Privacy & Security, then Location Services.',
        'Turn Location Services on and switch on your browser in the list.',
        'Close and reopen the browser, then press Try again.',
    ],
    other: [
        'Turn on Location in your device settings.',
        'Close and reopen the browser, then press Try again.',
    ],
};

const WEAK: Record<DeviceOs, string[]> = {
    android: [
        'Go outside or stand near a window.',
        'Turn on precise location in Settings, then Location.',
        'Press Try again.',
    ],
    ios: [
        'Go outside or stand near a window.',
        'In Settings, then Privacy & Security, then Location Services, turn on Precise Location for your browser.',
        'Press Try again.',
    ],
    windows: [
        'Computers do not have GPS, so the location is only a rough guess.',
        'Connect to Wi-Fi and try again, or use your phone.',
    ],
    mac: [
        'Computers do not have GPS, so the location is only a rough guess.',
        'Connect to Wi-Fi and try again, or use your phone.',
    ],
    other: [
        'Go outside or connect to Wi-Fi.',
        'Press Try again, or use your phone.',
    ],
};

/** Plain-language, device-specific instructions for each thing that can go wrong. */
export function locationHelp(
    problem: LocationProblem,
    os: DeviceOs,
): LocationHelp {
    switch (problem) {
        case 'denied':
            return {
                headline: 'Location is blocked for this website.',
                steps: DENIED[os],
            };
        case 'system':
        case 'unavailable':
            return {
                headline:
                    problem === 'system'
                        ? 'Your browser allows location, but your device did not give it.'
                        : 'Your device could not find your location.',
                steps: DEVICE_BLOCKED[os],
            };
        case 'inaccurate':
            return {
                headline: 'Your location is not accurate enough.',
                steps: WEAK[os],
            };
        case 'timeout':
            return {
                headline: 'Finding your location took too long.',
                steps: [
                    'Make sure Location is turned on.',
                    'Go outside or stand near a window.',
                    'Press Try again.',
                ],
            };
        case 'old':
            return {
                headline: 'Your device sent an old location.',
                steps: ['Press Try again to get a fresh one.'],
            };
        case 'jump':
            return {
                headline: 'Your location changed too fast to trust.',
                steps: ['Press Try again.'],
            };
        default:
            return {
                headline: 'We could not reach the server.',
                steps: ['Check your internet connection.', 'Press Try again.'],
            };
    }
}
