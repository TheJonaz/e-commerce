<?php

namespace Tests\Unit;

use App\Support\Vat;
use PHPUnit\Framework\TestCase;

class VatTest extends TestCase
{
    public function test_extracts_vat_from_gross(): void
    {
        $this->assertSame(25.00, Vat::fromGross(125, 25));
        $this->assertSame(2.68, Vat::fromGross(25, 12));
        $this->assertSame(0.0, Vat::fromGross(100, 0));
    }

    public function test_net_strips_vat(): void
    {
        $this->assertSame(100.00, Vat::net(125, 25));
        $this->assertSame(100.00, Vat::net(112, 12));
        $this->assertSame(100.00, Vat::net(106, 6));
        $this->assertSame(100.00, Vat::net(100, 0));
    }

    public function test_adds_vat_to_net(): void
    {
        $this->assertSame(25.00, Vat::fromNet(100, 25));
        $this->assertSame(12.00, Vat::fromNet(100, 12));
    }

    public function test_summarize_lines(): void
    {
        $totals = Vat::summarize([
            ['qty' => 2, 'unit_price_incl_vat' => 125.00, 'vat_rate' => 25],
            ['qty' => 1, 'unit_price_incl_vat' => 56.00, 'vat_rate' => 12],
        ]);

        $this->assertSame(306.00, $totals['grand_total']);
        $this->assertSame(250.00, $totals['subtotal_excl_vat']);
        $this->assertSame(56.00, $totals['vat_total']);
        $this->assertSame(
            $totals['grand_total'],
            round($totals['subtotal_excl_vat'] + $totals['vat_total'], 2)
        );
    }
}
