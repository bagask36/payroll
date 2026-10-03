<?php

namespace App\Livewire\Master;

use App\Models\GeofenceLocation;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Data Master: Titik Geofence Presensi - RS Juwita')]
class GeofenceIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showGeofenceModal = false;

    public ?int $editingGeofenceId = null;

    public string $geofenceName = '';

    public float $geofenceLat = -6.2088;

    public float $geofenceLng = 106.8456;

    public int $geofenceRadius = 150;

    public bool $geofenceIsActive = true;

    public ?string $feedbackMessage = null;

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user || (! $user->hasPermission('system.config_view') && ! $user->hasRole('super_admin') && ! $user->hasRole('hr_admin'))) {
            abort(403, 'Akses ke master data dibatasi khusus Administrator HR dan Super Admin.');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateGeofence(): void
    {
        $this->reset(['editingGeofenceId', 'geofenceName']);
        $this->geofenceLat = -6.2088;
        $this->geofenceLng = 106.8456;
        $this->geofenceRadius = 150;
        $this->geofenceIsActive = true;
        $this->showGeofenceModal = true;
        $this->dispatch('init-geofence-map', [
            'lat' => $this->geofenceLat,
            'lng' => $this->geofenceLng,
            'radius' => $this->geofenceRadius,
        ]);
    }

    public function editGeofence(int $id): void
    {
        $geo = GeofenceLocation::findOrFail($id);
        $this->editingGeofenceId = $geo->id;
        $this->geofenceName = $geo->name;
        $this->geofenceLat = (float) $geo->latitude;
        $this->geofenceLng = (float) $geo->longitude;
        $this->geofenceRadius = (int) $geo->radius_meters;
        $this->geofenceIsActive = (bool) $geo->is_active;
        $this->showGeofenceModal = true;
        $this->dispatch('init-geofence-map', [
            'lat' => $this->geofenceLat,
            'lng' => $this->geofenceLng,
            'radius' => $this->geofenceRadius,
        ]);
    }

    public function saveGeofence(AuditService $auditService): void
    {
        $this->validate([
            'geofenceName' => 'required|string|max:100',
            'geofenceLat' => 'required|numeric|between:-90,90',
            'geofenceLng' => 'required|numeric|between:-180,180',
            'geofenceRadius' => 'required|integer|min:10|max:5000',
        ]);

        if ($this->editingGeofenceId) {
            $geo = GeofenceLocation::findOrFail($this->editingGeofenceId);
            $geo->update([
                'name' => $this->geofenceName,
                'latitude' => $this->geofenceLat,
                'longitude' => $this->geofenceLng,
                'radius_meters' => $this->geofenceRadius,
                'is_active' => $this->geofenceIsActive,
            ]);
            $action = 'geofence.updated';
        } else {
            $geo = GeofenceLocation::create([
                'name' => $this->geofenceName,
                'latitude' => $this->geofenceLat,
                'longitude' => $this->geofenceLng,
                'radius_meters' => $this->geofenceRadius,
                'is_active' => $this->geofenceIsActive,
            ]);
            $action = 'geofence.created';
        }

        $auditService->log(action: $action, auditable: $geo, newValues: ['name' => $geo->name]);
        $this->showGeofenceModal = false;
        $this->feedbackMessage = "Titik lokasi geofence {$geo->name} berhasil disimpan!";
    }

    public function render()
    {
        $geofences = GeofenceLocation::when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        $allActiveGeofences = GeofenceLocation::where('is_active', true)->get();

        return view('livewire.master.geofence-index', [
            'geofences' => $geofences,
            'allActiveGeofences' => $allActiveGeofences,
        ]);
    }
}
