<?php

/**
 * A hook: runs by itself whenever a page under /blog is saved in the admin.
 * It only writes a line to the run log — open admin → Tasks → "Log blog saves"
 * after saving an article to see it. See /guide/tasks.
 */

use Station0\Service\TaskContext;

return [
    'label'       => 'Log blog saves',
    'description' => 'Hook demo: runs after every save of a page under /blog.',
    'roles'       => ['editor'],
    'on'          => ['page.saved:/blog', 'page.deleted:/blog'],
    'flush_cache' => false,
    'run'         => function (TaskContext $task): int {
        if (!$task->event()) {
            $task->warn('Nothing to do — this task runs as a hook. Save an article under /blog in the admin.');
            return 0;
        }
        $task->info(sprintf('%s: %s (%s)', $task->event('event'), $task->event('path'), $task->event('title')));
        if ($task->event('previous_path')) {
            $task->info('Renamed from ' . $task->event('previous_path'));
        }
        $task->success('Logged.');
        return 0;
    },
];
