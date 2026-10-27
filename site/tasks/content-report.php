<?php

/**
 * Content report — lists pages with their template and visibility state.
 * Run it from the admin (Tasks) or the console:
 *
 *   php vendor/bin/console task:run content-report
 *   php vendor/bin/console task:run content-report --section=/guide --not_live_only
 *
 * See /guide/tasks.
 */

use Station0\Service\TaskContext;

return [
    'label'       => 'Content report',
    'description' => 'Lists pages with their template and state (live, draft, scheduled…).',
    'roles'       => ['editor'],
    'params'      => [
        'section' => [
            'type'         => 'select',
            'label'        => 'Section',
            'options_from' => 'pages',
            'placeholder'  => '— whole site —',
            'help'         => 'Only this page and the pages below it.',
        ],
        'not_live_only' => ['type' => 'boolean', 'label' => 'Only pages that are not live'],
    ],
    'flush_cache' => false,   // read-only, keep the cache
    'run'         => function (TaskContext $task): int {
        $section = (string) $task->param('section', '');
        $count = 0;
        foreach ($task->pages()->all(true) as $page) {
            if ($section !== '' && $page->urlPath !== $section && !str_starts_with($page->urlPath, rtrim($section, '/') . '/')) {
                continue;
            }
            if ($task->param('not_live_only') && $page->isLive()) {
                continue;
            }
            $task->info(sprintf('%-42s %-9s %s', $page->urlPath, $page->template, $page->state()));
            $count++;
        }
        $task->success($count . ' pages.');
        return 0;
    },
];
