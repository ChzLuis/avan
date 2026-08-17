<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Support\StorefrontSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGlobalConfirmModalTest extends TestCase
{
    use RefreshDatabase;

    private function modalSource(): string
    {
        $source = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $marker = strpos($source, 'data-global-confirm-modal');
        $start = strrpos(substr($source, 0, $marker), '<div x-data="{');
        $end = strpos($source, '{{-- Flash messages', $marker);

        $this->assertNotFalse($marker);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($source, $start, $end - $start);
    }

    private function designer(string $section = 'plantilla')
    {
        // La pantalla clasica solo es alcanzable por superadmin con ?classic=1.
        $owner = User::factory()->create(['is_superadmin' => 1]);
        $project = Project::create([
            'owner_id' => $owner->id,
            'name' => 'Confirm modal contract',
            'slug' => 'confirm-modal-contract',
            'is_active' => true,
        ]);

        $project->settings()->createMany([
            ['key' => 'catalog_template', 'value' => 'computienda'],
            ['key' => 'primary_color', 'value' => '#2563eb'],
        ]);
        StorefrontSections::ensure($project);

        return $this->actingAs($owner)
            ->withSession(['active_project_id' => $project->id])
            ->get(route('settings.design', ['s' => $section, 'classic' => 1]));
    }

    public function test_shell_registers_one_global_confirm_without_an_executable_x_init_return(): void
    {
        $response = $this->designer()->assertOk();
        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, 'data-global-confirm-modal'));
        $response
            ->assertSee('init() {', false)
            ->assertSee('this._confirmHandler = (opts) => this.open(opts);', false)
            ->assertSee('window.__confirm = this._confirmHandler;', false)
            ->assertSee('destroy() {', false);
        $this->assertStringNotContainsString('x-init="window.__confirm', $html);
        $this->assertStringNotContainsString('window.confirm(', $html);
    }

    public function test_open_normalizes_missing_and_optional_options_once(): void
    {
        $response = $this->designer()->assertOk();
        $html = $response->getContent();

        $response
            ->assertSee("const options = opts !== null && typeof opts === 'object' && !Array.isArray(opts)", false)
            ->assertSee('if (!options) return Promise.resolve(false);', false)
            ->assertSee("typeof options.title === 'string'", false)
            ->assertSee("typeof options.msg === 'string'", false)
            ->assertSee("typeof options.message === 'string'", false)
            ->assertSee("typeof options.confirmText === 'string'", false)
            ->assertSee("typeof options.cancelText === 'string'", false);
        $this->assertStringNotContainsString('opts.title', $html);
        $this->assertStringNotContainsString('opts.msg', $html);
    }

    public function test_confirm_and_cancel_keep_the_boolean_promise_contract(): void
    {
        $response = $this->designer()->assertOk();

        $response
            ->assertSee('return new Promise(r => this._resolve = r);', false)
            ->assertSee('confirm() { this._settle(true); }', false)
            ->assertSee('cancel() { this._settle(false); }', false)
            ->assertSee('if (resolve) resolve(value);', false)
            ->assertSee('if (this._resolve) this._settle(false, false);', false)
            ->assertSee("document.body.style.overflow = 'hidden';", false)
            ->assertSee('document.body.style.overflow = this._previousOverflow;', false);
    }

    public function test_initial_focus_is_synchronized_bounded_and_cancelable(): void
    {
        $response = $this->designer()->assertOk();
        $html = $response->getContent();
        $modal = $this->modalSource();

        $this->assertSame(1, substr_count($html, 'x-ref="cancelButton"'));
        $this->assertSame(1, substr_count($modal, 'focusInitial() {'));
        $this->assertStringContainsString('this.$nextTick(() => this.focusInitial());', $modal);
        $this->assertLessThan(
            strpos($modal, 'this.$nextTick(() => this.focusInitial());'),
            strpos($modal, 'this.show = true;')
        );
        $this->assertStringContainsString('_maxFocusAttempts: 4', $modal);
        $this->assertStringContainsString('this._focusAttempts < this._maxFocusAttempts', $modal);
        $this->assertStringContainsString('requestAnimationFrame(() => {', $modal);
        $this->assertStringContainsString('cancelAnimationFrame(this._focusFrame);', $modal);
        $this->assertStringContainsString('this.cancelPendingFocus();', $modal);
        $this->assertStringContainsString("!cancelButton.closest('[inert]')", $modal);
        $this->assertStringContainsString('getComputedStyle(modal).display', $modal);
        $this->assertStringContainsString('dialog.contains(document.activeElement)', $modal);
        $this->assertStringContainsString('cancelButton.focus({ preventScroll: true });', $modal);
        $this->assertStringNotContainsString('setTimeout(', $modal);
        $this->assertStringNotContainsString('setInterval(', $modal);
    }

    public function test_modal_accessibility_and_designer_shell_contract_remain_present(): void
    {
        $response = $this->designer()->assertOk();
        $html = $response->getContent();

        $response
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('aria-labelledby="global-confirm-title"', false)
            ->assertSee('aria-describedby="global-confirm-message"', false)
            ->assertSee('@keydown.escape.window="show && cancel()"', false)
            ->assertSee('@keydown.tab.prevent="show && trapFocus($event)"', false)
            ->assertSee('type="button" x-ref="cancelButton"', false)
            ->assertSee('type="button" x-ref="confirmButton"', false)
            ->assertSee('this.$nextTick(() => this.focusInitial());', false)
            ->assertSee('this.markInteracted();', false)
            ->assertSee('this.cancelPendingFocus();', false)
            ->assertSee('this.$nextTick(() => previousFocus.focus());', false)
            ->assertSee('data-admin-shell', false);

        $this->assertSame(2, substr_count($html, 'data-primary-designer-tab='));
        $this->assertSame(3, substr_count($html, 'data-supported-template-card='));
    }
}
