<?php
/**
 * XRP Specter - Public Landing Page
 * Public page with news, charts, and platform information
 */

define('XRP_SPECTER', true);
require_once 'config.php';

// Get public data (no authentication required)
$price_data = [
    'price' => 0.5234,
    'change_24h' => 2.45,
    'market_cap' => 28000000000,
    'volume_24h' => 1200000000,
    'high_24h' => 0.5340,
    'low_24h' => 0.5120
];

$news_items = [
    [
        'title' => 'XRP Price Surges 5% Following Major Partnership Announcement',
        'link' => '#',
        'description' => 'Ripple announces new partnership with major financial institution, driving XRP adoption in cross-border payments.',
        'pubDate' => date('Y-m-d H:i:s'),
        'category' => 'Partnership'
    ],
    [
        'title' => 'SEC vs Ripple: Latest Developments in the Legal Battle',
        'link' => '#',
        'description' => 'Recent court filings reveal new evidence in the ongoing case, with potential implications for XRP classification.',
        'pubDate' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'category' => 'Legal'
    ],
    [
        'title' => 'XRP Ledger Upgrade Brings New Features and Improvements',
        'link' => '#',
        'description' => 'Latest upgrade introduces enhanced smart contract capabilities and improved transaction throughput.',
        'pubDate' => date('Y-m-d H:i:s', strtotime('-4 hours')),
        'category' => 'Technology'
    ],
    [
        'title' => 'Major Banks Adopt RippleNet for International Transfers',
        'link' => '#',
        'description' => 'Leading financial institutions join RippleNet network, expanding XRP utility in global finance.',
        'pubDate' => date('Y-m-d H:i:s', strtotime('-6 hours')),
        'category' => 'Adoption'
    ]
];

$market_stats = [
    'total_supply' => 100000000000,
    'circulating_supply' => 99999999999,
    'market_rank' => 6,
    'all_time_high' => 3.40,
    'all_time_low' => 0.002802
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XRP Specter - Modern XRP Dashboard & Community</title>
    <meta name="description" content="Real-time XRP price tracking, news, charts, and community features. Join the ultimate XRP dashboard experience.">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Preload critical resources -->
    <link rel="preload" href="assets/js/main.js" as="script">
</head>
<body class="theme-dark">
    <!-- Loading Screen -->
    <div id="loading-screen" class="loading-screen">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <h2>XRP Specter</h2>
            <p>Loading...</p>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <div class="header-left">
                <div class="logo">
                    <span class="logo-text">XRP Specter</span>
                </div>
                
                <!-- Price Ticker -->
                <div class="price-ticker">
                    <span class="ticker-label">XRP:</span>
                    <span class="ticker-price" id="live-price">$<?= number_format($price_data['price'], 4) ?></span>
                    <span class="ticker-change <?= $price_data['change_24h'] >= 0 ? 'positive' : 'negative' ?>" id="live-change">
                        <?= $price_data['change_24h'] >= 0 ? '+' : '' ?><?= number_format($price_data['change_24h'], 2) ?>%
                    </span>
                </div>
            </div>
            
            <div class="header-right">
                <!-- Theme Toggle -->
                <button class="theme-toggle" id="theme-toggle" aria-label="Toggle theme">
                    <svg class="theme-icon" viewBox="0 0 24 24">
                        <path class="sun-path" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        <path class="moon-path" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>
                
                <!-- Auth Buttons -->
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-secondary">Login</a>
                    <a href="register.php" class="btn btn-primary">Join Now</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">Welcome to XRP Specter</h1>
                    <p class="hero-subtitle">The ultimate XRP dashboard with real-time tracking, AI-powered analysis, and a thriving community of crypto enthusiasts.</p>
                    <div class="hero-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?= number_format($market_stats['market_rank']) ?></span>
                            <span class="stat-label">Market Rank</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">$<?= number_format($price_data['market_cap'] / 1000000000, 1) ?>B</span>
                            <span class="stat-label">Market Cap</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">$<?= number_format($price_data['volume_24h'] / 1000000, 1) ?>M</span>
                            <span class="stat-label">24h Volume</span>
                        </div>
                    </div>
                    <div class="hero-actions">
                        <a href="register.php" class="btn btn-primary btn-large">Get Started Free</a>
                        <a href="#features" class="btn btn-secondary btn-large">Learn More</a>
                    </div>
                </div>
            </div>
        </section>



        <!-- XRP Price Chart -->
        <section class="chart-section">
            <div class="card card-large">
                <div class="card-header">
                    <h3>XRP Price Chart</h3>
                    <div class="chart-controls">
                        <button class="chart-period active" data-period="7d">7D</button>
                        <button class="chart-period" data-period="30d">30D</button>
                        <button class="chart-period" data-period="90d">90D</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="price-chart" width="400" height="200"></canvas>
                </div>
            </div>
        </section>

        <div class="container">
            <!-- Market Information Grid -->
            <section class="market-info-grid">
                <div class="card">
                    <div class="card-header">
                        <h3>Market Overview</h3>
                    </div>
                    <div class="card-body">
                        <div class="market-stats">
                            <div class="stat-row">
                                <span class="stat-label">Current Price</span>
                                <span class="stat-value">$<?= number_format($price_data['price'], 4) ?></span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">24h Change</span>
                                <span class="stat-value <?= $price_data['change_24h'] >= 0 ? 'positive' : 'negative' ?>">
                                    <?= $price_data['change_24h'] >= 0 ? '+' : '' ?><?= number_format($price_data['change_24h'], 2) ?>%
                                </span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">24h High</span>
                                <span class="stat-value">$<?= number_format($price_data['high_24h'], 4) ?></span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">24h Low</span>
                                <span class="stat-value">$<?= number_format($price_data['low_24h'], 4) ?></span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Market Cap</span>
                                <span class="stat-value">$<?= number_format($price_data['market_cap'] / 1000000000, 1) ?>B</span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">24h Volume</span>
                                <span class="stat-value">$<?= number_format($price_data['volume_24h'] / 1000000, 1) ?>M</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Token Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="token-info">
                            <div class="info-item">
                                <span class="info-label">Total Supply</span>
                                <span class="info-value"><?= number_format($market_stats['total_supply']) ?> XRP</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Circulating Supply</span>
                                <span class="info-value"><?= number_format($market_stats['circulating_supply']) ?> XRP</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Market Rank</span>
                                <span class="info-value">#<?= number_format($market_stats['market_rank']) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">All Time High</span>
                                <span class="info-value">$<?= number_format($market_stats['all_time_high'], 2) ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">All Time Low</span>
                                <span class="info-value">$<?= number_format($market_stats['all_time_low'], 4) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Latest News -->
            <section class="news-section">
                <div class="card">
                    <div class="card-header">
                        <h3>Latest XRP News</h3>
                        <a href="#news" class="card-link">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="news-list">
                            <?php foreach ($news_items as $news): ?>
                            <div class="news-item">
                                <div class="news-category"><?= htmlspecialchars($news['category']) ?></div>
                                <h4 class="news-title"><?= htmlspecialchars($news['title']) ?></h4>
                                <p class="news-description"><?= htmlspecialchars($news['description']) ?></p>
                                <div class="news-meta">
                                    <span class="news-time"><?= date('M j, Y g:i A', strtotime($news['pubDate'])) ?></span>
                                    <a href="<?= htmlspecialchars($news['link']) ?>" class="news-link">Read More</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section id="features" class="features-section">
                <h2 class="section-title">Why Choose XRP Specter?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <h3>Real-time Data</h3>
                        <p>Get live XRP prices, charts, and market data updated every second from reliable sources.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🤖</div>
                        <h3>AI Analysis</h3>
                        <p>Advanced AI-powered market analysis and sentiment tracking to help you make informed decisions.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">📱</div>
                        <h3>Mobile Ready</h3>
                        <p>Fully responsive design that works perfectly on desktop, tablet, and mobile devices.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🎮</div>
                        <h3>Trading Simulator</h3>
                        <p>Practice trading with virtual money in our risk-free trading simulator with real market data.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🏆</div>
                        <h3>Community Features</h3>
                        <p>Join polls, earn XP, climb leaderboards, and connect with fellow XRP enthusiasts.</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">🔒</div>
                        <h3>Secure & Private</h3>
                        <p>Enterprise-grade security with no personal data collection. Your privacy is our priority.</p>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="cta-section">
                <div class="card">
                    <div class="card-body">
                        <div class="cta-content">
                            <h2>Ready to Start Your XRP Journey?</h2>
                            <p>Join thousands of users who trust XRP Specter for their crypto tracking and analysis needs.</p>
                            <div class="cta-actions">
                                <a href="register.php" class="btn btn-primary btn-large">Create Free Account</a>
                                <a href="login.php" class="btn btn-secondary btn-large">Sign In</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-left">
                    <span class="footer-text">© 2024 XRP Specter. All rights reserved.</span>
                </div>
                <div class="footer-right">
                    <span class="footer-text">Built with ❤️ for the XRP community</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hide loading screen
            setTimeout(() => {
                document.getElementById('loading-screen').style.display = 'none';
            }, 1000);

            // Initialize price chart
            const ctx = document.getElementById('price-chart').getContext('2d');
            const chartData = {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'XRP Price (USD)',
                    data: [0.48, 0.49, 0.51, 0.52, 0.53, 0.52, 0.52],
                    borderColor: '#00d4aa',
                    backgroundColor: 'rgba(0, 212, 170, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            };

            new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#ffffff'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)'
                            },
                            ticks: {
                                color: '#ffffff'
                            }
                        }
                    }
                }
            });

            // Theme toggle
            document.getElementById('theme-toggle').addEventListener('click', function() {
                document.body.classList.toggle('theme-light');
                document.body.classList.toggle('theme-dark');
            });

            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html> 