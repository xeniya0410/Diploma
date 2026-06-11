<?php
declare(strict_types=1);
/** @var array<string, mixed> $lesson */
/** @var int $idx */
/** @var bool $hideRemove */
$quiz = $lesson['quiz'] ?? [
    'enabled' => false,
    'question' => '',
    'options' => ['a' => '', 'b' => '', 'c' => ''],
    'correct' => 'a',
];
$illus = (string) ($lesson['illustration'] ?? '');
$illusUrl = $illus !== '' && function_exists('asset') ? asset($illus) : '';
?>
<div class="lesson-editor-row" data-lesson-row>
  <input type="hidden" name="lesson_id[]" value="<?= (int) ($lesson['id'] ?? 0) ?>">
  <input type="hidden" name="lesson_illustration_keep[]" value="<?= e($illus) ?>">

  <div class="fg">
    <label class="fl"><?= e_t('tch.lesson_title') ?> <?= (int) $idx + 1 ?></label>
    <input class="fi" type="text" name="lesson_title[]" required maxlength="200"
      value="<?= e((string) ($lesson['title'] ?? '')) ?>">
  </div>

  <div class="fg">
    <label class="fl"><?= e_t('tch.lesson_content') ?></label>
    <textarea class="fi" name="lesson_content[]" rows="3"><?= e((string) ($lesson['content'] ?? '')) ?></textarea>
  </div>

  <div class="fg lesson-image-field">
    <label class="fl"><?= e_t('tch.lesson_image') ?></label>
    <?php if ($illusUrl !== ''): ?>
      <div class="lesson-image-preview">
        <img src="<?= e($illusUrl) ?>" alt="" width="160" height="auto">
      </div>
    <?php endif; ?>
    <input class="fi" type="file" name="lesson_image[]" accept="image/png,image/jpeg,image/webp">
    <p class="hint lesson-field-hint"><?= e_t('tch.lesson_image_hint') ?></p>
  </div>

  <fieldset class="lesson-quiz-field">
    <legend class="lesson-quiz-legend"><?= e_t('tch.lesson_quiz') ?></legend>
    <label class="teacher-check">
      <input type="hidden" name="lesson_quiz_enabled[]" value="<?= !empty($quiz['enabled']) ? '1' : '0' ?>" class="lesson-quiz-flag">
      <input type="checkbox" class="lesson-quiz-enable" <?= !empty($quiz['enabled']) ? 'checked' : '' ?>>
      <?= e_t('tch.lesson_quiz_enable') ?>
    </label>
    <div class="lesson-quiz-body" <?= empty($quiz['enabled']) ? 'hidden' : '' ?>>
      <div class="fg">
        <label class="fl"><?= e_t('tch.lesson_quiz_question') ?></label>
        <input class="fi" type="text" name="lesson_quiz_question[]" maxlength="500"
          value="<?= e((string) ($quiz['question'] ?? '')) ?>">
      </div>
      <div class="form-row-2">
        <div class="fg">
          <label class="fl"><?= e_t('tch.lesson_quiz_opt_a') ?></label>
          <input class="fi" type="text" name="lesson_quiz_a[]" maxlength="255"
            value="<?= e((string) ($quiz['options']['a'] ?? '')) ?>">
        </div>
        <div class="fg">
          <label class="fl"><?= e_t('tch.lesson_quiz_opt_b') ?></label>
          <input class="fi" type="text" name="lesson_quiz_b[]" maxlength="255"
            value="<?= e((string) ($quiz['options']['b'] ?? '')) ?>">
        </div>
      </div>
      <div class="fg">
        <label class="fl"><?= e_t('tch.lesson_quiz_opt_c') ?></label>
        <input class="fi" type="text" name="lesson_quiz_c[]" maxlength="255"
          value="<?= e((string) ($quiz['options']['c'] ?? '')) ?>">
      </div>
      <div class="fg">
        <label class="fl"><?= e_t('tch.lesson_quiz_correct') ?></label>
        <select class="fi" name="lesson_quiz_correct[]">
          <?php foreach (['a', 'b', 'c'] as $key): ?>
            <option value="<?= e($key) ?>" <?= ($quiz['correct'] ?? 'a') === $key ? 'selected' : '' ?>>
              <?= e(strtoupper($key)) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </fieldset>

  <button type="button" class="btn-secondary btn-sm lesson-remove" data-remove-lesson<?= $hideRemove ? ' hidden' : '' ?>>
    <?= e_t('tch.lesson_remove') ?>
  </button>
</div>
