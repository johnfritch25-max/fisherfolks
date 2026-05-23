# fisherfolks

Fisherfolk Information Management with ID System

This repository contains a PHP application to manage fisherfolk records and generate IDs.
# 🎣 Fisherfolk IMS - New Features Implementation

All requested features have been successfully implemented and are ready to use!

## ✨ Features Implemented

### 1. **🏠 Homepage/Landing Page** (NEW)
- **File**: `homepage.php`
- **Who**: Fisherfolk users (non-admin)
- **Features**:
  - Facebook-like community feed
  - Share opinions and views
  - Post creation interface
  - Important announcements widget
  - Features showcase
  - Dark/light mode toggle built-in
  - Responsive sidebar navigation

### 2. **🌙 Dark Mode / Light Mode Toggle** (NEW)
- **Location**: Homepage sidebar
- **Button**: "🌙 Dark Mode" / "☀️ Light Mode"
- **Persistence**: Saves to browser localStorage
- **Coverage**: Full UI support for all elements
- **Files**: 
  - `homepage.php` - JavaScript toggle logic
  - `gui-override.css` - 130+ lines of dark mode CSS

### 3. **🎨 Color Customization System** (NEW)
- **File**: `theme_settings.php`
- **Access**: Admin Panel → "🎨 Theme Colors" (in sidebar)
- **Who**: Admin only
- **Customizable**:
  - Sidebar color
  - Body background color
  - Text/font color
  - Accent color (buttons, highlights)
- **Features**:
  - Live preview of changes
  - Color picker interface
  - Hex code input
  - Reset to defaults button
  - Immediate database save
  - Changes apply to all users

### 4. **📋 Activity Logs Tracker** (NEW)
- **File**: `activity_logs.php`
- **Access**: Admin Panel → "📋 Activity Logs" (in sidebar)
- **Who**: Admin only
- **Tracks**:
  - User actions (who did what)
  - Timestamp of each action
  - Table affected
  - Record ID
  - User role
- **Features**:
  - Pagination (50 logs per page)
  - Statistics cards (total, last activity)
  - Export button (ready for CSV)
  - Sortable/filterable

### 5. **💬 Community Posts System** (NEW)
- **Location**: Homepage feed
- **Features**:
  - Create posts with text
  - View all posts (newest first)
  - Like counter
  - Comment counter
  - Author name and timestamp
- **Tracking**: All post activities logged to activity_logs

---

## 🚀 Quick Setup

### Step 1: Run Setup Verification
Visit: `http://localhost/setup_verify.php` (adjust localhost to your domain)

This will:
- ✓ Create all database tables
- ✓ Verify all files exist
- ✓ Check database connections
- ✓ Confirm feature integration

### Step 2: Test the Features

#### For Fisherfolk Users:
1. Log in with fisherfolk credentials (e.g., juan)
2. You'll be redirected to the new **homepage**
3. Test features:
   - ✓ Click "🌙 Dark Mode" to toggle
   - ✓ Write a post in the text area
   - ✓ Click "Post" button
   - ✓ View your post in the feed

#### For Admin:
1. Log in with admin credentials
2. In admin sidebar, find new options:
   - **"🎨 Theme Colors"** - Customize system colors
   - **"📋 Activity Logs"** - View all user activities
3. Try changing colors and saving

---

## 📁 All New & Modified Files

### Created Files:
```
✨ homepage.php           - Fisherfolk homepage (411 lines)
✨ theme_settings.php     - Admin color customization (398 lines)
✨ activity_logs.php      - Admin activity viewer (351 lines)
✨ database_migration.sql - Database schema (38 lines)
✨ setup_verify.php       - Setup verification & DB init
✨ IMPLEMENTATION_GUIDE.md - Detailed setup guide
✨ README.md              - This file
```

### Modified Files:
```
📝 login.php         - Updated redirect to homepage
📝 admin.php         - Added 2 new sidebar links
📝 api.php           - Added 6 new API endpoints
📝 gui-override.css  - Added 130+ dark mode lines
```

---

## 📊 Database Changes

### New Tables Created:
- `fisherfolk_posts` - Community posts storage
- `post_likes` - Post reactions tracking
- `post_comments` - Comments on posts

### Updated Tables:
- `activity_logs` - Added 4 new columns:
  - `table_name` - Which table was affected
  - `record_id` - Which record was affected
  - `old_value` - Previous value (for auditing)
  - `new_value` - New value (for auditing)

### New Settings:
- `theme_sidebar_color` → `#2c6aa2`
- `theme_body_color` → `#ffffff`
- `theme_text_color` → `#333333`
- `theme_accent_color` → `#2c6aa2`
- `theme_mode` → `light`
- `system_name` → "Fisherfolk Information Management System"

---

## 🔌 New API Endpoints

All in `api.php`:

### GET Requests:
```
GET api.php?action=get_posts
Response: { success: true, posts: [...] }

GET api.php?action=get_theme_settings
Response: { success: true, settings: {...} }
(Admin only)

GET api.php?action=get_activity_logs
Response: { success: true, logs: [...] }
(Admin only)
```

### POST Requests:
```
POST api.php
{
  "action": "create_post",
  "content": "User's post text"
}

POST api.php
{
  "action": "update_theme_settings",
  "theme_sidebar_color": "#...",
  "theme_body_color": "#...",
  "theme_text_color": "#...",
  "theme_accent_color": "#..."
}
(Admin only)

POST api.php
{
  "action": "log_activity",
  "action": "action_name",
  "table_name": "table_name",
  "record_id": 123
}
(Admin only)
```

---

## 🧪 Testing Checklist

- [ ] Visit `setup_verify.php` - All checks should pass
- [ ] Log in as fisherfolk user
- [ ] Verify redirected to `homepage.php`
- [ ] Create and submit a post
- [ ] Toggle dark mode on/off
- [ ] Log out and log back in - dark mode preference saved
- [ ] Log in as admin
- [ ] Navigate to "🎨 Theme Colors"
- [ ] Change sidebar color to blue (#2196F3)
- [ ] Click Save
- [ ] Verify sidebar changes color
- [ ] Navigate to "📋 Activity Logs"
- [ ] Verify activity entries showing
- [ ] Check timestamp is recent

---

## 🎯 File Navigation

### For Regular Users:
- `homepage.php` → Community feed, posts, announcements
- `dashboard.php` → My profile, ID details, subsidy requests

### For Admin:
- `admin.php` → Main admin dashboard
- `theme_settings.php` → Color customization
- `activity_logs.php` → Activity tracking
- `setup_verify.php` → Setup verification

---

## 🔒 Security Features

✓ All endpoints require authentication  
✓ Admin-only features locked behind `isAdmin()` check  
✓ All user inputs validated and sanitized  
✓ Prepared statements prevent SQL injection  
✓ Activity logs track all modifications  
✓ Password hashing with bcrypt  

---

## 🎨 Color Customization Guide

### Accessibility Tips:
1. **For users with light sensitivity:**
   - Dark mode: Reduce brightness
   - Change sidebar to darker color (#1a2a3a)
   - Keep text white for contrast

2. **For users with color blindness:**
   - Avoid red/green combinations
   - Use high contrast colors
   - Test with colorblind simulator

3. **For readability:**
   - Ensure text color contrasts with background
   - Text color should be dark on light backgrounds
   - Light text on dark backgrounds

### Recommended Color Combos:
- **Professional Blue**: Sidebar #2c6aa2, Accent #2196F3
- **Ocean Theme**: Sidebar #0277BD, Accent #00B0FF
- **Corporate**: Sidebar #1A237E, Accent #5E35B1
- **Natural**: Sidebar #2E7D32, Accent #4CAF50

---

## 🐛 Troubleshooting

### Homepage not loading:
```
✓ Check: File exists at http://domain/homepage.php
✓ Check: Fisherfolk user account exists
✓ Check: Login.php redirects to homepage.php
```

### Dark mode not working:
```
✓ Clear browser cache (Ctrl+Shift+Del)
✓ Check: gui-override.css has body.dark-mode styles
✓ Check: Browser supports localStorage
```

### Theme colors not saving:
```
✓ Check: Admin is logged in
✓ Check: Database admin_settings table exists
✓ Check: Write permissions on database
```

### Activity logs empty:
```
✓ Normal for fresh setup
✓ Logs created automatically when actions occur
✓ Edit a fisherfolk record to generate logs
```

### Posts not appearing:
```
✓ Check: fisherfolk_posts table exists
✓ Check: Browser console for JS errors
✓ Check: Fisherfolk user account exists
```

---

## 📞 Support Resources

1. **Setup Issues**: Check `setup_verify.php` for diagnostics
2. **API Errors**: Check browser console (F12) for errors
3. **Database Issues**: Verify in phpMyAdmin:
   - Tables exist
   - Columns are correct
   - Foreign keys are set up

---

## 📈 Future Enhancements

Ready to implement:
- [ ] Post comments (frontend)
- [ ] Post likes (frontend)
- [ ] CSV export for activity logs
- [ ] Real-time notifications
- [ ] User mentions (@username)
- [ ] Post editing/deletion
- [ ] Advanced activity log filtering
- [ ] Dark mode for admin dashboard

---

## 💡 Pro Tips

1. **Keyboard Shortcuts**: (Can be added)
   - `D` for dark mode toggle
   - `P` to create new post

2. **Batch Color Updates**: Use admin panel instead of editing database directly

3. **Activity Log Insights**: Regular review helps identify system usage patterns

4. **Backup Theme Settings**: Export color scheme for multiple installations

5. **User Training**: Point users to dark mode for accessibility

---

## ✅ Verification Status

Run `setup_verify.php` to confirm:
- All database tables created
- All files in correct locations
- All API endpoints functional
- Theme settings initialized
- Activity logs operational

---

**🎉 Your Fisherfolk IMS is now fully configured and ready to use!**

Start with the homepage, test dark mode, then explore admin features.

For questions or issues, refer to `IMPLEMENTATION_GUIDE.md` for detailed technical information.
