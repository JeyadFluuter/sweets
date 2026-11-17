<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationTemplateResource\Pages;
use App\Models\NotificationTemplate;
use App\Support\AdminNavigation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'قوالب الإشعارات';

    protected static ?string $modelLabel = 'قالب إشعار';

    protected static ?string $pluralModelLabel = 'قوالب الإشعارات';

    protected static ?string $navigationGroup = AdminNavigation::INTEGRATIONS;

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $knownKeys = [
            'order_submitted' => 'استلام الطلب',
            'order_accepted' => 'قبول الطلب',
            'order_rejected' => 'رفض الطلب',
            'payment_received' => 'تأكيد دفعة',
            'payment_expired' => 'انتهاء مهلة الدفع',
            'order_status_preparing' => 'بدء التحضير',
            'order_status_ready' => 'جاهز للاستلام',
            'order_status_out_for_delivery' => 'خرج للتوصيل',
            'order_status_completed' => 'اكتمال الطلب',
            'slot_suggested' => 'اقتراح موعد بديل',
        ];

        return $form->schema([
            Forms\Components\Section::make('هوية القالب')
                ->description('كل قالب يُرسل للإشعار المناسب في دورة حياة الطلب.')
                ->icon('heroicon-o-hashtag')
                ->schema([
                    Forms\Components\Select::make('key')
                        ->label('نوع الإشعار')
                        ->options($knownKeys)
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->unique(ignoreRecord: true)
                        ->helperText('أو أدخل مفتاحاً مخصصاً من القائمة إن وُجد'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('مفعّل')
                        ->default(true)
                        ->inline(false)
                        ->helperText('القالب غير المفعّل لن يُرسل'),
                ])->columns(2),

            Forms\Components\Section::make('محتوى الإشعار')
                ->icon('heroicon-o-pencil-square')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('عنوان الإشعار')
                        ->required()
                        ->maxLength(120)
                        ->live(onBlur: true)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('body')
                        ->label('نص الرسالة')
                        ->rows(5)
                        ->required()
                        ->live(onBlur: true)
                        ->helperText('متغيرات شائعة: :number  :total  :remaining  :customer')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('معاينة')
                ->icon('heroicon-o-device-phone-mobile')
                ->schema([
                    Forms\Components\Placeholder::make('preview')
                        ->label('')
                        ->content(function (Get $get) {
                            $title = $get('title') ?: 'عنوان الإشعار';
                            $body = $get('body') ?: 'نص الرسالة يظهر هنا…';
                            $body = str_replace(
                                [':number', ':total', ':remaining', ':customer'],
                                ['SW-260105-0001', '85.00', '40.00', 'أحمد'],
                                $body
                            );

                            return new \Illuminate\Support\HtmlString(
                                '<div class="mx-auto max-w-sm overflow-hidden rounded-2xl border border-amber-200/80 bg-gradient-to-b from-amber-50 to-white shadow-sm dark:border-amber-500/20 dark:from-gray-900 dark:to-gray-950">'
                                .'<div class="bg-amber-500/90 px-4 py-2 text-xs font-medium text-white">إشعار تجريبي · معمل الحلويات</div>'
                                .'<div class="space-y-1 px-4 py-4">'
                                .'<div class="text-sm font-semibold text-gray-900 dark:text-white">'.e($title).'</div>'
                                .'<div class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">'.e($body).'</div>'
                                .'</div></div>'
                            );
                        })
                        ->columnSpanFull(),
                ])
                ->collapsed(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('المفتاح')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('العنوان')
                    ->searchable()
                    ->description(fn (NotificationTemplate $record) => \Illuminate\Support\Str::limit($record->body, 60)),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('مفعّل')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->since(),
            ])
            ->defaultSort('key')
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('تعديل')
                    ->modalHeading(fn (NotificationTemplate $record) => 'تعديل قالب: '.$record->key)
                    ->modalWidth('3xl')
                    ->slideOver(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageNotificationTemplates::route('/')];
    }
}
