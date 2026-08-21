<?php

namespace App\Filament\Pages;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\PlatformAnnouncement;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Notification as NotificationFacade;

/**
 * Phase 2 §1 — "important platform announcements". Broadcasts a single
 * in-app notification to every user, reusing the same notification
 * architecture as everything else (this is not a parallel messaging
 * system) — respects each recipient's own announcements preference.
 */
class SendAnnouncement extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Trust & Safety';

    protected static ?string $navigationLabel = 'Send Announcement';

    protected static string $view = 'filament.pages.send-announcement';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required()->maxLength(190),
            Forms\Components\Textarea::make('body')->required()->rows(4)->maxLength(2000),
        ])->statePath('data');
    }

    public function send(): void
    {
        $data = $this->form->getState();

        $recipients = User::all();

        NotificationFacade::send($recipients, new PlatformAnnouncement($data['title'], $data['body']));

        AuditLog::record('announcement.sent', auth()->user(), [
            'title' => $data['title'],
            'recipient_count' => $recipients->count(),
        ]);

        $this->form->fill();

        Notification::make()->title('Announcement sent to '.$recipients->count().' user(s)')->success()->send();
    }
}
