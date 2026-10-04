<?php

namespace App\Http\Controllers;

use App\Models\PayrollItem;
use App\Models\SystemSetting;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PayrollPrintController extends Controller
{
    public function print(int $id, Request $request, AuditService $auditService)
    {
        $item = PayrollItem::with([
            'run.period',
            'employee.unit',
            'employee.position',
            'components',
        ])->findOrFail($id);

        // Authorize view via PayrollItemPolicy
        Gate::authorize('view', $item);

        $user = $request->user();
        $isPrivileged = $user && ($user->hasRole('super_admin') || $user->hasRole('hr_admin') || $user->hasRole('hr_manager') || $user->hasRole('payroll') || $user->hasRole('finance'));

        if (! $isPrivileged) {
            $unlockedUntil = session('payslip_unlocked_until');
            if (! $unlockedUntil || $unlockedUntil < now()->timestamp) {
                return redirect()->route('payroll.my-payslips')
                    ->with('error', 'Silakan masukkan kata sandi akun login Anda terlebih dahulu untuk mengakses slip gaji.');
            }
        }

        $auditService->log(
            action: 'payroll.payslip_printed',
            auditable: $item,
            newValues: [
                'employee_id' => $item->employee_id,
                'period_name' => $item->run?->period?->name,
                'net_salary' => $item->net_salary,
            ]
        );

        $hospital = [
            'name' => SystemSetting::get('hospital_name', 'Rumah Sakit Juwita'),
            'address' => SystemSetting::get('hospital_address', 'Jl. Mayor M. Hasibuan No. 78, Bekasi Timur, Jawa Barat'),
            'phone' => SystemSetting::get('hospital_phone', '(021) 8816654'),
            'email' => SystemSetting::get('hospital_email', 'sdm@rsjuwita.co.id'),
            'faskes_code' => SystemSetting::get('hospital_faskes_code', '3275012'),
            'npwp' => SystemSetting::get('hospital_npwp', '01.889.345.2-407.000'),
        ];

        $terbilangWords = $this->terbilang((float) $item->net_salary);
        $terbilangFormatted = $terbilangWords ? trim($terbilangWords) . ' Rupiah' : 'Nol Rupiah';

        return view('payroll.payslip-print', [
            'item' => $item,
            'hospital' => $hospital,
            'terbilang' => $terbilangFormatted,
        ]);
    }

    /**
     * Convert numeric amount to formal Indonesian words (Terbilang).
     */
    private function terbilang(float $number): string
    {
        $bilangan = [
            '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima',
            'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'
        ];

        $number = abs($number);

        if ($number < 12) {
            return $bilangan[(int) $number];
        } elseif ($number < 20) {
            return $this->terbilang($number - 10) . ' Belas';
        } elseif ($number < 100) {
            $puluh = intdiv((int) $number, 10);
            $sisa = (int) $number % 10;
            return trim($this->terbilang($puluh) . ' Puluh ' . $this->terbilang($sisa));
        } elseif ($number < 200) {
            return trim('Seratus ' . $this->terbilang($number - 100));
        } elseif ($number < 1000) {
            $ratus = intdiv((int) $number, 100);
            $sisa = (int) $number % 100;
            return trim($this->terbilang($ratus) . ' Ratus ' . $this->terbilang($sisa));
        } elseif ($number < 2000) {
            return trim('Seribu ' . $this->terbilang($number - 1000));
        } elseif ($number < 1000000) {
            $ribu = intdiv((int) $number, 1000);
            $sisa = (int) $number % 1000;
            return trim($this->terbilang($ribu) . ' Ribu ' . $this->terbilang($sisa));
        } elseif ($number < 1000000000) {
            $juta = intdiv((int) $number, 1000000);
            $sisa = (int) $number % 1000000;
            return trim($this->terbilang($juta) . ' Juta ' . $this->terbilang($sisa));
        } elseif ($number < 1000000000000) {
            $miliar = intdiv((int) $number, 1000000000);
            $sisa = fmod($number, 1000000000);
            return trim($this->terbilang($miliar) . ' Miliar ' . $this->terbilang($sisa));
        }

        return '';
    }
}
