<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/v4_courses.php';
requireAuth();

$userId   = (int)$_SESSION['user_id'];
$courseId = (int)($_GET['course_id'] ?? 0);

$courseSt = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
$courseSt->execute([$courseId]);
$course = $courseSt->fetch();
if (!$course) {
    redirect('courses.php');
}

$slug = courseSlug($course);
$v4   = getV4CourseBySlug($slug);
$info = $_SESSION['v4_complete'] ?? [];
if ((int)($info['course_id'] ?? 0) !== $courseId && $v4) {
    $info = [
        'course_id' => $courseId,
        'title'     => $v4['title'] ?? $course['title'],
        'xp_gained' => (int)($v4['xp'] ?? $course['xp_reward'] ?? 0),
        'badge'     => $v4['badge'] ?? $course['badge'] ?? null,
    ];
}

$title   = courseLocalizedTitle($course);
$xpGain  = (int)($info['xp_gained'] ?? (int)($_GET['xp'] ?? 0));
$badge   = $info['badge'] ?? $v4['badge'] ?? $course['badge'] ?? null;
$userXp  = getUserXp($pdo, $userId);
syncSessionUserXp($pdo, $userId);

$pageTitle = __('course.complete_title') . ' — ' . __('site.name');
$extraCss  = ['css/pages/course-v4.css'];
$bodyClass = 'app-body page-complete';
require __DIR__ . '/includes/header.php';
?>

<div class="complete-screen">
  <div class="coi">
    <span class="coe">🎉</span>
    <h2 class="cot" data-i18n="course.complete_title"><?= e_t('course.complete_title') ?></h2>
    <p class="cos"<?= i18n_attrs('course.complete_sub', [$title]) ?>><?= e(__f('course.complete_sub', $title)) ?></p>
    <?php if ($xpGain > 0): ?>
    <div class="cox"<?= i18n_attrs('course.complete_xp', [$xpGain]) ?>><?= e(__f('course.complete_xp', $xpGain)) ?></div>
    <?php endif; ?>
    <?php if ($badge): ?>
    <div class="cobr"><span class="cobg"><?= e($badge) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($info['certificate'])): ?>
    <p class="complete-cert-msg"><span data-i18n-prefix="🎓 " data-i18n="course.complete_cert"><?= e_t('course.complete_cert') ?></span></p>
    <a href="<?= e(asset('profile.php')) ?>" class="btn btn-secondary" style="width:100%;justify-content:center;max-width:300px;display:inline-flex;margin-bottom:1rem;" data-i18n="profile.certificates">
      <?= e_t('profile.certificates') ?>
    </a>
    <?php endif; ?>
    <p style="margin-bottom:1.2rem;font-size:.86rem;color:var(--muted);font-weight:700;">
      <span data-i18n-prefix="🦊 " data-i18n="course.complete_finya"><?= e_t('course.complete_finya') ?></span>
    </p>
    <p style="margin-bottom:1rem;font-size:.8rem;color:var(--muted);">
      ⭐ <?= (int)$userXp ?> XP · <?= e_t('xp.level') ?> <?= getUserLevel($userXp) ?>
    </p>
    <a href="<?= e(asset('courses.php')) ?>" class="btn btn-primary" style="width:100%;justify-content:center;max-width:300px;display:inline-flex;" data-i18n="course.complete_home">
      <?= e_t('course.complete_home') ?>
    </a>
  </div>
</div>

<?php
unset($_SESSION['v4_complete']);
require __DIR__ . '/includes/footer.php';
