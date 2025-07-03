<?php
/**
 * XRP Specter - Polls API
 * Handle poll voting and management
 */

define('XRP_SPECTER', true);
require_once '../config.php';
require_once '../db.php';
require_once '../auth.php';
require_once '../utils.php';

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in
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
    error_log("Polls API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

function handleGetRequest($action) {
    switch ($action) {
        case 'list':
            getPolls();
            break;
        case 'results':
            getPollResults();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function handlePostRequest($action) {
    switch ($action) {
        case 'vote':
            submitVote();
            break;
        case 'create':
            createPoll();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function getPolls() {
    $polls = get_polls(true);
    
    // Add user voting status
    $user_id = current_user()['id'];
    foreach ($polls as &$poll) {
        $poll['user_has_voted'] = user_has_voted($user_id, $poll['id']);
        $poll['user_vote'] = null;
        
        if ($poll['user_has_voted']) {
            $vote = db()->fetch(
                "SELECT option FROM poll_votes WHERE user_id = ? AND poll_id = ?",
                [$user_id, $poll['id']]
            );
            $poll['user_vote'] = $vote['option'] ?? null;
        }
    }
    
    echo json_encode([
        'success' => true,
        'polls' => $polls
    ]);
}

function getPollResults() {
    $poll_id = $_GET['poll_id'] ?? null;
    
    if (!$poll_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Poll ID required']);
        return;
    }
    
    $results = get_poll_results($poll_id);
    $poll = db()->fetch("SELECT * FROM polls WHERE id = ?", [$poll_id]);
    
    if (!$poll) {
        http_response_code(404);
        echo json_encode(['error' => 'Poll not found']);
        return;
    }
    
    // Calculate percentages
    $total_votes = array_sum(array_column($results, 'votes'));
    foreach ($results as &$result) {
        $result['percentage'] = $total_votes > 0 ? round(($result['votes'] / $total_votes) * 100, 1) : 0;
    }
    
    echo json_encode([
        'success' => true,
        'poll' => $poll,
        'results' => $results,
        'total_votes' => $total_votes
    ]);
}

function submitVote() {
    $input = json_decode(file_get_contents('php://input'), true);
    $poll_id = $input['poll_id'] ?? null;
    $option = $input['option'] ?? null;
    
    if (!$poll_id || !$option) {
        http_response_code(400);
        echo json_encode(['error' => 'Poll ID and option required']);
        return;
    }
    
    $user_id = current_user()['id'];
    
    // Check if poll exists and is active
    $poll = db()->fetch("SELECT * FROM polls WHERE id = ? AND active = 1", [$poll_id]);
    if (!$poll) {
        http_response_code(404);
        echo json_encode(['error' => 'Poll not found or inactive']);
        return;
    }
    
    // Check if poll has expired
    if ($poll['expires_at'] && strtotime($poll['expires_at']) < time()) {
        http_response_code(400);
        echo json_encode(['error' => 'Poll has expired']);
        return;
    }
    
    // Check if user has already voted
    if (user_has_voted($user_id, $poll_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'You have already voted in this poll']);
        return;
    }
    
    // Validate option
    $options = json_decode($poll['options'], true);
    if (!in_array($option, $options)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid option']);
        return;
    }
    
    // Submit vote
    $result = add_vote($user_id, $poll_id, $option);
    
    if ($result) {
        // Award XP for voting
        add_xp($user_id, XP_POLL_VOTE, 'poll_vote', 'Voted in community poll');
        
        // Create notification
        create_notification($user_id, 'success', 'Vote Submitted', 'Your vote has been recorded! +' . XP_POLL_VOTE . ' XP');
        
        echo json_encode([
            'success' => true,
            'message' => 'Vote submitted successfully',
            'xp_awarded' => XP_POLL_VOTE
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to submit vote']);
    }
}

function createPoll() {
    // Check if user is admin or moderator
    $user = current_user();
    if (!in_array($user['role'], ['admin', 'moderator'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Insufficient permissions']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $question = $input['question'] ?? '';
    $options = $input['options'] ?? [];
    $expires_at = $input['expires_at'] ?? null;
    
    if (!$question || empty($options)) {
        http_response_code(400);
        echo json_encode(['error' => 'Question and options required']);
        return;
    }
    
    // Validate options (minimum 2, maximum 10)
    if (count($options) < 2 || count($options) > 10) {
        http_response_code(400);
        echo json_encode(['error' => 'Poll must have 2-10 options']);
        return;
    }
    
    $poll_data = [
        'question' => $question,
        'options' => json_encode($options),
        'active' => 1,
        'created_by' => $user['id']
    ];
    
    if ($expires_at) {
        $poll_data['expires_at'] = $expires_at;
    }
    
    $poll_id = db()->insert('polls', $poll_data);
    
    if ($poll_id) {
        echo json_encode([
            'success' => true,
            'message' => 'Poll created successfully',
            'poll_id' => $poll_id
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create poll']);
    }
}
?> 