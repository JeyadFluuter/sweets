import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/network/api_exception.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/models/order_model.dart';
import 'package:sweet_store/services/order_service.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/skeleton_box.dart';

class OrdersView extends StatefulWidget {
  const OrdersView({super.key});

  @override
  State<OrdersView> createState() => _OrdersViewState();
}

class _OrdersViewState extends State<OrdersView> {
  final _service = Get.find<OrderService>();
  bool _loading = true;
  String? _error;
  List<OrderModel> _orders = [];
  String _filter = 'all';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final orders = await _service.fetchOrders();
      if (!mounted) return;
      setState(() {
        _orders = orders;
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
        _error = 'تعذّر تحميل الطلبات';
        _loading = false;
      });
    }
  }

  List<OrderModel> get _filtered {
    switch (_filter) {
      case 'active':
        return _orders
            .where(
              (o) => const {
                'pending_review',
                'awaiting_payment',
                'confirmed',
                'preparing',
                'ready',
                'out_for_delivery',
              }.contains(o.status),
            )
            .toList();
      case 'done':
        return _orders
            .where(
              (o) => const {
                'completed',
                'cancelled',
                'rejected',
                'expired',
              }.contains(o.status),
            )
            .toList();
      default:
        return _orders;
    }
  }

  @override
  Widget build(BuildContext context) {
    final items = _filtered;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        elevation: 0,
        title: const Text('طلباتي'),
      ),
      body: Column(
        children: [
          SizedBox(
            height: 44,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              children: [
                _FilterChip(
                  label: 'الكل',
                  selected: _filter == 'all',
                  onTap: () => setState(() => _filter = 'all'),
                ),
                const SizedBox(width: 8),
                _FilterChip(
                  label: 'الجارية',
                  selected: _filter == 'active',
                  onTap: () => setState(() => _filter = 'active'),
                ),
                const SizedBox(width: 8),
                _FilterChip(
                  label: 'المنتهية',
                  selected: _filter == 'done',
                  onTap: () => setState(() => _filter = 'done'),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
          Expanded(
            child: _loading
                ? ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: 5,
                    itemBuilder: (context, index) => const Padding(
                      padding: EdgeInsets.only(bottom: 12),
                      child: SkeletonBox(height: 118, radius: 20),
                    ),
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
                    : items.isEmpty
                        ? _EmptyOrders(onRefresh: _load)
                        : RefreshIndicator(
                            color: AppColors.mint,
                            onRefresh: _load,
                            child: ListView.separated(
                              padding:
                                  const EdgeInsets.fromLTRB(16, 4, 16, 110),
                              itemCount: items.length,
                              separatorBuilder: (context, index) =>
                                  const SizedBox(height: 12),
                              itemBuilder: (context, i) {
                                final order = items[i];
                                return _OrderCard(
                                  order: order,
                                  onTap: () => Get.toNamed(
                                    AppRoutes.orderDetail
                                        .replaceFirst(':id', '${order.id}'),
                                  )?.then((_) => _load()),
                                );
                              },
                            ),
                          ),
          ),
        ],
      ),
    );
  }
}

class _FilterChip extends StatelessWidget {
  const _FilterChip({
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ChoiceChip(
      label: Text(label),
      selected: selected,
      onSelected: (_) => onTap(),
      selectedColor: AppColors.mint,
      labelStyle: TextStyle(
        color: selected ? Colors.white : AppColors.textDark,
        fontWeight: FontWeight.w700,
      ),
      backgroundColor: AppColors.surface,
      side: BorderSide(
        color: selected ? AppColors.mint : AppColors.border,
      ),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
    );
  }
}

class _OrderCard extends StatelessWidget {
  const _OrderCard({required this.order, required this.onTap});

  final OrderModel order;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final tone = _statusTone(order.status);

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(22),
        child: Ink(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(22),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.05),
                blurRadius: 16,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      order.number ?? '#${order.id}',
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 16,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 5,
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
                        fontSize: 11,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  const Icon(
                    Icons.event_rounded,
                    size: 16,
                    color: AppColors.textMuted,
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      order.fulfillmentLabel,
                      style: const TextStyle(
                        color: AppColors.textMuted,
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
              Row(
                children: [
                  Icon(
                    order.deliveryType == 'delivery'
                        ? Icons.delivery_dining_rounded
                        : Icons.storefront_rounded,
                    size: 16,
                    color: AppColors.textMuted,
                  ),
                  const SizedBox(width: 6),
                  Text(
                    order.deliveryTypeLabel,
                    style: const TextStyle(
                      color: AppColors.textMuted,
                      fontWeight: FontWeight.w600,
                      fontSize: 13,
                    ),
                  ),
                  const Spacer(),
                  Text(
                    order.displayTotal,
                    style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 16,
                      color: AppColors.mintDark,
                    ),
                  ),
                  const SizedBox(width: 4),
                  const EnterChevron(color: AppColors.textMuted),
                ],
              ),
              if (order.canPay) ...[
                const SizedBox(height: 12),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 10,
                  ),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFF4E5),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: const Row(
                    children: [
                      Icon(
                        Icons.payments_outlined,
                        size: 18,
                        color: Color(0xFFD97706),
                      ),
                      SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          'بانتظار الدفع — اضغط للتفاصيل والدفع',
                          style: TextStyle(
                            fontWeight: FontWeight.w700,
                            fontSize: 12,
                            color: Color(0xFFB45309),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _EmptyOrders extends StatelessWidget {
  const _EmptyOrders({required this.onRefresh});

  final Future<void> Function() onRefresh;

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: onRefresh,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(height: MediaQuery.sizeOf(context).height * 0.18),
          Center(
            child: Padding(
              padding: const EdgeInsets.all(28),
              child: Column(
                children: [
                  Container(
                    width: 96,
                    height: 96,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: AppColors.mint.withValues(alpha: 0.15),
                    ),
                    child: const Icon(
                      Icons.receipt_long_rounded,
                      size: 44,
                      color: AppColors.mintDark,
                    ),
                  ),
                  const SizedBox(height: 18),
                  const Text(
                    'لا توجد طلبات بعد',
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 20,
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'عندما تؤكد طلباً من السلة سيظهر هنا مع حالته وتفاصيله.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: AppColors.textMuted,
                      height: 1.45,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
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
