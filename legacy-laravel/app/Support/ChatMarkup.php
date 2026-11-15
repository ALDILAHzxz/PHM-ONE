<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Renders assistant answers with a deliberately tiny formatting vocabulary.
 *
 * The text is escaped FIRST, then only **bold**, line breaks and leading "· "
 * bullets are turned into markup. Nothing else is interpreted, so no answer —
 * including one produced by an external model — can inject HTML into the page.
 */
final class ChatMarkup
{
    public static function render(string $text): HtmlString
    {
        $safe = e($text);

        // **bold**
        $safe = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $safe);

        // _italic_ used only for the provider-unavailable note
        $safe = preg_replace('/_(.+?)_/u', '<em class="text-muted">$1</em>', $safe);

        // Indent bullet lines so multi-line items stay readable.
        $lines = array_map(function (string $line) {
            if (str_starts_with(trim($line), '·')) {
                return '<span class="block pl-1">'.$line.'</span>';
            }

            return $line;
        }, explode("\n", $safe));

        return new HtmlString(nl2br(implode("\n", $lines), false));
    }
}
