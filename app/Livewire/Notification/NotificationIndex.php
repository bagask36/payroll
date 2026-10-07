<?php

namespace App\Livewire\Notification;

use App\Services\Notification\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Pusat Notifikasi & Pemberitahuan - RS Permata Bunda')]
class NotificationIndex extends Component
{
    #[Url(except: 'all')]
    public string $selectedCategory = 'all';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $selectedType = 'all';

    /**
     * @var array<string, bool>
     */
    public array $dismissedAlerts = [];

    public function mount(): void
    {
        $this->dismissedAlerts = session()->get('dismissed_notifications', []);
    }

    public function setCategory(string $category): void
    {
        $this->selectedCategory = $category;
    }

    public function setType(string $type): void
    {
        $this->selectedType = $type;
    }

    public function dismiss(string $id): void
    {
        $this->dismissedAlerts[$id] = true;
        session()->put('dismissed_notifications', $this->dismissedAlerts);
    }

    public function restoreDismissed(): void
    {
        $this->dismissedAlerts = [];
        session()->forget('dismissed_notifications');
    }

    /**
     * Get processed notifications filtered by category, type, and search.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getFilteredNotificationsProperty(NotificationService $service): Collection
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        $allNotifications = $service->getNotificationsForUser($user);

        return $allNotifications
            ->reject(fn ($alert) => isset($this->dismissedAlerts[$alert['id']]))
            ->filter(function ($alert) {
                // Category filter
                if ($this->selectedCategory === 'urgent') {
                    if (empty($alert['is_urgent'])) {
                        return false;
                    }
                } elseif ($this->selectedCategory !== 'all') {
                    if (($alert['category'] ?? '') !== $this->selectedCategory) {
                        return false;
                    }
                }

                // Severity type filter
                if ($this->selectedType !== 'all' && ($alert['type'] ?? '') !== $this->selectedType) {
                    return false;
                }

                // Text search query
                if (! empty($this->search)) {
                    $needle = mb_strtolower(trim($this->search));
                    $haystack = mb_strtolower(
                        ($alert['title'] ?? '').' '.
                        ($alert['message'] ?? '').' '.
                        ($alert['category_label'] ?? '').' '.
                        ($alert['badge'] ?? '')
                    );

                    return str_contains($haystack, $needle);
                }

                return true;
            })
            ->values();
    }

    public function render(NotificationService $service): View
    {
        $user = Auth::user();
        $rawAlerts = $user ? $service->getNotificationsForUser($user) : collect();

        $stats = [
            'total' => $rawAlerts->count(),
            'urgent' => $rawAlerts->where('is_urgent', true)->count(),
            'info' => $rawAlerts->whereIn('type', ['info', 'success'])->count(),
            'system' => $rawAlerts->where('category', 'sistem')->count(),
        ];

        return view('livewire.notification.notification-index', [
            'notifications' => $this->filteredNotifications,
            'stats' => $stats,
            'hasDismissed' => count($this->dismissedAlerts) > 0,
        ]);
    }
}
