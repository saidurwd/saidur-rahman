<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

/** Purifies rich-text HTML from the editor before it is saved. Output is still safe to print with {!! !!}. */
class HtmlSanitizer
{
    private ?HTMLPurifier $purifier = null;

    public function clean(?string $html): ?string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return null;
        }

        return trim($this->purifier()->purify($html)) ?: null;
    }

    /** Plain text only: tags stripped, whitespace tidied. */
    public function plain(?string $text): ?string
    {
        $text = trim(strip_tags((string) $text));

        return $text === '' ? null : $text;
    }

    private function purifier(): HTMLPurifier
    {
        if ($this->purifier) {
            return $this->purifier;
        }

        $cache = storage_path('app/purifier');
        if (! is_dir($cache)) {
            mkdir($cache, 0755, true);
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', $cache);
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', implode(',', [
            'p', 'br', 'hr', 'strong', 'b', 'em', 'i', 'u', 's', 'sup', 'sub',
            'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'pre', 'code',
            'a[href|title|target|rel]', 'img[src|alt|title|width|height]',
            'figure', 'figcaption',
            'table', 'thead', 'tbody', 'tfoot', 'tr', 'th[colspan|rowspan|scope]', 'td[colspan|rowspan]',
        ]));
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.Nofollow', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
        $config->set('HTML.DefinitionID', 'site-rich-text');
        $config->set('HTML.DefinitionRev', 1);

        // HTMLPurifier predates HTML5, so teach it the figure elements the editor can produce.
        if ($definition = $config->maybeGetRawHTMLDefinition()) {
            $definition->addElement('figure', 'Block', 'Flow', 'Common');
            $definition->addElement('figcaption', 'Inline', 'Flow', 'Common');
        }

        return $this->purifier = new HTMLPurifier($config);
    }
}
