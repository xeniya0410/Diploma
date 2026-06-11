<?php
declare(strict_types=1);
/** @var array $questions */
/** @var string $formAction */
/** @var string $submitLabel */
/** @var string|null $submitLabelKey */
/** @var string $quizType lesson|final */
$formAction  = $formAction ?? '';
$submitLabel = $submitLabel ?? __('course.check_answers');
$submitLabelKey = $submitLabelKey ?? 'course.check_answers';
$quizType    = $quizType ?? 'lesson';
?>
<form class="quiz-form test-guard-zone" method="post" action="<?= e($formAction) ?>">
  <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
  <input type="hidden" name="quiz_type" value="<?= e($quizType) ?>">
  <?php foreach ($questions as $i => $q):
    $qid = (int)$q['id'];
    $opts = [];
    if (!empty($q['options_json'])) {
      $decoded = json_decode((string)$q['options_json'], true);
      if (is_array($decoded)) {
        $opts = $decoded;
      }
    }
  ?>
  <div class="quiz-question app-card">
    <p class="quiz-q-num"<?= i18n_attrs('quiz.q_num', [(int)$i + 1]) ?>><?= e(__f('quiz.q_num', (int)$i + 1)) ?></p>
    <p class="quiz-q-text"><?= e($q['question_text']) ?></p>
    <?php if ($q['type'] === 'open'): ?>
      <input type="text" name="answers[<?= $qid ?>]" class="auth-input" required data-i18n-placeholder="quiz.your_answer" placeholder="<?= e_t('quiz.your_answer') ?>">
    <?php elseif ($q['type'] === 'multiple'): ?>
      <div class="quiz-options">
        <?php foreach ($opts as $key => $label): ?>
        <label class="quiz-option">
          <input type="checkbox" name="answers[<?= $qid ?>][]" value="<?= e((string)$key) ?>">
          <span><?= e((string)$label) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="quiz-options">
        <?php foreach ($opts as $key => $label): ?>
        <label class="quiz-option">
          <input type="radio" name="answers[<?= $qid ?>]" value="<?= e((string)$key) ?>" required>
          <span><?= e((string)$label) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php if (count($questions) > 0): ?>
  <button type="submit" class="btn-primary" data-i18n="<?= e($submitLabelKey) ?>"><?= e($submitLabel) ?></button>
  <?php endif; ?>
</form>
