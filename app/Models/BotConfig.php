<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class BotConfig extends Model
{
    protected $fillable = ['project_id', 'bot_type', 'key', 'value'];

    public static function get(int $projectId, string $botType, string $key, string $default = ''): string
    {
        $config = static::where('project_id', $projectId)
            ->where('bot_type', $botType)
            ->where('key', $key)
            ->first();
        return $config?->value ?? $default;
    }

    public static function set(int $projectId, string $botType, string $key, string $value): void
    {
        static::updateOrCreate(
            ['project_id' => $projectId, 'bot_type' => $botType, 'key' => $key],
            ['value' => $value]
        );
    }
}
