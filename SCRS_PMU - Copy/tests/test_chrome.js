const { chromium } = require('playwright-core');
const path = require('path');
const fs = require('fs');

async function run() {
    console.log('🚀 Membuka Google Chrome...');

    // Pastikan folder screenshots wujud
    const screenshotsDir = path.join(__dirname, 'screenshots');
    if (!fs.existsSync(screenshotsDir)) {
        fs.mkdirSync(screenshotsDir, { recursive: true });
    }

    // Lancarkan Google Chrome sebenar pada komputer pengguna
    const browser = await chromium.launch({
        channel: 'chrome',       // Menggunakan Google Chrome yang terpasang
        headless: false,         // Buka tetingkap pelayar secara visual (dapat dilihat)
        slowMo: 400              // Perlahankan sedikit supaya pergerakan nampak jelas
    });

    // Cipta konteks pelayar dengan saiz skrin
    const context = await browser.newContext({
        viewport: { width: 1280, height: 800 }
    });

    const page = await context.newPage();

    const baseUrl = 'http://localhost/FullStackWeb/DFP40443/SCRS_PMU%20-%20Copy';

    console.log(`🌐 Membuka laman utama: ${baseUrl}/index.php`);
    await page.goto(`${baseUrl}/index.php`, { waitUntil: 'networkidle' });

    console.log('📸 Mengambil tangkapan skrin laman utama (Desktop)...');
    const screenshotDesktop = path.join(screenshotsDir, 'index_desktop.png');
    await page.screenshot({ path: screenshotDesktop, fullPage: true });
    console.log(`✅ Tangkapan skrin disimpan di: ${screenshotDesktop}`);

    // Tukar ke mod paparan telefon (iPhone 14 / Mobile Viewport)
    console.log('📱 Menguji paparan telefon bimbit (390 x 844)...');
    await page.setViewportSize({ width: 390, height: 844 });
    const screenshotMobile = path.join(screenshotsDir, 'index_mobile.png');
    await page.screenshot({ path: screenshotMobile, fullPage: true });
    console.log(`✅ Tangkapan skrin paparan telefon disimpan di: ${screenshotMobile}`);

    console.log('⏳ Menunggu 5 saat sebelum menutup Google Chrome...');
    await page.waitForTimeout(5000);

    await browser.close();
    console.log('🎉 Selesai!');
}

run().catch((err) => {
    console.error('❌ Ralat berlaku:', err);
});
