<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

if (isAdmin()) {
    redirect('admin.php');
}

ensureArchiveColumns();

// Get fisherfolk details for JavaScript initialization
$stmt = $pdo->prepare("
        SELECT f.*, u.username, b.barangay_name, b.municipality, b.province,
          ic.id_number, ic.photo_path, ic.signature_path, ic.issue_date, ic.expiry_date, ic.status as id_status
    FROM fisherfolk f
    LEFT JOIN users u ON f.user_id = u.user_id
    LEFT JOIN barangay b ON f.barangay_id = b.barangay_id
    LEFT JOIN id_cards ic ON f.fisherfolk_id = ic.fisherfolk_id
  WHERE f.user_id = ? AND f.archived_at IS NULL
");
$stmt->execute([$_SESSION['user_id']]);
$fisherfolk = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize user session for JavaScript
$defaultIdDesign = ['themeKey' => 'coastal', 'title' => 'Municipal Fisherfolk ID', 'subtitle' => 'Official identity card for registered fisherfolk', 'badge' => 'Verified Fisherfolk', 'accentColor' => ''];
$normalizeIdDesign = function($raw) use ($defaultIdDesign) {
  $decoded = json_decode((string)$raw, true);
  return is_array($decoded) ? array_merge($defaultIdDesign, $decoded) : $defaultIdDesign;
};

$userData = json_encode([
  'role' => 'user',
  'id' => $fisherfolk['fisherfolk_id'] ?? '',
    'username' => $fisherfolk['username'] ?? $_SESSION['username'],
    'firstName' => $fisherfolk['first_name'] ?? '',
  'middleName' => $fisherfolk['middle_name'] ?? '',
    'lastName' => $fisherfolk['last_name'] ?? '',
    'barangay' => $fisherfolk['barangay_name'] ?? '',
  'livelihood' => $fisherfolk['livelihood'] ?? '',
    'status' => $fisherfolk['status'] ?? 'Pending',
    'idNumber' => $fisherfolk['id_number'] ?? '',
    'validity' => $fisherfolk['expiry_date'] ?? '',
  'birthDate' => $fisherfolk['birthdate'] ?? '',
  'fishrNumber' => $fisherfolk['fishr_number'] ?? '',
  'rsbsaNumber' => $fisherfolk['rsbsa_number'] ?? '',
  'emergencyName' => $fisherfolk['emergency_name'] ?? '',
  'emergencyRelation' => $fisherfolk['emergency_relation'] ?? '',
  'emergencyAddress' => $fisherfolk['emergency_address'] ?? '',
  'emergencyContact' => $fisherfolk['emergency_contact'] ?? '',
    'photo' => $fisherfolk['photo_path'] ?? '',
    'contact' => $fisherfolk['contact_number'] ?? ''
]);

$recordsData = json_encode($fisherfolk ? [[
  'id' => (string)$fisherfolk['fisherfolk_id'],
  'username' => $fisherfolk['username'] ?? $_SESSION['username'],
  'firstName' => $fisherfolk['first_name'] ?? '',
  'middleName' => $fisherfolk['middle_name'] ?? '',
  'lastName' => $fisherfolk['last_name'] ?? '',
  'barangay' => $fisherfolk['barangay_name'] ?? '',
  'livelihood' => $fisherfolk['livelihood'] ?? '',
  'contact' => $fisherfolk['contact_number'] ?? '',
  'status' => $fisherfolk['status'] ?? 'Pending',
  'validity' => !empty($fisherfolk['expiry_date']) ? date('m/Y', strtotime($fisherfolk['expiry_date'])) : 'N/A',
  'gender' => $fisherfolk['gender'] ?? 'Not specified',
  'birthDate' => $fisherfolk['birthdate'] ?? '',
  'fishrNumber' => $fisherfolk['fishr_number'] ?? '',
  'rsbsaNumber' => $fisherfolk['rsbsa_number'] ?? '',
  'emergencyName' => $fisherfolk['emergency_name'] ?? '',
  'emergencyRelation' => $fisherfolk['emergency_relation'] ?? '',
  'emergencyAddress' => $fisherfolk['emergency_address'] ?? '',
  'emergencyContact' => $fisherfolk['emergency_contact'] ?? '',
  'photo' => $fisherfolk['photo_path'] ?? '',
  'idNumber' => $fisherfolk['id_number'] ?? '',
  'photoData' => '',
  'signatureData' => '',
  'idDesign' => $normalizeIdDesign($fisherfolk['id_design'] ?? '')
]] : []);

// Use a shared asset version so dashboard.php always loads the latest CSS/JS
$assetFiles = ['styles.css', 'gui-override.css', 'app.js'];
$assetVersion = 0;
foreach ($assetFiles as $af) {
  $path = __DIR__ . DIRECTORY_SEPARATOR . $af;
  if (file_exists($path)) {
    $assetVersion = max($assetVersion, filemtime($path));
  }
}
if (!$assetVersion) {
  $assetVersion = time();
}

// Get announcements for this user
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

// Get requests for this user
$requests = [];
try {
    $fisherfolkId = $fisherfolk['fisherfolk_id'] ?? 0;
    if ($fisherfolkId) {
        $stmt = $pdo->prepare("SELECT * FROM requests WHERE fisherfolk_id = ? ORDER BY date_submitted DESC");
        $stmt->execute([$fisherfolkId]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) { $requests = []; }
$requestsData = json_encode(array_map(function($r) {
    return [
        'id' => $r['request_id'],
        'userId' => $r['fisherfolk_id'],
        'type' => $r['request_type'],
        'request_type' => $r['request_type'],
        'subject' => $r['subject'] ?? '',
        'message' => $r['description'] ?? '',
        'status' => $r['status'] ?? 'Pending',
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Fisherfolk Information Management System - Dashboard</title>
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
          <div id="logoText">Fisherfolk IMS<span class="system-tag">User</span></div>
        </div>
        <nav class="nav-list" id="navList">
          <!-- Navigation items will be populated by JavaScript -->
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

        <section class="content-grid">
          <!-- Dashboard Hero -->
          <article class="panel" id="dashboardHero">
            <div class="panel-header">
              <h2 id="sectionTitle">My Dashboard</h2>
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

          <!-- Primary Panel -->
          <article class="panel" id="primaryPanel">
            <div class="panel-header">
              <h2>My Profile</h2>
            </div>
            <div id="profileView">
              <!-- Profile content will be populated by JavaScript -->
            </div>
          </article>

          <!-- Secondary Panel -->
          <article class="panel" id="secondaryPanel">
            <div class="panel-header">
              <h2>My ID Details</h2>
            </div>
            <div id="idDetailsView">
              <!-- ID details will be populated by JavaScript -->
            </div>
            <div class="id-preview hidden" id="idPreview">
              <h3>My ID Card</h3>
              <div class="id-card-print-wrap" id="idCardPrintWrap">
                <!-- ID card will be populated by JavaScript -->
              </div>
              <div class="button-row">
                <button class="btn primary" id="printIdBtn">🖨️ Print My ID</button>
                <button class="btn secondary" onclick="downloadId()">📥 Download</button>
              </div>
            </div>
          </article>
        </section>
      </main>
    </div>
  </section>

  <!-- Logout Modal -->
  <div class="modal-overlay hidden" id="logoutModal">
    <div class="modal-card">
      <h3>Confirm Logout</h3>
      <p>Are you sure you want to log out?</p>
      <div class="button-row">
        <button class="btn secondary" onclick="closeLogoutModal()">Cancel</button>
        <button class="btn primary" onclick="logout()">Logout</button>
      </div>
    </div>
  </div>

  <!-- Success Modal -->
  <div class="modal-overlay hidden" id="registrationSuccessModal" aria-hidden="true">
    <div class="modal-card success-modal-card" role="dialog" aria-modal="true" aria-labelledby="registrationSuccessTitle">
      <div class="success-modal-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
          <path d="M6 12.5l4.1 4.1L18.5 8.2" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
      </div>
      <h3 id="registrationSuccessTitle">Success</h3>
      <p id="registrationSuccessMessage">Action completed successfully.</p>
      <div class="button-row modal-actions success-modal-actions">
        <button class="btn primary" type="button" id="closeRegistrationSuccessModal">Okay</button>
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

  <script>
    // Initialize user data from PHP session
    window.phpUserData = <?php echo $userData; ?>;
    window.phpRecordsData = <?php echo $recordsData; ?>;
    window.phpRequestsData = <?php echo $requestsData; ?>;
    window.phpAnnouncementsData = <?php echo $announcementsData; ?>;
  </script>
  <script src="app.js?v=<?php echo $assetVersion; ?>"></script>
  <script>
    // When Announcements link is clicked in the dashboard nav, clear its badge and mark read
    (function(){
      const navList = document.getElementById('navList');
      if (!navList) return;
      navList.addEventListener('click', function(e){
        const a = e.target.closest('a.nav-item');
        if (!a) return;
        const view = a.getAttribute('data-view') || (a.getAttribute('href') || '');
        if ((view && view.indexOf('announcements') !== -1) || (a.textContent || '').toLowerCase().includes('announcements')) {
          const badge = a.querySelector('.nav-badge');
          if (badge) badge.remove();
          const sideBadge = document.querySelector('.notification-badge');
          if (sideBadge) sideBadge.remove();
          try { fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ action: 'mark_announcements_read' }) }).catch(()=>{}); } catch(e){}
        }
      }, false);
    })();
  </script>
</body>
</html>
