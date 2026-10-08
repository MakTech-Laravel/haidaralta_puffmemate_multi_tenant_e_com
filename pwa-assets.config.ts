import {
    createAppleSplashScreens,
    defineConfig,
    minimal2023Preset,
} from '@vite-pwa/assets-generator/config';

export default defineConfig({
    headLinkOptions: {
        preset: '2023',
        basePath: '/pwa/',
        xhtml: false,
    },
    overrideAssets: true,
    preset: {
        ...minimal2023Preset,
        appleSplashScreens: createAppleSplashScreens({
            padding: 0.3,
            resizeOptions: { background: '#ffffff', fit: 'contain' },
            linkMediaOptions: {
                log: true,
                addMediaScreen: true,
                basePath: '/pwa/',
                xhtml: false,
            },
            png: {
                compressionLevel: 9,
                quality: 60,
            },
            name: (landscape, size) =>
                `apple-splash-${landscape ? 'landscape' : 'portrait'}-${size.width}x${size.height}.png`,
        }),
    },
    images: ['public/pwa/mark.svg'],
});
