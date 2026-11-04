<?php
require_once 'includes/config.php';
require_once 'includes/seo_helper.php';

// -------------------------------------------------------------
// 1. Fetch Dynamic Products for Shopping Grid
// -------------------------------------------------------------
// Priority: Products explicitly marked show_on_front = 1, then featured, then newest
$prod_stmt = $pdo->query("
    SELECT p.*, s.name as shop_name, s.locality as shop_locality, s.type as shop_type
    FROM products p
    JOIN shops s ON p.shop_id = s.id
    WHERE (p.show_on_front = 1 OR p.show_on_front IS NULL)
    ORDER BY p.is_featured DESC, (s.type = 'privilege' OR s.type = 'classic') DESC, p.created_at DESC
    LIMIT 10
");
$front_products = $prod_stmt->fetchAll();

// Fallback products if none exist
if (empty($front_products)) {
    $front_products = [
        ['id' => 1, 'name' => 'Leather Sandals', 'price' => 799, 'image' => 'https://picsum.photos/seed/sandals/400/440'],
        ['id' => 2, 'name' => 'Salwar Suit', 'price' => 1199, 'image' => 'https://picsum.photos/seed/salwar/400/440'],
        ['id' => 3, 'name' => 'Smart Watch', 'price' => 1499, 'image' => 'https://picsum.photos/seed/smartwatch/400/440'],
        ['id' => 4, 'name' => 'Perfume 100ml', 'price' => 649, 'image' => 'https://picsum.photos/seed/perfume/400/440'],
        ['id' => 5, 'name' => 'LED Bulb 12W', 'price' => 129, 'image' => 'https://picsum.photos/seed/ledbulb/400/440'],
    ];
}

// -------------------------------------------------------------
// 2. Fetch Dynamic Offers / Banners for Top Offer Slider
// -------------------------------------------------------------
$offers_data = [];

// Try fetching from offers table first
try {
    $off_stmt = $pdo->query("
        SELECT o.*, s.name as shop_name, s.locality
        FROM offers o
        JOIN shops s ON o.shop_id = s.id
        WHERE o.is_active = 1 AND (o.show_on_front = 1 OR o.show_on_front IS NULL)
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    $offers_data = $off_stmt->fetchAll();
} catch (Exception $e) {
    $offers_data = [];
}

// If fewer than 3 offers, pull from active banners
if (count($offers_data) < 3) {
    try {
        $banners = $pdo->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY display_order ASC LIMIT 5")->fetchAll();
    } catch (Exception $e) {
        $banners = [];
    }
} else {
    $banners = [];
}

// Fallback Offer Slides if none exist
$slides = [];
if (!empty($offers_data)) {
    foreach ($offers_data as $o) {
        $pct = intval($o['discount_percent'] ?: 40);
        $slides[] = [
            'image' => 'https://picsum.photos/seed/offer-' . $o['id'] . '/900/500',
            'big' => 'SPECIAL<br>OFFER',
            'pct' => $pct . '%',
            'desc' => e($o['title']) . ' - ' . e($o['description'] ?: 'Exclusive deal by ' . $o['shop_name']),
            'url' => 'shop_detail.php?id=' . $o['shop_id']
        ];
    }
} elseif (!empty($banners)) {
    foreach ($banners as $b) {
        $slides[] = [
            'image' => (!empty($b['image_url']) && strpos($b['image_url'], 'http') === 0) ? $b['image_url'] : BASE_URL . '/' . $b['image_url'],
            'big' => 'MEGA<br>DEALS',
            'pct' => '50%',
            'desc' => e($b['title']) . (!empty($b['description']) ? ' - ' . e($b['description']) : ''),
            'url' => (!empty($b['target_url'])) ? $b['target_url'] : 'shopping.php'
        ];
    }
}

// Guarantee 3 default styled slides if still empty
if (empty($slides)) {
    $slides = [
        [
            'image' => 'https://picsum.photos/seed/sneaker-sale/900/500',
            'big' => 'BIG<br>SALE',
            'pct' => '60%',
            'desc' => 'On footwear & sportswear this week',
            'url' => 'shopping.php'
        ],
        [
            'image' => 'https://picsum.photos/seed/fashion-sale/900/500',
            'big' => 'STYLE<br>FEST',
            'pct' => '40%',
            'desc' => 'Ethnic & western wear for the family',
            'url' => 'shopping.php'
        ],
        [
            'image' => 'https://picsum.photos/seed/gadget-sale/900/500',
            'big' => 'TECH<br>DAYS',
            'pct' => '30%',
            'desc' => 'Smart watches, earbuds & more',
            'url' => 'shopping.php'
        ]
    ];
}

// -------------------------------------------------------------
// 3. Fetch Dynamic Services / Portfolios & Shops for Classifieds Slider
// -------------------------------------------------------------
$classified_items = [];

// Fetch portfolios / services marked for front
try {
    $port_stmt = $pdo->query("
        SELECT p.*, c.name as category_name
        FROM portfolios p
        LEFT JOIN professional_categories c ON p.category_id = c.id
        WHERE (p.show_on_front = 1 OR p.show_on_front IS NULL)
        ORDER BY p.created_at DESC
        LIMIT 9
    ");
    $db_portfolios = $port_stmt->fetchAll();
    foreach ($db_portfolios as $dp) {
        $classified_items[] = [
            'title' => $dp['title'],
            'subtitle' => ($dp['professional_name'] ? $dp['professional_name'] . ' • ' : '') . ($dp['locality'] ?: 'All areas') . ' - ' . (substr(strip_tags($dp['description']), 0, 48) . '...'),
            'category' => $dp['category_name'] ?: 'Services',
            'link' => 'portfolio_detail.php?id=' . $dp['id']
        ];
    }
} catch (Exception $e) {
    // Ignore if table issue
}

// Fetch shops marked for front to populate or supplement classifieds
if (count($classified_items) < 9) {
    try {
        $shop_stmt = $pdo->query("
            SELECT * FROM shops
            WHERE (show_on_front = 1 OR show_on_front IS NULL)
            ORDER BY (type = 'privilege' OR type = 'classic') DESC, created_at DESC
            LIMIT 9
        ");
        $db_shops = $shop_stmt->fetchAll();
        foreach ($db_shops as $ds) {
            $classified_items[] = [
                'title' => $ds['name'],
                'subtitle' => ($ds['locality'] ? $ds['locality'] . ' • ' : '') . (substr(strip_tags($ds['description'] ?: 'Verified local merchant'), 0, 52) . '...'),
                'category' => $ds['category'] ?: 'Local Business',
                'link' => 'shop_detail.php?id=' . $ds['id']
            ];
            if (count($classified_items) >= 9) break;
        }
    } catch (Exception $e) {
        // Ignore
    }
}

// Default classified items if still empty
if (empty($classified_items)) {
    $classified_items = [
        ['title' => '2BHK house for rent', 'subtitle' => 'Near town centre, family preferred. Ready to move in.', 'category' => 'Real estate', 'link' => 'localguide.php'],
        ['title' => 'Staff wanted – showroom', 'subtitle' => 'Sales executives, freshers welcome. Walk in this week.', 'category' => 'Jobs', 'link' => 'localguide.php'],
        ['title' => 'Used two-wheeler for sale', 'subtitle' => 'Single owner, 2020 model, all papers clear.', 'category' => 'Vehicles', 'link' => 'shopping.php'],
        ['title' => 'Home tutor available', 'subtitle' => 'Maths & science for classes 8–12.', 'category' => 'Services', 'link' => 'portfolios.php'],
        ['title' => 'Catering for functions', 'subtitle' => 'Weddings, housewarming and office events.', 'category' => 'Services', 'link' => 'portfolios.php'],
        ['title' => 'Shop space for lease', 'subtitle' => 'Ground floor, main road frontage.', 'category' => 'Real estate', 'link' => 'localguide.php'],
        ['title' => 'Laptop repair & upgrade', 'subtitle' => 'Same-day service, genuine parts.', 'category' => 'Services', 'link' => 'portfolios.php'],
        ['title' => 'Plot for sale', 'subtitle' => '10 cents, clear title, close to the highway.', 'category' => 'Real estate', 'link' => 'localguide.php'],
        ['title' => 'Drivers needed', 'subtitle' => 'Light vehicle licence, local routes.', 'category' => 'Jobs', 'link' => 'localguide.php']
    ];
}

// Group classified items into slides of 3
$classified_slides = array_chunk($classified_items, 3);
if (empty($classified_slides)) {
    $classified_slides = [[]];
}

// -------------------------------------------------------------
// 4. Fetch Dynamic Ads
// -------------------------------------------------------------
$banner_ad = null;
$side_ads = [];
try {
    $ads_res = $pdo->query("SELECT * FROM ads WHERE is_active = 1 LIMIT 4")->fetchAll();
    if (!empty($ads_res)) {
        $banner_ad = $ads_res[0];
        $side_ads = array_slice($ads_res, 1);
    }
} catch (Exception $e) {}

$csrf_token = get_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Powernet Digimart | Shopping, Local Guide &amp; Business Directory</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --deep:#0b5d38; --deeper:#073f26; --lime:#b8de7e; --lime-d:#9ccb5c;
  --red:#dc2a1f; --orange:#e67a1a; --blue:#667bb5; --taupe:#75645e;
  --ink:#1c1c1c; --white:#fff; --muted:#5b6470; --leaf:#39b54a;
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:'Poppins',system-ui,sans-serif;color:var(--ink);background:#fff;line-height:1.5}
a{text-decoration:none;color:inherit}
img{display:block;max-width:100%}
:focus-visible{outline:3px solid var(--orange);outline-offset:2px}
.wrap{max-width:1240px;margin:0 auto;padding:0 20px}

/* Header */
.top{background:var(--deep);color:#fff}
.top .wrap{display:flex;align-items:center;justify-content:space-between;height:84px}
.brand{display:flex;flex-direction:column;line-height:1}
.brand small{font-size:12px;font-weight:500;letter-spacing:.3px}
.brand b{font-size:34px;font-weight:700;letter-spacing:-.5px;display:flex;align-items:center}
.brand b span.m{color:var(--leaf);position:relative}
.brand b span.m i{position:absolute;left:50%;top:-9px;transform:translateX(-50%);font-size:13px;color:#fff}
.auth{display:flex;gap:26px;font-weight:600;font-size:15px;align-items:center}
.auth a:hover{color:var(--lime)}
.auth .reg{background:var(--lime);color:var(--deeper);padding:7px 18px;border-radius:999px}
.auth .reg:hover{color:var(--deeper);background:#fff}

/* Nav */
.menu{background:var(--lime);position:sticky;top:0;z-index:50;box-shadow:0 2px 0 rgba(0,0,0,.06)}
.menu .wrap{display:flex;align-items:center;justify-content:space-between}
.menu ul{display:flex;list-style:none;gap:6px}
.menu li a{display:block;padding:13px 16px;font-weight:600;font-size:15px;color:var(--deeper);border-bottom:3px solid transparent}
.menu li a:hover,.menu li a.on{border-color:var(--deeper);background:rgba(255,255,255,.3)}
.burger{display:none;background:none;border:0;font-size:22px;color:var(--deeper);padding:10px;cursor:pointer}

/* Headings */
.h-c{text-align:center;font-size:22px;font-weight:700;padding:22px 0 14px}

/* Sliders */
.slider{position:relative;overflow:hidden}
.track{display:flex;transition:transform .6s cubic-bezier(.65,0,.35,1)}
.slide{min-width:100%}
.arrow{position:absolute;top:50%;transform:translateY(-50%);z-index:5;background:none;border:0;color:#fff;font-size:30px;width:56px;height:80px;cursor:pointer;text-shadow:0 2px 8px rgba(0,0,0,.4)}
.arrow:hover{transform:translateY(-50%) scale(1.15)}
.arrow.prev{left:6px}.arrow.next{right:6px}
.dots{position:absolute;bottom:12px;left:0;right:0;display:flex;justify-content:center;gap:8px;z-index:5}
.dots button{width:10px;height:10px;border-radius:50%;border:0;background:rgba(255,255,255,.5);cursor:pointer}
.dots button.on{background:#fff;width:26px;border-radius:6px}

/* Offer slider */
.offer{background:#1d1b1a}
.offer .slide{display:grid;grid-template-columns:1.1fr .9fr;height:400px;position:relative}
.offer .pic{background:radial-gradient(circle at 50% 50%,#8d8a88 0%,#2d2a29 70%);display:flex;align-items:center;justify-content:center;overflow:hidden}
.offer .pic img{height:100%;width:100%;object-fit:cover;mix-blend-mode:luminosity;opacity:.9}
.offer .tag{background:var(--red);clip-path:polygon(18% 0,100% 0,100% 100%,0 100%);margin-left:-8%;color:#fff;display:flex;flex-direction:column;justify-content:center;padding:0 60px 0 14%;position:relative}
.offer .tag::before{content:"";position:absolute;inset:0;background:var(--orange);clip-path:polygon(0 0,18% 0,0 100%);margin-left:-3%;z-index:-1}
.tag .big{font-size:clamp(48px,7vw,92px);font-weight:800;font-style:italic;line-height:.95;letter-spacing:-2px}
.tag .pct{font-size:clamp(44px,6vw,80px);font-weight:800;font-style:italic;line-height:1;margin-top:8px}
.tag .pct small{font-size:.4em;display:inline-block;transform:rotate(-90deg);transform-origin:left bottom;margin-left:6px}
.tag p{font-size:15px;font-weight:500;margin-top:10px;opacity:.95}

/* Ad banner */
.ad-wide{margin:20px auto;background:var(--taupe);height:100px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:32px;font-weight:700;position:relative;overflow:hidden}
.ad-wide img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.28}
.ad-wide span{position:relative}

/* Shopping */
.shop{background:var(--lime);padding:22px 0 28px}
.shop-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px}
.shop-head h2{font-size:30px;font-weight:700;color:var(--ink)}
.shop-head .mini{font-size:15px;color:var(--deeper);font-weight:600}
.shop-head .mini b{color:var(--deep);font-size:22px;font-weight:700}
.shop-head .view{font-weight:600;color:var(--deeper);border-bottom:2px solid var(--deeper)}
.grid5{display:grid;grid-template-columns:repeat(5,1fr);gap:20px}
.prod{background:#fff;display:flex;flex-direction:column;box-shadow:0 2px 0 rgba(0,0,0,.08);border-radius:4px;overflow:hidden;position:relative}
.prod .ph{aspect-ratio:1/1.1;overflow:hidden;background:#f3f3f3;position:relative}
.prod .ph img{width:100%;height:100%;object-fit:cover;transition:transform .4s}
.prod:hover .ph img{transform:scale(1.06)}
.prod .info{padding:10px 12px 0;font-size:14px;font-weight:600;display:flex;justify-content:space-between;align-items:baseline;gap:6px}
.prod .info s{color:#999;font-weight:400;font-size:12px}
.prod .info em{font-style:normal;color:var(--deep)}
.buy{margin:10px auto 12px;background:var(--red);color:#fff;font-weight:600;font-size:13px;padding:6px 28px;border:0;cursor:pointer;font-family:inherit;text-align:center;display:inline-block;border-radius:3px}
.buy:hover{background:#b81f16;color:#fff}

/* Classified */
.classified .slide{height:260px;background:var(--blue);display:flex;align-items:center;justify-content:center;padding:0 90px}
.classified .items{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;width:100%;max-width:1000px}
.cl{background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.35);color:#fff;padding:18px;display:flex;flex-direction:column;gap:6px;border-radius:4px;transition:background .2s}
.cl:hover{background:rgba(255,255,255,.24)}
.cl b{font-size:16px}
.cl span{font-size:13px;opacity:.9}
.cl em{font-style:normal;font-size:12px;background:#fff;color:var(--blue);align-self:flex-start;padding:2px 10px;font-weight:600;margin-top:4px;border-radius:2px}

/* Bottom */
.bottom{padding:34px 0 40px}
.bottom .wrap{display:grid;grid-template-columns:200px 1fr;gap:26px}
.side{display:flex;flex-direction:column;gap:16px}
.side a{background:var(--orange);color:#fff;font-weight:700;font-size:20px;text-align:center;display:flex;align-items:center;justify-content:center;flex:1;min-height:92px;padding:10px;position:relative;overflow:hidden}
.side a img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.22}
.side a span{position:relative}
.signup{text-align:center}
.signup h2{font-size:30px;font-weight:700;line-height:1.2}
.signup>p{font-size:12px;color:var(--muted);margin-bottom:14px}
.su-grid{display:grid;grid-template-columns:1.2fr 1fr;gap:26px;text-align:left;align-items:start}
.dir .lead{font-size:18px;font-weight:500}
.dir h3{font-size:26px;font-weight:700;line-height:1.15;margin-bottom:2px}
.dir .sub{font-size:12px;color:var(--muted);margin-bottom:14px}
.reason{display:flex;gap:14px;margin-bottom:14px;align-items:flex-start}
.reason .ic{flex:0 0 46px;height:46px;border-radius:10px;background:#eaf6d9;color:var(--deep);display:flex;align-items:center;justify-content:center;font-size:20px}
.reason b{display:block;font-size:15px}
.reason p{font-size:12.5px;color:var(--muted);line-height:1.45}
.form{background:var(--lime);padding:16px}
.form input[type=text],.form input[type=tel],.form input[type=email],.form input[type=password]{width:100%;padding:11px 12px;border:0;margin-bottom:11px;font:inherit;font-size:14px;background:#fff}
.form label.agree{display:flex;gap:10px;align-items:flex-start;font-size:11px;margin:2px 0 12px;line-height:1.35}
.form label.agree input{margin-top:2px;accent-color:var(--deep);width:16px;height:16px;flex:none}
.form label.agree a{text-decoration:underline}
.form button{width:100%;background:#0c8c3a;color:#fff;font:inherit;font-weight:700;font-size:18px;padding:9px;border:0;cursor:pointer}
.form button:hover{background:var(--deep)}

/* Footer */
footer{background:#1d1b1a;color:#e8e8e8;text-align:center;padding:26px 20px;font-size:14px}
footer b{color:#fff}
footer .soc{margin-top:8px;display:flex;justify-content:center;gap:18px;font-size:16px}
footer .soc a:hover{color:var(--lime)}

/* Toast alert for quick feedback */
.toast-msg{position:fixed;bottom:20px;right:20px;background:var(--deep);color:#fff;padding:12px 20px;border-radius:8px;box-shadow:0 4px 14px rgba(0,0,0,0.25);z-index:9999;font-weight:600;display:none}

/* Responsive */
@media(max-width:1000px){
  .grid5{grid-template-columns:repeat(3,1fr)}
  .classified .items{grid-template-columns:1fr 1fr}.classified .items .cl:nth-child(3){display:none}
  .su-grid{grid-template-columns:1fr}
}
@media(max-width:780px){
  .burger{display:block}
  .menu ul{display:none;position:absolute;top:100%;left:0;right:0;background:var(--lime);flex-direction:column;padding:6px 20px 12px;box-shadow:0 8px 14px rgba(0,0,0,.15)}
  .menu ul.open{display:flex}
  .offer .slide{grid-template-columns:1fr;height:360px}
  .offer .pic{position:absolute;inset:0}
  .offer .tag{position:absolute;right:0;top:0;bottom:0;width:62%;margin:0;padding:0 40px 0 18%}
  .offer .tag::before{display:none}
  .bottom .wrap{grid-template-columns:1fr}
  .side{flex-direction:row}.side a{min-height:70px;font-size:15px}
  .ad-wide{font-size:24px;height:80px}
  .brand b{font-size:28px}
  .auth{gap:14px}
}
@media(max-width:560px){
  .grid5{grid-template-columns:1fr 1fr;gap:12px}
  .classified .slide{padding:0 46px;height:300px}
  .classified .items{grid-template-columns:1fr}.classified .items .cl:nth-child(2){display:none}
  .arrow{width:40px;font-size:24px}
  .shop-head h2{font-size:24px}
  .tag p{display:none}
}
@media(prefers-reduced-motion:reduce){*{transition:none!important;scroll-behavior:auto!important}}
</style>
</head>
<body>

<header class="top">
  <div class="wrap">
    <a href="index.php" class="brand" aria-label="Powernet Digimart home">
      <small>Powernet</small>
      <b>Digi<span class="m">m<i class="fas fa-bag-shopping"></i></span>art</b>
    </a>
    <nav class="auth" aria-label="Account">
      <?php if(is_logged_in()): ?>
        <a href="<?php echo ($_SESSION['role'] === 'admin') ? 'admin/index.php' : (($_SESSION['role'] === 'agent') ? 'agent_portal.php' : (($_SESSION['role'] === 'shop_owner') ? 'shop_portal.php' : 'dashboard.php')); ?>" class="reg">
          <i class="fas fa-user-circle me-1"></i> <?php echo e($_SESSION['username']); ?>
        </a>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="register.php">Sign Up</a>
        <a href="login.php">Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<div class="menu">
  <div class="wrap">
    <ul id="mainMenu">
      <li><a href="index.php" class="on">Home</a></li>
      <li><a href="localguide.php">Localguide</a></li>
      <li><a href="shopping.php">Shopping</a></li>
      <li><a href="royalty-store.php">Royalty Store</a></li>
      <li><a href="about.php">About Us</a></li>
    </ul>
    <button class="burger" id="burger" aria-label="Menu" aria-expanded="false"><i class="fas fa-bars"></i></button>
  </div>
</div>

<main>
  <h1 class="h-c">Offer Advertisement</h1>

  <!-- Offer slider (Dynamic) -->
  <section class="slider offer" id="offer" aria-label="Offers">
    <div class="track">
      <?php foreach ($slides as $s): ?>
      <div class="slide">
        <div class="pic">
          <a href="<?php echo e($s['url']); ?>" style="display:block;width:100%;height:100%;">
            <img src="<?php echo e($s['image']); ?>" alt="Offer">
          </a>
        </div>
        <div class="tag">
          <div class="big"><?php echo $s['big']; ?></div>
          <div class="pct"><?php echo $s['pct']; ?><small>OFF</small></div>
          <p><?php echo $s['desc']; ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <button class="arrow prev" aria-label="Previous"><i class="fas fa-angles-left"></i></button>
    <button class="arrow next" aria-label="Next"><i class="fas fa-angles-right"></i></button>
    <div class="dots"></div>
  </section>

  <!-- Advertisement Banner -->
  <div class="wrap">
    <a href="<?php echo !empty($banner_ad['target_url']) ? e($banner_ad['target_url']) : 'shopping.php'; ?>" class="ad-wide" aria-label="Advertisement">
      <img src="<?php echo !empty($banner_ad['image_url']) ? BASE_URL . '/' . e($banner_ad['image_url']) : 'https://picsum.photos/seed/banner-ad/1200/200'; ?>" alt="">
      <span>Advertisement</span>
    </a>
  </div>

  <!-- Shopping Grid (Dynamic Products) -->
  <section class="shop" id="shopping">
    <div class="wrap">
      <div class="shop-head">
        <h2>Shopping</h2>
        <div class="mini">Powernet <b>Digimart</b></div>
        <a class="view" href="shopping.php">View all products</a>
      </div>
      <div class="grid5">
        <?php foreach (array_slice($front_products, 0, 5) as $prod): ?>
        <article class="prod">
          <a href="product_detail.php?id=<?php echo $prod['id']; ?>" class="ph" aria-label="<?php echo e($prod['name']); ?>">
            <img src="<?php echo e($prod['image'] ?: 'https://picsum.photos/seed/' . $prod['id'] . '/400/440'); ?>" alt="<?php echo e($prod['name']); ?>" loading="lazy">
          </a>
          <div class="info">
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo e($prod['name']); ?>"><?php echo e($prod['name']); ?></span>
            <em>₹<?php echo number_format($prod['price']); ?></em>
          </div>
          <a href="product_detail.php?id=<?php echo $prod['id']; ?>" class="buy">Buy Now</a>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <h2 class="h-c" style="font-weight:500;font-size:19px">Classified News</h2>

  <!-- Classified Slider (Dynamic Services, Portfolios, & Local Shops) -->
  <section class="slider classified" id="classified" aria-label="Classified news">
    <div class="track">
      <?php foreach ($classified_slides as $group): ?>
      <div class="slide">
        <div class="items">
          <?php foreach ($group as $item): ?>
          <a href="<?php echo e($item['link']); ?>" class="cl">
            <b><?php echo e($item['title']); ?></b>
            <span><?php echo e($item['subtitle']); ?></span>
            <em><?php echo e($item['category']); ?></em>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <button class="arrow prev" aria-label="Previous"><i class="fas fa-angles-left"></i></button>
    <button class="arrow next" aria-label="Next"><i class="fas fa-angles-right"></i></button>
    <div class="dots"></div>
  </section>

  <!-- Bottom: ads + account -->
  <section class="bottom" id="signup">
    <div class="wrap">
      <aside class="side" aria-label="Advertisements">
        <?php if (!empty($side_ads)): ?>
          <?php foreach (array_slice($side_ads, 0, 3) as $sa): ?>
            <a href="<?php echo e($sa['target_url'] ?: '#'); ?>">
              <img src="<?php echo BASE_URL . '/' . e($sa['image_url']); ?>" alt="">
              <span>Advertisement</span>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <a href="#"><img src="https://picsum.photos/seed/ad-one/300/200" alt=""><span>Advertisement</span></a>
          <a href="#"><img src="https://picsum.photos/seed/ad-two/300/200" alt=""><span>Advertisement</span></a>
          <a href="#"><img src="https://picsum.photos/seed/ad-three/300/200" alt=""><span>Advertisement</span></a>
        <?php endif; ?>
      </aside>

      <div class="signup">
        <h2>Create an Account</h2>
        <p>Explore some of the best tips from around the world from our partners and friends.</p>
        <div class="su-grid">
          <div class="dir">
            <div class="lead">A few reasons you’ll love Online</div>
            <h3>Business Directory</h3>
            <div class="sub">Benefits of listing your business to a local online directory</div>
            <div class="reason">
              <div class="ic"><i class="fas fa-chart-column"></i></div>
              <div><b>Enhancing Your Business</b><p>Imagine you have made your presence online through a local online directory, but your competitors have…</p></div>
            </div>
            <div class="reason">
              <div class="ic"><i class="fas fa-rocket"></i></div>
              <div><b>Advertising Your Business</b><p>Advertising your business to area-specific has many advantages. For local businessmen, it is an opportunity.</p></div>
            </div>
            <div class="reason">
              <div class="ic"><i class="fas fa-hand-holding-dollar"></i></div>
              <div><b>Develop Brand Image</b><p>Your local business too needs brand management and image making. As you know the local market…</p></div>
            </div>
          </div>

          <form class="form" action="register.php" method="post">
            <input type="hidden" name="role" value="member">
            <input type="text" name="username" placeholder="Username / Name" autocomplete="name" required>
            <input type="tel" name="mobile" placeholder="Mobile" autocomplete="tel">
            <input type="email" name="email" placeholder="E-Mail" autocomplete="email" required>
            <input type="password" name="password" placeholder="Password" autocomplete="new-password" required>
            <label class="agree"><input type="checkbox" required><span>By signing up, you agree to the <a href="about.php">Terms and Conditions</a> and <a href="about.php">Privacy Policy</a></span></label>
            <button type="submit">SUBMIT NOW</button>
          </form>
        </div>
      </div>
    </div>
  </section>
</main>

<footer>
  <div>Powered By : <b>Power Net Associats</b></div>
  <div class="soc">
    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
    <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
  </div>
</footer>

<div id="toast" class="toast-msg"></div>

<script>
document.getElementById('burger').addEventListener('click',function(){
  var m=document.getElementById('mainMenu'),o=m.classList.toggle('open');
  this.setAttribute('aria-expanded',o);
});
function makeSlider(id,delay){
  var root=document.getElementById(id);
  if (!root) return;
  var track=root.querySelector('.track');
  if (!track || !track.children.length) return;
  var n=track.children.length,dotsBox=root.querySelector('.dots'),i=0,t;
  dotsBox.innerHTML = '';
  for(var k=0;k<n;k++){(function(k){var b=document.createElement('button');b.setAttribute('aria-label','Slide '+(k+1));b.onclick=function(){go(k)};dotsBox.appendChild(b)})(k)}
  function go(x){
    i=(x+n)%n;track.style.transform='translateX(-'+i*100+'%)';
    [].forEach.call(dotsBox.children,function(d,j){d.classList.toggle('on',j===i)});
    reset();
  }
  function reset(){clearInterval(t);if(!matchMedia('(prefers-reduced-motion:reduce)').matches)t=setInterval(function(){go(i+1)},delay)}
  var prevBtn = root.querySelector('.prev');
  var nextBtn = root.querySelector('.next');
  if(prevBtn) prevBtn.onclick=function(){go(i-1)};
  if(nextBtn) nextBtn.onclick=function(){go(i+1)};
  root.addEventListener('mouseenter',function(){clearInterval(t)});
  root.addEventListener('mouseleave',reset);
  go(0);
}
makeSlider('offer',5000);
makeSlider('classified',6500);
</script>
</body>
</html>
