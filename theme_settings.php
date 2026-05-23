<?php
require_once 'config.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('login.php');
}

// Get current theme settings
$stmt = $pdo->query("SELECT setting_name, setting_value FROM admin_settings WHERE setting_name LIKE 'theme_%' OR setting_name = 'system_name'");
$currentSettings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $currentSettings[$row['setting_name']] = $row['setting_value'];
}

$defaults = [
    'theme_sidebar_color' => '#2c6aa2',
    'theme_body_color' => '#ffffff',
    'theme_text_color' => '#333333',
    'theme_accent_color' => '#2c6aa2',
    'theme_mode' => 'light',
    'system_name' => 'Fisherfolk Information Management System'
];

$settings = array_merge($defaults, $currentSettings);

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
    <title>Theme Settings - Admin</title>
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
        .wide-layout-container, 
        #themeForm, 
        .settings-card {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        .content-scroll-area {
            padding: 0 !important;
            /* Scroll handled by gui-override.css */
        }

        .wide-layout-container {
            padding: 16px !important;
            display: flex !important;
            flex-direction: column !important;
            gap: 14px !important;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px !important;
            padding: 12px 16px !important;
            width: 100% !important;
            background: rgba(0,0,0,0.15);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .page-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .settings-card {
            background: rgba(13, 39, 56, 0.7) !important;
            border: 1px solid rgba(45, 168, 187, 0.3) !important;
            padding: 16px;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            backdrop-filter: blur(12px);
            margin-bottom: 0;
        }

        .settings-card h3 {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #35a7b8;
            font-weight: 800;
            margin-bottom: 10px;
            letter-spacing: 1.2px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .settings-card p {
            font-size: 0.8rem;
            margin-bottom: 10px;
            opacity: 0.8;
        }

        .form-label {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 600;
            font-size: 0.8rem;
            margin-bottom: 6px;
            display: block;
        }

        .input-field {
            background: rgba(0, 0, 0, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            padding: 8px 12px !important;
            border-radius: 8px !important;
            font-size: 0.85rem !important;
        }

        .input-field:focus {
            border-color: #35a7b8 !important;
            box-shadow: 0 0 0 3px rgba(53, 167, 184, 0.2) !important;
        }

        .color-preview-box {
            height: 50px;
            border-radius: 8px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: inset 0 0 10px rgba(0,0,0,0.15);
        }

        .btn-back {
            background: linear-gradient(135deg, #2c6aa2, #1e4d73) !important;
            color: white !important;
            padding: 8px 16px !important;
            border-radius: 8px !important;
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none !important;
            box-shadow: 0 3px 8px rgba(44, 106, 162, 0.3) !important;
        }

        .save-bar {
            background: rgba(13, 39, 56, 0.9);
            border-top: 1px solid rgba(45, 168, 187, 0.3);
            padding: 12px 16px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-radius: 0 0 10px 10px;
        }

        .btn {
            padding: 10px 20px !important;
            border-radius: 8px !important;
            font-weight: 700 !important;
            font-size: 0.75rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.8px !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            border: none !important;
        }

        .btn.primary {
            background: #35a7b8 !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(53, 167, 184, 0.3) !important;
        }

        .btn.secondary {
            background: rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
        }

        .btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }

        .btn:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .grid-2-col {
                grid-template-columns: 1fr !important;
            }

            .header-actions {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 8px !important;
                padding: 10px 12px !important;
            }

            .btn-back {
                width: 100% !important;
                justify-content: center !important;
            }

            .settings-card {
                padding: 12px !important;
            }

            .wide-layout-container {
                padding: 10px 10px 120px 10px !important;
                gap: 10px !important;
            }

            .save-bar {
                flex-direction: column !important;
                gap: 8px !important;
                padding: 12px !important;
                margin-top: 20px !important;
                position: relative !important;
                z-index: 100 !important;
            }

            .save-bar .btn {
                width: 100% !important;
                padding: 14px 16px !important;
                font-size: 0.85rem !important;
                min-height: 44px !important;
            }

            .page-title {
                font-size: 1rem !important;
            }
        }

        /* Mobile button override for inline styles */
        @media (max-width: 768px) {
            .save-bar {
                display: flex !important;
                flex-direction: column !important;
            }

            .save-bar .btn {
                flex: 1 !important;
                min-width: 100% !important;
            }
        }

        .grid-2-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .loader {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid #fff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        #saveBtn:disabled .loader { display: inline-block; }
    </style>
    <script>
        function updatePreview() {
            const sidebarColor = document.getElementById('sidebarColor')?.value;
            const accentColor = document.getElementById('accentColor')?.value;
            const previewSidebar = document.getElementById('previewSidebar');
            const previewAccent = document.getElementById('previewAccent');
            const sidebarColorText = document.getElementById('sidebarColorText');
            const accentColorText = document.getElementById('accentColorText');

            if(previewSidebar) previewSidebar.style.background = sidebarColor;
            if(previewAccent) previewAccent.style.background = accentColor;
            if(sidebarColorText) sidebarColorText.value = sidebarColor;
            if(accentColorText) accentColorText.value = accentColor;
        }

        function resetColors() {
            console.log("RESTORE CLICKED");
            if (!confirm('Revert all theme settings to system defaults?')) return;
            
            const defaultData = {
                action: 'update_theme_settings',
                theme_sidebar_color: '#2c6aa2',
                theme_accent_color: '#2c6aa2',
                theme_mode: 'light',
                system_name: 'Fisherfolk Information Management System'
            };

            const saveBtn = document.getElementById('saveBtn');
            if(saveBtn) saveBtn.disabled = true;

            console.log("SENDING RESTORE REQUEST...");
            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(defaultData)
            })
            .then(res => res.json())
            .then(data => {
                console.log("RESTORE RESPONSE:", data);
                if (data.success) {
                    openSuccessModal('✓ System defaults restored successfully!');
                    // Auto-refresh page after 2.5 seconds to apply changes
                    setTimeout(() => {
                        window.location.reload();
                    }, 2500);
                } else {
                    alert('✗ Restore failed: ' + (data.error || 'Unknown error'));
                    if(saveBtn) saveBtn.disabled = false;
                }
            })
            .catch(err => {
                console.error("RESTORE ERROR:", err);
                alert('✗ Connection error');
                if(saveBtn) saveBtn.disabled = false;
            });
        }

        function saveThemeSettings(event) {
            if(event) event.preventDefault();
            const saveBtn = document.getElementById('saveBtn');
            if(saveBtn) saveBtn.disabled = true;

            const themeData = {
                action: 'update_theme_settings',
                theme_sidebar_color: document.getElementById('sidebarColor').value,
                theme_accent_color: document.getElementById('accentColor').value,
                theme_mode: document.getElementById('themeMode').value,
                system_name: document.getElementById('systemName').value
            };

            fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(themeData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    openSuccessModal('✓ System appearance updated successfully!');
                    // Auto-refresh page after 2.5 seconds to apply changes
                    setTimeout(() => {
                        window.location.reload();
                    }, 2500);
                } else {
                    alert('✗ Update failed');
                    if(saveBtn) saveBtn.disabled = false;
                }
            })
            .catch(() => alert('✗ Connection error'))
            .finally(() => { if(saveBtn) saveBtn.disabled = false; });
        }

        function openSuccessModal(message) {
            const modal = document.getElementById('themeSuccessModal');
            const msgElem = document.getElementById('themeSuccessMessage');
            if(msgElem) msgElem.textContent = message;
            if(modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
            }
        }

        function selectMode(mode, event) {
            if(event) event.preventDefault();
            const themeModeInput = document.getElementById('themeMode');
            if(themeModeInput) themeModeInput.value = mode;
            document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
            if(event) event.currentTarget.classList.add('active');
        }

        function updateColorFromText(type, event) {
            const value = event.target.value;
            if (/^#[0-9A-F]{6}$/i.test(value)) {
                if (type === 'sidebar') document.getElementById('sidebarColor').value = value;
                else if (type === 'accent') document.getElementById('accentColor').value = value;
                updatePreview();
            }
        }

        function goBack() { window.location.href = 'admin.php'; }
        window.addEventListener('DOMContentLoaded', updatePreview);
    </script>
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
          <a class="nav-item active" href="theme_settings.php"><i class="fas fa-palette"></i><span>Interface Themes</span></a>
          <a class="nav-item" href="activity_logs.php"><i class="fas fa-file-shield"></i><span>System Audit Logs</span></a>
        </nav>
      </aside>

      <main class="main-panel">
        <header class="topbar">
          <div class="breadcrumbs">
            <span class="crumb">Admin Workspace</span>
            <span class="sep">/</span>
            <span class="crumb current">Interface Themes</span>
          </div>
          <div class="profile-wrap" id="profileWrap">
            <div class="profile-info">
              <span class="profile-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Administrator'); ?></span>
              <span class="profile-role">Admin</span>
            </div>
          </div>
        </header>

        <div class="content-scroll-area">
            <div class="header-actions">
              <h1 class="page-title">Theme Customization</h1>
              <button class="btn-back" onclick="goBack()">
                <span>← Back to Dashboard</span>
              </button>
            </div>

            <section class="wide-layout-container">
              <form id="themeForm" onsubmit="saveThemeSettings(event)" style="width: 100%; display: flex; flex-direction: column; gap: 14px;">
                <div id="alertBox"></div>

                <!-- System Config -->
                <div class="settings-card" style="width: 100%; margin: 0;">
                  <h3>⚙️ System Configuration</h3>
                  <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="systemName">System Display Name</label>
                    <input type="text" id="systemName" class="input-field" value="<?php echo htmlspecialchars($settings['system_name']); ?>" placeholder="e.g., Fisherfolk IMS" style="width: 100%;">
                  </div>
                  <input type="hidden" id="themeMode" value="<?php echo htmlspecialchars($settings['theme_mode']); ?>">
                </div>

                <!-- Sidebar Color -->
                <div class="settings-card" style="width: 100%; margin: 0;">
                  <h3>🎯 Sidebar Aesthetic</h3>
                  <p style="font-size: 0.8rem; color: rgba(255,255,255,0.6); margin-bottom: 12px;">Defines the mood and brand identity of the main navigation sidebar.</p>
                  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start;">
                    <div>
                      <label class="form-label">Sidebar Color</label>
                      <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="color" id="sidebarColor" value="<?php echo htmlspecialchars($settings['theme_sidebar_color']); ?>" oninput="updatePreview()" style="width: 40px; height: 36px; cursor: pointer; background: none; border: none; padding: 0;">
                        <input type="text" id="sidebarColorText" class="input-field" value="<?php echo htmlspecialchars($settings['theme_sidebar_color']); ?>" oninput="updateColorFromText('sidebar', event)" style="flex: 1; font-family: monospace; font-size: 0.8rem;">
                      </div>
                    </div>
                    <div>
                      <label class="form-label">Live Preview</label>
                      <div class="color-preview-box" id="previewSidebar" style="background: <?php echo htmlspecialchars($settings['theme_sidebar_color']); ?>; color: #fff;">
                        SIDEBAR PREVIEW
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Accent Color -->
                <div class="settings-card" style="width: 100%; margin: 0;">
                  <h3>⚡ Brand Accent</h3>
                  <p style="font-size: 0.8rem; color: rgba(255,255,255,0.6); margin-bottom: 12px;">Primary color for buttons, active states, and focus elements throughout the system.</p>
                  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; align-items: start;">
                    <div>
                      <label class="form-label">Accent Color</label>
                      <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="color" id="accentColor" value="<?php echo htmlspecialchars($settings['theme_accent_color']); ?>" oninput="updatePreview()" style="width: 40px; height: 36px; cursor: pointer; background: none; border: none; padding: 0;">
                        <input type="text" id="accentColorText" class="input-field" value="<?php echo htmlspecialchars($settings['theme_accent_color']); ?>" oninput="updateColorFromText('accent', event)" style="flex: 1; font-family: monospace; font-size: 0.8rem;">
                      </div>
                    </div>
                    <div>
                      <label class="form-label">Live Preview</label>
                      <div class="color-preview-box" id="previewAccent" style="background: <?php echo htmlspecialchars($settings['theme_accent_color']); ?>; color: #fff;">
                        ACCENT PREVIEW
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Save Actions -->
                <div class="save-bar" style="margin-top: 10px; background: rgba(0,0,0,0.2); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); display: flex !important; justify-content: flex-end !important; gap: 15px !important;">
                  <button type="button" class="btn secondary sm" onclick="resetColors()" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); padding: 10px 20px !important; font-size: 0.8rem; flex: 1;">RESTORE DEFAULTS</button>
                  <button type="submit" class="btn primary" id="saveBtn" style="min-width: 200px; display: flex; align-items: center; justify-content: center; gap: 10px; font-size: 0.85rem; flex: 1.5;">
                    <div class="loader"></div>
                    <span>APPLY THEME CHANGES</span>
                  </button>
                </div>
              </form>
            </section>
          </div>
        </main>
    </div>
  </section>

  <!-- Success Modal -->
  <div class="modal-overlay hidden" id="themeSuccessModal" style="z-index: 999999 !important; position: fixed !important; top: 0; left: 0; width: 100%; height: 100%; display: none; align-items: center; justify-content: center; background: rgba(0,0,0,0.85) !important; backdrop-filter: blur(8px);">
    <div class="modal-card success-modal-card" style="text-align: center; padding: 40px; background: #0d2738 !important; border: 1px solid #35a7b8 !important; position: relative; max-width: 450px; width: 90%; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
      <div class="success-modal-icon" style="width: 80px; height: 80px; background: rgba(40, 167, 69, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: #28a745;">
        <svg viewBox="0 0 24 24" style="width: 40px; height: 40px; fill: none; stroke: currentColor; stroke-width: 3; stroke-linecap: round; stroke-linejoin: round;">
          <path d="M20 6L9 17l-5-5" />
        </svg>
      </div>
      <h3 style="color: #fff; margin-bottom: 10px; font-size: 1.5rem; font-weight: 800;">Action Successful!</h3>
      <p id="themeSuccessMessage" style="color: rgba(255,255,255,0.7); margin-bottom: 25px; line-height: 1.5;">Settings updated successfully.</p>
      <div class="button-row" style="justify-content: center; display: flex;">
        <button class="btn primary" onclick="location.reload()" style="min-width: 140px; background: #35a7b8 !important; color: #fff !important; border: none !important; padding: 14px 28px; border-radius: 10px; cursor: pointer; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; transition: transform 0.2s;">OKAY</button>
      </div>
    </div>
  </div>

  <script>
    // Functions moved to head for better reliability
  </script>
</body>
</html>
