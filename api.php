<?php
/**
 * XRP Specter - API Endpoints
 * Handle AJAX requests for dashboard functionality
 */

define('XRP_SPECTER', true);
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';
require_once 'utils.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request data
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'get_price':
            $currency = $_GET['currency'] ?? 'usd';
            $price_data = $price_api->getCurrentPrice($currency);
            $response = ['success' => true, 'data' => $price_data];
            break;
            
        case 'get_price_history':
            $days = (int)($_GET['days'] ?? 7);
            $currency = $_GET['currency'] ?? 'usd';
            $history = $price_api->getPriceHistory($days, $currency);
            $response = ['success' => true, 'data' => $history];
            break;
            
        case 'get_news':
            $limit = (int)($_GET['limit'] ?? 10);
            $category = $_GET['category'] ?? null;
            $news = $news_feed->getLatestNews($limit, $category);
            $response = ['success' => true, 'data' => $news];
            break;
            
        case 'search_news':
            $query = $_GET['query'] ?? '';
            $limit = (int)($_GET['limit'] ?? 10);
            if (empty($query)) {
                $response = ['success' => false, 'message' => 'Query parameter required'];
                break;
            }
            $news = $news_feed->searchNews($query, $limit);
            $response = ['success' => true, 'data' => $news];
            break;
            
        case 'analyze_news':
            $limit = (int)($_GET['limit'] ?? 20);
            $news = $news_feed->getLatestNews($limit);
            $analysis = $ai_summary->analyzeNews($news);
            $response = ['success' => true, 'data' => $analysis];
            break;
            
        case 'convert_currency':
            if (!is_logged_in()) {
                $response = ['success' => false, 'message' => 'Authentication required'];
                break;
            }
            
            $amount = (float)($_POST['amount'] ?? 0);
            $from_currency = $_POST['from_currency'] ?? 'xrp';
            $to_currency = $_POST['to_currency'] ?? 'usd';
            
            if ($amount <= 0) {
                $response = ['success' => false, 'message' => 'Invalid amount'];
                break;
            }
            
            // Get current prices
            $from_price = $price_api->getCurrentPrice($to_currency)['price'];
            $to_price = $price_api->getCurrentPrice($to_currency)['price'];
            
            // Simple conversion (in real implementation, you'd use proper conversion rates)
            $converted_amount = $amount * $from_price;
            
            $response = [
                'success' => true,
                'data' => [
                    'from_amount' => $amount,
                    'from_currency' => $from_currency,
                    'to_currency' => $to_currency,
                    'converted_amount' => $converted_amount,
                    'rate' => $from_price
                ]
            ];
            break;
            
        case 'create_trade':
            if (!is_logged_in()) {
                $response = ['success' => false, 'message' => 'Authentication required'];
                break;
            }
            
            $user = current_user();
            $amount = (float)($_POST['amount'] ?? 0);
            $action = $_POST['trade_action'] ?? 'buy';
            
            if ($amount <= 0) {
                $response = ['success' => false, 'message' => 'Invalid amount'];
                break;
            }
            
            $trade_id = $simulator->createTrade($user['id'], $amount, $action);
            
            if ($trade_id) {
                $response = ['success' => true, 'data' => ['trade_id' => $trade_id]];
            } else {
                $response = ['success' => false, 'message' => 'Failed to create trade'];
            }
            break;
            
        case 'close_trade':
            if (!is_logged_in()) {
                $response = ['success' => false, 'message' => 'Authentication required'];
                break;
            }
            
            $user = current_user();
            $trade_id = (int)($_POST['trade_id'] ?? 0);
            
            if ($trade_id <= 0) {
                $response = ['success' => false, 'message' => 'Invalid trade ID'];
                break;
            }
            
            $success = $simulator->closeTrade($trade_id, $user['id']);
            
            if ($success) {
                $response = ['success' => true, 'message' => 'Trade closed successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to close trade'];
            }
            break;
            
        case 'get_user_stats':
            if (!is_logged_in()) {
                $response = ['success' => false, 'message' => 'Authentication required'];
                break;
            }
            
            $user = current_user();
            $stats = get_user_stats($user['id']);
            $xp = get_user_xp($user['id']);
            $level = get_user_level($user['id']);
            $achievements = get_user_achievements($user['id']);
            
            $response = [
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'xp' => $xp,
                    'level' => $level,
                    'achievements' => $achievements
                ]
            ];
            break;
            
        case 'get_leaderboard':
            $limit = (int)($_GET['limit'] ?? 10);
            $leaderboard = get_leaderboard($limit);
            $response = ['success' => true, 'data' => $leaderboard];
            break;
            
        case 'get_notifications':
            if (!is_logged_in()) {
                $response = ['success' => false, 'message' => 'Authentication required'];
                break;
            }
            
            $user = current_user();
            $limit = (int)($_GET['limit'] ?? 10);
            $notifications = get_user_notifications($user['id'], $limit);
            $unread_count = get_unread_notification_count($user['id']);
            
            $response = [
                'success' => true,
                'data' => [
                    'notifications' => $notifications,
                    'unread_count' => $unread_count
                ]
            ];
            break;
            
        case 'mark_notification_read':
            if (!is_logged_in()) {
                $response = ['success' => false, 'message' => 'Authentication required'];
                break;
            }
            
            $user = current_user();
            $notification_id = (int)($_POST['notification_id'] ?? 0);
            
            if ($notification_id <= 0) {
                $response = ['success' => false, 'message' => 'Invalid notification ID'];
                break;
            }
            
            $success = mark_notification_read($notification_id, $user['id']);
            
            if ($success) {
                $response = ['success' => true, 'message' => 'Notification marked as read'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to mark notification as read'];
            }
            break;
            
        case 'calculate_xrp':
            $amount = (float)($_POST['amount'] ?? 0);
            $operation = $_POST['operation'] ?? 'usd_to_xrp';
            $currency = $_POST['currency'] ?? 'usd';
            
            if ($amount <= 0) {
                $response = ['success' => false, 'message' => 'Invalid amount'];
                break;
            }
            
            $result = $calculator->calculate($amount, $currency, $operation);
            
            $response = [
                'success' => true,
                'data' => [
                    'amount' => $amount,
                    'operation' => $operation,
                    'result' => $result,
                    'formatted_result' => $operation === 'usd_to_xrp' ? 
                        $calculator->formatXRP($result) : 
                        $calculator->formatCurrency($result, $currency)
                ]
            ];
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Unknown action: ' . $action];
            break;
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    $response = ['success' => false, 'message' => 'Internal server error'];
}

// Return JSON response
echo json_encode($response);
exit;
?> 