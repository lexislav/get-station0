<?php

/**
 * Contact form handler (station0 >= 0.9) — the form is on /contact
 * (site/templates/contact.twig) and posts here. See /guide/forms.
 *
 * It validates the input, rate-limits by IP, logs the message to
 * writable/logs/app.log and flashes the result for action_state('contact').
 * To e-mail it instead, set MAIL_* in .env and use $ctx->mail().
 */

use Station0\Service\ActionContext;

return [
    'path'    => '/contact/send',
    'methods' => ['POST'],
    'handler' => function (ActionContext $ctx) {
        $name = (string) $ctx->input('name', '');
        $email = (string) $ctx->input('email', '');
        $message = (string) $ctx->input('message', '');

        if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $ctx->state()->flash('error', 'invalid');
            $ctx->state()->flash('old', ['name' => $name, 'email' => $email, 'message' => $message]);
            return $ctx->back();
        }
        if (!$ctx->throttle()->hit('contact:' . $ctx->ip(), 5, 3600)['allowed']) {
            $ctx->state()->flash('error', 'throttled');
            return $ctx->back();
        }

        $ctx->log()->info('Contact form: ' . $name . ' <' . $email . '>: ' . mb_substr($message, 0, 500));
        $ctx->state()->flash('sent', true);
        return $ctx->redirect('/contact');
    },
];
