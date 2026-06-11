<?php
declare(strict_types=1);

/**
 * @return array<int, array{keys: string[], answer_key: string, whatsapp: bool}>
 */
function getAiFaqEntries(): array
{
    return [
        [
            'keys'        => ['как зарегистр', 'как создать аккаунт', 'sign up', 'тіркелу', 'how to register', 'регистрац', 'тіркел'],
            'answer_key'  => 'ai.a_reg',
            'whatsapp'    => true,
        ],
        [
            'keys'        => ['не регистр', 'ошибк регистр', 'не получается', 'аккаунт заблок', 'не создаётся', 'не войти', 'не могу войти'],
            'answer_key'  => 'ai.a_reg',
            'whatsapp'    => true,
        ],
        [
            'keys'        => ['сертификат', 'аттестат', 'certificate', 'cert', 'не пришёл сертификат', 'не пришел'],
            'answer_key'  => 'ai.a_cert',
            'whatsapp'    => true,
        ],
        [
            'keys'        => ['поддержк', 'оператор', 'связаться', 'whatsapp', 'форм', 'operator', 'қолдау'],
            'answer_key'  => 'ai.a_support',
            'whatsapp'    => true,
        ],
        [
            'keys'        => ['что такое деньги', 'деньги это', 'what is money', 'ақша деген'],
            'answer_key'  => 'ai.a_money',
            'whatsapp'    => false,
        ],
        [
            'keys'        => ['бюджет', 'budget', 'расход', 'доход'],
            'answer_key'  => 'ai.a_budget',
            'whatsapp'    => false,
        ],
        [
            'keys'        => ['копить', 'копилк', 'сбереж', 'жинақ', 'save', 'saving'],
            'answer_key'  => 'ai.a_savings',
            'whatsapp'    => false,
        ],
        [
            'keys'        => ['процент', 'percent', 'пайыз'],
            'answer_key'  => 'ai.a_percent',
            'whatsapp'    => false,
        ],
        [
            'keys'        => ['курс', 'урок', 'пройти', 'обучен', 'course', 'lesson', 'сабақ'],
            'answer_key'  => 'ai.course',
            'whatsapp'    => false,
        ],
        [
            'keys'        => ['тест', 'экзамен', 'quiz', 'test'],
            'answer_key'  => 'ai.test',
            'whatsapp'    => false,
        ],
        [
            'keys'        => ['пароль', 'восстанов', 'забыл', 'password', 'reset', 'құпия'],
            'answer_key'  => 'ai.password',
            'whatsapp'    => true,
        ],
        [
            'keys'        => ['войти', 'вход', 'login', 'ошибк', 'техн', 'баг', 'кір'],
            'answer_key'  => 'ai.a_reg',
            'whatsapp'    => true,
        ],
    ];
}

function findAiAnswer(string $message): array
{
    $text = mb_strtolower(trim($message));
    if ($text === '') {
        return ['answer' => __('ai.greeting'), 'whatsapp' => false];
    }

    foreach (getAiFaqEntries() as $entry) {
        foreach ($entry['keys'] as $kw) {
            if (mb_strpos($text, mb_strtolower($kw)) !== false) {
                return [
                    'answer'   => __($entry['answer_key']),
                    'whatsapp' => $entry['whatsapp'],
                ];
            }
        }
    }

    return ['answer' => __('ai.unknown'), 'whatsapp' => false];
}
