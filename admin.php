<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/admin_analytics.php';
require_once __DIR__ . '/includes/teacher_applications.php';
require_once __DIR__ . '/includes/admin_courses.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrf();
  if (isset($_POST['ticket_id'])) {
    $pdo->prepare('UPDATE support_messages SET status = "closed" WHERE id = ?')->execute([(int) $_POST['ticket_id']]);
    flash('admin_ok', __('admin.ticket_closed'));
  }
  if (isset($_POST['approve_teacher'])) {
    $uid = (int) $_POST['approve_teacher'];
    $mailSent = approveTeacherApplication($pdo, $uid);
    if ($mailSent === true) {
      flash('admin_ok', __('admin.teacher_approved_mail'));
    } elseif ($mailSent === false) {
      flash('admin_ok', __('admin.teacher_approved_mail_fail'));
    } else {
      flash('admin_ok', __('admin.teacher_approved'));
    }
  }
  if (isset($_POST['delete_course_id'])) {
    $user = currentUser();
    $cid = (int) $_POST['delete_course_id'];
    if ($user && deleteTeacherCourse($pdo, $user, $cid)) {
      flash('admin_ok', __('admin.course_deleted'));
    } else {
      flash('admin_err', __('admin.course_delete_failed'));
    }
    redirect('admin.php?tab=courses');
  }
  redirect('admin.php');
}

$analytics = getAdminAnalytics($pdo);

$tickets = $pdo->query(
  'SELECT id, name, email, message, status, created_at FROM support_messages ORDER BY created_at DESC LIMIT 100'
)->fetchAll();

$teacherApps = fetchPendingTeacherApplications($pdo);
$chatLogs = [];
try {
  $chatLogs = $pdo->query(
    'SELECT cl.*, u.name AS user_name FROM chat_logs cl LEFT JOIN users u ON u.id = cl.user_id
         ORDER BY cl.created_at DESC LIMIT 50'
  )->fetchAll();
} catch (PDOException $e) { /* */
}

$users = $pdo->query('SELECT id, name, email, role, age, created_at FROM users ORDER BY created_at DESC LIMIT 100')->fetchAll();
$adminCourses = listCoursesForAdmin($pdo);
$adminOk = flash('admin_ok');
$adminErr = flash('admin_err');

$pageTitle = __('admin.title') . ' — ' . __('site.name');
$extraCss = ['css/pages/panels.css', 'css/pages/admin-dashboard.css'];
$extraJs = ['js/admin-tabs.js', 'js/admin-dashboard.js'];
$adminTabRaw = preg_replace('/[^a-z_]/', '', (string) ($_GET['tab'] ?? 'dashboard')) ?: 'dashboard';
$adminInitialTab = in_array($adminTabRaw, ['dashboard', 'stats', 'courses', 'users', 'requests', 'tickets', 'chat'], true)
    ? $adminTabRaw
    : 'dashboard';
$chartJsCdn = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
$bodyClass = 'app-body admin-body';
$adminTopbar = true;
require __DIR__ . '/includes/header.php';
?>

<div class="container panel-page admin-page">
  <div class="app-card">
    <?php if ($adminOk): ?>
      <p class="flash flash-ok"><?= e($adminOk) ?></p><?php endif; ?>
    <?php if ($adminErr): ?>
      <p class="flash flash-err"><?= e($adminErr) ?></p><?php endif; ?>

    <div class="admin-tabs-wrap">
          <div class="admin-tabs">
            <button type="button" class="atab<?= $adminInitialTab === 'dashboard' ? ' on' : '' ?>" data-tab="dashboard" data-i18n="admin.tab_dashboard"><?= e_t('admin.tab_dashboard') ?></button>
            <button type="button" class="atab<?= $adminInitialTab === 'stats' ? ' on' : '' ?>" data-tab="stats" data-i18n="admin.tab_stats"><?= e_t('admin.tab_stats') ?></button>
            <button type="button" class="atab<?= $adminInitialTab === 'courses' ? ' on' : '' ?>" data-tab="courses" data-i18n="admin.tab_courses"><?= e_t('admin.tab_courses') ?></button>
            <button type="button" class="atab<?= $adminInitialTab === 'users' ? ' on' : '' ?>" data-tab="users" data-i18n="admin.tab_users"><?= e_t('admin.tab_users') ?></button>
            <button type="button" class="atab<?= $adminInitialTab === 'requests' ? ' on' : '' ?>" data-tab="requests">
              <span data-i18n="admin.tab_requests"><?= e_t('admin.tab_requests') ?></span><?php if (count($teacherApps) > 0): ?>
                <span class="atab-badge"><?= count($teacherApps) ?></span><?php endif; ?>
            </button>
            <button type="button" class="atab<?= $adminInitialTab === 'tickets' ? ' on' : '' ?>" data-tab="tickets" data-i18n="admin.tickets"><?= e_t('admin.tickets') ?></button>
            <button type="button" class="atab<?= $adminInitialTab === 'chat' ? ' on' : '' ?>" data-tab="chat" data-i18n="admin.tab_chat"><?= e_t('admin.tab_chat') ?></button>
          </div>

          <div class="admin-section<?= $adminInitialTab === 'dashboard' ? ' show' : '' ?>" id="tab-dashboard">
            <?php require __DIR__ . '/includes/partials/admin_dashboard.php'; ?>
          </div>

          <div class="admin-section<?= $adminInitialTab === 'stats' ? ' show' : '' ?>" id="tab-stats">
            <?php $s = $analytics['summary']; ?>
            <div class="stat-grid">
              <div class="sg">
                <div class="sg-val gr"><?= (int) $s['total_users'] ?></div>
                <div class="sg-lbl" data-i18n="admin.users"><?= e_t('admin.users') ?></div>
              </div>
              <div class="sg">
                <div class="sg-val bl"><?= (int) $s['courses_count'] ?></div>
                <div class="sg-lbl" data-i18n="admin.courses"><?= e_t('admin.courses') ?></div>
              </div>
              <div class="sg">
                <div class="sg-val yl"><?= (int) $s['certificates'] ?></div>
                <div class="sg-lbl" data-i18n="admin.certs"><?= e_t('admin.certs') ?></div>
              </div>
              <div class="sg">
                <div class="sg-val pu">
                  <?= (int) $pdo->query('SELECT COUNT(*) FROM support_messages WHERE status = "new"')->fetchColumn() ?>
                </div>
                <div class="sg-lbl" data-i18n="admin.new_tickets"><?= e_t('admin.new_tickets') ?></div>
              </div>
            </div>
          </div>

          <div class="admin-section<?= $adminInitialTab === 'courses' ? ' show' : '' ?>" id="tab-courses">
            <?php require __DIR__ . '/includes/partials/admin_courses.php'; ?>
          </div>

          <div class="admin-section<?= $adminInitialTab === 'users' ? ' show' : '' ?>" id="tab-users">
            <div class="acard">
              <h3>👥 <span data-i18n="admin.tab_users"><?= e_t('admin.tab_users') ?></span></h3>
              <div class="tickets-table-wrap">
                <table class="tickets-table">
                  <thead>
                    <tr>
                      <th data-i18n="admin.name"><?= e_t('admin.name') ?></th>
                      <th data-i18n="auth.email"><?= e_t('auth.email') ?></th>
                      <th data-i18n="admin.role"><?= e_t('admin.role') ?></th>
                      <th data-i18n="admin.date"><?= e_t('admin.date') ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($users as $u):
                      $roleKey = match ($u['role'] ?? '') {
                          'student' => 'auth.role_student',
                          'teacher' => 'auth.role_teacher',
                          'admin'   => 'auth.role_admin',
                          default   => null,
                      };
                    ?>
                      <tr>
                        <td><?= e($u['name']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?php if ($roleKey): ?><span data-i18n="<?= e($roleKey) ?>"><?= e_t($roleKey) ?></span><?php else: ?><?= e($u['role']) ?><?php endif; ?></td>
                        <td><?= e(date('d.m.Y', strtotime($u['created_at']))) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="admin-section<?= $adminInitialTab === 'requests' ? ' show' : '' ?>" id="tab-requests">
            <div class="acard">
              <h3>📨 <span data-i18n="admin.tab_requests"><?= e_t('admin.tab_requests') ?></span></h3>
              <?php if (!$teacherApps): ?>
                <p class="hint" data-i18n="admin.no_requests"><?= e_t('admin.no_requests') ?></p><?php else: ?>
                <?php foreach ($teacherApps as $ta): ?>
                  <div style="border:1px solid var(--border);border-radius:12px;padding:12px;margin-bottom:8px;">
                    <strong><?= e($ta['name']) ?></strong> — <?= e($ta['email']) ?><br>
                    <small><?= e($ta['organization'] ?? '') ?> · <?= e($ta['experience'] ?? '') ?></small>
                    <form method="post" style="margin-top:8px;">
                      <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                      <input type="hidden" name="approve_teacher" value="<?= (int) $ta['user_id'] ?>">
                      <button type="submit" class="btn-primary btn-sm" data-i18n="admin.approve"><?= e_t('admin.approve') ?></button>
                    </form>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <div class="admin-section<?= $adminInitialTab === 'tickets' ? ' show' : '' ?>" id="tab-tickets">
            <div class="acard">
              <h3>💬 <span data-i18n="admin.tickets"><?= e_t('admin.tickets') ?></span></h3>
              <?php if (!$tickets): ?>
                <p class="hint" data-i18n="admin.no_tickets"><?= e_t('admin.no_tickets') ?></p><?php else: ?>
                <div class="tickets-table-wrap">
                  <table class="tickets-table">
                    <thead>
                      <tr>
                        <th data-i18n="admin.name"><?= e_t('admin.name') ?></th>
                        <th data-i18n="auth.email"><?= e_t('auth.email') ?></th>
                        <th data-i18n="support.message"><?= e_t('support.message') ?></th>
                        <th data-i18n="admin.date"><?= e_t('admin.date') ?></th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($tickets as $t): ?>
                        <tr>
                          <td><?= e($t['name']) ?></td>
                          <td><?= e($t['email']) ?></td>
                          <td class="ticket-msg"><?= nl2br(e($t['message'])) ?></td>
                          <td><?= e(date('d.m.Y H:i', strtotime($t['created_at']))) ?></td>
                          <td><?php if ($t['status'] !== 'closed'): ?>
                              <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="ticket_id" value="<?= (int) $t['id'] ?>">
                                <button type="submit" class="btn-secondary btn-sm" data-i18n="admin.mark_done"><?= e_t('admin.mark_done') ?></button>
                              </form>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="admin-section<?= $adminInitialTab === 'chat' ? ' show' : '' ?>" id="tab-chat">
            <div class="acard">
              <h3>🤖 <span data-i18n="admin.tab_chat"><?= e_t('admin.tab_chat') ?></span></h3>
              <?php if (!$chatLogs): ?>
                <p class="hint" data-i18n="admin.no_chat"><?= e_t('admin.no_chat') ?></p><?php else: ?>
                <div class="tickets-table-wrap">
                <table class="tickets-table">
                  <thead>
                    <tr>
                      <th data-i18n="admin.name"><?= e_t('admin.name') ?></th>
                      <th data-i18n="admin.question"><?= e_t('admin.question') ?></th>
                      <th data-i18n="admin.answer"><?= e_t('admin.answer') ?></th>
                      <th data-i18n="admin.col_wa"><?= e_t('admin.col_wa') ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($chatLogs as $cl): ?>
                      <tr>
                        <td><?= e($cl['user_name'] ?? '—') ?></td>
                        <td><?= e(mb_substr($cl['question'], 0, 80)) ?></td>
                        <td><?= e(mb_substr($cl['answer'] ?? '', 0, 80)) ?></td>
                        <td><?= (int) $cl['escalated'] ? '✅' : '—' ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
    </div>
  </div>
</div>

<script src="<?= e($chartJsCdn) ?>" crossorigin="anonymous"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>