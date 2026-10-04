<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <flux:heading size="xl" class="font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                Master Data: Titik Geofence Presensi
            </flux:heading>
            <flux:subheading class="mt-1 text-zinc-500 dark:text-zinc-400">
                Konfigurasi titik koordinat GPS perimeter radius kehadiran mandiri pegawai dan dokter RS Juwita.
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            <flux:button variant="primary" icon="plus" wire:click="openCreateGeofence">
                Tambah Titik Geofence
            </flux:button>
        </div>
    </div>

    @if($feedbackMessage)
        <div class="p-4 rounded-xl border border-emerald-300 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 flex items-center gap-3">
            <flux:icon name="check-circle" class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
            <span class="text-sm font-medium">{{ $feedbackMessage }}</span>
        </div>
    @endif

    {{-- Interactive Map Overview Card --}}
    <flux:card class="space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-3">
            <div>
                <flux:heading size="md" class="font-semibold text-zinc-900 dark:text-zinc-100">
                    Peta Sebaran Titik Geofence Presensi RS Juwita
                </flux:heading>
                <flux:subheading class="text-xs text-zinc-500">
                    Visualisasi lokasi titik presensi aktif dan perimeter radius toleransi pegawai/dokter.
                </flux:subheading>
            </div>
            <div class="flex items-center gap-2">
                <flux:badge size="sm" color="emerald">
                    {{ $allActiveGeofences->count() }} Lokasi Aktif
                </flux:badge>
            </div>
        </div>

        {{-- Map Container for Overview --}}
        <div 
            x-data="geofenceOverviewMap(@js($allActiveGeofences))" 
            x-init="initOverviewMap()"
            class="relative w-full h-72 sm:h-80 rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800"
        >
            <div wire:ignore x-ref="overviewMapContainer" class="w-full h-full z-0"></div>
        </div>
    </flux:card>

    {{-- PowerGrid Datatable --}}
    <flux:card class="p-2 sm:p-4 shadow-xs border border-zinc-200/80 dark:border-zinc-800">
        <livewire:tables.geofence-table />
    </flux:card>

    {{-- MODAL GEOFENCE DENGAN MAP PINPOINT & RADIUS SETUP --}}
    <flux:modal wire:model="showGeofenceModal" class="max-w-4xl w-full space-y-4">
        <div>
            <flux:heading size="lg">{{ $editingGeofenceId ? 'Edit Titik Geofence' : 'Tambah Titik Geofence' }}</flux:heading>
            <flux:subheading class="text-xs text-zinc-500">Tentukan koordinat titik pusat menggunakan pin peta dan atur radius toleransi presensi.</flux:subheading>
        </div>

        <form wire:submit="saveGeofence" class="space-y-4" x-data="geofencePicker()" x-init="initPicker()">
            <flux:input wire:model="geofenceName" label="Nama Lokasi / Gedung RS" placeholder="Contoh: Gedung Rawat Inap Teratai" required />

            {{-- Map Picker Section --}}
            <div class="space-y-2">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                        Pilih Titik Pinpoint &amp; Pantau Radius pada Peta:
                    </span>
                    <div class="flex items-center gap-2">
                        <button 
                            type="button" 
                            @click="useCurrentLocation()" 
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 transition"
                        >
                            <flux:icon name="map-pin" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" />
                            Lokasi Saya Saat Ini
                        </button>
                        <button 
                            type="button" 
                            @click="resetToHospital()" 
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-200 transition"
                        >
                            <flux:icon name="building-office-2" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                            RS Juwita Utama
                        </button>
                    </div>
                </div>

                {{-- Interactive Map Viewport --}}
                <div class="relative w-full rounded-xl overflow-hidden border border-zinc-300 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-900 shadow-inner" style="min-height: 300px;">
                    <div wire:ignore x-ref="pickerMapContainer" class="w-full z-0" style="height: 300px; min-height: 300px; width: 100%;"></div>
                    <div class="absolute bottom-2 left-2 z-10 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xs px-2.5 py-1 rounded-md text-[11px] font-mono text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 shadow-xs pointer-events-none">
                        Lat: <span x-text="lat?.toFixed(6) ?? '-'"></span> | Lng: <span x-text="lng?.toFixed(6) ?? '-'"></span> | Radius: <span x-text="radius"></span>m
                    </div>
                </div>
                <p class="text-[11px] text-zinc-500">
                    💡 Klik pada peta atau geser (drag) pin penanda untuk menentukan titik pusat. Lingkaran hijau menunjukkan jangkauan toleransi presensi pegawai.
                </p>
            </div>

            {{-- Coordinates and Radius Inputs --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <flux:input 
                        wire:model="geofenceLat" 
                        type="number" 
                        step="any" 
                        label="Latitude" 
                        @input="onManualCoordChange()" 
                        required 
                    />
                </div>
                <div>
                    <flux:input 
                        wire:model="geofenceLng" 
                        type="number" 
                        step="any" 
                        label="Longitude" 
                        @input="onManualCoordChange()" 
                        required 
                    />
                </div>
                <div>
                    <flux:input 
                        wire:model="geofenceRadius" 
                        type="number" 
                        min="10" 
                        max="5000" 
                        label="Radius Toleransi (Meter)" 
                        @input="onRadiusChange($event.target.value)" 
                        required 
                    />
                </div>
            </div>

            {{-- Interactive Radius Slider & Presets --}}
            <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/80 dark:border-zinc-700/60 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-zinc-700 dark:text-zinc-300">Pengaturan Cepat Radius Presensi:</span>
                    <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono"><span x-text="radius"></span> Meter</span>
                </div>
                <input 
                    type="range" 
                    min="20" 
                    max="1000" 
                    step="5" 
                    x-model="radius" 
                    @input="onRadiusChange(radius)" 
                    class="w-full h-2 bg-zinc-200 rounded-lg appearance-none cursor-pointer dark:bg-zinc-700 accent-emerald-600"
                />
                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <span class="text-[11px] text-zinc-400">Preset:</span>
                    <button type="button" @click="setPresetRadius(50)" class="px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-200/80 dark:bg-zinc-700 hover:bg-emerald-100 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-300 transition">50m (Poli / Klinik)</button>
                    <button type="button" @click="setPresetRadius(100)" class="px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-200/80 dark:bg-zinc-700 hover:bg-emerald-100 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-300 transition">100m (Gedung Standar)</button>
                    <button type="button" @click="setPresetRadius(150)" class="px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-200/80 dark:bg-zinc-700 hover:bg-emerald-100 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-300 transition">150m (Kompleks Gedung)</button>
                    <button type="button" @click="setPresetRadius(300)" class="px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-200/80 dark:bg-zinc-700 hover:bg-emerald-100 dark:hover:bg-emerald-950/40 text-zinc-700 dark:text-zinc-300 transition">300m (Kawasan Luar RS)</button>
                </div>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" wire:model="geofenceIsActive" id="geoActive" class="rounded text-emerald-600 focus:ring-emerald-500">
                <label for="geoActive" class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Status Titik Aktif (Digunakan untuk validasi presensi)</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                <flux:modal.close><flux:button variant="subtle">Batal</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Simpan Lokasi Geofence</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Leaflet Map Scripts for Geofence Pinpoint & Overview --}}
    <script>
        function geofenceOverviewMap(geofences) {
            return {
                map: null,
                resizeObserver: null,
                initOverviewMap() {
                    const container = this.$refs.overviewMapContainer;
                    if (!container) return;

                    const renderMap = () => {
                        if (!window.L) {
                            setTimeout(renderMap, 50);
                            return;
                        }

                        if (this.map && this.map._container !== container) {
                            try { this.map.remove(); } catch (e) {}
                            this.map = null;
                        }

                        if (container._leaflet_id && !this.map) {
                            delete container._leaflet_id;
                            container.innerHTML = '';
                        }

                        if (this.map) {
                            try { this.map.remove(); } catch (e) {}
                            this.map = null;
                        }

                        this.map = L.map(container).setView([-6.2088, 106.8456], 15);

                        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap'
                        }).addTo(this.map);

                        if (!geofences || geofences.length === 0) {
                            return;
                        }

                        const bounds = L.latLngBounds();

                        geofences.forEach(g => {
                            const lat = parseFloat(g.latitude);
                            const lng = parseFloat(g.longitude);
                            const radius = parseInt(g.radius_meters) || 100;

                            const marker = L.marker([lat, lng]).addTo(this.map);
                            const circle = L.circle([lat, lng], {
                                radius: radius,
                                color: g.is_active ? '#059669' : '#71717a',
                                fillColor: g.is_active ? '#10b981' : '#a1a1aa',
                                fillOpacity: 0.2,
                                weight: 2
                            }).addTo(this.map);

                            marker.bindPopup(`
                                <div class="text-xs space-y-1">
                                    <strong class="text-sm font-semibold">${g.name}</strong><br/>
                                    <span class="text-zinc-500 font-mono">${lat.toFixed(6)}, ${lng.toFixed(6)}</span><br/>
                                    <span class="text-emerald-700 font-semibold font-mono">Radius: ${radius} meter</span><br/>
                                    <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] font-semibold ${g.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-zinc-100 text-zinc-600'}">
                                        ${g.is_active ? 'Aktif' : 'Nonaktif'}
                                    </span>
                                </div>
                            `);

                            bounds.extend([lat, lng]);
                        });

                        if (geofences.length > 0) {
                            this.map.fitBounds(bounds, { padding: [50, 50], maxZoom: 17 });
                        }

                        if (window.ResizeObserver && !this.resizeObserver) {
                            this.resizeObserver = new ResizeObserver(() => {
                                if (this.map && container.clientHeight > 0) {
                                    this.map.invalidateSize();
                                }
                            });
                            this.resizeObserver.observe(container);
                        }

                        setTimeout(() => this.map && this.map.invalidateSize(), 300);
                    };

                    setTimeout(renderMap, 100);
                }
            };
        }

        function geofencePicker() {
            return {
                lat: -6.2088,
                lng: 106.8456,
                radius: 150,
                map: null,
                marker: null,
                circle: null,
                resizeObserver: null,
                renderTimeout: null,

                initPicker() {
                    window.addEventListener('init-geofence-map', (e) => {
                        const detail = Array.isArray(e.detail) ? e.detail[0] : e.detail;
                        if (detail) {
                            this.lat = parseFloat(detail.lat) || -6.2088;
                            this.lng = parseFloat(detail.lng) || 106.8456;
                            this.radius = parseInt(detail.radius) || 150;
                        }
                        this.scheduleRender();
                    });

                    this.$watch('$wire.showGeofenceModal', (val) => {
                        if (val) {
                            const wireLat = parseFloat(this.$wire.get('geofenceLat'));
                            const wireLng = parseFloat(this.$wire.get('geofenceLng'));
                            const wireRadius = parseInt(this.$wire.get('geofenceRadius'));
                            if (!isNaN(wireLat)) this.lat = wireLat;
                            if (!isNaN(wireLng)) this.lng = wireLng;
                            if (!isNaN(wireRadius) && wireRadius > 0) this.radius = wireRadius;
                            this.scheduleRender();
                        } else {
                            this.destroyMap();
                        }
                    });
                },

                scheduleRender() {
                    if (this.renderTimeout) {
                        clearTimeout(this.renderTimeout);
                    }
                    this.renderTimeout = setTimeout(() => {
                        this.setupOrUpdateMap();
                    }, 50);
                },

                destroyMap() {
                    if (this.map) {
                        try { this.map.remove(); } catch (e) {}
                        this.map = null;
                        this.marker = null;
                        this.circle = null;
                    }
                    const container = this.$refs.pickerMapContainer;
                    if (container) {
                        delete container._leaflet_id;
                        container.innerHTML = '';
                    }
                },

                setupOrUpdateMap() {
                    const container = this.$refs.pickerMapContainer;
                    if (!container) return;

                    let attempts = 0;
                    const maxAttempts = 30;

                    const tryInit = () => {
                        attempts++;

                        if (!window.L) {
                            if (attempts < maxAttempts) setTimeout(tryInit, 50);
                            return;
                        }

                        // Tunggu hingga container memiliki dimensi width & height valid (> 0)
                        if (container.offsetWidth === 0 || container.offsetHeight === 0) {
                            if (attempts < maxAttempts) {
                                setTimeout(tryInit, 50);
                            }
                            return;
                        }

                        // Bersihkan map lama agar tidak ada memory leak / state rusak
                        if (this.map) {
                            try { this.map.remove(); } catch (e) {}
                            this.map = null;
                        }
                        if (container._leaflet_id) {
                            delete container._leaflet_id;
                            container.innerHTML = '';
                        }

                        // Inisialisasi peta Leaflet fresh
                        this.map = L.map(container, {
                            zoomControl: true,
                            scrollWheelZoom: true
                        }).setView([this.lat, this.lng], 16);

                        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap'
                        }).addTo(this.map);

                        this.marker = L.marker([this.lat, this.lng], {
                            draggable: true,
                            title: 'Titik Pusat Geofence'
                        }).addTo(this.map);

                        this.circle = L.circle([this.lat, this.lng], {
                            radius: this.radius,
                            color: '#059669',
                            fillColor: '#10b981',
                            fillOpacity: 0.25,
                            weight: 2
                        }).addTo(this.map);

                        // Klik pada peta untuk pindahkan pin
                        this.map.on('click', (e) => {
                            this.setPosition(e.latlng.lat, e.latlng.lng);
                        });

                        // Geser pin (drag) untuk perbarui lingkaran dan koordinat
                        this.marker.on('drag', (e) => {
                            const pos = e.target.getLatLng();
                            if (this.circle) {
                                this.circle.setLatLng(pos);
                            }
                        });

                        this.marker.on('dragend', (e) => {
                            const pos = e.target.getLatLng();
                            this.setPosition(pos.lat, pos.lng);
                        });

                        // ResizeObserver untuk menjaga ukuran jika window/dialog berubah
                        if (window.ResizeObserver && !this.resizeObserver) {
                            this.resizeObserver = new ResizeObserver(() => {
                                if (this.map && container.clientHeight > 0) {
                                    this.map.invalidateSize();
                                }
                            });
                            this.resizeObserver.observe(container);
                        }

                        // Invalidate size beberapa kali untuk memastikan animasi transisi modal selesai
                        const invalidate = () => {
                            if (this.map && container.clientHeight > 0) {
                                this.map.invalidateSize();
                            }
                        };
                        requestAnimationFrame(invalidate);
                        setTimeout(invalidate, 100);
                        setTimeout(invalidate, 250);
                        setTimeout(invalidate, 500);
                    };

                    tryInit();
                },

                setPosition(newLat, newLng) {
                    this.lat = parseFloat(newLat.toFixed(6));
                    this.lng = parseFloat(newLng.toFixed(6));

                    if (this.marker) this.marker.setLatLng([this.lat, this.lng]);
                    if (this.circle) this.circle.setLatLng([this.lat, this.lng]);

                    this.$wire.set('geofenceLat', this.lat);
                    this.$wire.set('geofenceLng', this.lng);
                },

                onManualCoordChange() {
                    setTimeout(() => {
                        const manualLat = parseFloat(this.$wire.get('geofenceLat'));
                        const manualLng = parseFloat(this.$wire.get('geofenceLng'));

                        if (!isNaN(manualLat) && !isNaN(manualLng)) {
                            this.lat = manualLat;
                            this.lng = manualLng;
                            if (this.marker) this.marker.setLatLng([this.lat, this.lng]);
                            if (this.circle) this.circle.setLatLng([this.lat, this.lng]);
                            if (this.map) this.map.panTo([this.lat, this.lng]);
                        }
                    }, 50);
                },

                onRadiusChange(val) {
                    const r = parseInt(val) || 100;
                    this.radius = r;
                    if (this.circle) this.circle.setRadius(r);
                    this.$wire.set('geofenceRadius', r);
                },

                setPresetRadius(r) {
                    this.onRadiusChange(r);
                },

                useCurrentLocation() {
                    if ("geolocation" in navigator) {
                        navigator.geolocation.getCurrentPosition(
                            (pos) => {
                                const userLat = pos.coords.latitude;
                                const userLng = pos.coords.longitude;
                                if (this.map) this.map.setView([userLat, userLng], 17);
                                this.setPosition(userLat, userLng);
                            },
                            (err) => {
                                alert("Gagal membaca GPS browser: " + err.message);
                            },
                            { enableHighAccuracy: true, timeout: 8000 }
                        );
                    } else {
                        alert("Peramban Anda tidak mendukung sensor Geolocation.");
                    }
                },

                resetToHospital() {
                    const hospLat = -6.208800;
                    const hospLng = 106.845600;
                    if (this.map) this.map.setView([hospLat, hospLng], 16);
                    this.setPosition(hospLat, hospLng);
                }
            };
        }
    </script>
</div>
