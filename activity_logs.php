<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Get search params
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$actionFilter = isset($_GET['action_filter']) ? trim($_GET['action_filter']) : '';

// Get pagination params
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$whereClauses = [];
$queryParams = [];

if ($search !== '') {
    $whereClauses[] = "(u.username LIKE :search OR al.action LIKE :search OR al.table_name LIKE :search OR al.new_value LIKE :search)";
    $queryParams[':search'] = "%$search%";
}

if ($actionFilter !== '') {
    $whereClauses[] = "al.action = :action_filter";
    $queryParams[':action_filter'] = $actionFilter;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM activity_logs al LEFT JOIN users u ON al.user_id = u.user_id $whereSql";
$countStmt = $pdo->prepare($countQuery);
foreach ($queryParams as $key => $val) { $countStmt->bindValue($key, $val); }
$countStmt->execute();
$totalLogs = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalLogs / $limit);

// Get activity logs with user info
$query = "
    SELECT 
        al.log_id,
        al.user_id,
        al.action,
        al.timestamp,
        al.table_name,
        al.record_id,
        u.username,
        u.email,
        u.role
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    $whereSql
    ORDER BY al.timestamp DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($query);
foreach ($queryParams as $key => $val) { $stmt->bindValue($key, $val); }
$stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique actions for filter dropdown
$actionStmt = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action ASC");
$actionTypes = $actionStmt->fetchAll(PDO::FETCH_COLUMN);

// Generate asset version
$assetFiles = ['styles.css', 'gui-override.css', 'app.js'];
$assetVersion = 0;
foreach ($assetFiles as $af) {
    $path = __DIR__ . DIRECTORY_SEPARATOR . $af;
    if (file_exists($path)) {
        $assetVersion = max($assetVersion, filemtime($path));
    }
}
if (!$assetVersion) $assetVersion = time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="design-system.css">
    <link rel="stylesheet" href="styles.css?v=<?php echo $assetVersion; ?>">
    <link rel="stylesheet" href="gui-override.css?v=<?php echo $assetVersion; ?>">
    <script src="app.js?v=<?php echo $assetVersion; ?>" defer></script>
    <?php injectThemeStyles(); ?>
    <style>
        /* FORCE FULL WIDTH EXPANSION */
        .main-panel, 
        .content-scroll-area, 
        .wide-layout-container {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        .content-scroll-area {
            padding: 0 !important;
        }

        .wide-layout-container {
            padding: 20px !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 32px !important;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            padding: 20px !important;
            background: rgba(0,0,0,0.15);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            width: 100% !important;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            width: 100%;
        }
        
        .stat-card {
            background: rgba(13, 39, 56, 0.7) !important;
            border: 1px solid rgba(45, 168, 187, 0.3) !important;
            padding: 24px;
            border-radius: var(--radius-lg);
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(12px);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #35a7b8 !important;
            background: rgba(13, 39, 56, 0.85) !important;
        }

        .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #35a7b8;
            font-weight: 800;
            margin-bottom: 12px;
            letter-spacing: 1.5px;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -1px;
        }

        /* Badge Styling */
        .action-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            display: inline-block;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.4);
        }

        .action-create { background: #28a745 !important; color: #ffffff !important; }
        .action-update { background: #007bff !important; color: #ffffff !important; }
        .action-delete { background: #dc3545 !important; color: #ffffff !important; }
        
        .user-info .name { font-weight: 700; color: #ffffff; display: block; }
        .user-info .email { font-size: 0.75rem; color: rgba(255,255,255,0.4); }

        .table-container {
            max-height: 600px;
            overflow-y: auto;
            overflow-x: auto;
            border-radius: 0 0 12px 12px;
            background: rgba(0,0,0,0.05);
            scrollbar-width: thin;
            scrollbar-color: rgba(53, 167, 184, 0.3) rgba(0,0,0,0.1);
        }
        
        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.1);
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: rgba(53, 167, 184, 0.3);
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: rgba(53, 167, 184, 0.5);
        }

        /* Sticky header */
        .registry-table thead th {
            position: sticky !important;
            top: 0 !important;
            z-index: 20 !important;
            background: #0a1e2b !important; /* Matches panel color */
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .panel {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            overflow: hidden; /* Clips the scrollable container's corners */
        }

        .btn-back {
            background: linear-gradient(135deg, #2c6aa2, #1e4d73) !important;
            color: white !important;
            padding: 12px 24px !important;
            border-radius: 8px !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none !important;
            box-shadow: 0 4px 12px rgba(44, 106, 162, 0.4) !important;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 106, 162, 0.6) !important;
        }
    </style>
</head>
<body>
  <section class="app-shell" id="appShell">
    <button class="sidebar-collapse-btn" id="sidebarCollapseToggle" onclick="toggleSidebarCollapse()" title="Toggle Sidebar">‹</button>
    <div class="canvas">
      <aside class="sidebar" id="mainSidebar">
        <div class="logo-block">
          <img src="logo.jpg" alt="Fisherfolk IMS" class="sidebar-logo" />
          <div id="logoText">Fisherfolk IMS<span class="system-tag">Admin Workspace</span></div>
        </div>
        <nav class="nav-list" id="navList">
          <div class="nav-header">Overview</div>
          <a class="nav-item" href="admin.php?view=dashboard"><i class="fas fa-house-chimney-window"></i><span>Dashboard</span></a>
          
          <div class="nav-header">Registry & Records</div>
          <a class="nav-item" href="admin.php?view=register-fisherfolk"><i class="fas fa-user-plus"></i><span>Resident</span></a>
          <a class="nav-item" href="admin.php?view=id-printing-queue"><i class="fas fa-id-card-clip"></i><span>ID Issuance Queue</span></a>
          <a class="nav-item" href="admin.php?view=archive-storage"><i class="fas fa-box-archive"></i><span>Record Archives</span></a>
          
          <div class="nav-header">Analytics & Services</div>
          <a class="nav-item" href="admin.php?view=subsidy-reports-admin"><i class="fas fa-hand-holding-dollar"></i><span>Subsidy Management</span></a>
          <a class="nav-item" href="admin.php?view=announcements-admin"><i class="fas fa-bullhorn"></i><span>System Broadcasts</span></a>
          <a class="nav-item" href="admin.php?view=reports-analytics"><i class="fas fa-database"></i><span>Master Registry</span></a>
          <a class="nav-item" href="admin.php?view=reported-posts"><i class="fas fa-flag-checkered"></i><span>Content Moderation</span></a>
          
          <div class="nav-header">System Config</div>
          <a class="nav-item" href="admin.php?view=settings-users"><i class="fas fa-gears"></i><span>Barangay Configuration</span></a>
          <a class="nav-item" href="theme_settings.php"><i class="fas fa-palette"></i><span>Interface Themes</span></a>
          <a class="nav-item active" href="activity_logs.php"><i class="fas fa-file-shield"></i><span>System Audit Logs</span></a>
        </nav>
      </aside>

      <main class="main-panel">
        <header class="topbar">
          <div class="breadcrumbs">
            <span class="crumb">Admin Workspace</span>
            <span class="sep">/</span>
            <span class="crumb current">System Audit Logs</span>
          </div>
          <div class="profile-wrap" id="profileWrap">
            <div class="profile-info">
              <span class="profile-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Administrator'); ?></span>
              <span class="profile-role">Admin</span>
            </div>
          </div>
        </header>

        <div class="content-scroll-area">
          <section class="wide-layout-container">
            <!-- Header and Back Button -->
            <div class="header-actions">
              <h1 class="page-title">Activity Logs</h1>
              <button class="btn-back" onclick="goBack()">
                <span>← Back to Dashboard</span>
              </button>
            </div>

            <!-- Stats Row -->
            <div class="stat-grid">
              <div class="stat-card">
                <div class="stat-label">Total Activities</div>
                <div class="stat-value"><?php echo number_format($totalLogs); ?></div>
              </div>
              <div class="stat-card">
                <div class="stat-label">Entries on Page</div>
                <div class="stat-value"><?php echo count($logs); ?></div>
              </div>
              <div class="stat-card">
                <div class="stat-label">Current Page</div>
                <div class="stat-value"><?php echo $page; ?> / <?php echo max(1, $totalPages); ?></div>
              </div>
            </div>

            <!-- Search Toolbar -->
            <div class="panel" style="margin-bottom: 0 !important; background: rgba(13, 39, 56, 0.4) !important; border: 1px solid rgba(45, 168, 187, 0.2) !important;">
              <form action="activity_logs.php" method="GET" style="display: flex; gap: 20px; padding: 20px; flex-wrap: wrap; align-items: flex-end;">
                <div style="flex: 1; min-width: 250px;">
                  <label class="stat-label" style="display: block; margin-bottom: 8px; font-size: 0.65rem; color: #35a7b8;">Search Audit Trail</label>
                  <input type="text" name="search" class="input-field" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by username, action, or table..." style="width: 100%; background: rgba(0,0,0,0.2) !important; border-color: rgba(255,255,255,0.1) !important; color: #fff;">
                </div>
                <div style="width: 250px;">
                  <label class="stat-label" style="display: block; margin-bottom: 8px; font-size: 0.65rem; color: #35a7b8;">Filter by Action</label>
                  <select name="action_filter" class="input-field" style="width: 100%; background: rgba(0,0,0,0.2) !important; border-color: rgba(255,255,255,0.1) !important; color: #fff;">
                    <option value="">All Actions</option>
                    <?php foreach ($actionTypes as $type): ?>
                      <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $actionFilter === $type ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(str_replace('_', ' ', strtoupper($type))); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div style="display: flex; gap: 12px;">
                  <button type="submit" class="btn primary sm" style="padding: 12px 24px !important; font-size: 0.75rem; min-width: 120px;">Apply Filter</button>
                  <a href="activity_logs.php" class="btn secondary sm" style="padding: 12px 24px !important; font-size: 0.75rem; text-decoration: none; display: flex; align-items: center; justify-content: center; background: rgba(255,255,255,0.05) !important; min-width: 100px;">Clear</a>
                </div>
              </form>
            </div>

            <!-- History Panel -->
            <article class="panel">
              <div class="panel-header" style="background: linear-gradient(90deg, #0a1e2b, #103e5a) !important;">
                <h2 style="color: #35a7b8 !important; margin: 0;">History Records</h2>
              </div>
              
              <div class="panel-body" style="padding: 0; background: transparent !important;">
                <div class="table-container">
                  <table class="registry-table">
                    <thead>
                      <tr>
                        <th style="width: 180px;">TIMESTAMP</th>
                        <th style="width: 200px;">USER ACCOUNT</th>
                        <th>ACTION PERFORMED</th>
                        <th>DATA TABLE</th>
                        <th style="width: 100px;">RECORD ID</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (count($logs) > 0): ?>
                        <?php foreach ($logs as $log): 
                          $actionClass = 'action-create';
                          $actionLabel = $log['action'];
                          if (strpos(strtolower($log['action']), 'update') !== false) $actionClass = 'action-update';
                          if (strpos(strtolower($log['action']), 'delete') !== false || strpos(strtolower($log['action']), 'archive') !== false) $actionClass = 'action-delete';
                        ?>
                          <tr>
                            <td style="color: rgba(255,255,255,0.7); font-size: 0.85rem; font-weight: 600;">
                              <?php echo date('M d, Y | H:i:s', strtotime($log['timestamp'])); ?>
                            </td>
                            <td>
                              <div class="user-info">
                                <span class="name"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></span>
                                <span class="email"><?php echo htmlspecialchars($log['role'] ?? 'user'); ?></span>
                              </div>
                            </td>
                            <td>
                              <span class="action-badge <?php echo $actionClass; ?>">
                                <?php echo htmlspecialchars(str_replace('_', ' ', strtoupper($actionLabel))); ?>
                              </span>
                            </td>
                            <td style="font-family: monospace; color: #35a7b8; font-weight: 600;"><?php echo htmlspecialchars($log['table_name'] ?? '-'); ?></td>
                            <td style="font-weight: 800; color: #ffffff;"><?php echo $log['record_id'] ?: '-'; ?></td>
                          </tr>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="5" class="text-center" style="padding: 100px 20px; color: rgba(255,255,255,0.3);">
                            No activity records found matching your search criteria.
                          </td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                  <?php 
                    $qs = "&search=" . urlencode($search) . "&action_filter=" . urlencode($actionFilter);
                  ?>
                  <div class="pagination" style="padding: 30px; display: flex; justify-content: center; gap: 15px; background: rgba(0,0,0,0.2);">
                    <?php if ($page > 1): ?>
                      <a href="activity_logs.php?page=1<?php echo $qs; ?>" class="btn secondary sm">First</a>
                      <a href="activity_logs.php?page=<?php echo $page - 1; ?><?php echo $qs; ?>" class="btn secondary sm">Prev</a>
                    <?php endif; ?>

                    <div style="display: flex; align-items: center; gap: 5px; color: #ffffff; font-weight: 700; font-size: 0.9rem;">
                      Page <span style="color: #35a7b8;"><?php echo $page; ?></span> of <?php echo $totalPages; ?>
                    </div>

                    <?php if ($page < $totalPages): ?>
                      <a href="activity_logs.php?page=<?php echo $page + 1; ?><?php echo $qs; ?>" class="btn secondary sm">Next</a>
                      <a href="activity_logs.php?page=<?php echo $totalPages; ?><?php echo $qs; ?>" class="btn secondary sm">Last</a>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            </article>
          </section>
        </div>
      </main>
    </div>
  </section>

  <script>
    function goBack() { window.location.href = 'admin.php'; }
  </script>
</body>
</html>
