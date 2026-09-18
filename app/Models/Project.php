<?php

namespace App\Models;

use App\Modules\Ventas\Models\Order;
use App\Modules\Ventas\Models\Proposal;
use App\Modules\Ventas\Models\Quote;

use App\Modules\Catalogo\Models\CatalogIntegration;
use App\Modules\Catalogo\Models\Category;
use App\Modules\Catalogo\Models\Combo;
use App\Modules\Catalogo\Models\Product;
use App\Modules\Catalogo\Models\ProductAttribute;
use App\Modules\Catalogo\Models\ProductAttributeValue;
use App\Modules\Catalogo\Models\ProductVariant;
use App\Modules\Catalogo\Models\Service;

use App\Modules\Tienda\Models\Complaint;
use App\Modules\Tienda\Models\ContactMessage;
use App\Modules\Tienda\Models\Coupon;
use App\Modules\Tienda\Models\Promotion;
use App\Modules\Tienda\Models\StoreCatalogProfile;
use App\Modules\Tienda\Models\StoreMenu;
use App\Modules\Tienda\Models\StoreMenuItem;
use App\Modules\Tienda\Models\StorePage;
use App\Modules\Tienda\Models\StorePopup;
use App\Modules\Tienda\Models\StoreSection;

use App\Modules\Finanzas\Models\Invoice;
use App\Modules\Finanzas\Models\GuiaRemision;

use App\Modules\Inventario\Models\Proveedor;

use App\Modules\Personas\Models\UserGroup;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    protected $fillable = [
        'owner_id', 'name', 'slug', 'description', 'logo_url',
        'category', 'phone', 'whatsapp', 'address', 'is_active', 'wa_phone', 'custom_domain',
    ];

    protected $casts = ['is_active' => 'boolean'];

    // Secretos de integración: nunca se serializan hacia el cliente.
    protected $hidden = ['wa_bot_token', 'copilot_token'];

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

    public function catalogLists(): HasMany  { return $this->hasMany(\App\Modules\Catalogo\Models\CatalogList::class); }
    public function categories(): HasMany   { return $this->hasMany(Category::class); }
    public function products(): HasMany     { return $this->hasMany(Product::class); }
    public function productAttributes(): HasMany { return $this->hasMany(ProductAttribute::class); }
    public function productAttributeValues(): HasMany { return $this->hasMany(ProductAttributeValue::class); }
    public function productVariants(): HasMany { return $this->hasMany(ProductVariant::class); }
    public function catalogIntegrations(): HasMany { return $this->hasMany(CatalogIntegration::class); }
    public function services(): HasMany     { return $this->hasMany(Service::class); }
    public function clients(): HasMany      { return $this->hasMany(Client::class); }
    public function orders(): HasMany       { return $this->hasMany(Order::class); }
    public function quotes(): HasMany       { return $this->hasMany(Quote::class); }
    public function invoices(): HasMany     { return $this->hasMany(Invoice::class); }
    /** Las guías dicen cómo viajó la mercadería; las facturas, qué se vendió. */
    public function guiasRemision(): HasMany { return $this->hasMany(GuiaRemision::class); }
    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
    public function employees(): HasMany    { return $this->hasMany(Employee::class); }
    public function settings(): HasMany     { return $this->hasMany(ProjectSetting::class); }
    public function catalogProfiles(): HasMany { return $this->hasMany(StoreCatalogProfile::class); }
    public function sedes(): HasMany        { return $this->hasMany(Sede::class); }
    public function combos(): HasMany       { return $this->hasMany(Combo::class); }
    public function promotions(): HasMany   { return $this->hasMany(Promotion::class); }
    public function userGroups(): HasMany   { return $this->hasMany(UserGroup::class); }
    public function proveedores(): HasMany  { return $this->hasMany(Proveedor::class); }
    public function coupons(): HasMany      { return $this->hasMany(Coupon::class); }
    public function reviews(): HasMany      { return $this->hasMany(\App\Modules\Tienda\Models\Review::class); }
    public function proposals(): HasMany   { return $this->hasMany(Proposal::class); }
    public function operationalMaps(): HasMany    { return $this->hasMany(OperationalMap::class); }
    public function operationalObjects(): HasMany { return $this->hasMany(OperationalObject::class); }
    public function storeSections(): HasMany      { return $this->hasMany(StoreSection::class); }
    public function storePages(): HasMany         { return $this->hasMany(StorePage::class); }
    public function storePopups(): HasMany        { return $this->hasMany(StorePopup::class); }
    public function storeMenus(): HasMany         { return $this->hasMany(StoreMenu::class); }
    public function storeMenuItems(): HasMany     { return $this->hasMany(StoreMenuItem::class); }
    public function contactMessages(): HasMany    { return $this->hasMany(ContactMessage::class); }
    public function complaints(): HasMany         { return $this->hasMany(Complaint::class); }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings()->where('key', $key)->value('value') ?? $default;
    }

    public function hasModule(string $key): bool
    {
        return $this->activeModules()->where('key', $key)->exists();
    }
}
