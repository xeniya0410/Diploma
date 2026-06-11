<?php
declare(strict_types=1);

function dbTableExists(PDO $pdo, string $table): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $st->execute([$table]);

    return (int)$st->fetchColumn() > 0;
}

function dbColumnExists(PDO $pdo, string $table, string $column): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $st->execute([$table, $column]);

    return (int)$st->fetchColumn() > 0;
}

/** Создаёт недостающие объекты БД для заявок преподавателей, если после развёртывания не импортировали database/schema.sql. */
function ensureTeacherApplicationSchema(PDO $pdo): void
{
    if (!dbColumnExists($pdo, 'users', 'teacher_status')) {
        $pdo->exec(
            "ALTER TABLE users ADD COLUMN teacher_status
             ENUM('none','pending','approved') NOT NULL DEFAULT 'none' AFTER role"
        );
    }

    if (!dbTableExists($pdo, 'teacher_applications')) {
        $pdo->exec(
            'CREATE TABLE teacher_applications (
              id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              user_id INT UNSIGNED NOT NULL,
              organization VARCHAR(255) DEFAULT NULL,
              experience TEXT,
              status ENUM(\'pending\',\'approved\',\'rejected\') NOT NULL DEFAULT \'pending\',
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uk_teacher_app_user (user_id),
              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        return;
    }

    if (!dbIndexExists($pdo, 'teacher_applications', 'uk_teacher_app_user')) {
        try {
            $pdo->exec('ALTER TABLE teacher_applications ADD UNIQUE KEY uk_teacher_app_user (user_id)');
        } catch (PDOException $e) {
            // дубликаты user_id — оставляем как есть
        }
    }
}

function dbIndexExists(PDO $pdo, string $table, string $index): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $st->execute([$table, $index]);

    return (int)$st->fetchColumn() > 0;
}

function setUserTeacherPending(PDO $pdo, int $userId): void
{
    ensureTeacherApplicationSchema($pdo);
    $pdo->prepare("UPDATE users SET teacher_status = 'pending' WHERE id = ? AND role = 'teacher'")
        ->execute([$userId]);
}

function saveTeacherApplication(PDO $pdo, int $userId, string $organization, string $experience): void
{
    ensureTeacherApplicationSchema($pdo);
    setUserTeacherPending($pdo, $userId);

    $st = $pdo->prepare('SELECT id FROM teacher_applications WHERE user_id = ? LIMIT 1');
    $st->execute([$userId]);
    if ($st->fetch()) {
        $pdo->prepare(
            'UPDATE teacher_applications
             SET organization = ?, experience = ?, status = \'pending\'
             WHERE user_id = ?'
        )->execute([$organization ?: null, $experience ?: null, $userId]);
        return;
    }

    $pdo->prepare(
        'INSERT INTO teacher_applications (user_id, organization, experience, status)
         VALUES (?, ?, ?, \'pending\')'
    )->execute([$userId, $organization ?: null, $experience ?: null]);

    notifyAdminNewTeacherApplication($pdo, $userId, $organization, $experience);
}

function notifyAdminNewTeacherApplication(
    PDO $pdo,
    int $userId,
    string $organization,
    string $experience
): void {
    require_once __DIR__ . '/mail.php';

    $st = $pdo->prepare('SELECT name, email FROM users WHERE id = ? AND role = \'teacher\' LIMIT 1');
    $st->execute([$userId]);
    $user = $st->fetch();
    if (!$user) {
        mailLog('teacher application notify: user ' . $userId . ' not found');
        return;
    }

    $adminEmail = defined('ADMIN_EMAIL') ? (string)ADMIN_EMAIL : 'admin@finkid.kz';
    $adminUrl   = mailAbsoluteUrl('admin.php') . '#tab-requests';

    sendNewTeacherApplicationAdminEmail(
        $adminEmail,
        (string)$user['name'],
        (string)$user['email'],
        $organization,
        $experience,
        $adminUrl
    );
}

/**
 * Одобряет заявку и отправляет письмо преподавателю.
 *
 * @return bool|null true — письмо отправлено, false — ошибка SMTP, null — письмо не требовалось
 */
function approveTeacherApplication(PDO $pdo, int $userId): ?bool
{
    $st = $pdo->prepare(
        'SELECT name, email, teacher_status FROM users WHERE id = ? AND role = \'teacher\' LIMIT 1'
    );
    $st->execute([$userId]);
    $teacher = $st->fetch();
    if (!$teacher) {
        return null;
    }

    $wasPending = ($teacher['teacher_status'] ?? 'none') === 'pending';

    $pdo->prepare('UPDATE users SET teacher_status = "approved" WHERE id = ?')->execute([$userId]);
    try {
        $pdo->prepare('UPDATE teacher_applications SET status = "approved" WHERE user_id = ?')->execute([$userId]);
    } catch (PDOException $e) {
        mailLog('approveTeacherApplication: ' . $e->getMessage());
    }

    if (!$wasPending) {
        return null;
    }

    require_once __DIR__ . '/mail.php';
    $loginUrl = mailAbsoluteUrl('index.php') . '?auth=login';

    return sendTeacherApprovedEmail((string)$teacher['email'], (string)$teacher['name'], $loginUrl);
}

/** Синхронизирует teacher_status с таблицей заявок (после старых регистраций). */
function repairTeacherApplicationState(PDO $pdo): void
{
    if (!dbTableExists($pdo, 'teacher_applications') || !dbColumnExists($pdo, 'users', 'teacher_status')) {
        return;
    }

    $pdo->exec(
        "UPDATE users u
         INNER JOIN teacher_applications ta ON ta.user_id = u.id AND ta.status = 'pending'
         SET u.teacher_status = 'pending'
         WHERE u.role = 'teacher' AND u.teacher_status <> 'pending'"
    );
}

/** @return list<array<string, mixed>> */
function fetchPendingTeacherApplications(PDO $pdo): array
{
    ensureTeacherApplicationSchema($pdo);
    repairTeacherApplicationState($pdo);

    $apps = [];

    if (dbTableExists($pdo, 'teacher_applications')) {
        $apps = $pdo->query(
            'SELECT ta.user_id, ta.organization, ta.experience, ta.status, ta.created_at,
                    u.name, u.email
             FROM teacher_applications ta
             INNER JOIN users u ON u.id = ta.user_id
             WHERE ta.status = \'pending\'
             ORDER BY ta.created_at DESC'
        )->fetchAll();
    }

    $listedIds = array_map(static fn(array $r): int => (int)$r['user_id'], $apps);

    if (!dbColumnExists($pdo, 'users', 'teacher_status')) {
        return $apps;
    }

    $pendingUsers = $pdo->query(
        "SELECT id AS user_id, name, email, created_at
         FROM users
         WHERE role = 'teacher' AND teacher_status = 'pending'
         ORDER BY created_at DESC"
    )->fetchAll();

    foreach ($pendingUsers as $pu) {
        $uid = (int)$pu['user_id'];
        if (in_array($uid, $listedIds, true)) {
            continue;
        }
        $apps[] = [
            'user_id'      => $uid,
            'name'         => $pu['name'],
            'email'        => $pu['email'],
            'organization' => '',
            'experience'   => '',
            'status'       => 'pending',
            'created_at'   => $pu['created_at'] ?? '',
        ];
        if (dbTableExists($pdo, 'teacher_applications')) {
            $pdo->prepare(
                'INSERT INTO teacher_applications (user_id, organization, experience, status)
                 VALUES (?, NULL, NULL, \'pending\')'
            )->execute([$uid]);
        }
    }

    return $apps;
}
