<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotInstance extends Model
{
    protected $fillable = ['project_id', 'name', 'bot_type', 'description', 'icon_color', 'port', 'is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    public function project() { return $this->belongsTo(Project::class); }
    public function flow()    { return $this->hasOne(BotFlow::class, 'bot_type', 'bot_type'); }

    public function statusFile(): string
    {
        return base_path("whatsbot/{$this->bot_type}-status.json");
    }

    public function readStatus(): array
    {
        $path = $this->statusFile();
        if (!file_exists($path)) return ['status' => 'offline', 'qr' => null];
        return json_decode(file_get_contents($path), true)
            ?: ['status' => 'offline', 'qr' => null];
    }

    public function pm2Name(): string
    {
        return "bot-{$this->bot_type}";
    }

    public function iconColors(): array
    {
        return match($this->icon_color) {
            'purple' => ['bg-purple-100', 'text-purple-600'],
            'blue'   => ['bg-blue-100',   'text-blue-600'],
            'orange' => ['bg-orange-100', 'text-orange-600'],
            'red'    => ['bg-red-100',    'text-red-600'],
            default  => ['bg-green-100',  'text-green-600'],
        };
    }
}
