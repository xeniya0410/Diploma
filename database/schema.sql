-- ФинКид — схема БД и безопасное обновление (MySQL 8+, MariaDB 10.5+)
--
-- Особенности:
--   • НЕ удаляет таблицы и не чистит данные — файл можно импортировать поверх существующей БД.
--   • CREATE IF NOT EXISTS, ADD COLUMN IF NOT EXISTS (совместимо с приложением-патчами).
--   • Посев курсов/уроков/вопросов только если они ещё отсутствуют по slug или по числу строк.
--
-- Новая база или обновление: импорт в phpMyAdmin в БД «finkid».
-- Администратора создаёт скрипт: php database/install_admin.php

SET NAMES utf8mb4;

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  age TINYINT UNSIGNED DEFAULT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','teacher','admin') NOT NULL DEFAULT 'student',
  teacher_status ENUM('none','pending','approved') NOT NULL DEFAULT 'none',
  xp INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS courses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  icon VARCHAR(16) NOT NULL DEFAULT '📚',
  is_free TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  slug VARCHAR(50) NULL,
  xp_reward INT UNSIGNED NOT NULL DEFAULT 30,
  badge VARCHAR(120) NULL,
  created_by INT UNSIGNED NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lessons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  content TEXT,
  content_html MEDIUMTEXT NULL,
  illustration VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lesson_id INT UNSIGNED NULL,
  course_id INT UNSIGNED NULL,
  question_text TEXT NOT NULL,
  type ENUM('single','multiple','open') NOT NULL DEFAULT 'single',
  options_json JSON NULL,
  correct_answer VARCHAR(50) NULL,
  correct_open VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lesson_completions (
  user_id INT UNSIGNED NOT NULL,
  lesson_id INT UNSIGNED NOT NULL,
  completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, lesson_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lesson_quiz_results (
  user_id INT UNSIGNED NOT NULL,
  lesson_id INT UNSIGNED NOT NULL,
  score DECIMAL(5,1) NOT NULL DEFAULT 0,
  passed TINYINT(1) NOT NULL DEFAULT 0,
  completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, lesson_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_progress (
  user_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  test_passed TINYINT(1) NOT NULL DEFAULT 0,
  test_score DECIMAL(5,1) NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, course_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS test_answers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  question_id INT UNSIGNED NOT NULL,
  answer_text TEXT,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  answered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS certificates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  code VARCHAR(32) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_course (user_id, course_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS support_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('new','read','closed') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS teacher_applications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  organization VARCHAR(255) DEFAULT NULL,
  experience TEXT,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_teacher_app_user (user_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS chat_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  question TEXT NOT NULL,
  answer TEXT,
  escalated TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE users ADD COLUMN IF NOT EXISTS teacher_status ENUM('none','pending','approved') NOT NULL DEFAULT 'none' AFTER role;
ALTER TABLE users ADD COLUMN IF NOT EXISTS xp INT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE courses ADD COLUMN IF NOT EXISTS slug VARCHAR(50) NULL;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS xp_reward INT UNSIGNED NOT NULL DEFAULT 30;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS badge VARCHAR(120) NULL;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED NULL;

ALTER TABLE lessons ADD COLUMN IF NOT EXISTS content_html MEDIUMTEXT NULL;
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS illustration VARCHAR(255) NULL;

-- Если импорт чистый повторный: FK и UNIQUE уже в CREATE выше и в ALTER добавлять нельзя.
-- Очень старые установки один раз могут понадобиться дописать вручную (перед импортом раскомментируйте строки ниже только при нужде):
-- ALTER TABLE teacher_applications ADD UNIQUE KEY uk_teacher_app_user (user_id);
-- Очень старые установки (таблица уже была без ограничения): добавьте вручную только если в SHOW CREATE TABLE courses нет внешнего ключа на created_by —
-- ALTER TABLE courses ADD CONSTRAINT fk_courses_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- ---------- Посев платформы (строго добавляет недостающее, не удаляет существующее) ----------

INSERT INTO courses (title, description, icon, is_free, sort_order, slug, xp_reward, badge)
SELECT 'Деньги и ты',
  'Три урока о деньгах, валютах и их назначении + итоговый тест и сертификат',
  '💰', 1, 1, 'money', 30, '🪙 Первые знания'
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE slug = 'money' LIMIT 1);

INSERT INTO courses (title, description, icon, is_free, sort_order, slug, xp_reward, badge)
SELECT 'Бюджет и расходы',
  'План доходов и расходов — кто планирует, тот управляет',
  '📊', 0, 2, 'budget', 40, NULL
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE slug = 'budget' LIMIT 1);

INSERT INTO courses (title, description, icon, is_free, sort_order, slug, xp_reward, badge)
SELECT 'Копить — это круто',
  'Копилка, проценты и финансово грамотные решения',
  '🏦', 0, 3, 'savings', 50, '🏦 Вкладчик'
WHERE NOT EXISTS (SELECT 1 FROM courses WHERE slug = 'savings' LIMIT 1);

-- Уроки для money — одним блоком, только если ещё 0 строк в курсе
INSERT INTO lessons (course_id, title, content, sort_order)
SELECT c.id, lt.title, lt.content, lt.sort_order
FROM courses c
CROSS JOIN (
  SELECT 'Что такое деньги?' AS title,
    'Интерактивный урок v4 — img/courses/money/lesson-1.png' AS content,
    1 AS sort_order UNION ALL SELECT 'Валюты разных стран',
      'Интерактивный урок v4 — img/courses/money/lesson-2.png', 2 UNION ALL SELECT 'Зачем вообще нужны деньги?',
      'Интерактивный урок v4 — img/courses/money/lesson-3.png', 3
) AS lt
WHERE c.slug = 'money'
  AND NOT EXISTS (SELECT 1 FROM lessons x WHERE x.course_id = c.id LIMIT 1);

INSERT INTO lessons (course_id, title, content, sort_order)
SELECT c.id, 'Бюджет и расходы', 'Интерактивный урок v4', 1
FROM courses c
WHERE c.slug = 'budget'
  AND NOT EXISTS (SELECT 1 FROM lessons x WHERE x.course_id = c.id LIMIT 1);

INSERT INTO lessons (course_id, title, content, sort_order)
SELECT c.id, 'Копить — это круто', 'Интерактивный урок v4', 1
FROM courses c
WHERE c.slug = 'savings'
  AND NOT EXISTS (SELECT 1 FROM lessons x WHERE x.course_id = c.id LIMIT 1);

-- Финальный тест курса «money» (course-level, lesson_id IS NULL) — добавляются все сразу, только если финальных ещё нет
INSERT INTO questions (course_id, lesson_id, question_text, type, options_json, correct_answer, sort_order)
SELECT c.id, qb.lesson_id, qb.question_text, qb.qtype, qb.options_json, qb.correct_answer, qb.sort_order
FROM courses c
CROSS JOIN (
  SELECT CAST(NULL AS UNSIGNED) AS lesson_id,
    'Что такое деньги в современном мире?' AS question_text,
    'single' AS qtype,
    CAST('{"a":"Универсальный способ обмена и покупок","b":"Только бумажки для коллекции","c":"Игрушки"}' AS JSON) AS options_json,
    'a' AS correct_answer,
    1 AS sort_order
  UNION ALL SELECT NULL,
    'Какая валюта используется в Японии?',
    'single',
    CAST('{"a":"Тенге (₸)","b":"Иена (¥)","c":"Евро (€)"}' AS JSON),
    'b',
    2
  UNION ALL SELECT NULL,
    'Зачем людям в основном нужны деньги?',
    'single',
    CAST('{"a":"Чтобы обменивать и покупать нужное","b":"Только для украшения","c":"Чтобы спрятать навсегда"}' AS JSON),
    'a',
    3
  UNION ALL SELECT NULL,
    'Что из перечисленного НЕ является функцией денег?',
    'single',
    CAST('{"a":"Мера стоимости","b":"Украшение интерьера","c":"Средство накопления"}' AS JSON),
    'b',
    4
  UNION ALL SELECT NULL,
    'В Казахстане основная валюта — это:',
    'single',
    CAST('{"a":"Доллар ($)","b":"Тенге (₸)","c":"Рубль (₽)"}' AS JSON),
    'b',
    5
) AS qb
WHERE c.slug = 'money'
  AND NOT EXISTS (
    SELECT 1 FROM questions q WHERE q.course_id = c.id AND q.lesson_id IS NULL LIMIT 1
);

SET FOREIGN_KEY_CHECKS = 1;
