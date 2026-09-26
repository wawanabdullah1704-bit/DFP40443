<?php
// includes/notifications.php - Sistem Notifikasi & Avatar Profil SCRS PMU

// Pastikan jadual notifications dan lajur sokongan wujud
function init_notification_system($conn) {
    static $initialized = false;
    if ($initialized) return;

    // 1. Cipta jadual notifications jika belum wujud
    $sql_table = "CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_type` ENUM('student', 'provider', 'admin', 'jhepp') NOT NULL,
        `user_id` INT(11) NOT NULL,
        `title` VARCHAR(150) NOT NULL,
        `message` TEXT NOT NULL,
        `link` VARCHAR(255) DEFAULT NULL,
        `type` VARCHAR(50) DEFAULT 'info',
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_user` (`user_type`, `user_id`, `is_read`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $conn->query($sql_table);

    // 2. Semak jika lajur profile_picture wujud dalam jadual admins
    $res_adm = $conn->query("SHOW COLUMNS FROM `admins` LIKE 'profile_picture'");
    if ($res_adm && $res_adm->num_rows === 0) {
        $conn->query("ALTER TABLE `admins` ADD COLUMN `profile_picture` VARCHAR(255) DEFAULT NULL");
    }

    // 3. Semak jika lajur profile_picture wujud dalam jadual jhepp
    $res_jhp = $conn->query("SHOW COLUMNS FROM `jhepp` LIKE 'profile_picture'");
    if ($res_jhp && $res_jhp->num_rows === 0) {
        $conn->query("ALTER TABLE `jhepp` ADD COLUMN `profile_picture` VARCHAR(255) DEFAULT NULL");
    }

    $initialized = true;
}

// Cipta notifikasi baharu
function create_notification($conn, $user_type, $user_id, $title, $message, $link = null, $type = 'info') {
    init_notification_system($conn);
    $stmt = $conn->prepare("INSERT INTO notifications (user_type, user_id, title, message, link, type) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sissss", $user_type, $user_id, $title, $message, $link, $type);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }
    return false;
}

// Dapatkan kiraan notifikasi belum dibaca
function get_unread_notifications_count($conn, $user_type, $user_id) {
    init_notification_system($conn);
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_type = ? AND user_id = ? AND is_read = 0");
    if ($stmt) {
        $stmt->bind_param("si", $user_type, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt->close();
        return (int)$res;
    }
    return 0;
}

// Dapatkan senarai notifikasi terkini
function get_user_notifications($conn, $user_type, $user_id, $limit = 7) {
    init_notification_system($conn);
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_type = ? AND user_id = ? ORDER BY created_at DESC LIMIT ?");
    if ($stmt) {
        $stmt->bind_param("sii", $user_type, $user_id, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $list = [];
        while ($row = $res->fetch_assoc()) {
            $list[] = $row;
        }
        $stmt->close();
        return $list;
    }
    return [];
}

// Dapatkan URL gambar profil terkini pengguna
function get_user_avatar_url($conn, $user_type, $user_id) {
    init_notification_system($conn);
    $table_map = [
        'student'  => 'students',
        'provider' => 'providers',
        'admin'    => 'admins',
        'jhepp'    => 'jhepp'
    ];

    if (!isset($table_map[$user_type])) return null;
    $table = $table_map[$user_type];

    $stmt = $conn->prepare("SELECT profile_picture FROM `$table` WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!empty($row['profile_picture'])) {
            return $row['profile_picture'];
        }
    }
    return null;
}

// Format masa relatif (contoh: '2 minit lepas', '1 jam lepas')
function format_relative_time($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return "Sebentar tadi";
    if ($diff < 3600) return floor($diff / 60) . " minit lepas";
    if ($diff < 86400) return floor($diff / 3600) . " jam lepas";
    if ($diff < 604800) return floor($diff / 86400) . " hari lepas";
    return date('d/m/Y', $time);
}

// Fungsi seragam untuk render elemen kanan navbar (Butang Notifikasi + Butang Profil Gambar Sahaja)
function render_navbar_actions($conn, $user_type, $user_id, $username, $root_path = '../') {
    init_notification_system($conn);
    $unread_count = get_unread_notifications_count($conn, $user_type, $user_id);
    $notifications = get_user_notifications($conn, $user_type, $user_id, 6);
    $avatar_file = get_user_avatar_url($conn, $user_type, $user_id);

    $avatar_src = null;
    if (!empty($avatar_file)) {
        // Laraskan laluan path mengikut root_path
        $clean_path = ltrim($avatar_file, '/');
        $avatar_src = $root_path . $clean_path;
    }

    $first_letter = strtoupper(substr($username, 0, 1));
    $notif_action_url = $root_path . 'includes/notification_action.php';
    $edit_profile_url = $root_path . 'auth/edit_profile.php';
    $logout_url = $root_path . 'auth/logout.php';

    ?>
    <style>
    .nav-right-actions {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        position: relative !important;
    }
    .notif-container, .profile-container {
        position: relative !important;
        display: flex !important;
        align-items: center !important;
    }
    .notif-btn {
        width: 42px !important;
        height: 42px !important;
        min-width: 42px !important;
        border-radius: 50% !important;
        border: 2px solid #000 !important;
        background-color: #fff !important;
        color: #000 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.15rem !important;
        box-shadow: 2px 2px 0px #000 !important;
        cursor: pointer !important;
        position: relative !important;
        transition: 0.15s ease !important;
        padding: 0 !important;
    }
    .notif-btn:hover {
        transform: translate(-1.5px, -1.5px) !important;
        box-shadow: 3px 3px 0px #000 !important;
        background-color: #ffde59 !important;
    }
    .profile-btn.avatar-only {
        width: 42px !important;
        height: 42px !important;
        min-width: 42px !important;
        min-height: 42px !important;
        max-width: 42px !important;
        max-height: 42px !important;
        border-radius: 50% !important;
        border: 2px solid #000 !important;
        padding: 0 !important;
        margin: 0 !important;
        box-shadow: 2px 2px 0px #000 !important;
        overflow: hidden !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        background-color: #ffde59 !important;
        cursor: pointer !important;
        transition: 0.15s ease !important;
        position: relative !important;
        box-sizing: border-box !important;
    }
    .profile-btn.avatar-only:hover {
        transform: translate(-1.5px, -1.5px) !important;
        box-shadow: 3px 3px 0px #000 !important;
    }
    .profile-avatar-img {
        width: 100% !important;
        height: 100% !important;
        min-width: 100% !important;
        min-height: 100% !important;
        object-fit: cover !important;
        display: block !important;
        border-radius: 50% !important;
        pointer-events: none !important;
    }
    .profile-avatar-placeholder {
        width: 100% !important;
        height: 100% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 1.15rem !important;
        font-weight: 900 !important;
        color: #000 !important;
        background-color: #ffde59 !important;
        border-radius: 50% !important;
        pointer-events: none !important;
    }
    .profile-container .dropdown-menu,
    .dropdown-menu {
        display: none !important;
        position: absolute !important;
        top: calc(100% + 10px) !important;
        right: 0 !important;
        min-width: 200px !important;
        background: #ffffff !important;
        border: 3px solid #000000 !important;
        border-radius: 12px !important;
        box-shadow: 4px 4px 0px #000000 !important;
        list-style: none !important;
        padding: 8px 6px !important;
        margin: 0 !important;
        z-index: 1050 !important;
        text-align: left !important;
    }
    .profile-container .dropdown-menu.show,
    .dropdown-menu.show {
        display: block !important;
    }
    .dropdown-menu li {
        list-style: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .dropdown-header-user {
        padding: 6px 12px 8px 12px !important;
        font-size: 0.72rem !important;
        color: #666 !important;
        line-height: 1.3 !important;
    }
    .dropdown-header-user strong {
        display: block !important;
        font-size: 0.85rem !important;
        font-weight: 900 !important;
        color: #000 !important;
    }
    .dropdown-item {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 10px 12px !important;
        border-radius: 8px !important;
        font-weight: 800 !important;
        font-size: 0.85rem !important;
        color: #000 !important;
        text-transform: uppercase !important;
        text-decoration: none !important;
        transition: 0.15s ease !important;
    }
    .dropdown-item:hover {
        background-color: #ff66c4 !important;
        color: #000 !important;
    }
    .dropdown-divider {
        border: none !important;
        border-top: 2px solid #eee !important;
        margin: 6px 0 !important;
    }

    /* DROPDOWN NOTIFIKASI */
    .notif-dropdown {
        position: absolute !important;
        top: calc(100% + 10px) !important;
        right: 0 !important;
        width: 340px !important;
        max-width: calc(100vw - 24px) !important;
        background: #ffffff !important;
        border: 3px solid #000000 !important;
        border-radius: 12px !important;
        box-shadow: 4px 4px 0px #000000 !important;
        z-index: 1050 !important;
        display: none !important;
        overflow: hidden !important;
        text-align: left !important;
    }
    .notif-dropdown.show {
        display: block !important;
    }
    .notif-dropdown-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        padding: 10px 14px !important;
        border-bottom: 2px solid #000000 !important;
        background: #fdfae6 !important;
    }
    .notif-header-title {
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        font-weight: 900 !important;
        font-size: 0.88rem !important;
        color: #000000 !important;
        text-transform: uppercase !important;
    }
    .notif-count-pill {
        background: #ff66c4 !important;
        color: #000000 !important;
        font-size: 0.65rem !important;
        font-weight: 900 !important;
        padding: 2px 6px !important;
        border-radius: 999px !important;
        border: 1px solid #000000 !important;
    }
    .notif-mark-all-btn {
        background: #ffffff !important;
        border: 1.5px solid #000000 !important;
        border-radius: 6px !important;
        padding: 3px 8px !important;
        font-size: 0.72rem !important;
        font-weight: 800 !important;
        cursor: pointer !important;
        box-shadow: 1px 1px 0px #000000 !important;
        transition: 0.15s ease !important;
    }
    .notif-mark-all-btn:hover {
        background: #ffde59 !important;
    }
    .notif-list {
        max-height: 360px !important;
        overflow-y: auto !important;
    }
    .notif-item {
        display: flex !important;
        gap: 10px !important;
        padding: 12px 14px !important;
        text-decoration: none !important;
        color: #000000 !important;
        border-bottom: 1px solid #eeeeee !important;
        transition: background 0.15s ease !important;
        align-items: flex-start !important;
        position: relative !important;
    }
    .notif-item:last-child {
        border-bottom: none !important;
    }
    .notif-item:hover {
        background-color: #fcfcfc !important;
    }
    .notif-item.unread {
        background-color: #f0fdf4 !important;
        border-left: 4px solid #16a34a !important;
    }
    .notif-item-icon {
        font-size: 1.15rem !important;
        flex-shrink: 0 !important;
        margin-top: 1px !important;
    }
    .notif-item-content {
        flex: 1 !important;
        min-width: 0 !important;
    }
    .notif-item-title {
        font-weight: 900 !important;
        font-size: 0.82rem !important;
        color: #000000 !important;
        line-height: 1.3 !important;
    }
    .notif-item-msg {
        font-size: 0.76rem !important;
        color: #444444 !important;
        line-height: 1.35 !important;
        margin-top: 2px !important;
        word-break: break-word !important;
    }
    .notif-item-time {
        font-size: 0.68rem !important;
        font-weight: 700 !important;
        color: #777777 !important;
        margin-top: 4px !important;
        display: flex !important;
        align-items: center !important;
        gap: 4px !important;
    }
    .notif-unread-dot {
        width: 8px !important;
        height: 8px !important;
        background-color: #16a34a !important;
        border-radius: 50% !important;
        display: inline-block !important;
        flex-shrink: 0 !important;
        margin-top: 6px !important;
    }
    .notif-empty {
        padding: 30px 20px !important;
        text-align: center !important;
        color: #777777 !important;
    }
    .notif-empty i {
        font-size: 2rem !important;
        margin-bottom: 6px !important;
        display: block !important;
    }
    .notif-empty p {
        margin: 0 !important;
        font-weight: 700 !important;
        font-size: 0.85rem !important;
    }

    /* Responsif Mobile untuk Notifikasi */
    @media (max-width: 768px) {
        .notif-dropdown {
            position: fixed !important;
            top: 70px !important;
            left: 12px !important;
            right: 12px !important;
            width: auto !important;
            max-width: calc(100vw - 24px) !important;
            border-radius: 12px !important;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25), 4px 4px 0px #000000 !important;
            z-index: 1200 !important;
        }
        .notif-list {
            max-height: 60vh !important;
        }
        .notif-item {
            padding: 10px 12px !important;
            gap: 8px !important;
        }
        .notif-item-title {
            font-size: 0.8rem !important;
        }
        .notif-item-msg {
            font-size: 0.74rem !important;
        }
    }
    </style>
    <div class="nav-right-actions">
        <!-- BUTANG NOTIFIKASI -->
        <div class="notif-container" id="notif-container">
            <button type="button" class="notif-btn" id="notif-toggle" aria-label="Buka Notifikasi" title="Notifikasi">
                <i class="bi bi-bell-fill"></i>
                <span class="notif-badge" id="notif-badge" style="<?php echo ($unread_count > 0) ? '' : 'display:none;'; ?>">
                    <?php echo $unread_count; ?>
                </span>
            </button>

            <!-- DROPDOWN NOTIFIKASI -->
            <div class="notif-dropdown" id="notif-dropdown">
                <div class="notif-dropdown-header">
                    <div class="notif-header-title">
                        <i class="bi bi-bell-fill text-warning"></i>
                        <span>Notifikasi</span>
                        <?php if ($unread_count > 0): ?>
                            <span class="notif-count-pill" id="notif-header-count"><?php echo $unread_count; ?> baru</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($unread_count > 0): ?>
                        <button type="button" class="notif-mark-all-btn" id="notif-mark-all" title="Tanda semua sebagai dibaca">
                            <i class="bi bi-check2-all"></i> Tanda dibaca
                        </button>
                    <?php endif; ?>
                </div>

                <div class="notif-list" id="notif-list">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $n): 
                            $is_unread = ($n['is_read'] == 0);
                            $type_icon = 'bi-info-circle-fill text-primary';
                            if ($n['type'] === 'success') $type_icon = 'bi-check-circle-fill text-success';
                            else if ($n['type'] === 'warning') $type_icon = 'bi-exclamation-triangle-fill text-warning';
                            else if ($n['type'] === 'danger') $type_icon = 'bi-x-circle-fill text-danger';

                            $raw_link = trim($n['link'] ?? '');
                            if (!empty($raw_link) && $raw_link !== '#') {
                                if (preg_match('/^https?:\/\//i', $raw_link) || str_starts_with($raw_link, '/')) {
                                    $item_link = htmlspecialchars($raw_link);
                                } else {
                                    $item_link = htmlspecialchars($root_path . ltrim($raw_link, '/'));
                                }
                            } else {
                                $item_link = 'javascript:void(0);';
                            }
                        ?>
                            <a href="<?php echo $item_link; ?>" class="notif-item <?php echo $is_unread ? 'unread' : ''; ?>" data-id="<?php echo $n['id']; ?>">
                                <div class="notif-item-icon">
                                    <i class="bi <?php echo $type_icon; ?>"></i>
                                </div>
                                <div class="notif-item-content">
                                    <div class="notif-item-title"><?php echo htmlspecialchars($n['title']); ?></div>
                                    <div class="notif-item-msg"><?php echo htmlspecialchars($n['message']); ?></div>
                                    <div class="notif-item-time"><i class="bi bi-clock"></i> <?php echo format_relative_time($n['created_at']); ?></div>
                                </div>
                                <?php if ($is_unread): ?>
                                    <span class="notif-unread-dot" title="Belum dibaca"></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="notif-empty">
                            <i class="bi bi-bell-slash"></i>
                            <p>Tiada notifikasi pada masa ini</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- BUTANG PROFIL (GAMBAR AVATAR SAHAJA) -->
        <div class="profile-container" id="profile-container">
            <button type="button" class="profile-btn avatar-only" id="profile-toggle" aria-label="Menu Profil" title="Akaun @<?php echo htmlspecialchars($username); ?>">
                <?php if (!empty($avatar_src)): ?>
                    <img src="<?php echo htmlspecialchars($avatar_src); ?>" alt="<?php echo htmlspecialchars($username); ?>" class="profile-avatar-img" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=ffde59&color=000000&bold=true&size=84';">
                <?php else: ?>
                    <div class="profile-avatar-placeholder"><?php echo $first_letter; ?></div>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu" id="profile-menu">
                <li class="dropdown-header-user">
                    <small>Log Masuk Sebagai</small>
                    <strong>@<?php echo htmlspecialchars($username); ?></strong>
                </li>
                <li><hr class="dropdown-divider" style="margin: 4px 0; border-color: #eee;"></li>
                <li><a href="<?php echo $edit_profile_url; ?>" class="dropdown-item"><i class="bi bi-gear-fill me-2"></i> Edit Profil</a></li>
                <li><a href="<?php echo $logout_url; ?>" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i> Log Keluar</a></li>
            </ul>
        </div>
    </div>

    <!-- SKRIP PENGENDALI NOTIFIKASI & PROFIL NAVBAR -->
    <script>
    (function() {
        const notifToggle = document.getElementById('notif-toggle');
        const notifDropdown = document.getElementById('notif-dropdown');
        const profileToggle = document.getElementById('profile-toggle');
        const profileMenu = document.getElementById('profile-menu');
        const markAllBtn = document.getElementById('notif-mark-all');

        // Toggle Notifikasi
        if (notifToggle && notifDropdown) {
            notifToggle.addEventListener('click', function(e) {
                e.stopImmediatePropagation();
                e.stopPropagation();
                if (profileMenu) profileMenu.classList.remove('show');
                notifDropdown.classList.toggle('show');
            });
        }

        // Toggle Profil (Gunakan stopImmediatePropagation untuk elakkan duplikasi listener dari fail halaman)
        if (profileToggle && profileMenu) {
            profileToggle.addEventListener('click', function(e) {
                e.stopImmediatePropagation();
                e.stopPropagation();
                if (notifDropdown) notifDropdown.classList.remove('show');
                profileMenu.classList.toggle('show');
            });
        }

        // Tutup jika klik luar
        document.addEventListener('click', function(e) {
            if (notifDropdown && !notifDropdown.contains(e.target) && notifToggle && !notifToggle.contains(e.target)) {
                notifDropdown.classList.remove('show');
            }
            if (profileMenu && !profileMenu.contains(e.target) && profileToggle && !profileToggle.contains(e.target)) {
                profileMenu.classList.remove('show');
            }
        });

        // Tanda semua sebagai dibaca melalui AJAX
        if (markAllBtn) {
            markAllBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                fetch('<?php echo $notif_action_url; ?>?action=mark_all_read', { credentials: 'same-origin' })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const badge = document.getElementById('notif-badge');
                            if (badge) badge.style.display = 'none';
                            const headerCount = document.getElementById('notif-header-count');
                            if (headerCount) headerCount.style.display = 'none';
                            markAllBtn.style.display = 'none';

                            document.querySelectorAll('.notif-item.unread').forEach(el => {
                                el.classList.remove('unread');
                                const dot = el.querySelector('.notif-unread-dot');
                                if (dot) dot.remove();
                            });
                        }
                    })
                    .catch(err => console.error('Error marking all read:', err));
            });
        }

        // Klik satu notifikasi untuk tanda baca
        document.querySelectorAll('.notif-item.unread').forEach(item => {
            item.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                if (id) {
                    try {
                        fetch('<?php echo $notif_action_url; ?>?action=mark_read&id=' + encodeURIComponent(id), { 
                            credentials: 'same-origin',
                            keepalive: true
                        });
                    } catch(e) {}
                }
            });
        });
    })();
    </script>
    <?php
}
