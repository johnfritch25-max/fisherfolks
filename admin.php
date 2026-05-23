<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Handle admin credential update via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'update_credentials') {
    header('Content-Type: application/json');
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $newEmail = isset($input['email']) ? trim($input['email']) : '';
        $newPassword = isset($input['password']) ? trim($input['password']) : '';
        
        // Validate email
        if (empty($newEmail)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Email is required']);
            exit;
        }
        
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Invalid email format']);
            exit;
        }
        
        // Validate password (minimum 8 characters)
        if (empty($newPassword) || strlen($newPassword) < 8) {
          http_response_code(400);
          echo json_encode(['ok' => false, 'message' => 'Password must be at least 8 characters']);
          exit;
        }
        
        // Derive a new username from the email so the old admin login handle stops working.
        $newUsername = $newEmail;
        if (strlen($newUsername) > 50) {
          $newUsername = substr($newEmail, 0, 40) . '_' . substr(md5($newEmail), 0, 8);
        }

        // Hash the password
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        
        // Update the admin user
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE user_id = ? AND role = 'admin'");
        $stmt->execute([$newUsername, $newEmail, $hashedPassword, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            // Log the activity
            $logStmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, table_name, record_id, timestamp)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $logStmt->execute([
                $_SESSION['user_id'],
                'Updated admin credentials',
                'users',
                $_SESSION['user_id']
            ]);

            $_SESSION['username'] = $newUsername;
            
            http_response_code(200);
            echo json_encode(['ok' => true, 'message' => 'Admin credentials updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Failed to update credentials']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

ensureArchiveColumns();

// Get all fisherfolk data for JavaScript initialization
$allFisherfolk = [];
try {
    $stmt = $pdo->query("
            SELECT f.*, u.username, b.barangay_name, b.municipality, b.province,
              ic.id_number, ic.photo_path, ic.signature_path, ic.issue_date, ic.expiry_date, ic.status as id_status
        FROM fisherfolk f
        LEFT JOIN users u ON f.user_id = u.user_id
        LEFT JOIN barangay b ON f.barangay_id = b.barangay_id
        LEFT JOIN id_cards ic ON f.fisherfolk_id = ic.fisherfolk_id
      WHERE f.archived_at IS NULL
        ORDER BY f.created_at DESC
    ");
    $allFisherfolk = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    try { $stmt = $pdo->query("SELECT * FROM fisherfolk WHERE archived_at IS NULL ORDER BY created_at DESC"); $allFisherfolk = $stmt->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e2) { $allFisherfolk = []; }
}

$archivedFisherfolk = [];
try {
    $stmt = $pdo->query("
            SELECT f.*, u.username, b.barangay_name, b.municipality, b.province,
              ic.id_number, ic.photo_path, ic.signature_path, ic.issue_date, ic.expiry_date, ic.status as id_status
        FROM fisherfolk f
        LEFT JOIN users u ON f.user_id = u.user_id
        LEFT JOIN barangay b ON f.barangay_id = b.barangay_id
        LEFT JOIN id_cards ic ON f.fisherfolk_id = ic.fisherfolk_id
        WHERE f.archived_at IS NOT NULL
        ORDER BY f.archived_at DESC, f.created_at DESC
    ");
    $archivedFisherfolk = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $archivedFisherfolk = [];
}

// Convert to JavaScript format
$defaultIdDesign = ['themeKey' => 'coastal', 'title' => 'Municipal Fisherfolk ID', 'subtitle' => 'Official identity card for registered fisherfolk', 'badge' => 'Verified Fisherfolk', 'accentColor' => ''];
$normalizeIdDesign = function($raw) use ($defaultIdDesign) {
  $decoded = json_decode((string)$raw, true);
  return is_array($decoded) ? array_merge($defaultIdDesign, $decoded) : $defaultIdDesign;
};

$recordsData = json_encode(array_map(function($f) {
  global $normalizeIdDesign, $defaultIdDesign;
    return [
        'id' => $f['fisherfolk_id'],
        'username' => $f['username'],
        'firstName' => $f['first_name'],
        'lastName' => $f['last_name'],
        'barangay' => $f['barangay_name'],
        'livelihood' => $f['livelihood'],
        'contact' => $f['contact_number'],
        'status' => $f['status'],
        'validity' => $f['expiry_date'] ? date('m/Y', strtotime($f['expiry_date'])) : 'N/A',
        'gender' => $f['gender'] ?? 'Not specified',
        'birthDate' => $f['birthdate'] ?? '',
        'middleName' => $f['middle_name'] ?? '',
        'fishrNumber' => $f['fishr_number'] ?? '',
        'rsbsaNumber' => $f['rsbsa_number'] ?? '',
        'emergencyName' => $f['emergency_name'] ?? '',
        'emergencyRelation' => $f['emergency_relation'] ?? '',
        'emergencyAddress' => $f['emergency_address'] ?? '',
        'emergencyContact' => $f['emergency_contact'] ?? '',
        'photo' => $f['photo_path'] ?? 'Photo',
        'idNumber' => $f['id_number'] ?? '',
        'photoData' => '',
        'signatureData' => $f['signature_path'] ?? '',
        'idDesign' => $normalizeIdDesign($f['id_design'] ?? '')
    ];
}, $allFisherfolk));

    $archivedRecordsData = json_encode(array_map(function($f) {
      global $normalizeIdDesign, $defaultIdDesign;
      return [
        'id' => $f['fisherfolk_id'],
        'username' => $f['username'],
        'firstName' => $f['first_name'],
        'lastName' => $f['last_name'],
        'barangay' => $f['barangay_name'],
        'livelihood' => $f['livelihood'],
        'contact' => $f['contact_number'],
        'status' => $f['status'],
        'validity' => $f['expiry_date'] ? date('m/Y', strtotime($f['expiry_date'])) : 'N/A',
        'gender' => $f['gender'] ?? 'Not specified',
        'birthDate' => $f['birthdate'] ?? '',
        'middleName' => $f['middle_name'] ?? '',
        'fishrNumber' => $f['fishr_number'] ?? '',
        'rsbsaNumber' => $f['rsbsa_number'] ?? '',
        'emergencyName' => $f['emergency_name'] ?? '',
        'emergencyRelation' => $f['emergency_relation'] ?? '',
        'emergencyAddress' => $f['emergency_address'] ?? '',
        'emergencyContact' => $f['emergency_contact'] ?? '',
        'photo' => $f['photo_path'] ?? 'Photo',
        'idNumber' => $f['id_number'] ?? '',
        'photoData' => '',
        'signatureData' => $f['signature_path'] ?? '',
        'idDesign' => $normalizeIdDesign($f['id_design'] ?? ''),
        'archivedAt' => $f['archived_at'] ?? '',
        'archivedBy' => $f['archived_by'] ?? '',
        'archiveReason' => $f['archive_reason'] ?? ''
      ];
    }, $archivedFisherfolk));

// Get announcements
$announcements = [];
try {
    $stmt = $pdo->query("SELECT * FROM announcements ORDER BY date_posted DESC");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $announcements = []; }
  $announcementsData = json_encode(array_map(function($a) {
  return [
    'id' => 'ANN-' . date('Ymd', strtotime($a['date_posted'])) . '-' . rand(100, 999),
    'title' => $a['title'],
    'message' => $a['message'],
    'createdAt' => $a['date_posted']
  ];
}, $announcements));

// Get requests (Simpler query to avoid join issues)
$requests = [];
$_debugRequestsError = '';
try {
    $stmt = $pdo->query("SELECT * FROM requests ORDER BY date_submitted DESC");
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_debugRequestsError = $e->getMessage();
    // Try without ORDER BY in case column name is different
    try {
        $stmt = $pdo->query("SELECT * FROM requests");
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        $_debugRequestsError .= ' | Fallback also failed: ' . $e2->getMessage();
        $requests = [];
    }
}

// Also get all fisherfolk names for mapping later if needed
$stmtF = $pdo->query("SELECT fisherfolk_id, first_name, last_name FROM fisherfolk");
$fNames = $stmtF ? $stmtF->fetchAll(PDO::FETCH_ASSOC) : [];
$nameMap = [];
foreach($fNames as $fn) { $nameMap[$fn['fisherfolk_id']] = $fn['first_name'] . ' ' . $fn['last_name']; }

$requestsData = json_encode(array_map(function($r) use ($nameMap) {
    return [
        'id' => $r['request_id'],
        'userId' => $r['fisherfolk_id'],
        'fisherfolk_id' => $r['fisherfolk_id'],
        'fisherfolkName' => $nameMap[$r['fisherfolk_id']] ?? 'Unknown',
        'type' => $r['request_type'],
        'request_type' => $r['request_type'],
        'subject' => $r['subject'],
        'message' => $r['description'],
        'status' => $r['status'],
        'adminNote' => $r['admin_notes'] ?? '',
        'createdAt' => $r['date_submitted'],
        'boatName' => $r['boat_name'] ?? '',
        'boatType' => $r['boat_type'] ?? '',
        'boatColor' => $r['boat_color'] ?? '',
        'boatSize' => $r['boat_size'] ?? '',
        'incidentDate' => $r['incident_date'] ?? '',
        'damageCost' => $r['estimated_damage'] ?? ''
    ];
}, $requests));

// Get reported posts (wrapped in try/catch to prevent page crash if tables don't exist)
$reports = [];
try {
    $stmt = $pdo->query("
        SELECT r.*, p.content, p.image_path, u.username as reporter_name, au.username as author_name
        FROM post_reports r
        JOIN posts p ON r.post_id = p.post_id
        JOIN users u ON r.reporter_id = u.user_id
        JOIN users au ON p.user_id = au.user_id
        ORDER BY r.created_at DESC
    ");
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // post_reports or posts table may not exist yet — continue safely
    $reports = [];
}
$reportsData = json_encode($reports);
 
// Get pending requests count for sidebar badge
$pendingRequestsCount = 0;
try {
    // First ensure the is_read column exists
    $pdo->exec("ALTER TABLE requests ADD COLUMN IF NOT EXISTS is_read TINYINT(1) DEFAULT 0");
    $stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE request_type LIKE '%Subsidy%' AND is_read = 0");
    $pendingRequestsCount = $stmt->fetchColumn();
} catch (Exception $e) {
    // Fallback: count all pending subsidy requests
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Pending'");
        $pendingRequestsCount = $stmt->fetchColumn();
    } catch (Exception $e2) {
        $pendingRequestsCount = 0;
    }
}

  $adminData = json_encode([
    'role' => 'admin',
    'username' => $_SESSION['username'] ?? 'admin',
    'name' => $_SESSION['full_name'] ?? 'Administrator',
    'email' => 'admin@lgu.gov.ph'
  ]);
?>

  <?php
  // Generate an asset version based on the newest file modification time so browsers fetch updated files
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Fisherfolk Information Management System - Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Manrope:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="public/tailwind.css" />
  <link rel="stylesheet" href="design-system.css?v=<?php echo $assetVersion; ?>" />
  <link rel="stylesheet" href="styles.css?v=<?php echo $assetVersion; ?>" />
  <link rel="stylesheet" href="gui-override.css?v=<?php echo $assetVersion; ?>" />
  <?php injectThemeStyles(); ?>
  <link rel="stylesheet" href="printStyles.css" media="print" />
</head>
<body>
  <div class="app-shell" id="appShell">
    <button class="sidebar-collapse-btn" id="sidebarCollapseToggle" onclick="toggleSidebarCollapse()" title="Toggle Sidebar">‹</button>
    <div class="canvas">
      <aside class="sidebar" id="mainSidebar">
        <div class="logo-block">
          <img src="logo.jpg" alt="Fisherfolk IMS" class="sidebar-logo" />
          <div id="logoText">Fisherfolk IMS<span class="system-tag">Admin Workspace</span></div>
        </div>
        <nav class="nav-list" id="navList">
          <div class="nav-header">Overview</div>
          <a class="nav-item active" data-view="dashboard" href="admin.php?view=dashboard"><i class="fas fa-house-chimney-window"></i><span>Dashboard</span></a>
          
          <div class="nav-header">Registry & Records</div>
          <a class="nav-item" data-view="register-fisherfolk" data-label="Resident" href="admin.php?view=register-fisherfolk"><i class="fas fa-user-plus"></i><span>Resident</span></a>
          <a class="nav-item" data-view="id-printing-queue" data-label="ID Issuance Queue" href="admin.php?view=id-printing-queue"><i class="fas fa-id-card-clip"></i><span>ID Issuance Queue</span></a>
          <a class="nav-item" data-view="archive-storage" data-label="Record Archives" href="admin.php?view=archive-storage"><i class="fas fa-box-archive"></i><span>Record Archives</span></a>
          
          <div class="nav-header">Analytics & Services</div>
          <a class="nav-item" data-view="subsidy-reports-admin" data-label="Subsidy Management" href="admin.php?view=subsidy-reports-admin">
            <i class="fas fa-hand-holding-dollar"></i><span>Subsidy Management</span>
            <?php if ($pendingRequestsCount > 0): ?>
              <span class="nav-badge" id="subsidyBadge" style="background: #ff416c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: 800; margin-left: auto;"><?php echo $pendingRequestsCount; ?></span>
            <?php endif; ?>
          </a>
          <a class="nav-item" data-view="announcements-admin" data-label="System Broadcasts" href="admin.php?view=announcements-admin"><i class="fas fa-bullhorn"></i><span>System Broadcasts</span></a>
          <a class="nav-item" data-view="reports-analytics" data-label="Master Registry" href="admin.php?view=reports-analytics"><i class="fas fa-database"></i><span>Master Registry</span></a>
          <a class="nav-item" data-view="reported-posts" data-label="Content Moderation" href="admin.php?view=reported-posts"><i class="fas fa-flag-checkered"></i><span>Content Moderation</span></a>
          
          <div class="nav-header">System Config</div>
          <a class="nav-item" data-view="settings-users" data-label="Barangay Configuration" href="admin.php?view=settings-users"><i class="fas fa-gears"></i><span>Barangay Configuration</span></a>
          <a class="nav-item" data-label="Interface Themes" href="theme_settings.php"><i class="fas fa-palette"></i><span>Interface Themes</span></a>
          <a class="nav-item" data-label="System Audit Logs" href="activity_logs.php"><i class="fas fa-file-shield"></i><span>System Audit Logs</span></a>
        </nav>
      </aside>

      <main class="main-panel">
        <header class="topbar">
          <div class="breadcrumbs" id="breadcrumbs">
            <!-- Breadcrumbs will be populated by JavaScript -->
          </div>
          <div class="profile-wrap" id="profileWrap">
            <!-- Profile info will be populated by JavaScript -->
          </div>
        </header>

        <div class="content-scroll-area">
          <section class="content-grid">
            <!-- Dashboard Hero -->
            <article class="panel" id="dashboardHero">
              <div class="panel-header">
                <h2 id="sectionTitle">Fisherfolk Registry</h2>
              </div>

              <!-- Quick Actions -->
              <div class="quick-actions" id="quickActions">
                <!-- Quick action buttons will be populated by JavaScript -->
              </div>

              <!-- KPI Grid -->
              <div class="kpi-grid" id="kpiGrid">
                <!-- KPI cards will be populated by JavaScript -->
              </div>
            </article>

            <!-- Primary Panel (Registry Table) -->
            <article class="panel" id="primaryPanel">
              <div class="panel-header">
                <h2>Registry</h2>
                <div class="actions">
                  <input type="text" id="searchInput" placeholder="Search..." />
                  <select id="statusFilter">
                    <option value="all">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Pending">Pending</option>
                    <option value="Expired">Expired</option>
                  </select>
                  <select id="barangayFilter">
                    <option value="all">All Barangays</option>
                  </select>
                </div>
              </div>
              <div class="table-container">
                <table class="registry-table">
                  <thead>
                    <tr>
                      <th>ID No.</th>
                      <th>Name</th>
                      <th>Barangay</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody id="registryTableBody">
                    <!-- Table rows will be populated by JavaScript -->
                  </tbody>
                </table>
              </div>
            </article>

            <!-- Secondary Panel (Details) -->
            <article class="panel" id="secondaryPanel">
              <div class="panel-header">
                <h2>Fisherfolk Details</h2>
              </div>
              <div id="detailsView">
                <p>Select a fisherfolk from the registry to view details.</p>
              </div>
              <div class="id-preview hidden" id="idPreview">
                <h3>ID Card Preview</h3>
                <div class="id-card-print-wrap" id="idCardPrintWrap">
                  <!-- ID card will be populated by JavaScript -->
                </div>
                <div class="button-row">
                  <button class="btn primary" id="printIdBtn">🖨️ Print ID Card</button>
                  <button class="btn secondary" onclick="downloadId()">📥 Download</button>
                </div>
              </div>
            </article>
          </section>
        </div>
      </main>
    </div>
  </section>

    <!-- Logout Modal -->
    <div class="modal-overlay hidden" id="logoutModal">
      <div class="modal-card">
        <h3>Confirm Logout</h3>
        <p>Are you sure you want to log out of the admin panel?</p>
        <div class="button-row">
          <button class="btn secondary" onclick="closeLogoutModal()">Cancel</button>
          <button class="btn primary" onclick="logout()">Logout</button>
        </div>
      </div>
    </div>

    <!-- Add Barangay Modal -->
    <div class="modal-overlay hidden" id="addBarangayModal">
      <div class="modal-card" style="background: linear-gradient(180deg, #0a56a2 0%, #0a1e2b 100%) !important; color: #ffffff !important;">
        <h3>Add New Barangay</h3>
        <p style="margin-bottom: 20px;">Enter the name of the new barangay to add to the system.</p>
        <form id="modalBarangayForm">
          <div class="field-group">
            <input class="input-field" id="modalNewBarangay" name="newBarangay" placeholder="Enter barangay name" required />
          </div>
          <div class="button-row" style="margin-top: 20px;">
            <button class="btn secondary" type="button" onclick="closeAddBarangayModal()">Cancel</button>
            <button class="btn primary" type="submit">Add Barangay</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Master Detail Modal -->
  <div class="modal-overlay hidden" id="masterDetailModal">
    <div class="modal-card master-detail-modal-card">
      <div class="master-detail-modal-head">
        <h3>Master Detail</h3>
        <button class="btn" type="button" id="closeMasterDetailModal">Close</button>
      </div>
      <div id="masterDetailModalBody"></div>
    </div>
  </div>

  <!-- Registration Success Modal -->
  <div class="modal-overlay hidden" id="registrationSuccessModal">
    <div class="modal-card">
      <h3 id="successModalTitle">Success</h3>
      <p id="successModalMessage">Action completed successfully.</p>
      <div class="button-row">
        <button class="btn primary" id="closeRegistrationSuccessModal" type="button">Close</button>
      </div>
    </div>
  </div>

  <!-- Alert Modal -->
  <div class="modal-overlay hidden" id="alertModal" aria-hidden="true">
    <div class="modal-card alert-modal-card" role="dialog" aria-modal="true" aria-labelledby="alertModalTitle">
      <div class="success-modal-icon" aria-hidden="true" style="color: #ff6b6b;">
        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
          <path d="M12 8v5" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" />
          <path d="M12 16.8h.01" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" />
          <path d="M10.3 4.8l-7.1 12.3A2 2 0 0 0 4.9 20h14.2a2 2 0 0 0 1.7-2.9L13.7 4.8a2 2 0 0 0-3.4 0Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
        </svg>
      </div>
      <h3 id="alertModalTitle">Notice</h3>
      <p id="alertModalMessage">Action could not be completed.</p>
      <div class="button-row modal-actions success-modal-actions">
        <button class="btn primary" type="button" id="closeAlertModal">Okay</button>
      </div>
    </div>
  </div>

  <!-- Bulk Print Confirmation Modal -->
  <div class="modal-overlay hidden" id="bulkPrintConfirmModal">
    <div class="modal-card">
      <h3>Confirm Bulk Printing</h3>
      <p id="bulkPrintConfirmMessage">Are you sure you want to print the selected IDs?</p>
      <div class="button-row">
        <button class="btn primary" id="confirmBulkPrintButton" type="button">Yes, Print All</button>
        <button class="btn" id="cancelBulkPrintButton" type="button">Cancel</button>
      </div>
    </div>
  </div>

  <!-- Generic Action Confirmation Modal -->
  <div class="modal-overlay hidden" id="actionConfirmModal">
    <div class="modal-card" style="background: linear-gradient(145deg, #0d1f2d, #051421); border: 2px solid var(--secondary-teal); box-shadow: 0 0 30px rgba(10, 240, 255, 0.2);">
      <h3 id="actionConfirmTitle" style="color: #0af0ff; font-family: 'Fraunces', serif;">Confirm Action</h3>
      <p id="actionConfirmMessage" style="margin: 20px 0; line-height: 1.6; opacity: 0.9;">Are you sure you want to proceed with this action?</p>
      <div class="button-row" style="gap: 12px; margin-top: 30px;">
        <button class="btn secondary" id="actionCancelBtn" type="button" style="flex: 1; padding: 12px; border: 1px solid rgba(255,255,255,0.1);">Cancel</button>
        <button class="btn primary" id="actionConfirmBtn" type="button" style="flex: 1; padding: 12px; background: linear-gradient(135deg, #0af0ff, #00d4ff); color: #051421; font-weight: 800;">Confirm</button>
      </div>
    </div>
  </div>

  <!-- Admin Account Settings Modal -->
  <div class="modal-overlay hidden" id="adminAccountSettingsModal">
    <div class="modal-card" style="background: linear-gradient(180deg, #0a56a2 0%, #0a1e2b 100%) !important; color: #ffffff !important; max-width: 450px;">
      <h3>Admin Account Settings</h3>
      <p style="margin-bottom: 20px; opacity: 0.8;">Update your administrative credentials. Changes will take effect upon saving.</p>
      <form id="modalAdminSettingsForm">
        <div class="field-group" style="margin-bottom: 15px;">
          <label class="field-label" for="modalAdminEmail" style="color: #0af0ff;">Admin Email</label>
          <input class="input-field" id="modalAdminEmail" name="adminEmail" type="email" style="background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(0,212,255,0.3);" required />
        </div>
        <div class="field-group" style="margin-bottom: 20px;">
          <label class="field-label" for="modalAdminPassword" style="color: #0af0ff;">Admin Password</label>
          <input class="input-field" id="modalAdminPassword" name="adminPassword" type="password" style="background: rgba(255,255,255,0.1); color: #fff; border: 1px solid rgba(0,212,255,0.3);" minlength="8" required />
        </div>
        <div class="button-row">
          <button class="btn secondary" type="button" onclick="closeAdminAccountSettingsModal()">Cancel</button>
          <button class="btn primary" type="submit">Save Account Settings</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Barangay Residents Modal -->
  <div class="modal-overlay hidden" id="barangayResidentsModal">
    <div class="modal-card" style="background: linear-gradient(180deg, #0a1e2b 0%, #051421 100%) !important; color: #ffffff !important; max-width: 1100px; width: 95%; padding: 40px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid rgba(0, 212, 255, 0.2); padding-bottom: 20px;">
        <h3 id="barangayResidentsTitle" style="margin: 0; color: #0af0ff; font-size: 1.8rem; font-family: 'Fraunces', serif;">Residents List</h3>
        <button class="btn" type="button" onclick="closeBarangayResidentsModal()" style="background: rgba(255,255,255,0.1); color: #fff; padding: 10px 25px; font-size: 1rem;">Close</button>
      </div>
      <div id="barangayResidentsBody">
        <!-- Resident table will be injected here -->
      </div>
    </div>
  </div>

  <script>
    // Initialize admin data from PHP database
    window.phpUserData = <?php echo $adminData; ?>;
    window.phpRecordsData = <?php echo $recordsData; ?>;
    window.phpArchivedRecordsData = <?php echo $archivedRecordsData; ?>;
    window.phpRequestsData = <?php echo $requestsData; ?>;
    window.phpReportsData = <?php echo $reportsData; ?>;
  </script>

  <script src="app.js?v=<?php echo $assetVersion; ?>"></script>

  <script>
    // When Announcements link is clicked in the admin nav, clear its badge and mark read
    (function(){
      const logoutModal = document.getElementById('logoutModal');
      const addBarangayModal = document.getElementById('addBarangayModal');
      const masterDetailModal = document.getElementById('masterDetailModal');
      const registrationSuccessModal = document.getElementById('registrationSuccessModal');
      const bulkPrintConfirmModal = document.getElementById('bulkPrintConfirmModal');

      if (logoutModal && !logoutModal.classList.contains('hidden')) {
        closeLogoutModal();
      } else if (addBarangayModal && !addBarangayModal.classList.contains('hidden')) {
        closeAddBarangayModal();
      } else if (masterDetailModal && !masterDetailModal.classList.contains('hidden')) {
        closeMasterDetailModal();
      } else if (registrationSuccessModal && !registrationSuccessModal.classList.contains('hidden')) {
        closeRegistrationSuccessModal();
      } else if (bulkPrintConfirmModal && !bulkPrintConfirmModal.classList.contains('hidden')) {
        document.getElementById('bulkPrintConfirmModal').classList.add('hidden');
      }

      const navList = document.getElementById('navList');
      if (!navList) return;
        navList.addEventListener('click', function(e){
        const a = e.target.closest('a.nav-item');
        if (!a) return;
        
        // Clear Subsidy Badge and Mark Read in DB
        if (a.getAttribute('data-view') === 'subsidy-reports-admin') {
          const sBadge = a.querySelector('#subsidyBadge');
          if (sBadge) sBadge.remove();
          try { 
            const formData = new FormData();
            formData.append('action', 'mark_requests_read');
            fetch('api.php', { method: 'POST', body: formData }).catch(()=>{}); 
          } catch(e){}
        }

        const view = a.getAttribute('data-view') || (a.getAttribute('href') || '');
        if ((view && view.indexOf('announcements') !== -1) || (a.textContent || '').toLowerCase().includes('announcements')) {
          const badge = a.querySelector('.nav-badge');
          if (badge) badge.remove();
          try { fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'mark_announcements_read' }) }).catch(()=>{}); } catch(e){}
        }
      }, false);
    })();
  </script>

  <!-- Persist registration form data across refresh/navigation using localStorage -->
  <script>
    (function(){
      const storageKey = 'registerFormDraft_v1';

      function saveDraft() {
        try {
          const f = document.getElementById('registerForm');
          if (!f) return;
          const data = {};
          Array.from(f.elements).forEach(function(el){
            if (!el.name) return;
            const t = (el.type || '').toLowerCase();
            if (t === 'file' || t === 'submit' || t === 'button') return;
            if (t === 'checkbox' || t === 'radio') { data[el.name] = el.checked; return; }
            if (el.tagName.toLowerCase() === 'select') { data[el.name] = el.value; return; }
            if (el.tagName.toLowerCase() === 'textarea') { data[el.name] = el.value; return; }
            if (t === 'password') return; // do not persist passwords
            data[el.name] = el.value;
          });
          localStorage.setItem(storageKey, JSON.stringify(data));
        } catch(e){ /* ignore */ }
      }

      function restoreDraft() {
        try {
          const raw = localStorage.getItem(storageKey);
          if (!raw) return;
          const data = JSON.parse(raw);
          const f = document.getElementById('registerForm');
          if (!f) return;
          Object.keys(data).forEach(function(name){
            const el = f.querySelector('[name="' + name + '"]');
            if (!el) return;
            const t = (el.type || '').toLowerCase();
            if (t === 'checkbox' || t === 'radio') { el.checked = !!data[name]; return; }
            if (el.tagName.toLowerCase() === 'select') { el.value = data[name]; return; }
            if (el.tagName.toLowerCase() === 'textarea') { el.value = data[name]; return; }
            if (t === 'password' || t === 'file') return;
            el.value = data[name];
          });
        } catch(e){ /* ignore */ }
      }

      function clearDraft() { try { localStorage.removeItem(storageKey); } catch(e){} }

      function attachListeners() {
        const f = document.getElementById('registerForm');
        if (!f) return;
        // save on input/change
        f.addEventListener('input', saveDraft);
        f.addEventListener('change', saveDraft);
        // clear on successful submit
        f.addEventListener('submit', function(){ clearDraft(); });
      }

      document.addEventListener('DOMContentLoaded', function(){
        restoreDraft();
        attachListeners();
      });

      // If form is injected later, watch and attach
      const mo = new MutationObserver(function(){ if (document.getElementById('registerForm')) { restoreDraft(); attachListeners(); } });
      mo.observe(document.body, { childList: true, subtree: true });
    })();
  </script>

  <!-- Dev test helper: open success modal when ?dev_test_modal=1 is in URL -->
  <script>
    (function(){
      try {
        const params = new URLSearchParams(window.location.search);
        if (params.get('dev_test_modal') === '1') {
          document.addEventListener('DOMContentLoaded', function(){
            // small delay to ensure app.js mounted event handlers
            window.setTimeout(function(){
              if (typeof openSuccessModal === 'function') {
                openSuccessModal('Generate ID successfully', 'The new fisherfolk ID has been created and added to the system.');
              } else {
                console.log('Dev test: openSuccessModal not available yet.');
              }
            }, 300);
          });
        }
      } catch(e) { console.warn('Dev test helper error', e); }
    })();
  </script>
</body>
</html>