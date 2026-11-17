<?php

namespace App\Filament\Pages;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\AiPromptVersion;
use App\Models\AiSetting;
use App\Services\Ai\AiManager;
use App\Support\AdminNavigation;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class AiSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static string $view = 'filament.pages.ai-settings';

    protected static ?string $navigationLabel = 'الذكاء الاصطناعي';

    protected static ?string $title = 'إعدادات المساعد الذكي';

    protected static ?string $navigationGroup = AdminNavigation::INTEGRATIONS;

    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public ?AiSetting $record = null;

    public function mount(): void
    {
        $this->record = app(AiManager::class)->settings();
        $this->fillFormFromRecord();
    }

    public function form(Form $form): Form
    {
        $templates = app(AiManager::class)->templates();

        return $form
            ->schema([
                Forms\Components\Section::make('الحالة الحالية')
                    ->description('ملخص سريع لحالة المساعد في التطبيق.')
                    ->icon('heroicon-o-signal')
                    ->schema([
                        Forms\Components\Placeholder::make('info_enabled')
                            ->label('في التطبيق')
                            ->content(fn () => $this->record?->is_enabled ? '🟢 مفعّل' : '⚪ متوقف'),
                        Forms\Components\Placeholder::make('info_test')
                            ->label('آخر اختبار اتصال')
                            ->content(function () {
                                $status = $this->record?->last_test_status;
                                if (! $status) {
                                    return 'لم يُختبر بعد';
                                }
                                $label = $status === 'connected' ? '✅ ناجح' : '❌ فاشل';
                                $at = $this->record?->last_tested_at?->format('Y-m-d H:i');

                                return $at ? "{$label} · {$at}" : $label;
                            }),
                        Forms\Components\Placeholder::make('info_key')
                            ->label('مفتاح API')
                            ->content(fn () => $this->record?->maskedApiKey() ?: 'غير مُعرَّف'),
                        Forms\Components\Placeholder::make('info_stats')
                            ->label('المحادثات / الرسائل')
                            ->content(function () {
                                $conversations = AiConversation::query()->count();
                                $messages = AiMessage::query()->count();

                                return "{$conversations} محادثة · {$messages} رسالة";
                            }),
                        Forms\Components\Placeholder::make('info_error')
                            ->label('آخر خطأ')
                            ->content(fn () => $this->record?->last_error ?: '—')
                            ->columnSpanFull()
                            ->visible(fn () => filled($this->record?->last_error)),
                    ])->columns(2),

                Forms\Components\Section::make('التفعيل')
                    ->icon('heroicon-o-bolt')
                    ->schema([
                        Forms\Components\Toggle::make('is_enabled')
                            ->label('تفعيل المساعد في التطبيق')
                            ->helperText('عند التفعيل يظهر تبويب المساعد في التطبيق، وعند الإيقاف يختفي. يُفضّل اختبار الاتصال بنجاح قبل التفعيل.')
                            ->inline(false),
                    ]),

                Forms\Components\Section::make('المزوّد والنموذج')
                    ->description('لـ Gemini: اختر القالب ثم الصق مفتاح Google AI Studio. لا تضع المفتاح في كود التطبيق.')
                    ->icon('heroicon-o-cpu-chip')
                    ->schema([
                        Forms\Components\Select::make('template')
                            ->label('قالب سريع')
                            ->options(collect($templates)->mapWithKeys(fn ($t, $k) => [$k => $t['label']]))
                            ->placeholder('اختر قالباً لتعبئة الحقول')
                            ->dehydrated(false)
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) use ($templates) {
                                if (! $state || ! isset($templates[$state])) {
                                    return;
                                }
                                $t = $templates[$state];
                                $set('provider', $t['provider']);
                                $set('base_url', $t['base_url']);
                                $set('model', $t['model']);
                                if (($t['provider'] ?? '') === 'openai_compatible' && str_contains((string) ($t['base_url'] ?? ''), 'generativelanguage.googleapis.com')) {
                                    $set('fallback_provider', 'demo');
                                }
                            })
                            ->columnSpanFull(),
                        Forms\Components\Select::make('provider')
                            ->label('المزوّد')
                            ->options([
                                'openai_compatible' => 'OpenAI Compatible (Gemini / Groq / OpenRouter / Ollama)',
                                'demo' => 'تجريبي محلي (بدون مفتاح)',
                                'anthropic' => 'Anthropic Claude',
                            ])
                            ->required()
                            ->native(false)
                            ->live(),
                        Forms\Components\Select::make('fallback_provider')
                            ->label('مزوّد احتياطي')
                            ->options([
                                'demo' => 'تجريبي محلي',
                                'openai_compatible' => 'OpenAI Compatible',
                                'anthropic' => 'Anthropic',
                            ])
                            ->placeholder('بدون احتياطي')
                            ->helperText('عند فشل Gemini يمكن الرجوع للتجريبي تلقائياً.')
                            ->native(false)
                            ->nullable(),
                        Forms\Components\TextInput::make('base_url')
                            ->label('Base URL')
                            ->helperText('Gemini: https://generativelanguage.googleapis.com/v1beta/openai/')
                            ->visible(fn (Get $get) => ! in_array($get('provider'), ['anthropic', 'demo'], true)),
                        Forms\Components\TextInput::make('model')
                            ->label('اسم النموذج')
                            ->required()
                            ->placeholder('gemini-2.5-flash')
                            ->helperText('الموصى به حالياً: gemini-2.5-flash (gemini-2.0-flash لم يعد متاحاً).'),
                        Forms\Components\TextInput::make('api_key')
                            ->label('مفتاح API (Gemini)')
                            ->password()
                            ->revealable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->visible(fn (Get $get) => $get('provider') !== 'demo')
                            ->helperText(fn () => $this->record?->maskedApiKey()
                                ? 'المفتاح الحالي: '.$this->record->maskedApiKey().' — اترك الحقل فارغاً للإبقاء عليه. يمكن أيضاً وضع GEMINI_API_KEY في .env'
                                : 'من https://aistudio.google.com/apikey — يُحفظ مشفّراً هنا أو عبر GEMINI_API_KEY في .env')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('حدود السلوك')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->schema([
                        Forms\Components\TextInput::make('temperature')
                            ->label('Temperature')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(2)
                            ->step(0.1)
                            ->required(),
                        Forms\Components\TextInput::make('max_tokens')
                            ->label('Max Tokens')
                            ->numeric()
                            ->minValue(64)
                            ->required(),
                        Forms\Components\TextInput::make('daily_message_limit')
                            ->label('حد الرسائل اليومي لكل عميل')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('تعليمات النظام (System Prompt)')
                    ->description('البرومبت الكامل للمساعد. يمكنك تعديل كل حرف ثم الحفظ. يُحقن تلقائياً مع قوائم المنتجات والباقات عبر المتغيرات.')
                    ->icon('heroicon-o-document-text')
                    ->collapsible(false)
                    ->schema([
                        Forms\Components\Placeholder::make('prompt_vars')
                            ->label('متغيرات تُستبدل تلقائياً عند التشغيل')
                            ->content('{{today}} · {{products}} · {{collections}} · {{lead_time_rules}}')
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('prompt_hint')
                            ->label('ملاحظة')
                            ->content('عدّل الأسلوب، القواعد، الحالات، والقيود كما تشاء. بعد الحفظ يُحفظ إصدار في السجل أدناه ويمكن استرجاعه.')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('system_prompt')
                            ->label('البرومبت الكامل (قابل للتعديل بالكامل)')
                            ->rows(32)
                            ->extraInputAttributes([
                                'style' => 'min-height: 28rem; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.875rem; line-height: 1.55; direction: rtl;',
                                'spellcheck' => 'true',
                            ])
                            ->autosize()
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('resetPrompt')
                                ->label('استعادة البرومبت الافتراضي الكامل')
                                ->icon('heroicon-o-arrow-path')
                                ->color('gray')
                                ->requiresConfirmation()
                                ->modalHeading('استعادة البرومبت الافتراضي؟')
                                ->modalDescription('سيتم استبدال النص الحالي بالنسخة الافتراضية من النظام. لن يُحفظ إلا بعد ضغط «حفظ الإعدادات».')
                                ->action(function (Forms\Set $set) {
                                    $set('system_prompt', app(AiManager::class)->defaultPrompt());
                                    Notification::make()
                                        ->title('تمت تعبئة البرومبت الافتراضي — اضغط حفظ للتطبيق')
                                        ->success()
                                        ->send();
                                }),
                            Forms\Components\Actions\Action::make('copyDefaultLength')
                                ->label('إحصاء الأحرف')
                                ->icon('heroicon-o-calculator')
                                ->color('gray')
                                ->action(function (Forms\Get $get) {
                                    $len = mb_strlen((string) $get('system_prompt'));
                                    Notification::make()
                                        ->title('طول البرومبت الحالي: '.$len.' حرف')
                                        ->info()
                                        ->send();
                                }),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $record = $this->record ??= app(AiManager::class)->settings();

        $payload = [
            'is_enabled' => (bool) ($data['is_enabled'] ?? false),
            'provider' => $data['provider'],
            'fallback_provider' => $data['fallback_provider'] ?? null,
            'base_url' => $data['base_url'] ?? null,
            'model' => $data['model'],
            'temperature' => $data['temperature'],
            'max_tokens' => $data['max_tokens'],
            'daily_message_limit' => $data['daily_message_limit'],
            'system_prompt' => $data['system_prompt'],
        ];

        if (filled($data['api_key'] ?? null)) {
            $payload['api_key_encrypted'] = $data['api_key'];
        }

        $promptChanged = trim((string) $record->system_prompt) !== trim((string) $payload['system_prompt']);

        $record->fill($payload)->save();

        if ($promptChanged) {
            AiPromptVersion::query()->create([
                'prompt' => $payload['system_prompt'],
                'edited_by' => Auth::id(),
            ]);
        }

        $this->record = $record->fresh();
        $this->fillFormFromRecord();

        Notification::make()
            ->title('تم حفظ إعدادات الذكاء الاصطناعي')
            ->success()
            ->send();
    }

    public function testConnection(): void
    {
        // احفظ أولاً إن وُجدت تغييرات في المفتاح/المزوّد حتى يُختبر الإعداد الحالي
        $this->saveQuietlyForTest();

        $result = app(AiManager::class)->testConnection($this->record->fresh());
        $this->record = $this->record->fresh();
        $this->fillFormFromRecord();

        Notification::make()
            ->title($result->ok ? 'الاتصال ناجح' : 'فشل الاتصال')
            ->body($result->message)
            ->{$result->ok ? 'success' : 'danger'}()
            ->send();
    }

    /**
     * @return list<array{id:int,prompt:string,preview:string,editor:?string,at:string,chars:int}>
     */
    public function recentPromptVersions(): array
    {
        return AiPromptVersion::query()
            ->with('editor:id,name')
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (AiPromptVersion $v) => [
                'id' => $v->id,
                'prompt' => (string) $v->prompt,
                'preview' => mb_substr($v->prompt, 0, 220).(mb_strlen($v->prompt) > 220 ? '…' : ''),
                'editor' => $v->editor?->name,
                'at' => $v->created_at?->format('Y-m-d H:i') ?? '—',
                'chars' => mb_strlen((string) $v->prompt),
            ])
            ->all();
    }

    public function restorePromptVersion(int $versionId): void
    {
        $version = AiPromptVersion::query()->findOrFail($versionId);
        $this->data['system_prompt'] = $version->prompt;
        $this->form->fill($this->data);

        Notification::make()
            ->title('تم تحميل نسخة البرومبت في المحرر — اضغط حفظ لتثبيتها')
            ->success()
            ->send();
    }

    protected function fillFormFromRecord(): void
    {
        $record = $this->record;
        if (! $record) {
            return;
        }

        $this->form->fill([
            'is_enabled' => $record->is_enabled,
            'provider' => $record->provider,
            'fallback_provider' => $record->fallback_provider,
            'base_url' => $record->base_url,
            'model' => $record->model,
            'api_key' => null,
            'temperature' => $record->temperature,
            'max_tokens' => $record->max_tokens,
            'daily_message_limit' => $record->daily_message_limit,
            'system_prompt' => $record->system_prompt ?: app(AiManager::class)->defaultPrompt(),
        ]);
    }

    protected function saveQuietlyForTest(): void
    {
        $data = $this->form->getState();
        $record = $this->record ??= app(AiManager::class)->settings();

        $payload = [
            'provider' => $data['provider'],
            'fallback_provider' => $data['fallback_provider'] ?? null,
            'base_url' => $data['base_url'] ?? null,
            'model' => $data['model'],
            'temperature' => $data['temperature'],
            'max_tokens' => $data['max_tokens'],
        ];

        if (filled($data['api_key'] ?? null)) {
            $payload['api_key_encrypted'] = $data['api_key'];
        }

        $record->forceFill($payload)->save();
        $this->record = $record->fresh();
    }
}
