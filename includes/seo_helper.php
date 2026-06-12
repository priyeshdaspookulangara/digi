<?php
require_once 'config.php';

/**
 * Generates JSON-LD structured data for a product.
 */
function renderProductJSONLD($product, $shop) {
    $data = [
        "@context" => "https://schema.org/",
        "@type" => "Product",
        "name" => $product['name'],
        "image" => $product['image'],
        "description" => $product['description'],
        "brand" => [
            "@type" => "Brand",
            "name" => $product['brand'] ?? $shop['name']
        ],
        "offers" => [
            "@type" => "Offer",
            "url" => BASE_URL . "/product_detail.php?id=" . $product['id'],
            "priceCurrency" => "INR",
            "price" => $product['price'],
            "availability" => "https://schema.org/InStock",
            "seller" => [
                "@type" => "Organization",
                "name" => $shop['name']
            ]
        ]
    ];

    return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generates JSON-LD structured data for a shop (LocalBusiness).
 */
function renderShopJSONLD($shop) {
    $data = [
        "@context" => "https://schema.org",
        "@type" => "LocalBusiness",
        "name" => $shop['name'],
        "image" => $shop['logo'],
        "description" => $shop['description'],
        "url" => BASE_URL . "/shop_portal.php?id=" . $shop['id'],
        "address" => [
            "@type" => "PostalAddress",
            "addressLocality" => $shop['locality']
        ]
    ];

    return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Renders Meta tags for Open Graph and Twitter Cards.
 */
function renderMetaTags($title, $description, $image, $url, $keywords = "") {
    $html = "";
    if ($keywords) {
        $html .= '    <meta name="keywords" content="' . htmlspecialchars($keywords) . '">' . "\n";
    }

    // Open Graph
    $html .= '    <meta property="og:title" content="' . htmlspecialchars($title) . '">' . "\n";
    $html .= '    <meta property="og:description" content="' . htmlspecialchars($description) . '">' . "\n";
    if ($image) {
        $html .= '    <meta property="og:image" content="' . htmlspecialchars($image) . '">' . "\n";
    }
    $html .= '    <meta property="og:url" content="' . htmlspecialchars($url) . '">' . "\n";
    $html .= '    <meta property="og:type" content="website">' . "\n";

    // Twitter Cards
    $html .= '    <meta name="twitter:card" content="summary_large_image">' . "\n";
    $html .= '    <meta name="twitter:title" content="' . htmlspecialchars($title) . '">' . "\n";
    $html .= '    <meta name="twitter:description" content="' . htmlspecialchars($description) . '">' . "\n";
    if ($image) {
        $html .= '    <meta name="twitter:image" content="' . htmlspecialchars($image) . '">' . "\n";
    }

    return $html;
}
