<?php

namespace App\Services;

/**
 * Splits a taxable amount into CGST + SGST (intra-state) and rounds the total
 * to the nearest rupee. All amounts are integer paise.
 */
class GstCalculator
{
    public function rate(): float
    {
        return (float) config('dailytable.gst.rate_percent');
    }

    /** @return array{taxable:int,cgst:int,sgst:int,round_off:int,total:int} */
    public function forTaxable(int $taxablePaise): array
    {
        $half = $this->rate() / 2 / 100;
        $cgst = (int) round($taxablePaise * $half);
        $sgst = (int) round($taxablePaise * $this->rate() / 100) - $cgst;
        $gross = $taxablePaise + $cgst + $sgst;
        $rounded = (int) (round($gross / 100) * 100);
        return ['taxable' => $taxablePaise, 'cgst' => $cgst, 'sgst' => $sgst, 'round_off' => $rounded - $gross, 'total' => $rounded];
    }

    /** When menu prices include GST, back out the taxable value first. */
    public function taxableFromInclusive(int $inclusivePaise): int
    {
        return (int) round($inclusivePaise / (1 + $this->rate() / 100));
    }
}
