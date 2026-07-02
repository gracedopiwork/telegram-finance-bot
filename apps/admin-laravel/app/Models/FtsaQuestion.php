<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FtsaQuestion extends Model
{
    protected $fillable = [
        'question_num',
        'domain_key',
        'text',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'question_num' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function domainMeta(): array
    {
        $domain = (array) config('baseline_assessment.ftsa_domains.'.$this->domain_key, []);

        return [
            'code' => (string) ($domain['code'] ?? strtoupper($this->domain_key)),
            'label' => (string) ($domain['label'] ?? $this->domain_key),
            'archetype_label' => (string) ($domain['archetype_label'] ?? ''),
        ];
    }
}
