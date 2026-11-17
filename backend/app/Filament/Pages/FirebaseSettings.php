<?php

namespace App\Filament\Pages;

use App\Models\IntegrationSetting;
use App\Services\Notifications\FirebaseConnectionTester;
use App\Services\Notifications\FirebaseCredentialsProvider;
use App\Support\AdminNavigation;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FirebaseSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static string $view = 'filament.pages.firebase-settings';

    protected static ?string $navigationLabel = 'Firebase';

    protected static ?string $title = 'إعدادات Firebase';

    protected static ?string $navigationGroup = AdminNavigation::INTEGRATIONS;

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public array $lastTest = [];

    public function mount(): void
    {
        $meta = app(FirebaseCredentialsProvider::class)->meta();
        $raw = IntegrationSetting::query()->where('key', 'firebase_last_test')->value('value');
        $this->lastTest = $raw ? (json_decode($raw, true) ?: []) : [];

        $this->form->fill([
            'project_id' => $meta['project_id'] ?? null,
            'client_email' => $meta['client_email'] ?? null,
            'uploaded_at' => $meta['uploaded_at'] ?? null,
        ]);
    }

    public function form(Form $form): Form
    {
        $hasCredentials = (bool) app(FirebaseCredentialsProvider::class)->path();

        return $form
            ->schema([
                Forms\Components\Section::make('حالة التكامل')
                    ->description('ارفع ملف Service Account من Firebase Console ثم اختبر الاتصال.')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Forms\Components\Placeholder::make('conn_status')
                            ->label('الاعتماد')
                            ->content($hasCredentials ? '✅ ملف الاعتماد محفوظ' : '⚠️ لم يُرفع ملف بعد'),
                        Forms\Components\Placeholder::make('info_project')
                            ->label('Project ID')
                            ->content(fn () => $this->data['project_id'] ?? '—'),
                        Forms\Components\Placeholder::make('info_email')
                            ->label('Client Email')
                            ->content(fn () => $this->data['client_email'] ?? '—'),
                        Forms\Components\Placeholder::make('info_uploaded')
                            ->label('آخر رفع')
                            ->content(fn () => $this->data['uploaded_at'] ?? '—'),
                    ])->columns(2),

                Forms\Components\Section::make('رفع الاعتماد')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->schema([
                        Forms\Components\FileUpload::make('credentials')
                            ->label('ملف JSON')
                            ->acceptedFileTypes(['application/json', 'text/plain', '.json'])
                            ->storeFiles(false)
                            ->helperText('من Firebase → Project settings → Service accounts')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('اختبار اختياري')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('device_token')
                            ->label('Device Token')
                            ->placeholder('اختياري لإرسال إشعار تجريبي لجهاز')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $file = $this->data['credentials'] ?? null;
        $uploaded = $this->resolveUpload($file);

        if (! $uploaded) {
            Notification::make()->title('اختر ملف JSON')->danger()->send();

            return;
        }

        $contents = $uploaded->get();
        $json = json_decode($contents, true);

        if (! is_array($json)) {
            Notification::make()->title('ملف JSON غير صالح')->danger()->send();

            return;
        }

        try {
            $meta = app(FirebaseCredentialsProvider::class)->storeUploadedJson(
                $json,
                $uploaded->getClientOriginalName() ?: 'service-account.json'
            );
            $this->data['project_id'] = $meta['project_id'] ?? null;
            $this->data['client_email'] = $meta['client_email'] ?? null;
            $this->data['uploaded_at'] = $meta['uploaded_at'] ?? null;
            $this->data['credentials'] = null;
            Notification::make()->title('تم حفظ اعتماد Firebase')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('فشل الحفظ')->body($e->getMessage())->danger()->send();
        }
    }

    public function testConnection(): void
    {
        $token = $this->data['device_token'] ?? null;
        $result = app(FirebaseConnectionTester::class)->test(filled($token) ? $token : null);
        $this->lastTest = $result;
        Notification::make()
            ->title($result['ok'] ? 'اختبار ناجح' : 'اختبار فاشل')
            ->body('الحالة: '.$result['status'])
            ->{$result['ok'] ? 'success' : 'danger'}()
            ->send();
    }

    protected function resolveUpload(mixed $file): ?TemporaryUploadedFile
    {
        if ($file instanceof TemporaryUploadedFile) {
            return $file;
        }

        if (is_array($file)) {
            $first = reset($file);

            return $first instanceof TemporaryUploadedFile ? $first : null;
        }

        return null;
    }
}
