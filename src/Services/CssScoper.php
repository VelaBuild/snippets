<?php

namespace VelaBuild\Snippets\Services;

/**
 * Prefix every selector in a block of CSS with [data-snippet="{id}"] so
 * snippet styles can't leak into the surrounding page. Leaves @rules
 * (media, keyframes, supports, import, font-face, charset) alone.
 *
 * Not a full CSS parser — uses a tokenising walk that's robust enough for
 * snippet-sized inputs. Nested blocks inside @media / @supports are
 * scoped; @keyframes selectors (0%, 100%, from, to) are passed through.
 */
class CssScoper
{
    public function scope(string $css, int $id): string
    {
        $prefix = '[data-snippet="' . (int) $id . '"]';

        // Strip comments — they can contain `{}` which would break the walk.
        $css = preg_replace('#/\*.*?\*/#s', '', $css) ?? $css;

        return $this->rewriteBlock($css, $prefix);
    }

    /**
     * Walk a block of CSS, rewriting selector lists. When we hit an
     * @media / @supports / @container, recurse into its body.
     */
    protected function rewriteBlock(string $css, string $prefix): string
    {
        $out = '';
        $i = 0;
        $len = strlen($css);

        while ($i < $len) {
            // Skip leading whitespace but keep it in output.
            $wsStart = $i;
            while ($i < $len && ctype_space($css[$i])) { $i++; }
            $out .= substr($css, $wsStart, $i - $wsStart);
            if ($i >= $len) break;

            // At-rule
            if ($css[$i] === '@') {
                $semi = strpos($css, ';', $i);
                $brace = strpos($css, '{', $i);

                if ($brace === false || ($semi !== false && $semi < $brace)) {
                    // Single-line at-rule: @import, @charset …
                    $end = $semi === false ? $len - 1 : $semi;
                    $out .= substr($css, $i, $end - $i + 1);
                    $i = $end + 1;
                    continue;
                }

                $head = substr($css, $i, $brace - $i);
                $headTrim = trim($head);
                $body = $this->extractBraceContents($css, $brace, $end);
                $i = $end + 1;

                // @keyframes selectors are percentages / from/to — don't scope.
                if (stripos($headTrim, '@keyframes') === 0 || stripos($headTrim, '@-webkit-keyframes') === 0 || stripos($headTrim, '@font-face') === 0) {
                    $out .= $head . '{' . $body . '}';
                    continue;
                }

                // Nested at-rules (@media, @supports, @container) — recurse.
                $out .= $head . '{' . $this->rewriteBlock($body, $prefix) . '}';
                continue;
            }

            // Regular rule: selector list up to `{`
            $brace = strpos($css, '{', $i);
            if ($brace === false) {
                $out .= substr($css, $i);
                break;
            }

            $selectors = substr($css, $i, $brace - $i);
            $body = $this->extractBraceContents($css, $brace, $end);
            $i = $end + 1;

            $out .= $this->prefixSelectors($selectors, $prefix) . '{' . $body . '}';
        }

        return $out;
    }

    /**
     * Return the contents between a `{` at $open and its matching `}`,
     * setting $closeOut to the index of that closing brace.
     */
    protected function extractBraceContents(string $css, int $open, ?int &$closeOut): string
    {
        $depth = 0;
        $len = strlen($css);
        for ($k = $open; $k < $len; $k++) {
            $ch = $css[$k];
            if ($ch === '{') $depth++;
            elseif ($ch === '}') {
                $depth--;
                if ($depth === 0) {
                    $closeOut = $k;
                    return substr($css, $open + 1, $k - $open - 1);
                }
            }
        }
        $closeOut = $len - 1;
        return substr($css, $open + 1);
    }

    /**
     * Split a comma-separated selector list and prefix each with the
     * snippet scope. Leaves selectors starting with `:root`, `html`, `body`
     * alone (no sensible scoping for those — just prefix directly).
     */
    protected function prefixSelectors(string $selectors, string $prefix): string
    {
        $parts = [];
        foreach (preg_split('/\s*,\s*/', trim($selectors)) as $sel) {
            $sel = trim($sel);
            if ($sel === '') continue;

            // `&` at start = refer-to-root (Sass-style) — not standard CSS but some users write it.
            if (str_starts_with($sel, '&')) {
                $parts[] = $prefix . substr($sel, 1);
                continue;
            }

            $parts[] = $prefix . ' ' . $sel;
        }
        return implode(', ', $parts) . ' ';
    }
}
