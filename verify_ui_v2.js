const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  await page.setViewportSize({ width: 1280, height: 800 });

  console.log('Capturing Index Page...');
  await page.goto('http://localhost:8000/index.php');
  await page.screenshot({ path: 'index_v2.png', fullPage: true });

  console.log('Capturing Product Detail Page...');
  await page.goto('http://localhost:8000/product_detail.php?id=1');
  await page.screenshot({ path: 'product_detail_v2.png', fullPage: true });

  await browser.close();
  console.log('Verification Complete.');
})();
