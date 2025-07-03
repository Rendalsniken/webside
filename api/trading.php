<?php
/**
 * XRP Specter - Trading Simulator API
 * Handles trading actions for the simulator
 */

define('XRP_SPECTER', true);
require_once '../config.php';
require_once '../db.php';
require_once '../auth.php';
require_once '../utils.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$user = current_user();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action);
            break;
        case 'POST':
            handlePostRequest($action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log("Trading API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function handleGetRequest($action) {
    switch ($action) {
        case 'list':
            getTrades();
            break;
        case 'stats':
            getTradeStats();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function handlePostRequest($action) {
    switch ($action) {
        case 'create':
            createTrade();
            break;
        case 'close':
            closeTrade();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function getTrades() {
    $user_id = current_user()['id'];
    $trades = db()->fetchAll("SELECT * FROM simulator_logs WHERE user_id = ? ORDER BY created_at DESC", [$user_id]);
    echo json_encode(['success' => true, 'trades' => $trades]);
}

function getTradeStats() {
    $user_id = current_user()['id'];
    $total = db()->fetch("SELECT COUNT(*) as count FROM simulator_logs WHERE user_id = ?", [$user_id])['count'];
    $profitable = db()->fetch("SELECT COUNT(*) as count FROM simulator_logs WHERE user_id = ? AND profit_loss > 0", [$user_id])['count'];
    $completed = db()->fetch("SELECT COUNT(*) as count FROM simulator_logs WHERE user_id = ? AND status = 'completed'", [$user_id])['count'];
    $pnl = db()->fetch("SELECT SUM(profit_loss) as total FROM simulator_logs WHERE user_id = ?", [$user_id])['total'] ?? 0;
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_trades' => (int)$total,
            'profitable_trades' => (int)$profitable,
            'completed_trades' => (int)$completed,
            'total_profit_loss' => (float)$pnl
        ]
    ]);
}

function createTrade() {
    $input = json_decode(file_get_contents('php://input'), true);
    $amount = (float)($input['amount'] ?? 0);
    $action = $input['action'] ?? 'buy';
    $user_id = current_user()['id'];
    $price = get_current_xrp_price();
    if ($amount <= 0 || !in_array($action, ['buy', 'sell'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid trade parameters']);
        return;
    }
    $xrp_amount = $action === 'buy' ? $amount / $price : $amount;
    $trade_id = db()->insert('simulator_logs', [
        'user_id' => $user_id,
        'start_amount' => $amount,
        'buy_price' => $price,
        'xrp_amount' => $xrp_amount,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    if ($trade_id) {
        echo json_encode(['success' => true, 'trade_id' => $trade_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create trade']);
    }
}

function closeTrade() {
    $input = json_decode(file_get_contents('php://input'), true);
    $trade_id = (int)($input['trade_id'] ?? 0);
    $user_id = current_user()['id'];
    $trade = db()->fetch("SELECT * FROM simulator_logs WHERE id = ? AND user_id = ? AND status = 'active'", [$trade_id, $user_id]);
    if (!$trade) {
        http_response_code(404);
        echo json_encode(['error' => 'Trade not found or already closed']);
        return;
    }
    $sell_price = get_current_xrp_price();
    $profit_loss = ($sell_price - $trade['buy_price']) * $trade['xrp_amount'];
    db()->update('simulator_logs', [
        'sell_price' => $sell_price,
        'profit_loss' => $profit_loss,
        'status' => 'completed',
        'updated_at' => date('Y-m-d H:i:s')
    ], 'id = ?', [$trade_id]);
    // Award XP for completed trade
    add_xp($user_id, 10, 'trade', 'Completed trading simulation');
    create_notification($user_id, $profit_loss >= 0 ? 'success' : 'error', 'Trade Closed',
        'Your trade was closed with ' . ($profit_loss >= 0 ? 'profit' : 'loss') . ': ' . number_format($profit_loss, 2) . ' USD');
    echo json_encode(['success' => true, 'profit_loss' => $profit_loss]);
}

function get_current_xrp_price() {
    // Use cached price if available
    $price_data = get_cache('xrp_price_data');
    if ($price_data && isset($price_data['usd']['price'])) {
        return (float)$price_data['usd']['price'];
    }
    // Fallback
    return 0.5;
} 