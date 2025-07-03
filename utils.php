<?php
/**
 * XRP Specter - Utilities and Helper Functions
 * API integrations, helper functions, and common utilities
 */

// Prevent direct access
if (!defined('XRP_SPECTER')) {
    define('XRP_SPECTER', true);
}

require_once 'config.php';
require_once 'db.php';

/**
 * XRP Price Integration with CoinGecko API
 */
class XRPPriceAPI {
    private $cache_key = 'xrp_price_data';
    
    public function getCurrentPrice($currency = 'usd') {
        // Check cache first
        $cached = get_cache($this->cache_key);
        if ($cached && isset($cached[$currency])) {
            return $cached[$currency];
        }
        
        try {
            $url = COINGECKO_API_URL . "/simple/price?ids=" . COINGECKO_XRP_ID . "&vs_currencies=" . $currency . "&include_24hr_change=true&include_market_cap=true";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'XRP-Specter/1.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200 && $response) {
                $data = json_decode($response, true);
                
                if ($data && isset($data[COINGECKO_XRP_ID])) {
                    $price_data = [
                        'price' => $data[COINGECKO_XRP_ID][$currency] ?? 0,
                        'change_24h' => $data[COINGECKO_XRP_ID][$currency . '_24h_change'] ?? 0,
                        'market_cap' => $data[COINGECKO_XRP_ID][$currency . '_market_cap'] ?? 0,
                        'last_updated' => time()
                    ];
                    
                    // Cache the data
                    $cached_data = get_cache($this->cache_key) ?: [];
                    $cached_data[$currency] = $price_data;
                    set_cache($this->cache_key, $cached_data, API_CACHE_DURATION);
                    
                    return $price_data;
                }
            }
        } catch (Exception $e) {
            error_log("XRP price API error: " . $e->getMessage());
        }
        
        // Return fallback data
        return [
            'price' => 0.50,
            'change_24h' => 0,
            'market_cap' => 0,
            'last_updated' => time()
        ];
    }
    
    public function getPriceHistory($days = 7, $currency = 'usd') {
        $cache_key = "xrp_price_history_{$days}_{$currency}";
        
        // Check cache first
        $cached = get_cache($cache_key);
        if ($cached) {
            return $cached;
        }
        
        try {
            $url = COINGECKO_API_URL . "/coins/" . COINGECKO_XRP_ID . "/market_chart?vs_currency={$currency}&days={$days}";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_USERAGENT, 'XRP-Specter/1.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200 && $response) {
                $data = json_decode($response, true);
                
                if ($data && isset($data['prices'])) {
                    $history = [];
                    foreach ($data['prices'] as $point) {
                        $history[] = [
                            'timestamp' => $point[0] / 1000,
                            'price' => $point[1]
                        ];
                    }
                    
                    // Cache the data
                    set_cache($cache_key, $history, API_CACHE_DURATION);
                    
                    return $history;
                }
            }
        } catch (Exception $e) {
            error_log("XRP price history API error: " . $e->getMessage());
        }
        
        // Return fallback data
        return $this->generateFallbackPriceHistory($days);
    }
    
    private function generateFallbackPriceHistory($days) {
        $history = [];
        $base_price = 0.50;
        $current_time = time();
        
        for ($i = $days; $i >= 0; $i--) {
            $timestamp = $current_time - ($i * 24 * 60 * 60);
            $variation = (rand(-10, 10) / 100); // ±10% variation
            $price = $base_price * (1 + $variation);
            
            $history[] = [
                'timestamp' => $timestamp,
                'price' => round($price, 6)
            ];
        }
        
        return $history;
    }
}

/**
 * News System with RSS Feed Parsing and AI Analysis
 */
class NewsFeed {
    private $cache_key = 'news_feed_data';
    private $rss_urls = [
        'cryptoslate' => 'https://cryptoslate.com/feed/',
        'cointelegraph' => 'https://cointelegraph.com/rss',
        'coindesk' => 'https://www.coindesk.com/arc/outboundfeeds/rss/',
        'bitcoinist' => 'https://bitcoinist.com/feed/'
    ];
    
    public function getLatestNews($limit = 10, $category = null) {
        $cache_key = "news_latest_{$limit}_" . ($category ?? 'all');
        
        // Check cache first
        $cached = get_cache($cache_key);
        if ($cached) {
            return $cached;
        }
        
        $all_news = [];
        
        foreach ($this->rss_urls as $source => $url) {
            try {
                $news = $this->parseRSSFeed($url, $source);
                $all_news = array_merge($all_news, $news);
            } catch (Exception $e) {
                error_log("News feed error for {$source}: " . $e->getMessage());
            }
        }
        
        // Filter by category if specified
        if ($category) {
            $all_news = array_filter($all_news, function($item) use ($category) {
                return stripos($item['title'], $category) !== false || 
                       stripos($item['description'], $category) !== false;
            });
        }
        
        // Sort by date and limit
        usort($all_news, function($a, $b) {
            return strtotime($b['pubDate']) - strtotime($a['pubDate']);
        });
        
        $news = array_slice($all_news, 0, $limit);
        
        // Cache the results
        set_cache($cache_key, $news, 1800); // 30 minutes
        
        return $news;
    }
    
    private function parseRSSFeed($url, $source) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'XRP-Specter/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200 || !$response) {
            return [];
        }
        
        $xml = simplexml_load_string($response);
        if (!$xml) {
            return [];
        }
        
        $news = [];
        foreach ($xml->channel->item as $item) {
            $news[] = [
                'title' => (string)$item->title,
                'link' => (string)$item->link,
                'description' => (string)$item->description,
                'pubDate' => (string)$item->pubDate,
                'source' => $source,
                'category' => $this->categorizeNews((string)$item->title, (string)$item->description)
            ];
        }
        
        return $news;
    }
    
    private function categorizeNews($title, $description) {
        $text = strtolower($title . ' ' . $description);
        
        $categories = [
            'XRP' => ['xrp', 'ripple', 'xrp ledger'],
            'Legal' => ['sec', 'legal', 'court', 'lawsuit', 'regulation'],
            'Technology' => ['upgrade', 'technology', 'development', 'code'],
            'Partnership' => ['partnership', 'collaboration', 'integration'],
            'Adoption' => ['adoption', 'bank', 'financial', 'institution'],
            'Market' => ['price', 'market', 'trading', 'volume']
        ];
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    return $category;
                }
            }
        }
        
        return 'General';
    }
    
    public function searchNews($query, $limit = 10) {
        $all_news = $this->getLatestNews(50);
        $results = [];
        
        $query = strtolower($query);
        foreach ($all_news as $item) {
            $text = strtolower($item['title'] . ' ' . $item['description']);
            if (strpos($text, $query) !== false) {
                $results[] = $item;
                if (count($results) >= $limit) break;
            }
        }
        
        return $results;
    }
}

/**
 * AI News Analysis and Summary
 */
class AINewsSummary {
    private $cache_key = 'ai_news_analysis';
    
    public function analyzeNews($news_items) {
        $cache_key = $this->cache_key . '_' . md5(serialize($news_items));
        
        // Check cache first
        $cached = get_cache($cache_key);
        if ($cached) {
            return $cached;
        }
        
        $analysis = [
            'sentiment' => $this->analyzeSentiment($news_items),
            'trends' => $this->identifyTrends($news_items),
            'keywords' => $this->extractKeywords($news_items),
            'summary' => $this->generateSummary($news_items),
            'impact_score' => $this->calculateImpactScore($news_items),
            'confidence' => $this->calculateConfidence($news_items)
        ];
        
        // Cache the analysis
        set_cache($cache_key, $analysis, 3600); // 1 hour
        
        return $analysis;
    }
    
    private function analyzeSentiment($news_items) {
        $positive_words = ['surge', 'rise', 'gain', 'bullish', 'positive', 'growth', 'adoption', 'partnership'];
        $negative_words = ['drop', 'fall', 'decline', 'bearish', 'negative', 'loss', 'legal', 'sec'];
        
        $sentiment_score = 0;
        $total_items = count($news_items);
        
        foreach ($news_items as $item) {
            $text = strtolower($item['title'] . ' ' . $item['description']);
            
            foreach ($positive_words as $word) {
                if (strpos($text, $word) !== false) {
                    $sentiment_score += 1;
                }
            }
            
            foreach ($negative_words as $word) {
                if (strpos($text, $word) !== false) {
                    $sentiment_score -= 1;
                }
            }
        }
        
        $average_sentiment = $total_items > 0 ? $sentiment_score / $total_items : 0;
        
        if ($average_sentiment > 0.5) return 'positive';
        if ($average_sentiment < -0.5) return 'negative';
        return 'neutral';
    }
    
    private function identifyTrends($news_items) {
        $trends = [];
        $categories = [];
        
        foreach ($news_items as $item) {
            $category = $item['category'] ?? 'General';
            $categories[$category] = ($categories[$category] ?? 0) + 1;
        }
        
        arsort($categories);
        $trends = array_slice(array_keys($categories), 0, 3);
        
        return $trends;
    }
    
    private function extractKeywords($news_items) {
        $keywords = [];
        $stop_words = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        
        foreach ($news_items as $item) {
            $text = strtolower($item['title'] . ' ' . $item['description']);
            $words = preg_split('/\s+/', $text);
            
            foreach ($words as $word) {
                $word = preg_replace('/[^a-z]/', '', $word);
                if (strlen($word) > 3 && !in_array($word, $stop_words)) {
                    $keywords[$word] = ($keywords[$word] ?? 0) + 1;
                }
            }
        }
        
        arsort($keywords);
        return array_slice(array_keys($keywords), 0, 10);
    }
    
    private function generateSummary($news_items) {
        if (empty($news_items)) {
            return 'No recent news available.';
        }
        
        $summary_parts = [];
        $categories = [];
        
        foreach ($news_items as $item) {
            $category = $item['category'] ?? 'General';
            $categories[$category] = ($categories[$category] ?? 0) + 1;
        }
        
        arsort($categories);
        $top_category = array_keys($categories)[0];
        
        $summary_parts[] = "Recent XRP news shows a focus on {$top_category} with " . count($news_items) . " articles.";
        
        $sentiment = $this->analyzeSentiment($news_items);
        $summary_parts[] = "Overall sentiment appears to be {$sentiment}.";
        
        return implode(' ', $summary_parts);
    }
    
    private function calculateImpactScore($news_items) {
        $impact_score = 0;
        
        foreach ($news_items as $item) {
            $score = 1;
            
            // Higher score for XRP-specific news
            if (stripos($item['title'], 'xrp') !== false || stripos($item['title'], 'ripple') !== false) {
                $score += 2;
            }
            
            // Higher score for recent news
            $age_hours = (time() - strtotime($item['pubDate'])) / 3600;
            if ($age_hours < 24) $score += 1;
            if ($age_hours < 6) $score += 1;
            
            $impact_score += $score;
        }
        
        return min(100, $impact_score);
    }
    
    private function calculateConfidence($news_items) {
        $confidence = 70; // Base confidence
        
        // Higher confidence with more news items
        if (count($news_items) > 10) $confidence += 10;
        if (count($news_items) > 20) $confidence += 10;
        
        // Higher confidence with recent news
        $recent_count = 0;
        foreach ($news_items as $item) {
            $age_hours = (time() - strtotime($item['pubDate'])) / 3600;
            if ($age_hours < 24) $recent_count++;
        }
        
        if ($recent_count > 5) $confidence += 10;
        
        return min(100, $confidence);
    }
}

/**
 * XRP Calculator
 */
class XRPCalculator {
    private $price_api;
    
    public function __construct() {
        $this->price_api = new XRPPriceAPI();
    }
    
    public function calculate($amount, $currency = 'usd', $operation = 'usd_to_xrp') {
        $price_data = $this->price_api->getCurrentPrice($currency);
        $price = $price_data['price'];
        
        switch ($operation) {
            case 'usd_to_xrp':
                return $amount / $price;
            case 'xrp_to_usd':
                return $amount * $price;
            case 'calculate_profit':
                return $this->calculateProfit($amount, $price);
            default:
                return 0;
        }
    }
    
    public function calculateProfit($investment, $current_price, $buy_price = null) {
        if (!$buy_price) {
            $buy_price = $current_price * 0.9; // Assume 10% lower buy price
        }
        
        $xrp_amount = $investment / $buy_price;
        $current_value = $xrp_amount * $current_price;
        $profit = $current_value - $investment;
        $profit_percentage = ($profit / $investment) * 100;
        
        return [
            'investment' => $investment,
            'buy_price' => $buy_price,
            'current_price' => $current_price,
            'xrp_amount' => $xrp_amount,
            'current_value' => $current_value,
            'profit' => $profit,
            'profit_percentage' => $profit_percentage
        ];
    }
    
    public function formatCurrency($amount, $currency = 'usd') {
        $symbols = [
            'usd' => '$',
            'eur' => '€',
            'gbp' => '£',
            'jpy' => '¥'
        ];
        
        $symbol = $symbols[$currency] ?? '$';
        return $symbol . number_format($amount, 2);
    }
    
    public function formatXRP($amount) {
        return number_format($amount, 6) . ' XRP';
    }
}

/**
 * Trading Simulator
 */
class TradingSimulator {
    private $price_api;
    
    public function __construct() {
        $this->price_api = new XRPPriceAPI();
    }
    
    public function createTrade($user_id, $amount, $action = 'buy') {
        $current_price = $this->price_api->getCurrentPrice()['price'];
        
        $trade_data = [
            'user_id' => $user_id,
            'start_amount' => $amount,
            'buy_price' => $current_price,
            'xrp_amount' => $action === 'buy' ? $amount / $current_price : 0,
            'status' => 'active'
        ];
        
        $trade_id = db()->insert('simulator_logs', $trade_data);
        
        // Add XP for trading
        add_xp($user_id, 5, 'trade_simulation', 'Trading simulation created');
        
        return $trade_id;
    }
    
    public function closeTrade($trade_id, $user_id) {
        $trade = db()->fetch("SELECT * FROM simulator_logs WHERE id = ? AND user_id = ?", [$trade_id, $user_id]);
        
        if (!$trade || $trade['status'] !== 'active') {
            return false;
        }
        
        $current_price = $this->price_api->getCurrentPrice()['price'];
        $current_value = $trade['xrp_amount'] * $current_price;
        $profit_loss = $current_value - $trade['start_amount'];
        
        $update_data = [
            'sell_price' => $current_price,
            'profit_loss' => $profit_loss,
            'status' => 'completed'
        ];
        
        db()->update('simulator_logs', $update_data, 'id = ?', [$trade_id]);
        
        // Add XP based on performance
        $xp_bonus = $profit_loss > 0 ? 10 : 5;
        add_xp($user_id, $xp_bonus, 'trade_completed', 'Trading simulation completed');
        
        return true;
    }
    
    public function getUserTrades($user_id, $status = null) {
        $where = 'user_id = ?';
        $params = [$user_id];
        
        if ($status) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        
        $sql = "SELECT * FROM simulator_logs WHERE {$where} ORDER BY created_at DESC";
        return db()->fetchAll($sql, $params);
    }
    
    public function getTradeStats($user_id) {
        $sql = "SELECT 
                    COUNT(*) as total_trades,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_trades,
                    SUM(CASE WHEN profit_loss > 0 THEN 1 ELSE 0 END) as profitable_trades,
                    SUM(profit_loss) as total_profit_loss,
                    AVG(profit_loss) as avg_profit_loss
                FROM simulator_logs 
                WHERE user_id = ? AND status = 'completed'";
        
        return db()->fetch($sql, [$user_id]);
    }
}

/**
 * General Utility Functions
 */
function format_number($number, $decimals = 2) {
    return number_format($number, $decimals);
}

function format_percentage($number, $decimals = 2) {
    return number_format($number, $decimals) . '%';
}

function format_date($timestamp, $format = 'M j, Y') {
    return date($format, $timestamp);
}

function format_time_ago($timestamp) {
    $time_diff = time() - $timestamp;
    
    if ($time_diff < 60) {
        return 'Just now';
    } elseif ($time_diff < 3600) {
        $minutes = floor($time_diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time_diff < 86400) {
        $hours = floor($time_diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($time_diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}

function sanitize_input($input) {
    if (is_array($input)) {
        return array_map('sanitize_input', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generate_random_string($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function get_client_ip() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// is_ajax_request() function is already defined in config.php

function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function redirect($url) {
    header("Location: {$url}");
    exit;
}

/**
 * XP & Achievement System
 */
function get_user_xp($user_id) {
    try {
        $db = db();
        $user = $db->fetch("SELECT xp, level FROM users WHERE id = ?", [$user_id]);
        return $user ? $user['xp'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

function get_user_level($user_id) {
    try {
        $db = db();
        $user = $db->fetch("SELECT level FROM users WHERE id = ?", [$user_id]);
        return $user ? $user['level'] : 1;
    } catch (Exception $e) {
        return 1;
    }
}

function calculate_level($xp) {
    // Level calculation: each level requires more XP
    $level = 1;
    $xp_required = 100;
    
    while ($xp >= $xp_required) {
        $xp -= $xp_required;
        $level++;
        $xp_required = $level * 100; // Increasing XP requirement
    }
    
    return $level;
}

function check_achievements($user_id) {
    $achievements = [
        'first_login' => [
            'name' => 'First Steps',
            'description' => 'Complete your first login',
            'condition' => 'login_count >= 1',
            'xp_reward' => 10
        ],
        'daily_streak_7' => [
            'name' => 'Week Warrior',
            'description' => 'Login for 7 consecutive days',
            'condition' => 'daily_streak >= 7',
            'xp_reward' => 50
        ],
        'daily_streak_30' => [
            'name' => 'Monthly Master',
            'description' => 'Login for 30 consecutive days',
            'condition' => 'daily_streak >= 30',
            'xp_reward' => 200
        ],
        'xp_1000' => [
            'name' => 'XP Collector',
            'description' => 'Reach 1,000 XP',
            'condition' => 'xp >= 1000',
            'xp_reward' => 100
        ],
        'xp_5000' => [
            'name' => 'XP Master',
            'description' => 'Reach 5,000 XP',
            'condition' => 'xp >= 5000',
            'xp_reward' => 500
        ],
        'level_10' => [
            'name' => 'Level 10',
            'description' => 'Reach level 10',
            'condition' => 'level >= 10',
            'xp_reward' => 200
        ],
        'level_25' => [
            'name' => 'Level 25',
            'description' => 'Reach level 25',
            'condition' => 'level >= 25',
            'xp_reward' => 500
        ],
        'first_trade' => [
            'name' => 'First Trade',
            'description' => 'Complete your first trading simulation',
            'condition' => 'trades_count >= 1',
            'xp_reward' => 25
        ],
        'profitable_trader' => [
            'name' => 'Profitable Trader',
            'description' => 'Make a profitable trade',
            'condition' => 'profitable_trades >= 1',
            'xp_reward' => 50
        ],
        'news_reader' => [
            'name' => 'News Reader',
            'description' => 'Read 10 news articles',
            'condition' => 'news_read >= 10',
            'xp_reward' => 30
        ]
    ];
    
    try {
        $db = db();
        $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$user_id]);
        
        if (!$user) return;
        
        // Get user stats
        $stats = get_user_stats($user_id);
        
        foreach ($achievements as $achievement_id => $achievement) {
            // Check if already earned
            $existing = $db->fetch(
                "SELECT * FROM user_achievements WHERE user_id = ? AND achievement_id = ?",
                [$user_id, $achievement_id]
            );
            
            if ($existing) continue;
            
            // Check condition
            if (check_achievement_condition($achievement['condition'], $user, $stats)) {
                // Award achievement
                $db->insert('user_achievements', [
                    'user_id' => $user_id,
                    'achievement_id' => $achievement_id,
                    'earned_at' => date('Y-m-d H:i:s')
                ]);
                
                // Award XP
                add_xp($user_id, $achievement['xp_reward'], 'achievement', $achievement['name']);
                
                // Create notification
                create_notification($user_id, 'achievement', $achievement['name'], $achievement['description']);
            }
        }
    } catch (Exception $e) {
        error_log("Achievement check error: " . $e->getMessage());
    }
}

function check_achievement_condition($condition, $user, $stats) {
    // Simple condition parser
    $condition = str_replace(['login_count', 'daily_streak', 'xp', 'level', 'trades_count', 'profitable_trades', 'news_read'], 
                           [$stats['login_count'], $stats['daily_streak'], $user['xp'], $user['level'], $stats['trades_count'], $stats['profitable_trades'], $stats['news_read']], 
                           $condition);
    
    return eval("return $condition;");
}

function get_user_stats($user_id) {
    try {
        $db = db();
        
        $stats = [
            'login_count' => 0,
            'daily_streak' => 0,
            'trades_count' => 0,
            'profitable_trades' => 0,
            'news_read' => 0
        ];
        
        // Get login count
        $login_count = $db->fetch("SELECT COUNT(*) as count FROM xp_logs WHERE user_id = ? AND action = 'login'", [$user_id]);
        $stats['login_count'] = $login_count['count'] ?? 0;
        
        // Get trades count
        $trades_count = $db->fetch("SELECT COUNT(*) as count FROM simulator_logs WHERE user_id = ?", [$user_id]);
        $stats['trades_count'] = $trades_count['count'] ?? 0;
        
        // Get profitable trades
        $profitable_trades = $db->fetch("SELECT COUNT(*) as count FROM simulator_logs WHERE user_id = ? AND profit_loss > 0", [$user_id]);
        $stats['profitable_trades'] = $profitable_trades['count'] ?? 0;
        
        return $stats;
    } catch (Exception $e) {
        return ['login_count' => 0, 'daily_streak' => 0, 'trades_count' => 0, 'profitable_trades' => 0, 'news_read' => 0];
    }
}

function get_user_achievements($user_id) {
    try {
        $db = db();
        return $db->fetchAll(
            "SELECT ua.*, a.name, a.description, a.xp_reward 
             FROM user_achievements ua 
             JOIN achievements a ON ua.achievement_id = a.id 
             WHERE ua.user_id = ? 
             ORDER BY ua.earned_at DESC",
            [$user_id]
        );
    } catch (Exception $e) {
        return [];
    }
}

function mark_notification_read($notification_id, $user_id) {
    try {
        $db = db();
        return $db->update('notifications', 
            ['is_read' => 1], 
            'id = ? AND user_id = ?', 
            [$notification_id, $user_id]
        );
    } catch (Exception $e) {
        return false;
    }
}

function get_unread_notification_count($user_id) {
    try {
        $db = db();
        $result = $db->fetch(
            "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
            [$user_id]
        );
        return $result['count'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Rate Limiting System
 */
function check_rate_limit($ip_address, $endpoint, $max_requests = null, $window = null) {
    if ($max_requests === null) $max_requests = RATE_LIMIT_REQUESTS;
    if ($window === null) $window = RATE_LIMIT_WINDOW;
    
    try {
        $db = db();
        
        // Clean old entries
        $db->execute("DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)", [$window]);
        
        // Get current window
        $current_window = date('Y-m-d H:i:s', floor(time() / $window) * $window);
        
        // Check existing rate limit
        $existing = $db->fetch(
            "SELECT requests_count FROM rate_limits WHERE ip_address = ? AND endpoint = ? AND window_start = ?",
            [$ip_address, $endpoint, $current_window]
        );
        
        if ($existing) {
            if ($existing['requests_count'] >= $max_requests) {
                return false; // Rate limit exceeded
            }
            
            // Increment count
            $db->execute(
                "UPDATE rate_limits SET requests_count = requests_count + 1 WHERE ip_address = ? AND endpoint = ? AND window_start = ?",
                [$ip_address, $endpoint, $current_window]
            );
        } else {
            // Create new entry
            $db->insert('rate_limits', [
                'ip_address' => $ip_address,
                'endpoint' => $endpoint,
                'requests_count' => 1,
                'window_start' => $current_window
            ]);
        }
        
        return true; // Request allowed
    } catch (Exception $e) {
        error_log("Rate limiting error: " . $e->getMessage());
        return true; // Allow request if rate limiting fails
    }
}

/**
 * Login Attempt Limiting
 */
function check_login_attempts($ip_address) {
    try {
        $db = db();
        
        // Clean old attempts
        $db->execute("DELETE FROM rate_limits WHERE ip_address = ? AND endpoint = 'login' AND window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)", 
                    [$ip_address, LOGIN_ATTEMPTS_WINDOW]);
        
        // Check current attempts
        $attempts = $db->fetch(
            "SELECT SUM(requests_count) as total FROM rate_limits WHERE ip_address = ? AND endpoint = 'login'",
            [$ip_address]
        );
        
        return ($attempts['total'] ?? 0) < LOGIN_ATTEMPTS_LIMIT;
    } catch (Exception $e) {
        error_log("Login attempts check error: " . $e->getMessage());
        return true; // Allow login if check fails
    }
}

function record_login_attempt($ip_address, $success = false) {
    try {
        $db = db();
        $current_window = date('Y-m-d H:i:s', floor(time() / 60) * 60); // 1-minute windows
        
        $db->insert('rate_limits', [
            'ip_address' => $ip_address,
            'endpoint' => 'login',
            'requests_count' => 1,
            'window_start' => $current_window
        ]);
    } catch (Exception $e) {
        error_log("Login attempt recording error: " . $e->getMessage());
    }
}

/**
 * XSS Prevention
 */
function xss_clean($data) {
    if (is_array($data)) {
        return array_map('xss_clean', $data);
    }
    
    // Remove potentially dangerous tags and attributes
    $data = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $data);
    $data = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi', '', $data);
    $data = preg_replace('/<object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object>/mi', '', $data);
    $data = preg_replace('/<embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed>/mi', '', $data);
    
    // Remove dangerous attributes
    $data = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $data);
    $data = preg_replace('/javascript:/i', '', $data);
    $data = preg_replace('/vbscript:/i', '', $data);
    
    return $data;
}

/**
 * Input Validation
 */
function validate_username($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function validate_password($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 && 
           preg_match('/[A-Z]/', $password) && 
           preg_match('/[a-z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

/**
 * Session Security
 */
function regenerate_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

function secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
        regenerate_session();
        $_SESSION['last_regeneration'] = time();
    }
    
    // Set session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Security Logging
 */
function log_security_event($event_type, $details, $user_id = null, $ip_address = null) {
    if ($ip_address === null) $ip_address = get_client_ip();
    
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event_type' => $event_type,
        'details' => $details,
        'user_id' => $user_id,
        'ip_address' => $ip_address,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ];
    
    error_log("SECURITY: " . json_encode($log_entry));
}

/**
 * Send email using PHPMailer
 */
function send_email($to, $subject, $body, $altBody = '', $toName = '') {
    require_once __DIR__ . '/vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        //Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to, $toName ?: $to);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email could not be sent. Mailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send secure HTTP security headers
 */
function send_security_headers() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer-when-downgrade');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'");
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Initialize utility classes
$price_api = new XRPPriceAPI();
$news_feed = new NewsFeed();
$ai_summary = new AINewsSummary();
$calculator = new XRPCalculator();
$simulator = new TradingSimulator();
?> 