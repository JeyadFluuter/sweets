import 'dart:async';

import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/cart_controller.dart';
import 'package:sweet_store/controllers/home_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/models/banner_model.dart';
import 'package:sweet_store/services/catalog_service.dart';
import 'package:sweet_store/services/notification_service.dart';
import 'package:sweet_store/widgets/category_chip.dart';
import 'package:sweet_store/widgets/collection_card.dart';
import 'package:sweet_store/widgets/product_card.dart';
import 'package:sweet_store/widgets/section_header.dart';
import 'package:sweet_store/widgets/skeleton_box.dart';

class HomeView extends StatefulWidget {
  const HomeView({super.key});

  static const _pastel = [
    Color(0xFFFFF3E0),
    Color(0xFFE6F9F0),
    Color(0xFFFFE8EA),
    Color(0xFFEAF2FF),
    Color(0xFFFFF0E8),
  ];

  @override
  State<HomeView> createState() => _HomeViewState();
}

class _HomeViewState extends State<HomeView> {
  final _search = TextEditingController();

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  void _goSearch([String? value]) {
    final q = (value ?? _search.text).trim();
    if (q.isEmpty) {
      Get.toNamed(AppRoutes.search);
      return;
    }
    Get.toNamed(AppRoutes.search, arguments: q);
  }

  HomeController get _home {
    if (Get.isRegistered<HomeController>()) {
      return Get.find<HomeController>();
    }
    return Get.put(
      HomeController(
        Get.find<CatalogService>(),
        Get.find<NotificationService>(),
      ),
      permanent: true,
    );
  }

  @override
  Widget build(BuildContext context) {
    final controller = _home;

    return SafeArea(
      child: Obx(() {
        if (controller.isLoading.value &&
            controller.categories.isEmpty &&
            controller.banners.isEmpty) {
          return const HomeSkeleton();
        }

        if (controller.errorMessage.value != null &&
            controller.categories.isEmpty) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(controller.errorMessage.value!),
                  const SizedBox(height: 12),
                  ElevatedButton(
                    onPressed: () => controller.load(force: true),
                    child: const Text('إعادة المحاولة'),
                  ),
                ],
              ),
            ),
          );
        }

        return RefreshIndicator(
          color: AppColors.mint,
          onRefresh: () => controller.load(force: true),
          child: ListView(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.only(bottom: 28),
            children: [
              _TopBar(controller: controller),
              const SizedBox(height: 14),
              _BannerCarousel(banners: controller.banners.toList()),
              const SizedBox(height: 14),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: TextField(
                  controller: _search,
                  readOnly: true,
                  onTap: () => _goSearch(),
                  decoration: InputDecoration(
                    hintText: 'ابحث عن حلويات أو باقات...',
                    prefixIcon: const Icon(Icons.search_rounded),
                    suffixIcon: IconButton(
                      onPressed: () => _goSearch(),
                      icon: const Icon(Icons.search_rounded),
                    ),
                    filled: true,
                    fillColor: AppColors.surface,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(18),
                      borderSide: const BorderSide(color: AppColors.border),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(18),
                      borderSide: const BorderSide(color: AppColors.border),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(18),
                      borderSide: const BorderSide(color: AppColors.mint),
                    ),
                    contentPadding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 14,
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 22),
              SectionHeader(
                title: 'الأقسام',
                onSeeMore: () => Get.toNamed(AppRoutes.categories),
              ),
              SizedBox(
                height: 100,
                child: ListView.separated(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  scrollDirection: Axis.horizontal,
                  itemCount: controller.categories.length,
                  separatorBuilder: (context, index) =>
                      const SizedBox(width: 10),
                  itemBuilder: (context, i) {
                    final cat = controller.categories[i];
                      return CategoryChip(
                        category: cat,
                        color: HomeView._pastel[i % HomeView._pastel.length],
                        onTap: () => Get.toNamed(
                          AppRoutes.categoryDetail
                              .replaceFirst(':id', '${cat.id}'),
                          arguments: {
                            'category': cat,
                            'siblings': controller.categories.toList(),
                          },
                        ),
                      );
                  },
                ),
              ),
              if (controller.collections.isNotEmpty) ...[
                const SizedBox(height: 6),
                SectionHeader(
                  title: 'الباقات',
                  onSeeMore: () => Get.toNamed(AppRoutes.collections),
                ),
                SizedBox(
                  height: 248,
                  child: ListView.separated(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                    scrollDirection: Axis.horizontal,
                    itemCount: controller.collections.length,
                    separatorBuilder: (context, index) =>
                        const SizedBox(width: 14),
                    itemBuilder: (context, i) {
                      final collection = controller.collections[i];
                      return CollectionCard(
                        collection: collection,
                        onTap: () => Get.toNamed(
                          AppRoutes.collectionDetail
                              .replaceFirst(':id', '${collection.id}'),
                        ),
                        onAdd: () async {
                          final ok = await Get.find<CartController>()
                              .addCollection(collection.id);
                          if (ok) {
                            Get.snackbar(
                              'السلة',
                              'أُضيفت الباقة إلى السلة',
                              snackPosition: SnackPosition.BOTTOM,
                            );
                          }
                        },
                      );
                    },
                  ),
                ),
              ],
              const SizedBox(height: 10),
              SectionHeader(
                title: 'الأكثر طلباً',
                onSeeMore: () => Get.toNamed(AppRoutes.popular),
              ),
              SizedBox(
                height: 248,
                child: controller.popularProducts.isEmpty
                    ? const Center(child: Text('لا توجد أصناف بعد'))
                    : ListView.separated(
                        padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                        scrollDirection: Axis.horizontal,
                        itemCount: controller.popularProducts.length,
                        separatorBuilder: (context, index) =>
                            const SizedBox(width: 14),
                        itemBuilder: (context, i) {
                          final product = controller.popularProducts[i];
                          return ProductCard(
                            product: product,
                            onTap: () => Get.toNamed(
                              AppRoutes.productDetail
                                  .replaceFirst(':id', '${product.id}'),
                            ),
                            onAdd: () async {
                              final ok = await Get.find<CartController>()
                                  .addProduct(product.id);
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
              if (controller.offers.isNotEmpty) ...[
                const SizedBox(height: 8),
                SectionHeader(
                  title: 'عروض',
                  onSeeMore: () => Get.toNamed(AppRoutes.popular),
                ),
                SizedBox(
                  height: 248,
                  child: ListView.separated(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                    scrollDirection: Axis.horizontal,
                    itemCount: controller.offers.length,
                    separatorBuilder: (context, index) =>
                        const SizedBox(width: 14),
                    itemBuilder: (context, i) {
                      final product = controller.offers[i];
                      return ProductCard(
                        product: product,
                        onTap: () => Get.toNamed(
                          AppRoutes.productDetail
                              .replaceFirst(':id', '${product.id}'),
                        ),
                        onAdd: () async {
                          final ok = await Get.find<CartController>()
                              .addProduct(product.id);
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
            ],
          ),
        );
      }),
    );
  }
}

class _TopBar extends StatelessWidget {
  const _TopBar({required this.controller});

  final HomeController controller;

  @override
  Widget build(BuildContext context) {
    final cart = Get.find<CartController>();

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 0),
      child: Directionality(
        textDirection: TextDirection.ltr,
        child: Row(
          children: [
            Obx(() {
              final count = controller.unreadNotifications.value;
              return _IconBox(
                onTap: () => Get.toNamed(AppRoutes.notifications),
                child: Badge(
                  isLabelVisible: count > 0,
                  backgroundColor: AppColors.coral,
                  label: Text(
                    count > 99 ? '99+' : '$count',
                    style: const TextStyle(color: Colors.white, fontSize: 10),
                  ),
                  child: const Icon(Icons.notifications_none_rounded),
                ),
              );
            }),
            const Spacer(),
            Obx(() {
              final count = cart.badgeCount;
              return _IconBox(
                onTap: () => Get.toNamed(AppRoutes.cart),
                child: Badge(
                  isLabelVisible: count > 0,
                  backgroundColor: AppColors.coral,
                  label: Text(
                    count > 99 ? '99+' : '$count',
                    style: const TextStyle(color: Colors.white, fontSize: 10),
                  ),
                  child: const Icon(Icons.shopping_cart_outlined),
                ),
              );
            }),
          ],
        ),
      ),
    );
  }
}

class _IconBox extends StatelessWidget {
  const _IconBox({required this.child, required this.onTap});

  final Widget child;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        width: 44,
        height: 44,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.border),
        ),
        child: child,
      ),
    );
  }
}

class _BannerCarousel extends StatefulWidget {
  const _BannerCarousel({required this.banners});

  final List<BannerModel> banners;

  @override
  State<_BannerCarousel> createState() => _BannerCarouselState();
}

class _BannerCarouselState extends State<_BannerCarousel> {
  static const _autoPlayInterval = Duration(seconds: 4);

  final _page = PageController();
  int _index = 0;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _startAutoPlay();
  }

  @override
  void didUpdateWidget(covariant _BannerCarousel oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.banners.length != widget.banners.length) {
      _index = 0;
      _restartAutoPlay();
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    _page.dispose();
    super.dispose();
  }

  void _startAutoPlay() {
    _timer?.cancel();
    if (widget.banners.length < 2) return;
    _timer = Timer.periodic(_autoPlayInterval, (_) => _goNext());
  }

  void _restartAutoPlay() {
    _timer?.cancel();
    _startAutoPlay();
  }

  void _goNext() {
    if (!mounted || !_page.hasClients || widget.banners.length < 2) return;
    final next = (_index + 1) % widget.banners.length;
    _page.animateToPage(
      next,
      duration: const Duration(milliseconds: 420),
      curve: Curves.easeInOut,
    );
  }

  @override
  Widget build(BuildContext context) {
    final banners = widget.banners;
    if (banners.isEmpty) {
      return const Padding(
        padding: EdgeInsets.symmetric(horizontal: 16),
        child: SkeletonBox(height: 168, radius: 22),
      );
    }

    return Column(
      children: [
        SizedBox(
          height: 168,
          child: PageView.builder(
            controller: _page,
            itemCount: banners.length,
            onPageChanged: (i) {
              setState(() => _index = i);
              _restartAutoPlay();
            },
            itemBuilder: (context, i) {
              final banner = banners[i];
              return Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(22),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      _BannerImage(url: banner.imageUrl),
                      if (banner.title != null &&
                          banner.title!.trim().isNotEmpty)
                        DecoratedBox(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                              colors: [
                                Colors.transparent,
                                Colors.black.withValues(alpha: 0.45),
                              ],
                            ),
                          ),
                          child: Align(
                            alignment: Alignment.bottomRight,
                            child: Padding(
                              padding: const EdgeInsets.all(16),
                              child: Text(
                                banner.title!,
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w800,
                                  fontSize: 18,
                                ),
                              ),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              );
            },
          ),
        ),
        const SizedBox(height: 8),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: List.generate(banners.length, (i) {
            final active = i == _index;
            return AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              margin: const EdgeInsets.symmetric(horizontal: 3),
              width: active ? 16 : 7,
              height: 7,
              decoration: BoxDecoration(
                color: active ? AppColors.mint : AppColors.border,
                borderRadius: BorderRadius.circular(8),
              ),
            );
          }),
        ),
      ],
    );
  }
}

class _BannerImage extends StatelessWidget {
  const _BannerImage({this.url});

  final String? url;

  @override
  Widget build(BuildContext context) {
    if (url == null || url!.isEmpty) {
      return const ColoredBox(
        color: Color(0xFFFFF3E0),
        child: Center(
          child: Icon(
            Icons.image_outlined,
            size: 48,
            color: AppColors.coralSoft,
          ),
        ),
      );
    }

    return Image.network(
      url!,
      fit: BoxFit.cover,
      frameBuilder: (context, child, frame, wasSynchronouslyLoaded) {
        if (wasSynchronouslyLoaded || frame != null) return child;
        return const SkeletonBox(height: 168, radius: 0);
      },
      errorBuilder: (context, error, stackTrace) => const ColoredBox(
        color: Color(0xFFFFF3E0),
        child: Center(
          child: Icon(
            Icons.broken_image_outlined,
            size: 48,
            color: AppColors.coralSoft,
          ),
        ),
      ),
    );
  }
}
