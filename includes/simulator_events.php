<?php
declare(strict_types=1);

/** @return array<int, array{emoji:string,title_key:string,text_key:string,choices:array}> */
function getSimulatorMonths(): array
{
    return [
        [
            'emoji' => '🎒',
            'title_key' => 'sim.m1_title',
            'text_key'  => 'sim.m1_text',
            'choices'   => [
                ['label_key' => 'sim.m1_a', 'money' => -400, 'mood' => -10, 'save' => 0, 'tip_key' => 'sim.m1_tip_a'],
                ['label_key' => 'sim.m1_b', 'money' => 0, 'mood' => 5, 'save' => 200, 'tip_key' => 'sim.m1_tip_b'],
            ],
        ],
        [
            'emoji' => '🧸',
            'title_key' => 'sim.m2_title',
            'text_key'  => 'sim.m2_text',
            'choices'   => [
                ['label_key' => 'sim.m2_a', 'money' => -300, 'mood' => 15, 'save' => 0, 'tip_key' => 'sim.m2_tip_a'],
                ['label_key' => 'sim.m2_b', 'money' => 0, 'mood' => 0, 'save' => 150, 'tip_key' => 'sim.m2_tip_b'],
            ],
        ],
        [
            'emoji' => '📱',
            'title_key' => 'sim.m3_title',
            'text_key'  => 'sim.m3_text',
            'choices'   => [
                ['label_key' => 'sim.m3_a', 'money' => 500, 'mood' => 10, 'save' => 0, 'tip_key' => 'sim.m3_tip_a', 'debt' => 600],
                ['label_key' => 'sim.m3_b', 'money' => 0, 'mood' => 5, 'save' => 100, 'tip_key' => 'sim.m3_tip_b'],
            ],
        ],
    ];
}
