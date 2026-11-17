import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/network/api_exception.dart';
import 'package:sweet_store/models/cart_model.dart';
import 'package:sweet_store/models/order_model.dart';
import 'package:sweet_store/services/order_service.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/app_button.dart';

class PaymentView extends StatefulWidget {
  const PaymentView({super.key});

  @override
  State<PaymentView> createState() => _PaymentViewState();
}

class _PaymentViewState extends State<PaymentView> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _card = TextEditingController();
  final _expiry = TextEditingController();
  final _cvv = TextEditingController();

  OrderModel? _order;
  int _plan = 50;
  bool _busy = false;
  bool _loading = false;
  bool _done = false;
  String? _error;
  PaymentModel? _payment;
  OrderModel? _updatedOrder;

  OrderModel get order => _order!;

  @override
  void initState() {
    super.initState();
    final arg = Get.arguments;
    if (arg is OrderModel) {
      _order = arg;
    } else {
      _bootstrap();
    }
  }

  Future<void> _bootstrap() async {
    final id = int.tryParse(Get.parameters['id'] ?? '');
    if (id == null) {
      setState(() => _error = 'طلب غير صالح');
      return;
    }
    setState(() => _loading = true);
    try {
      final order = await Get.find<OrderService>().fetchOrder(id);
      if (!mounted) return;
      setState(() {
        _order = order;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _error = 'تعذّر تحميل الطلب';
        _loading = false;
      });
    }
  }

  @override
  void dispose() {
    _name.dispose();
    _card.dispose();
    _expiry.dispose();
    _cvv.dispose();
    super.dispose();
  }

  num get _payAmount => order.total * _plan / 100;

  Future<void> _submit() async {
    if (_order == null) {
      Get.snackbar('الدفع', 'طلب غير صالح', snackPosition: SnackPosition.BOTTOM);
      return;
    }
    if (!(_formKey.currentState?.validate() ?? false)) return;

    setState(() => _busy = true);
    try {
      // بيانات البطاقة واجهة مبدئية فقط — الـ API يستقبل خطة الدفع
      final result = await Get.find<OrderService>().pay(
        orderId: order.id,
        paymentPlan: _plan,
      );
      if (!mounted) return;
      setState(() {
        _payment = result.payment;
        _updatedOrder = result.order;
        _done = true;
        _busy = false;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _busy = false);
      Get.snackbar('الدفع', e.firstError, snackPosition: SnackPosition.BOTTOM);
    } catch (_) {
      if (!mounted) return;
      setState(() => _busy = false);
      Get.snackbar(
        'الدفع',
        'تعذّر إتمام الدفع',
        snackPosition: SnackPosition.BOTTOM,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        elevation: 0,
        leading: const AppBackButton(),
        title: const Text('إتمام الدفع'),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(child: Text(_error!))
              : _order == null
                  ? const Center(child: Text('طلب غير صالح'))
                  : _done
                      ? _SuccessBody(payment: _payment, order: _updatedOrder)
                      : _formBody(),
    );
  }

  Widget _formBody() {
    return Column(
      children: [
        Expanded(
          child: Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
              children: [
                _OrderMiniHeader(order: order),
                const SizedBox(height: 18),
                Text(
                  'خطة الدفع',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    for (final p in [25, 50, 100]) ...[
                      if (p != 25) const SizedBox(width: 8),
                      Expanded(
                        child: _PlanTile(
                          plan: p,
                          amount: moneyLabel(
                            order.total * p / 100,
                            order.currency,
                          ),
                          selected: _plan == p,
                          onTap: () => setState(() => _plan = p),
                        ),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  _plan == 100
                      ? 'ستدفع القيمة كاملة الآن'
                      : 'ستدفع مقدماً $_plan% والمتبقي عند الاستلام/التأكيد',
                  style: const TextStyle(
                    color: AppColors.textMuted,
                    fontSize: 12,
                  ),
                ),
                const SizedBox(height: 22),
                Text(
                  'بيانات البطاقة',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 6),
                const Text(
                  'واجهة مبدئية للتجربة — لا تُخزَّن بيانات البطاقة على الخادم حالياً.',
                  style: TextStyle(
                    color: AppColors.textMuted,
                    fontSize: 12,
                    height: 1.4,
                  ),
                ),
                const SizedBox(height: 14),
                _CardPreview(
                  name: _name.text.isEmpty ? 'HOLDER NAME' : _name.text,
                  number: _card.text.isEmpty
                      ? '•••• •••• •••• ••••'
                      : _formatPreview(_card.text),
                  expiry: _expiry.text.isEmpty ? 'MM/YY' : _expiry.text,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _name,
                  textCapitalization: TextCapitalization.characters,
                  onChanged: (_) => setState(() {}),
                  decoration: const InputDecoration(
                    labelText: 'الاسم على البطاقة',
                  ),
                  validator: (v) =>
                      (v == null || v.trim().length < 3) ? 'أدخل الاسم' : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _card,
                  keyboardType: TextInputType.number,
                  onChanged: (_) => setState(() {}),
                  inputFormatters: [
                    FilteringTextInputFormatter.digitsOnly,
                    LengthLimitingTextInputFormatter(16),
                    _CardNumberFormatter(),
                  ],
                  decoration: const InputDecoration(
                    labelText: 'رقم البطاقة',
                    hintText: 'ACCT-000003',
                  ),
                  validator: (v) {
                    final digits = (v ?? '').replaceAll(' ', '');
                    if (digits.length < 16) return 'رقم البطاقة غير مكتمل';
                    return null;
                  },
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: TextFormField(
                        controller: _expiry,
                        keyboardType: TextInputType.number,
                        onChanged: (_) => setState(() {}),
                        inputFormatters: [
                          FilteringTextInputFormatter.digitsOnly,
                          LengthLimitingTextInputFormatter(4),
                          _ExpiryFormatter(),
                        ],
                        decoration: const InputDecoration(
                          labelText: 'الانتهاء',
                          hintText: 'MM/YY',
                        ),
                        validator: (v) {
                          if (v == null || !RegExp(r'^\d{2}/\d{2}$').hasMatch(v)) {
                            return 'MM/YY';
                          }
                          return null;
                        },
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: TextFormField(
                        controller: _cvv,
                        keyboardType: TextInputType.number,
                        obscureText: true,
                        inputFormatters: [
                          FilteringTextInputFormatter.digitsOnly,
                          LengthLimitingTextInputFormatter(4),
                        ],
                        decoration: const InputDecoration(
                          labelText: 'CVV',
                          hintText: '123',
                        ),
                        validator: (v) {
                          if (v == null || v.length < 3) return 'CVV';
                          return null;
                        },
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
        SafeArea(
          top: false,
          child: Container(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
            decoration: BoxDecoration(
              color: AppColors.surface,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.06),
                  blurRadius: 16,
                  offset: const Offset(0, -4),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    const Text(
                      'المبلغ الآن',
                      style: TextStyle(
                        color: AppColors.textMuted,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const Spacer(),
                    Text(
                      moneyLabel(_payAmount, order.currency),
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 20,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                AppButton(
                  label: 'ادفع الآن',
                  loading: _busy,
                  onPressed: _submit,
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  String _formatPreview(String raw) {
    final digits = raw.replaceAll(' ', '');
    final buf = StringBuffer();
    for (var i = 0; i < digits.length; i++) {
      if (i > 0 && i % 4 == 0) buf.write(' ');
      buf.write(digits[i]);
    }
    return buf.toString();
  }
}

class _OrderMiniHeader extends StatelessWidget {
  const _OrderMiniHeader({required this.order});

  final OrderModel order;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        children: [
          Container(
            width: 46,
            height: 46,
            decoration: BoxDecoration(
              color: const Color(0xFFE8F8EF),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(
              Icons.receipt_long_rounded,
              color: AppColors.mintDark,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  order.number ?? '#${order.id}',
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
                Text(
                  'الإجمالي ${order.displayTotal}',
                  style: const TextStyle(
                    color: AppColors.textMuted,
                    fontSize: 13,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _PlanTile extends StatelessWidget {
  const _PlanTile({
    required this.plan,
    required this.amount,
    required this.selected,
    required this.onTap,
  });

  final int plan;
  final String amount;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final title = plan == 100 ? 'كامل' : '$plan%';
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Ink(
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 8),
        decoration: BoxDecoration(
          color: selected ? const Color(0xFFE8F8EF) : AppColors.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: selected ? AppColors.mint : AppColors.border,
            width: selected ? 1.6 : 1,
          ),
        ),
        child: Column(
          children: [
            Text(
              title,
              style: TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 16,
                color: selected ? AppColors.mintDark : AppColors.textDark,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              amount,
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: AppColors.textMuted,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CardPreview extends StatelessWidget {
  const _CardPreview({
    required this.name,
    required this.number,
    required this.expiry,
  });

  final String name;
  final String number;
  final String expiry;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 170,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(22),
        gradient: const LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: [Color(0xFF2F5D50), Color(0xFF76C893)],
        ),
        boxShadow: [
          BoxShadow(
            color: AppColors.mint.withValues(alpha: 0.35),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.credit_card_rounded, color: Colors.white70),
              Spacer(),
              Text(
                'Sweet Pay',
                style: TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
          const Spacer(),
          Text(
            number,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 20,
              letterSpacing: 1.4,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'CARD HOLDER',
                      style: TextStyle(color: Colors.white60, fontSize: 10),
                    ),
                    Text(
                      name.toUpperCase(),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'EXPIRES',
                    style: TextStyle(color: Colors.white60, fontSize: 10),
                  ),
                  Text(
                    expiry,
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _SuccessBody extends StatelessWidget {
  const _SuccessBody({required this.payment, required this.order});

  final PaymentModel? payment;
  final OrderModel? order;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        children: [
          const Spacer(),
          Container(
            width: 96,
            height: 96,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: AppColors.mint.withValues(alpha: 0.18),
            ),
            child: const Icon(
              Icons.check_rounded,
              size: 48,
              color: AppColors.mintDark,
            ),
          ),
          const SizedBox(height: 18),
          const Text(
            'تم إنشاء طلب الدفع',
            style: TextStyle(fontWeight: FontWeight.w800, fontSize: 20),
          ),
          const SizedBox(height: 8),
          Text(
            [
              if (payment?.reference != null) 'المرجع: ${payment!.reference}',
              if (payment != null) 'المبلغ: ${payment!.displayAmount}',
              if (order?.statusLabel != null) 'حالة الطلب: ${order!.statusLabel}',
            ].join('\n'),
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: AppColors.textMuted,
              height: 1.5,
            ),
          ),
          const Spacer(),
          AppButton(
            label: 'العودة لتفاصيل الطلب',
            onPressed: () => Get.back(result: order),
          ),
        ],
      ),
    );
  }
}

class _CardNumberFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    final digits = newValue.text.replaceAll(' ', '');
    final buf = StringBuffer();
    for (var i = 0; i < digits.length; i++) {
      if (i > 0 && i % 4 == 0) buf.write(' ');
      buf.write(digits[i]);
    }
    final text = buf.toString();
    return TextEditingValue(
      text: text,
      selection: TextSelection.collapsed(offset: text.length),
    );
  }
}

class _ExpiryFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    var digits = newValue.text.replaceAll('/', '');
    if (digits.length > 4) digits = digits.substring(0, 4);
    final text = digits.length >= 3
        ? '${digits.substring(0, 2)}/${digits.substring(2)}'
        : digits;
    return TextEditingValue(
      text: text,
      selection: TextSelection.collapsed(offset: text.length),
    );
  }
}
