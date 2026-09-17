<?php

namespace Tests\Feature;

use App\Modules\Inventario\Models\InventoryMovement;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Project;
use App\Modules\Inventario\Models\Proveedor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOwnedModelScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_inventory_and_purchasing_models_follow_the_active_project(): void
    {
        $mine = $this->project('mine');
        $other = $this->project('other');

        $mineProduct = Product::create(['project_id' => $mine->id, 'name' => 'Mine', 'price' => 10, 'stock' => 1]);
        $otherProduct = Product::create(['project_id' => $other->id, 'name' => 'Other', 'price' => 10, 'stock' => 1]);

        Payment::create(['project_id' => $mine->id, 'payable_type' => 'order', 'payable_id' => 1, 'amount_cents' => 100, 'received_at' => now()]);
        Payment::create(['project_id' => $other->id, 'payable_type' => 'order', 'payable_id' => 2, 'amount_cents' => 200, 'received_at' => now()]);
        InventoryMovement::create(['project_id' => $mine->id, 'product_id' => $mineProduct->id, 'type' => 'in', 'reason' => 'test', 'quantity' => 1]);
        InventoryMovement::create(['project_id' => $other->id, 'product_id' => $otherProduct->id, 'type' => 'in', 'reason' => 'test', 'quantity' => 1]);
        Proveedor::create(['project_id' => $mine->id, 'name' => 'Mine supplier']);
        Proveedor::create(['project_id' => $other->id, 'name' => 'Other supplier']);

        session(['active_project_id' => $mine->id]);

        $this->assertSame([$mine->id], Payment::query()->pluck('project_id')->all());
        $this->assertSame([$mine->id], InventoryMovement::query()->pluck('project_id')->all());
        $this->assertSame([$mine->id], Proveedor::query()->pluck('project_id')->all());

        $this->assertCount(2, Payment::allProjects()->get());
        $this->assertCount(2, InventoryMovement::allProjects()->get());
        $this->assertCount(2, Proveedor::allProjects()->get());
    }

    private function project(string $slug): Project
    {
        return Project::create([
            'owner_id' => User::factory()->create()->id,
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
        ]);
    }
}
