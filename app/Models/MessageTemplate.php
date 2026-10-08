<?php

namespace App\Models;

use App\Enums\MessageChannel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageTemplate extends Model
{
    protected $fillable = ['name', 'channel', 'content', 'is_active'];

    protected function casts(): array
    {
        return ['channel' => MessageChannel::class, 'is_active' => 'boolean'];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MessageLog::class, 'template_id');
    }

    public function careRules(): HasMany
    {
        return $this->hasMany(CareRule::class, 'message_template_id');
    }

    /** Replace {patient_name}, {clinic_name}, {date} placeholders */
    public function render(array $vars): string
    {
        $content = $this->content;
        foreach ($vars as $key => $value) {
            $content = str_replace('{'.$key.'}', $value, $content);
        }

        return $content;
    }
}
