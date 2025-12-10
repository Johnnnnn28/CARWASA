<?php
// admin_dashboard.php
session_start();

// Database connection
$host = 'localhost';
$db = 'carwasa_dbfinal';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle Water Validation Approval/Decline
if (isset($_POST['validate_user'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];

    $stmt = $pdo->prepare("UPDATE users SET status=? WHERE user_id=?");
    $stmt->execute([$action, $user_id]);

    $stmt = $pdo->prepare("SELECT email, first_name FROM users WHERE user_id=?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user) {
        $to = $user['email'];
        $subject = "CARWASA Account Validation";
        $message = "Hello ".$user['first_name'].",\n\nYour account has been ".$action.".\n\nRegards,\nCARWASA";
        $headers = "From: carwasa@example.com";
        @mail($to, $subject, $message, $headers);
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Handle Water Connection Approval/Decline
if (isset($_POST['validate_connection'])) {
    $conn_id = $_POST['conn_id'];
    $action = $_POST['action'];

    if ($action == 'declined') {
        $stmt = $pdo->prepare("DELETE FROM new_connection_request WHERE id=?");
        $stmt->execute([$conn_id]);
    } elseif ($action == 'approved') {
        // You may want to move to approved connections table or update status
        $stmt = $pdo->prepare("DELETE FROM new_connection_request WHERE id=?");
        $stmt->execute([$conn_id]);
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Handle Water Disconnection Approval/Decline
if (isset($_POST['validate_disconnection'])) {
    $disc_id = $_POST['disc_id'];
    $action = $_POST['action'];

    $stmt = $pdo->prepare("SELECT email, first_name, last_name FROM disconnection_request WHERE id=?");
    $stmt->execute([$disc_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        $to = $user['email'];
        $subject = "CARWASA Disconnection Request";
        $message = "Hello ".$user['first_name']." ".$user['last_name'].",\n\nYour disconnection request has been ".$action.".\n\nRegards,\nCARWASA";
        $headers = "From: carwasa@example.com";
        @mail($to, $subject, $message, $headers);
    }
    
    if ($action == 'declined' || $action == 'approved') {
        $stmt = $pdo->prepare("DELETE FROM disconnection_request WHERE id=?");
        $stmt->execute([$disc_id]);
    }
    
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Handle Water Interruption CRUD - FIXED: Using interruption_id instead of id
if (isset($_POST['add_interruption'])) {
    // Generate unique interruption_id
    $interruption_id = rand(1000, 9999);
    $stmt = $pdo->prepare("INSERT INTO water_interruptions (interruption_id, type, description, day, month, year, posted_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$interruption_id, $_POST['type'], $_POST['description'], $_POST['day'], $_POST['month'], $_POST['year']]);
    header("Location: ".$_SERVER['PHP_SELF']."?tab=interruptions");
    exit;
}

if (isset($_POST['update_interruption'])) {
    $stmt = $pdo->prepare("UPDATE water_interruptions SET type=?, description=?, day=?, month=?, year=? WHERE interruption_id=?");
    $stmt->execute([$_POST['type'], $_POST['description'], $_POST['day'], $_POST['month'], $_POST['year'], $_POST['interruption_id']]);
    header("Location: ".$_SERVER['PHP_SELF']."?tab=interruptions");
    exit;
}

if (isset($_POST['delete_interruption'])) {
    $stmt = $pdo->prepare("DELETE FROM water_interruptions WHERE interruption_id=?");
    $stmt->execute([$_POST['interruption_id']]);
    header("Location: ".$_SERVER['PHP_SELF']."?tab=interruptions");
    exit;
}

// Handle Water Quality CRUD - FIXED: Using report_id instead of id
if (isset($_POST['add_quality'])) {
    // Generate unique report_id
    $report_id = rand(10, 99);
    $stmt = $pdo->prepare("INSERT INTO water_quality_reports (report_id, parameter, result, status, test_date, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$report_id, $_POST['parameter'], $_POST['result'], $_POST['status'], $_POST['test_date']]);
    header("Location: ".$_SERVER['PHP_SELF']."?tab=quality");
    exit;
}

if (isset($_POST['update_quality'])) {
    $stmt = $pdo->prepare("UPDATE water_quality_reports SET parameter=?, result=?, status=?, test_date=? WHERE report_id=?");
    $stmt->execute([$_POST['parameter'], $_POST['result'], $_POST['status'], $_POST['test_date'], $_POST['report_id']]);
    header("Location: ".$_SERVER['PHP_SELF']."?tab=quality");
    exit;
}

if (isset($_POST['delete_quality'])) {
    $stmt = $pdo->prepare("DELETE FROM water_quality_reports WHERE report_id=?");
    $stmt->execute([$_POST['report_id']]);
    header("Location: ".$_SERVER['PHP_SELF']."?tab=quality");
    exit;
}

// Handle News Article CRUD - FIXED: Using news_id instead of id
if (isset($_POST['add_news'])) {
    $stmt = $pdo->prepare("INSERT INTO news_articles (Title, content, status, post_day, post_month, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$_POST['title'], $_POST['content'], $_POST['status'], $_POST['post_day'], $_POST['post_month']]);
    header("Location: ".$_SERVER['PHP_SELF']."?tab=news");
    exit;
}

if (isset($_POST['update_news'])) {
    $stmt = $pdo->prepare("UPDATE news_articles SET Title=?, content=?, status=?, post_day=?, post_month=? WHERE news_id=?");
    $stmt->execute([$_POST['title'], $_POST['content'], $_POST['status'], $_POST['post_day'], $_POST['post_month'], $_POST['news_id']]);
    header("Location: ".$_SERVER['PHP_SELF']."?tab=news");
    exit;
}

if (isset($_POST['delete_news'])) {
    $stmt = $pdo->prepare("DELETE FROM news_articles WHERE news_id=?");
    $stmt->execute([$_POST['news_id']]);
    header("Location: ".$_SERVER['PHP_SELF']."?tab=news");
    exit;
}

// Fetch all data
$users = $pdo->query("SELECT * FROM users WHERE status='pending'")->fetchAll();
$connections = $pdo->query("SELECT * FROM new_connection_request ORDER BY created_at DESC")->fetchAll();
$disconnections = $pdo->query("SELECT * FROM disconnection_request ORDER BY created_at DESC")->fetchAll();
$interruptions = $pdo->query("SELECT * FROM water_interruptions ORDER BY posted_at DESC")->fetchAll();
$quality = $pdo->query("SELECT * FROM water_quality_reports ORDER BY created_at DESC")->fetchAll();
$news = $pdo->query("SELECT * FROM news_articles ORDER BY created_at DESC")->fetchAll();

// Get active tab from URL
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'meter';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - CARWASA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f8fa;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #003d82 0%, #001f4d 100%);
            color: white;
            padding: 24px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
        }
        .header-content {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .header h1 { font-size: 26px; font-weight: 600; }
        .header p { font-size: 15px; opacity: 0.9; margin-top: 4px; }

        .container { max-width: 1300px; margin: 40px auto; padding: 0 20px; }

        .tabs {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .tabs-header {
            display: flex;
            border-bottom: 1px solid #e2e8f0;
            overflow-x: auto;
            scrollbar-width: none;
            white-space: nowrap;
        }
        .tabs-header::-webkit-scrollbar { display: none; }

        .tab-button {
            flex: none;
            min-width: 180px;
            padding: 20px 16px;
            background: none;
            border: none;
            font-size: 15px;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            position: relative;
        }
        .tab-button:hover { background: #f8fafc; color: #003d82; }
        .tab-button.active {
            color: #003d82;
            font-weight: 600;
            background: #f0f7ff;
        }
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 4px;
            background: #003d82;
            border-radius: 4px 4px 0 0;
        }

        .tab-content { padding: 40px; display: none; }
        .tab-content.active { display: block; }

        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: #003d82;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card {
            background: white;
            border-radius: 14px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        .card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.15); }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .card-title { font-size: 18px; font-weight: 600; }
        .card-subtitle { font-size: 14px; color: #666; margin-top: 4px; }

        .detail {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 12px 0;
        }
        .detail i { color: #003d82; width: 20px; }

        .actions { margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap; }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14.5px;
            transition: all 0.3s;
        }
        .btn-primary { background: #003d82; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-view { background: #007bff; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-sm { padding: 8px 16px; font-size: 13px; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

        .badge {
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 12.5px;
            font-weight: 600;
        }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-safe { background: #d4edda; color: #155724; }
        .badge-attention { background: #fff3cd; color: #856404; }
        .badge-unsafe { background: #f8d7da; color: #721c24; }
        .badge-published { background: #d1ecf1; color: #0c5460; }
        .badge-draft { background: #e2e3e5; color: #383d41; }
        .badge-emergency { background: #f8d7da; color: #721c24; }
        .badge-scheduled { background: #d1ecf1; color: #0c5460; }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }
        .modal.active { display: block; }
        .modal-content {
            background-color: white;
            margin: 50px auto;
            padding: 40px;
            border-radius: 16px;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .modal-header {
            font-size: 24px;
            font-weight: 600;
            color: #003d82;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .close {
            color: #aaa;
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        .close:hover { color: #000; }

        .form-group {
            margin-bottom: 24px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: border 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #003d82;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        @media (max-width: 768px) {
            .tabs-header { flex-direction: column; }
            .tab-button { min-width: 100%; }
            .actions { flex-direction: column; }
            .btn { width: 100%; }
            .modal-content { margin: 20px; padding: 20px; }
        }
    </style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <img src="images/logo.png" alt="CARWASA Logo" style="width:44px;height:44px;border-radius:12px;object-fit:cover;" onerror="this.style.display='none'">
        <div>
            <h1>Super Admin Dashboard</h1>
            <p>CARWASA Management System</p>
        </div>
    </div>
</div>

<div class="container">
    <div class="tabs">
        <div class="tabs-header">
            <button class="tab-button <?=$activeTab=='meter'?'active':''?>" onclick="switchTab('meter')">Water Meter Validation</button>
            <button class="tab-button <?=$activeTab=='connection'?'active':''?>" onclick="switchTab('connection')">Water Connection Request</button>
            <button class="tab-button <?=$activeTab=='disconnection'?'active':''?>" onclick="switchTab('disconnection')">Water Disconnection</button>
            <button class="tab-button <?=$activeTab=='interruptions'?'active':''?>" onclick="switchTab('interruptions')">Water Interruptions</button>
            <button class="tab-button <?=$activeTab=='quality'?'active':''?>" onclick="switchTab('quality')">Water Quality</button>
            <button class="tab-button <?=$activeTab=='news'?'active':''?>" onclick="switchTab('news')">News Articles</button>
        </div>

        <div class="tab-content <?=$activeTab=='meter'?'active':''?>" id="meter-content">
            <h2 class="section-title">Water Meter Validation Requests</h2>
            <?php if(empty($users)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No pending validation requests</p>
                </div>
            <?php else: ?>
                <?php foreach($users as $user): ?>
                <div class="card">
                    <div class="card-title"><?=htmlspecialchars($user['first_name'].' '.$user['last_name'])?></div>
                    <div class="card-subtitle">Email: <?=htmlspecialchars($user['email'])?></div>
                    <div class="detail"><i class="fas fa-phone"></i> <?=htmlspecialchars($user['phone'])?></div>
                    <div class="detail"><i class="fas fa-tachometer-alt"></i> Meter #: <?=htmlspecialchars($user['meter_number'])?></div>
                    <div class="detail"><i class="fas fa-calendar"></i> Registered: <?=htmlspecialchars($user['created_at'])?></div>
                    <form method="post" class="actions">
                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                        <input type="hidden" name="action" value="validated">
                        <button class="btn btn-success" name="validate_user" type="submit">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    </form>
                    <form method="post" class="actions">
                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                        <input type="hidden" name="action" value="invalidated">
                        <button class="btn btn-danger" name="validate_user" type="submit">
                            <i class="fas fa-times"></i> Decline
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="tab-content <?=$activeTab=='connection'?'active':''?>" id="connection-content">
            <h2 class="section-title">New Water Connection Requests</h2>
            <?php if(empty($connections)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No pending connection requests</p>
                </div>
            <?php else: ?>
                <?php foreach($connections as $conn): ?>
                <div class="card">
                    <div class="card-title">Connection Request #<?=htmlspecialchars($conn['id'])?></div>
                    <div class="detail"><i class="fas fa-calendar"></i> Requested: <?=htmlspecialchars($conn['request_date'])?></div>
                    <div class="detail"><i class="fas fa-paperclip"></i> Document: <?=htmlspecialchars($conn['attachment_file'])?></div>
                    <div class="detail"><i class="fas fa-clock"></i> Submitted: <?=htmlspecialchars($conn['created_at'])?></div>
                    <form method="post" class="actions">
                        <input type="hidden" name="conn_id" value="<?= $conn['id'] ?>">
                        <input type="hidden" name="action" value="approved">
                        <button class="btn btn-success" name="validate_connection" type="submit">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    </form>
                    <form method="post" class="actions">
                        <input type="hidden" name="conn_id" value="<?= $conn['id'] ?>">
                        <input type="hidden" name="action" value="declined">
                        <button class="btn btn-danger" name="validate_connection" type="submit">
                            <i class="fas fa-times"></i> Decline
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="tab-content <?=$activeTab=='disconnection'?'active':''?>" id="disconnection-content">
            <h2 class="section-title">Water Disconnection Requests</h2>
            <?php if(empty($disconnections)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No pending disconnection requests</p>
                </div>
            <?php else: ?>
                <?php foreach($disconnections as $disc): ?>
                <div class="card">
                    <div class="card-title"><?=htmlspecialchars($disc['first_name'].' '.$disc['last_name'])?></div>
                    <div class="card-subtitle">Water Number: <?=htmlspecialchars($disc['water_number'])?></div>
                    <div class="detail"><i class="fas fa-envelope"></i> <?=htmlspecialchars($disc['email'])?></div>
                    <div class="detail"><i class="fas fa-calendar"></i> Requested: <?=htmlspecialchars($disc['request_date'])?></div>
                    <div class="detail"><i class="fas fa-paperclip"></i> Document: <?=htmlspecialchars($disc['attachment_file'])?></div>
                    <form method="post" class="actions">
                        <input type="hidden" name="disc_id" value="<?= $disc['id'] ?>">
                        <input type="hidden" name="action" value="approved">
                        <button class="btn btn-success" name="validate_disconnection" type="submit">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    </form>
                    <form method="post" class="actions">
                        <input type="hidden" name="disc_id" value="<?= $disc['id'] ?>">
                        <input type="hidden" name="action" value="declined">
                        <button class="btn btn-danger" name="validate_disconnection" type="submit">
                            <i class="fas fa-times"></i> Decline
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="tab-content <?=$activeTab=='interruptions'?'active':''?>" id="interruptions-content">
            <h2 class="section-title">
                Water Interruptions
                <button class="btn btn-primary" onclick="openModal('interruption-modal', 'add')">
                    <i class="fas fa-plus"></i> Add New
                </button>
            </h2>
            <?php foreach($interruptions as $int): ?>
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title"><?=htmlspecialchars(ucfirst($int['type']))?> Interruption</div>
                        <div class="card-subtitle">Scheduled: <?=htmlspecialchars($int['day'].'/'.$int['month'].'/'.$int['year'])?></div>
                    </div>
                    <span class="badge badge-<?=htmlspecialchars($int['type'])?>">
                        <?=htmlspecialchars(ucfirst($int['type']))?>
                    </span>
                </div>
                <p><?=htmlspecialchars($int['description'])?></p>
                <div class="detail"><i class="fas fa-clock"></i> Posted: <?=htmlspecialchars($int['posted_at'])?></div>
                <div class="actions">
                    <button class="btn btn-info btn-sm" onclick="editInterruption(<?=htmlspecialchars(json_encode($int), ENT_QUOTES, 'UTF-8')?>)">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="interruption_id" value="<?=$int['interruption_id']?>">
                        <button class="btn btn-danger btn-sm" name="delete_interruption" type="submit" onclick="return confirm('Are you sure you want to delete this interruption?')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="tab-content <?=$activeTab=='quality'?'active':''?>" id="quality-content">
            <h2 class="section-title">
                Water Quality Reports
                <button class="btn btn-primary" onclick="openModal('quality-modal', 'add')">
                    <i class="fas fa-plus"></i> Add New
                </button>
            </h2>
            <?php foreach($quality as $q): ?>
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title"><?=htmlspecialchars($q['parameter'])?></div>
                        <div class="card-subtitle">Test Date: <?=htmlspecialchars($q['test_date'])?></div>
                    </div>
                    <span class="badge badge-<?=htmlspecialchars($q['status'])?>">
                        <?=htmlspecialchars(ucfirst($q['status']))?>
                    </span>
                </div>
                <div class="detail"><i class="fas fa-vial"></i> Result: <?=htmlspecialchars($q['result'])?></div>
                <div class="detail"><i class="fas fa-calendar"></i> Recorded: <?=htmlspecialchars($q['created_at'])?></div>
                <div class="actions">
                    <button class="btn btn-info btn-sm" onclick='editQuality(<?=htmlspecialchars(json_encode($q), ENT_QUOTES, "UTF-8")?>)'>
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="report_id" value="<?=$q['report_id']?>">
                        <button class="btn btn-danger btn-sm" name="delete_quality" type="submit" onclick="return confirm('Are you sure you want to delete this report?')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="tab-content <?=$activeTab=='news'?'active':''?>" id="news-content">
            <h2 class="section-title">
                News Articles
                <button class="btn btn-primary" onclick="openModal('news-modal', 'add')">
                    <i class="fas fa-plus"></i> Add New
</button>
            </h2>
            <?php foreach($news as $n): ?>
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title"><?=htmlspecialchars($n['Title'])?></div>
                        <div class="card-subtitle">Posted: <?=htmlspecialchars($n['post_day'].'/'.$n['post_month'])?></div>
                    </div>
                    <span class="badge badge-<?=strtolower($n['status'])?>">
                        <?=htmlspecialchars($n['status'])?>
                    </span>
                </div>
                <p><?=htmlspecialchars(substr(strip_tags($n['content']), 0, 200))?>...</p>
                <div class="detail"><i class="fas fa-clock"></i> Created: <?=htmlspecialchars($n['created_at'])?></div>
                <div class="actions">
                    <button class="btn btn-info btn-sm" onclick='editNews(<?=htmlspecialchars(json_encode($n), ENT_QUOTES, "UTF-8")?>)'>
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="news_id" value="<?=$n['news_id']?>">
                        <button class="btn btn-danger btn-sm" name="delete_news" type="submit" onclick="return confirm('Are you sure you want to delete this article?')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<!-- Interruption Modal -->
<div id="interruption-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span id="interruption-modal-title">Add Water Interruption</span>
            <span class="close" onclick="closeModal('interruption-modal')">&times;</span>
        </div>
        <form method="post" id="interruption-form">
            <input type="hidden" name="interruption_id" id="int-interruption-id">
            <div class="form-group">
                <label>Type</label>
                <select name="type" id="int-type" required>
                    <option value="scheduled">Scheduled</option>
                    <option value="emergency">Emergency</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="int-description" required></textarea>
            </div>
            <div class="form-group">
                <label>Day</label>
                <input type="number" name="day" id="int-day" min="1" max="31" required>
            </div>
            <div class="form-group">
                <label>Month</label>
                <input type="number" name="month" id="int-month" min="1" max="12" required>
            </div>
            <div class="form-group">
                <label>Year</label>
                <input type="number" name="year" id="int-year" min="2024" required>
            </div>
            <button type="submit" class="btn btn-primary" id="interruption-submit-btn" name="add_interruption">
                <i class="fas fa-save"></i> Save
            </button>
        </form>
    </div>
</div>

<!-- Quality Modal -->
<div id="quality-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span id="quality-modal-title">Add Water Quality Report</span>
            <span class="close" onclick="closeModal('quality-modal')">&times;</span>
        </div>
        <form method="post" id="quality-form">
            <input type="hidden" name="report_id" id="qual-report-id">
            <div class="form-group">
                <label>Parameter</label>
                <select name="parameter" id="qual-parameter" required>
                    <option value="pH Level">pH Level</option>
                    <option value="Chlorine">Chlorine</option>
                    <option value="Turbidity">Turbidity</option>
                    <option value="Lead">Lead</option>
                    <option value="Nitrate">Nitrate</option>
                </select>
            </div>
            <div class="form-group">
                <label>Result</label>
                <input type="text" name="result" id="qual-result" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="qual-status" required>
                    <option value="safe">Safe</option>
                    <option value="attention">Attention</option>
                    <option value="unsafe">Unsafe</option>
                </select>
            </div>
            <div class="form-group">
                <label>Test Date</label>
                <input type="date" name="test_date" id="qual-test-date" required>
            </div>
            <button type="submit" class="btn btn-primary" id="quality-submit-btn" name="add_quality">
                <i class="fas fa-save"></i> Save
            </button>
        </form>
    </div>
</div>

<!-- News Modal -->
<div id="news-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span id="news-modal-title">Add News Article</span>
            <span class="close" onclick="closeModal('news-modal')">&times;</span>
        </div>
        <form method="post" id="news-form">
            <input type="hidden" name="news_id" id="news-news-id">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" id="news-title" required>
            </div>
            <div class="form-group">
                <label>Content</label>
                <textarea name="content" id="news-content" required style="min-height: 200px;"></textarea>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" id="news-status" required>
                    <option value="Published">Published</option>
                    <option value="Draft">Draft</option>
                </select>
            </div>
            <div class="form-group">
                <label>Post Day</label>
                <input type="number" name="post_day" id="news-day" min="1" max="31" required>
            </div>
            <div class="form-group">
                <label>Post Month</label>
                <input type="number" name="post_month" id="news-month" min="1" max="12" required>
            </div>
            <button type="submit" class="btn btn-primary" id="news-submit-btn" name="add_news">
                <i class="fas fa-save"></i> Save
            </button>
        </form>
    </div>
</div>

<script>
function switchTab(id){
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.tab-button').forEach(b=>b.classList.remove('active'));
    document.getElementById(id+'-content').classList.add('active');
    // Find the corresponding button using its onclick attribute
    document.querySelector(`.tab-button[onclick="switchTab('${id}')"]`).classList.add('active');
    // Update URL history without reloading
    history.pushState(null, '', `?tab=${id}`);
}

// Modal Functions
function openModal(modalId, mode) {
    document.getElementById(modalId).classList.add('active');
    if (mode === 'add') {
        if (modalId === 'interruption-modal') {
            document.getElementById('interruption-modal-title').textContent = 'Add Water Interruption';
            document.getElementById('interruption-submit-btn').name = 'add_interruption';
            document.getElementById('interruption-form').reset();
            document.getElementById('int-interruption-id').value = '';
        } else if (modalId === 'quality-modal') {
            document.getElementById('quality-modal-title').textContent = 'Add Water Quality Report';
            document.getElementById('quality-submit-btn').name = 'add_quality';
            document.getElementById('quality-form').reset();
            document.getElementById('qual-report-id').value = '';
        } else if (modalId === 'news-modal') {
            document.getElementById('news-modal-title').textContent = 'Add News Article';
            document.getElementById('news-submit-btn').name = 'add_news';
            document.getElementById('news-form').reset();
            document.getElementById('news-news-id').value = '';
        }
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Interruption Edit
function editInterruption(data) {
    openModal('interruption-modal', 'edit');
    document.getElementById('interruption-modal-title').textContent = 'Edit Water Interruption';
    document.getElementById('interruption-submit-btn').name = 'update_interruption';
    
    document.getElementById('int-interruption-id').value = data.interruption_id;
    document.getElementById('int-type').value = data.type;
    document.getElementById('int-description').value = data.description;
    document.getElementById('int-day').value = data.day;
    document.getElementById('int-month').value = data.month;
    document.getElementById('int-year').value = data.year;
}

// Quality Edit
function editQuality(data) {
    openModal('quality-modal', 'edit');
    document.getElementById('quality-modal-title').textContent = 'Edit Water Quality Report';
    document.getElementById('quality-submit-btn').name = 'update_quality';
    
    document.getElementById('qual-report-id').value = data.report_id;
    document.getElementById('qual-parameter').value = data.parameter;
    document.getElementById('qual-result').value = data.result;
    document.getElementById('qual-status').value = data.status;
    document.getElementById('qual-test-date').value = data.test_date;
}

// News Edit
function editNews(data) {
    openModal('news-modal', 'edit');
    document.getElementById('news-modal-title').textContent = 'Edit News Article';
    document.getElementById('news-submit-btn').name = 'update_news';
    
    document.getElementById('news-news-id').value = data.news_id;
    document.getElementById('news-title').value = data.Title;
    document.getElementById('news-content').value = data.content;
    document.getElementById('news-status').value = data.status;
    document.getElementById('news-day').value = data.post_day;
    document.getElementById('news-month').value = data.post_month;
}

// Initial switch to activate the correct tab on load
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab') || 'meter';
    switchTab(initialTab);

    // Close modal when clicking outside
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal.id);
            }
        });
    });
});
</script>

</body>
</html>