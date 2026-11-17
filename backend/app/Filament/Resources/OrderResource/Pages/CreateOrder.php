<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Product;
use App\Services\Orders\OrderService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'إنشاء طلبية';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('العميل والموعد')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\Select::make('customer_id')
                        ->label('العميل')
                        ->placeholder('ابحث بالاسم أو الهاتف...')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            $term = trim($search);
                            if ($term === '') {
                                return [];
                            }

                            return Customer::query()
                                ->where('is_guest', false)
                                ->where('is_blocked', false)
                                ->where(function ($q) use ($term) {
                                    $q->where('name', 'like', "%{$term}%")
                                        ->orWhere('phone', 'like', "%{$term}%");
                                })
                                ->orderBy('name')
                                ->limit(20)
                                ->get()
                                ->mapWithKeys(fn (Customer $c) => [
                                    $c->id => "{$c->name} — {$c->phone}",
                                ])
                                ->all();
                        })
                        ->getOptionLabelUsing(function ($value): ?string {
                            $c = Customer::query()->find($value);

                            return $c ? "{$c->name} — {$c->phone}" : null;
                        })
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Select::make('delivery_type')
                        ->label('الاستلام')
                        ->options(['pickup' => 'من المعمل', 'delivery' => 'توصيل'])
                        ->default('pickup')
                        ->required()
                        ->live(),

                    Forms\Components\DatePicker::make('fulfillment_date')
                        ->label('التاريخ')
                        ->required()
                        ->minDate(today())
                        ->default(today()->addDay()),

                    Forms\Components\TimePicker::make('fulfillment_time')
                        ->label('الوقت')
                        ->seconds(false)
                        ->required()
                        ->default('18:00'),

                    Forms\Components\TextInput::make('address_text')
                        ->label('عنوان التوصيل')
                        ->visible(fn (Get $get) => $get('delivery_type') === 'delivery')
                        ->required(fn (Get $get) => $get('delivery_type') === 'delivery')
                        ->columnSpanFull(),
                ])
                ->columns(3),

            Forms\Components\Section::make('الأصناف')
                ->icon('heroicon-o-shopping-bag')
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->label('')
                        ->schema([
                            Forms\Components\Select::make('sellable')
                                ->label('ابحث عن منتج أو باقة')
                                ->searchable()
                                ->getSearchResultsUsing(fn (string $search) => $this->searchSellables($search))
                                ->getOptionLabelUsing(fn ($value) => $this->sellableLabel($value))
                                ->required()
                                ->columnSpan(3),
                            Forms\Components\TextInput::make('quantity')
                                ->label('الكمية')
                                ->numeric()
                                ->minValue(1)
                                ->default(1)
                                ->required()
                                ->columnSpan(1),
                        ])
                        ->columns(4)
                        ->addActionLabel('+ إضافة')
                        ->defaultItems(1)
                        ->minItems(1)
                        ->required()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('الدفع')
                ->icon('heroicon-o-banknotes')
                ->schema([
                    Forms\Components\Radio::make('payment_mode')
                        ->label('طريقة الدفع')
                        ->options([
                            'pay_now' => 'دفع كامل الآن',
                            'pay_later' => 'آجل عند الاستلام',
                            'deposit' => 'عربون الآن (الباقي لاحقاً)',
                        ])
                        ->default('pay_later')
                        ->required()
                        ->live()
                        ->descriptions([
                            'pay_now' => 'يسجّل التحصيل فوراً ويؤكّد الطلب',
                            'pay_later' => 'يؤكّد الطلب والدفع عند الاستلام أو التوصيل',
                            'deposit' => 'عربون الآن — سجّل الباقي لاحقاً من تفاصيل الطلب',
                        ])
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('deposit_amount')
                        ->label('مبلغ العربون (د.ل)')
                        ->numeric()
                        ->minValue(0.01)
                        ->visible(fn (Get $get) => $get('payment_mode') === 'deposit')
                        ->required(fn (Get $get) => $get('payment_mode') === 'deposit'),

                    Forms\Components\Select::make('payment_method')
                        ->label('وسيلة التحصيل')
                        ->options([
                            'cash' => 'نقداً',
                            'transfer' => 'تحويل بنكي',
                        ])
                        ->default('cash')
                        ->visible(fn (Get $get) => in_array($get('payment_mode'), ['pay_now', 'deposit'], true))
                        ->required(fn (Get $get) => in_array($get('payment_mode'), ['pay_now', 'deposit'], true)),

                    Forms\Components\TextInput::make('payment_reference')
                        ->label('مرجع التحويل')
                        ->visible(fn (Get $get) => in_array($get('payment_mode'), ['pay_now', 'deposit'], true)
                            && $get('payment_method') === 'transfer'),

                    Forms\Components\Textarea::make('admin_note')
                        ->label('ملاحظة')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $items = [];

        foreach ($data['items'] ?? [] as $row) {
            $sellable = $row['sellable'] ?? null;
            if (! is_string($sellable) || ! str_contains($sellable, ':')) {
                continue;
            }
            [$type, $id] = explode(':', $sellable, 2);
            $items[] = [
                'item_type' => $type,
                'item_id' => (int) $id,
                'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
            ];
        }

        $data['items'] = $items;
        $data['recipient_is_self'] = true;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(OrderService::class)->placeByAdmin(auth()->user(), $data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('تم إنشاء الطلبية')
            ->body('رقم الطلب: '.$this->record->number);
    }

    /**
     * @return array<string, string>
     */
    protected function searchSellables(string $search): array
    {
        $term = trim($search);
        if ($term === '') {
            return [];
        }

        $results = [];

        Product::query()
            ->where('is_available', true)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(12)
            ->get()
            ->each(function (Product $p) use (&$results) {
                $price = number_format((float) $p->effectivePrice(), 2);
                $results["product:{$p->id}"] = "منتج · {$p->name} · {$price} د.ل";
            });

        Collection::query()
            ->where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->each(function (Collection $c) use (&$results) {
                $price = number_format((float) $c->price, 2);
                $results["collection:{$c->id}"] = "باقة · {$c->name} · {$price} د.ل";
            });

        return $results;
    }

    protected function sellableLabel(mixed $value): ?string
    {
        if (! is_string($value) || ! str_contains($value, ':')) {
            return null;
        }

        [$type, $id] = explode(':', $value, 2);

        if ($type === 'product') {
            $p = Product::query()->find($id);

            return $p ? 'منتج · '.$p->name.' · '.number_format((float) $p->effectivePrice(), 2).' د.ل' : null;
        }

        if ($type === 'collection') {
            $c = Collection::query()->find($id);

            return $c ? 'باقة · '.$c->name.' · '.number_format((float) $c->price, 2).' د.ل' : null;
        }

        return null;
    }
}
