const { chromium } = require('playwright-core');
const path = require('path');
const fs = require('fs');

async function testProviderHistory() {
    console.log('🚀 Memulakan Ujian Playwright dengan Google Chrome...');

    const screenshotsDir = path.join(__dirname, 'screenshots');
    if (!fs.existsSync(screenshotsDir)) {
        fs.mkdirSync(screenshotsDir, { recursive: true });
    }

    // 1. Lancarkan Google Chrome
    const browser = await chromium.launch({
        channel: 'chrome',
        headless: false,   // Buka secara visual supaya pengguna boleh lihat pelayar bergerak sendiri
        slowMo: 600        // Kelajuan sederhana untuk melihat interaksi
    });

    const context = await browser.newContext({
        viewport: { width: 1280, height: 800 }
    });
    const page = await context.newPage();

    const baseUrl = 'http://localhost/FullStackWeb/DFP40443/SCRS_PMU%20-%20Copy';

    console.log(`🌐 1. Membuka halaman log masuk: ${baseUrl}/index.php`);
    await page.goto(`${baseUrl}/index.php`, { waitUntil: 'networkidle' });

    // 2. Log Masuk sebagai Provider
    console.log('🔑 2. Mengisi maklumat log masuk (provider1)...');
    await page.fill('input[name="username"]', 'provider1');
    await page.fill('input[name="password"]', 'Password123!');
    
    console.log('🔘 3. Menekan butang Log Masuk...');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle' }),
        page.click('button[type="submit"]')
    ]);

    console.log(`📍 Berjaya log masuk! Halaman semasa: ${page.url()}`);

    // 3. Pergi ke halaman Rekod Tempahan
    console.log('📑 4. Membuka halaman Rekod Tempahan (provider_history.php)...');
    await page.goto(`${baseUrl}/provider/provider_history.php`, { waitUntil: 'networkidle' });

    // 4. Ambil tangkapan skrin Desktop
    console.log('📸 5. Mengambil tangkapan skrin paparan Desktop...');
    const pathDesktop = path.join(screenshotsDir, 'provider_history_desktop.png');
    await page.screenshot({ path: pathDesktop, fullPage: true });
    console.log(`✅ Tangkapan skrin Desktop disimpan di: ${pathDesktop}`);

    // 5. Ubah ke paparan Telefon Pintar (Mobile Viewport 390x844)
    console.log('📱 6. Mengubah ke paparan Mobile (iPhone - 390 x 844)...');
    await page.setViewportSize({ width: 390, height: 844 });
    await page.waitForTimeout(1000);

    const pathMobile = path.join(screenshotsDir, 'provider_history_mobile.png');
    await page.screenshot({ path: pathMobile, fullPage: true });
    console.log(`✅ Tangkapan skrin Mobile disimpan di: ${pathMobile}`);

    console.log('⏳ Menunggu 4 saat sebelum menutup Google Chrome...');
    await page.waitForTimeout(4000);

    await browser.close();
    console.log('🎉 Ujian Playwright dengan Google Chrome Berjaya Sepenuhnya!');
}

testProviderHistory().catch((err) => {
    console.error('❌ Ralat berlaku semasa ujian:', err);
});
