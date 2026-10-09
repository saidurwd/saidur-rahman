<?php

namespace App\Support;

/**
 * The tiny inline script that sets the light/dark theme before first paint. It lives here, not in the
 * layout, so the Content-Security-Policy can allow exactly this text by its hash.
 */
final class ThemeInit
{
    public const SCRIPT = <<<'JS'
(function () {
    var d = document.documentElement, t = null;
    try { t = localStorage.getItem('theme'); } catch (e) {}
    if (t !== 'light' && t !== 'dark') { t = matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
    d.dataset.theme = t;
    d.classList.add('js');
})();
JS;

    public static function hash(): string
    {
        return "'sha256-".base64_encode(hash('sha256', self::SCRIPT, true))."'";
    }
}
