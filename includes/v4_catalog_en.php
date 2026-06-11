<?php
declare(strict_types=1);

return [
        'money' => [
            'title' => 'Money and You',
            'xp' => 30,
            'lesson_xp' => 5,
            'badge' => '🪙 First Steps',
            'has_final_test' => true,
            'lessons' => [
                [
                    'title' => 'What is money?',
                    'illustration' => 'img/courses/money/lesson-1.png',
                    'bubbles' => [
                        'Help me! 😰 The merchant wants money, but I do not know what that is...',
                        'Wow, so people used to trade fish for bread? 🤔',
                        'Let\'s count together!',
                        '✅ Great! Let\'s see what you remember?',
                    ],
                    'steps' => [
                        [
                            'type' => 'content',
                            'html' => '<p><strong>Money</strong> is a universal way to exchange. People used to trade goods directly — called <em>barter</em>.</p>'
                                . '<div class="exb">📐 Example: 1 kg bread = 200₸ = 2 bus rides</div>'
                                . '<div class="tip">💡 Three functions of money: <strong>exchange</strong>, <strong>measure of value</strong>, <strong>saving</strong>.</div>',
                            'btn' => 'Next →',
                        ],
                        [
                            'type' => 'calc',
                            'html' => '<p>In Kazakhstan people pay in <strong>tenge (₸)</strong>. Rate: 1$ = 450₸.</p>',
                            'question' => '1$ = 450₸. How many tenge for <strong>10 dollars</strong>?',
                            'answer' => 4500,
                            'xp' => 5,
                            'btn' => 'Next task →',
                        ],
                        [
                            'type' => 'quiz',
                            'question' => 'Which is NOT a function of money?',
                            'options' => [
                                ['text' => 'Measure of value', 'correct' => false, 'feedback' => '❌ That is a function of money!'],
                                ['text' => 'Home decoration 😄', 'correct' => true, 'feedback' => '✅ Right! Decoration is not a function of money.'],
                                ['text' => 'Store of value', 'correct' => false, 'feedback' => '❌ Saving is an important function of money!'],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Currencies of the world',
                    'illustration' => 'img/courses/money/lesson-2.png',
                    'bubbles' => [
                        'I am going on vacation! 🌍 Which money should I take?',
                        'Each country has its own currency!',
                        'Match country and currency 👇',
                        'Let\'s check what you remember!',
                    ],
                    'steps' => [
                        [
                            'type' => 'content',
                            'html' => '<p>Different countries have <strong>different currencies</strong>. Each has its own symbol and name.</p>'
                                . '<div class="exb">🇰🇿 Tenge (₸) · 🇺🇸 Dollar ($) · 🇪🇺 Euro (€) · 🇯🇵 Yen (¥) · 🇷🇺 Ruble (₽)</div>'
                                . '<div class="tip">💡 Before a trip, learn the local currency and exchange rate.</div>',
                            'btn' => 'Next →',
                        ],
                        [
                            'type' => 'match',
                            'title' => '🌍 Match country and currency',
                            'pairs' => [
                                ['id' => 'kz', 'left' => '🇰🇿 Kazakhstan', 'right' => 'Tenge (₸)'],
                                ['id' => 'us', 'left' => '🇺🇸 USA', 'right' => 'Dollar ($)'],
                                ['id' => 'jp', 'left' => '🇯🇵 Japan', 'right' => 'Yen (¥)'],
                                ['id' => 'eu', 'left' => '🇪🇺 Eurozone', 'right' => 'Euro (€)'],
                            ],
                            'btn' => 'To mini-quiz →',
                        ],
                        [
                            'type' => 'quiz',
                            'question' => 'Which currency is used in Japan?',
                            'options' => [
                                ['text' => 'Dollar ($)', 'correct' => false, 'feedback' => '❌ The dollar is used in the USA and some other countries.'],
                                ['text' => 'Yen (¥)', 'correct' => true, 'feedback' => '✅ Correct! Japan uses yen.'],
                                ['text' => 'Tenge (₸)', 'correct' => false, 'feedback' => '❌ Tenge is the currency of Kazakhstan.'],
                            ],
                        ],
                    ],
                ],
                [
                    'title' => 'Why do we need money?',
                    'illustration' => 'img/courses/money/lesson-3.png',
                    'bubbles' => [
                        'Can we live without money? 🤔',
                        'Money makes life easier!',
                        'True or false?',
                        'Final check!',
                    ],
                    'steps' => [
                        [
                            'type' => 'content',
                            'html' => '<p>Money helps you <strong>buy what you need</strong>, <strong>save for dreams</strong>, and <strong>trade</strong> without barter.</p>'
                                . '<div class="exb">🛒 Shopping · 🎯 Goals · 🤝 Trading services</div>'
                                . '<div class="tip">💡 Without money you would need to find someone to trade with every time.</div>',
                            'btn' => 'Next →',
                        ],
                        [
                            'type' => 'truefalse',
                            'title' => 'True or false?',
                            'statements' => [
                                ['text' => 'Money helps compare prices of different goods', 'correct' => true],
                                ['text' => 'Money is only for decorating your wallet', 'correct' => false],
                                ['text' => 'With money it is easier to save for a big goal', 'correct' => true],
                            ],
                            'btn' => 'To quiz →',
                        ],
                        [
                            'type' => 'quiz',
                            'question' => 'Why do people mainly use money?',
                            'options' => [
                                ['text' => 'To trade and buy what they need', 'correct' => true, 'feedback' => '✅ Exactly! Money is a tool for trade and purchases.'],
                                ['text' => 'Only for playing', 'correct' => false, 'feedback' => '❌ Money is not a toy — it is an important tool.'],
                                ['text' => 'To hide under a pillow forever', 'correct' => false, 'feedback' => '❌ Money is useful when used wisely.'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'savings' => [
            'title' => 'Saving is cool',
            'xp' => 50,
            'badge' => '🏦 Saver',
            'bubbles' => [
                'I have 5,000₸ and want a bike for 35,000₸. How do I save? 😕',
                'The bank pays me just for keeping money?! 🏦',
                'Ready for the quiz! 📝',
            ],
            'steps' => [
                [
                    'type' => 'content',
                    'video_label' => 'LESSON — HOW TO SAVE',
                    'html' => '<p>Saving means setting aside part of your money. Bike 35,000₸ → 3,500₸/month = <strong>10 months!</strong></p>'
                        . '<div class="tip">💡 10% rule: from every 100₸ save 10₸.</div>',
                    'btn' => 'Next →',
                ],
                [
                    'type' => 'calc',
                    'html' => '<p>Banks pay <strong>interest</strong> for keeping money. 10,000₸ at 12% = <strong>11,200₸</strong> in a year!</p>',
                    'question' => '20,000₸ at 10% per year. How much will the bank add?',
                    'answer' => 2000,
                    'xp' => 5,
                    'btn' => 'To quiz →',
                ],
                [
                    'type' => 'quiz',
                    'question' => 'You received 10,000₸. What is the smart choice?',
                    'options' => [
                        ['text' => 'Spend everything on fun 🎮', 'correct' => false, 'feedback' => '❌ Spending all — no safety net!'],
                        ['text' => 'Spend some, save some 💰', 'correct' => true, 'feedback' => '✅ Correct! Golden balance!'],
                        ['text' => 'Spend nothing at all 🏦', 'correct' => false, 'feedback' => '❌ Small spending is normal.'],
                    ],
                ],
            ],
        ],
        'budget' => [
            'title' => 'Budget and spending',
            'xp' => 40,
            'lesson_xp' => 5,
            'badge' => '📊 Budgeteer',
            'has_final_test' => true,
            'lessons' => [
                [
                    'title' => 'What is a budget?',
                    'bubbles' => [
                        'Let\'s learn about price and quality! 🛒',
                        'Expensive is not always better!',
                        'Read carefully!',
                        'Let\'s check what you learned!',
                    ],
                    'steps' => [
                        [
                            'type' => 'content',
                            'html' => '<div class="tip">💡 A budget is a plan for income and spending. Study the infographic above, then tap Next.</div>',
                            'btn' => 'Next →',
                        ],
                    ],
                ],
                [
                    'title' => 'Where family money comes from',
                    'bubbles' => [
                        'Every family has income 👨‍👩‍👧',
                        'Money does not appear by itself!',
                        'Read carefully!',
                        'Let\'s check what you learned!',
                    ],
                    'steps' => [
                        [
                            'type' => 'content',
                            'html' => '<div class="tip">💡 Families usually earn money through work, side jobs, or help from relatives. Read the material, then tap Next.</div>',
                            'btn' => 'Next →',
                        ],
                    ],
                ],
                [
                    'title' => 'Savings plan',
                    'bubbles' => [
                        'Your dream is closer! 🎯',
                        'Goal, amount, and deadline — that\'s the plan!',
                        'Read carefully!',
                        'Let\'s check what you learned!',
                    ],
                    'steps' => [
                        [
                            'type' => 'content',
                            'html' => '<div class="tip">💡 Write down your goal, amount, and deadline — saving is easier that way. Study the infographic above, then tap Next.</div>',
                            'btn' => 'Next →',
                        ],
                    ],
                ],
            ],
        ],
    ];
