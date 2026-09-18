<?php

namespace Tests\Unit;

use App\Modules\Finanzas\Models\Invoice;
use Tests\TestCase;

class InvoiceInternalDateTest extends TestCase
{
    public function test_uses_fiscal_date_when_there_is_no_internal_override(): void
    {
        $invoice = new Invoice([
            'issue_date' => '2026-09-04',
        ]);

        $this->assertSame('2026-09-04', $invoice->fechaInterna()?->format('Y-m-d'));
    }

    public function test_internal_override_does_not_modify_fiscal_date(): void
    {
        $invoice = new Invoice([
            'issue_date' => '2026-09-04',
            'internal_issue_date' => '2026-09-03',
        ]);

        $this->assertSame('2026-09-03', $invoice->fechaInterna()?->format('Y-m-d'));
        $this->assertSame('2026-09-04', $invoice->issue_date?->format('Y-m-d'));
    }
}
