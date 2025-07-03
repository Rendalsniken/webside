/**
 * XRP Specter - Main JavaScript
 * Theme switching, AJAX functionality, and interactive features
 */

// Global variables
let currentTheme = 'dark';
let priceChart = null;
let priceUpdateInterval = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
    initializeEventListeners();
    initializePriceUpdates();
    initializeNotifications();
});

/**
 * Theme Management
 */
function initializeTheme() {
    // Get saved theme from localStorage
    const savedTheme = localStorage.getItem('xrp_theme') || 'dark';
    setTheme(savedTheme);
    
    // Theme toggle functionality
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
}

function setTheme(theme) {
    currentTheme = theme;
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('xrp_theme', theme);
    
    // Update theme toggle icon
    updateThemeIcon(theme);
}

function toggleTheme() {
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    setTheme(newTheme);
}

function updateThemeIcon(theme) {
    const sunPath = document.querySelector('.sun-path');
    const moonPath = document.querySelector('.moon-path');
    
    if (sunPath && moonPath) {
        if (theme === 'dark') {
            sunPath.style.opacity = '0';
            moonPath.style.opacity = '1';
        } else {
            sunPath.style.opacity = '1';
            moonPath.style.opacity = '0';
        }
    }
}

/**
 * Event Listeners
 */
function initializeEventListeners() {
    // Chart period buttons
    const chartPeriods = document.querySelectorAll('.chart-period');
    chartPeriods.forEach(button => {
        button.addEventListener('click', function() {
            const period = this.dataset.period;
            loadPriceHistory(period);
            
            // Update active state
            chartPeriods.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Calculator form
    const calculateBtn = document.getElementById('calculate-btn');
    if (calculateBtn) {
        calculateBtn.addEventListener('click', handleCalculator);
    }
    
    // Poll form
    const pollForm = document.getElementById('poll-form');
    if (pollForm) {
        pollForm.addEventListener('click', function(e) {
            if (e.target.type === 'radio') {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            }
        });
        
        pollForm.addEventListener('submit', handlePollVote);
    }
    
    // Trade simulator
    const newTradeBtn = document.getElementById('new-trade-btn');
    if (newTradeBtn) {
        newTradeBtn.addEventListener('click', showTradeModal);
    }
    
    // Modal close buttons
    const modalCloseBtns = document.querySelectorAll('.modal-close, .btn-secondary');
    modalCloseBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                hideModal(modal);
            }
        });
    });
    
    // Close modal on outside click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            hideModal(e.target);
        }
    });
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal[style*="display: block"]');
            if (openModal) {
                hideModal(openModal);
            }
        }
    });
    
    // Trade form submission
    const tradeForm = document.getElementById('trade-form');
    if (tradeForm) {
        tradeForm.addEventListener('submit', handleTradeSubmission);
    }
}

/**
 * Price Updates
 */
function initializePriceUpdates() {
    // Update price every 30 seconds
    priceUpdateInterval = setInterval(updateLivePrice, 30000);
    
    // Initial update
    updateLivePrice();
}

function updateLivePrice() {
    fetch('api/price.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updatePriceDisplay(data.price, data.change_24h);
            }
        })
        .catch(error => {
            console.error('Failed to update price:', error);
        });
}

function updatePriceDisplay(price, change) {
    const priceElement = document.getElementById('live-price');
    const changeElement = document.getElementById('live-change');
    
    if (priceElement) {
        priceElement.textContent = `$${parseFloat(price).toFixed(4)}`;
    }
    
    if (changeElement) {
        const isPositive = change >= 0;
        changeElement.textContent = `${isPositive ? '+' : ''}${parseFloat(change).toFixed(2)}%`;
        changeElement.className = `ticker-change ${isPositive ? 'positive' : 'negative'}`;
    }
}

/**
 * Price Chart
 */
function initPriceChart() {
    const ctx = document.getElementById('price-chart');
    if (!ctx) return;
    
    const chartCtx = ctx.getContext('2d');
    
    priceChart = new Chart(chartCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'XRP Price (USD)',
                data: [],
                borderColor: getComputedStyle(document.documentElement).getPropertyValue('--primary-color'),
                backgroundColor: 'rgba(0, 212, 255, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--bg-card'),
                    titleColor: getComputedStyle(document.documentElement).getPropertyValue('--text-primary'),
                    bodyColor: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary'),
                    borderColor: getComputedStyle(document.documentElement).getPropertyValue('--border-color'),
                    borderWidth: 1,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return `$${context.parsed.y.toFixed(4)}`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    grid: {
                        color: getComputedStyle(document.documentElement).getPropertyValue('--border-light')
                    },
                    ticks: {
                        color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary'),
                        callback: function(value) {
                            return '$' + value.toFixed(4);
                        }
                    }
                },
                x: {
                    grid: {
                        color: getComputedStyle(document.documentElement).getPropertyValue('--border-light')
                    },
                    ticks: {
                        color: getComputedStyle(document.documentElement).getPropertyValue('--text-secondary'),
                        maxTicksLimit: 8
                    }
                }
            }
        }
    });
    
    // Load initial data
    loadPriceHistory('7d');
}

function loadPriceHistory(period) {
    fetch(`api/price-history.php?period=${period}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && priceChart) {
                updatePriceChart(data.data);
            }
        })
        .catch(error => {
            console.error('Failed to load price history:', error);
        });
}

function updatePriceChart(data) {
    if (!priceChart || !data) return;
    
    const labels = data.map(item => {
        const date = new Date(item.timestamp * 1000);
        return date.toLocaleDateString();
    });
    
    const prices = data.map(item => item.price);
    
    priceChart.data.labels = labels;
    priceChart.data.datasets[0].data = prices;
    priceChart.update('none');
}

/**
 * Calculator
 */
function handleCalculator() {
    const amount = document.getElementById('calc-amount').value;
    const operation = document.getElementById('calc-operation').value;
    const resultDiv = document.getElementById('calculator-result');
    
    if (!amount || isNaN(amount) || amount <= 0) {
        showResult('Please enter a valid amount', 'error', resultDiv);
        return;
    }
    
    // Show loading
    resultDiv.innerHTML = '<div class="loading">Calculating...</div>';
    
    fetch('api/calculate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            amount: parseFloat(amount),
            operation: operation
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showResult(data.result, 'success', resultDiv);
        } else {
            showResult(data.message, 'error', resultDiv);
        }
    })
    .catch(error => {
        showResult('Calculation failed', 'error', resultDiv);
    });
}

function showResult(message, type, container) {
    container.innerHTML = `<div class="${type}">${message}</div>`;
}

/**
 * Poll System
 */
function handlePollVote(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const pollId = formData.get('poll_id');
    const option = formData.get('poll_option');
    
    if (!option) {
        showNotification('Please select an option', 'warning');
        return;
    }
    
    fetch('api/vote.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Vote submitted successfully!', 'success');
            loadPollResults();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Vote failed', 'error');
    });
}

function loadPollResults() {
    const resultsDiv = document.getElementById('poll-results');
    if (!resultsDiv) return;
    
    fetch('api/poll-results.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultsDiv.innerHTML = data.html;
            }
        })
        .catch(error => {
            console.error('Failed to load poll results:', error);
        });
}

/**
 * Trade Simulator
 */
function showTradeModal() {
    const modal = document.getElementById('trade-modal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

function hideModal(modal) {
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

function handleTradeSubmission(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const amount = formData.get('amount');
    const action = formData.get('action');
    
    if (!amount || isNaN(amount) || amount <= 0) {
        showNotification('Please enter a valid amount', 'error');
        return;
    }
    
    fetch('api/trade.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Trade created successfully!', 'success');
            hideModal(document.getElementById('trade-modal'));
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Trade creation failed', 'error');
    });
}

/**
 * Notifications
 */
function initializeNotifications() {
    // Check for new notifications every 30 seconds
    setInterval(checkNotifications, 30000);
}

function checkNotifications() {
    fetch('api/notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.notifications.length > 0) {
                data.notifications.forEach(notification => {
                    showNotification(notification.message, notification.type);
                });
            }
        })
        .catch(error => {
            console.error('Failed to check notifications:', error);
        });
}

function showNotification(message, type = 'info') {
    const notifications = document.getElementById('notifications');
    if (!notifications) return;
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    notifications.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
    
    // Remove on click
    notification.addEventListener('click', function() {
        this.remove();
    });
}

/**
 * Utility Functions
 */
function formatNumber(number, decimals = 2) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
}

function formatCurrency(amount, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

function formatPercentage(value) {
    return `${value >= 0 ? '+' : ''}${value.toFixed(2)}%`;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * AJAX Helper Functions
 */
function makeRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin'
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    return fetch(url, finalOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
}

function postData(url, data) {
    return makeRequest(url, {
        method: 'POST',
        body: JSON.stringify(data)
    });
}

function getData(url) {
    return makeRequest(url);
}

/**
 * Error Handling
 */
function handleError(error, context = '') {
    console.error(`Error in ${context}:`, error);
    showNotification(`An error occurred: ${error.message}`, 'error');
}

// Global error handler
window.addEventListener('error', function(e) {
    handleError(e.error, 'Global');
});

// Unhandled promise rejection handler
window.addEventListener('unhandledrejection', function(e) {
    handleError(e.reason, 'Promise');
});

/**
 * Performance Monitoring
 */
function measurePerformance(name, fn) {
    const start = performance.now();
    const result = fn();
    const end = performance.now();
    
    console.log(`${name} took ${(end - start).toFixed(2)}ms`);
    return result;
}

/**
 * Local Storage Management
 */
const Storage = {
    set: function(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            console.error('Failed to save to localStorage:', error);
        }
    },
    
    get: function(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (error) {
            console.error('Failed to read from localStorage:', error);
            return defaultValue;
        }
    },
    
    remove: function(key) {
        try {
            localStorage.removeItem(key);
        } catch (error) {
            console.error('Failed to remove from localStorage:', error);
        }
    }
};

/**
 * Export functions for global use
 */
window.XRPSpecter = {
    showNotification,
    setTheme,
    toggleTheme,
    formatNumber,
    formatCurrency,
    formatPercentage,
    Storage
};

// Cryptocurrency Converter Logic (dashboard only)
(function() {
    const form = document.getElementById('crypto-converter-form');
    if (!form) return;
    const fromAmount = document.getElementById('from-amount');
    const fromCurrency = document.getElementById('from-currency');
    const toCurrency = document.getElementById('to-currency');
    const resultDiv = document.getElementById('converter-result');
    const swapBtn = document.getElementById('swap-btn');

    // Supported CoinGecko IDs
    const ids = {
        xrp: 'ripple',
        btc: 'bitcoin',
        eth: 'ethereum',
        usd: 'usd',
        eur: 'eur'
    };

    // Swap currencies
    swapBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const tmp = fromCurrency.value;
        fromCurrency.value = toCurrency.value;
        toCurrency.value = tmp;
    });

    // Fetch conversion rate and calculate
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const amount = parseFloat(fromAmount.value);
        const from = fromCurrency.value;
        const to = toCurrency.value;
        if (from === to) {
            resultDiv.textContent = `${amount} ${from.toUpperCase()} = ${amount} ${to.toUpperCase()}`;
            resultDiv.classList.remove('error');
            return;
        }
        resultDiv.textContent = 'Converting...';
        resultDiv.classList.remove('error');
        // Build CoinGecko API URL
        let url = '';
        if ((from === 'usd' || from === 'eur') && (to !== 'usd' && to !== 'eur')) {
            // Fiat to crypto
            url = `https://api.coingecko.com/api/v3/simple/price?ids=${ids[to]}&vs_currencies=${from}`;
        } else if ((to === 'usd' || to === 'eur') && (from !== 'usd' && from !== 'eur')) {
            // Crypto to fiat
            url = `https://api.coingecko.com/api/v3/simple/price?ids=${ids[from]}&vs_currencies=${to}`;
        } else if ((from !== 'usd' && from !== 'eur') && (to !== 'usd' && to !== 'eur')) {
            // Crypto to crypto
            url = `https://api.coingecko.com/api/v3/simple/price?ids=${ids[from]}&vs_currencies=${to}`;
        } else if ((from === 'usd' || from === 'eur') && (to === 'usd' || to === 'eur')) {
            // Fiat to fiat
            url = `https://api.exchangerate.host/convert?from=${from.toUpperCase()}&to=${to.toUpperCase()}`;
        }
        fetch(url)
            .then(res => res.json())
            .then(data => {
                let rate = null;
                if ((from === 'usd' || from === 'eur') && (to !== 'usd' && to !== 'eur')) {
                    // Fiat to crypto
                    rate = 1 / data[ids[to]][from];
                } else if ((to === 'usd' || to === 'eur') && (from !== 'usd' && from !== 'eur')) {
                    // Crypto to fiat
                    rate = data[ids[from]][to];
                } else if ((from !== 'usd' && from !== 'eur') && (to !== 'usd' && to !== 'eur')) {
                    // Crypto to crypto
                    rate = data[ids[from]][to];
                } else if ((from === 'usd' || from === 'eur') && (to === 'usd' || to === 'eur')) {
                    // Fiat to fiat
                    rate = data.result;
                }
                if (!rate || isNaN(rate)) throw new Error('Invalid rate');
                const converted = amount * rate;
                resultDiv.textContent = `${amount} ${from.toUpperCase()} = ${converted.toFixed(6)} ${to.toUpperCase()}`;
                resultDiv.classList.remove('error');
            })
            .catch(() => {
                resultDiv.textContent = 'Conversion failed. Please try again.';
                resultDiv.classList.add('error');
            });
    });
})(); 