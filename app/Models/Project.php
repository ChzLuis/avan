<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    protected $fillable = [
        'owner_id', 'name', 'slug', 'description', 'logo_url',
        'category', 'phone', 'whatsapp', 'address', 'is_active', 'wa_phone',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'project_modules')
            ->withPivot('is_active')->withTimestamps();
    }

    public function activeModules(): BelongsToMany
    {
        return $this->modules()->wherePivot('is_active', true);
    }

    public function catalogLists(): HasMany  { return $this->hasMany(\App\Models\CatalogList::class); }
    public function categories(): HasMany   { return $this->hasMany(Category::class); }
    public function products(): HasMany     { return $this->hasMany(Product::class); }
    public function services(): HasMany     { return $this->hasMany(Service::class); }
    public function clients(): HasMany      { return $this->hasMany(Client::class); }
    public function orders(): HasMany       { return $this->hasMany(Order::class); }
    public function quotes(): HasMany       { return $this->hasMany(Quote::class); }
    public function invoices(): HasMany     { return $this->hasMany(Invoice::class); }
    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
    public function employees(): HasMany    { return $this->hasMany(Employee::class); }
    public function settings(): HasMany     { return $this->hasMany(ProjectSetting::class); }
    public function sedes(): HasMany        { return $this->hasMany(Sede::class); }
    public function userGroups(): HasMany   { return $this->hasMany(UserGroup::class); }
    public function proveedores(): HasMany  { return $this->hasMany(Proveedor::class); }
    public function coupons(): HasMany      { return $this->hasMany(Coupon::class); }
    public function reviews(): HasMany      { return $this->hasMany(\App\Models\Review::class); }
    public function proposals(): HasMany   { return $this->hasMany(Proposal::class); }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings()->where('key', $key)->value('value') ?? $default;
    }

    public function hasModule(string $key): bool
    {
        return $this->activeModules()->where('key', $key)->exists();
    }
}
