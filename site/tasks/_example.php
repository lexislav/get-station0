<?php

/**
 * Example site task — files starting with `_` are ignored, so this one does
 * not show up. Copy it to e.g. site/tasks/list-pages.php to try it:
 *
 *   php vendor/bin/console task:run list-pages --published_only
 *
 * or run it from the admin → Tasks.
 */

use Station0\Service\TaskContext;

return [
    'label'       => 'List pages',
    'description' => 'Prints every page with its template.',
    'roles'       => ['editor'],           // admins may always run a task
    'params'      => [
        'published_only' => ['type' => 'boolean', 'label' => 'Published pages only'],
    ],
    // 'on' => ['page.saved'],             // uncomment to also run after every page save
    'flush_cache' => false,                // read-only task, keep the cache
    'run'         => function (TaskContext $task): int {
        $pages = $task->pages()->all(!$task->param('published_only'));
        foreach ($pages as $page) {
            $task->info(sprintf('%-40s %s', $page->urlPath, $page->template));
        }
        if ($task->event()) {
            $task->info('Triggered by ' . $task->event('event') . ' on ' . $task->event('path'));
        }
        $task->success(count($pages) . ' pages.');
        return 0;
    },
];
