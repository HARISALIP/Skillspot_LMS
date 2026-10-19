<?php

namespace App\Support;

class RichTextSanitizer
{
    public static function sanitize(string $html): string
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<div id="rich-text">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $allowedTags = ['p', 'br', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 's', 'strike', 'font', 'span', 'mark', 'code', 'ul', 'ol', 'li', 'a', 'img', 'video', 'iframe', 'blockquote', 'pre', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'div'];
        foreach (iterator_to_array($document->getElementsByTagName('*')) as $node) {
            if ($node->getAttribute('id') === 'rich-text') continue;
            if (!in_array($node->tagName, $allowedTags, true)) {
                $node->parentNode?->removeChild($node);
                continue;
            }
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $isSafeUrl = in_array($attribute->name, ['href', 'src'], true)
                    && (str_starts_with($attribute->value, '/') || filter_var($attribute->value, FILTER_VALIDATE_URL))
                    && !preg_match('/^(?:javascript|data|vbscript):/i', $attribute->value);
                $isSafeColor = $node->tagName === 'font' && $attribute->name === 'color'
                    && preg_match('/^#[0-9a-f]{6}$/i', $attribute->value);
                $styleRules = array_values(array_filter(array_map('trim', explode(';', $attribute->value))));
                $isSafeStyle = $attribute->name === 'style' && $styleRules !== [] && collect($styleRules)->every(fn ($rule) =>
                    preg_match('/^text-align:\s*(?:left|center|right)$/i', $rule)
                    || preg_match('/^color:\s*inherit$/i', $rule)
                    || preg_match('/^(?:color|background-color):\s*(?:#[0-9a-f]{6}|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\))$/i', $rule)
                );
                if (!in_array($attribute->name, ['alt', 'rel', 'title', 'loading', 'allowfullscreen', 'controls', 'preload'], true) && !$isSafeUrl && !$isSafeColor && !$isSafeStyle) $node->removeAttribute($attribute->name);
            }
            if ($node->tagName === 'iframe') {
                $host = strtolower(parse_url($node->getAttribute('src'), PHP_URL_HOST) ?: '');
                $allowedEmbedHosts = ['youtube.com', 'youtu.be', 'youtube-nocookie.com', 'vimeo.com', 'player.vimeo.com', 'codepen.io', 'codesandbox.io', 'figma.com', 'embed.figma.com', 'docs.google.com', 'drive.google.com', 'notion.so'];
                if (!$node->hasAttribute('src') || !collect($allowedEmbedHosts)->contains(fn ($domain) => $host === $domain || str_ends_with($host, '.'.$domain))) {
                    $node->parentNode?->removeChild($node);
                    continue;
                }
                $node->setAttribute('loading', 'lazy');
                $node->setAttribute('sandbox', 'allow-scripts allow-same-origin allow-popups allow-forms allow-presentation');
            }
        }

        $root = $document->getElementById('rich-text');
        return collect(iterator_to_array($root->childNodes))->map(fn ($child) => $document->saveHTML($child))->implode('');
    }
}
