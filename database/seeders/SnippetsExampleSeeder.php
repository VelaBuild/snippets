<?php

namespace VelaBuild\Snippets\Database\Seeders;

use Illuminate\Database\Seeder;
use VelaBuild\Snippets\Models\Snippet;

class SnippetsExampleSeeder extends Seeder
{
    public function run(): void
    {
        $examples = [
            [
                'slug' => 'callout-teal',
                'name' => 'Callout · teal',
                'category' => 'ui',
                'description' => 'A rounded callout box with a teal accent. Pair text + emoji.',
                'html' => '<div class="callout"><span class="ico">✨</span><div><strong>Pro tip.</strong> Snippets are reusable across any page — edit once, update everywhere.</div></div>',
                'css' => ".callout { display:flex; gap:14px; align-items:center; padding:16px 18px; background:#E7F7F8; border-left:4px solid #22A2AB; border-radius:10px; font-family:system-ui, sans-serif; color:#121B2E; }\n.callout .ico { font-size:22px; }",
                'js' => '',
            ],
            [
                'slug' => 'copy-button',
                'name' => 'Copy-to-clipboard button',
                'category' => 'interactive',
                'description' => "Button that copies its data-copy attribute to the clipboard. Useful for code samples, API keys, etc.",
                'html' => "<button class=\"copy-btn\" data-copy=\"vela ship it\">Copy: vela ship it</button>",
                'css' => ".copy-btn { padding:10px 16px; border:1px solid #DCE0E9; border-radius:8px; background:#fff; font-family:ui-monospace, monospace; font-size:13px; cursor:pointer; transition:background .15s; }\n.copy-btn:hover { background:#F6F7F9; }\n.copy-btn.copied { background:#E4F5EC; border-color:#16A374; color:#0E7A54; }",
                'js' => "document.querySelectorAll('.copy-btn').forEach(b=>{b.addEventListener('click',()=>{const t=b.getAttribute('data-copy')||'';navigator.clipboard.writeText(t).then(()=>{const o=b.textContent;b.classList.add('copied');b.textContent='✓ Copied';setTimeout(()=>{b.textContent=o;b.classList.remove('copied');},1400);});});});",
            ],
            [
                'slug' => 'countdown',
                'name' => 'Countdown timer',
                'category' => 'interactive',
                'description' => "Live countdown to a target date. Set data-target on the element to an ISO timestamp.",
                'html' => '<div class="countdown" data-target="2026-12-31T00:00:00Z"><span class="d">-</span><label>days</label><span class="h">-</span><label>hrs</label><span class="m">-</span><label>min</label><span class="s">-</span><label>sec</label></div>',
                'css' => ".countdown { display:inline-flex; gap:12px; font-family:system-ui, sans-serif; }\n.countdown span { min-width:48px; text-align:center; font-family:ui-monospace, monospace; font-size:28px; font-weight:600; background:#121B2E; color:#40B6BD; padding:8px 10px; border-radius:8px; }\n.countdown label { display:block; font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:#6B7388; margin-top:4px; }",
                'js' => "document.querySelectorAll('.countdown').forEach(el=>{const t=new Date(el.dataset.target).getTime();function tick(){const now=Date.now();const d=Math.max(0,t-now);const days=Math.floor(d/86400000);const hrs=Math.floor((d%86400000)/3600000);const min=Math.floor((d%3600000)/60000);const sec=Math.floor((d%60000)/1000);el.querySelector('.d').textContent=days;el.querySelector('.h').textContent=String(hrs).padStart(2,'0');el.querySelector('.m').textContent=String(min).padStart(2,'0');el.querySelector('.s').textContent=String(sec).padStart(2,'0');}tick();setInterval(tick,1000);});",
            ],
        ];

        foreach ($examples as $ex) {
            Snippet::updateOrCreate(
                ['slug' => $ex['slug']],
                array_merge($ex, ['is_active' => true, 'scope_css' => true])
            );
        }
    }
}
