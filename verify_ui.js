const { chromium } = require('playwright');
const path = require('path');
const fs = require('fs');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  const baseUrl = 'http://localhost:8000';
  const screenshotDir = path.join(__dirname, 'screenshots');

  if (!fs.existsSync(screenshotDir)){
      fs.mkdirSync(screenshotDir, { recursive: true });
  }

  // Helper to wait for network idle
  const goto = async (url) => {
    console.log(`Navigating to ${url}`);
    await page.goto(url, { waitUntil: 'networkidle' });
  };

  // Marketplace Homepage
  await goto(`${baseUrl}/index.php`);
  await page.screenshot({ path: path.join(screenshotDir, 'marketplace.png'), fullPage: true });

  // Product Detail
  await goto(`${baseUrl}/product_detail.php?id=1`);
  await page.screenshot({ path: path.join(screenshotDir, 'product_detail.png'), fullPage: true });

  // Shop Owner Portal
  await goto(`${baseUrl}/shop_portal.php`);
  await page.screenshot({ path: path.join(screenshotDir, 'shop_portal.png'), fullPage: true });

  // Admin Dashboard
  await goto(`${baseUrl}/admin/index.php`);
  await page.screenshot({ path: path.join(screenshotDir, 'admin_dashboard.png'), fullPage: true });

  await browser.close();
  console.log('Verification screenshots generated.');
})();
