<?php

/**
 * Example site action (station0 >= 0.9) — files starting with `_` are
 * ignored, so this one is not active. Copy it to e.g. site/actions/newsletter.php
 * and post a form to /newsletter:
 *
 *   <form method="post" action="/newsletter">
 *       <input type="hidden" name="{{ csrf.nameKey }}" value="{{ csrf.name }}">
 *       <input type="hidden" name="{{ csrf.valueKey }}" value="{{ csrf.value }}">
 *       <input type="email" name="email" required>
 *       <button>Subscribe</button>
 *   </form>
 *   {% set nl = action_state('newsletter') %}
 *   {% if nl.thanks ?? false %}<p>Thanks!</p>{% endif %}
 */

use Station0\Service\ActionContext;

return [
    'path'    => '/newsletter',
    'methods' => ['POST'],
    'handler' => function (ActionContext $ctx) {
        if (!$ctx->throttle()->hit('newsletter:' . $ctx->ip(), 5, 3600)['allowed']) {
            $ctx->state()->flash('error', 'throttled');
            return $ctx->back();
        }
        $email = (string) $ctx->input('email', '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $ctx->log()->info('Newsletter sign-up: ' . $email);
            $ctx->state()->flash('thanks', true);
        }
        return $ctx->back();
    },
];
