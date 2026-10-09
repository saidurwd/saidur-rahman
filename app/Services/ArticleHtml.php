<?php

namespace App\Services;

use DOMDocument;
use Illuminate\Support\Str;

/** Adds stable ids to the h2/h3 headings of an article and returns them as a table of contents. */
class ArticleHtml
{
    /**
     * @return array{html: string, toc: list<array{id: string, text: string, level: int}>}
     */
    public function process(?string $html): array
    {
        $html = trim((string) $html);

        if ($html === '') {
            return ['html' => '', 'toc' => []];
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $toc = [];
        $used = [];

        foreach ($document->getElementsByTagName('*') as $node) {
            if (! in_array($node->tagName, ['h2', 'h3'], true)) {
                continue;
            }

            $text = trim(preg_replace('/\s+/', ' ', $node->textContent) ?? '');
            if ($text === '') {
                continue;
            }

            $base = Str::slug($text) ?: 'section';
            $id = $base;
            for ($i = 2; in_array($id, $used, true); $i++) {
                $id = "{$base}-{$i}";
            }
            $used[] = $id;

            $node->setAttribute('id', $id);
            $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) substr($node->tagName, 1)];
        }

        $output = '';
        foreach ($document->getElementsByTagName('body')->item(0)->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return ['html' => $output, 'toc' => $toc];
    }
}
