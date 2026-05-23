const storageKeys = {
  records: 'fisherfolkRecords',
  requests: 'fisherfolkRequests',
  adminSettings: 'fisherfolkAdminSettings',
  announcements: 'fisherfolkAnnouncements',
  announcementSeenAt: 'fisherfolkAnnouncementSeenAt',
  navSeenViews: 'fisherfolkNavSeenViews',
  adminTableSort: 'fisherfolkAdminTableSort',
  barangays: 'fisherfolkBarangays',
  archivedBarangays: 'fisherfolkArchivedBarangays'
};

// ========== SUCCESS MODAL SYSTEM ==========
// Integrated with the design system modal patterns
function showSuccessModal(title = 'Successfully Applied', message = '') {
  // Remove existing modal if any
  const existingModal = document.querySelector('.success-modal-overlay');
  if (existingModal) {
    existingModal.remove();
  }
  
  // Create modal overlay
  const overlay = document.createElement('div');
  overlay.className = 'success-modal-overlay';
  
  // Create modal content
  const content = document.createElement('div');
  content.className = 'success-modal-content';
  content.innerHTML = `
    <div class="success-checkmark">
      <i class="fas fa-check"></i>
    </div>
    <h3 class="success-title">${title}</h3>
    ${message ? `<p class="success-message">${message}</p>` : ''}
  `;
  
  overlay.appendChild(content);
  document.body.appendChild(overlay);
  
  // Trigger show animation with slight delay for better visual effect
  setTimeout(() => {
    overlay.classList.add('show');
  }, 10);
  
  // Auto-fade out and remove after 2.5 seconds
  setTimeout(() => {
    content.classList.add('fade-out');
    overlay.classList.remove('show');
    setTimeout(() => {
      overlay.remove();
    }, 500);
  }, 2500);
}

// Make it globally available
window.showSuccessModal = showSuccessModal;

// Setup button handlers for Apply and Restore Defaults
(function initSettingsModals() {
  const attachHandlers = () => {
    // Find all buttons
    document.querySelectorAll('button').forEach(btn => {
      const text = btn.textContent.trim();
      
      // Apply button handler
      if ((text.includes('APPLY') || text.includes('Apply')) && !btn.hasApplyListener) {
        btn.addEventListener('click', function() {
          showSuccessModal('Settings Applied', 'Your changes have been saved successfully.');
        });
        btn.hasApplyListener = true;
      }
      
      // Restore Defaults button handler
      if ((text.includes('RESTORE') || text.includes('Restore')) && !btn.hasRestoreListener) {
        btn.addEventListener('click', function() {
          showSuccessModal('Defaults Restored', 'All settings have been reset to their default values.');
        });
        btn.hasRestoreListener = true;
      }
    });
  };
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', attachHandlers);
  } else {
    attachHandlers();
  }
})();

// Auto-clean old hardcoded data from browser memory
(function cleanOldMemory() {
  try {
    const stored = localStorage.getItem(storageKeys.barangays);
    if (stored && (stored.includes('Poblacion') || stored.includes('San Isidro'))) {
      localStorage.removeItem(storageKeys.barangays);
      localStorage.removeItem(storageKeys.archivedBarangays);
    }
  } catch (e) { }
})();

const idDesignThemes = [
  { key: 'coastal', label: 'Coastal Blue', accentColor: '#2c6aa2' },
  { key: 'reef', label: 'Reef Teal', accentColor: '#187c78' },
  { key: 'sunrise', label: 'Sunrise Gold', accentColor: '#c86e1f' },
  { key: 'midnight', label: 'Midnight Navy', accentColor: '#294776' }
];

const defaultIdDesign = {
  themeKey: 'coastal',
  title: 'Municipal Fisherfolk ID',
  subtitle: 'Official identity card for registered fisherfolk',
  badge: 'Verified Fisherfolk',
  accentColor: ''
};

// No built-in demo fisherfolk records: table data should come from the database.
const defaultRecords = [];

const adminAccount = {
  username: 'admin',
  email: 'admin@lgu.gov.ph',
  password: 'admin123',
  code: '246810',
  name: 'Municipal Admin'
};

const defaultAnnouncements = [
  {
    id: `ANN-${Date.now()}-1`,
    title: 'Fisherfolk ID Renewal Drive',
    message: 'Please check your ID validity and visit the municipal office for renewals before expiry.',
    createdAt: new Date().toISOString()
  }
];

const defaultBarangayOptions = [
  'Aplaya',
  'Labasan',
  'BB1',
  'BB2',
  'Sagana',
  'Other'
];

let barangayOptions = normalizeBarangayList(loadJson(storageKeys.barangays, defaultBarangayOptions));

(function loadAdminSettings() {
  try {
    const raw = localStorage.getItem(storageKeys.adminSettings);
    if (!raw) return;
    const parsed = JSON.parse(raw);
    if (parsed && typeof parsed === 'object') {
      adminAccount.email = String(parsed.email || adminAccount.email);
      adminAccount.password = String(parsed.password || adminAccount.password);
      adminAccount.code = String(parsed.code || adminAccount.code);
    }
  } catch {
    localStorage.removeItem(storageKeys.adminSettings);
  }
})();

const roleViews = {
  admin: [
    { isHeader: true, label: 'Overview' },
    { id: 'dashboard', label: 'Dashboard', icon: 'fas fa-house-chimney-window' },
    { isHeader: true, label: 'Registry & Records' },
    { id: 'register-fisherfolk', label: 'Resident', icon: 'fas fa-user-plus' },
    { id: 'id-printing-queue', label: 'ID Issuance Queue', icon: 'fas fa-id-card-clip' },
    { id: 'archive-storage', label: 'Record Archives', icon: 'fas fa-box-archive' },
    { isHeader: true, label: 'Analytics & Services' },
    { id: 'subsidy-reports-admin', label: 'Subsidy Management', icon: 'fas fa-hand-holding-dollar' },
    { id: 'announcements-admin', label: 'System Broadcasts', icon: 'fas fa-bullhorn' },
    { id: 'reports-analytics', label: 'Master Registry', icon: 'fas fa-database' },
    { id: 'reported-posts', label: 'Content Moderation', icon: 'fas fa-flag-checkered' },
    { isHeader: true, label: 'System Config' },
    { id: 'settings-users', label: 'Barangay Configuration', icon: 'fas fa-gears' },
    { id: 'theme-settings', label: 'Interface Themes', icon: 'fas fa-palette', url: 'theme_settings.php' },
    { id: 'activity-logs', label: 'System Audit Logs', icon: 'fas fa-file-shield', url: 'activity_logs.php' }
  ],
  user: [
    { isHeader: true, label: 'Main Menu' },
    { id: 'my-profile', label: 'My Profile', icon: 'fas fa-user-gear' },
    { id: 'account-settings', label: 'Account Settings', icon: 'fas fa-key' },
    { id: 'my-id-details', label: 'My ID Details', icon: 'fas fa-id-card' },
    { isHeader: true, label: 'Services' },
    { id: 'subsidy-report', label: 'Assistance Request', icon: 'fas fa-hand-holding-hand' },
    { id: 'history', label: 'My History', icon: 'fas fa-clock-rotate-left' },
    { id: 'announcements', label: 'Announcements', icon: 'fas fa-bullhorn' },
    { isHeader: true, label: 'Community' },
    { id: 'community-feed', label: 'Community Feed', icon: 'fas fa-users-viewfinder', url: 'index.php' }
  ]
};

const state = {
  role: null,
  account: null,
  records: loadJson(storageKeys.records, defaultRecords),
  archivedRecords: [],
  requests: loadJson(storageKeys.requests, []),
  announcements: loadJson(storageKeys.announcements, defaultAnnouncements),
  lastSeenAnnouncementsAt: 0,
  adminTableSort: { field: 'id', direction: 'asc' },
  search: '',
  statusFilter: 'all',
  barangayFilter: 'all',
  historyFilter: 'all',
  selectedAdminRecordId: null,
  selectedQueueIds: [],
  currentView: null,
  flashMessage: '',
  archivedBarangays: loadJson(storageKeys.archivedBarangays, []),
  reportedPosts: []
};

// Use PHP data if available (for logged-in users)
if (window.phpRecordsData) {
  state.records = window.phpRecordsData;
}
if (window.phpAnnouncementsData) {
  state.announcements = window.phpAnnouncementsData.map(ann => ({
    ...ann,
    id: ann.announcement_id || ann.id,
    createdAt: ann.date_posted || ann.created_at || ann.createdAt
  }));
}
if (window.phpArchivedRecordsData) {
  state.archivedRecords = window.phpArchivedRecordsData;
}
if (window.phpRequestsData) {
  state.requests = window.phpRequestsData.map(req => ({
    ...req,
    id: req.request_id || req.id,
    userId: req.fisherfolk_id || req.userId,
    fisherfolk_id: req.fisherfolk_id || req.userId,
    type: req.request_type || req.type,
    request_type: req.request_type || req.type,
    boatName: req.boat_name || req.boatName,
    boatType: req.boat_type || req.boatType,
    boatColor: req.boat_color || req.boatColor,
    boatSize: req.boat_size || req.boatSize,
    incidentDate: req.incident_date || req.incidentDate,
    damageCost: req.damage_cost || req.estimated_damage || req.damageCost,
    adminNote: req.admin_notes || req.adminNote,
    fisherfolkName: req.fisherfolkName,
    createdAt: req.date_submitted || req.created_at || req.createdAt
  }));
  console.log("State Requests Initialized:", state.requests);
}
if (window.phpUserData) {
  state.account = window.phpUserData;
  state.role = window.phpUserData.role === 'admin' ? 'admin' : 'user';
  if (window.phpReportsData) {
    state.reportedPosts = window.phpReportsData.map(rep => ({
      ...rep,
      createdAt: rep.created_at || rep.createdAt
    }));
  }
  state.currentView = getRequestedView(state.role) || getDefaultView(state.role);
}

state.records = state.records.map((record) => ({
  ...record,
  id: String(record.id || record.fisherfolk_id || ''),
  firstName: record.firstName || record.first_name || '',
  lastName: record.lastName || record.last_name || '',
  middleName: record.middleName || record.middle_name || record.middleInitial || '',
  contact: record.contact || record.contact_number || '',
  livelihood: record.livelihood || record.primary_livelihood || '',
  photo: record.photo || 'Photo',
  photoData: String(record.photo_data || record.photoData || ''),
  signatureData: String(record.signature_data || record.signatureData || ''),
  idDesign: {
    ...defaultIdDesign,
    ...(record.idDesign || {})
  }
}));

state.archivedRecords = state.archivedRecords.map((record) => ({
  ...record,
  id: String(record.id || record.fisherfolk_id || ''),
  firstName: record.firstName || record.first_name || '',
  lastName: record.lastName || record.last_name || '',
  photo: record.photo || 'Photo',
  photoData: String(record.photo_data || record.photoData || ''),
  signatureData: String(record.signature_data || record.signatureData || ''),
  archivedAt: String(record.archivedAt || record.archived_at || ''),
  archiveReason: String(record.archiveReason || ''),
  idDesign: {
    ...defaultIdDesign,
    ...(record.idDesign || {})
  }
}));

state.requests = state.requests.map((request) => ({
  ...request,
  id: String(request.id || ''),
  userId: String(request.userId || '')
}));

function loadAdminTableSortState() {
  try {
    const raw = localStorage.getItem(storageKeys.adminTableSort);
    if (!raw) return { field: 'id', direction: 'asc' };
    const parsed = JSON.parse(raw);
    const field = ['id', 'name', 'barangay', 'status'].includes(parsed.field) ? parsed.field : 'id';
    const direction = parsed.direction === 'desc' ? 'desc' : 'asc';
    return { field, direction };
  } catch {
    return { field: 'id', direction: 'asc' };
  }
}

function saveAdminTableSortState(nextState) {
  state.adminTableSort = {
    field: ['id', 'name', 'barangay', 'status'].includes(nextState.field) ? nextState.field : 'id',
    direction: nextState.direction === 'desc' ? 'desc' : 'asc'
  };
  try {
    localStorage.setItem(storageKeys.adminTableSort, JSON.stringify(state.adminTableSort));
  } catch {
    // ignore storage failures
  }
}

state.adminTableSort = loadAdminTableSortState();

function getLatestAnnouncementTimestamp() {
  return state.announcements.reduce((latest, item) => {
    const time = Date.parse(item.createdAt || item.date_posted || item.datePosted || item.created_at || '');
    return Number.isFinite(time) && time > latest ? time : latest;
  }, 0);
}

function loadAnnouncementSeenAt() {
  try {
    const raw = localStorage.getItem(storageKeys.announcementSeenAt);
    const value = raw ? Number(raw) : 0;
    return Number.isFinite(value) ? value : 0;
  } catch {
    return 0;
  }
}

function saveAnnouncementSeenAt(timestamp) {
  try {
    localStorage.setItem(storageKeys.announcementSeenAt, String(timestamp || 0));
  } catch {
    // ignore storage failures
  }
  state.lastSeenAnnouncementsAt = Number(timestamp || 0);
}

function loadSeenNavViews() {
  try {
    const raw = localStorage.getItem(storageKeys.navSeenViews);
    const parsed = raw ? JSON.parse(raw) : [];
    return Array.isArray(parsed) ? parsed.map(String) : [];
  } catch {
    return [];
  }
}

function saveSeenNavViews(values) {
  try {
    localStorage.setItem(storageKeys.navSeenViews, JSON.stringify(values));
  } catch {
    // ignore storage failures
  }
}

function hasSeenNavView(viewId) {
  return loadSeenNavViews().includes(String(viewId));
}

function markNavViewSeen(viewId) {
  const normalized = String(viewId || '');
  if (!normalized) return;
  const seenViews = loadSeenNavViews();
  if (!seenViews.includes(normalized)) {
    seenViews.push(normalized);
    saveSeenNavViews(seenViews);
  }
}

function markAnnouncementsSeen() {
  const latestTimestamp = getLatestAnnouncementTimestamp();
  saveAnnouncementSeenAt(latestTimestamp);
  try {
    fetch('api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'mark_announcements_read' })
    }).catch(() => { });
  } catch (error) {
    // ignore
  }
}

state.lastSeenAnnouncementsAt = loadAnnouncementSeenAt();
const latestAnnouncementTimestamp = getLatestAnnouncementTimestamp();
if (!state.lastSeenAnnouncementsAt && latestAnnouncementTimestamp > 0) {
  // treat an untouched session as unread until the user opens announcements
  state.lastSeenAnnouncementsAt = 0;
}

// Only save to localStorage if not using PHP data
if (!window.phpRecordsData) {
  saveJson(storageKeys.records, state.records);
}
if (!window.phpAnnouncementsData) {
  saveJson(storageKeys.announcements, state.announcements);
}
if (!window.phpRequestsData) {
  saveJson(storageKeys.requests, state.requests);
}

syncBarangayOptionsWithRecords();

function loadJson(key, fallback) {
  try {
    const raw = localStorage.getItem(key);
    if (!raw) {
      localStorage.setItem(key, JSON.stringify(fallback));
      return [...fallback];
    }
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [...fallback];
  } catch {
    return [...fallback];
  }
}

function saveJson(key, value) {
  localStorage.setItem(key, JSON.stringify(value));
}

function saveToDatabase(action, data) {
  const payload = { action: action, ...data };

  return fetch('api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload)
  })
    .then(async (response) => {
      const result = await response.json().catch(() => ({ success: false, error: 'Invalid server response' }));
      if (!response.ok || !result.success) {
        throw new Error(result.error || `Request failed (${response.status})`);
      }
      return result;
    })
    .then(result => {
      return result;
    })
    .catch(error => {
      console.error('Network error:', error);
      setFlashMessage('Error saving data: ' + error.message);
      return { success: false, error: error.message };
    });
}

async function refreshRecordsFromDatabase() {
  try {
    const response = await fetch('api.php?action=get_records', {
      method: 'GET',
      headers: { 'Accept': 'application/json' }
    });
    const result = await response.json().catch(() => ({ success: false, error: 'Invalid server response' }));
    if (!response.ok || !result.success || !Array.isArray(result.records)) {
      throw new Error(result.error || `Request failed (${response.status})`);
    }

    state.records = result.records.map((record) => ({
      ...record,
      id: String(record.id || ''),
      photo: record.photo || 'Photo',
      photoData: String(record.photoData || ''),
      signatureData: String(record.signatureData || ''),
      idDesign: {
        ...defaultIdDesign,
        ...(record.idDesign || {})
      }
    }));

    if (state.selectedAdminRecordId && !state.records.some((item) => item.id === state.selectedAdminRecordId)) {
      state.selectedAdminRecordId = state.records[0]?.id || null;
    }

    syncBarangayOptionsWithRecords();
    return true;
  } catch (error) {
    console.error('Failed to refresh records from database:', error);
    return false;
  }
}

function setFlashMessage(message) {
  state.flashMessage = String(message || '');
}

function getRecordById(recordId) {
  const targetId = String(recordId || '');
  return state.records.find((item) => String(item.id || '') === targetId) || null;
}

function getIdTheme(themeKey) {
  return idDesignThemes.find((item) => item.key === themeKey) || idDesignThemes[0];
}

function hexToRgb(hexValue) {
  const clean = String(hexValue || '').trim().replace('#', '');
  if (!/^[0-9a-fA-F]{3}$|^[0-9a-fA-F]{6}$/.test(clean)) return null;
  const normalized = clean.length === 3
    ? clean.split('').map((char) => `${char}${char}`).join('')
    : clean;
  return {
    r: parseInt(normalized.slice(0, 2), 16),
    g: parseInt(normalized.slice(2, 4), 16),
    b: parseInt(normalized.slice(4, 6), 16)
  };
}

function getReadableTextColor(backgroundHex) {
  const rgb = hexToRgb(backgroundHex);
  if (!rgb) return '#ffffff';
  const luminance = ((0.299 * rgb.r) + (0.587 * rgb.g) + (0.114 * rgb.b)) / 255;
  return luminance > 0.6 ? '#173a5b' : '#ffffff';
}

function hexToRgba(hexValue, alpha = 1) {
  const rgb = hexToRgb(hexValue);
  if (!rgb) return '';
  const safeAlpha = Math.max(0, Math.min(1, Number(alpha)));
  return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${safeAlpha})`;
}

function getIdDesign(record) {
  return {
    ...defaultIdDesign,
    ...(record?.idDesign || {})
  };
}

function renderIdCardMarkup(record) {
  // New design based on provided image
  const design = getIdDesign(record);
  const theme = getIdTheme(design.themeKey);
  const customAccent = String(design.accentColor || '').trim();
  const accentColor = hexToRgb(customAccent) ? customAccent : theme.accentColor;
  const photoData = String(record?.photoData || '').trim();
  const photoPath = String(record?.photo || '').trim();
  const signatureData = String(record?.signatureData || '').trim();
  const photoSrc = /^data:image\//i.test(photoData)
    ? photoData
    : (photoPath && !/^photo$/i.test(photoPath) ? photoPath : '');
  const hasPhoto = Boolean(photoSrc);
  // Accept signature as either a data URL or a saved file path (photos/.. or image filename)
  const signatureSrc = /^data:image\//i.test(signatureData)
    ? signatureData
    : (signatureData && /\.(jpg|jpeg|jfif|png|gif|webp)$/i.test(signatureData) ? signatureData : '');
  const hasSignature = Boolean(signatureSrc);

  const lastName = toTitleCase(record?.lastName || '');
  const firstName = toTitleCase(record?.firstName || '');
  const middleName = toTitleCase(record?.middleName || record?.middleInitial || '');
  const barangay = toTitleCase(record?.barangay || '');
  const birthDate = record?.birthDate ? formatDate(record.birthDate) : '';
  const fishrNumber = record?.fishrNumber || '';
  const rsbsaNumber = record?.rsbsaNumber || '';

  const buildLine = (label, value) => `
        <div class="id-line-redesign">
          <span class="id-label-redesign">${escapeHtml(label)}</span>
          <span class="id-value-redesign">
            <span class="id-value-text">${escapeHtml(value || '')}</span>
            <span class="id-underline"></span>
          </span>
        </div>
      `;

  // banner/title section from design
  const bannerTitle = escapeHtml(design.title || defaultIdDesign.title);
  const bannerSubtitle = escapeHtml(design.subtitle || defaultIdDesign.subtitle);
  const badgeText = escapeHtml(design.badge || defaultIdDesign.badge);

  return `
        <div class="id-card-print-wrap" aria-hidden="false">
          <div class="id-card-realistic" role="region" aria-label="Fisherfolk ID preview">
            <div class="id-card-redesign id-card--${escapeHtml(theme.key)}" style="--id-accent: ${escapeHtml(accentColor)};">
              <div class="id-card-header-redesign" style="background: ${escapeHtml(accentColor)};">
                <img src="logo.jpg" style="position: absolute; left: 6mm; top: 50%; transform: translateY(-50%);" alt="Logo" />
                <div style="flex: 1; text-align: center; padding: 0 16mm;">
                  <div style="font-size: 2.8mm; line-height: 1;">REPUBLIC OF THE PHILIPPINES</div>
                  <div style="font-size: 2.8mm; line-height: 1;">PROVINCE OF MINDORO</div>
                  <div style="font-size: 3.3mm; line-height: 1.1; font-weight: 900;">MUNICIPALITY OF BONGABONG</div>
                </div>
              </div>
              <!-- Header uses accent color via --id-accent variable set on the root element -->
              <div class="id-card-body-redesign">
                <div class="id-card-fields-redesign">
                  ${buildLine('LAST NAME', lastName)}
                  ${buildLine('FIRST NAME', firstName)}
                  ${buildLine('MIDDLE NAME', middleName)}
                  ${buildLine('BARANGAY', barangay)}
                  ${buildLine('BIRTHDAY', birthDate)}
                  ${buildLine('FishR Number', fishrNumber)}
                  ${buildLine('RSBSA Number', rsbsaNumber)}
                </div>
                <div class="id-card-photo-signature">
                  <div class="id-photo-redesign">${hasPhoto
      ? `<img class="id-photo-image-redesign" src="${escapeHtml(photoSrc)}" alt="Fisherfolk photo" />`
      : '<span>Photo Here</span>'}</div>
                  <div class="id-signature-redesign">
                    <div class="id-signature-image-slot-redesign">${hasSignature
      ? `<img class="id-signature-image-redesign" src="${escapeHtml(signatureSrc)}" alt="Fisherfolk signature" />`
      : '<span class="id-signature-placeholder-redesign">SIGNATURE</span>'}</div>
                    <div class="id-signature-line-redesign" aria-hidden="true"></div>
                    <div class="id-signature-label-redesign" style="color: #111111 !important; -webkit-text-fill-color: #111111 !important;">SIGNATURE</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
}

function fileToDataUrl(file) {
  return new Promise((resolve) => {
    if (!(file instanceof File) || !file.size) {
      resolve('');
      return;
    }
    if (!String(file.type || '').toLowerCase().startsWith('image/')) {
      resolve('');
      return;
    }
    const reader = new FileReader();
    reader.onload = () => resolve(typeof reader.result === 'string' ? reader.result : '');
    reader.onerror = () => resolve('');
    reader.readAsDataURL(file);
  });
}

// Improve file input UX: update visible label and optional thumbnail when a file is chosen
window.handleFileInputChange = async function(inputEl, labelId, thumbId) {
  try {
    const label = document.getElementById(labelId);
    const thumb = thumbId ? document.getElementById(thumbId) : null;
    if (!inputEl) return;
    const f = inputEl.files && inputEl.files[0] ? inputEl.files[0] : null;
    if (label) label.textContent = f ? f.name : 'No file chosen';
    if (thumb) {
      if (f) {
        const data = await fileToDataUrl(f);
        if (data) { thumb.src = data; thumb.style.display = 'block'; }
      } else {
        thumb.style.display = 'none';
      }
    }
  } catch (e) { /* ignore */ }
};

// Validate file input (type + max size). Returns true if valid, sets label text on error.
window.validateFileInput = function(inputEl, labelId, maxSizeBytes = 2 * 1024 * 1024) {
  try {
    const label = document.getElementById(labelId);
    if (!inputEl) return false;
    const f = inputEl.files && inputEl.files[0] ? inputEl.files[0] : null;
    if (!f) { if (label) label.textContent = 'No file chosen'; return true; }
    const allowed = ['image/jpeg','image/jpg','image/png','image/jfif'];
    if (!allowed.includes((f.type || '').toLowerCase())) {
      if (label) label.textContent = 'Invalid file type';
      return false;
    }
    if (f.size > maxSizeBytes) {
      if (label) label.textContent = 'File too large';
      return false;
    }
    return true;
  } catch (e) { return false; }
};

window.clearFileInput = function(inputId, labelId, thumbId) {
  try {
    const inp = document.getElementById(inputId);
    const lbl = document.getElementById(labelId);
    const th = thumbId ? document.getElementById(thumbId) : null;
    if (inp) {
      inp.value = '';
    }
    if (lbl) lbl.textContent = 'No file chosen';
    if (th) { th.src = ''; th.style.display = 'none'; }
  } catch(e){}
};

function renderIdCardBackMarkup(record) {
  const design = getIdDesign(record);
  const theme = getIdTheme(design.themeKey);
  const customAccent = String(design.accentColor || '').trim();
  const accentColor = hexToRgb(customAccent) ? customAccent : theme.accentColor;

  const emergencyName = String(record?.emergencyName || '').trim();
  const emergencyRelation = String(record?.emergencyRelation || '').trim();
  const emergencyAddress = String(record?.emergencyAddress || '').trim();
  const emergencyContact = String(record?.emergencyContact || '').trim();

  return `
    <div class="id-card-realistic" role="region" aria-label="Fisherfolk ID back preview">
      <div class="id-card-redesign id-card-back id-card--${escapeHtml(theme.key)}" style="--id-accent: ${escapeHtml(accentColor)};">
        <div class="id-card-body-back">
          <div class="id-back-emergency">
            <div class="id-back-heading">IN CASE OF EMERGENCY, PLEASE NOTIFY:</div>
            <div class="id-back-line"><span>NAME:</span><span>${escapeHtml(emergencyName)}</span></div>
            <div class="id-back-line"><span>RELATION:</span><span>${escapeHtml(emergencyRelation)}</span></div>
            <div class="id-back-line"><span>COMPLETE ADDRESS:</span><span>${escapeHtml(emergencyAddress)}</span></div>
            <div class="id-back-line"><span>CONTACT NO.:</span><span>${escapeHtml(emergencyContact)}</span></div>
          </div>

          <div class="id-back-cert-title" style="background: ${escapeHtml(accentColor)} !important;">CERTIFICATION</div>

          <div class="id-back-cert-copy">
            This is to certify that the bearer of this card is a legitimate fisherfolk of the Municipality of Bongabong.
          </div>

          <div class="id-back-footer">
            <div class="id-back-logo-wrap">
              <img src="logo.jpg" class="id-back-logo" alt="Logo" />
            </div>
            <div class="id-back-sign-area">
              <div class="id-back-sign">Signature</div>
              <div class="id-back-name"><span>Name:</span><span>${escapeHtml(record?.firstName + ' ' + record?.lastName || '')}</span></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
}

function getPrintStyles() {
  return `
        @page {
          size: A4 portrait;
          margin: 8mm;
        }
        * {
          box-sizing: border-box;
          -webkit-print-color-adjust: exact;
          print-color-adjust: exact;
        }
        body {
          margin: 0;
          padding: 0;
          font-family: "Arial Narrow", "Arial", "Helvetica Neue", sans-serif;
          background: #ffffff;
        }
        .print-wrap {
          width: 100%;
          min-height: calc(100vh - 16mm);
          display: grid;
          place-items: start center;
        }
        .print-page {
          width: 100%;
          display: grid;
          place-items: start center;
        }
        .print-page + .print-page {
          margin-top: 0;
        }
        .id-card {
          --id-accent: #2c6aa2;
          --id-accent-soft: rgba(44, 106, 162, 0.35);
          width: 85.6mm;
          min-height: 54mm;
          border: 0.45mm solid #7d7d7d;
          border-radius: 2.6mm;
          background: #ffffff;
          padding: 2mm;
          box-shadow: none;
          position: relative;
          overflow: hidden;
        }
        .id-card-header {
          background: var(--id-accent);
          color: #ffffff;
          border-radius: 0;
          height: 8.6mm;
          display: flex;
          align-items: center;
          justify-content: center;
          text-align: center;
          padding: 0 1.4mm;
          font-size: 3.8mm;
          letter-spacing: 0.16mm;
          line-height: 1;
          font-weight: 900;
          text-transform: uppercase;
        }

        .id-card-redesign {
          width: 90mm;
          height: 57mm;
          border: 0.45mm solid #7d7d7d;
          border-radius: 2.6mm;
          background: #ffffff;
          display: flex;
          flex-direction: column;
          overflow: hidden;
          padding: 0;
        }
        .id-card-header-redesign {
          background: var(--id-accent, #2176c7);
          color: #ffffff;
          display: flex;
          align-items: center;
          justify-content: center;
          position: relative;
          padding: 2mm 0;
          min-height: 12.5mm;
          line-height: 1.05;
          letter-spacing: 0.1mm;
        }
        .id-card-header-redesign img {
          position: absolute;
          left: 6mm;
          top: 50%;
          transform: translateY(-50%);
          height: 11mm;
          width: 11mm;
          border-radius: 50%;
          object-fit: cover;
          border: 1px solid rgba(255,255,255,0.4);
        }
        .id-card-header-redesign div {
          margin: 0;
          text-align: center;
        }
        .id-card-body-redesign {
          display: flex;
          flex-direction: row;
          align-items: center;
          flex: 1;
          padding: 1.5mm 4mm 1mm 5mm;
          gap: 3.5mm;
        }
        .id-card-fields-redesign {
          flex: 1;
          display: flex;
          flex-direction: column;
          gap: 0.85mm;
        }
        .id-card-photo-signature {
          width: 32mm;
          display: flex;
          flex-direction: column;
          justify-content: center;
          align-items: center;
          gap: 1.5mm;
        }
        .id-line-redesign {
          display: grid;
          grid-template-columns: 32mm 1fr; /* match back label width for alignment */
          align-items: end;
          column-gap: 1.5mm;
        }
        .id-label-redesign {
          font-size: 2.3mm; /* slightly larger to match back */
          font-weight: 900;
          color: #111111;
          line-height: 1;
          white-space: nowrap;
        }
        .id-value-redesign {
          position: relative;
          min-height: 4mm;
        }
        .id-value-text {
          position: absolute;
          left: 0;
          bottom: 0.45mm;
          max-width: 100%;
          font-size: 2.3mm; /* match back value size for visual parity */
          color: #111111;
          line-height: 1;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          background: #ffffff;
          padding-right: 0.35mm;
        }
        .id-line-redesign:nth-child(4) .id-value-text {
          font-size: 2.1mm;
        }
        .id-underline {
          position: absolute;
          left: 0;
          right: 0;
          bottom: 0;
          border-bottom: 0.35mm solid #222222;
        }
        .id-card-photo-signature {
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          align-items: center;
        }
        .id-photo-redesign {
          width: 27.2mm;
          height: 24.6mm; /* reduced so signature has space */
          border: 0.35mm solid #8a8a8a;
          background: #ffffff;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        .id-photo-redesign span {
          font-size: 2.8mm;
          color: #666666;
          font-style: italic;
        }
        .id-photo-image-redesign {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }
        .id-signature-redesign {
          width: 27.2mm;
          color: #111111;
          font-weight: 700;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: flex-start;
          overflow: visible;
          min-height: 13mm;
          max-height: 16mm;
          margin-top: 0.4mm;
        }

        .id-signature-image-slot-redesign {
          width: 100%;
          min-height: 6.4mm;
          display: flex;
          align-items: center;
          justify-content: center;
          overflow: visible;
        }

        .id-signature-image-redesign {
          width: 96%;
          height: 100%;
          max-width: 100%;
          max-height: 100%;
          object-fit: cover;
          object-position: center;
          display: block;
          transform: scale(1.08);
          transform-origin: center center;
        }

        .id-signature-placeholder-redesign {
          font-size: 2.3mm;
          font-style: italic;
          color: #666666;
          line-height: 1;
        }

        .id-signature-line-redesign {
          width: 100%;
          border-top: 0.35mm solid #222222;
          margin-top: 0.3mm;
        }

        .id-signature-label-redesign {
          width: 100%;
          text-align: center;
          font-size: 2.05mm;
          line-height: 1;
          margin-top: 0.1mm;
        }

        .id-card-back {
          display: flex;
          flex-direction: column;
          height: 100%;
          background: #ffffff;
          color: #000000 !important;
        }
        .id-card-body-back {
          display: flex;
          flex-direction: column;
          flex: 1;
          padding: 5mm 5.5mm;
          color: #000000 !important;
        }
        .id-back-emergency {
          display: grid;
          gap: 1.2mm;
          width: 100%;
          color: #000000 !important;
        }
        .id-back-heading {
          font-size: 2.35mm;
          font-weight: 900;
          color: #000000 !important;
          -webkit-text-fill-color: #000000 !important;
          line-height: 1.1;
          letter-spacing: 0.03mm;
          text-transform: uppercase;
          margin-bottom: 0.5mm;
          display: block !important;
          opacity: 1 !important;
          visibility: visible !important;
        }
        .id-back-line {
          display: grid;
          grid-template-columns: 32mm 1fr; /* Wider fixed column to ensure labels fit */
          align-items: end;
          gap: 1.5mm;
          color: #000000 !important;
        }
        .id-back-line > span:first-child {
          font-size: 2.3mm;
          font-weight: 900;
          color: #000000 !important;
          -webkit-text-fill-color: #000000 !important;
          line-height: 1.05;
          text-transform: uppercase;
          display: block !important;
          opacity: 1 !important;
          visibility: visible !important;
        }
        .id-back-line > span:last-child {
          display: block !important;
          border-bottom: 0.3mm solid #000000;
          min-height: 3.8mm;
          font-size: 2.3mm;
          color: #000000 !important;
          -webkit-text-fill-color: #000000 !important;
          font-weight: 700;
          padding-left: 1.2mm;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          opacity: 1 !important;
          visibility: visible !important;
        }
        .id-back-cert-title {
          margin-top: 2.5mm;
          background: var(--id-accent, #2176c7) !important;
          color: #ffffff !important;
          -webkit-text-fill-color: #ffffff !important;
          text-align: center;
          font-size: 7.2mm;
          font-weight: 900;
          letter-spacing: 0.1mm;
          line-height: 1;
          padding: 1.4mm;
          text-transform: uppercase;
        }
        .id-back-cert-copy {
          margin-top: 2.5mm;
          text-align: center;
          font-size: 2.3mm;
          line-height: 1.35;
          color: #000000 !important;
          -webkit-text-fill-color: #000000 !important;
          font-weight: 500;
          padding: 0 4mm;
          display: block !important;
          opacity: 1 !important;
          visibility: visible !important;
        }
        .id-back-footer {
          margin-top: auto;
          position: relative;
          width: 100%;
          min-height: 16mm;
          padding-bottom: 3mm;
          display: flex;
          flex-direction: column;
          justify-content: flex-end;
          align-items: center;
        }
        .id-back-logo-wrap {
          position: absolute;
          left: 4mm;
          bottom: 3mm;
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 1;
        }
        .id-back-logo {
          height: 14mm;
          width: 14mm;
          border-radius: 50%;
          border: 0.3mm solid #222;
          object-fit: cover;
          background: #ffffff;
        }
        .id-back-sign-area {
          width: 75%;
          display: flex;
          flex-direction: column;
          align-items: center;
          position: relative;
          z-index: 2;
        }
        .id-back-sign {
          font-size: 2.8mm;
          line-height: 1.1;
          color: #000000 !important;
          -webkit-text-fill-color: #000000 !important;
          font-weight: 600;
          margin-bottom: 0.5mm;
          text-align: center;
          opacity: 1 !important;
          visibility: visible !important;
        }
        .id-back-name {
          margin: 0 auto;
          width: 70%;
          display: flex;
          justify-content: center;
          align-items: flex-end;
          border-bottom: 0.3mm solid #000000;
          min-height: 4.8mm;
          padding-bottom: 0.2mm;
          color: #000000 !important;
        }
        .id-back-name > span:first-child {
          font-size: 2.4mm;
          line-height: 1.1;
          color: #000000 !important;
          -webkit-text-fill-color: #000000 !important;
          font-weight: 800;
          margin-right: 2mm;
          text-transform: uppercase;
          opacity: 1 !important;
          visibility: visible !important;
        }
        .id-back-name > span:last-child {
          display: block !important;
          font-size: 2.8mm;
          color: #000000 !important;
          -webkit-text-fill-color: #000000 !important;
          font-weight: 700;
          text-align: center;
          opacity: 1 !important;
          visibility: visible !important;
        }
        .id-card-body {
          margin-top: 1.4mm;
          display: grid;
          grid-template-columns: 1fr 26.2mm;
          gap: 2.4mm;
          align-items: start;
        }
        .id-card-fields {
          display: grid;
          gap: 1.1mm;
        }
        .id-line {
          display: grid;
          grid-template-columns: 18.5mm 1fr;
          gap: 1.8mm;
          align-items: center;
        }
        .id-line-full {
          grid-template-columns: 18.5mm 1fr;
        }
        .id-label {
          font-size: 1.85mm;
          font-weight: 800;
          color: #121212;
          letter-spacing: 0.05mm;
          line-height: 1.05;
          text-transform: uppercase;
        }
        .id-value {
          min-height: 4.2mm;
          border-radius: 0;
          background: #e3e3e3;
          display: flex;
          align-items: center;
          padding: 0 1.2mm;
          font-size: 1.75mm;
          color: #1e1e1e;
          font-weight: 700;
          line-height: 1;
        }
        .id-card-side {
          display: grid;
          gap: 6mm;
          align-content: start;
        }
        .id-photo {
          height: 24.5mm;
          border: 0.55mm solid #888888;
          background: #ffffff;
          border-radius: 0;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        .id-photo span {
          font-size: 3.9mm;
          font-weight: 500;
          color: #696969;
          letter-spacing: 0.08mm;
          font-style: italic;
          text-transform: uppercase;
        }
        .id-signature {
          border-top: 0.45mm solid #9a9a9a;
          min-height: 5.5mm;
          display: flex;
          align-items: flex-end;
          justify-content: center;
          padding-top: 1.2mm;
        }
        .id-signature span {
          font-size: 2.2mm;
          font-weight: 800;
          color: #474747;
          letter-spacing: 0.05mm;
          line-height: 1;
          text-transform: uppercase;
        }
        @media print {
          body {
            padding: 0;
          }
          .print-wrap {
            min-height: auto;
            display: block;
          }
          .print-page {
            break-before: page;
            page-break-before: always;
            margin: 0;
          }
          .print-page:first-child {
            break-before: auto;
            page-break-before: auto;
          }
        }
      `;
}

function buildIdPrintDocument(record) {
  const frontMarkup = renderIdCardMarkup(record);
  const backMarkup = renderIdCardBackMarkup(record);

  return `
        <!doctype html>
        <html>
          <head>
            <meta charset="utf-8" />
            <title>Print Fisherfolk ID - ${escapeHtml(record.id || 'N/A')}</title>
            <style>${getPrintStyles()}</style>
          </head>
          <body>
            <div class="print-wrap">
              <div class="print-page">${frontMarkup}</div>
              <div class="print-page">${backMarkup}</div>
            </div>
          </body>
        </html>
      `;
}

function buildBulkIdPrintDocument(records) {
  const pagesMarkup = records.map(record => `
    <div class="print-page">${renderIdCardMarkup(record)}</div>
    <div class="print-page">${renderIdCardBackMarkup(record)}</div>
  `).join('');

  return `
        <!doctype html>
        <html>
          <head>
            <meta charset="utf-8" />
            <title>Batch Print Fisherfolk IDs</title>
            <style>${getPrintStyles()}</style>
          </head>
          <body>
            <div class="print-wrap">
              ${pagesMarkup}
            </div>
          </body>
        </html>
      `;
}

function printIdCard(record) {
  if (!record) {
    setFlashMessage('ID record not found. Unable to print.');
    return false;
  }

  // Try opening a popup first (fast path). If blocked, fall back to an inline iframe print.
  const tryPopup = () => {
    const printWindow = window.open('', '_blank', 'width=920,height=700');
    if (!printWindow) return null;
    return printWindow;
  };

  const fullMarkup = buildIdPrintDocument(record);

  // Attempt popup first
  const popup = tryPopup();
  if (popup) {
    popup.document.open();
    popup.document.write(fullMarkup);
    popup.document.close();
    popup.focus();
    popup.onload = () => {
      try { popup.print(); } catch (e) { /* ignore */ }
      popup.onafterprint = () => popup.close();
    };
    // in case onload doesn't fire for some reason
    setTimeout(() => { try { popup.print(); } catch (e) { } }, 500);
    return true;
  }

  // Popup blocked: fallback to hidden iframe printing
  try {
    let iframe = document.getElementById('idPrintFallbackFrame');
    if (!iframe) {
      iframe = document.createElement('iframe');
      iframe.id = 'idPrintFallbackFrame';
      iframe.style.position = 'fixed';
      iframe.style.right = '0';
      iframe.style.bottom = '0';
      iframe.style.width = '1px';
      iframe.style.height = '1px';
      iframe.style.border = '0';
      iframe.style.visibility = 'hidden';
      document.body.appendChild(iframe);
    }
    const idoc = iframe.contentWindow.document;
    idoc.open();
    idoc.write(fullMarkup);
    idoc.close();
    iframe.contentWindow.focus();
    setTimeout(() => {
      try { iframe.contentWindow.print(); } catch (e) { setFlashMessage('Unable to open print dialog. Please allow popups or try using browser print.'); }
    }, 400);
    return true;
  } catch (err) {
    setFlashMessage('Unable to open print preview. Please allow popups or check browser settings.');
    return false;
  }
}

function printBulkIdCards(records) {
  if (!records || !records.length) {
    setFlashMessage('No records found for bulk printing.');
    return false;
  }

  const fullMarkup = buildBulkIdPrintDocument(records);

  const tryPopup = () => {
    const printWindow = window.open('', '_blank', 'width=920,height=700');
    if (!printWindow) return null;
    return printWindow;
  };

  const popup = tryPopup();
  if (popup) {
    popup.document.open();
    popup.document.write(fullMarkup);
    popup.document.close();
    popup.focus();
    popup.onload = () => {
      try { popup.print(); } catch (e) { /* ignore */ }
      popup.onafterprint = () => popup.close();
    };
    setTimeout(() => { try { popup.print(); } catch (e) { } }, 600);
    return true;
  }

  try {
    let iframe = document.getElementById('idPrintFallbackFrame');
    if (!iframe) {
      iframe = document.createElement('iframe');
      iframe.id = 'idPrintFallbackFrame';
      iframe.style.position = 'fixed';
      iframe.style.right = '0';
      iframe.style.bottom = '0';
      iframe.style.width = '1px';
      iframe.style.height = '1px';
      iframe.style.border = '0';
      iframe.style.visibility = 'hidden';
      document.body.appendChild(iframe);
    }
    const idoc = iframe.contentWindow.document;
    idoc.open();
    idoc.write(fullMarkup);
    idoc.close();
    iframe.contentWindow.focus();
    setTimeout(() => {
      try { iframe.contentWindow.print(); } catch (e) { setFlashMessage('Unable to open print dialog.'); }
    }, 500);
    return true;
  } catch (err) {
    setFlashMessage('Unable to open print preview.');
    return false;
  }
}

function downloadIdCard(record) {
  if (!record) {
    setFlashMessage('ID record not found. Unable to download.');
    return false;
  }
  const fullMarkup = buildIdPrintDocument(record);
  const fileName = `fisherfolk-id-${String(record.id || 'record')}.html`;
  downloadTextFile(fileName, fullMarkup, 'text/html;charset=utf-8');
  setFlashMessage(`Downloaded printable ID file for ${record.id}.`);
  return true;
}

function getCurrentPrintableRecord() {
  if (state.role === 'admin') {
    return getFocusedAdminRecord() || getRecordById(state.selectedAdminRecordId) || state.records[0] || null;
  }
  return getUserRecord();
}

window.downloadId = function downloadId() {
  const record = getCurrentPrintableRecord();
  if (!record) {
    setFlashMessage('ID record not found. Unable to download.');
    renderApp();
    return false;
  }
  const result = downloadIdCard(record);
  renderApp();
  return result;
};

function updateRecordFields(recordId, fields) {
  let updated = false;
  state.records = state.records.map((item) => {
    if (item.id !== recordId) return item;
    updated = true;
    const nextDesign = fields.idDesign
      ? { ...getIdDesign(item), ...fields.idDesign }
      : item.idDesign;
    const nextRecord = { ...item, ...fields };
    if (fields.idDesign) {
      nextRecord.idDesign = nextDesign;
    }
    return nextRecord;
  });

  if (!updated) return false;
  if (state.role === 'user' && state.account?.id === recordId) {
    state.account = state.records.find((item) => item.id === recordId) || state.account;
  }

  // Save to PHP database if available
  if (window.phpRecordsData) {
    saveToDatabase('update_record', { recordId: recordId, fields: fields });
  } else {
    saveJson(storageKeys.records, state.records);
  }

  return true;
}

function updateRecordStatus(recordId, nextStatus) {
  let updated = false;
  state.records = state.records.map((item) => {
    if (item.id !== recordId) return item;
    updated = true;
    return { ...item, status: nextStatus };
  });

  if (!updated) return false;

  // Save to PHP database if available
  if (window.phpRecordsData) {
    saveToDatabase('update_status', { recordId: recordId, status: nextStatus });
  } else {
    saveJson(storageKeys.records, state.records);
  }

  return true;
}

function updateRequestFields(requestId, fields) {
  let updated = false;
  state.requests = state.requests.map((item) => {
    if (item.id !== requestId) return item;
    updated = true;
    return { ...item, ...fields };
  });

  if (!updated) return false;

  // Save to PHP database if available
  if (window.phpRequestsData) {
    saveToDatabase('update_request', { requestId: requestId, fields: fields });
  } else {
    saveJson(storageKeys.requests, state.requests);
  }

  return true;
}

function archiveRecord(recordId, reason = 'Archived from admin dashboard') {
  const target = getRecordById(recordId);
  if (!target) return false;

  const archivedAt = new Date().toISOString();
  state.records = state.records.filter((item) => item.id !== recordId);
  state.archivedRecords = [
    ...state.archivedRecords.filter((item) => item.id !== recordId),
    { ...target, archivedAt, archiveReason: reason }
  ];

  if (window.phpRecordsData) {
    saveToDatabase('archive_record', { recordId: recordId, reason });
  } else {
    saveJson(storageKeys.records, state.records);
    saveJson('fisherfolkArchivedRecords', state.archivedRecords);
  }

  return true;
}

function restoreArchivedRecord(recordId) {
  const target = state.archivedRecords.find((item) => item.id === recordId);
  if (!target) return false;

  state.archivedRecords = state.archivedRecords.filter((item) => item.id !== recordId);
  state.records = [
    ...state.records,
    { ...target, archivedAt: '', archiveReason: '' }
  ];

  if (window.phpRecordsData) {
    saveToDatabase('restore_record', { recordId: recordId });
  } else {
    saveJson(storageKeys.records, state.records);
    saveJson('fisherfolkArchivedRecords', state.archivedRecords);
  }

  return true;
}

function deleteRecord(recordId) {
  return archiveRecord(recordId);
}

function toCsv(rows) {
  const escapeCsv = (value) => {
    const text = String(value ?? '');
    if (/[",\n]/.test(text)) {
      return `"${text.replace(/"/g, '""')}"`;
    }
    return text;
  };
  return rows.map((row) => row.map(escapeCsv).join(',')).join('\n');
}

function downloadTextFile(fileName, content, mimeType) {
  const blob = new Blob([content], { type: mimeType });
  const url = URL.createObjectURL(blob);
  const anchor = document.createElement('a');
  anchor.href = url;
  anchor.download = fileName;
  document.body.appendChild(anchor);
  anchor.click();
  document.body.removeChild(anchor);
  URL.revokeObjectURL(url);
}

function exportAdminReport(kind) {
  if (kind === 'records') {
    const header = ['ID', 'First Name', 'Last Name', 'Barangay', 'Livelihood', 'Contact', 'Status', 'Validity'];
    const rows = state.records.map((item) => [item.id, item.firstName, item.lastName, item.barangay, item.livelihood, item.contact, item.status, item.validity]);
    downloadTextFile(`fisherfolk-records-${Date.now()}.csv`, toCsv([header, ...rows]), 'text/csv;charset=utf-8');
    setFlashMessage('Records report exported successfully.');
    return;
  }

  const header = ['Request ID', 'User ID', 'Type', 'Subject', 'Boat Name', 'Boat Type', 'Boat Color', 'Boat Size', 'Incident Date', 'Damage Cost', 'Message', 'Contact Preference', 'Status', 'Admin Note', 'Created At'];
  const rows = state.requests.map((item) => [
    item.id,
    item.userId,
    item.type || 'General',
    item.subject,
    item.boatName || '',
    item.boatType || '',
    item.boatColor || '',
    item.boatSize || '',
    item.incidentDate || '',
    item.damageCost || '',
    item.message,
    item.contactPreference || '',
    item.status,
    item.adminNote || '',
    formatDate(item.createdAt)
  ]);
  downloadTextFile(`fisherfolk-requests-${Date.now()}.csv`, toCsv([header, ...rows]), 'text/csv;charset=utf-8');
  setFlashMessage('Requests report exported successfully.');
}

function openPrintableReport(title, summaryItems, tableHeaders, tableRows, footerNote) {
  const win = window.open('', '_blank', 'width=1200,height=900');
  if (!win) {
    setFlashMessage('Popup blocked. Please allow popups to print the report.');
    return;
  }

  const tableHead = tableHeaders.map((header) => `<th>${escapeHtml(header)}</th>`).join('');
  const tableBody = tableRows.map((row) => `<tr>${row.map((cell) => `<td>${escapeHtml(cell)}</td>`).join('')}</tr>`).join('');

  win.document.open();
  win.document.write(`
        <!doctype html>
        <html lang="en">
        <head>
          <meta charset="utf-8">
          <meta name="viewport" content="width=device-width,initial-scale=1">
          <title>${escapeHtml(title)}</title>
          <style>
            @page { size: A4; margin: 12mm 12mm 14mm 12mm; }
            html,body { height: 100%; }
            body { font-family: "Segoe UI", Roboto, Arial, sans-serif; color: #12202b; margin: 0; background: #fff; }
            .container { max-width: 1100px; margin: 0 auto; padding: 8px 0; }
            .header { display:flex; align-items: center; gap: 16px; border-bottom: 2px solid #d6e6f3; padding-bottom: 10px; }
            .org { font-weight:700; font-size:16px; }
            .sub { color:#4a6b7f; font-size:12px; }
            .title { font-size:20px; font-weight:800; margin:8px 0 4px; }
            .meta { font-size:12px; color:#556b78; }
            .section-title { margin-top:10px; font-weight:800; font-size:13px; }
            table { width:100%; border-collapse:collapse; font-size:12px; margin-top:8px; }
            thead th { background:#123c5a; color:#fff; padding:8px 10px; text-align:left; font-weight:700; font-size:11px; }
            tbody td { padding:8px 10px; border-bottom:1px solid #eef6fb; vertical-align:top; }
            tbody tr:nth-child(even) td { background: #fbfdff; }
            tfoot td { padding-top:12px; font-size:12px; color:#556b78; }
            .footer-note { margin-top:14px; font-size:12px; color:#556b78; }
            /* print helpers */
            thead { display:table-header-group; }
            tfoot { display:table-footer-group; }
            @media print {
              body { background: #fff; }
              .container { box-shadow: none; }
              .no-print { display:none !important; }
            }
          </style>
        </head>
        <body>
          <div class="container">
            <div class="header">
              <div style="flex:0 0 auto; width:64px; height:64px; border-radius:6px; background:#e6f2fb; display:flex;align-items:center;justify-content:center;color:#0b4b76;font-weight:800;">LGU</div>
              <div style="flex:1 1 auto;">
                <div class="org">Municipality of Bongabong</div>
                <div class="sub">Fisherfolk Information Management System</div>
                <div class="title">${escapeHtml(title)}</div>
                <div class="meta">Generated: ${escapeHtml(new Date().toLocaleString())} &nbsp;|&nbsp; Prepared by: Municipal Admin</div>
              </div>
            </div>

            <div class="section-title">Details</div>
            <table>
              <thead><tr>${tableHead}</tr></thead>
              <tbody>${tableBody}</tbody>
            </table>

            <div class="footer-note">${escapeHtml(footerNote)}</div>
          </div>
          <script>
            // allow layout to settle before printing
            window.onload = function() { setTimeout(() => { window.print(); setTimeout(() => window.close(), 600); }, 120); };
          </script>
        </body>
        </html>
      `);
  win.document.close();
}

function printRecordsReport() {
  const summaryItems = [
    { label: 'Total Records', value: String(state.records.length) },
    { label: 'Active', value: String(state.records.filter((item) => item.status === 'Active').length) },
    { label: 'Pending', value: String(state.records.filter((item) => item.status === 'Pending').length) },
    { label: 'Expired', value: String(state.records.filter((item) => item.status === 'Expired').length) }
  ];
  const tableHeaders = ['ID', 'First Name', 'Last Name', 'Barangay', 'Livelihood', 'Contact', 'Status', 'Validity'];
  const tableRows = state.records.map((item) => [item.id, item.firstName, item.lastName, item.barangay, item.livelihood, item.contact, item.status, item.validity]);
  openPrintableReport('Fisherfolk Records Report', summaryItems, tableHeaders, tableRows, 'This report lists the current active registry records only.');
}

function printRequestsReport() {
  const summaryItems = [
    { label: 'Total Requests', value: String(state.requests.length) },
    { label: 'Pending', value: String(state.requests.filter((item) => item.status === 'Pending').length) },
    { label: 'Approved', value: String(state.requests.filter((item) => item.status === 'Approved').length) },
    { label: 'Rejected', value: String(state.requests.filter((item) => item.status === 'Rejected').length) }
  ];
  const tableHeaders = ['Request ID', 'User ID', 'Type', 'Subject', 'Status', 'Created At', 'Admin Note'];
  const tableRows = state.requests.map((item) => [
    item.id,
    item.userId,
    item.type || 'General',
    item.subject || '',
    item.status || 'Pending',
    formatDate(item.createdAt),
    item.adminNote || ''
  ]);
  openPrintableReport('Fisherfolk Requests Report', summaryItems, tableHeaders, tableRows, 'This report summarizes submitted requests and their current processing status.');
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email).trim());
}

function validatePhoneNumber(phone) {
  return /^09\d{9}$/.test(String(phone).replace(/\D/g, ''));
}

function getStatusBadgeClass(status) {
  const map = {
    'Active': 'badge-success',
    'Pending': 'badge-warning',
    'Expired': 'badge-danger',
    'Approved': 'badge-success',
    'Rejected': 'badge-danger',
    'Under Review': 'badge-warning'
  };
  return map[status] || 'badge-muted';
}

function renderStatusBadge(status) {
  return `<span class="status-badge ${getStatusBadgeClass(status)}">${escapeHtml(status)}</span>`;
}

function formatDate(value) {
  if (!value) return 'N/A';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString();
}

function toTitleCase(value) {
  return String(value || '')
    .toLowerCase()
    .split(/\s+/)
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
}

function normalizeBarangayValue(value) {
  return toTitleCase(String(value || '').trim());
}

function normalizeBarangayList(list) {
  const seen = new Set();
  return (Array.isArray(list) ? list : [])
    .map((item) => normalizeBarangayValue(item))
    .filter((item) => {
      if (!item) return false;
      const key = item.toLowerCase();
      if (seen.has(key)) return false;
      seen.add(key);
      return true;
    });
}

function syncBarangayOptionsWithRecords() {
  const fromRecords = state.records.map((item) => normalizeBarangayValue(item.barangay));
  const merged = normalizeBarangayList([...barangayOptions, ...defaultBarangayOptions, ...fromRecords]);
  barangayOptions = merged;
  saveJson(storageKeys.barangays, barangayOptions);
}

function addBarangayOption(value) {
  const candidate = normalizeBarangayValue(value);
  if (!candidate) return { ok: false, message: 'Barangay name is required.' };
  const exists = barangayOptions.some((item) => item.toLowerCase() === candidate.toLowerCase());
  if (exists) return { ok: false, message: 'Barangay already exists in the list.' };
  barangayOptions = normalizeBarangayList([...barangayOptions, candidate]);
  saveJson(storageKeys.barangays, barangayOptions);
  return { ok: true, value: candidate };
}

function archiveBarangayOption(value) {
  const target = normalizeBarangayValue(value);
  if (!target) return { ok: false, message: 'Invalid barangay selection.' };

  // Find it in options
  const index = barangayOptions.findIndex(item => item.toLowerCase() === target.toLowerCase());
  if (index === -1) return { ok: false, message: 'Barangay not found in active list.' };

  const inUse = state.records.some((item) => normalizeBarangayValue(item.barangay) === target);
  if (inUse) {
    return { ok: false, message: 'Cannot archive a barangay that still has registered residents. Please reassign them first.' };
  }

  const barangayToArchive = barangayOptions[index];
  barangayOptions.splice(index, 1);
  state.archivedBarangays = [...state.archivedBarangays, barangayToArchive];

  saveJson(storageKeys.barangays, barangayOptions);
  saveJson(storageKeys.archivedBarangays, state.archivedBarangays);

  if (state.barangayFilter !== 'all' && normalizeBarangayValue(state.barangayFilter) === target) {
    state.barangayFilter = 'all';
  }
  return { ok: true, value: target };
}

function restoreBarangayOption(value) {
  const target = normalizeBarangayValue(value);
  if (!target) return { ok: false, message: 'Invalid barangay selection.' };

  const index = state.archivedBarangays.findIndex(item => item.toLowerCase() === target.toLowerCase());
  if (index === -1) return { ok: false, message: 'Barangay not found in archive.' };

  const barangayToRestore = state.archivedBarangays[index];
  state.archivedBarangays.splice(index, 1);
  barangayOptions = normalizeBarangayList([...barangayOptions, barangayToRestore]);

  saveJson(storageKeys.barangays, barangayOptions);
  saveJson(storageKeys.archivedBarangays, state.archivedBarangays);

  return { ok: true, value: target };
}

function viewBarangayResidents(barangayName) {
  openBarangayResidentsModal(barangayName);
}

function renderBarangayOptions(selectedValue = '') {
  const normalizedSelected = normalizeBarangayValue(selectedValue);
  const normalizedOptions = new Set(barangayOptions.map((item) => toTitleCase(item)));
  const options = [...barangayOptions];
  if (normalizedSelected && !normalizedOptions.has(normalizedSelected)) {
    options.push(normalizedSelected);
  }

  return options
    .map((item) => {
      const value = toTitleCase(item);
      const selected = value === normalizedSelected ? 'selected' : '';
      return `<option value="${escapeHtml(value)}" ${selected}>${escapeHtml(value)}</option>`;
    })
    .join('');
}

function renderBarangayFilterOptions(selectedValue = 'all') {
  const selected = normalizeBarangayValue(selectedValue || 'all');
  const current = selected && selected !== 'All' ? selected : 'all';
  return [
    '<option value="all">All Barangays</option>',
    ...barangayOptions.map((item) => {
      const value = normalizeBarangayValue(item);
      const isSelected = value.toLowerCase() === String(current).toLowerCase() ? 'selected' : '';
      return `<option value="${escapeHtml(value)}" ${isSelected}>${escapeHtml(value)}</option>`;
    })
  ].join('');
}

function getBarangayCounts(records) {
  const counts = records.reduce((acc, item) => {
    const key = normalizeBarangayValue(item.barangay) || 'Unspecified';
    acc[key] = (acc[key] || 0) + 1;
    return acc;
  }, {});

  return Object.entries(counts)
    .map(([barangay, total]) => ({ barangay, total }))
    .sort((a, b) => b.total - a.total || a.barangay.localeCompare(b.barangay));
}

function getUserRecord() {
  const accountId = String(state.account?.id || '');
  return state.records.find((record) => String(record.id || '') === accountId) || null;
}

function getDefaultView(role) {
  const views = (roleViews[role] || []).filter(item => !item.isHeader);
  return views[0]?.id ?? null;
}

function getRequestedView(role) {
  const params = new URLSearchParams(window.location.search);
  const requestedView = params.get('view');
  const views = roleViews[role] || [];
  return views.some((item) => item.id && item.id === requestedView) ? requestedView : null;
}

function getViewUrl(viewId) {
  const navItem = (roleViews[state.role] || []).find(item => item.id === viewId);
  if (navItem && navItem.url) {
    return navItem.url;
  }
  const basePath = state.role === 'admin' ? 'admin.php' : 'dashboard.php';
  return `${basePath}?view=${encodeURIComponent(viewId)}`;
}

function getNavItems() {
  return roleViews[state.role] || [];
}

function ensureCurrentView() {
  const items = getNavItems().filter(item => !item.isHeader);
  if (!items.length) {
    state.currentView = null;
    return;
  }

  const hasCurrent = items.some((item) => item.id === state.currentView);
  if (!hasCurrent) {
    state.currentView = items[0].id;
  }
}

function getCurrentNavItem() {
  ensureCurrentView();
  return getNavItems().find((item) => item.id === state.currentView) || null;
}

function getNavBadgeCount(viewId) {
  // AUTO-HIDE: If this is the current active view, don't show the badge
  if (state.currentView === viewId) return 0;

  if (state.role === 'user') {
    const record = getUserRecord();
    if (viewId === 'subsidy-report') {
      return 0; // Hide fixed badge after click
    }
    if (viewId === 'history') {
      if (hasSeenNavView('history')) return 0;
      return state.requests.filter((item) => item.userId === record?.id).length;
    }
    if (viewId === 'announcements') {
      if (hasSeenNavView('announcements')) return 0;
      return state.announcements.filter((item) => {
        const time = Date.parse(item.createdAt || item.date_posted || item.datePosted || item.created_at || '');
        return Number.isFinite(time) && time > state.lastSeenAnnouncementsAt;
      }).length;
    }
    return 0;
  }

  if (viewId === 'id-printing-queue') {
    if (hasSeenNavView('id-printing-queue')) return 0;
    return state.records.filter((item) => item.status === 'Pending' || item.status === 'Active').length;
  }
  if (viewId === 'register-fisherfolk') {
    if (hasSeenNavView('register-fisherfolk')) return 0;
    return state.records.filter((item) => item.status === 'Pending').length;
  }
  if (viewId === 'announcements-admin') {
    if (hasSeenNavView('announcements-admin')) return 0;
    return state.announcements.filter((item) => {
      const time = Date.parse(item.createdAt || item.date_posted || item.datePosted || item.created_at || '');
      return Number.isFinite(time) && time > state.lastSeenAnnouncementsAt;
    }).length;
  }
  if (viewId === 'subsidy-reports-admin') {
    if (hasSeenNavView('subsidy-reports-admin')) return 0;
    return state.requests.filter((item) => item.type === 'Subsidy' && (item.status === 'Pending' || item.status === 'Under Review')).length;
  }
  return 0;
}

function animateDashboardView() {
  const targets = ['breadcrumbs', 'dashboardHero', 'quickActions', 'sectionTitle', 'kpiGrid', 'primaryPanel', 'secondaryPanel'];
  targets.forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('view-enter');
    requestAnimationFrame(() => {
      el.classList.add('view-enter');
    });
  });
}

function renderBreadcrumbs() {
  const el = document.getElementById('breadcrumbs');
  if (!el) return;
  const current = getCurrentNavItem();
  const workspaceLabel = state.role === 'admin' ? 'Admin Workspace' : 'User Workspace';
  const currentLabel = current?.label || 'Dashboard';
  el.innerHTML = `
                <span class="crumb">${escapeHtml(workspaceLabel)}</span>
                <span class="sep">/</span>
                <span class="crumb current">${escapeHtml(currentLabel)}</span>
              `;
}

function setLoginWelcome(stateLabel) {
  const title = document.getElementById('loginWelcomeTitle');
  const subtitle = document.getElementById('loginWelcomeSubtitle');
  if (!title || !subtitle) return;

  if (stateLabel === 'user') {
    title.textContent = 'WELCOME USER!';
    subtitle.textContent = 'User portal access granted.';
    return;
  }

  if (stateLabel === 'admin') {
    title.textContent = 'WELCOME ADMIN!';
    subtitle.textContent = 'Admin portal access granted.';
    return;
  }

  title.textContent = 'WELCOME BACK!';
  subtitle.textContent = '';
}

function loginAsUser(identifier, password) {
  const normalized = identifier.trim().toLowerCase();
  const record = state.records.find((item) => {
    return (item.id.toLowerCase() === normalized || item.username.toLowerCase() === normalized) && item.password === password;
  });

  if (!record) {
    showLoginAlert('User login failed. Check the ID/username and password.');
    return;
  }

  state.role = 'user';
  state.account = record;
  state.currentView = getDefaultView('user');
  setLoginWelcome('user');
  hideLoginShowApp();
}

function loginAsAdmin(identifier, password, code) {
  const normalized = identifier.trim().toLowerCase();
  const matchesAccount = (normalized === adminAccount.username || normalized === adminAccount.email) && password === adminAccount.password;

  if (!matchesAccount || code.trim() !== adminAccount.code) {
    showLoginAlert('Admin login failed. Check the username, password, and one-time code.');
    return;
  }

  state.role = 'admin';
  state.account = adminAccount;
  state.currentView = getDefaultView('admin');
  state.selectedAdminRecordId = state.records[0]?.id || null;
  setLoginWelcome('admin');
  hideLoginShowApp();
}

function getNextFisherfolkId() {
  const maxId = state.records.reduce((max, item) => {
    const match = String(item.id).match(/^FF-(\d+)$/);
    if (!match) return max;
    const value = Number(match[1]);
    return Number.isFinite(value) && value > max ? value : max;
  }, 0);
  return `FF-${String(maxId + 1).padStart(4, '0')}`;
}

function registerFisherfolk(formData) {
  const username = String(formData.get('username')).trim();
  const password = String(formData.get('password'));
  const confirmPassword = String(formData.get('confirmPassword'));

  if (password !== confirmPassword) {
    showLoginAlert('Registration failed. Password and confirm password do not match.');
    return false;
  }

  const usernameExists = state.records.some((record) => String(record.username).toLowerCase() === username.toLowerCase());
  if (usernameExists) {
    showLoginAlert('Registration failed. Username is already in use.');
    return false;
  }

  const currentYear = new Date().getFullYear();
  const newRecord = {
    id: getNextFisherfolkId(),
    username,
    password,
    firstName: String(formData.get('firstName')).trim(),
    middleName: String(formData.get('middleName') || '').trim(),
    lastName: String(formData.get('lastName')).trim(),
    barangay: String(formData.get('barangay')).trim(),
    livelihood: String(formData.get('livelihood')).trim(),
    contact: String(formData.get('contact')).trim(),
    birthDate: String(formData.get('birthDate') || '').trim(),
    fishrNumber: String(formData.get('fishrNumber') || '').trim(),
    rsbsaNumber: String(formData.get('rsbsaNumber') || '').trim(),
    emergencyName: String(formData.get('emergencyName') || '').trim(),
    emergencyRelation: String(formData.get('emergencyRelation') || '').trim(),
    emergencyAddress: String(formData.get('emergencyAddress') || '').trim(),
    emergencyContact: String(formData.get('emergencyContact') || '').trim(),
    status: 'Pending',
    validity: `12/${currentYear + 1}`,
    gender: 'Not specified',
    photo: 'Photo',
    photoData: '',
    signatureData: '',
    idDesign: { ...defaultIdDesign }
  };

  state.records = [newRecord, ...state.records];
  saveJson(storageKeys.records, state.records);
  showLoginAlert(`Registration successful. Your Fisherfolk ID is ${newRecord.id}. You can now log in.`);
  return newRecord;
}

function setPortalView(targetId) {
  const targetView = document.getElementById(targetId);
  if (!targetView) return;

  document.querySelectorAll('.portal-tab').forEach((tab) => {
    const isActive = tab.getAttribute('data-portal-target') === targetId;
    tab.classList.toggle('active', isActive);
    tab.setAttribute('aria-selected', String(isActive));
  });

  document.querySelectorAll('.portal-view').forEach((view) => {
    view.classList.toggle('active', view.id === targetId);
  });

  const alertBox = document.getElementById('loginAlert');
  if (alertBox) {
    alertBox.classList.add('hidden');
    alertBox.textContent = '';
  }
}

function setUserAuthView(targetId) {
  document.querySelectorAll('.auth-tab').forEach((tab) => {
    const isActive = tab.getAttribute('data-auth-target') === targetId;
    tab.classList.toggle('active', isActive);
    tab.setAttribute('aria-selected', String(isActive));
  });

  document.querySelectorAll('.auth-view').forEach((view) => {
    view.classList.toggle('active', view.id === targetId);
  });
}

function showLoginAlert(message) {
  const alertBox = document.getElementById('loginAlert');
  if (!alertBox) return;
  alertBox.textContent = message;
  alertBox.classList.remove('hidden');
}

function openLogoutModal() {
  const modal = document.getElementById('logoutModal');
  if (!modal) return;
  // Store the element that had focus before modal opened
  window.__previousFocus = document.activeElement;
  modal.classList.remove('hidden');
  // Focus the cancel button for accessibility
  const cancelBtn = document.getElementById('cancelLogoutButton');
  if (cancelBtn) cancelBtn.focus();
}

function closeLogoutModal() {
  const modal = document.getElementById('logoutModal');
  if (!modal) return;
  modal.classList.add('hidden');
  // Restore focus to the element that had focus before modal opened
  if (window.__previousFocus && typeof window.__previousFocus.focus === 'function') {
    window.__previousFocus.focus();
  }
}

function openAdminAccountSettingsModal() {
  const modal = document.getElementById('adminAccountSettingsModal');
  if (!modal) return;
  window.__previousFocus = document.activeElement;

  const emailInput = document.getElementById('modalAdminEmail');
  const passwordInput = document.getElementById('modalAdminPassword');
  if (emailInput) emailInput.value = adminAccount.email;
  if (passwordInput) passwordInput.value = adminAccount.password;

  modal.classList.remove('hidden');
}

function closeAdminAccountSettingsModal() {
  const modal = document.getElementById('adminAccountSettingsModal');
  if (!modal) return;
  modal.classList.add('hidden');
  if (window.__previousFocus && typeof window.__previousFocus.focus === 'function') {
    window.__previousFocus.focus();
  }
}

function openBarangayResidentsModal(barangayName) {
  const modal = document.getElementById('barangayResidentsModal');
  const title = document.getElementById('barangayResidentsTitle');
  const body = document.getElementById('barangayResidentsBody');
  if (!modal || !title || !body) return;

  const normalized = normalizeBarangayValue(barangayName);
  title.textContent = `Residents in Barangay ${normalized}`;

  const residents = state.records.filter(r => normalizeBarangayValue(r.barangay) === normalized);

  if (residents.length === 0) {
    body.innerHTML = '<div style="padding: 60px; text-align: center; color: #00d4ff; opacity: 0.6; font-style: italic;">No residents found for this barangay.</div>';
  } else {
    const rows = residents.map(r => `
      <tr class="admin-table-row">
        <td style="padding: 20px; color: #fff !important; font-weight: bold; border-bottom: 1px solid rgba(0, 212, 255, 0.1); font-size: 1rem;">${escapeHtml(r.id)}</td>
        <td style="padding: 20px; color: #0af0ff !important; font-weight: 800; border-bottom: 1px solid rgba(0, 212, 255, 0.1); font-size: 1.1rem;">${escapeHtml(r.firstName)} ${escapeHtml(r.lastName)}</td>
        <td style="padding: 20px; color: #fff !important; border-bottom: 1px solid rgba(0, 212, 255, 0.1); font-size: 1rem;">${escapeHtml(r.contact)}</td>
        <td style="padding: 20px; border-bottom: 1px solid rgba(0, 212, 255, 0.1);">${renderStatusBadge(r.status)}</td>
      </tr>
    `).join('');

    body.innerHTML = `
      <div class="table-wrap settings-table-wrap" style="background: #001a33 !important; border: 2px solid #00d4ff !important; border-radius: 12px; overflow: hidden; margin-top: 10px;">
        <table class="registry-table" style="width: 100%; border-collapse: collapse;">
          <thead style="background: #0a1e2b; position: sticky; top: 0; z-index: 10; border-bottom: 2px solid #00d4ff;">
            <tr>
              <th style="padding: 20px; text-align: left; color: #00d4ff; font-size: 0.95rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px;">ID NO.</th>
              <th style="padding: 20px; text-align: left; color: #00d4ff; font-size: 0.95rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px;">FULL NAME</th>
              <th style="padding: 20px; text-align: left; color: #00d4ff; font-size: 0.95rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px;">CONTACT NO.</th>
              <th style="padding: 20px; text-align: left; color: #00d4ff; font-size: 0.95rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1px;">REGISTRY STATUS</th>
            </tr>
          </thead>
          <tbody style="background: #001a33 !important;">
            ${rows}
          </tbody>
        </table>
      </div>
    `;
  }

  window.__previousFocus = document.activeElement;
  modal.classList.remove('hidden');
}

function closeBarangayResidentsModal() {
  const modal = document.getElementById('barangayResidentsModal');
  if (!modal) return;
  modal.classList.add('hidden');
  if (window.__previousFocus && typeof window.__previousFocus.focus === 'function') {
    window.__previousFocus.focus();
  }
}

function openAddBarangayModal() {
  const modal = document.getElementById('addBarangayModal');
  if (!modal) return;
  // Store the element that had focus before modal opened
  window.__previousFocus = document.activeElement;
  modal.classList.remove('hidden');
  const input = document.getElementById('modalNewBarangay');
  if (input) input.focus();
}

function closeAddBarangayModal() {
  const modal = document.getElementById('addBarangayModal');
  if (!modal) return;
  modal.classList.add('hidden');
  const form = document.getElementById('modalBarangayForm');
  if (form) form.reset();
  // Restore focus to the element that had focus before modal opened
  if (window.__previousFocus && typeof window.__previousFocus.focus === 'function') {
    window.__previousFocus.focus();
  }
}

function openMasterDetailModal(record) {
  const modal = document.getElementById('masterDetailModal');
  const body = document.getElementById('masterDetailModalBody');
  if (!modal || !body || !record) return;
  // Store the element that had focus before modal opened
  window.__previousFocus = document.activeElement;
  body.innerHTML = renderAdminMasterDetailModal(record);
  modal.classList.remove('hidden');
  // Focus the close button for accessibility
  const closeBtn = document.getElementById('closeMasterDetailModal');
  if (closeBtn) closeBtn.focus();
}

function closeMasterDetailModal() {
  const modal = document.getElementById('masterDetailModal');
  if (!modal) return;
  modal.classList.add('hidden');
  // Restore focus to the element that had focus before modal opened
  if (window.__previousFocus && typeof window.__previousFocus.focus === 'function') {
    window.__previousFocus.focus();
  }
}

// Open the admin edit form inside the Master Detail modal
window.openAdminEditInModal = function(recordId) {
  const modal = document.getElementById('masterDetailModal');
  const body = document.getElementById('masterDetailModalBody');
  if (!modal || !body) return;
  state.selectedAdminRecordId = String(recordId || '');
  body.innerHTML = renderAdminEditRecord();
  modal.classList.remove('hidden');
  // Focus the first input for accessibility
  const first = body.querySelector('input, select, textarea, button');
  if (first && typeof first.focus === 'function') first.focus();
};

function openSuccessModal(title, message) {
  const modal = document.getElementById('registrationSuccessModal');
  const titleEl = document.getElementById('registrationSuccessTitle');
  const messageEl = modal ? modal.querySelector('p') : null;
  if (!modal) return;
  if (titleEl && title) titleEl.textContent = title;
  if (messageEl && message) messageEl.textContent = message;
  modal.classList.remove('hidden');
  modal.setAttribute('aria-hidden', 'false');
}

function ensureAlertModal() {
  let modal = document.getElementById('alertModal');
  if (modal) {
    if (!modal.dataset.bound) {
      const closeButton = document.getElementById('closeAlertModal');
      if (closeButton) closeButton.addEventListener('click', closeAlertModal);
      modal.addEventListener('click', (event) => {
        if (event.target === modal) closeAlertModal();
      });
      modal.dataset.bound = '1';
    }
    return modal;
  }
  const wrapper = document.createElement('div');
  wrapper.innerHTML = `
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
    </div>`;
  document.body.appendChild(wrapper.firstElementChild);
  modal = document.getElementById('alertModal');
  if (modal && !modal.dataset.bound) {
    const closeButton = document.getElementById('closeAlertModal');
    if (closeButton) closeButton.addEventListener('click', closeAlertModal);
    modal.addEventListener('click', (event) => {
      if (event.target === modal) closeAlertModal();
    });
    modal.dataset.bound = '1';
  }
  return modal;
}

function openAlertModal(title, message) {
  const modal = ensureAlertModal();
  if (!modal) return;
  const titleEl = document.getElementById('alertModalTitle');
  const messageEl = document.getElementById('alertModalMessage');
  if (titleEl && title) titleEl.textContent = title;
  if (messageEl && message) messageEl.textContent = message;
  modal.classList.remove('hidden');
  modal.setAttribute('aria-hidden', 'false');
}

function closeAlertModal() {
  const modal = document.getElementById('alertModal');
  if (!modal) return;
  modal.classList.add('hidden');
  modal.setAttribute('aria-hidden', 'true');
}

function openRegistrationSuccessModal() {
  openSuccessModal('Generate ID successfully', 'The new fisherfolk ID has been created and added to the system.');
}

function closeRegistrationSuccessModal() {
  const modal = document.getElementById('registrationSuccessModal');
  if (!modal) return;
  modal.classList.add('hidden');
  modal.setAttribute('aria-hidden', 'true');
}

function showActionConfirm(title, message, onConfirm) {
  const modal = document.getElementById('actionConfirmModal');
  const titleEl = document.getElementById('actionConfirmTitle');
  const msgEl = document.getElementById('actionConfirmMessage');
  const confirmBtn = document.getElementById('actionConfirmBtn');
  const cancelBtn = document.getElementById('actionCancelBtn');

  if (!modal || !titleEl || !msgEl || !confirmBtn || !cancelBtn) {
    if (confirm(message)) onConfirm();
    return;
  }

  titleEl.textContent = title;
  msgEl.textContent = message;
  modal.classList.remove('hidden');

  confirmBtn.onclick = () => {
    modal.classList.add('hidden');
    onConfirm();
  };

  cancelBtn.onclick = () => {
    modal.classList.add('hidden');
  };
}

function openMasterDetailModalById(recordId) {
  if (!recordId) return;
  const targetRecord = (typeof getRecordById === 'function') ? getRecordById(recordId) : null;
  if (!targetRecord) return;
  state.selectedAdminRecordId = recordId;
  openMasterDetailModal(targetRecord);
}

function switchShell(fromId, toId, onShown) {
  const fromEl = document.getElementById(fromId);
  const toEl = document.getElementById(toId);
  if (!fromEl || !toEl) {
    if (typeof onShown === 'function') onShown();
    return;
  }

  fromEl.classList.remove('shell-enter', 'shell-exit');
  toEl.classList.remove('shell-enter', 'shell-exit');
  toEl.classList.remove('hidden');

  requestAnimationFrame(() => {
    toEl.classList.add('shell-enter');
    fromEl.classList.add('shell-exit');

    window.setTimeout(() => {
      fromEl.classList.add('hidden');
      fromEl.classList.remove('shell-exit');
      toEl.classList.remove('shell-enter');
      if (typeof onShown === 'function') onShown();
    }, 220);
  });
}

function persistSession() {
  if (!state.role) return;
  sessionStorage.setItem('fisherfolkSession', JSON.stringify({
    role: state.role,
    accountId: state.role === 'user' ? state.account?.id : adminAccount.username,
    currentView: state.currentView
  }));
}

function hideLoginShowApp() {
  const loginShell = document.getElementById('loginShell');
  const appShell = document.getElementById('appShell');
  const alertBox = document.getElementById('loginAlert');
  if (alertBox) {
    alertBox.classList.add('hidden');
    alertBox.textContent = '';
  }

  if (!loginShell || !appShell) return;
  switchShell('loginShell', 'appShell');

  persistSession();
  renderApp();
}

function logout() {
  closeLogoutModal();
  // For PHP-based system, redirect to logout.php
  if (window.phpUserData || window.phpRecordsData) {
    window.location.href = 'logout.php';
    return;
  }

  // Original JavaScript logout for non-PHP mode
  state.role = null;
  state.account = null;
  state.search = '';
  state.historyFilter = 'all';
  state.selectedAdminRecordId = null;
  state.currentView = null;
  sessionStorage.removeItem('fisherfolkSession');
  const userForm = document.getElementById('userLoginForm');
  const adminForm = document.getElementById('adminLoginForm');
  if (userForm) userForm.reset();
  if (adminForm) adminForm.reset();
  setLoginWelcome('back');
  setPortalView('userPortalView');
  switchShell('appShell', 'loginShell', () => {
    const input = document.getElementById('userIdentifier');
    if (input) input.focus();
  });
}

function buildKpiCards() {
  const kpiGrid = document.getElementById('kpiGrid');

  if (state.role === 'admin') {
    const activeCount = state.records.filter((item) => item.status === 'Active').length;
    const pendingCount = state.records.filter((item) => item.status === 'Pending').length;
    const expiredCount = state.records.filter((item) => item.status === 'Expired').length;

    kpiGrid.innerHTML = [
      ['Total Registered', state.records.length],
      ['Active IDs', activeCount],
      ['Pending Verification', pendingCount],
      ['Expired IDs', expiredCount]
    ].map(([label, value]) => `
          <article class="kpi">
            <div class="label">${escapeHtml(label)}</div>
            <div class="value">${escapeHtml(value)}</div>
          </article>
        `).join('');
    return;
  }

  const record = getUserRecord();
  const requestCount = state.requests.filter((item) => item.userId === record?.id).length;
  kpiGrid.innerHTML = [
    ['My Fisherfolk ID', record?.id ?? 'N/A'],
    ['Profile Status', record?.status ?? 'N/A'],
    ['My Requests', requestCount],
    ['Valid Until', record?.validity ?? 'N/A']
  ].map(([label, value]) => `
        <article class="kpi">
          <div class="label">${escapeHtml(label)}</div>
          <div class="value">${escapeHtml(value)}</div>
        </article>
      `).join('');
}

function buildNav() {
  const navList = document.getElementById('navList');
  ensureCurrentView();
  const items = getNavItems();

  if (!items.length) {
    if (navList && navList.children.length) {
      return;
    }
    return;
  }

  navList.innerHTML = items.map((item) => {
    if (item.isHeader) {
      return `<div class="nav-header">${escapeHtml(item.label)}</div>`;
    }
    const activeClass = item.id === state.currentView ? 'active' : '';
    const badgeCount = getNavBadgeCount(item.id);
    const badgeMarkup = badgeCount > 0 ? `<span class="nav-badge">${escapeHtml(badgeCount)}</span>` : '';
    const iconMarkup = item.icon ? `<i class="${escapeHtml(item.icon)}"></i>` : '';
    return `<a class="nav-item ${activeClass}" data-view="${escapeHtml(item.id)}" href="${escapeHtml(getViewUrl(item.id))}">${iconMarkup}<span>${escapeHtml(item.label)}</span>${badgeMarkup}</a>`;
  }).join('');
}

// Clear nav badges and mark server-side announcements read when user opens views
(function attachNavBadgeHandler() {
  const navList = document.getElementById('navList');
  if (!navList) return;
  navList.addEventListener('click', function (e) {
    const link = e.target.closest('a.nav-item');
    if (!link) return;
    const viewId = link.getAttribute('data-view');

    // remove the visual badge immediately to avoid confusion
    const badge = link.querySelector('.nav-badge');
    if (badge) badge.remove();

    // mark any badge-bearing nav item as seen so it stays cleared after re-render
    if (viewId) {
      markNavViewSeen(viewId);
    }

    // if announcements view, inform server that user has seen announcements
    if (viewId === 'announcements' || viewId === 'announcements-admin') {
      markAnnouncementsSeen();
    }
  }, false);
})();

function buildQuickActions() {
  const quickActions = document.getElementById('quickActions');
  const actions = state.role === 'admin'
    ? [
      { label: 'New Registration', view: 'register-fisherfolk' },
      { label: 'Print ID Queue', view: 'id-printing-queue' },
      { label: 'Subsidy Reports', view: 'subsidy-reports-admin' },
      { label: 'View Reports', view: 'reports-analytics' }
    ]
    : [
      { label: 'View Profile', view: 'my-profile' },
      { label: 'Edit Credentials', view: 'account-settings' },
      { label: 'Report Boat Damage', view: 'subsidy-report' },
      { label: 'View History', view: 'history' },
      { label: 'Check ID Status', view: 'my-id-details' },
      { label: 'View Announcements', view: 'announcements' }
    ];

  quickActions.innerHTML = actions
    .map((item) => `<button class="quick-action" data-view="${escapeHtml(item.view)}" type="button">${escapeHtml(item.label)}</button>`)
    .join('');
}

function buildTopBar() {
  const searchWrap = document.getElementById('searchWrap');
  const profileWrap = document.getElementById('profileWrap');

  if (searchWrap) {
    if (state.role === 'admin') {
      // Temporarily hide admin search bar.
      searchWrap.innerHTML = '';
    } else {
      const record = getUserRecord();
      const displayName = `${toTitleCase(record?.firstName)} ${toTitleCase(record?.lastName)}`.trim() || 'Profile';
      searchWrap.innerHTML = `<div class="meta-row"><span class="role-badge">User Portal</span><span>Viewing ${escapeHtml(displayName)}</span></div>`;
    }
  }

  const userDisplayName = `${toTitleCase(state.account?.firstName)} ${toTitleCase(state.account?.lastName)}`.trim();
  const adminDisplayName = toTitleCase(adminAccount.name || 'Municipal Admin');
  const accountLabel = `${userDisplayName || 'User'}${state.account?.id ? ` (${state.account.id})` : ''}`;
  const avatarText = state.role === 'admin'
    ? 'A'
    : `${(toTitleCase(state.account?.firstName) || 'U').charAt(0)}${(toTitleCase(state.account?.lastName) || '').charAt(0)}`.toUpperCase().trim() || 'U';

  const accountMarkup = state.role === 'admin'
    ? `
          <div class="profile-meta">
            <strong>${escapeHtml(adminDisplayName)}</strong>
            <span>${escapeHtml(adminAccount.email)}</span>
          </div>
        `
    : `<span>${escapeHtml(accountLabel || 'Logged in')}</span>`;

  profileWrap.innerHTML = `
        <div class="meta-row" style="justify-content: space-between; width: 100%;">
          <div class="meta-row">
            <div class="profile-avatar">${escapeHtml(avatarText)}</div>
            ${accountMarkup}
          </div>
          <button class="link-button" id="logoutButton" type="button">Logout</button>
        </div>
      `;
}

function buildHero() {
  const hero = document.getElementById('dashboardHero');
  if (!hero) return;

  if (state.role === 'admin') {
    const activeCount = state.records.filter((item) => item.status === 'Active').length;
    const pendingCount = state.records.filter((item) => item.status === 'Pending').length;
    const validatedCount = state.records.length - pendingCount;

    hero.innerHTML = `
          <div class="panel-header dashboard-hero-header">
            <h2 id="sectionTitle">Fisherfolk Registry</h2>
          </div>
          <div class="hero-copy">
            <span class="stats-tag">Admin Overview</span>
            <h2>Welcome Admin. Operational control for fisherfolk registration and ID issuance.</h2>
            <p>Track new entries, validate records, and print IDs from a single municipal workspace designed for speed and clarity.</p>
          </div>
          <div class="quick-actions" id="quickActions"></div>
          <div class="kpi-grid" id="kpiGrid"></div>
        `;
    return;
  }

  const record = getUserRecord();
  const requestCount = state.requests.filter((item) => item.userId === record?.id).length;

  hero.innerHTML = `
        <div class="panel-header dashboard-hero-header">
          <h2 id="sectionTitle">My Dashboard</h2>
        </div>
        <div class="hero-copy">
          <span class="stats-tag">User Overview</span>
          <h2>Welcome User. Your fisherfolk identity, subsidy reports, and announcements in one place.</h2>
          <p>View your own account and ID details, report boat damage subsidy concerns, and monitor announcements from the municipal office.</p>
        </div>
        <div class="quick-actions" id="quickActions"></div>
        <div class="kpi-grid" id="kpiGrid"></div>
      `;
}

function filteredRecords() {
  let results = state.records.filter((record) => !record.archivedAt);

  if (state.statusFilter && state.statusFilter !== 'all') {
    results = results.filter((record) => record.status === state.statusFilter);
  }

  if (state.barangayFilter && state.barangayFilter !== 'all') {
    const activeBarangay = normalizeBarangayValue(state.barangayFilter);
    results = results.filter((record) => normalizeBarangayValue(record.barangay) === activeBarangay);
  }

  const query = state.search.trim().toLowerCase();
  if (!query) return results;

  return results.filter((record) => {
    return [record.id, record.firstName, record.lastName, record.barangay, record.status, record.livelihood]
      .some((value) => String(value).toLowerCase().includes(query));
  });
}

function renderUserProfileOnly() {
  const record = getUserRecord();
  return `
            <header class="panel-head">
              <span>My Profile</span>
              <span>Personal information</span>
            </header>
            <div class="panel-body">
              <div class="record-card">
                <strong>${escapeHtml(record?.firstName ?? 'User')} ${escapeHtml(record?.lastName ?? '')}</strong>
                <span>Fisherfolk ID: ${escapeHtml(record?.id ?? 'N/A')}</span>
                <span>Barangay: ${escapeHtml(record?.barangay ?? 'N/A')}</span>
                <span>Livelihood: ${escapeHtml(record?.livelihood ?? 'N/A')}</span>
                <span>Contact: ${escapeHtml(record?.contact ?? 'N/A')}</span>
                <span>Status: ${escapeHtml(record?.status ?? 'N/A')}</span>
              </div>
            </div>
          `;
}

function renderUserIdDetailsOnly() {
  const record = getUserRecord();
  return `
            <header class="panel-head">
              <span>My ID Details</span>
              <span>Card validity and issue trail</span>
            </header>
            <div class="panel-body">
              <div class="id-card-print-wrap">
                ${renderIdCardMarkup(record)}
              </div>
              <div class="button-row" style="margin-top: 20px; display: flex; gap: 12px;">
                <button class="btn" type="button" id="downloadMyIdButton" style="flex: 1; height: 42px;">Download ID</button>
                <button class="btn primary" type="button" id="printMyIdButton" style="flex: 1; height: 42px;">Print ID Card</button>
              </div>
              <div class="timeline">
                <div class="timeline-item">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content">
                    <strong>Registration received</strong>
                    <span>Profile details were submitted and accepted by the office.</span>
                  </div>
                </div>
                <div class="timeline-item">
                  <div class="timeline-dot"></div>
                  <div class="timeline-content">
                    <strong>ID status: ${escapeHtml(record?.status ?? 'N/A')}</strong>
                    <span>Current validity period is ${escapeHtml(record?.validity ?? 'N/A')}.</span>
                  </div>
                </div>
              </div>
            </div>
          `;
}

function renderUserAnnouncements() {
  const items = state.announcements.slice().reverse();
  const cards = items.map((item) => `
            <div class="record-card">
              <strong>${escapeHtml(item.title)}</strong>
              <span>${escapeHtml(item.message)}</span>
              <span>${escapeHtml(formatDate(item.createdAt))}</span>
            </div>
          `).join('') || '<div class="record-card">No announcements published yet.</div>';

  return `
            <header class="panel-head">
              <span>ID System Announcements</span>
              <span>${escapeHtml(items.length)} published</span>
            </header>
            <div class="panel-body">
              <div class="notice">Announcements from the municipal admin about ID release schedules, renewals, and service advisories.</div>
              <div class="record-list scrollable-list">${cards}</div>
            </div>
          `;
}

function renderUserDashboardPrimary() {
  const record = getUserRecord();
  const requestCount = state.requests.filter((item) => item.userId === record?.id).length;
  return `
            <header class="panel-head">
              <span>My Dashboard Overview</span>
              <span>${escapeHtml(requestCount)} active request(s)</span>
            </header>
            <div class="panel-body">
              <div class="activity-strip">
                <div class="activity-card"><span class="small-label">Current Status</span><span class="large-value">${escapeHtml(record?.status ?? 'N/A')}</span><span class="helper-text">Your latest registration status.</span></div>
                <div class="activity-card"><span class="small-label">Valid Until</span><span class="large-value">${escapeHtml(record?.validity ?? 'N/A')}</span><span class="helper-text">Keep your ID updated before expiry.</span></div>
                <div class="activity-card"><span class="small-label">Requests</span><span class="large-value">${escapeHtml(requestCount)}</span><span class="helper-text">Submitted requests and pending updates.</span></div>
              </div>
              <div class="notice">Use sidebar menus to open full Profile, ID Details, History, and Announcements pages.</div>
            </div>
          `;
}

function renderUserDashboardSecondary() {
  const record = getUserRecord();
  const recent = state.requests
    .filter((item) => item.userId === record?.id)
    .slice()
    .reverse()
    .slice(0, 5);

  const cards = recent.map((item) => `
            <div class="record-card">
              <strong>${escapeHtml(item.subject)}</strong>
              <span>Status: ${escapeHtml(item.status)}</span>
              <span>${escapeHtml(formatDate(item.createdAt))}</span>
            </div>
          `).join('') || '<div class="record-card">No recent activity yet.</div>';

  return `
            <header class="panel-head">
              <span>Recent Activity</span>
              <span>Latest updates</span>
            </header>
            <div class="panel-body">
              <div class="record-list scrollable-list">${cards}</div>
            </div>
          `;
}

function renderUserProfileSecondary() {
  const record = getUserRecord();
  return `
            <header class="panel-head">
              <span>Profile Guidance</span>
              <span>What you can update</span>
            </header>
            <div class="panel-body">
              <div class="notice">Need assistance? Use the available menu items to review your profile, ID details, history, or announcements.</div>
              <div class="record-card">
                <strong>Current Contact</strong>
                <span>${escapeHtml(record?.contact ?? 'N/A')}</span>
              </div>
              <div class="record-card">
                <strong>Current Barangay</strong>
                <span>${escapeHtml(record?.barangay ?? 'N/A')}</span>
              </div>
            </div>
          `;
}

function renderUserAccountSettingsPrimary() {
  const record = getUserRecord();
  return `
            <header class="panel-head">
              <span>Account Settings</span>
              <span>Username and password only</span>
            </header>
            <div class="panel-body">
              <div class="notice" style="margin-bottom: 16px;">Update your login credentials here. Leave password blank if you only want to change your username.</div>
              <div class="record-card">
                <strong>${escapeHtml(record?.firstName ?? 'User')} ${escapeHtml(record?.lastName ?? '')}</strong>
                <span>Fisherfolk ID: ${escapeHtml(record?.id ?? 'N/A')}</span>
                <span>Current Username: ${escapeHtml(record?.username ?? 'N/A')}</span>
              </div>
              <form id="userAccountForm" onsubmit="event.preventDefault(); window.handleUserAccountSave && window.handleUserAccountSave()" style="margin-top: 18px;">
                <div class="form-grid">
                  <div class="field-group full"><label class="field-label" for="uaUsername">Username</label><input class="input-field" id="uaUsername" name="username" value="${escapeHtml(record?.username || '')}" autocomplete="off" /></div>
                  <div class="field-group full"><label class="field-label" for="uaPassword">Password</label><input class="input-field" id="uaPassword" name="password" type="password" placeholder="Leave blank to keep current password" autocomplete="new-password" /></div>
                </div>
                <div class="button-row" style="margin-top: 12px;">
                  <button class="btn primary" type="submit">Save Account</button>
                </div>
              </form>
            </div>
          `;
}

function renderUserAccountSettingsSecondary() {
  return `
            <header class="panel-head">
              <span>Security Tips</span>
              <span>Account safety</span>
            </header>
            <div class="panel-body">
              <div class="record-card"><strong>Username</strong><span>Use a unique username that you can remember easily.</span></div>
              <div class="record-card"><strong>Password</strong><span>Choose a strong password and change it regularly.</span></div>
              <div class="notice">If you do not type a new password, your current password stays unchanged.</div>
            </div>
          `;
}

function renderUserIdSecondary() {
  const record = getUserRecord();
  return `
            <header class="panel-head">
              <span>ID Status & Info</span>
              <span>Registration details</span>
            </header>
            <div class="panel-body">
              <div class="notice">Your Fisherfolk ID design and layout are managed by the municipal admin. Below are your current ID details.</div>
              <div class="record-list">
                <div class="record-card">
                  <strong>ID Status</strong>
                  <span>${escapeHtml(record?.status || 'Pending')}</span>
                </div>
                <div class="record-card">
                  <strong>Validity / Expiry Date</strong>
                  <span>${escapeHtml(record?.validity || 'N/A')}</span>
                </div>
              </div>
              <div class="notice">If your ID is expired or expiring soon, please visit the municipal office or contact your barangay official.</div>
            </div>
          `;
}

function renderAdminDirectorySummary() {
  const records = filteredRecords();
  const grouped = ['Active', 'Pending', 'Expired'].map((status) => {
    const count = records.filter((item) => item.status === status).length;
    return `<div class="record-card"><strong>${escapeHtml(status)}</strong><span>${escapeHtml(count)} record(s)</span></div>`;
  }).join('');

  return `
            <header class="panel-head">
              <span>Directory Summary</span>
              <span>${escapeHtml(records.length)} shown</span>
            </header>
            <div class="panel-body">
              <div class="record-list scrollable-list">${grouped}</div>
              <div class="notice">Use search to filter by name, ID, or barangay.</div>
            </div>
          `;
}

function renderAdminPrintingQueue() {
  const queue = state.records.filter((item) => item.status === 'Pending' || item.status === 'Active');
  const allSelected = queue.length > 0 && queue.every(item => state.selectedQueueIds.includes(item.id));
  const selectedCount = state.selectedQueueIds.length;

  const rows = queue.map((item) => {
    const isSelected = state.selectedQueueIds.includes(item.id);
    return `
            <tr class="${isSelected ? 'selected' : ''}">
              <td style="width: 40px; text-align: center;">
                <input type="checkbox" class="queue-checkbox" data-id="${escapeHtml(item.id)}" ${isSelected ? 'checked' : ''} style="width: 18px; height: 18px; cursor: pointer;">
              </td>
              <td><strong>${escapeHtml(item.id)}</strong></td>
              <td>${escapeHtml(item.firstName)} ${escapeHtml(item.lastName)}</td>
              <td>${escapeHtml(item.barangay)}</td>
              <td><span class="badge ${item.status.toLowerCase()}">${escapeHtml(item.status)}</span></td>
              <td>${escapeHtml(item.validity)}</td>
              <td>
                <div class="button-row" style="margin:0; justify-content: flex-start; gap: 8px;">
                  <button class="btn sm primary" type="button" data-admin-action="print-id" data-id="${escapeHtml(item.id)}" style="padding: 5px 10px; font-size: 11px;">Print</button>
                  <button class="btn sm secondary" type="button" data-admin-action="expire-id" data-id="${escapeHtml(item.id)}" style="padding: 5px 10px; font-size: 11px;">Expire</button>
                </div>
              </td>
            </tr>
          `;
  }).join('') || '<tr><td colspan="7" style="text-align:center; padding: 20px;">No records in printing queue.</td></tr>';

  const bulkActionBar = selectedCount > 0 ? `
    <div class="bulk-action-bar" style="background: linear-gradient(90deg, rgba(44, 106, 162, 0.2), rgba(24, 124, 120, 0.1)); border: 1px solid rgba(44, 106, 162, 0.4); border-radius: 12px; padding: 14px 24px; margin: 0 15px 15px; display: flex; justify-content: space-between; align-items: center; gap: 20px; animation: globalFadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);">
      <div style="font-weight: 600; color: #fff; font-size: 14px; display: flex; align-items: center;">
        <span style="background: var(--color-primary); color: #fff; padding: 4px 10px; border-radius: 6px; margin-right: 12px; font-size: 13px; box-shadow: 0 2px 8px rgba(44, 106, 162, 0.4);">${selectedCount}</span>
        Fisherfolk record(s) selected
      </div>
      <div class="button-row" style="margin:0; gap: 12px; flex-shrink: 0;">
        <button class="btn primary" type="button" data-admin-action="bulk-print-id" style="height: 40px; padding: 0 20px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap !important; min-width: max-content !important;">Print Selected IDs</button>
        <button class="btn" type="button" data-admin-action="clear-queue-selection" style="height: 40px; padding: 0 16px; font-size: 12px; font-weight: 600; background: rgba(255,255,255,0.05) !important; color: rgba(255,255,255,0.8) !important; border: 1px solid rgba(255,255,255,0.15) !important; text-transform: uppercase; white-space: nowrap !important;">Clear</button>
      </div>
    </div>
  ` : '';

  return `
            <header class="panel-head">
              <span>ID Printing Queue</span>
              <span>${escapeHtml(queue.length)} record(s)</span>
            </header>
            <div class="panel-body" style="padding:0;">
              <div class="notice" style="margin: 15px;">Queue includes active IDs for reprint and pending IDs for first release.</div>
              ${bulkActionBar}
              <div class="registry-table-wrap" style="max-height: 600px; overflow-y: auto; margin: 0; width: 100% !important;">
                <table class="registry-table" style="width: 100%; border-collapse: collapse;">
                  <thead style="position: sticky; top: 0; z-index: 10;">
                    <tr>
                      <th style="width: 40px; text-align: center;">
                        <input type="checkbox" id="selectAllQueue" ${allSelected ? 'checked' : ''} style="width: 18px; height: 18px; cursor: pointer;" title="Select All">
                      </th>
                      <th>ID No.</th>
                      <th>Name</th>
                      <th>Barangay</th>
                      <th>Status</th>
                      <th>Validity</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${rows}
                  </tbody>
                </table>
              </div>
            </div>
          `;
}

function buildReportsChartMarkup() {
  const monthCount = 6;
  const monthFormatter = new Intl.DateTimeFormat('en-US', { month: 'short' });
  const now = new Date();
  const buckets = [];

  for (let index = monthCount - 1; index >= 0; index -= 1) {
    const bucketDate = new Date(now.getFullYear(), now.getMonth() - index, 1);
    const key = `${bucketDate.getFullYear()}-${String(bucketDate.getMonth() + 1).padStart(2, '0')}`;
    buckets.push({
      key,
      label: monthFormatter.format(bucketDate),
      registrations: 0,
      approvals: 0,
      releases: 0
    });
  }

  const bucketIndexByKey = new Map(buckets.map((bucket, index) => [bucket.key, index]));
  const keyFromDate = (value) => {
    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) return '';
    return `${parsed.getFullYear()}-${String(parsed.getMonth() + 1).padStart(2, '0')}`;
  };

  const recordsWithDates = state.records.filter((item) => item.createdAt && !Number.isNaN(new Date(item.createdAt).getTime()));
  if (recordsWithDates.length > 0) {
    recordsWithDates.forEach((item) => {
      const index = bucketIndexByKey.get(keyFromDate(item.createdAt));
      if (index === undefined) return;
      buckets[index].registrations += 1;
      if (item.status === 'Active') buckets[index].releases += 1;
    });
  } else {
    const total = state.records.length;
    state.records.slice().reverse().forEach((item, index) => {
      const bucketIndex = total <= 1
        ? monthCount - 1
        : Math.round((index / (total - 1)) * (monthCount - 1));
      buckets[bucketIndex].registrations += 1;
      if (item.status === 'Active') buckets[bucketIndex].releases += 1;
    });
  }

  state.requests.forEach((item) => {
    const index = bucketIndexByKey.get(keyFromDate(item.createdAt));
    if (index === undefined) return;
    if (item.status === 'Approved') {
      buckets[index].approvals += 1;
    }
  });

  const maxValue = Math.max(
    1,
    ...buckets.map((item) => Math.max(item.registrations, item.approvals, item.releases))
  );

  const chartWidth = 720;
  const chartHeight = 280;
  const leftPad = 44;
  const rightPad = 18;
  const topPad = 18;
  const bottomPad = 44;
  const innerWidth = chartWidth - leftPad - rightPad;
  const innerHeight = chartHeight - topPad - bottomPad;
  const groupWidth = innerWidth / buckets.length;
  const barWidth = Math.max(12, Math.min(22, groupWidth * 0.32));

  const yFromValue = (value) => topPad + innerHeight - ((value / maxValue) * innerHeight);
  const xCenter = (index) => leftPad + (groupWidth * index) + (groupWidth / 2);

  // Generate unique integer ticks for Y-axis
  const tickSteps = maxValue < 5 ? maxValue : 4;
  const gridLines = [];
  for (let i = 0; i <= tickSteps; i++) {
    const value = Math.round((maxValue / tickSteps) * i);
    // Avoid duplicate labels for small maxValues
    if (i > 0 && value === Math.round((maxValue / tickSteps) * (i - 1)) && maxValue < 5) continue;

    const y = yFromValue(value);
    gridLines.push(`
      <line x1="${leftPad}" y1="${y.toFixed(2)}" x2="${chartWidth - rightPad}" y2="${y.toFixed(2)}" class="chart-grid" />
      <text x="${leftPad - 10}" y="${(y + 4).toFixed(2)}" class="chart-axis-label chart-axis-tick">${escapeHtml(value)}</text>
    `);
  }

  const bars = buckets.map((item, index) => {
    const barHeight = (item.registrations / maxValue) * innerHeight;
    const x = xCenter(index) - (barWidth / 2);
    const y = yFromValue(item.registrations);
    return `
              <rect x="${x.toFixed(2)}" y="${y.toFixed(2)}" width="${barWidth.toFixed(2)}" height="${barHeight.toFixed(2)}" rx="4" class="chart-bar" />
            `;
  }).join('');

  const approvalsPath = buckets.map((item, index) => `${xCenter(index).toFixed(2)},${yFromValue(item.approvals).toFixed(2)}`).join(' ');
  const releasesPath = buckets.map((item, index) => `${xCenter(index).toFixed(2)},${yFromValue(item.releases).toFixed(2)}`).join(' ');

  const approvalsDots = buckets.map((item, index) => `
            <circle cx="${xCenter(index).toFixed(2)}" cy="${yFromValue(item.approvals).toFixed(2)}" r="3" class="chart-dot chart-dot-approval" />
          `).join('');

  const releasesDots = buckets.map((item, index) => `
            <circle cx="${xCenter(index).toFixed(2)}" cy="${yFromValue(item.releases).toFixed(2)}" r="3" class="chart-dot chart-dot-release" />
          `).join('');

  const monthLabels = buckets.map((item, index) => `
            <text x="${xCenter(index).toFixed(2)}" y="${chartHeight - 12}" class="chart-axis-label chart-month-label">${escapeHtml(item.label)}</text>
          `).join('');

  const getTrendSummary = (series) => {
    const firstNonZeroIndex = series.findIndex((value) => value > 0);
    let lastNonZeroIndex = -1;
    for (let index = series.length - 1; index >= 0; index -= 1) {
      if (series[index] > 0) {
        lastNonZeroIndex = index;
        break;
      }
    }

    const start = firstNonZeroIndex >= 0 ? series[firstNonZeroIndex] : 0;
    const end = lastNonZeroIndex >= 0 ? series[lastNonZeroIndex] : 0;
    const direction = end > start ? 'upward' : (end < start ? 'downward' : 'steady');
    const percent = start > 0 ? Math.round(((end - start) / start) * 100) : (end > 0 ? 100 : 0);
    const label = direction === 'upward'
      ? `Up ${percent}%`
      : direction === 'downward'
        ? `Down ${Math.abs(percent)}%`
        : 'Steady';

    return { start, end, direction, percent, label };
  };

  const recordTrendSeries = buckets.map((item) => item.registrations);
  const requestTrendSeries = buckets.map((item) => item.approvals);
  const recordTrend = getTrendSummary(recordTrendSeries);
  const requestTrend = getTrendSummary(requestTrendSeries);
  const overallSeries = buckets.map((item) => item.registrations + item.approvals + item.releases);
  const peakActivity = Math.max(...overallSeries);
  const activeMonths = overallSeries.filter((value) => value > 0).length;

  const recordsTrendPath = recordTrendSeries
    .map((value, index) => `${xCenter(index).toFixed(2)},${yFromValue(value).toFixed(2)}`)
    .join(' ');
  const requestsTrendPath = requestTrendSeries
    .map((value, index) => `${xCenter(index).toFixed(2)},${yFromValue(value).toFixed(2)}`)
    .join(' ');

  const recordsTrendDots = recordTrendSeries.map((value, index) => `
            <circle cx="${xCenter(index).toFixed(2)}" cy="${yFromValue(value).toFixed(2)}" r="2.5" class="chart-dot chart-dot-record-trend" />
          `).join('');
  const requestsTrendDots = requestTrendSeries.map((value, index) => `
            <circle cx="${xCenter(index).toFixed(2)}" cy="${yFromValue(value).toFixed(2)}" r="2.5" class="chart-dot chart-dot-request-trend" />
          `).join('');

  return `
            <div class="trend-summary" aria-label="Trend summary for the last six months">
              <div class="trend-card" style="border-left: 4px solid var(--accent);">
                <div class="trend-label">Records trend</div>
                <div class="trend-value">${escapeHtml(recordTrend.label)}</div>
              </div>
              <div class="trend-card" style="border-left: 4px solid #00d28f;">
                <div class="trend-label">Requests trend</div>
                <div class="trend-value">${escapeHtml(requestTrend.label)}</div>
              </div>
              <div class="trend-card" style="border-left: 4px solid #ff9a2f;">
                <div class="trend-label">Peak activity</div>
                <div class="trend-value">${escapeHtml(String(peakActivity))}</div>
              </div>
              <div class="trend-card" style="border-left: 4px solid #9b6bff;">
                <div class="trend-label">Active months</div>
                <div class="trend-value">${escapeHtml(String(activeMonths))}</div>
              </div>
            </div>
            <div class="chart performance-chart" role="img" aria-label="Monthly performance chart for registrations, approved requests, active releases, and trend over the last six months.">
              <div class="chart-title">Monthly Performance (Last 6 Months)</div>
              <svg viewBox="0 0 ${chartWidth} ${chartHeight}" class="chart-svg" aria-hidden="true" focusable="false" preserveAspectRatio="xMidYMid meet">
                ${gridLines.join('')}
                <line x1="${leftPad}" y1="${topPad + innerHeight}" x2="${chartWidth - rightPad}" y2="${topPad + innerHeight}" class="chart-axis-line" />
                ${bars}
                <polyline points="${approvalsPath}" class="chart-line chart-line-approval" />
                <polyline points="${releasesPath}" class="chart-line chart-line-release" />
                <polyline points="${recordsTrendPath}" class="chart-line chart-line-record-trend" />
                <polyline points="${requestsTrendPath}" class="chart-line chart-line-request-trend" />
                ${approvalsDots}
                ${releasesDots}
                ${recordsTrendDots}
                ${requestsTrendDots}
                ${monthLabels}
              </svg>
              <div class="chart-legend">
                <span><i class="legend-swatch legend-bar"></i>Registrations</span>
                <span><i class="legend-swatch legend-approval"></i>Approvals</span>
                <span><i class="legend-swatch legend-release"></i>Releases</span>
                <span><i class="legend-swatch legend-record-trend"></i>Record Trend</span>
                <span><i class="legend-swatch legend-request-trend"></i>Request Trend</span>
              </div>
            </div>
          `;
}

function renderAdminReports() {
  const active = state.records.filter((item) => item.status === 'Active').length;
  const pending = state.records.filter((item) => item.status === 'Pending').length;
  const expired = state.records.filter((item) => item.status === 'Expired').length;
  const totalRequests = state.requests.length;

  // 1. Fisherfolk Records Table Rows
  const recordRows = state.records.slice().reverse().map(r => `
    <tr>
      <td>${escapeHtml(r.id)}</td>
      <td><strong>${escapeHtml(r.firstName)} ${escapeHtml(r.lastName)}</strong></td>
      <td>${escapeHtml(r.barangay)}</td>
      <td>${escapeHtml(r.status)}</td>
      <td>${r.createdAt ? formatDate(r.createdAt) : 'N/A'}</td>
    </tr>
  `).join('') || '<tr><td colspan="5" style="text-align: center; padding: 20px;">No fisherfolk records found.</td></tr>';

  // 2. System Requests Table Rows
  const requestRows = state.requests.slice().reverse().map(req => {
    const fId = req.userId || req.fisherfolk_id;
    const fisherfolk = getRecordById(fId);
    const name = fisherfolk ? `${fisherfolk.firstName} ${fisherfolk.lastName}` : 'Unknown';
    const reqType = req.type || req.request_type || 'ID Request';
    return `
      <tr>
        <td>${escapeHtml(req.id)}</td>
        <td>${escapeHtml(reqType)}</td>
        <td>${escapeHtml(name)}</td>
        <td><span class="status-badge ${String(req.status || 'pending').toLowerCase()}">${escapeHtml(req.status || 'Pending')}</span></td>
        <td>${req.createdAt ? formatDate(req.createdAt) : 'N/A'}</td>
      </tr>
    `;
  }).join('') || '<tr><td colspan="5" style="text-align: center; padding: 20px;">No requests found.</td></tr>';

  return `
            <header class="panel-head">
              <span>Master Registry</span>
              <span>Comprehensive System Records</span>
            </header>
            <div class="panel-body" style="${window.innerWidth <= 1024 ? 'padding: 8px !important; display: block !important;' : 'padding: 15px !important; display: block !important;'}">
              <div class="kpi-grid analytics-kpi-grid" id="fimsGridUltimate" style="display: block !important; width: 100% !important; overflow: hidden !important; margin: 5px 0 !important;">
                ${(() => {
      const isMob = window.innerWidth <= 1024;
      const cards = [
        { label: 'Active IDs', value: active },
        { label: 'Pending', value: pending },
        { label: 'Expired', value: expired },
        { label: 'Requests', value: totalRequests }
      ];
      return cards.map(c => `
                    <article class="kpi" style="float: left !important; 
                      width: ${isMob ? '48%' : '24%'} !important; 
                      height: ${isMob ? '38px' : '60px'} !important; 
                      margin: ${isMob ? '1%' : '0.5%'} !important; 
                      padding: ${isMob ? '2px 8px' : '0 15px'} !important; 
                      border-left: ${isMob ? '3px' : '5px'} solid #0af0ff !important; 
                      background: rgba(10, 240, 255, 0.1) !important; 
                      border-radius: 6px !important; 
                      box-sizing: border-box !important; 
                      display: flex !important; 
                      flex-direction: ${isMob ? 'column' : 'row'} !important; 
                      justify-content: ${isMob ? 'center' : 'space-between'} !important; 
                      align-items: center !important;">
                      <div class="label" style="font-size: ${isMob ? '0.45rem' : '0.75rem'} !important; color: #0af0ff !important; text-transform: uppercase !important; font-weight: 800 !important; line-height: 1 !important; margin: 0 !important;">${c.label}</div>
                      <div class="value" style="font-size: ${isMob ? '0.85rem' : '1.3rem'} !important; font-weight: 800 !important; color: #fff !important; line-height: 1 !important; margin: 0 !important;">${escapeHtml(c.value)}</div>
                    </article>
                  `).join('');
    })()}
              </div>


              <div class="button-row analytics-button-row" style="margin-bottom: 24px;">
                <button class="btn primary" type="button" data-admin-action="print-records-report">Print Full Registry</button>
                <button class="btn primary" type="button" data-admin-action="print-requests-report">Print All Requests</button>
              </div>

              <div class="report-section" style="margin-bottom: 30px;">
                <h4 class="section-title" style="font-size: 14px; color: #0af0ff; margin-bottom: 12px; text-transform: uppercase;">Detailed Fisherfolk Registry Report</h4>
                <div class="table-wrap" style="max-height: 400px; overflow-y: auto;">
                  <table class="registry-table">
                    <thead style="position: sticky; top: 0; z-index: 10;">
                      <tr>
                        <th>ID No.</th>
                        <th>Full Name</th>
                        <th>Barangay</th>
                        <th>Status</th>
                        <th>Date Registered</th>
                      </tr>
                    </thead>
                    <tbody>${recordRows}</tbody>
                  </table>
                </div>
              </div>

              <div class="report-section">
                <h4 class="section-title" style="font-size: 14px; color: #0af0ff; margin-bottom: 12px; text-transform: uppercase;">System Requests & Activity Report</h4>
                <div class="table-wrap" style="max-height: 400px; overflow-y: auto;">
                  <table class="registry-table">
                    <thead style="position: sticky; top: 0; z-index: 10;">
                      <tr>
                        <th>Request ID</th>
                        <th>Type</th>
                        <th>Fisherfolk</th>
                        <th>Status</th>
                        <th>Date Submitted</th>
                      </tr>
                    </thead>
                    <tbody>${requestRows}</tbody>
                  </table>
                </div>
              </div>
            </div>
          `;
}

function renderAdminReportedPosts() {
  const reports = state.reportedPosts || [];
  const rows = reports.map(r => `
    <tr>
      <td>${escapeHtml(r.author_name)}</td>
      <td>
        <div style="max-width: 300px; white-space: normal; word-break: break-word;">
            ${escapeHtml(r.content)}
            ${r.image_path ? `<br><img src="${escapeHtml(r.image_path)}" style="max-width: 100px; margin-top: 5px; border-radius: 4px;">` : ''}
        </div>
      </td>
      <td>${escapeHtml(r.reporter_name)}</td>
      <td>${escapeHtml(r.reason)}</td>
      <td>${formatDate(r.created_at)}</td>
      <td>
        <div class="button-row" style="gap: 8px; flex-direction: row; flex-wrap: nowrap; justify-content: flex-start;">
            <button class="btn" onclick="handleAdminAction('delete-reported-post', '${r.post_id}', '${r.report_id}')" 
                style="background: linear-gradient(135deg, #ff4b2b, #ff416c); color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(255, 75, 43, 0.3); text-transform: uppercase; letter-spacing: 0.5px;">
                <span style="font-size: 14px;">🗑️</span> Delete
            </button>
            <button class="btn" onclick="handleAdminAction('dismiss-report', '${r.report_id}')" 
                style="background: linear-gradient(135deg, #0af0ff, #00d4ff); color: #051421; border: none; padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(10, 240, 255, 0.3); text-transform: uppercase; letter-spacing: 0.5px;">
                <span style="font-size: 14px;">✅</span> Dismiss
            </button>
        </div>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="6" style="text-align: center; padding: 20px;">No reported posts found.</td></tr>';

  return `
    <header class="panel-head">
      <span>🚩 Reported Community Posts</span>
      <span>${reports.length} reports pending</span>
    </header>
    <div class="panel-body">
      <div class="table-wrap">
        <table class="registry-table">
          <thead>
            <tr>
              <th>Author</th>
              <th>Content</th>
              <th>Reporter</th>
              <th>Reason</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>
      </div>
      <div class="notice" style="margin-top: 15px;">Deleting a post will permanently remove it from the community feed. Dismissing a report removes the flag from this list.</div>
    </div>
  `;
}

function renderAdminSubsidyReportsPrimary() {
  // Fix: Handle both 'type' (JS state) and 'request_type' (PHP/Database)
  const reports = state.requests.filter((item) => {
    const t = String(item.type || item.request_type || '').toLowerCase();
    const s = String(item.subject || '').toLowerCase();
    const b = String(item.boatName || item.boat_name || '').toLowerCase();
    // Catch anything that looks like a subsidy report
    return t.includes('subsidy') || s.includes('subsidy') || s.includes('damage') || b !== '';
  }).slice().reverse();

  // Calculate status counts for the summary strip
  const pending = reports.filter((item) => (item.status || 'Pending') === 'Pending').length;
  const review = reports.filter((item) => item.status === 'Under Review').length;
  const approved = reports.filter((item) => item.status === 'Approved').length;
  const rejected = reports.filter((item) => item.status === 'Rejected').length;

  const rows = reports.map((item) => {
    const fId = item.userId || item.fisherfolk_id;
    const fisherfolk = getRecordById(fId);
    const fisherfolkName = item.fisherfolkName || (fisherfolk ? `${fisherfolk.firstName} ${fisherfolk.lastName}` : 'Unknown');
    const currentStatus = item.status || 'Pending';
    return `
              <tr class="admin-record-row">
                <td>
                  <strong>${escapeHtml(item.id)}</strong>
                  <small style="display: block; opacity: 0.6; font-size: 10px;">ID: ${escapeHtml(fId || 'N/A')}</small>
                </td>
                <td>
                  <div style="font-weight: 700;">${escapeHtml(fisherfolkName)}</div>
                  <div style="font-size: 11px; opacity: 0.8;">${escapeHtml(item.contactPreference || 'Any contact')}</div>
                </td>
                <td>
                  <div style="font-weight: 600; color: #0af0ff;">${escapeHtml(item.boatName || 'N/A')}</div>
                  <small style="display: block; opacity: 0.7;">${escapeHtml(item.boatType || '')} • ${escapeHtml(item.boatColor || '')}</small>
                </td>
                <td>${escapeHtml(item.boatSize || 'N/A')}</td>
                <td>${escapeHtml(item.incidentDate || 'N/A')}</td>
                <td style="color: #0af0ff; font-weight: 700;">₱${escapeHtml(item.damageCost || '0')}</td>
                <td>
                  <select class="input-field subsidy-status-select" data-subsidy-status-select data-id="${escapeHtml(item.id)}" style="min-width: 130px; font-size: 12px;">
                    <option value="Pending" ${currentStatus === 'Pending' ? 'selected' : ''}>Pending</option>
                    <option value="Under Review" ${currentStatus === 'Under Review' ? 'selected' : ''}>Under Review</option>
                    <option value="Approved" ${currentStatus === 'Approved' ? 'selected' : ''}>Approved</option>
                    <option value="Rejected" ${currentStatus === 'Rejected' ? 'selected' : ''}>Rejected</option>
                  </select>
                </td>
                <td style="min-width: 200px;">
                  <input class="input-field subsidy-note-input" data-subsidy-note-input data-id="${escapeHtml(item.id)}" value="${escapeHtml(item.adminNote || '')}" placeholder="Admin note..." style="font-size: 12px; width: 100%;" />
                </td>
                <td>
                  <button class="btn primary" type="button" data-subsidy-note-save data-id="${escapeHtml(item.id)}" style="padding: 8px 16px; font-size: 12px;">Save</button>
                </td>
              </tr>
            `;
  }).join('') || '<tr><td colspan="9" style="padding: 30px; text-align: center;">No subsidy reports submitted yet.</td></tr>';

  return `
            <header class="panel-head">
              <span>Boat Damage Subsidy Reports</span>
              <span>${escapeHtml(reports.length)} total report(s)</span>
            </header>
            <div class="panel-body">
              <div class="subsidy-metric-strip" id="subsidyGridBoxed" style="display: grid; width: 100%; gap: 10px; margin-bottom: 25px; ${window.innerWidth <= 1024 ? 'grid-template-columns: 1fr 1fr;' : 'grid-template-columns: repeat(4, 1fr);'}">
                ${(() => {
      const isMob = window.innerWidth <= 1024;
      const items = [
        { label: 'Pending', val: pending, color: '#0af0ff' },
        { label: 'Under Review', val: review, color: '#35a7b8' },
        { label: 'Approved', val: approved, color: 'var(--success)' },
        { label: 'Rejected', val: rejected, color: 'var(--danger)' }
      ];
      return items.map(i => `
                    <div class="record-card" style="display: flex !important; 
                      flex-direction: column !important; 
                      justify-content: center !important; 
                      align-items: center !important; 
                      height: ${isMob ? '65px' : '90px'} !important; 
                      padding: 10px !important; 
                      border-left: 4px solid ${i.color} !important; 
                      background: rgba(10, 240, 255, 0.05) !important; 
                      margin: 0 !important;
                      text-align: center !important;
                      box-sizing: border-box !important;">
                      <strong style="font-size: ${isMob ? '1.4rem' : '1.8rem'}; color: #fff; line-height: 1; margin-bottom: 2px;">${i.val}</strong>
                      <span style="text-transform: uppercase; font-size: ${isMob ? '9px' : '11px'}; font-weight: 800; color: #0af0ff; letter-spacing: 0.5px;">${i.label}</span>
                    </div>
                  `).join('');
    })()}
              </div>

              <div class="notice" style="margin-bottom: 20px;">Review each report, update status, and add admin notes for fisherfolk follow-up. Use full-width view for efficient processing.</div>
              <div class="table-wrap registry-table-wrap" style="max-height: 600px; overflow-y: auto;">
                <table class="registry-table">
                  <thead>
                    <tr>
                      <th>Ref ID</th>
                      <th>Fisherfolk</th>
                      <th>Boat Details</th>
                      <th>Size</th>
                      <th>Date</th>
                      <th>Cost</th>
                      <th>Status</th>
                      <th>Admin Note</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${rows}
                  </tbody>
                </table>
              </div>
            </div>
          `;
}

function renderAdminSubsidyReportsSecondary() {
  const reports = state.requests.filter((item) => {
    const t = String(item.type || item.request_type || '').toLowerCase();
    const s = String(item.subject || '').toLowerCase();
    const b = String(item.boatName || item.boat_name || '').toLowerCase();
    return t.includes('subsidy') || s.includes('subsidy') || s.includes('damage') || b !== '';
  });
  const pending = reports.filter((item) => (item.status || 'Pending') === 'Pending').length;
  const review = reports.filter((item) => item.status === 'Under Review').length;
  const approved = reports.filter((item) => item.status === 'Approved').length;
  const rejected = reports.filter((item) => item.status === 'Rejected').length;

  return `
            <header class="panel-head">
              <span>Subsidy Processing Summary</span>
              <span>Evaluation status</span>
            </header>
            <div class="panel-body">
              <div class="record-card"><strong>Pending</strong><span>${escapeHtml(pending)} report(s)</span></div>
              <div class="record-card"><strong>Under Review</strong><span>${escapeHtml(review)} report(s)</span></div>
              <div class="record-card"><strong>Approved</strong><span>${escapeHtml(approved)} report(s)</span></div>
              <div class="record-card"><strong>Rejected</strong><span>${escapeHtml(rejected)} report(s)</span></div>
              <div class="notice">Use Export Requests CSV in Reports & Analytics for compliance records and audit submissions.</div>
            </div>
          `;
}

function renderAdminSettings() {
  const rows = barangayOptions.map((item) => {
    const residentCount = state.records.filter(r => r.barangay === item).length;
    return `
            <tr class="admin-table-row">
              <td style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.2); color: #fff !important;">
                <strong>${escapeHtml(item)}</strong>
              </td>
              <td style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.2); color: #0af0ff !important; text-align: center;">
                <strong>${residentCount}</strong>
              </td>
              <td style="padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.2); text-align: right;">
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                  <button class="btn" type="button" data-view-barangay-residents="${escapeHtml(item)}" style="padding: 6px 12px; font-size: 0.8rem; background: rgba(0, 212, 255, 0.2) !important; color: #0af0ff !important; border: 1px solid rgba(0, 212, 255, 0.4) !important;">View</button>
                  <button class="btn danger" type="button" data-archive-barangay="${escapeHtml(item)}" style="padding: 6px 12px; font-size: 0.8rem; background: #dc3545 !important; color: #fff !important; border: none !important;">Archive</button>
                </div>
              </td>
            </tr>
          `;
  }).join('');

  return `
            <header class="panel-head">
              <span>Settings & Manage Barangays</span>
              <span>Workspace controls</span>
            </header>
            <div class="panel-body" style="background: linear-gradient(180deg, rgba(8, 33, 56, 0.94), rgba(5, 24, 43, 0.95)) !important;">
              
              <div class="settings-top-actions" style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
                <button class="btn" type="button" onclick="openAdminAccountSettingsModal()" style="background: rgba(0, 212, 255, 0.1); border: 1px solid rgba(0, 212, 255, 0.3); color: #0af0ff;">⚙️ Account Settings</button>
              </div>

              <div class="record-list" style="background: rgba(0, 18, 34, 0.4) !important; padding: 25px; border-radius: 12px; border: 1px solid rgba(0, 212, 255, 0.1) !important;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                  <h4 style="margin: 0; color: #fff; font-size: 1.2rem;">Manage Barangays</h4>
                  <button class="btn primary" type="button" onclick="openAddBarangayModal()">+ Add Barangay</button>
                </div>
                <div class="table-wrap settings-table-wrap" style="background: #001a33 !important; border: 2px solid #00d4ff !important; border-radius: 8px; overflow: hidden; min-height: 400px;">
                  <table style="width: 100%; border-collapse: collapse;">
                    <thead style="position: sticky; top: 0; background: #0a1e2b; z-index: 1; border-bottom: 2px solid #00d4ff;">
                      <tr>
                        <th style="padding: 15px; text-align: left; color: #00d4ff; font-size: 0.9rem; text-transform: uppercase; font-weight: bold;">Barangay Name</th>
                        <th style="padding: 15px; text-align: center; color: #00d4ff; font-size: 0.9rem; text-transform: uppercase; font-weight: bold;">Registered Residents</th>
                        <th style="padding: 15px; text-align: right; color: #00d4ff; font-size: 0.9rem; text-transform: uppercase; font-weight: bold;">Actions</th>
                      </tr>
                    </thead>
                    <tbody style="background: #001a33 !important;">
                      ${rows || '<tr><td colspan="3" style="padding: 40px; text-align: center; color: #00d4ff; opacity: 0.7;">No barangays added yet. Click "+ Add Barangay" to get started.</td></tr>'}
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="record-list" style="margin-top: 30px; background: rgba(0, 18, 34, 0.4) !important; padding: 25px; border-radius: 12px; border: 1px solid rgba(0, 212, 255, 0.1) !important; opacity: 0.85;">
                <h4 style="margin: 0 0 15px 0; color: #aaa; font-size: 1rem; text-transform: uppercase; letter-spacing: 1px;">Archived Barangays</h4>
                <div class="table-wrap settings-table-wrap" style="background: rgba(0, 26, 51, 0.6) !important; border: 1px solid rgba(0, 212, 255, 0.2) !important; border-radius: 8px; overflow: hidden;">
                  <table style="width: 100%; border-collapse: collapse;">
                    <tbody style="background: transparent !important;">
                      ${state.archivedBarangays.length ? state.archivedBarangays.map((item) => `
                        <tr class="admin-table-row">
                          <td style="padding: 10px 15px; border-bottom: 1px solid rgba(255,255,255,0.1); color: #aaa !important;">
                            ${escapeHtml(item)}
                          </td>
                          <td style="padding: 10px 15px; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: right;">
                            <button class="btn" type="button" data-restore-barangay="${escapeHtml(item)}" style="padding: 4px 10px; font-size: 0.75rem; background: rgba(40, 167, 69, 0.2) !important; color: #28a745 !important; border: 1px solid rgba(40, 167, 69, 0.4) !important;">Restore</button>
                          </td>
                        </tr>
                      `).join('') : '<tr><td colspan="2" style="padding: 20px; text-align: center; color: #555;">No archived barangays.</td></tr>'}
                    </tbody>
                  </table>
                </div>
              </div>
              <div class="notice" style="margin-top: 25px; font-style: italic; opacity: 0.8;">Note: Residents count reflects active records in the municipal registry.</div>
            </div>
          `;
}

function getFocusedAdminRecord(records = filteredRecords()) {
  if (!records.length) return null;
  const selectedId = String(state.selectedAdminRecordId || '');
  const selected = records.find((item) => String(item.id || '') === selectedId);
  if (selected) return selected;
  state.selectedAdminRecordId = records[0].id;
  return records[0];
}

function getRecordInitials(record) {
  const first = String(record?.firstName || '').trim().charAt(0);
  const last = String(record?.lastName || '').trim().charAt(0);
  return `${first}${last}`.trim() || 'FF';
}

function renderAdminEditRecord() {
  const record = getRecordById(state.selectedAdminRecordId);
  if (!record) return '<div class="notice">Record not found.</div>';

  return `
            <div class="admin-register-form-wrapper">
              <header class="panel-head">
                <span>Edit Fisherfolk Record</span>
                <span>${escapeHtml(record.id)}</span>
              </header>
              <form class="admin-edit-form" id="adminEditForm" onsubmit="event.preventDefault(); window.handleAdminEditSubmit && window.handleAdminEditSubmit('${escapeHtml(record.id)}')">
                <div class="form-grid">
                  <h3 style="grid-column: 1/-1; margin-top: 0;">Personal Information</h3>
                  <div class="field-group"><label class="field-label" for="editLastName">Last Name</label><input class="input-field" id="editLastName" name="lastName" value="${escapeHtml(record.lastName || '')}" required /></div>
                  <div class="field-group"><label class="field-label" for="editFirstName">First Name</label><input class="input-field" id="editFirstName" name="firstName" value="${escapeHtml(record.firstName || '')}" required /></div>
                  <div class="field-group"><label class="field-label" for="editMiddleName">Middle Name</label><input class="input-field" id="editMiddleName" name="middleName" value="${escapeHtml(record.middleName || record.middleInitial || '')}" /></div>
                  <div class="field-group full"><label class="field-label" for="editBarangay">Barangay</label><select class="input-field" id="editBarangay" name="barangay" required>
                    <option value="">Select a Barangay</option>
                    ${(window.barangayList || []).map(bg => `<option value="${escapeHtml(bg)}" ${record.barangay === bg ? 'selected' : ''}>${escapeHtml(bg)}</option>`).join('')}
                  </select></div>
                  <div class="field-group"><label class="field-label" for="editBirthDate">Birthday</label><input class="input-field" id="editBirthDate" name="birthDate" type="date" value="${record.birthDate || ''}" /></div>
                  <div class="field-group"><label class="field-label" for="editGender">Gender</label><select class="input-field" id="editGender" name="gender">
                    <option value="">Select</option>
                    <option value="Male" ${record.gender === 'Male' ? 'selected' : ''}>Male</option>
                    <option value="Female" ${record.gender === 'Female' ? 'selected' : ''}>Female</option>
                  </select></div>
                  
                  <h3 style="grid-column: 1/-1; margin-top: 20px;">Livelihood & Contact</h3>
                  <div class="field-group full"><label class="field-label" for="editLivelihood">Livelihood</label><input class="input-field" id="editLivelihood" name="livelihood" value="${escapeHtml(record.livelihood || '')}" /></div>
                  <div class="field-group"><label class="field-label" for="editContact">Contact Number</label><input class="input-field" id="editContact" name="contact" autocomplete="tel" value="${escapeHtml(record.contact || '')}" /></div>
                  <div class="field-group"><label class="field-label" for="editFishrNumber">FishR Number</label><input class="input-field" id="editFishrNumber" name="fishrNumber" value="${escapeHtml(record.fishrNumber || '')}" /></div>
                  <div class="field-group"><label class="field-label" for="editRsbsaNumber">RSBSA Number</label><input class="input-field" id="editRsbsaNumber" name="rsbsaNumber" value="${escapeHtml(record.rsbsaNumber || '')}" /></div>
                  <!-- Account credentials removed: only personal info editable here -->
                  
                  <h3 style="grid-column: 1/-1; margin-top: 20px;">Photo & Signature</h3>
                  <div class="field-group full">
                    <label class="field-label">UPDATE ID PICTURE</label>
                    <div style="display:flex; gap:12px; align-items:center;">
                      <button type="button" class="btn" aria-label="Choose photo" onclick="document.getElementById('editPhotoFile').click();">Choose Photo</button>
                      <span id="editPhotoFileLabel" style="color:#ecf9ff; opacity:1; font-size:14px;" aria-live="polite">${record.photoData ? 'Current photo' : 'No file chosen'}</span>
                      <button type="button" class="btn" style="background:transparent; border:none; color:#fff; opacity:0.7; font-size:0.9rem;" onclick="clearFileInput('editPhotoFile','editPhotoFileLabel','editPhotoThumb')" aria-label="Clear photo selection">Clear</button>
                    </div>
                    <input style="display:none;" class="input-field" id="editPhotoFile" name="photoFile" type="file" accept=".jpg,.jpeg,.jfif,.png" onchange="(validateFileInput(this,'editPhotoFileLabel') && handleFileInputChange(this,'editPhotoFileLabel','editPhotoThumb'))" />
                    <img id="editPhotoThumb" src="${record.photoData ? escapeHtml(record.photoData) : ''}" alt="photo preview" style="${record.photoData ? 'display:block;' : 'display:none;'} margin-top:8px; max-width:120px; border-radius:6px;" />
                    <small style="color: #666; margin-top: 8px; display: block;">Leave empty to keep current photo</small>
                  </div>
                  <div class="field-group full">
                    <label class="field-label">UPDATE SIGNATURE PICTURE</label>
                    <div style="display:flex; gap:12px; align-items:center;">
                      <button type="button" class="btn" aria-label="Choose signature" onclick="document.getElementById('editSignatureFile').click();">Choose Signature</button>
                      <span id="editSignatureFileLabel" style="color:#ecf9ff; opacity:1; font-size:14px;" aria-live="polite">${record.signatureData ? 'Current signature' : 'No file chosen'}</span>
                      <button type="button" class="btn" style="background:transparent; border:none; color:#fff; opacity:0.7; font-size:0.9rem;" onclick="clearFileInput('editSignatureFile','editSignatureFileLabel','editSignatureThumb')" aria-label="Clear signature selection">Clear</button>
                    </div>
                    <input style="display:none;" class="input-field" id="editSignatureFile" name="signatureFile" type="file" accept=".jpg,.jpeg,.jfif,.png" onchange="(validateFileInput(this,'editSignatureFileLabel') && handleFileInputChange(this,'editSignatureFileLabel','editSignatureThumb'))" />
                    <img id="editSignatureThumb" src="${record.signatureData ? escapeHtml(record.signatureData) : ''}" alt="signature preview" style="${record.signatureData ? 'display:block;' : 'display:none;'} margin-top:8px; max-width:160px; border-radius:6px;" />
                    <small style="color: #666; margin-top: 8px; display: block;">Leave empty to keep current signature</small>
                  </div>
                  
                  <h3 style="grid-column: 1/-1; margin-top: 20px;">Emergency Contact</h3>
                  <div class="field-group full"><label class="field-label" for="editEmergencyName">Name</label><input class="input-field" id="editEmergencyName" name="emergencyName" value="${escapeHtml(record.emergencyName || '')}" /></div>
                  <div class="field-group"><label class="field-label" for="editEmergencyRelation">Relation</label><input class="input-field" id="editEmergencyRelation" name="emergencyRelation" value="${escapeHtml(record.emergencyRelation || '')}" /></div>
                  <div class="field-group"><label class="field-label" for="editEmergencyContact">Contact No.</label><input class="input-field" id="editEmergencyContact" name="emergencyContact" value="${escapeHtml(record.emergencyContact || '')}" /></div>
                  <div class="field-group full"><label class="field-label" for="editEmergencyAddress">Complete Address</label><textarea class="input-field" id="editEmergencyAddress" name="emergencyAddress" rows="2">${escapeHtml(record.emergencyAddress || '')}</textarea></div>
                </div>
                <div class="button-row">
                  <button class="btn primary" type="submit">Save Changes</button>
                  <button class="btn" type="button" onclick="(document.getElementById('masterDetailModal') ? closeMasterDetailModal() : (state.currentView = 'dashboard', renderApp()));">Cancel</button>
                </div>
              </form>
            </div>
          `;
}

function renderAdminMasterDetailModal(selected) {
  return `
            <div class="panel-body registry-detail-body">
              <div class="profile-card registry-profile-card">
                <div class="profile-avatar registry-avatar-large">
                  ${selected.photoData ? `<img src="${escapeHtml(selected.photoData)}" alt="${escapeHtml(selected.firstName)} ${escapeHtml(selected.lastName)}" />` : `<span>${escapeHtml(getRecordInitials(selected))}</span>`}
                </div>
                <div class="profile-copy">
                  <strong>${escapeHtml(selected.firstName)} ${escapeHtml(selected.lastName)}</strong>
                  <span>${escapeHtml(selected.id)} • ${escapeHtml(selected.barangay)}</span>
                  <span>${escapeHtml(selected.livelihood || 'Fisherfolk')}</span>
                </div>
              </div>
              <div class="record-list registry-summary-list">
                <div class="record-card"><strong>Contact</strong><span>${escapeHtml(selected.contact || 'Not provided')}</span></div>
                <div class="record-card"><strong>Status</strong><span>${escapeHtml(selected.status)}</span></div>
                <div class="record-card"><strong>Validity</strong><span>${escapeHtml(selected.validity || 'N/A')}</span></div>
              </div>
              
              <div class="registry-id-preview">
                <header class="panel-head" style="display: flex; justify-content: space-between; align-items: center;">
                  <span>ID Card Preview</span>
                  <div style="display: flex; gap: 10px; align-items: center;">
                    <!-- Back preview is shown side-by-side now -->
                    <span style="font-size: 0.75rem; opacity: 0.6; font-weight: 500;">${escapeHtml(selected.id)}</span>
                  </div>
                </header>
                  <div class="panel-body">
                  <div class="id-card-print-wrap registry-id-card-wrap" data-id="${escapeHtml(selected.id)}">
                    <div style="display:flex; gap:18px; align-items:flex-start; justify-content:center; flex-wrap:wrap; width:100%;">
                      <div style="display:flex; align-items:flex-start; justify-content:center;">
                          ${renderIdCardMarkup(selected)}
                      </div>
                      <div style="display:flex; align-items:flex-start; justify-content:center;">
                          ${renderIdCardBackMarkup(selected)}
                      </div>
                    </div>
                  </div>
                    <div class="button-row" style="justify-content: center; margin-top: 16px; gap: 12px; flex-direction: row;">
                      <button class="btn" type="button" style="flex: 0 1 auto; min-width: 140px; margin: 0;" onclick="openAdminEditInModal('${escapeHtml(selected.id)}')">Edit Info</button>
                      <button class="btn" type="button" data-admin-action="download-id" data-id="${escapeHtml(selected.id)}" onclick="window.handleAdminAction && window.handleAdminAction('download-id','${escapeHtml(selected.id)}')" style="flex: 0 1 auto; min-width: 140px; margin: 0;">Download ID</button>
                      <button class="btn primary" type="button" data-admin-action="print-id" data-id="${escapeHtml(selected.id)}" onclick="window.handleAdminAction && window.handleAdminAction('print-id','${escapeHtml(selected.id)}')" style="flex: 0 1 auto; min-width: 140px; margin: 0;">Print ID Card</button>
                    </div>
                </div>
              </div>
            </div>
          `;
}

function renderAdminDashboardSecondary() {
  const selected = getFocusedAdminRecord();
  if (!selected) {
    return `
              <header class="panel-head">
                <span>Registry Detail</span>
                <span>No selection</span>
              </header>
              <div class="panel-body">
                <div class="record-card">No fisherfolk record is available.</div>
              </div>
            `;
  }

  return `
            <header class="panel-head registry-detail-head">
              <div>
                <span>Master Detail</span>
                <span class="detail-subtitle">${escapeHtml(selected.firstName)} ${escapeHtml(selected.lastName)}</span>
              </div>
              <button class="btn master-detail-btn" type="button" data-admin-action="focus-selected" data-id="${escapeHtml(selected.id)}">Master-Detail</button>
            </header>
            <div class="panel-body registry-detail-body">
              <div class="profile-card registry-profile-card">
                <div class="profile-avatar registry-avatar-large">
                  ${selected.photoData ? `<img src="${escapeHtml(selected.photoData)}" alt="${escapeHtml(selected.firstName)} ${escapeHtml(selected.lastName)}" />` : `<span>${escapeHtml(getRecordInitials(selected))}</span>`}
                </div>
                <div class="profile-copy">
                  <strong>${escapeHtml(selected.firstName)} ${escapeHtml(selected.lastName)}</strong>
                  <span>${escapeHtml(selected.id)} • ${escapeHtml(selected.barangay)}</span>
                  <span>${escapeHtml(selected.livelihood || 'Fisherfolk')}</span>
                </div>
              </div>
              <div class="record-list registry-summary-list">
                <div class="record-card"><strong>Contact</strong><span>${escapeHtml(selected.contact || 'Not provided')}</span></div>
                <div class="record-card"><strong>Status</strong><span>${escapeHtml(selected.status)}</span></div>
                <div class="record-card"><strong>Validity</strong><span>${escapeHtml(selected.validity || 'N/A')}</span></div>
              </div>
            <div class="registry-id-preview">
                <header class="panel-head" style="display: flex; justify-content: space-between; align-items: center;">
                  <span>ID Card Preview</span>
                  <div style="display: flex; gap: 10px; align-items: center;">
                    <!-- Back preview is shown side-by-side now -->
                    <span style="font-size: 0.75rem; opacity: 0.6; font-weight: 500;">${escapeHtml(selected.id)}</span>
                  </div>
                </header>
                <div class="panel-body">
                  <div class="id-card-print-wrap registry-id-card-wrap" data-id="${escapeHtml(selected.id)}">
                    ${renderIdCardMarkup(selected)}
                  </div>
                  <div class="button-row" style="justify-content: center; margin-top: 16px; gap: 12px; flex-direction: row;">
                    <button class="btn" type="button" data-admin-action="download-id" data-id="${escapeHtml(selected.id)}" onclick="window.handleAdminAction && window.handleAdminAction('download-id','${escapeHtml(selected.id)}')" style="flex: 0 1 auto; min-width: 140px; margin: 0;">Download ID</button>
                    <button class="btn primary" type="button" data-admin-action="print-id" data-id="${escapeHtml(selected.id)}" onclick="window.handleAdminAction && window.handleAdminAction('print-id','${escapeHtml(selected.id)}')" style="flex: 0 1 auto; min-width: 140px; margin: 0;">Print ID Card</button>
                  </div>
                </div>
              </div>
            </div>
          `;
}

function renderAdminRegisterSecondary() {
  const pending = state.records.filter((item) => item.status === 'Pending').length;
  return `
            <header class="panel-head">
              <span>Registration Guidance</span>
              <span>Intake workflow</span>
            </header>
            <div class="panel-body">
              <div class="record-list">
                <div class="record-card"><strong>Step 1</strong><span>Verify personal details and barangay address.</span></div>
                <div class="record-card"><strong>Step 2</strong><span>Capture complete livelihood and contact information.</span></div>
                <div class="record-card"><strong>Step 3</strong><span>Save, review, then queue for ID generation.</span></div>
              </div>
              <div class="notice">There are currently ${escapeHtml(pending)} pending registration record(s) in the system.</div>
            </div>
          `;
}

function renderAdminQueueSecondary() {
  const pending = state.records.filter((item) => item.status === 'Pending').length;
  const active = state.records.filter((item) => item.status === 'Active').length;
  return `
            <header class="panel-head">
              <span>Queue Controls</span>
              <span>Print readiness</span>
            </header>
            <div class="panel-body">
              <div class="record-card"><strong>Pending for first print</strong><span>${escapeHtml(pending)} record(s)</span></div>
              <div class="record-card"><strong>Eligible for reprint</strong><span>${escapeHtml(active)} active record(s)</span></div>
              <div class="notice">Prioritize pending records to reduce turnaround time for new registrants.</div>
            </div>
          `;
}

function renderAdminReportsSecondary() {
  return `
            <header class="panel-head">
              <span>Report Notes</span>
              <span>Decision support</span>
            </header>
            <div class="panel-body">
              <div class="record-list">
                <div class="record-card"><strong>Use cases</strong><span>Budget planning, barangay workload balancing, and renewal campaigns.</span></div>
                <div class="record-card"><strong>Data cadence</strong><span>Refresh dashboard after every registration or status update for up-to-date reporting.</span></div>
              </div>
            </div>
          `;
}

function renderAdminSettingsSecondary() {
  return `
            <header class="panel-head">
              <span>Policy Reminders</span>
              <span>Governance and access</span>
            </header>
            <div class="panel-body" style="background: linear-gradient(180deg, rgba(8, 33, 56, 0.94), rgba(5, 24, 43, 0.95)) !important;">
              <div class="notice" style="background: rgba(0, 18, 34, 0.4) !important; border: 1px solid rgba(0, 212, 255, 0.1) !important; color: #fff !important; padding: 15px; border-radius: 8px;">Apply least-privilege access, rotate credentials periodically, and keep audit logs for major account changes.</div>
            </div>
          `;
}

function getStatusBadge(status) {
  const statusMap = {
    'Active': { class: 'active', label: 'Active' },
    'Pending': { class: 'pending', label: 'Pending' },
    'Expired': { class: 'expired', label: 'Expired' }
  };
  const config = statusMap[status] || { class: 'pending', label: status };
  return `<span class="status-badge ${config.class}">${config.label}</span>`;
}

function renderAdminPrimary() {
  const records = filteredRecords();
  const sort = state.adminTableSort || { field: 'id', direction: 'asc' };
  const sortedRecords = [...records].sort((left, right) => {
    const direction = sort.direction === 'desc' ? -1 : 1;
    const leftId = String(left.id || '').toLowerCase();
    const rightId = String(right.id || '').toLowerCase();
    const leftName = `${left.firstName || ''} ${left.lastName || ''}`.trim().toLowerCase();
    const rightName = `${right.firstName || ''} ${right.lastName || ''}`.trim().toLowerCase();
    const leftBarangay = String(left.barangay || '').toLowerCase();
    const rightBarangay = String(right.barangay || '').toLowerCase();
    const leftStatus = String(left.status || '').toLowerCase();
    const rightStatus = String(right.status || '').toLowerCase();

    let comparison = 0;
    switch (sort.field) {
      case 'name':
        comparison = leftName.localeCompare(rightName);
        break;
      case 'barangay':
        comparison = leftBarangay.localeCompare(rightBarangay);
        break;
      case 'status':
        comparison = leftStatus.localeCompare(rightStatus);
        break;
      case 'id':
      default:
        comparison = leftId.localeCompare(rightId, undefined, { numeric: true, sensitivity: 'base' });
        break;
    }
    return comparison * direction;
  });

  const focused = getFocusedAdminRecord(sortedRecords);
  const rows = sortedRecords.map((record) => `
        <tr class="admin-record-row ${focused?.id === record.id ? 'selected' : ''}" data-admin-focus-record data-id="${escapeHtml(record.id)}" role="button" tabindex="0" aria-label="View ${escapeHtml(record.firstName)} ${escapeHtml(record.lastName)}">
          <td>
            <strong>${escapeHtml(record.id)}</strong>
            <small style="display: block; color: #666;">${escapeHtml(record.status)}</small>
          </td>
          <td>${escapeHtml(record.firstName)} ${escapeHtml(record.lastName)}</td>
          <td>${escapeHtml(record.barangay)}</td>
          <td>
            <select class="input-field admin-status-select" data-admin-status-select data-id="${escapeHtml(record.id)}" aria-label="Status for ${escapeHtml(record.id)}">
              <option value="Pending" ${record.status === 'Pending' ? 'selected' : ''}>Pending</option>
              <option value="Active" ${record.status === 'Active' ? 'selected' : ''}>Active</option>
              <option value="Expired" ${record.status === 'Expired' ? 'selected' : ''}>Expired</option>
            </select>
          </td>
          <td class="row-actions">
            <button class="btn" type="button" data-admin-action="focus-selected" data-id="${escapeHtml(record.id)}" onclick="window.handleAdminAction && window.handleAdminAction('focus-selected','${escapeHtml(record.id)}')">View</button>
            <button class="btn primary" type="button" data-admin-action="print-id" data-id="${escapeHtml(record.id)}" onclick="window.handleAdminAction && window.handleAdminAction('print-id','${escapeHtml(record.id)}')">Print</button>
            <button class="btn" type="button" data-admin-action="archive-record" data-id="${escapeHtml(record.id)}" onclick="window.handleAdminAction && window.handleAdminAction('archive-record','${escapeHtml(record.id)}')">Archive</button>
          </td>
        </tr>
      `).join('') || '<tr><td colspan="5" style="padding: 12px; text-align: center;">No records found.</td></tr>';

  const headerLabel = (field, label) => {
    const isActive = sort.field === field;
    const marker = isActive ? (sort.direction === 'asc' ? '▲' : '▼') : '↕';
    return `<button type="button" class="queue-sort-header" data-admin-sort-field="${field}">${escapeHtml(label)} <span class="queue-sort-arrow">${marker}</span></button>`;
  };

  return `
        <header class="panel-head">
          <span>Fisherfolk Registry</span>
          <span>${escapeHtml(records.length)} record(s)</span>
        </header>
        <div class="panel-body">
          <div class="dashboard-analytics-top" style="margin-bottom: 25px; background: rgba(0,0,0,0.2); border-radius: 12px; padding: 20px; border: 1px solid rgba(255,255,255,0.05); overflow: hidden;">
            ${buildReportsChartMarkup()}
          </div>
          <div class="record-list" style="margin-bottom: 0;">
            <div class="form-grid registry-toolbar">
              <div class="field-group">
                <label class="field-label" for="adminSearch">Search</label>
                <input class="input-field" id="adminSearch" placeholder="Search by ID, name, barangay, status" value="${escapeHtml(state.search)}" />
              </div>
              <div class="field-group">
                <label class="field-label" for="adminStatusFilter">Status Filter</label>
                <select class="input-field" id="adminStatusFilter">
                  <option value="all" ${state.statusFilter === 'all' ? 'selected' : ''}>All Statuses</option>
                  <option value="Pending" ${state.statusFilter === 'Pending' ? 'selected' : ''}>Pending</option>
                  <option value="Active" ${state.statusFilter === 'Active' ? 'selected' : ''}>Active</option>
                  <option value="Expired" ${state.statusFilter === 'Expired' ? 'selected' : ''}>Expired</option>
                </select>
              </div>
              <div class="field-group">
                <label class="field-label" for="adminBarangayFilter">Barangay Filter</label>
                <select class="input-field" id="adminBarangayFilter">${renderBarangayFilterOptions(state.barangayFilter)}</select>
              </div>
              <div class="field-group registry-toolbar-chip">
                <label class="field-label">Focused</label>
                <div class="toolbar-pill">${escapeHtml(focused ? `${focused.firstName} ${focused.lastName}` : 'None')}</div>
              </div>
            </div>
          </div>
          <div class="table-wrap registry-table-wrap" style="max-height: 400px; overflow-y: auto;">
            <table class="registry-table">
              <thead>
                <tr class="admin-table-head">
                  <th>${headerLabel('id', 'ID No.')}</th>
                  <th>${headerLabel('name', 'Name')}</th>
                  <th>${headerLabel('barangay', 'Barangay')}</th>
                  <th>${headerLabel('status', 'Status')}</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                ${rows}
              </tbody>
            </table>
          </div>
        </div>
      `;
}

function renderAdminArchiveStoragePrimary() {
  const records = (state.archivedRecords || []).slice();
  const sort = state.adminTableSort || { field: 'id', direction: 'asc' };
  const sortedRecords = [...records].sort((left, right) => {
    const direction = sort.direction === 'desc' ? -1 : 1;
    const leftId = String(left.id || '').toLowerCase();
    const rightId = String(right.id || '').toLowerCase();
    const leftName = `${left.firstName || ''} ${left.lastName || ''}`.trim().toLowerCase();
    const rightName = `${right.firstName || ''} ${right.lastName || ''}`.trim().toLowerCase();
    const leftBarangay = String(left.barangay || '').toLowerCase();
    const rightBarangay = String(right.barangay || '').toLowerCase();
    const leftStatus = String(left.status || '').toLowerCase();
    const rightStatus = String(right.status || '').toLowerCase();

    let comparison = 0;
    switch (sort.field) {
      case 'name':
        comparison = leftName.localeCompare(rightName);
        break;
      case 'barangay':
        comparison = leftBarangay.localeCompare(rightBarangay);
        break;
      case 'status':
        comparison = leftStatus.localeCompare(rightStatus);
        break;
      case 'id':
      default:
        comparison = leftId.localeCompare(rightId, undefined, { numeric: true, sensitivity: 'base' });
        break;
    }
    return comparison * direction;
  });

  const rows = sortedRecords.map((record) => `
          <tr class="admin-record-row" data-admin-focus-record data-id="${escapeHtml(record.id)}" role="button" tabindex="0" aria-label="Archived ${escapeHtml(record.firstName)} ${escapeHtml(record.lastName)}">
            <td>
              <strong>${escapeHtml(record.id)}</strong>
              <small style="display: block; color: #666;">Archived</small>
            </td>
            <td>${escapeHtml(record.firstName)} ${escapeHtml(record.lastName)}</td>
            <td>${escapeHtml(record.barangay)}</td>
            <td>
                <span class="status-badge expired">Archived</span>
                <small style="display: block; color: #666; margin-top: 4px;">Previous: ${escapeHtml(record.status || 'N/A')}</small>
            </td>
            <td class="row-actions">
              <button class="btn primary" type="button" data-admin-action="restore-record" data-id="${escapeHtml(record.id)}" onclick="window.handleAdminAction && window.handleAdminAction('restore-record','${escapeHtml(record.id)}')">Restore</button>
              <button class="btn" type="button" data-admin-action="print-id" data-id="${escapeHtml(record.id)}" onclick="window.handleAdminAction && window.handleAdminAction('print-id','${escapeHtml(record.id)}')">Print</button>
            </td>
          </tr>
        `).join('') || '<tr><td colspan="5" style="padding: 12px; text-align: center;">No archived records found.</td></tr>';

  return `
          <header class="panel-head">
            <span>Archive Storage</span>
            <span>${escapeHtml(records.length)} record(s)</span>
          </header>
          <div class="panel-body">
            <div class="notice">Archived records are removed from the active registry here and can be restored when needed.</div>
            <div class="table-wrap registry-table-wrap" style="width: 100%; overflow-x: auto; overflow-y: visible; max-height: none;">
              <table class="registry-table">
                <thead>
                  <tr class="admin-table-head">
                    <th>ID No.</th>
                    <th>Name</th>
                    <th>Barangay</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  ${rows}
                </tbody>
              </table>
            </div>
          </div>
        `;
}

function renderAdminSecondary() {
  return `
        <header class="panel-head">
          <span>Register Fisherfolk</span>
          <span>[ Step 1 of 3 ]</span>
        </header>
        <div class="panel-body">
          <form id="registerForm" class="record-list" autocomplete="off">
            <div class="form-grid">
              <div class="field-group"><label class="field-label" for="firstName">First Name</label><input class="input-field" id="firstName" name="firstName" autocomplete="off" required /></div>
              <div class="field-group"><label class="field-label" for="middleName">Middle Name</label><input class="input-field" id="middleName" name="middleName" autocomplete="off" /></div>
              <div class="field-group"><label class="field-label" for="lastName">Last Name</label><input class="input-field" id="lastName" name="lastName" autocomplete="off" required /></div>
              <div class="field-group"><label class="field-label" for="birthDate">Date of Birth</label><input class="input-field" id="birthDate" name="birthDate" type="date" autocomplete="bday" required /></div>
              <div class="field-group">
                <label class="field-label" for="gender">Gender</label>
                <select class="input-field" id="gender" name="gender" autocomplete="off" required>
                  <option value="" disabled selected>Select gender</option>
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Prefer not to say">Prefer not to say</option>
                </select>
              </div>
              <div class="field-group full">
                <label class="field-label" for="barangay">Address / Barangay</label>
                <select class="input-field" id="barangay" name="barangay" autocomplete="off" required>
                  <option value="" disabled selected>Select barangay</option>
                  ${renderBarangayOptions()}
                </select>
              </div>
              <div class="field-group">
                <label class="field-label" for="livelihood">Primary Livelihood</label>
                <select class="input-field" id="livelihood" name="livelihood" autocomplete="off" required>
                  <option value="" disabled selected>Select livelihood</option>
                  <option value="Fishing">Fishing</option>
                  <option value="Fish Vendor">Fish Vendor</option>
                  <option value="Aquaculture">Aquaculture</option>
                  <option value="Boat Repair">Boat Repair</option>
                  <option value="Others">Others</option>
                </select>
              </div>
              <div class="field-group"><label class="field-label" for="contact">Contact Number</label><input class="input-field" id="contact" name="contact" autocomplete="tel" required /></div>
              <div class="field-group"><label class="field-label" for="fishrNumber">FishR Number</label><input class="input-field" id="fishrNumber" name="fishrNumber" autocomplete="off" /></div>
              <div class="field-group"><label class="field-label" for="rsbsaNumber">RSBSA Number</label><input class="input-field" id="rsbsaNumber" name="rsbsaNumber" autocomplete="off" /></div>
              <div class="field-group"><label class="field-label" for="username">Username</label><input class="input-field" id="username" name="username" autocomplete="off" required /></div>
              <div class="field-group"><label class="field-label" for="password">Password</label><input class="input-field" id="password" name="password" type="password" autocomplete="new-password" required /></div>
              <div class="field-group full">
                <label class="field-label" for="status">Status</label>
                <select class="input-field" id="status" name="status" autocomplete="off" required>
                  <option value="Pending" selected>Pending</option>
                  <option value="Active">Active</option>
                  <option value="Expired">Expired</option>
                </select>
              </div>
              <div class="field-group full">
                <label class="field-label" for="idPhotoFile">IMPORT ID PICTURE</label>
                <input class="input-field" id="idPhotoFile" name="idPhotoFile" type="file" accept=".jpg,.jpeg,.jfif,.png" required />
              </div>
              <div class="field-group full">
                <label class="field-label" for="idSignatureFile">IMPORT SIGNATURE PICTURE</label>
                <input class="input-field" id="idSignatureFile" name="idSignatureFile" type="file" accept=".jpg,.jpeg,.jfif,.png" required />
              </div>
              <div class="field-group full" style="margin-top:8px;">
                <h4>Emergency Contact</h4>
              </div>
              <div class="field-group full">
                <label class="field-label" for="emergencyName">Name</label>
                <input class="input-field" id="emergencyName" name="emergencyName" autocomplete="off" />
              </div>
              <div class="field-group"><label class="field-label" for="emergencyRelation">Relation</label><input class="input-field" id="emergencyRelation" name="emergencyRelation" /></div>
              <div class="field-group"><label class="field-label" for="emergencyContact">Contact No.</label><input class="input-field" id="emergencyContact" name="emergencyContact" /></div>
              <div class="field-group full"><label class="field-label" for="emergencyAddress">Complete Address</label><textarea class="input-field" id="emergencyAddress" name="emergencyAddress" rows="2"></textarea></div>
            </div>
            <div class="button-row">
              <button class="btn primary" type="submit">Save & Generate ID</button>
              <button class="btn" type="reset">Clear</button>
            </div>
          </form>
          <div class="thumb">Photo Capture and Attachments</div>
          <div class="notice">Registration checklist: verify identity, capture photo, review barangay, then issue the ID.</div>
          <div class="id-card" id="adminIdPreview">
            <div class="id-photo">Photo</div>
            <div class="id-meta">
              <div><strong>Municipality Fisherfolk ID</strong></div>
              <div>ID No: FF-XXXX</div>
              <div>Name: Firstname Lastname</div>
              <div>Barangay: __________</div>
              <div>Valid Until: MM/YYYY</div>
            </div>
          </div>
        </div>
      </header>
      
      <!-- Registered Fisherfolk Records temporarily removed to restore layout -->
      `;
}

function renderUserPrimary() {
  const record = getUserRecord();
  const requestCount = state.requests.filter((item) => item.userId === record?.id).length;
  return `
        <header class="panel-head">
          <span>My Profile and ID</span>
          <span>${escapeHtml(requestCount)} request(s)</span>
        </header>
        <div class="panel-body">
          <div class="activity-strip">
            <div class="activity-card"><span class="small-label">Current Status</span><span class="large-value">${escapeHtml(record?.status ?? 'N/A')}</span><span class="helper-text">Your registration and ID status at a glance.</span></div>
            <div class="activity-card"><span class="small-label">Valid Until</span><span class="large-value">${escapeHtml(record?.validity ?? 'N/A')}</span><span class="helper-text">Keep your ID updated before expiration.</span></div>
            <div class="activity-card"><span class="small-label">Requests</span><span class="large-value">${escapeHtml(requestCount)}</span><span class="helper-text">Submitted requests and follow-ups.</span></div>
          </div>
          <div class="record-card">
            <strong>${escapeHtml(record?.firstName ?? 'User')} ${escapeHtml(record?.lastName ?? '')}</strong>
            <span>ID Number: ${escapeHtml(record?.id ?? 'N/A')}</span>
            <span>Barangay: ${escapeHtml(record?.barangay ?? 'N/A')}</span>
            <span>Livelihood: ${escapeHtml(record?.livelihood ?? 'N/A')}</span>
            <span>Status: ${escapeHtml(record?.status ?? 'N/A')}</span>
            <span>Valid Until: ${escapeHtml(record?.validity ?? 'N/A')}</span>
          </div>
          ${renderIdCardMarkup(record)}
          <div class="timeline">
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-content">
                <strong>Profile verified</strong>
                <span>Identity and barangay record matched against the current system entry.</span>
              </div>
            </div>
            <div class="timeline-item">
              <div class="timeline-dot"></div>
              <div class="timeline-content">
                <strong>ID issued</strong>
                <span>Printable fisherfolk card is available for display and field use.</span>
              </div>
            </div>
          </div>
        </div>
      `;
}

function renderUserSubsidyPrimary() {
  return `
        <header class="panel-head">
          <span>Boat Damage Subsidy Report</span>
          <span>Submit your request form</span>
        </header>
        <div class="panel-body">
          <div class="notice">Report damaged boats here so LGU can evaluate and process subsidy support. After submitting, your report will appear in My History.</div>
          <form id="subsidyForm" class="record-list scrollable-form">
            <div class="field-group">
              <label class="field-label" for="boatName">Boat Name</label>
              <input class="input-field" id="boatName" name="boatName" placeholder="Enter registered boat name" required />
            </div>
            <div class="field-group">
              <label class="field-label" for="boatType">Boat Type</label>
              <select class="input-field" id="boatType" name="boatType" required>
                <option value="" disabled selected>Select boat type</option>
                <option value="Motorized">Motorized</option>
                <option value="Non-Motorized">Non-Motorized</option>
                <option value="Banca">Banca</option>
                <option value="Fiberglass">Fiberglass</option>
                <option value="Wooden">Wooden</option>
                <option value="Others">Others</option>
              </select>
            </div>
            <div class="field-group">
              <label class="field-label" for="boatColor">Boat Color</label>
              <select class="input-field" id="boatColorPreset" name="boatColorPreset" required>
                <option value="" disabled selected>Select boat color</option>
                <option value="Blue">Blue</option>
                <option value="White">White</option>
                <option value="Red">Red</option>
                <option value="Green">Green</option>
                <option value="Yellow">Yellow</option>
                <option value="Black">Black</option>
                <option value="Custom">Custom</option>
              </select>
            </div>
            <div class="field-group hidden" id="boatColorCustomWrap">
              <label class="field-label" for="boatColorCustom">Custom Boat Color</label>
              <input class="input-field" id="boatColorCustom" name="boatColorCustom" placeholder="e.g. Blue and White" />
            </div>
            <div class="field-group">
              <label class="field-label" for="boatSize">Boat Size</label>
              <select class="input-field" id="boatSize" name="boatSize" required>
                <option value="" disabled selected>Select boat size</option>
                <option value="Small">Small</option>
                <option value="Medium">Medium</option>
                <option value="Large">Large</option>
                <option value="Custom">Custom</option>
              </select>
            </div>
            <div class="field-group hidden" id="boatSizeCustomWrap">
              <label class="field-label" for="boatSizeCustom">Custom Boat Size</label>
              <input class="input-field" id="boatSizeCustom" name="boatSizeCustom" placeholder="e.g. 18ft x 5ft" />
            </div>
            <div class="field-group">
              <label class="field-label" for="incidentDate">Incident Date</label>
              <input class="input-field" id="incidentDate" name="incidentDate" type="date" required />
            </div>
            <div class="field-group">
              <label class="field-label" for="damageCost">Estimated Damage Cost</label>
              <input class="input-field" id="damageCost" name="damageCost" placeholder="e.g. 25000" required />
            </div>
            <div class="field-group">
              <label class="field-label" for="incidentDetails">Incident Details</label>
              <textarea class="input-field" id="incidentDetails" name="incidentDetails" rows="5" placeholder="Describe how the damage happened and what parts were affected" required></textarea>
            </div>
            <div class="field-group">
              <label class="field-label" for="contactPreference">Preferred Contact</label>
              <select class="input-field" id="contactPreference" name="contactPreference">
                <option value="" selected>Any</option>
                <option value="SMS">SMS</option>
                <option value="Call">Call</option>
                <option value="Email">Email</option>
              </select>
            </div>
            <div class="button-row">
              <button class="btn primary" type="submit">Submit Subsidy Report</button>
            </div>
          </form>
        </div>
      `;
}

function renderUserHistoryPrimary() {
  const record = getUserRecord();
  const historyItems = state.requests
    .filter((item) => String(item.userId || item.fisherfolk_id) === String(record?.id))
    .slice()
    .reverse();

  const activeFilter = String(state.historyFilter || 'all').toLowerCase();
  const filteredHistoryItems = historyItems.filter((item) => {
    const reqType = item.type || item.request_type;
    if (activeFilter === 'all') return true;
    if (activeFilter === 'subsidy') return reqType === 'Subsidy';
    if (activeFilter === 'pending') {
      const status = String(item.status || 'Pending');
      return status === 'Pending' || status === 'Under Review';
    }
    if (activeFilter === 'resolved') {
      const status = String(item.status || 'Pending');
      return status === 'Approved' || status === 'Rejected';
    }
    return true;
  });

  const historyFilterTabs = [
    { key: 'all', label: `All (${historyItems.length})` },
    { key: 'subsidy', label: `Subsidy (${historyItems.filter((item) => (item.type || item.request_type) === 'Subsidy').length})` },
    {
      key: 'pending', label: `Pending (${historyItems.filter((item) => {
        const status = String(item.status || 'Pending');
        return status === 'Pending' || status === 'Under Review';
      }).length})`
    },
    {
      key: 'resolved', label: `Resolved (${historyItems.filter((item) => {
        const status = String(item.status || 'Pending');
        return status === 'Approved' || status === 'Rejected';
      }).length})`
    }
  ];

  const historyCards = filteredHistoryItems.map((item) => {
    const reqType = item.type || item.request_type;
    const subject = reqType === 'Subsidy' ? 'Boat Damage Subsidy' : (item.subject || reqType || 'Request');
    const date = item.createdAt || item.created_at || item.date_submitted;
    return `
          <div class="record-card">
            <strong>${escapeHtml(subject)} - ${escapeHtml(item.id || 'N/A')}</strong>
            <span>Status: ${escapeHtml(item.status || 'Pending')}</span>
            <span>Date Submitted: ${escapeHtml(formatDate(date))}</span>
            ${reqType === 'Subsidy' ? `
              <span>Boat Name: ${escapeHtml(item.boatName || 'N/A')}</span>
              <span>Boat Type: ${escapeHtml(item.boatType || 'N/A')}</span>
              <span>Boat Color: ${escapeHtml(item.boatColor || 'N/A')}</span>
              <span>Boat Size: ${escapeHtml(item.boatSize || 'N/A')}</span>
              <span>Incident Date: ${escapeHtml(item.incidentDate || 'N/A')}</span>
              <span>Estimated Damage: ${escapeHtml(item.damageCost || 'N/A')}</span>
            ` : ''}
            <span>Details: ${escapeHtml(item.message || 'N/A')}</span>
            <span>Admin Note: ${escapeHtml(item.adminNote || 'No note yet.')}</span>
          </div>
        `;
  }).join('') || '<div class="record-card">No records in this history filter yet.</div>';

  return `
        <header class="panel-head">
          <span>My History</span>
          <span>${escapeHtml(historyItems.length)} record(s)</span>
        </header>
        <div class="panel-body">
          <div class="notice">All your submitted requests are listed here for easy tracking and status updates.</div>
          <div class="table-tools">
            ${historyFilterTabs.map((item) => {
    const activeClass = item.key === activeFilter ? 'active' : '';
    return `<button class="filter-pill ${activeClass}" type="button" data-history-filter="${escapeHtml(item.key)}">${escapeHtml(item.label)}</button>`;
  }).join('')}
          </div>
          <div class="record-list scrollable-list">${historyCards}</div>
        </div>
      `;
}

function renderUserHistorySecondary() {
  const record = getUserRecord();
  const requests = state.requests.filter((item) => (item.userId || item.fisherfolk_id) === record?.id);
  const pending = requests.filter((item) => (item.status || 'Pending') === 'Pending').length;
  const underReview = requests.filter((item) => item.status === 'Under Review').length;
  const approved = requests.filter((item) => item.status === 'Approved').length;
  const rejected = requests.filter((item) => item.status === 'Rejected').length;

  return `
        <header class="panel-head">
          <span>History Summary</span>
          <span>Request progress</span>
        </header>
        <div class="panel-body">
          <div class="record-card"><strong>Pending</strong><span>${escapeHtml(pending)} request(s)</span></div>
          <div class="record-card"><strong>Under Review</strong><span>${escapeHtml(underReview)} request(s)</span></div>
          <div class="record-card"><strong>Approved</strong><span>${escapeHtml(approved)} request(s)</span></div>
          <div class="record-card"><strong>Rejected</strong><span>${escapeHtml(rejected)} request(s)</span></div>
        </div>
      `;
}

function renderUserAnnouncementsSecondary() {
  const record = getUserRecord();
  return `
        <header class="panel-head">
          <span>Member Context</span>
          <span>Account and ID reference</span>
        </header>
        <div class="panel-body">
          <div class="record-card"><strong>Fisherfolk Account</strong><span>${escapeHtml(record?.firstName ?? 'N/A')} ${escapeHtml(record?.lastName ?? '')}</span></div>
          <div class="record-card"><strong>Fisherfolk ID</strong><span>${escapeHtml(record?.id ?? 'N/A')}</span></div>
          <div class="notice">Announcements are tied to the ID system. Open My ID Details anytime to verify your current status and validity.</div>
        </div>
      `;
}

function renderUserSubsidySecondary() {
  const record = getUserRecord();
  const requests = state.requests.filter((item) => item.userId === record?.id && item.type === 'Subsidy');
  const pending = requests.filter((item) => item.status === 'Pending').length;
  return `
        <header class="panel-head">
          <span>Subsidy Summary</span>
          <span>${escapeHtml(requests.length)} total report(s)</span>
        </header>
        <div class="panel-body">
          <div class="record-card"><strong>Pending Evaluation</strong><span>${escapeHtml(pending)} report(s)</span></div>
          <div class="record-card"><strong>Processed</strong><span>${escapeHtml(requests.length - pending)} report(s)</span></div>
          <div class="notice">Your report is reviewed by admin for validation and subsidy eligibility assessment.</div>
        </div>
      `;
}

function renderAdminAnnouncementsPrimary() {
  const items = state.announcements.slice().reverse();
  const cards = items.map((item) => `
        <div class="record-card">
          <strong>${escapeHtml(item.title)}</strong>
          <span>${escapeHtml(item.message)}</span>
          <span>${escapeHtml(formatDate(item.createdAt))}</span>
          <div class="button-row">
            <button class="btn danger" type="button" data-announcement-delete="${escapeHtml(item.id)}">Remove</button>
          </div>
        </div>
      `).join('') || '<div class="record-card">No announcements yet.</div>';

  return `
        <header class="panel-head">
          <span>Announcement Management</span>
          <span>${escapeHtml(state.announcements.length)} total</span>
        </header>
        <div class="panel-body">
          <form id="announcementForm" class="record-list">
            <div class="field-group">
              <label class="field-label" for="announcementTitle">Title</label>
              <input class="input-field" id="announcementTitle" name="announcementTitle" placeholder="ID schedule, renewal drive, advisory" required />
            </div>
            <div class="field-group">
              <label class="field-label" for="announcementMessage">Announcement</label>
              <textarea class="input-field" id="announcementMessage" name="announcementMessage" rows="4" placeholder="Enter announcement details for fisherfolk members" required></textarea>
            </div>
            <div class="button-row">
              <button class="btn primary" type="submit">Publish Announcement</button>
            </div>
          </form>
          <div class="notice">Published announcements are visible to all fisherfolk accounts.</div>
          <div class="record-list scrollable-list">${cards}</div>
        </div>
      `;
}

function renderAdminAnnouncementsSecondary() {
  const subsidyReports = state.requests.filter((item) => {
    const t = String(item.type || item.request_type || '').toLowerCase();
    const s = String(item.subject || '').toLowerCase();
    const b = String(item.boatName || item.boat_name || '').toLowerCase();
    return t.includes('subsidy') || s.includes('subsidy') || s.includes('damage') || b !== '';
  }).length;
  return `
        <header class="panel-head">
          <span>Communication Snapshot</span>
          <span>Member-facing updates</span>
        </header>
        <div class="panel-body">
          <div class="record-card"><strong>Published Announcements</strong><span>${escapeHtml(state.announcements.length)} post(s)</span></div>
          <div class="record-card"><strong>Subsidy Reports Logged</strong><span>${escapeHtml(subsidyReports)} report(s)</span></div>
          <div class="notice">Use clear announcement titles so fisherfolk can quickly find ID release, renewal, and support advisories.</div>
        </div>
      `;
}

function renderPageNote() {
  const note = document.getElementById('pageNote');
  if (!note) return;
  if (state.flashMessage) {
    note.textContent = state.flashMessage;
    note.className = 'note flash-message';
    state.flashMessage = '';
    return;
  }
  const view = state.currentView;
  const notes = state.role === 'admin'
    ? {
      'dashboard': 'Admin dashboard view focused on fisherfolk registry and status management.',
      'register-fisherfolk': 'Registration view for adding new fisherfolk accounts and generating IDs.',
      'id-printing-queue': 'Printing queue view for tracking records ready for ID production.',
      'archive-storage': 'Archive storage view for restoring records back into the active registry.',
      'subsidy-reports-admin': 'Subsidy report management view for review, status updates, and admin notes.',
      'announcements-admin': 'Publish and manage member-facing announcements for the ID system.',
      'reports-analytics': 'Reports view for summary metrics and performance trend monitoring.',
      'settings-users': 'Settings view for system controls and barangay management.'
    }
    : {
      'my-profile': 'Profile view for checking your registered personal and livelihood details.',
      'account-settings': 'Account settings view for updating your login username and password.',
      'my-id-details': 'ID details view for card information, issue status, and validity timeline.',
      'subsidy-report': 'Boat damage subsidy reporting view for submission and status tracking.',
      'history': 'History view for all submitted reports, request statuses, and admin updates.',
      'announcements': 'Announcement board for ID schedules, renewals, and LGU advisories.'
    };

  note.textContent = notes[view] || '';
  note.className = 'note';
}

function renderApp() {
  ensureCurrentView();
  const current = getCurrentNavItem();
  const contentGrid = document.querySelector('.content-grid');
  if (contentGrid) {
    const isDirectoryFocus = state.role === 'admin' && (state.currentView === 'dashboard' || state.currentView === 'archive-storage');
    const isSinglePanel = state.role === 'admin' && (state.currentView === 'dashboard' || state.currentView === 'archive-storage' || state.currentView === 'reports-analytics' || state.currentView === 'id-printing-queue' || state.currentView === 'subsidy-reports-admin' || state.currentView === 'settings-users');
    contentGrid.classList.toggle('directory-focus', isDirectoryFocus);
    contentGrid.classList.toggle('single-panel', isSinglePanel);
  }
  document.getElementById('logoText').innerHTML = state.role === 'admin'
    ? 'Fisherfolk IMS<span class="system-tag">Admin Workspace</span>'
    : 'Fisherfolk IMS<span class="system-tag">User Workspace</span>';
  const sectionTitleEl = document.getElementById('sectionTitle');
  if (sectionTitleEl) {
    sectionTitleEl.textContent = `${escapeHtml(current?.label || 'Workspace')} - Fisherfolk Information Management System`;
  }

  const heroSection = document.getElementById('dashboardHero');
  const quickActions = document.getElementById('quickActions');
  const kpiGrid = document.getElementById('kpiGrid');

  // Only show hero section, quick actions, and KPI cards for dashboard and my-profile views
  const shouldShowHeroSection = (state.role === 'admin' && state.currentView === 'dashboard') ||
    (state.role !== 'admin' && state.currentView === 'my-profile');

  if (heroSection) heroSection.classList.toggle('hidden', !shouldShowHeroSection);
  if (quickActions) quickActions.classList.toggle('hidden', !shouldShowHeroSection);
  if (kpiGrid) kpiGrid.classList.toggle('hidden', !shouldShowHeroSection);

  buildNav();
  buildTopBar();
  renderBreadcrumbs();

  // Only build hero/quick-actions/kpi for dashboard views
  if (shouldShowHeroSection) {
    buildHero();
    buildQuickActions();
    buildKpiCards();
  } else {
    // Clear hero section content for non-dashboard views
    if (heroSection) heroSection.innerHTML = '';
    if (quickActions) quickActions.innerHTML = '';
    if (kpiGrid) kpiGrid.innerHTML = '';
  }

  const primaryPanel = document.getElementById('primaryPanel');
  const secondaryPanel = document.getElementById('secondaryPanel');

  // Clear panels completely before rendering new content
  if (primaryPanel) {
    primaryPanel.innerHTML = '';
    // Remove all context-specific classes
    primaryPanel.classList.remove('admin-table-panel', 'admin-register-panel');
    // Apply context-specific classes for current view
    if (state.role === 'admin') {
      if (state.currentView === 'dashboard' || state.currentView === 'subsidy-reports-admin' || state.currentView === 'settings-users') primaryPanel.classList.add('admin-table-panel');
      if (state.currentView === 'register-fisherfolk') primaryPanel.classList.add('admin-register-panel');
    }
  }
  if (secondaryPanel) {
    secondaryPanel.innerHTML = '';
    // Remove all context-specific classes
    secondaryPanel.classList.remove('admin-detail-panel', 'admin-register-side-panel');
    // Apply context-specific classes for current view
    if (state.role === 'admin') {
      if (state.currentView === 'dashboard') secondaryPanel.classList.add('admin-detail-panel');
      if (state.currentView === 'register-fisherfolk') secondaryPanel.classList.add('admin-register-side-panel');
    }
  }

  if (state.role === 'admin') {
    const adminViewMap = {
      'dashboard': [renderAdminPrimary, null],
      'register-fisherfolk': [renderAdminSecondary, renderAdminRegisterSecondary],
      'edit-record': [renderAdminEditRecord, null],
      'id-printing-queue': [renderAdminPrintingQueue, null],
      'archive-storage': [renderAdminArchiveStoragePrimary, null],
      'subsidy-reports-admin': [renderAdminSubsidyReportsPrimary, null],
      'announcements-admin': [renderAdminAnnouncementsPrimary, renderAdminAnnouncementsSecondary],
      'reports-analytics': [renderAdminReports, null],
      'reported-posts': [renderAdminReportedPosts, null],
      'settings-users': [renderAdminSettings, null]
    };
    const [primaryFn, secondaryFn] = adminViewMap[state.currentView] || adminViewMap.dashboard;
    if (primaryPanel) primaryPanel.innerHTML = primaryFn();
    if (typeof secondaryFn === 'function') {
      if (secondaryPanel) {
        secondaryPanel.classList.remove('hidden');
        secondaryPanel.innerHTML = secondaryFn();
      }
    } else {
      if (secondaryPanel) {
        secondaryPanel.classList.add('hidden');
        secondaryPanel.innerHTML = '';
      }
    }
  } else {
    const userViewMap = {
      'my-profile': [renderUserProfileOnly, renderUserProfileSecondary],
      'account-settings': [renderUserAccountSettingsPrimary, renderUserAccountSettingsSecondary],
      'my-id-details': [renderUserIdDetailsOnly, renderUserIdSecondary],
      'subsidy-report': [renderUserSubsidyPrimary, renderUserSubsidySecondary],
      'history': [renderUserHistoryPrimary, renderUserHistorySecondary],
      'announcements': [renderUserAnnouncements, renderUserAnnouncementsSecondary]
    };
    const [primaryFn, secondaryFn] = userViewMap[state.currentView] || userViewMap['my-profile'];
    if (secondaryPanel) secondaryPanel.classList.remove('hidden');
    if (primaryPanel) primaryPanel.innerHTML = primaryFn();
    if (secondaryPanel) secondaryPanel.innerHTML = secondaryFn();
  }

  persistSession();

  renderPageNote();
  animateDashboardView();
  attachAppListeners();
}

window.flipIdCard = function (recordId) {
  const wrappers = document.querySelectorAll(`.registry-id-card-wrap[data-id="${recordId}"]`);
  if (!wrappers.length) return;

  const record = getRecordById(recordId);
  if (!record) return;

  wrappers.forEach(wrap => {
    const container = wrap.closest('.registry-id-preview');
    const btn = container ? container.querySelector('[data-admin-action="flip-id"]') : null;
    const isShowingBack = wrap.querySelector('.id-card-back');

    if (isShowingBack) {
      wrap.innerHTML = renderIdCardMarkup(record);
      if (btn) btn.textContent = 'View Back';
    } else {
      wrap.innerHTML = renderIdCardBackMarkup(record);
      if (btn) btn.textContent = 'View Front';
    }
  });
};

function attachAppListeners() {
  // Provide a direct global handler for inline onclicks
  if (!window.handleAdminAction) {
    window.handleAdminAction = (action, recordId) => {
      if (!action) return;
      try {
        if (action === 'focus-selected' && recordId) {
          openMasterDetailModalById(recordId);
          return;
        }
        if (action === 'edit-record' && recordId) {
          closeMasterDetailModal();
          state.selectedAdminRecordId = recordId;
          state.currentView = 'edit-record';
          renderApp();
          return;
        }
        if (action === 'print-id' && recordId) {
          const targetRecord = (typeof getRecordById === 'function') ? getRecordById(recordId) : null;
          if (targetRecord) {
            printIdCard(targetRecord);
            updateRecordStatus(recordId, 'Active');
          }
          renderApp();
          return;
        }
        if (action === 'download-id' && recordId) {
          const targetRecord = (typeof getRecordById === 'function') ? getRecordById(recordId) : null;
          if (targetRecord) {
            downloadIdCard(targetRecord);
          }
          renderApp();
          return;
        }
        if (action === 'print-records-report') {
          printRecordsReport();
          return;
        }
        if (action === 'print-requests-report') {
          printRequestsReport();
          return;
        }
        if (action === 'archive-record' && recordId) {
          const targetRecord = (typeof getRecordById === 'function') ? getRecordById(recordId) : null;
          if (targetRecord) {
            if (archiveRecord(recordId)) {
              setFlashMessage(`${recordId} moved to archive storage.`);
            }
          }
          renderApp();
          return;
        }
        if (action === 'restore-record' && recordId) {
          const targetRecord = (state.archivedRecords || []).find((item) => item.id === recordId) || null;
          if (targetRecord) {
            if (restoreArchivedRecord(recordId)) {
              setFlashMessage(`${recordId} restored to active registry.`);
            }
          }
          renderApp();
          return;
        }
        if (action === 'expire-id' && recordId) {
          if (updateRecordStatus(recordId, 'Expired')) setFlashMessage(`${recordId} removed from queue and marked Expired.`);
          renderApp();
          return;
        }
        if (action === 'dismiss-report' && recordId) {
          showActionConfirm('Confirm Dismiss', 'Are you sure you want to dismiss this report? It will be removed from your review list.', () => {
            const formData = new FormData();
            formData.append('action', 'dismiss_report');
            formData.append('report_id', recordId);
            fetch('api_social.php', { method: 'POST', body: formData })
              .then(r => r.json())
              .then(res => {
                if (res.success) {
                  location.reload();
                } else {
                  alert(res.message);
                }
              });
          });
          return;
        }
        if (action === 'delete-reported-post' && recordId) {
          const reportId = arguments[2]; // Passed as third arg
          showActionConfirm('PERMANENT DELETE', 'Are you sure you want to PERMANENTLY DELETE this post? This action cannot be undone.', () => {
            // First delete the post
            const formData = new FormData();
            formData.append('action', 'delete_post');
            formData.append('post_id', recordId);
            fetch('api_social.php', { method: 'POST', body: formData })
              .then(r => r.json())
              .then(res => {
                if (res.success) {
                  // Post deleted, now dismiss the report too
                  const fd2 = new FormData();
                  fd2.append('action', 'dismiss_report');
                  fd2.append('report_id', reportId);
                  fetch('api_social.php', { method: 'POST', body: fd2 }).finally(() => {
                    location.reload();
                  });
                } else {
                  alert(res.message);
                }
              });
          });
          return;
        }
      } catch (err) {
        console.error('Admin action error:', err);
        setFlashMessage('An error occurred. Please try again.');
      }
    };
  }

  if (!window.handleAdminEditSubmit) {
    window.handleAdminEditSubmit = async (recordId) => {
      const form = document.getElementById('adminEditForm');
      if (!form) return;
      const formData = new FormData(form);
      const photoFile = document.getElementById('editPhotoFile').files[0];
      const signatureFile = document.getElementById('editSignatureFile').files[0];

      try {
        let photoData = null;
        let signatureData = null;

        if (photoFile) {
          photoData = await fileToDataUrl(photoFile);
        }
        if (signatureFile) {
          signatureData = await fileToDataUrl(signatureFile);
        }

        const updatePayload = {
          id: recordId,
          lastName: formData.get('lastName'),
          firstName: formData.get('firstName'),
          middleName: formData.get('middleName'),
          barangay: formData.get('barangay'),
          birthDate: formData.get('birthDate'),
          gender: formData.get('gender'),
          livelihood: formData.get('livelihood'),
          contact: formData.get('contact'),
          fishrNumber: formData.get('fishrNumber'),
          rsbsaNumber: formData.get('rsbsaNumber'),
          emergencyName: formData.get('emergencyName'),
          emergencyRelation: formData.get('emergencyRelation'),
          emergencyContact: formData.get('emergencyContact'),
          emergencyAddress: formData.get('emergencyAddress'),
          photoData: photoData,
          signatureData: signatureData
        };
        // Do not allow editing username/password from this form — only personal info is updated here

        const response = await saveToDatabase('update_record', updatePayload);
        if (response && response.success) {
          setFlashMessage('Record updated successfully!');
          state.currentView = 'dashboard';
          await loadDataFromServer();
          renderApp();
        } else {
          if (String(response?.error || response?.message || '').toLowerCase().includes('username already exists')) {
            openAlertModal('Invalid Account', 'You already have an account. Username is already in use.');
            return;
          }
          setFlashMessage('Error updating record: ' + (response?.message || 'Unknown error'), 'error');
        }
      } catch (error) {
        console.error('Edit submit error:', error);
        setFlashMessage('Error: ' + error.message, 'error');
      }
    };
  }

  

  if (!window.handleUserAccountSave) {
    window.handleUserAccountSave = async () => {
      try {
        const record = getUserRecord();
        if (!record) { setFlashMessage('No user record found'); return; }
        const username = (document.getElementById('uaUsername')?.value || '').toString().trim();
        const password = (document.getElementById('uaPassword')?.value || '').toString();

        if (!username && !password) { setFlashMessage('No changes to save'); return; }

        const payload = { id: record.id };
        if (username) payload.username = username;
        if (password) payload.password = password;

        const response = await saveToDatabase('update_record', payload);
        if (response && response.success) {
          setFlashMessage('Account updated successfully');
          await loadDataFromServer();
          renderApp();
        } else {
          if (String(response?.error || response?.message || '').toLowerCase().includes('username already exists')) {
            openAlertModal('Invalid Account', 'You already have an account. Username is already in use.');
            return;
          }
          setFlashMessage('Error updating account: ' + (response?.error || response?.message || 'Unknown'));
        }
      } catch (err) {
        console.error('User account save error:', err);
        setFlashMessage('Error: ' + err.message);
      }
    };
  }

  if (!window.setAdminCurrentView) {
    window.setAdminCurrentView = (view) => {
      if (!view) return;
      state.currentView = view;
      persistSession();
      renderApp();
    };
  }

  const logoutButton = document.getElementById('logoutButton');
  if (logoutButton) {
    logoutButton.onclick = openLogoutModal;
  }

  document.querySelectorAll('.nav-item[data-view]').forEach((item) => {
    item.addEventListener('click', (event) => {
      const view = item.getAttribute('data-view');
      if (!view || view === state.currentView) return;

      const href = item.getAttribute('href') || '';
      const isInternalViewLink = href.includes('?view=');
      if (isInternalViewLink) {
        event.preventDefault();
      }

      state.currentView = view;
      persistSession();

      if (isInternalViewLink) {
        const basePath = state.role === 'admin' ? 'admin.php' : 'dashboard.php';
        const nextUrl = `${basePath}?view=${encodeURIComponent(view)}`;
        try {
          window.history.replaceState({}, '', nextUrl);
        } catch (ex) {
          // ignore history API errors
        }
      }

      renderApp();
    });
  });

  document.querySelectorAll('.quick-action[data-view]').forEach((item) => {
    item.addEventListener('click', () => {
      const view = item.getAttribute('data-view');
      if (!view) return;
      state.currentView = view;
      persistSession();
      renderApp();
    });
  });

  document.querySelectorAll('[data-admin-sort-field]').forEach((button) => {
    button.addEventListener('click', () => {
      const field = button.getAttribute('data-admin-sort-field') || 'id';
      const nextDirection = state.adminTableSort?.field === field && state.adminTableSort?.direction === 'asc' ? 'desc' : 'asc';
      saveAdminTableSortState({ field, direction: nextDirection });
      renderApp();
    });
  });

  const adminSearch = document.getElementById('adminSearch');
  if (adminSearch) {
    adminSearch.oninput = (event) => {
      const typedValue = event.target.value;
      const cursorPosition = event.target.selectionStart ?? typedValue.length;
      state.search = typedValue;
      renderApp();

      const refreshedSearch = document.getElementById('adminSearch');
      if (refreshedSearch) {
        refreshedSearch.focus();
        const safeCursorPosition = Math.min(cursorPosition, refreshedSearch.value.length);
        refreshedSearch.setSelectionRange(safeCursorPosition, safeCursorPosition);
      }
    };
  }

  const printMyIdButton = document.getElementById('printMyIdButton');
  if (printMyIdButton) {
    printMyIdButton.addEventListener('click', () => {
      const record = getUserRecord();
      if (printIdCard(record)) {
        setFlashMessage('Print dialog opened for your ID card.');
        renderApp();
      }
    });
  }

  const downloadMyIdButton = document.getElementById('downloadMyIdButton');
  if (downloadMyIdButton) {
    downloadMyIdButton.addEventListener('click', () => {
      const record = getUserRecord();
      if (downloadIdCard(record)) {
        renderApp();
      }
    });
  }

  const printIdBtn = document.getElementById('printIdBtn');
  if (printIdBtn) {
    printIdBtn.addEventListener('click', () => {
      const record = getCurrentPrintableRecord();
      if (!record) {
        setFlashMessage('ID record not found. Unable to print.');
        renderApp();
        return;
      }
      if (printIdCard(record)) {
        setFlashMessage('Print dialog opened for the selected ID card.');
        renderApp();
      }
    });
  }

  const idDesignAccentColor = document.getElementById('idDesignAccentColor');
  const idDesignAccentColorCode = document.getElementById('idDesignAccentColorCode');
  if (idDesignAccentColor && idDesignAccentColorCode) {
    idDesignAccentColor.addEventListener('input', () => {
      idDesignAccentColorCode.textContent = String(idDesignAccentColor.value || '').toUpperCase();
    });
  }

  document.querySelectorAll('[data-status-filter]').forEach((button) => {
    button.addEventListener('click', () => {
      const filterValue = button.getAttribute('data-status-filter');
      if (!filterValue) return;
      state.statusFilter = filterValue;
      renderApp();
    });
  });

  document.querySelectorAll('[data-history-filter]').forEach((button) => {
    button.addEventListener('click', () => {
      const filterValue = String(button.getAttribute('data-history-filter') || 'all').toLowerCase();
      state.historyFilter = filterValue;
      renderApp();
    });
  });

  const registerForm = document.getElementById('registerForm');
  if (registerForm) {
    registerForm.onsubmit = async (event) => {
      event.preventDefault();
      const formData = new FormData(registerForm);
      const username = String(formData.get('username')).trim();
      const password = String(formData.get('password'));
      const firstName = String(formData.get('firstName')).trim();
      const lastName = String(formData.get('lastName')).trim();
      const photoData = await fileToDataUrl(formData.get('idPhotoFile'));
      const signatureData = await fileToDataUrl(formData.get('idSignatureFile'));

      if (!photoData || !signatureData) {
        openAlertModal('Invalid Registration', 'Please import both photo and signature images.');
        renderApp();
        return;
      }

      if (!username || !password || !firstName || !lastName) {
        openAlertModal('Invalid Registration', 'Please complete all required account fields.');
        renderApp();
        return;
      }

      const usernameExists = state.records.some((record) => String(record.username).toLowerCase() === username.toLowerCase());
      if (usernameExists) {
        openAlertModal('Invalid Account', 'You already have an account. Username is already in use.');
        renderApp();
        return;
      }

      const currentYear = new Date().getFullYear();
      const localRecord = {
        id: getNextFisherfolkId(),
        username,
        password,
        firstName,
        middleName: String(formData.get('middleName') || '').trim(),
        lastName,
        barangay: normalizeBarangayValue(formData.get('barangay')),
        livelihood: String(formData.get('livelihood')).trim(),
        contact: String(formData.get('contact')).trim(),
        fishrNumber: String(formData.get('fishrNumber') || '').trim(),
        rsbsaNumber: String(formData.get('rsbsaNumber') || '').trim(),
        emergencyName: String(formData.get('emergencyName') || '').trim(),
        emergencyRelation: String(formData.get('emergencyRelation') || '').trim(),
        emergencyAddress: String(formData.get('emergencyAddress') || '').trim(),
        emergencyContact: String(formData.get('emergencyContact') || '').trim(),
        status: String(formData.get('status')).trim() || 'Pending',
        validity: `12/${currentYear + 1}`,
        gender: String(formData.get('gender')).trim(),
        birthDate: String(formData.get('birthDate')),
        photo: 'Uploaded Photo',
        photoData,
        signatureData,
        idDesign: { ...defaultIdDesign }
      };

      if (window.phpRecordsData) {
        const result = await saveToDatabase('add_record', {
          username,
          password,
          firstName,
          middleName: String(formData.get('middleName') || '').trim(),
          lastName,
          birthDate: String(formData.get('birthDate') || '').trim(),
          gender: String(formData.get('gender') || '').trim(),
          barangay: normalizeBarangayValue(formData.get('barangay')),
          livelihood: String(formData.get('livelihood') || '').trim(),
          contact: String(formData.get('contact') || '').trim(),
          status: String(formData.get('status') || 'Pending').trim(),
          fishrNumber: String(formData.get('fishrNumber') || '').trim(),
          rsbsaNumber: String(formData.get('rsbsaNumber') || '').trim(),
          emergencyName: String(formData.get('emergencyName') || '').trim(),
          emergencyRelation: String(formData.get('emergencyRelation') || '').trim(),
          emergencyAddress: String(formData.get('emergencyAddress') || '').trim(),
          emergencyContact: String(formData.get('emergencyContact') || '').trim(),
          photoData,
          signatureData
        });

        if (!result.success) {
          if (String(result.error || result.message || '').toLowerCase().includes('username already exists')) {
            openAlertModal('Invalid Account', 'You already have an account. Username is already in use.');
          }
          renderApp();
          return;
        }

        const dbRecord = result.record ? {
          ...result.record,
          photoData,
          signatureData,
          idDesign: { ...defaultIdDesign }
        } : localRecord;

        await refreshRecordsFromDatabase();
        state.currentView = 'dashboard';
        state.selectedAdminRecordId = dbRecord.id;
        state.search = '';
        state.statusFilter = 'all';
        state.barangayFilter = 'all';
        if (typeof window.setAdminCurrentView === 'function') {
          window.setAdminCurrentView('dashboard');
        }
        registerForm.reset();
        setFlashMessage(`New fisherfolk account created: ${dbRecord.id} (${dbRecord.username}).`);
        renderApp();
        openSuccessModal('Generate ID successfully', 'The new fisherfolk ID has been created and added to the system.');
        return;
      }

      state.records = [localRecord, ...state.records];
      state.currentView = 'dashboard';
      state.selectedAdminRecordId = localRecord.id;
      state.search = '';
      state.statusFilter = 'all';
      state.barangayFilter = 'all';
      if (typeof window.setAdminCurrentView === 'function') {
        window.setAdminCurrentView('dashboard');
      }
      saveJson(storageKeys.records, state.records);
      syncBarangayOptionsWithRecords();
      registerForm.reset();
      setFlashMessage(`New fisherfolk account created: ${localRecord.id} (${localRecord.username}).`);
      renderApp();
      openSuccessModal('Generate ID successfully', 'The new fisherfolk ID has been created and added to the system.');
    };
  }

  const adminStatusFilter = document.getElementById('adminStatusFilter');
  if (adminStatusFilter) {
    adminStatusFilter.addEventListener('change', () => {
      state.statusFilter = String(adminStatusFilter.value || 'all');
      renderApp();
    });
  }

  const adminBarangayFilter = document.getElementById('adminBarangayFilter');
  if (adminBarangayFilter) {
    adminBarangayFilter.addEventListener('change', () => {
      state.barangayFilter = String(adminBarangayFilter.value || 'all');
      renderApp();
    });
  }

  const toggleSubsidyCustomizationFields = () => {
    const colorPresetField = document.getElementById('boatColorPreset');
    const colorCustomWrap = document.getElementById('boatColorCustomWrap');
    const colorCustomField = document.getElementById('boatColorCustom');
    const sizeField = document.getElementById('boatSize');
    const sizeCustomWrap = document.getElementById('boatSizeCustomWrap');
    const sizeCustomField = document.getElementById('boatSizeCustom');

    if (colorPresetField && colorCustomWrap && colorCustomField) {
      const showCustomColor = colorPresetField.value === 'Custom';
      colorCustomWrap.classList.toggle('hidden', !showCustomColor);
      colorCustomField.required = showCustomColor;
      if (!showCustomColor) colorCustomField.value = '';
    }

    if (sizeField && sizeCustomWrap && sizeCustomField) {
      const showCustomSize = sizeField.value === 'Custom';
      sizeCustomWrap.classList.toggle('hidden', !showCustomSize);
      sizeCustomField.required = showCustomSize;
      if (!showCustomSize) sizeCustomField.value = '';
    }
  };

  const subsidyForm = document.getElementById('subsidyForm');
  if (subsidyForm) {
    const colorPresetField = document.getElementById('boatColorPreset');
    const sizeField = document.getElementById('boatSize');

    if (colorPresetField) {
      colorPresetField.addEventListener('change', toggleSubsidyCustomizationFields);
    }
    if (sizeField) {
      sizeField.addEventListener('change', toggleSubsidyCustomizationFields);
    }
    toggleSubsidyCustomizationFields();

    subsidyForm.onsubmit = (event) => {
      event.preventDefault();
      const formData = new FormData(subsidyForm);
      const record = getUserRecord();
      if (!record) return;

      const boatColorPreset = String(formData.get('boatColorPreset') || '').trim();
      const boatColorCustom = String(formData.get('boatColorCustom') || '').trim();
      const boatSizeSelected = String(formData.get('boatSize') || '').trim();
      const boatSizeCustom = String(formData.get('boatSizeCustom') || '').trim();

      const boatColor = boatColorPreset === 'Custom' ? boatColorCustom : boatColorPreset;
      const boatSize = boatSizeSelected === 'Custom' ? boatSizeCustom : boatSizeSelected;

      if (!boatColor || !boatSize) {
        setFlashMessage('Please complete custom boat color and size details.');
        renderApp();
        return;
      }

      const request = {
        id: `RQ-${Date.now()}`,
        userId: record.id,
        type: 'Subsidy',
        subject: 'Boat Damage Subsidy',
        boatName: String(formData.get('boatName')).trim(),
        boatType: String(formData.get('boatType')).trim(),
        boatColor,
        boatSize,
        incidentDate: String(formData.get('incidentDate')).trim(),
        damageCost: String(formData.get('damageCost')).trim(),
        message: String(formData.get('incidentDetails')).trim(),
        contactPreference: String(formData.get('contactPreference')).trim(),
        status: 'Pending',
        createdAt: new Date().toISOString()
      };

      state.requests = [request, ...state.requests];

      // Always save to PHP database
      saveToDatabase('add_request', {
        fisherfolkId: record.id,
        requestType: 'Subsidy',
        subject: request.subject,
        description: request.message,
        boatName: request.boatName,
        boatType: request.boatType,
        boatColor: request.boatColor,
        boatSize: request.boatSize,
        incidentDate: request.incidentDate,
        damageCost: request.damageCost
      });
      // Also save to localStorage as backup
      saveJson(storageKeys.requests, state.requests);

      subsidyForm.reset();
      toggleSubsidyCustomizationFields();
      state.currentView = 'history';
      setFlashMessage('Subsidy report submitted. You were redirected to My History for tracking.');
      renderApp();
      openSuccessModal('Subsidy report submitted', 'Your subsidy request was saved successfully and moved to My History.');
    };
  }

  const announcementForm = document.getElementById('announcementForm');
  if (announcementForm) {
    announcementForm.onsubmit = (event) => {
      event.preventDefault();
      const formData = new FormData(announcementForm);
      const announcement = {
        id: `ANN-${Date.now()}`,
        title: String(formData.get('announcementTitle')).trim(),
        message: String(formData.get('announcementMessage')).trim(),
        createdAt: new Date().toISOString()
      };

      state.announcements = [...state.announcements, announcement];

      // Save to PHP database if available
      if (window.phpAnnouncementsData) {
        saveToDatabase('add_announcement', {
          title: announcement.title,
          message: announcement.message
        });
      } else {
        saveJson(storageKeys.announcements, state.announcements);
      }

      announcementForm.reset();
      setFlashMessage('Announcement published successfully.');
      renderApp();
    };
  }

  const modalAdminSettingsForm = document.getElementById('modalAdminSettingsForm');
  if (modalAdminSettingsForm) {
    modalAdminSettingsForm.onsubmit = (event) => {
      event.preventDefault();
      const formData = new FormData(modalAdminSettingsForm);
      const newEmail = String(formData.get('adminEmail')).trim();
      const newPassword = String(formData.get('adminPassword'));

      console.log('Saving admin credentials:', { newEmail, newPassword });

      // Send to backend to update database
      fetch('admin.php?action=update_credentials', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: newEmail,
          password: newPassword
        })
      })
      .then(response => {
        console.log('Response status:', response.status);
        return response.text().then(text => {
          console.log('Response text:', text);
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error('Failed to parse response as JSON:', text);
            throw new Error('Invalid response: ' + text);
          }
        });
      })
      .then(data => {
        console.log('Response data:', data);
        if (data.ok) {
          // Update local storage only after successful backend update
          adminAccount.email = newEmail;
          adminAccount.password = newPassword;

          saveJson(storageKeys.adminSettings, {
            email: adminAccount.email,
            password: adminAccount.password,
            code: adminAccount.code // Keep existing code in storage
          });

          closeAdminAccountSettingsModal();
          setFlashMessage('Admin account credentials updated successfully.');
          renderApp();
          openSuccessModal('Account Settings Updated', 'Your administrative email and password have been changed successfully. You may need to log in again with your new credentials.');
        } else {
          setFlashMessage('Error: ' + (data.message || 'Failed to update credentials'));
          renderApp();
        }
      })
      .catch(error => {
        console.error('Error updating credentials:', error);
        setFlashMessage('Error: ' + error.message);
        renderApp();
      });
    };
  }

  const barangaySettingsForm = document.getElementById('barangaySettingsForm');
  if (barangaySettingsForm) {
    barangaySettingsForm.onsubmit = (event) => {
      event.preventDefault();
      const formData = new FormData(barangaySettingsForm);
      const candidate = String(formData.get('newBarangay') || '');
      const result = addBarangayOption(candidate);
      if (!result.ok) {
        setFlashMessage(result.message);
        renderApp();
        return;
      }
      barangaySettingsForm.reset();
      setFlashMessage(`Barangay added: ${result.value}.`);
      renderApp();
    };
  }

  document.querySelectorAll('[data-archive-barangay]').forEach((button) => {
    button.addEventListener('click', () => {
      const barangayName = String(button.getAttribute('data-archive-barangay') || '');
      const result = archiveBarangayOption(barangayName);
      if (!result.ok) {
        setFlashMessage(result.message);
        renderApp();
        return;
      }
      setFlashMessage(`Barangay archived: ${result.value}.`);
      renderApp();
    });
  });

  document.querySelectorAll('[data-restore-barangay]').forEach((button) => {
    button.addEventListener('click', () => {
      const barangayName = String(button.getAttribute('data-restore-barangay') || '');
      const result = restoreBarangayOption(barangayName);
      if (!result.ok) {
        setFlashMessage(result.message);
        renderApp();
        return;
      }
      setFlashMessage(`Barangay restored: ${result.value}.`);
      renderApp();
    });
  });

  document.querySelectorAll('[data-view-barangay-residents]').forEach((button) => {
    button.addEventListener('click', () => {
      const barangayName = String(button.getAttribute('data-view-barangay-residents') || '');
      viewBarangayResidents(barangayName);
    });
  });

  const idDesignForm = document.getElementById('idDesignForm');
  if (idDesignForm) {
    idDesignForm.onsubmit = (event) => {
      event.preventDefault();
      const formData = new FormData(idDesignForm);
      const record = getUserRecord();
      if (!record) return;

      const nextThemeKey = String(formData.get('idDesignTheme') || '').trim() || defaultIdDesign.themeKey;
      const nextAccentColorRaw = String(formData.get('idDesignAccentColor') || '').trim();
      const nextAccentColor = nextAccentColorRaw || defaultIdDesign.accentColor;
      // Preserve title/subtitle/badge set by admin or defaults; fisherfolk may only change theme and accent
      const currentDesign = getIdDesign(record);
      const nextDesign = {
        themeKey: nextThemeKey,
        title: currentDesign.title,
        subtitle: currentDesign.subtitle,
        badge: currentDesign.badge,
        accentColor: nextAccentColor
      };

      if (updateRecordFields(record.id, { idDesign: nextDesign })) {
        setFlashMessage('ID design saved successfully.');
        renderApp();
        openSuccessModal('ID design saved successfully', 'Your fisherfolk ID theme changes were saved.');
        return;
      }
      renderApp();
    };

    // Live preview: update ID preview while editing theme/accent (does not persist until save)
    (function bindIdDesignLivePreview() {
      const themeSelect = idDesignForm.querySelector('#idDesignTheme');
      const accentInput = idDesignForm.querySelector('#idDesignAccentColor');

      function refreshPreviewFromForm() {
        const record = getUserRecord();
        if (!record) return;
        const currentDesign = {
          themeKey: (themeSelect && themeSelect.value) || (record.idDesign && record.idDesign.themeKey) || defaultIdDesign.themeKey,
          title: record.idDesign && record.idDesign.title ? record.idDesign.title : defaultIdDesign.title,
          subtitle: record.idDesign && record.idDesign.subtitle ? record.idDesign.subtitle : defaultIdDesign.subtitle,
          badge: record.idDesign && record.idDesign.badge ? record.idDesign.badge : defaultIdDesign.badge,
          accentColor: (accentInput && String(accentInput.value).trim()) || (record.idDesign && record.idDesign.accentColor) || ''
        };

        const previewWrap = document.querySelector('.id-card-print-wrap');
        if (!previewWrap) return;

        const mock = { ...record, idDesign: currentDesign };
        const buttons = previewWrap.querySelector('.button-row');
        const buttonsHtml = buttons ? buttons.outerHTML : '';
        previewWrap.innerHTML = renderIdCardMarkup(mock) + buttonsHtml;
      }

      [themeSelect, accentInput].forEach((el) => {
        if (!el) return;
        el.addEventListener('input', refreshPreviewFromForm);
        el.addEventListener('change', refreshPreviewFromForm);
      });
    })();
  }

  document.querySelectorAll('[data-id-design-reset]').forEach((button) => {
    button.addEventListener('click', () => {
      const record = getUserRecord();
      if (!record) return;

      if (updateRecordFields(record.id, { idDesign: { ...defaultIdDesign } })) {
        setFlashMessage('ID design restored to the default theme.');
      }
      renderApp();
    });
  });

  document.querySelectorAll('[data-admin-action]').forEach((button) => {
    button.addEventListener('click', (event) => {
      // Prevent row-level click handlers from firing when pressing action buttons.
      event.stopPropagation();
      const action = button.getAttribute('data-admin-action');
      const recordId = button.getAttribute('data-id');

      if (action === 'focus-selected' && recordId) {
        openMasterDetailModalById(recordId);
        return;
      }

      if (action === 'approve-record' && recordId) {
        if (updateRecordStatus(recordId, 'Active')) {
          setFlashMessage(`${recordId} marked as Active.`);
        }
        renderApp();
        return;
      }

      if (action === 'expire-record' && recordId) {
        if (updateRecordStatus(recordId, 'Expired')) {
          setFlashMessage(`${recordId} marked as Expired.`);
        }
        renderApp();
        return;
      }

      if (action === 'archive-record' && recordId) {
        const target = getRecordById(recordId);
        if (!target) return;
        const shouldArchive = window.confirm(`Archive ${target.firstName} ${target.lastName} (${recordId})? It will move to Archive Storage.`);
        if (!shouldArchive) return;
        if (archiveRecord(recordId)) {
          setFlashMessage(`${recordId} moved to archive storage.`);
        }
        renderApp();
        return;
      }

      if (action === 'restore-record' && recordId) {
        const target = (state.archivedRecords || []).find((item) => item.id === recordId);
        if (!target) return;
        const shouldRestore = window.confirm(`Restore ${target.firstName} ${target.lastName} (${recordId}) to the active registry?`);
        if (!shouldRestore) return;
        if (restoreArchivedRecord(recordId)) {
          setFlashMessage(`${recordId} restored to active registry.`);
        }
        renderApp();
        return;
      }

      if (action === 'print-id' && recordId) {
        const targetRecord = getRecordById(recordId);
        if (!targetRecord) return;
        if (printIdCard(targetRecord) && updateRecordStatus(recordId, 'Active')) {
          setFlashMessage(`${recordId} print dialog opened and status set to Active.`);
        }
        renderApp();
        return;
      }

      if (action === 'download-id' && recordId) {
        const targetRecord = getRecordById(recordId);
        if (!targetRecord) return;
        if (downloadIdCard(targetRecord)) {
          setFlashMessage(`${recordId} downloaded as printable ID file.`);
        }
        renderApp();
        return;
      }

      if (action === 'expire-id' && recordId) {
        if (updateRecordStatus(recordId, 'Expired')) {
          setFlashMessage(`${recordId} removed from queue and marked Expired.`);
        }
        renderApp();
        return;
      }

      if (action === 'print-records-report') {
        printRecordsReport();
        renderApp();
        return;
      }

      if (action === 'print-requests-report') {
        printRequestsReport();
        renderApp();
        return;
      }

      if (action === 'bulk-print-id') {
        if (!state.selectedQueueIds.length) return;

        const modal = document.getElementById('bulkPrintConfirmModal');
        const message = document.getElementById('bulkPrintConfirmMessage');
        if (modal && message) {
          message.textContent = `Are you sure you want to print ${state.selectedQueueIds.length} selected IDs? This will also mark them as Active.`;
          modal.classList.remove('hidden');
        }
        return;
      }

      if (action === 'clear-queue-selection') {
        state.selectedQueueIds = [];
        renderApp();
        return;
      }

      if (action === 'export-records') {
        exportAdminReport('records');
        renderApp();
        return;
      }

      if (action === 'export-requests') {
        exportAdminReport('requests');
        renderApp();
      }
    });
  });

  document.querySelectorAll('[data-admin-focus-record]').forEach((row) => {
    row.addEventListener('click', (event) => {
      const interactive = event.target.closest('button, select, input, textarea, a, label');
      if (interactive) return;
      const recordId = row.getAttribute('data-id');
      if (recordId) {
        state.selectedAdminRecordId = recordId;
        renderApp();
      }
    });

    row.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' && event.key !== ' ') return;
      event.preventDefault();
      const recordId = row.getAttribute('data-id');
      if (recordId) {
        state.selectedAdminRecordId = recordId;
        renderApp();
      }
    });
  });

  document.querySelectorAll('[data-admin-status-select]').forEach((select) => {
    select.addEventListener('click', (event) => {
      event.stopPropagation();
    });

    select.addEventListener('change', () => {
      const nextStatus = select.value;
      const recordId = select.getAttribute('data-id');
      if (!nextStatus || !recordId) return;

      if (updateRecordStatus(recordId, nextStatus)) {
        setFlashMessage(`${recordId} status updated to ${nextStatus}.`);
      }
      renderApp();
    });
  });

  document.querySelectorAll('[data-announcement-delete]').forEach((button) => {
    button.addEventListener('click', () => {
      const announcementId = button.getAttribute('data-announcement-delete');
      if (!announcementId) return;
      state.announcements = state.announcements.filter((item) => item.id !== announcementId);
      saveJson(storageKeys.announcements, state.announcements);
      setFlashMessage('Announcement removed successfully.');
      renderApp();
    });
  });

  document.querySelectorAll('[data-subsidy-status-select]').forEach((select) => {
    select.addEventListener('change', () => {
      const requestId = select.getAttribute('data-id');
      const nextStatus = select.value;
      if (!requestId || !nextStatus) return;

      if (updateRequestFields(requestId, { status: nextStatus })) {
        setFlashMessage(`${requestId} subsidy report marked as ${nextStatus}.`);
      }
      renderApp();
    });
  });

  document.querySelectorAll('[data-subsidy-note-save]').forEach((button) => {
    button.addEventListener('click', () => {
      const requestId = button.getAttribute('data-id');
      if (!requestId) return;

      const noteInput = document.querySelector(`[data-subsidy-note-input][data-id="${requestId}"]`);
      const note = noteInput ? String(noteInput.value || '').trim() : '';

      if (updateRequestFields(requestId, { adminNote: note })) {
        setFlashMessage(`${requestId} note saved successfully.`);
      }
      renderApp();
    });
  });

  // Printing Queue Selection Listeners
  const selectAllCheckbox = document.getElementById('selectAllQueue');
  if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', () => {
      const queue = state.records.filter((item) => item.status === 'Pending' || item.status === 'Active');
      if (selectAllCheckbox.checked) {
        state.selectedQueueIds = queue.map(item => item.id);
      } else {
        state.selectedQueueIds = [];
      }
      renderApp();
    });
  }

  document.querySelectorAll('.queue-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', () => {
      const id = checkbox.getAttribute('data-id');
      if (checkbox.checked) {
        if (!state.selectedQueueIds.includes(id)) {
          state.selectedQueueIds.push(id);
        }
      } else {
        state.selectedQueueIds = state.selectedQueueIds.filter(itemId => itemId !== id);
      }
      renderApp();
    });
  });
}

document.querySelectorAll('.portal-tab').forEach((button) => {
  button.addEventListener('click', () => {
    const target = button.getAttribute('data-portal-target');
    if (!target) return;
    setPortalView(target);
  });
});

document.querySelectorAll('.auth-tab').forEach((button) => {
  button.addEventListener('click', () => {
    const target = button.getAttribute('data-auth-target');
    if (!target) return;
    setUserAuthView(target);
  });
});

const userLoginForm = document.getElementById('userLoginForm');
if (userLoginForm) {
  userLoginForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    loginAsUser(String(formData.get('userIdentifier')), String(formData.get('userPassword')));
  });
}

const adminLoginForm = document.getElementById('adminLoginForm');
if (adminLoginForm) {
  adminLoginForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const formData = new FormData(event.currentTarget);
    loginAsAdmin(String(formData.get('adminIdentifier')), String(formData.get('adminPassword')), String(formData.get('adminCode')));
  });
}

document.querySelectorAll('[data-toggle-password]').forEach((button) => {
  // Initialize aria-label on page load
  const inputId = button.getAttribute('data-toggle-password');
  if (inputId) {
    const input = document.getElementById(inputId);
    if (input) {
      const isMasked = input.getAttribute('type') === 'password';
      button.setAttribute('aria-label', isMasked ? 'Show password' : 'Hide password');
    }
  }

  button.addEventListener('click', () => {
    const inputId = button.getAttribute('data-toggle-password');
    if (!inputId) return;
    const input = document.getElementById(inputId);
    if (!input || input.tagName !== 'INPUT') return;

    try {
      const isMasked = input.getAttribute('type') === 'password';
      input.setAttribute('type', isMasked ? 'text' : 'password');
      const isVisible = !isMasked;
      button.classList.toggle('active', isVisible);
      button.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
    } catch (err) {
      console.error('Password toggle error:', err);
    }
  });
});

function jumpToPortal(targetId, focusInputId) {
  setPortalView(targetId);
  if (!focusInputId) return;

  window.setTimeout(() => {
    const input = document.getElementById(focusInputId);
    if (input && typeof input.focus === 'function') {
      input.focus();
    }
  }, 140);
}

const goToAdminPortalButton = document.getElementById('goToAdminPortalButton');
if (goToAdminPortalButton) {
  goToAdminPortalButton.addEventListener('click', () => {
    jumpToPortal('adminPortalView', 'adminIdentifier');
  });
}

const goToUserPortalButton = document.getElementById('goToUserPortalButton');
if (goToUserPortalButton) {
  goToUserPortalButton.addEventListener('click', () => {
    jumpToPortal('userPortalView', 'userIdentifier');
  });
}

const logoutModal = document.getElementById('logoutModal');
const masterDetailModal = document.getElementById('masterDetailModal');
const registrationSuccessModal = document.getElementById('registrationSuccessModal');
const cancelLogoutButton = document.getElementById('cancelLogoutButton');
const confirmLogoutButton = document.getElementById('confirmLogoutButton');
const closeMasterDetailButton = document.getElementById('closeMasterDetailModal');
const closeRegistrationSuccessButton = document.getElementById('closeRegistrationSuccessModal');

if (cancelLogoutButton) {
  cancelLogoutButton.addEventListener('click', closeLogoutModal);
}

if (confirmLogoutButton) {
  confirmLogoutButton.addEventListener('click', logout);
}

if (logoutModal) {
  logoutModal.addEventListener('click', (event) => {
    if (event.target === logoutModal) {
      closeLogoutModal();
    }
  });
}

if (closeMasterDetailButton) {
  closeMasterDetailButton.addEventListener('click', closeMasterDetailModal);
}

if (masterDetailModal) {
  masterDetailModal.addEventListener('click', (event) => {
    if (event.target === masterDetailModal) {
      closeMasterDetailModal();
    }
  });
}

if (closeRegistrationSuccessButton) {
  closeRegistrationSuccessButton.addEventListener('click', closeRegistrationSuccessModal);
}

// Bulk Print Modal Listeners
const confirmBulkPrintButton = document.getElementById('confirmBulkPrintButton');
const cancelBulkPrintButton = document.getElementById('cancelBulkPrintButton');
const bulkPrintConfirmModal = document.getElementById('bulkPrintConfirmModal');

if (confirmBulkPrintButton) {
  confirmBulkPrintButton.addEventListener('click', () => {
    if (state.selectedQueueIds.length > 0) {
      const recordsToPrint = state.selectedQueueIds
        .map(id => getRecordById(id))
        .filter(Boolean);

      if (printBulkIdCards(recordsToPrint)) {
        state.selectedQueueIds.forEach(recordId => {
          updateRecordStatus(recordId, 'Active');
        });
        setFlashMessage(`Combined print dialog opened for ${state.selectedQueueIds.length} records.`);
        state.selectedQueueIds = [];
        if (bulkPrintConfirmModal) bulkPrintConfirmModal.classList.add('hidden');
        renderApp();
      }
    }
  });
}

if (cancelBulkPrintButton) {
  cancelBulkPrintButton.addEventListener('click', () => {
    if (bulkPrintConfirmModal) bulkPrintConfirmModal.classList.add('hidden');
  });
}

if (bulkPrintConfirmModal) {
  bulkPrintConfirmModal.addEventListener('click', (event) => {
    if (event.target === bulkPrintConfirmModal) {
      bulkPrintConfirmModal.classList.add('hidden');
    }
  });
}

// Add global Escape key support to close modals
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' || event.key === 'Esc') {
    const logoutModal = document.getElementById('logoutModal');
    const addBarangayModal = document.getElementById('addBarangayModal');
    const masterDetailModal = document.getElementById('masterDetailModal');
    const registrationSuccessModal = document.getElementById('registrationSuccessModal');

    if (logoutModal && !logoutModal.classList.contains('hidden')) {
      closeLogoutModal();
    } else if (addBarangayModal && !addBarangayModal.classList.contains('hidden')) {
      closeAddBarangayModal();
    } else if (masterDetailModal && !masterDetailModal.classList.contains('hidden')) {
      closeMasterDetailModal();
    } else if (registrationSuccessModal && !registrationSuccessModal.classList.contains('hidden')) {
      closeRegistrationSuccessModal();
    }
  }
});

if (registrationSuccessModal) {
  registrationSuccessModal.addEventListener('click', (event) => {
    if (event.target === registrationSuccessModal) {
      closeRegistrationSuccessModal();
    }
  });
}

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  const modal = document.getElementById('logoutModal');
  if (modal && !modal.classList.contains('hidden')) {
    closeLogoutModal();
    return;
  }
  const masterModal = document.getElementById('masterDetailModal');
  if (masterModal && !masterModal.classList.contains('hidden')) {
    closeMasterDetailModal();
  }
});

if (window.phpUserData && document.getElementById('appShell')) {
  // PHP-authenticated pages must use server session as source of truth.
  persistSession();
  renderApp();
} else {
  (function restoreSession() {
    try {
      const raw = sessionStorage.getItem('fisherfolkSession');
      if (!raw) return;

      const parsed = JSON.parse(raw);
      if (parsed.role === 'admin') {
        state.role = 'admin';
        state.account = adminAccount;
        const adminViews = roleViews.admin.map((item) => item.id);
        state.currentView = getRequestedView('admin') || (adminViews.includes(parsed.currentView) ? parsed.currentView : getDefaultView('admin'));
        hideLoginShowApp();
        return;
      }

      const record = state.records.find((item) => item.id === parsed.accountId);
      if (record) {
        state.role = 'user';
        state.account = record;
        const userViews = roleViews.user.map((item) => item.id);
        state.currentView = getRequestedView('user') || (userViews.includes(parsed.currentView) ? parsed.currentView : getDefaultView('user'));
        hideLoginShowApp();
      }
    } catch {
      sessionStorage.removeItem('fisherfolkSession');
    }
  })();
}


/* Modal Form Handling Overrides */
document.addEventListener('submit', function (e) {
  if (e.target && e.target.id === 'modalBarangayForm') {
    e.preventDefault();
    const formData = new FormData(e.target);
    const candidate = String(formData.get('newBarangay') || '');
    const result = addBarangayOption(candidate);
    if (!result.ok) {
      setFlashMessage(result.message);
      renderApp();
      return;
    }
    closeAddBarangayModal();
    openSuccessModal('Barangay Added Successfully', 'Barangay ' + result.value + ' has been added to the system.');
    renderApp();
  }
});

/* ========== COLLAPSIBLE SIDEBAR LOGIC ========== */
window.toggleSidebarCollapse = function () {
  try {
    const sidebar = document.getElementById('mainSidebar');
    const appShell = document.getElementById('appShell');
    const canvas = document.querySelector('.canvas');

    if (!sidebar) {
      console.warn('Sidebar not found');
      return;
    }

    const isCollapsed = sidebar.classList.toggle('collapsed');

    // Toggle classes on containers
    if (canvas) canvas.classList.toggle('sidebar-collapsed', isCollapsed);
    if (appShell) appShell.classList.toggle('is-collapsed', isCollapsed);

    localStorage.setItem('sidebarCollapsed', isCollapsed);

    // Update arrow direction
    const btn = document.getElementById('sidebarCollapseToggle');
    if (btn) btn.textContent = isCollapsed ? '›' : '‹';
  } catch (err) {
    console.error('Sidebar toggle error:', err);
  }
};

// Restore sidebar state on page load
(function initSidebarState() {
  const apply = () => {
    const sidebar = document.getElementById('mainSidebar');
    const appShell = document.getElementById('appShell');
    const canvas = document.querySelector('.canvas');
    const btn = document.getElementById('sidebarCollapseToggle');

    if (!sidebar) return;

    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
      sidebar.classList.add('collapsed');
      if (canvas) canvas.classList.add('sidebar-collapsed');
      if (appShell) appShell.classList.add('is-collapsed');
      if (btn) btn.textContent = '›';
    }

    // Mobile: Close sidebar when clicking the overlay (background dim)
    if (appShell) {
      appShell.addEventListener('click', (e) => {
        if (window.innerWidth <= 1024) {
          // If clicking precisely the appShell (which is where our ::after overlay is)
          // and the sidebar is currently NOT collapsed
          if (e.target === appShell && !sidebar.classList.contains('collapsed')) {
            window.toggleSidebarCollapse();
          }
        }
      });
    }
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', apply);
  } else {
    apply();
  }
})();

