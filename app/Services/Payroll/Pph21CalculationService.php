<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\Pph21Parameter;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class Pph21CalculationService
{
    /**
     * Calculate monthly PPh 21 tax withholding for an employee.
     *
     * @param  Employee  $employee
     * @param  float  $basicSalary
     * @param  float  $taxableAllowances
     * @param  array{kes: float, jkk: float, jkm: float}  $employerBpjs
     * @param  array{jht: float, jp: float}  $employeeBpjs
     * @param  CarbonInterface|string|null  $asOfDate
     * @return array{
     *     monthly_tax: float,
     *     annual_tax: float,
     *     taxable_gross: float,
     *     biaya_jabatan: float,
     *     pensiun_deductions: float,
     *     monthly_net: float,
     *     annual_net: float,
     *     ptkp_code: string,
     *     ptkp_annual: float,
     *     annual_pkp: float
     * }
     */
    public function calculateMonthlyTax(
        Employee $employee,
        float $basicSalary,
        float $taxableAllowances = 0.0,
        array $employerBpjs = ['kes' => 0.0, 'jkk' => 0.0, 'jkm' => 0.0],
        array $employeeBpjs = ['jht' => 0.0, 'jp' => 0.0],
        CarbonInterface|string|null $asOfDate = null
    ): array {
        $date = $asOfDate ? Carbon::parse($asOfDate) : Carbon::today();

        // 1. Determine PTKP code based on marital status & dependents
        $isMarried = strtolower((string) $employee->marital_status) === 'married';
        $prefix = $isMarried ? 'K' : 'TK';
        $dependents = min(3, max(0, (int) $employee->tax_dependents));
        $ptkpCode = "PTKP_{$prefix}{$dependents}";

        // 2. Fetch PTKP threshold
        $ptkpAnnual = $this->getPtkpAmount($ptkpCode, $date, $prefix, $dependents);

        // 3. Taxable Gross Income (Gaji Pokok + Tunjangan Kena Pajak + Premi JKK/JKM/Kes Pemberi Kerja)
        $kesEmployer = (float) ($employerBpjs['kes'] ?? 0.0);
        $jkkEmployer = (float) ($employerBpjs['jkk'] ?? 0.0);
        $jkmEmployer = (float) ($employerBpjs['jkm'] ?? 0.0);

        $taxableGross = $basicSalary + $taxableAllowances + $kesEmployer + $jkkEmployer + $jkmEmployer;

        // 4. Deductions (Biaya Jabatan 5% maks Rp 500.000/bln + Iuran JHT & JP Pegawai)
        $biayaJabatan = $this->calculateBiayaJabatan($taxableGross, $date);

        $jhtEmployee = (float) ($employeeBpjs['jht'] ?? 0.0);
        $jpEmployee = (float) ($employeeBpjs['jp'] ?? 0.0);
        $pensiunDeductions = $jhtEmployee + $jpEmployee;

        $totalDeductions = $biayaJabatan + $pensiunDeductions;

        // 5. Monthly & Annual Net Income
        $monthlyNet = max(0.0, $taxableGross - $totalDeductions);
        $annualNet = $monthlyNet * 12;

        // 6. Taxable Income (Penghasilan Kena Pajak / PKP Setahun)
        $annualPkp = max(0.0, $annualNet - $ptkpAnnual);
        // Round down to thousands per Indonesian Directorate General of Taxes (DJP) rules
        $annualPkp = floor($annualPkp / 1000) * 1000;

        // 7. Progressive Tax Calculation (Tarif Progresif Pasal 17 UU HPP)
        $annualTax = 0.0;
        if ($annualPkp > 0.0) {
            $annualTax = $this->calculateProgressiveTax($annualPkp, $date);
        }

        // 8. Monthly Tax Withholding
        $monthlyTax = round($annualTax / 12, 2);

        return [
            'monthly_tax' => $monthlyTax,
            'annual_tax' => round($annualTax, 2),
            'taxable_gross' => round($taxableGross, 2),
            'biaya_jabatan' => round($biayaJabatan, 2),
            'pensiun_deductions' => round($pensiunDeductions, 2),
            'monthly_net' => round($monthlyNet, 2),
            'annual_net' => round($annualNet, 2),
            'ptkp_code' => $ptkpCode,
            'ptkp_annual' => $ptkpAnnual,
            'annual_pkp' => $annualPkp,
        ];
    }

    /**
     * Resolve PTKP annual amount from configured Pph21Parameter or standard statutory defaults.
     */
    protected function getPtkpAmount(string $ptkpCode, Carbon $date, string $prefix, int $dependents): float
    {
        $param = Pph21Parameter::where('category', 'ptkp')
            ->where('code', $ptkpCode)
            ->where('is_active', true)
            ->where('effective_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date->toDateString());
            })
            ->latest('effective_from')
            ->first();

        if ($param && $param->amount !== null) {
            return (float) $param->amount;
        }

        // Standard statutory fallbacks (UU PPh / UU HPP):
        // Wajib Pajak Sendiri: Rp 54.000.000
        // Tambahan Kawin: Rp 4.500.000
        // Tambahan per tanggungan (maks 3): Rp 4.500.000
        $base = 54000000.00;
        $marriage = ($prefix === 'K') ? 4500000.00 : 0.0;
        $depAmount = $dependents * 4500000.00;

        return $base + $marriage + $depAmount;
    }

    /**
     * Calculate monthly Biaya Jabatan (5% max Rp 500.000/month or from parameter).
     */
    protected function calculateBiayaJabatan(float $taxableGross, Carbon $date): float
    {
        $param = Pph21Parameter::where('category', 'biaya_jabatan')
            ->where('is_active', true)
            ->where('effective_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date->toDateString());
            })
            ->latest('effective_from')
            ->first();

        $rate = $param && $param->rate !== null ? (float) $param->rate : 0.05;
        $monthlyCap = $param && $param->amount !== null ? (float) $param->amount : 500000.00;

        return min($taxableGross * $rate, $monthlyCap);
    }

    /**
     * Calculate annual progressive tax according to Pasal 17 UU HPP brackets.
     */
    protected function calculateProgressiveTax(float $pkp, Carbon $date): float
    {
        $brackets = Pph21Parameter::where('category', 'tarif_pasal_17')
            ->where('is_active', true)
            ->where('effective_from', '<=', $date->toDateString())
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $date->toDateString());
            })
            ->orderBy('threshold_min')
            ->get();

        if ($brackets->isNotEmpty()) {
            $tax = 0.0;
            foreach ($brackets as $bracket) {
                $min = (float) $bracket->threshold_min;
                $max = $bracket->threshold_max !== null ? (float) $bracket->threshold_max : null;
                $rate = (float) $bracket->rate;

                if ($pkp > $min) {
                    $taxableAmount = $max !== null ? min($pkp, $max) - $min : $pkp - $min;
                    $tax += $taxableAmount * $rate;
                }
            }

            return $tax;
        }

        // Standard statutory fallbacks (UU HPP No. 7 Tahun 2021):
        // 0 - 60 jt: 5%
        // 60 - 250 jt: 15%
        // 250 - 500 jt: 25%
        // 500 jt - 5 M: 30%
        // > 5 M: 35%
        $tax = 0.0;
        $tiers = [
            ['min' => 0.0, 'max' => 60000000.0, 'rate' => 0.05],
            ['min' => 60000000.0, 'max' => 250000000.0, 'rate' => 0.15],
            ['min' => 250000000.0, 'max' => 500000000.0, 'rate' => 0.25],
            ['min' => 500000000.0, 'max' => 5000000000.0, 'rate' => 0.30],
            ['min' => 5000000000.0, 'max' => null, 'rate' => 0.35],
        ];

        foreach ($tiers as $tier) {
            $min = $tier['min'];
            $max = $tier['max'];
            $rate = $tier['rate'];

            if ($pkp > $min) {
                $taxableAmount = $max !== null ? min($pkp, $max) - $min : $pkp - $min;
                $tax += $taxableAmount * $rate;
            }
        }

        return $tax;
    }
}
