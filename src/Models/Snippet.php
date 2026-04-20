<?php

namespace VelaBuild\Snippets\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Snippet extends Model
{
    use SoftDeletes, HasFactory;

    public $table = 'vela_snippets';

    protected $fillable = [
        'name', 'slug', 'category', 'description',
        'html', 'css', 'js',
        'scope_css', 'is_active', 'created_by',
    ];

    protected $casts = [
        'scope_css' => 'boolean',
        'is_active' => 'boolean',
        'uses_count' => 'integer',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Render this snippet into a self-contained HTML fragment safe to drop
     * into a page. CSS is optionally scoped to a data-attribute so styles
     * can't leak; JS is wrapped in an IIFE so vars don't leak into window.
     *
     * @return string
     */
    public function render(): string
    {
        if (!$this->is_active) {
            return '';
        }

        $attr = ' data-snippet="' . (int) $this->id . '"';
        $out = '<div class="vela-snippet"' . $attr . '>';

        if ($this->css) {
            $css = $this->scope_css
                ? app(\VelaBuild\Snippets\Services\CssScoper::class)->scope($this->css, $this->id)
                : $this->css;
            $out .= '<style>' . $css . '</style>';
        }

        $out .= $this->html ?? '';

        if ($this->js) {
            // IIFE so snippet-local vars don't leak into window.
            $out .= '<script>(function(){' . $this->js . '})();</script>';
        }

        $out .= '</div>';

        return $out;
    }

    /**
     * Lightweight summary for the picker dropdown / API.
     */
    public function toPickerArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category' => $this->category,
            'description' => $this->description,
        ];
    }
}
