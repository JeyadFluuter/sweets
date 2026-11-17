import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/network/api_exception.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/models/cart_model.dart';
import 'package:sweet_store/models/order_model.dart';
import 'package:sweet_store/services/order_service.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/app_button.dart';
import 'package:sweet_store/widgets/skeleton_box.dart';

class OrderDetailView extends StatefulWidget {
  const OrderDetailView({super.key});

  @override
  State<OrderDetailView> createState() => _OrderDetailViewState();
}

class _OrderDetailViewState extends State<OrderDetailView> {
  final _service = Get.find<OrderService>();
  bool _loading = true;
  bool _busy = false;
  String? _error;
  OrderModel? _order;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final id = int.tryParse(Get.parameters['id'] ?? '');
    if (id == null) {
      setState(() {
        _error = 'رقم الطلب غير صالح';
        _loading = false;
      });
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final order = await _service.fetchOrder(id);
      if (!mounted) return;
      setState(() {
        _order = order;
        _loading = false;
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.firstError;
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

  Future<void> _cancel() async {
    final order = _order;
    if (order == null) return;
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('إلغاء الطلب؟'),
        content: const Text('هل تريد إلغاء هذا الطلب؟'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('لا'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text(
              'نعم، إلغاء',
              style: TextStyle(color: AppColors.coral),
            ),
          ),
        ],
      ),
    );
    if (ok != true) return;

    setState(() => _busy = true);
    try {
      final updated = await _service.cancel(order.id);
      if (!mounted) return;
      setState(() {
        _order = updated;
        _busy = false;
      });
      Get.snackbar(
        'الطلب',
        'تم إلغاء الطلب',
        snackPosition: SnackPosition.BOTTOM,
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _busy = false);
      Get.snackbar('الطلب', e.firstError, snackPosition: SnackPosition.BOTTOM);
    } catch (_) {
      if (!mounted) return;
      setState(() => _busy = false);
      Get.snackbar(
        'الطلب',
        'تعذّر الإلغاء',
        snackPosition: SnackPosition.BOTTOM,
      );
    }
  }

  Future<void> _openPayment() async {
    final order = _order;
    if (order == null || !order.canPay) return;
    final result = await Get.toNamed(
      AppRoutes.orderPay.replaceFirst(':id', '${order.id}'),
      arguments: order,
    );
    if (result is OrderModel) {
      setState(() => _order = result);
    } else {
      await _load();
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
        title: Text(_order?.number ?? 'تفاصيل الطلب'),
      ),
      body: _loading
          ? ListView(
              padding: const EdgeInsets.all(16),
              children: const [
                SkeletonBox(height: 120, radius: 20),
                SizedBox(height: 12),
                SkeletonBox(height: 180, radius: 20),
              ],
            )
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(_error!),
                      const SizedBox(height: 12),
                      ElevatedButton(
                        onPressed: _load,
                        child: const Text('إعادة المحاولة'),
                      ),
                    ],
                  ),
                )
              : _buildBody(context, _order!),
    );
  }

  Widget _buildBody(BuildContext context, OrderModel order) {
    final tone = _statusTone(order.status);

    return Column(
      children: [
        Expanded(
          child: RefreshIndicator(
            color: AppColors.mint,
            onRefresh: _load,
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
              children: [
                Container(
                  padding: const EdgeInsets.all(18),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(24),
                    gradient: LinearGradient(
                      begin: Alignment.topRight,
                      end: Alignment.bottomLeft,
                      colors: [
                        tone.fg.withValues(alpha: 0.12),
                        AppColors.surface,
                      ],
                    ),
                    border: Border.all(color: tone.fg.withValues(alpha: 0.18)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 6,
                            ),
                            decoration: BoxDecoration(
                              color: tone.bg,
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              order.statusLabel ?? order.status ?? '—',
                              style: TextStyle(
                                color: tone.fg,
                                fontWeight: FontWeight.w800,
                                fontSize: 12,
                              ),
                            ),
                          ),
                          const Spacer(),
                          Text(
                            order.displayTotal,
                            style: const TextStyle(
                              fontWeight: FontWeight.w800,
                              fontSize: 22,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      _MetaRow(
                        icon: Icons.event_rounded,
                        label: 'الموعد',
                        value: order.fulfillmentLabel,
                      ),
                      const SizedBox(height: 10),
                      _MetaRow(
                        icon: order.deliveryType == 'delivery'
                            ? Icons.delivery_dining_rounded
                            : Icons.storefront_rounded,
                        label: 'الاستلام',
                        value: order.deliveryTypeLabel,
                      ),
                      if (order.addressText != null &&
                          order.addressText!.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        _MetaRow(
                          icon: Icons.place_outlined,
                          label: 'العنوان',
                          value: order.addressText!,
                        ),
                      ],
                      if (!order.recipientIsSelf &&
                          order.recipientName != null) ...[
                        const SizedBox(height: 10),
                        _MetaRow(
                          icon: Icons.person_outline_rounded,
                          label: 'المستلم',
                          value:
                              '${order.recipientName} · ${order.recipientPhone ?? ''}',
                        ),
                      ],
                      if (order.rejectionReason != null) ...[
                        const SizedBox(height: 12),
                        Text(
                          'سبب الرفض: ${order.rejectionReason}',
                          style: const TextStyle(
                            color: AppColors.coral,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                const SizedBox(height: 18),
                Text(
                  'الأصناف',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                ),
                const SizedBox(height: 10),
                ...order.items.map(
                  (item) => Container(
                    margin: const EdgeInsets.only(bottom: 10),
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: AppColors.surface,
                      borderRadius: BorderRadius.circular(18),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.03),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        Container(
                          width: 48,
                          height: 48,
                          decoration: BoxDecoration(
                            color: const Color(0xFFFFF1E4),
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: const Icon(
                            Icons.cake_outlined,
                            color: AppColors.coral,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                item.name,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                '×${item.quantity} · ${moneyLabel(item.unitPrice, order.currency)}',
                                style: const TextStyle(
                                  color: AppColors.textMuted,
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ),
                        Text(
                          moneyLabel(item.lineTotal, order.currency),
                          style: const TextStyle(fontWeight: FontWeight.w800),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: Column(
                    children: [
                      _moneyRow('الفرعي', order.displaySubtotal),
                      if (order.discount > 0) ...[
                        const SizedBox(height: 8),
                        _moneyRow('الخصم', '- ${order.displayDiscount}'),
                      ],
                      if (order.deliveryFee > 0) ...[
                        const SizedBox(height: 8),
                        _moneyRow('التوصيل', order.displayDeliveryFee),
                      ],
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 12),
                        child: Divider(height: 1),
                      ),
                      _moneyRow('الإجمالي', order.displayTotal, bold: true),
                      if (order.paidAmount > 0) ...[
                        const SizedBox(height: 8),
                        _moneyRow(
                          'المدفوع',
                          moneyLabel(order.paidAmount, order.currency),
                        ),
                      ],
                      if (order.remainingAmount > 0) ...[
                        const SizedBox(height: 8),
                        _moneyRow('المتبقي', order.displayRemaining),
                      ],
                    ],
                  ),
                ),
                if (order.customerNote != null &&
                    order.customerNote!.isNotEmpty) ...[
                  const SizedBox(height: 14),
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: AppColors.fieldFill,
                      borderRadius: BorderRadius.circular(16),
                    ),
                    child: Text(
                      'ملاحظة: ${order.customerNote}',
                      style: const TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
                if (order.timeline.isNotEmpty) ...[
                  const SizedBox(height: 18),
                  Text(
                    'مسار الطلب',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w800,
                        ),
                  ),
                  const SizedBox(height: 10),
                  ...order.timeline.asMap().entries.map((entry) {
                    final i = entry.key;
                    final e = entry.value;
                    final last = i == order.timeline.length - 1;
                    return Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Column(
                          children: [
                            Container(
                              width: 14,
                              height: 14,
                              decoration: BoxDecoration(
                                color: last
                                    ? AppColors.mint
                                    : AppColors.mint.withValues(alpha: 0.35),
                                shape: BoxShape.circle,
                              ),
                            ),
                            if (!last)
                              Container(
                                width: 2,
                                height: 36,
                                color: AppColors.border,
                              ),
                          ],
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Padding(
                            padding: const EdgeInsets.only(bottom: 12),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  e.to ?? '—',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                                if ((e.reason ?? '').isNotEmpty ||
                                    (e.at ?? '').isNotEmpty)
                                  Text(
                                    [e.reason, e.at]
                                        .whereType<String>()
                                        .where((s) => s.isNotEmpty)
                                        .join(' · '),
                                    style: const TextStyle(
                                      color: AppColors.textMuted,
                                      fontSize: 12,
                                    ),
                                  ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    );
                  }),
                ],
              ],
            ),
          ),
        ),
        if (order.canPay || order.canCancel)
          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
              child: Column(
                children: [
                  if (order.canPay)
                    AppButton(
                      label: 'إتمام الدفع',
                      loading: _busy,
                      onPressed: _openPayment,
                    ),
                  if (order.canPay && order.canCancel)
                    const SizedBox(height: 8),
                  if (order.canCancel)
                    SizedBox(
                      width: double.infinity,
                      height: 50,
                      child: OutlinedButton(
                        onPressed: _busy ? null : _cancel,
                        style: OutlinedButton.styleFrom(
                          foregroundColor: AppColors.coral,
                          side: const BorderSide(color: AppColors.coral),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        child: const Text(
                          'إلغاء الطلب',
                          style: TextStyle(fontWeight: FontWeight.w800),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
      ],
    );
  }

  Widget _moneyRow(String label, String value, {bool bold = false}) {
    return Row(
      children: [
        Text(
          label,
          style: TextStyle(
            fontWeight: bold ? FontWeight.w800 : FontWeight.w600,
            color: bold ? AppColors.textDark : AppColors.textMuted,
          ),
        ),
        const Spacer(),
        Text(
          value,
          style: TextStyle(
            fontWeight: FontWeight.w800,
            fontSize: bold ? 16 : 14,
          ),
        ),
      ],
    );
  }
}

class _MetaRow extends StatelessWidget {
  const _MetaRow({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: AppColors.textMuted),
        const SizedBox(width: 8),
        Text(
          '$label: ',
          style: const TextStyle(
            color: AppColors.textMuted,
            fontWeight: FontWeight.w600,
          ),
        ),
        Expanded(
          child: Text(
            value,
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
        ),
      ],
    );
  }
}

({Color bg, Color fg}) _statusTone(String? status) {
  switch (status) {
    case 'awaiting_payment':
      return (bg: const Color(0xFFFFF4E5), fg: const Color(0xFFB45309));
    case 'confirmed':
    case 'preparing':
    case 'ready':
    case 'out_for_delivery':
      return (bg: const Color(0xFFE8F8EF), fg: AppColors.mintDark);
    case 'completed':
      return (bg: const Color(0xFFE8F1FF), fg: const Color(0xFF3B82F6));
    case 'rejected':
    case 'cancelled':
    case 'expired':
      return (bg: const Color(0xFFFFE8E6), fg: AppColors.coral);
    default:
      return (bg: AppColors.fieldFill, fg: AppColors.textMuted);
  }
}
