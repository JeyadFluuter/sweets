import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/cart_controller.dart';
import 'package:sweet_store/controllers/quantity_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/models/cart_model.dart';
import 'package:sweet_store/models/product_model.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/skeleton_box.dart';

class CartView extends StatefulWidget {
  const CartView({super.key});

  @override
  State<CartView> createState() => _CartViewState();
}

class _CartViewState extends State<CartView> {
  final _couponCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Get.find<CartController>().syncFromBackend();
    });
  }

  @override
  void dispose() {
    _couponCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final cart = Get.find<CartController>();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        elevation: 0,
        leading: const AppBackButton(),
        title: const Text('السلة'),
        actions: [
          Obx(() {
            if (cart.cart.value.isEmpty) return const SizedBox.shrink();
            return Padding(
              padding: const EdgeInsetsDirectional.only(end: 12),
              child: Material(
                color: AppColors.coral.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(14),
                child: InkWell(
                  onTap: () => _confirmClear(context, cart),
                  borderRadius: BorderRadius.circular(14),
                  child: const SizedBox(
                    width: 42,
                    height: 42,
                    child: Tooltip(
                      message: 'تفريغ السلة',
                      child: Icon(
                        Icons.delete_forever_rounded,
                        color: AppColors.coral,
                        size: 24,
                      ),
                    ),
                  ),
                ),
              ),
            );
          }),
        ],
      ),
      body: Obx(() {
        if (cart.isLoading.value && cart.cart.value.isEmpty) {
          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: 5,
            itemBuilder: (context, index) => const Padding(
              padding: EdgeInsets.only(bottom: 12),
              child: SkeletonBox(height: 96, radius: 18),
            ),
          );
        }

        if (cart.errorMessage.value != null && cart.cart.value.isEmpty) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(cart.errorMessage.value!),
                  const SizedBox(height: 12),
                  ElevatedButton(
                    onPressed: cart.refreshCart,
                    child: const Text('إعادة المحاولة'),
                  ),
                ],
              ),
            ),
          );
        }

        if (cart.cart.value.isEmpty) {
          return _EmptyCart(
            onBrowse: () {
              if (Navigator.of(context).canPop()) {
                Get.back();
              } else {
                Get.offAllNamed(AppRoutes.main);
              }
            },
          );
        }

        final summary = cart.cart.value;
        return Column(
          children: [
            Expanded(
              child: RefreshIndicator(
                color: AppColors.mint,
                onRefresh: cart.refreshCart,
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
                  children: [
                    const _StoreCard(),
                    const SizedBox(height: 22),
                    Text(
                      'طلبك',
                      style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.w800,
                          ),
                    ),
                    const SizedBox(height: 12),
                    ...summary.items.map(
                      (item) => Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: _CartItemCard(
                          item: item,
                          busy: cart.isMutating.value,
                          onQuantity: (q) => cart.updateQuantity(item.id, q),
                          onRemove: () => cart.removeWithUndo(item.id),
                        ),
                      ),
                    ),
                    Align(
                      alignment: AlignmentDirectional.centerStart,
                      child: TextButton.icon(
                        onPressed: () {
                          if (Navigator.of(context).canPop()) {
                            Get.back();
                          } else {
                            Get.offAllNamed(AppRoutes.main);
                          }
                        },
                        icon: const Icon(Icons.add_rounded, size: 20),
                        label: const Text(
                          'أضف المزيد',
                          style: TextStyle(fontWeight: FontWeight.w800),
                        ),
                        style: TextButton.styleFrom(
                          foregroundColor: AppColors.mintDark,
                          padding: EdgeInsets.zero,
                        ),
                      ),
                    ),
                    if (cart.suggestions.isNotEmpty) ...[
                      const SizedBox(height: 10),
                      Text(
                        'يُطلب كثيراً مع طلبك',
                        style:
                            Theme.of(context).textTheme.titleMedium?.copyWith(
                                  fontWeight: FontWeight.w800,
                                ),
                      ),
                      const SizedBox(height: 12),
                      SizedBox(
                        height: 148,
                        child: ListView.separated(
                          scrollDirection: Axis.horizontal,
                          itemCount: cart.suggestions.length,
                          separatorBuilder: (context, index) =>
                              const SizedBox(width: 12),
                          itemBuilder: (context, i) {
                            final product = cart.suggestions[i];
                            return _SuggestionCard(
                              product: product,
                              onAdd: () async {
                                final ok =
                                    await cart.addProduct(product.id);
                                if (ok) {
                                  Get.snackbar(
                                    'السلة',
                                    'أُضيف ${product.name}',
                                    snackPosition: SnackPosition.BOTTOM,
                                  );
                                }
                              },
                            );
                          },
                        ),
                      ),
                    ],
                    const SizedBox(height: 18),
                    _CouponSection(
                      controller: _couponCtrl,
                      coupon: summary.coupon,
                      discountLabel: summary.displayDiscount,
                      busy: cart.isMutating.value,
                      onApply: () async {
                        final ok = await cart.applyCoupon(_couponCtrl.text);
                        if (ok) _couponCtrl.clear();
                      },
                      onRemove: cart.removeCoupon,
                    ),
                    const SizedBox(height: 14),
                    _TotalsCard(summary: summary),
                    const SizedBox(height: 8),
                  ],
                ),
              ),
            ),
            _CheckoutFooter(
              total: summary.displayTotal,
              subtotal: summary.displaySubtotal,
              discount: summary.discount > 0 ? summary.displayDiscount : null,
              loading: cart.isMutating.value,
              onCheckout: () => Get.toNamed(AppRoutes.checkout),
            ),
          ],
        );
      }),
    );
  }

  Future<void> _confirmClear(
    BuildContext context,
    CartController cart,
  ) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('تفريغ السلة؟'),
        content: const Text('سيتم حذف كل الأصناف من السلة.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('إلغاء'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text(
              'تفريغ',
              style: TextStyle(color: AppColors.coral),
            ),
          ),
        ],
      ),
    );
    if (ok == true) await cart.clearCart();
  }
}

class _StoreCard extends StatelessWidget {
  const _StoreCard();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 12,
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
              color: const Color(0xFFE8F8EF),
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(
              Icons.storefront_rounded,
              color: AppColors.mintDark,
            ),
          ),
          const SizedBox(width: 12),
          const Expanded(
            child: Text(
              'Sweet Store',
              style: TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 15,
              ),
            ),
          ),
          const EnterChevron(),
        ],
      ),
    );
  }
}

class _CartItemCard extends StatelessWidget {
  const _CartItemCard({
    required this.item,
    required this.onQuantity,
    required this.onRemove,
    this.busy = false,
  });

  final CartItemModel item;
  final ValueChanged<int> onQuantity;
  final VoidCallback onRemove;
  final bool busy;

  @override
  Widget build(BuildContext context) {
    return Dismissible(
      key: ValueKey('cart-item-${item.id}'),
      direction: DismissDirection.endToStart,
      confirmDismiss: (_) async {
        onRemove();
        return false;
      },
      background: Container(
        alignment: AlignmentDirectional.centerEnd,
        padding: const EdgeInsetsDirectional.only(end: 20),
        decoration: BoxDecoration(
          color: AppColors.coral.withValues(alpha: 0.12),
          borderRadius: BorderRadius.circular(18),
        ),
        child: const Icon(Icons.delete_outline_rounded, color: AppColors.coral),
      ),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(18),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(14),
              child: Container(
                width: 72,
                height: 72,
                color: item.isCollection
                    ? const Color(0xFFE8F8EF)
                    : const Color(0xFFFFF1E4),
                child: item.imageUrl == null
                    ? Icon(
                        item.isCollection
                            ? Icons.card_giftcard_rounded
                            : Icons.cake_outlined,
                        color: item.isCollection
                            ? AppColors.mint
                            : AppColors.coral,
                      )
                    : Image.network(
                        item.imageUrl!,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) => Icon(
                          item.isCollection
                              ? Icons.card_giftcard_rounded
                              : Icons.cake_outlined,
                          color: item.isCollection
                              ? AppColors.mint
                              : AppColors.coral,
                        ),
                      ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Text(
                          item.name,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 14,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        item.displayLineTotal,
                        style: const TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                  if (item.subtitle != null && item.subtitle!.isNotEmpty) ...[
                    const SizedBox(height: 4),
                    Text(
                      item.subtitle!,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: AppColors.textMuted,
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                  const SizedBox(height: 10),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: Text(
                          item.displayUnitPrice,
                          style: const TextStyle(
                            color: AppColors.mintDark,
                            fontWeight: FontWeight.w700,
                            fontSize: 12,
                          ),
                        ),
                      ),
                      const Spacer(),
                      _CartQtyControl(
                        item: item,
                        enabled: !busy,
                        onQuantity: onQuantity,
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CartQtyControl extends StatefulWidget {
  const _CartQtyControl({
    required this.item,
    required this.onQuantity,
    this.enabled = true,
  });

  final CartItemModel item;
  final ValueChanged<int> onQuantity;
  final bool enabled;

  @override
  State<_CartQtyControl> createState() => _CartQtyControlState();
}

class _CartQtyControlState extends State<_CartQtyControl> {
  late final QuantityController _qty;

  @override
  void initState() {
    super.initState();
    _qty = QuantityController(
      min: widget.item.minOrderQty,
      max: widget.item.maxOrderQty,
      initial: widget.item.quantity,
    );
  }

  @override
  void didUpdateWidget(covariant _CartQtyControl oldWidget) {
    super.didUpdateWidget(oldWidget);
    final item = widget.item;
    if (oldWidget.item.quantity != item.quantity ||
        oldWidget.item.minOrderQty != item.minOrderQty ||
        oldWidget.item.maxOrderQty != item.maxOrderQty) {
      _qty.configure(
        min: item.minOrderQty,
        max: item.maxOrderQty,
        initial: item.quantity,
      );
    }
  }

  void _minus() {
    if (!widget.enabled) return;
    final next = _qty.nextAfterMinus();
    if (next == null) return;
    widget.onQuantity(next);
  }

  void _plus() {
    if (!widget.enabled) return;
    final next = _qty.nextAfterPlus();
    if (next == null) return;
    widget.onQuantity(next);
  }

  Future<void> _edit() async {
    if (!widget.enabled) return;
    final result = await _qty.pickManually(context);
    if (result == null) return;
    widget.onQuantity(result);
  }

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final value = _qty.qty.value;
      final hint = _qty.limitHint.value;
      final atMin = value <= _qty.min;
      final atMax = value >= _qty.max;

      return Column(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Container(
            height: 34,
            decoration: BoxDecoration(
              color: AppColors.fieldFill,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                _qtyBtn(
                  Icons.remove_rounded,
                  widget.enabled ? _minus : null,
                  muted: atMin,
                ),
                InkWell(
                  onTap: widget.enabled ? _edit : null,
                  borderRadius: BorderRadius.circular(10),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 6,
                    ),
                    child: Text(
                      '$value',
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 13,
                        decoration: TextDecoration.underline,
                        decorationColor: AppColors.textHint,
                        decorationStyle: TextDecorationStyle.dotted,
                      ),
                    ),
                  ),
                ),
                _qtyBtn(
                  Icons.add_rounded,
                  widget.enabled ? _plus : null,
                  muted: atMax,
                ),
              ],
            ),
          ),
          if (hint != null) ...[
            const SizedBox(height: 4),
            Text(
              hint,
              style: const TextStyle(
                color: AppColors.coral,
                fontSize: 10,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ],
      );
    });
  }

  Widget _qtyBtn(IconData icon, VoidCallback? onTap, {bool muted = false}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: SizedBox(
        width: 32,
        height: 34,
        child: Icon(
          icon,
          size: 18,
          color: onTap == null || muted
              ? AppColors.textHint
              : AppColors.mintDark,
        ),
      ),
    );
  }
}

class _SuggestionCard extends StatelessWidget {
  const _SuggestionCard({required this.product, required this.onAdd});

  final ProductModel product;
  final VoidCallback onAdd;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 108,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Stack(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: Container(
                  width: 108,
                  height: 96,
                  color: const Color(0xFFFFF1E4),
                  child: product.imageUrl == null
                      ? const Icon(Icons.cake_outlined, color: AppColors.coral)
                      : Image.network(
                          product.imageUrl!,
                          fit: BoxFit.cover,
                          errorBuilder: (context, error, stackTrace) =>
                              const Icon(
                            Icons.cake_outlined,
                            color: AppColors.coral,
                          ),
                        ),
                ),
              ),
              Positioned(
                top: 6,
                left: 6,
                child: Material(
                  color: AppColors.mint,
                  shape: const CircleBorder(),
                  child: InkWell(
                    customBorder: const CircleBorder(),
                    onTap: onAdd,
                    child: const SizedBox(
                      width: 28,
                      height: 28,
                      child: Icon(Icons.add_rounded,
                          size: 18, color: Colors.white),
                    ),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 8),
          Text(
            product.name,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(
              fontWeight: FontWeight.w800,
              fontSize: 12,
            ),
          ),
          Text(
            product.displayPrice,
            style: const TextStyle(
              color: AppColors.textMuted,
              fontWeight: FontWeight.w700,
              fontSize: 11,
            ),
          ),
        ],
      ),
    );
  }
}

class _CouponSection extends StatelessWidget {
  const _CouponSection({
    required this.controller,
    required this.coupon,
    required this.discountLabel,
    required this.onApply,
    required this.onRemove,
    this.busy = false,
  });

  final TextEditingController controller;
  final CartCouponModel? coupon;
  final String discountLabel;
  final VoidCallback onApply;
  final VoidCallback onRemove;
  final bool busy;

  @override
  Widget build(BuildContext context) {
    if (coupon != null) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        decoration: BoxDecoration(
          color: const Color(0xFFE8F8EF),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Row(
          children: [
            const Icon(Icons.local_offer_outlined, color: AppColors.mintDark),
            const SizedBox(width: 10),
            Expanded(
              child: Text(
                'كوبون ${coupon!.code} · خصم $discountLabel',
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
            ),
            TextButton(
              onPressed: busy ? null : onRemove,
              child: const Text('إزالة'),
            ),
          ],
        ),
      );
    }

    return Row(
      children: [
        Expanded(
          child: TextField(
            controller: controller,
            textInputAction: TextInputAction.done,
            onSubmitted: (_) => onApply(),
            decoration: InputDecoration(
              hintText: 'رمز الكوبون',
              prefixIcon: const Icon(Icons.local_offer_outlined),
              filled: true,
              fillColor: AppColors.surface,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: const BorderSide(color: AppColors.border),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: const BorderSide(color: AppColors.border),
              ),
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 12,
                vertical: 12,
              ),
            ),
          ),
        ),
        const SizedBox(width: 10),
        SizedBox(
          height: 48,
          child: ElevatedButton(
            onPressed: busy ? null : onApply,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.mint,
              foregroundColor: Colors.white,
              elevation: 0,
              minimumSize: const Size(0, 48),
              padding: const EdgeInsets.symmetric(horizontal: 16),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14),
              ),
            ),
            child: const Text(
              'تطبيق',
              style: TextStyle(fontWeight: FontWeight.w800),
            ),
          ),
        ),
      ],
    );
  }
}

class _TotalsCard extends StatelessWidget {
  const _TotalsCard({required this.summary});

  final CartModel summary;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          _totalRow('المجموع الفرعي', summary.displaySubtotal),
          if (summary.discount > 0) ...[
            const SizedBox(height: 8),
            _totalRow('الخصم', '- ${summary.displayDiscount}'),
          ],
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 12),
            child: Divider(height: 1),
          ),
          _totalRow('الإجمالي', summary.displayTotal, bold: true),
        ],
      ),
    );
  }

  Widget _totalRow(String label, String value, {bool bold = false}) {
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

class _CheckoutFooter extends StatelessWidget {
  const _CheckoutFooter({
    required this.total,
    required this.subtotal,
    required this.onCheckout,
    this.discount,
    this.loading = false,
  });

  final String total;
  final String subtotal;
  final String? discount;
  final VoidCallback onCheckout;
  final bool loading;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.fromLTRB(
        16,
        14,
        16,
        14 + MediaQuery.paddingOf(context).bottom,
      ),
      decoration: BoxDecoration(
        color: AppColors.surface,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 18,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text(
                  'الإجمالي شامل الخصم',
                  style: TextStyle(
                    color: AppColors.textMuted,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  total,
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 22,
                  ),
                ),
                if (discount != null)
                  Text(
                    'قبل الخصم $subtotal',
                    style: const TextStyle(
                      color: AppColors.textMuted,
                      fontSize: 11,
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          ElevatedButton(
            onPressed: loading ? null : onCheckout,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.mint,
              foregroundColor: Colors.white,
              elevation: 0,
              minimumSize: const Size(0, 50),
              maximumSize: const Size(200, 50),
              padding: const EdgeInsets.symmetric(horizontal: 22),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
            ),
            child: loading
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.2,
                      color: Colors.white,
                    ),
                  )
                : const Text(
                    'إتمام الطلب',
                    style: TextStyle(fontWeight: FontWeight.w800),
                  ),
          ),
        ],
      ),
    );
  }
}

class _EmptyCart extends StatelessWidget {
  const _EmptyCart({required this.onBrowse});

  final VoidCallback onBrowse;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 120,
              height: 120,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    AppColors.mint.withValues(alpha: 0.28),
                    AppColors.mint.withValues(alpha: 0.05),
                  ],
                ),
              ),
              child: const Icon(
                Icons.shopping_bag_outlined,
                size: 54,
                color: AppColors.mintDark,
              ),
            ),
            const SizedBox(height: 22),
            const Text(
              'سلتك فارغة',
              style: TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 22,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'ما زالت فارغة. أضف حلويات أو باقات وستظهر هنا مباشرة.',
              textAlign: TextAlign.center,
              style: TextStyle(
                color: AppColors.textMuted,
                height: 1.45,
              ),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              height: 52,
              child: ElevatedButton(
                onPressed: onBrowse,
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.mint,
                  foregroundColor: Colors.white,
                  elevation: 0,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                ),
                child: const Text(
                  'تصفح الحلويات',
                  style: TextStyle(fontWeight: FontWeight.w800),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
