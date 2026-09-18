<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Modules\Tienda\Storefront\StorefrontContext;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class StorefrontContextTest extends TestCase
{
    private function context(array $settings): StorefrontContext
    {
        return new StorefrontContext(
            new Project(['name' => 'Test']), $settings, ['key' => 'direct'], ['key' => 'direct'],
            collect(), collect(), collect(), null, collect(), collect(),
            ['home' => '/demo', 'shop' => '/demo/tienda'], ['catalog' => true], [], [], collect(),
        );
    }

    public function test_explicit_false_zero_string_zero_and_empty_string_are_not_absence(): void
    {
        $context = $this->context(['false' => false, 'zero' => 0, 'string_zero' => '0', 'empty' => '']);

        $this->assertFalse($context->setting('false', true));
        $this->assertSame(0, $context->setting('zero', 99));
        $this->assertSame('0', $context->setting('string_zero', '1'));
        $this->assertSame('', $context->setting('empty', 'fallback'));
        $this->assertSame('fallback', $context->setting('missing', 'fallback'));
    }

    public function test_read_contract_exposes_template_urls_capabilities_and_store_view(): void
    {
        $context = $this->context(['primary_color' => '#123456']);

        $this->assertSame('#123456', $context->setting('primary_color'));
        $this->assertSame('direct', $context->templateKey());
        $this->assertSame('/demo/tienda', $context->publicUrl('shop'));
        $this->assertTrue($context->capabilities()['catalog']);
        $this->assertSame('home', $context->storeView);
    }
}
