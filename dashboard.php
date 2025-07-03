<?php
/**
 * XRP Specter - User Dashboard
 * Full dashboard with all features (requires login)
 */

define('XRP_SPECTER', true);
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';
require_once 'utils.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Get current user
$user = current_user();

// Get real-time data
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
        'description' => 'Ripple announces new partnership with major financial institution...',
        'pubDate' => date('Y-m-d H:i:s'),
        'category' => 'Partnership'
    ],
    [
        'title' => 'SEC vs Ripple: Latest Developments in the Legal Battle',
        'link' => '#',
        'description' => 'Recent court filings reveal new evidence in the ongoing case...',
        'pubDate' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'category' => 'Legal'
    ],
    [
        'title' => 'XRP Ledger Upgrade Brings New Features and Improvements',
        'link' => '#',
        'description' => 'Latest upgrade introduces enhanced smart contract capabilities...',
        'pubDate' => date('Y-m-d H:i:s', strtotime('-4 hours')),
        'category' => 'Technology'
    ]
];

$polls = [
    [
        'id' => 1,
        'question' => 'What do you think about XRP\'s price movement this week?',
        'options' => ['Bullish', 'Bearish', 'Neutral'],
        'votes' => [45, 23, 32],
        'total_votes' => 100,
        'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days'))
    ]
];

$leaderboard = [
    ['username' => 'crypto_king', 'xp_points' => 5420, 'level' => 12],
    ['username' => 'xrp_trader', 'xp_points' => 3890, 'level' => 10],
    ['username' => 'blockchain_guru', 'xp_points' => 3240, 'level' => 9],
    ['username' => $user['username'], 'xp_points' => $user['xp_points'], 'level' => $user['level']],
    ['username' => 'newbie_trader', 'xp_points' => 890, 'level' => 4]
];

$ai_summary_text = "Based on recent market analysis, XRP shows strong momentum with increasing adoption in cross-border payments. The technical indicators suggest a bullish trend, while regulatory clarity continues to improve market sentiment.";

$trade_stats = [
    'total_trades' => 12,
    'profitable_trades' => 8,
    'completed_trades' => 12,
    'total_profit_loss' => 150.00
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - XRP Specter</title>
    <meta name="description" content="Your personal XRP dashboard with real-time data, calculator, and community features">
    
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
            <p>Loading dashboard...</p>
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
                
                <!-- User Menu -->
                <div class="user-menu">
                    <div class="user-avatar">
                        <img src="<?= $user['avatar'] ?: 'assets/img/default-avatar.png' ?>" alt="<?= htmlspecialchars($user['username']) ?>">
                    </div>
                    <div class="user-dropdown">
                        <div class="user-info">
                            <span class="username"><?= htmlspecialchars($user['username']) ?></span>
                            <span class="user-level">Level <?= $user['level'] ?></span>
                            <span class="user-xp"><?= number_format($user['xp_points']) ?> XP</span>
                        </div>
                        <div class="dropdown-menu">
                            <a href="#profile" class="dropdown-item">Profile</a>
                            <a href="#settings" class="dropdown-item">Settings</a>
                            <?php if (is_admin()): ?>
                                <a href="admin.php" class="dropdown-item">Admin Panel</a>
                            <?php endif; ?>
                            <a href="logout.php" class="dropdown-item">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Welcome Message -->
            <div class="welcome-banner">
                <h1>Welcome back, <?= htmlspecialchars($user['username']) ?>!</h1>
                <p>Your XRP dashboard is ready. Track prices, read news, and engage with the community.</p>
            </div>

            <!-- Cryptocurrency Converter (Minimalist) -->
            <section class="crypto-converter-section minimal">
                <div class="converter-card">
                    <form id="crypto-converter-form" class="crypto-converter-form minimal">
                        <div class="converter-fields">
                            <input type="number" id="from-amount" class="converter-input" value="1" min="0" step="any" required placeholder="Amount">
                            <select id="from-currency" class="converter-select">
                                <option value="xrp">XRP</option>
                                <option value="btc">BTC</option>
                                <option value="eth">ETH</option>
                                <option value="usd">USD</option>
                                <option value="eur">EUR</option>
                            </select>
                            <button type="button" id="swap-btn" class="swap-btn" title="Swap">⇄</button>
                            <select id="to-currency" class="converter-select">
                                <option value="usd">USD</option>
                                <option value="eur">EUR</option>
                                <option value="xrp">XRP</option>
                                <option value="btc">BTC</option>
                                <option value="eth">ETH</option>
                            </select>
                        </div>
                        <button type="submit" class="converter-btn">Convert</button>
                    </form>
                    <div id="converter-result" class="converter-result minimal"></div>
                </div>
            </section>

            <!-- Dashboard Grid -->
            <div class="dashboard-grid">
                <!-- XRP Price Chart -->
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

                <!-- XRP Calculator -->
                <div class="card">
                    <div class="card-header">
                        <h3>XRP Calculator</h3>
                    </div>
                    <div class="card-body">
                        <div class="calculator-form">
                            <div class="form-group">
                                <label for="calc-amount">Amount</label>
                                <input type="number" id="calc-amount" class="form-input" placeholder="Enter amount" step="0.01">
                            </div>
                            <div class="form-group">
                                <label for="calc-operation">Operation</label>
                                <select id="calc-operation" class="form-select">
                                    <option value="usd_to_xrp">USD to XRP</option>
                                    <option value="xrp_to_usd">XRP to USD</option>
                                </select>
                            </div>
                            <button class="btn btn-primary btn-block" id="calculate-btn">Calculate</button>
                            <div class="calculator-result" id="calculator-result"></div>
                        </div>
                    </div>
                </div>

                <!-- News Feed -->
                <div class="card">
                    <div class="card-header">
                        <h3>Latest News</h3>
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

                <!-- AI Market Analysis -->
                <div class="card">
                    <div class="card-header">
                        <h3>AI Market Analysis</h3>
                        <span class="ai-badge">AI Powered</span>
                    </div>
                    <div class="card-body">
                        <div class="ai-analysis">
                            <div class="sentiment-indicator positive">
                                <span class="sentiment-label">Market Sentiment</span>
                                <span class="sentiment-value">Bullish</span>
                            </div>
                            <div class="analysis-text">
                                <?= htmlspecialchars($ai_summary_text) ?>
                            </div>
                            <div class="analysis-metrics">
                                <div class="metric">
                                    <span class="metric-label">Confidence</span>
                                    <span class="metric-value">85%</span>
                                </div>
                                <div class="metric">
                                    <span class="metric-label">Trend</span>
                                    <span class="metric-value">↗️ Upward</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Community Poll -->
                <div class="card">
                    <div class="card-header">
                        <h3>Community Poll</h3>
                        <span class="poll-status active" id="poll-status">Active</span>
                    </div>
                    <div class="card-body">
                        <div class="poll-container" id="poll-container">
                            <div class="poll-loading">Loading polls...</div>
                        </div>
                    </div>
                </div>

                <!-- Leaderboard -->
                <div class="card">
                    <div class="card-header">
                        <h3>Community Leaderboard</h3>
                    </div>
                    <div class="card-body">
                        <div class="leaderboard-list">
                            <?php foreach ($leaderboard as $index => $player): ?>
                            <div class="leaderboard-item <?= $player['username'] === $user['username'] ? 'current-user' : '' ?>">
                                <div class="rank">#<?= $index + 1 ?></div>
                                <div class="player-info">
                                    <span class="username"><?= htmlspecialchars($player['username']) ?></span>
                                    <span class="level">Level <?= $player['level'] ?></span>
                                </div>
                                <div class="xp-points"><?= number_format($player['xp_points']) ?> XP</div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Trading Simulator (updated) -->
                <div class="card">
                    <div class="card-header">
                        <h3>Trading Simulator</h3>
                    </div>
                    <div class="card-body">
                        <div class="simulator-stats" id="simulator-stats">
                            <!-- Stats will be loaded here -->
                        </div>
                        <div class="simulator-actions">
                            <button class="btn btn-primary" id="new-trade-btn">New Trade</button>
                        </div>
                        <div class="trading-history">
                            <h4>Trade History</h4>
                            <div id="trade-history-list">Loading...</div>
                        </div>
                    </div>
                </div>

                <!-- Security Tips -->
                <div class="card">
                    <div class="card-header">
                        <h3>Security Tips</h3>
                    </div>
                    <div class="card-body">
                        <div class="security-tips">
                            <div class="tip-item">
                                <span class="tip-icon">🔒</span>
                                <span class="tip-text">Enable 2FA on your crypto accounts</span>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">💾</span>
                                <span class="tip-text">Use hardware wallets for large holdings</span>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">🔍</span>
                                <span class="tip-text">Always verify transaction addresses</span>
                            </div>
                            <div class="tip-item">
                                <span class="tip-icon">📱</span>
                                <span class="tip-text">Keep your private keys offline</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <button class="action-btn" onclick="alert('Feature: Set price alert')">
                                <span class="action-icon">🔔</span>
                                <span class="action-text">Set Price Alert</span>
                            </button>
                            <button class="action-btn" onclick="alert('Feature: Share portfolio')">
                                <span class="action-icon">📤</span>
                                <span class="action-text">Share Portfolio</span>
                            </button>
                            <button class="action-btn" onclick="alert('Feature: Export data')">
                                <span class="action-icon">📊</span>
                                <span class="action-text">Export Data</span>
                            </button>
                            <button class="action-btn" onclick="alert('Feature: View analytics')">
                                <span class="action-icon">📈</span>
                                <span class="action-text">View Analytics</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modals -->
    <div id="trade-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>New Trade</h3>
                <button class="modal-close" id="close-trade-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="trade-form">
                    <div class="form-group">
                        <label for="trade-amount">Investment Amount (USD)</label>
                        <input type="number" id="trade-amount" name="amount" class="form-input" placeholder="Enter amount" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="trade-action">Action</label>
                        <select id="trade-action" name="action" class="form-select">
                            <option value="buy">Buy XRP</option>
                            <option value="sell">Sell XRP</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="cancel-trade">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Trade</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Notifications -->
    <div id="notifications" class="notifications"></div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-left">
                    <span class="footer-text">© 2024 XRP Specter. All rights reserved.</span>
                </div>
                <div class="footer-right">
                    <span class="footer-text">Welcome back, <?= htmlspecialchars($user['username']) ?>!</span>
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

            // Calculator functionality
            document.getElementById('calculate-btn').addEventListener('click', function() {
                const amount = parseFloat(document.getElementById('calc-amount').value) || 0;
                const operation = document.getElementById('calc-operation').value;
                const currentPrice = <?= $price_data['price'] ?>;
                
                if (isNaN(amount) || amount <= 0) {
                    document.getElementById('calculator-result').innerHTML = '<div class="error">Please enter a valid amount</div>';
                    return;
                }
                
                let result = '';
                if (operation === 'usd_to_xrp') {
                    const xrpAmount = amount / currentPrice;
                    result = `${amount.toFixed(2)} USD = ${xrpAmount.toFixed(2)} XRP`;
                } else {
                    const usdAmount = amount * currentPrice;
                    result = `${amount.toFixed(2)} XRP = $${usdAmount.toFixed(2)} USD`;
                }
                
                document.getElementById('calculator-result').innerHTML = `<div class="success">${result}</div>`;
            });

            // Theme toggle
            document.getElementById('theme-toggle').addEventListener('click', function() {
                document.body.classList.toggle('theme-light');
                document.body.classList.toggle('theme-dark');
            });

            // Trading Simulator Integration
            function loadTradingStats() {
                fetch('api/trading.php?action=stats')
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const stats = data.stats;
                            document.getElementById('simulator-stats').innerHTML = `
                                <div class="stat-item">
                                    <span class="stat-label">Total Trades</span>
                                    <span class="stat-value">${stats.total_trades}</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Win Rate</span>
                                    <span class="stat-value">${stats.completed_trades > 0 ? ((stats.profitable_trades / stats.completed_trades) * 100).toFixed(1) : 0}%</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Total P&L</span>
                                    <span class="stat-value ${stats.total_profit_loss >= 0 ? 'positive' : 'negative'}">$${stats.total_profit_loss.toFixed(2)}</span>
                                </div>
                            `;
                        }
                    });
            }

            function loadTradeHistory() {
                fetch('api/trading.php?action=list')
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const trades = data.trades;
                            if (trades.length === 0) {
                                document.getElementById('trade-history-list').innerHTML = '<div>No trades yet.</div>';
                                return;
                            }
                            let html = '<table class="admin-table"><thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Buy Price</th><th>Sell Price</th><th>P&L</th><th>Status</th><th>Action</th></tr></thead><tbody>';
                            trades.forEach(trade => {
                                html += `<tr>
                                    <td>${new Date(trade.created_at).toLocaleString()}</td>
                                    <td>${trade.buy_price && !trade.sell_price ? 'Buy' : 'Sell'}</td>
                                    <td>${trade.start_amount}</td>
                                    <td>${trade.buy_price ? trade.buy_price.toFixed(4) : '-'}</td>
                                    <td>${trade.sell_price ? trade.sell_price.toFixed(4) : '-'}</td>
                                    <td class="${trade.profit_loss >= 0 ? 'positive' : 'negative'}">${trade.profit_loss !== null ? '$' + trade.profit_loss.toFixed(2) : '-'}</td>
                                    <td>${trade.status}</td>
                                    <td>${trade.status === 'active' ? `<button class='btn btn-secondary btn-sm' onclick='closeTrade(${trade.id})'>Close</button>` : ''}</td>
                                </tr>`;
                            });
                            html += '</tbody></table>';
                            document.getElementById('trade-history-list').innerHTML = html;
                        }
                    });
            }

            function createTrade(amount, action) {
                fetch('api/trading.php?action=create', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ amount, action })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Trade created!', 'success');
                        loadTradingStats();
                        loadTradeHistory();
                    } else {
                        showNotification(data.error || 'Failed to create trade', 'error');
                    }
                });
            }

            function closeTrade(tradeId) {
                fetch('api/trading.php?action=close', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ trade_id: tradeId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Trade closed! P&L: $' + data.profit_loss.toFixed(2), 'success');
                        loadTradingStats();
                        loadTradeHistory();
                    } else {
                        showNotification(data.error || 'Failed to close trade', 'error');
                    }
                });
            }

            // Modal handling
            const newTradeBtn = document.getElementById('new-trade-btn');
            const tradeModal = document.getElementById('trade-modal');
            const closeModal = document.getElementById('close-trade-modal');
            const cancelTrade = document.getElementById('cancel-trade');
            const tradeForm = document.getElementById('trade-form');

            newTradeBtn.addEventListener('click', function() {
                tradeModal.style.display = 'block';
            });
            closeModal.addEventListener('click', function() {
                tradeModal.style.display = 'none';
            });
            cancelTrade.addEventListener('click', function() {
                tradeModal.style.display = 'none';
            });
            tradeForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const amount = parseFloat(document.getElementById('trade-amount').value);
                const action = document.getElementById('trade-action').value;
                if (isNaN(amount) || amount <= 0) {
                    showNotification('Enter a valid amount', 'error');
                    return;
                }
                createTrade(amount, action);
                tradeModal.style.display = 'none';
            });

            // Initial load
            loadTradingStats();
            loadTradeHistory();

            // Polling functionality
            loadPolls();

            async function loadPolls() {
                try {
                    const response = await fetch('api/polls.php?action=list');
                    const data = await response.json();
                    
                    if (data.success && data.polls.length > 0) {
                        displayPoll(data.polls[0]);
                    } else {
                        document.getElementById('poll-container').innerHTML = '<div class="poll-empty">No active polls available</div>';
                    }
                } catch (error) {
                    console.error('Error loading polls:', error);
                    document.getElementById('poll-container').innerHTML = '<div class="poll-error">Error loading polls</div>';
                }
            }

            function displayPoll(poll) {
                const container = document.getElementById('poll-container');
                const options = JSON.parse(poll.options);
                
                let pollHtml = `
                    <h4 class="poll-question">${escapeHtml(poll.question)}</h4>
                    <div class="poll-options">
                `;
                
                options.forEach((option, index) => {
                    const votes = poll.total_votes || 0;
                    const percentage = votes > 0 ? Math.round((votes / poll.total_votes) * 100) : 0;
                    
                    pollHtml += `
                        <div class="poll-option ${poll.user_has_voted ? 'voted' : ''}" data-option="${escapeHtml(option)}">
                            <div class="poll-bar">
                                <div class="poll-fill" style="width: ${percentage}%"></div>
                            </div>
                            <div class="poll-label">
                                <span class="option-text">${escapeHtml(option)}</span>
                                <span class="option-votes">${votes} votes (${percentage}%)</span>
                            </div>
                            ${!poll.user_has_voted ? `<button class="vote-btn" onclick="submitVote(${poll.id}, '${escapeHtml(option)}')">Vote</button>` : ''}
                        </div>
                    `;
                });
                
                pollHtml += `
                    </div>
                    <div class="poll-footer">
                        <span class="total-votes">${poll.total_votes || 0} total votes</span>
                        ${poll.expires_at ? `<span class="poll-expires">Expires: ${new Date(poll.expires_at).toLocaleDateString()}</span>` : ''}
                    </div>
                `;
                
                container.innerHTML = pollHtml;
            }

            async function submitVote(pollId, option) {
                try {
                    const response = await fetch('api/polls.php?action=vote', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            poll_id: pollId,
                            option: option
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showNotification(`Vote submitted! +${data.xp_awarded} XP`, 'success');
                        loadPolls(); // Reload polls to show updated results
                    } else {
                        showNotification(data.error || 'Failed to submit vote', 'error');
                    }
                } catch (error) {
                    console.error('Error submitting vote:', error);
                    showNotification('Error submitting vote', 'error');
                }
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Notification function
            function showNotification(message, type = 'info') {
                const notifications = document.getElementById('notifications');
                const notification = document.createElement('div');
                notification.className = `notification notification-${type}`;
                notification.textContent = message;
                
                notifications.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 5000);
            }
        });
    </script>
</body>
</html> 