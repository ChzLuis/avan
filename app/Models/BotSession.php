<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotSession extends Model
{
    protected $fillable = ['flow_id', 'wa_number', 'current_state', 'data', 'last_activity_at'];
    protected $casts    = ['data' => 'array', 'last_activity_at' => 'datetime'];

    public function flow()
    {
        return $this->belongsTo(BotFlow::class);
    }

    // Obtener o crear sesión para un número
    public static function forNumber(int $flowId, string $waNumber): static
    {
        return static::firstOrCreate(
            ['flow_id' => $flowId, 'wa_number' => $waNumber],
            ['current_state' => 'inicio', 'data' => [], 'last_activity_at' => now()]
        );
    }

    public function setData(string $key, mixed $value): void
    {
        $data = $this->data ?? [];
        $data[$key] = $value;
        $this->update(['data' => $data, 'last_activity_at' => now()]);
    }

    public function getData(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function moveToState(string $stateKey): void
    {
        $this->update(['current_state' => $stateKey, 'last_activity_at' => now()]);
    }
}
