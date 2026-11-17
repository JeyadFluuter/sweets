import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/product_detail_controller.dart';
import 'package:sweet_store/controllers/quantity_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/models/product_model.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/skeleton_box.dart';

/// تفاصيل الصنف — تخطيط قريب من واجهة المرجع (هيرو ناعم + كمية + أزرار سفلية)
class ProductDetailView extends GetView<ProductDetailController> {
  const ProductDetailView({super.key});

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      if (controller.loading.value) {
        return const Scaffold(
          backgroundColor: Colors.white,
          body: SafeArea(
            child: Padding(
              padding: EdgeInsets.all(20),
              child: Column(
                children: [
                  SkeletonBox(height: 280, radius: 28),
                  SizedBox(height: 24),
                  SkeletonBox(height: 28, radius: 10),
                  SizedBox(height: 12),
                  SkeletonBox(height: 100, radius: 12),
                ],
              ),
            ),
          ),
        );
      }

      final error = controller.errorMessage.value;
      final product = controller.product.value;
      if (error != null || product == null) {
        return Scaffold(
          appBar: AppBar(leading: const AppBackButton()),
          body: Center(child: Text(error ?? 'غير متاح')),
        );
      }

      return _ProductBody(controller: controller, product: product);
    });
  }
}

class _ProductBody extends StatelessWidget {
  const _ProductBody({
    required this.controller,
    required this.product,
  });

  final ProductDetailController controller;
  final ProductModel product;

  @override
  Widget build(BuildContext context) {
    final images = controller.images;
    final topPad = MediaQuery.paddingOf(context).top;

    return Scaffold(
      backgroundColor: Colors.white,
      body: Column(
        children: [
          Expanded(
            child: CustomScrollView(
              physics: const BouncingScrollPhysics(),
              slivers: [
                SliverToBoxAdapter(
                  child: SizedBox(
                    height: 380 + topPad * 0.15,
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        ColoredBox(
                          color: const Color(0xFFFFF3E8),
                          child: images.isEmpty
                              ? const Center(
                                  child: Icon(
                                    Icons.cake_outlined,
                                    size: 96,
                                    color: AppColors.coralSoft,
                                  ),
                                )
                              : PageView.builder(
                                  controller: controller.pageController,
                                  itemCount: images.length,
                                  onPageChanged: controller.onImageChanged,
                                  itemBuilder: (context, i) {
                                    return Image.network(
                                      images[i],
                                      fit: BoxFit.cover,
                                      width: double.infinity,
                                      height: double.infinity,
                                      errorBuilder:
                                          (context, error, stackTrace) =>
                                              const ColoredBox(
                                                color: Color(0xFFFFF3E8),
                                                child: Center(
                                                  child: Icon(
                                                    Icons.cake_outlined,
                                                    size: 72,
                                                    color: AppColors.coral,
                                                  ),
                                                ),
                                              ),
                                    );
                                  },
                                ),
                        ),
                        Positioned(
                          top: 0,
                          left: 0,
                          right: 0,
                          height: topPad + 70,
                          child: DecoratedBox(
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                begin: Alignment.topCenter,
                                end: Alignment.bottomCenter,
                                colors: [
                                  Colors.black.withValues(alpha: 0.35),
                                  Colors.transparent,
                                ],
                              ),
                            ),
                          ),
                        ),
                        Positioned(
                          left: 0,
                          right: 0,
                          bottom: 0,
                          height: 120,
                          child: DecoratedBox(
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                begin: Alignment.topCenter,
                                end: Alignment.bottomCenter,
                                colors: [
                                  Colors.transparent,
                                  Colors.black.withValues(alpha: 0.15),
                                  Colors.black.withValues(alpha: 0.45),
                                  Colors.black.withValues(alpha: 0.72),
                                ],
                                stops: const [0.0, 0.35, 0.7, 1.0],
                              ),
                            ),
                          ),
                        ),
                        Positioned(
                          top: topPad + 8,
                          left: 16,
                          right: 16,
                          child: const Align(
                            alignment: AlignmentDirectional.centerStart,
                            child: _RoundNav(child: AppBackButton()),
                          ),
                        ),
                        if (images.length > 1)
                          Positioned(
                            left: 0,
                            right: 0,
                            bottom: 36,
                            child: Obx(() {
                              final index = controller.imageIndex.value;
                              return Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: List.generate(images.length, (i) {
                                  final active = i == index;
                                  return AnimatedContainer(
                                    duration: const Duration(milliseconds: 220),
                                    margin: const EdgeInsets.symmetric(
                                      horizontal: 3,
                                    ),
                                    width: active ? 18 : 7,
                                    height: 7,
                                    decoration: BoxDecoration(
                                      color: active
                                          ? Colors.white
                                          : Colors.white.withValues(alpha: 0.45),
                                      borderRadius: BorderRadius.circular(8),
                                      boxShadow: [
                                        BoxShadow(
                                          color: Colors.black.withValues(
                                            alpha: 0.25,
                                          ),
                                          blurRadius: 6,
                                        ),
                                      ],
                                    ),
                                  );
                                }),
                              );
                            }),
                          ),
                      ],
                    ),
                  ),
                ),
                SliverToBoxAdapter(
                  child: Transform.translate(
                    offset: const Offset(0, -28),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.fromLTRB(24, 22, 24, 28),
                      decoration: const BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.vertical(
                          top: Radius.circular(28),
                        ),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Center(
                            child: Container(
                              width: 40,
                              height: 4,
                              margin: const EdgeInsets.only(bottom: 18),
                              decoration: BoxDecoration(
                                color: AppColors.border,
                                borderRadius: BorderRadius.circular(4),
                              ),
                            ),
                          ),
                          Text(
                            product.name,
                            style: const TextStyle(
                              fontWeight: FontWeight.w800,
                              fontSize: 26,
                              height: 1.25,
                              color: AppColors.textDark,
                            ),
                          ),
                          const SizedBox(height: 20),
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Expanded(
                                child: Obx(() {
                                  final qty = controller.quantity.qty.value;
                                  final total = controller.total;
                                  return Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      const Text(
                                        'السعر الإجمالي',
                                        style: TextStyle(
                                          color: AppColors.textMuted,
                                          fontWeight: FontWeight.w600,
                                          fontSize: 13,
                                        ),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        '${total.toStringAsFixed(total % 1 == 0 ? 0 : 2)} د.ل',
                                        style: const TextStyle(
                                          fontWeight: FontWeight.w800,
                                          fontSize: 28,
                                          color: AppColors.textDark,
                                        ),
                                      ),
                                      if (product.hasActiveDiscount &&
                                          product.price != null)
                                        Text(
                                          '${(product.price! * qty).toStringAsFixed(product.price! % 1 == 0 ? 0 : 2)} د.ل',
                                          style: const TextStyle(
                                            color: AppColors.textHint,
                                            decoration:
                                                TextDecoration.lineThrough,
                                            fontWeight: FontWeight.w600,
                                            fontSize: 13,
                                          ),
                                        ),
                                    ],
                                  );
                                }),
                              ),
                              _ProductQtyControl(
                                controller: controller.quantity,
                              ),
                            ],
                          ),
                          if (product.description != null &&
                                  product.description!.trim().isNotEmpty ||
                              product.hasIngredients ||
                              product.hasAllergenAlerts) ...[
                            const SizedBox(height: 22),
                            _ProductInfoBlock(product: product),
                          ],
                          const SizedBox(height: 8),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 14),
              child: Obx(() {
                final busy = controller.adding.value;
                return Row(
                  children: [
                    Expanded(
                      child: SizedBox(
                        height: 56,
                        child: OutlinedButton(
                          onPressed: busy ? null : controller.addToCart,
                          style: OutlinedButton.styleFrom(
                            foregroundColor: AppColors.textDark,
                            side: const BorderSide(color: AppColors.border),
                            backgroundColor: const Color(0xFFF5F5F5),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(28),
                            ),
                          ),
                          child: const Text(
                            'أضف للسلة',
                            style: TextStyle(
                              fontWeight: FontWeight.w800,
                              fontSize: 15,
                            ),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Container(
                        height: 56,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(28),
                          boxShadow: [
                            BoxShadow(
                              color: AppColors.mint.withValues(alpha: 0.45),
                              blurRadius: 18,
                              offset: const Offset(0, 8),
                            ),
                          ],
                        ),
                        child: ElevatedButton(
                          onPressed: busy ? null : controller.buyNow,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.mint,
                            foregroundColor: Colors.white,
                            elevation: 0,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(28),
                            ),
                          ),
                          child: busy
                              ? const SizedBox(
                                  width: 22,
                                  height: 22,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2.2,
                                    color: Colors.white,
                                  ),
                                )
                              : const Text(
                                  'اشترِ الآن',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w800,
                                    fontSize: 15,
                                  ),
                                ),
                        ),
                      ),
                    ),
                  ],
                );
              }),
            ),
          ),
        ],
      ),
    );
  }
}

class _ProductInfoBlock extends StatelessWidget {
  const _ProductInfoBlock({required this.product});

  final ProductModel product;

  @override
  Widget build(BuildContext context) {
    final sections = <({String title, String text})>[];

    final description = product.description?.trim();
    if (description != null && description.isNotEmpty) {
      sections.add((title: 'الوصف', text: description));
    }
    if (product.hasIngredients) {
      sections.add((title: 'المكونات', text: product.ingredients!.trim()));
    }
    if (product.hasAllergenAlerts) {
      sections.add((
        title: 'تنبيهات الحساسية',
        text: product.allergenAlerts!.trim(),
      ));
    }

    if (sections.isEmpty) return const SizedBox.shrink();

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 18),
      decoration: BoxDecoration(
        color: const Color(0xFFF7F8F7),
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          for (var i = 0; i < sections.length; i++) ...[
            if (i > 0) ...[
              const SizedBox(height: 14),
              const Divider(height: 1, color: AppColors.border),
              const SizedBox(height: 14),
            ],
            Text(
              sections[i].title,
              style: const TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 16,
                color: AppColors.textDark,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              sections[i].text,
              style: const TextStyle(
                color: AppColors.textMuted,
                height: 1.7,
                fontSize: 15,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _ProductQtyControl extends StatelessWidget {
  const _ProductQtyControl({required this.controller});

  final QuantityController controller;

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final value = controller.qty.value;
      final hint = controller.limitHint.value;
      final atMin = value <= controller.min;
      final atMax = value >= controller.max;

      return Column(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              _QtyBtn(
                icon: Icons.remove_rounded,
                filled: false,
                muted: atMin,
                onTap: controller.minus,
              ),
              Material(
                color: Colors.transparent,
                child: InkWell(
                  onTap: () => controller.editManually(context),
                  borderRadius: BorderRadius.circular(10),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 8,
                    ),
                    child: Text(
                      '$value',
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 18,
                        decoration: TextDecoration.underline,
                        decorationColor: AppColors.textHint,
                        decorationStyle: TextDecorationStyle.dotted,
                      ),
                    ),
                  ),
                ),
              ),
              _QtyBtn(
                icon: Icons.add_rounded,
                filled: true,
                muted: atMax,
                onTap: controller.plus,
              ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            hint ?? 'اضغط الرقم للكتابة',
            style: TextStyle(
              color: hint == null ? AppColors.textHint : AppColors.coral,
              fontSize: 11,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      );
    });
  }
}

class _RoundNav extends StatelessWidget {
  const _RoundNav({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 44,
      height: 44,
      decoration: BoxDecoration(
        color: Colors.white,
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: child,
    );
  }
}

class _QtyBtn extends StatelessWidget {
  const _QtyBtn({
    required this.icon,
    required this.filled,
    required this.onTap,
    this.muted = false,
  });

  final IconData icon;
  final bool filled;
  final bool muted;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: filled
          ? (muted ? AppColors.mint.withValues(alpha: 0.45) : AppColors.mint)
          : const Color(0xFFF0F0F0),
      shape: const CircleBorder(),
      elevation: filled && !muted ? 4 : 0,
      shadowColor: AppColors.mint.withValues(alpha: 0.45),
      child: InkWell(
        customBorder: const CircleBorder(),
        onTap: onTap,
        child: SizedBox(
          width: 40,
          height: 40,
          child: Icon(
            icon,
            size: 20,
            color: filled
                ? Colors.white
                : (muted ? AppColors.textHint : AppColors.textDark),
          ),
        ),
      ),
    );
  }
}
