import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/models/category_model.dart';
import 'package:sweet_store/services/catalog_service.dart';
import 'package:sweet_store/views/catalog/categories_view.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/category_chip.dart';
import 'package:sweet_store/widgets/product_card.dart';
import 'package:sweet_store/widgets/section_header.dart';
import 'package:sweet_store/widgets/skeleton_box.dart';

class CategoryDetailView extends StatefulWidget {
  const CategoryDetailView({super.key});

  @override
  State<CategoryDetailView> createState() => _CategoryDetailViewState();
}

class _CategoryDetailViewState extends State<CategoryDetailView> {
  final _service = Get.find<CatalogService>();
  final _chipScroll = ScrollController();

  CategoryModel? _category;
  List<CategoryModel> _siblings = [];
  bool _loading = true;
  bool _switching = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  @override
  void dispose() {
    _chipScroll.dispose();
    super.dispose();
  }

  Future<void> _bootstrap() async {
    final arg = Get.arguments;
    CategoryModel? initial;
    List<CategoryModel> siblings = [];

    if (arg is CategoryModel) {
      initial = arg;
    } else if (arg is Map) {
      final cat = arg['category'];
      if (cat is CategoryModel) initial = cat;
      final sib = arg['siblings'];
      if (sib is List) {
        siblings = sib.whereType<CategoryModel>().toList();
      }
    }

    final id = int.tryParse(Get.parameters['id'] ?? '') ?? initial?.id;
    if (id == null) {
      setState(() {
        _error = 'قسم غير موجود';
        _loading = false;
      });
      return;
    }

    if (siblings.isEmpty) {
      try {
        siblings = await _service.fetchCategories();
      } catch (_) {}
    }

    setState(() {
      _siblings = siblings;
      if (initial != null) _category = initial;
    });

    await _loadCategory(id, showFullSkeleton: initial == null);
    WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToSelected());
  }

  Future<void> _loadCategory(int id, {bool showFullSkeleton = false}) async {
    setState(() {
      if (showFullSkeleton) _loading = true;
      _switching = !showFullSkeleton;
      _error = null;
    });

    try {
      final detail = await _service.fetchCategory(id);
      if (!mounted) return;
      setState(() {
        _category = detail;
        _loading = false;
        _switching = false;
      });
      _scrollToSelected();
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
        _switching = false;
      });
    }
  }

  void _scrollToSelected() {
    if (!_chipScroll.hasClients || _category == null || _siblings.isEmpty) {
      return;
    }
    final index = _siblings.indexWhere((c) => c.id == _category!.id);
    if (index < 0) return;
    const itemWidth = 94.0;
    final target = (index * itemWidth) -
        (_chipScroll.position.viewportDimension / 2) +
        (itemWidth / 2);
    _chipScroll.animateTo(
      target.clamp(0.0, _chipScroll.position.maxScrollExtent),
      duration: const Duration(milliseconds: 280),
      curve: Curves.easeOutCubic,
    );
  }

  Future<void> _selectSibling(CategoryModel cat) async {
    if (_category?.id == cat.id || _switching) return;
    // تنقل سلس داخل نفس الشاشة بدون تكديس routes
    setState(() => _category = cat);
    await _loadCategory(cat.id);
  }

  void _openChild(CategoryModel child) {
    Get.toNamed(
      AppRoutes.categoryDetail.replaceFirst(':id', '${child.id}'),
      arguments: {
        'category': child,
        'siblings': child.children.isNotEmpty
            ? child.children
            : (_category?.children ?? []),
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final cat = _category;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        elevation: 0,
        leading: const AppBackButton(),
        title: Text(cat?.name ?? 'القسم'),
      ),
      body: _loading && cat == null
          ? const _CategoryDetailSkeleton()
          : _error != null && cat == null
              ? Center(child: Text(_error!))
              : Column(
                  children: [
                    if (_siblings.isNotEmpty)
                      SizedBox(
                        height: 108,
                        child: ListView.separated(
                          controller: _chipScroll,
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
                          scrollDirection: Axis.horizontal,
                          itemCount: _siblings.length,
                          separatorBuilder: (context, index) =>
                              const SizedBox(width: 10),
                          itemBuilder: (context, i) {
                            final item = _siblings[i];
                            return CategoryChip(
                              category: item,
                              selected: item.id == cat?.id,
                              color: CategoriesView
                                  .pastel[i % CategoriesView.pastel.length],
                              onTap: () => _selectSibling(item),
                            );
                          },
                        ),
                      ),
                    Expanded(
                      child: AnimatedSwitcher(
                        duration: const Duration(milliseconds: 220),
                        child: _switching
                            ? const _ProductsSkeleton(key: ValueKey('sk'))
                            : _CategoryContent(
                                key: ValueKey('cat-${cat!.id}'),
                                category: cat,
                                onOpenChild: _openChild,
                              ),
                      ),
                    ),
                  ],
                ),
    );
  }
}

class _CategoryContent extends StatelessWidget {
  const _CategoryContent({
    super.key,
    required this.category,
    required this.onOpenChild,
  });

  final CategoryModel category;
  final ValueChanged<CategoryModel> onOpenChild;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.only(bottom: 28),
      children: [
        if (category.children.isNotEmpty) ...[
          const SectionHeader(title: 'أقسام فرعية'),
          SizedBox(
            height: 100,
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              scrollDirection: Axis.horizontal,
              itemCount: category.children.length,
              separatorBuilder: (context, index) => const SizedBox(width: 10),
              itemBuilder: (context, i) {
                final child = category.children[i];
                return CategoryChip(
                  category: child,
                  color:
                      CategoriesView.pastel[i % CategoriesView.pastel.length],
                  onTap: () => onOpenChild(child),
                );
              },
            ),
          ),
        ],
        const SectionHeader(title: 'الأصناف'),
        if (category.products.isEmpty)
          const Padding(
            padding: EdgeInsets.all(28),
            child: Center(
              child: Text(
                'لا توجد أصناف في هذا القسم',
                style: TextStyle(color: AppColors.textMuted),
              ),
            ),
          )
        else
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
            child: GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: category.products.length,
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                mainAxisSpacing: 16,
                crossAxisSpacing: 14,
                childAspectRatio: 0.72,
              ),
              itemBuilder: (context, i) {
                final product = category.products[i];
                return ProductCard(
                  product: product,
                  width: null,
                  onTap: () => Get.toNamed(
                    AppRoutes.productDetail.replaceFirst(
                      ':id',
                      '${product.id}',
                    ),
                  ),
                );
              },
            ),
          ),
        ...category.children.expand((child) {
          if (child.products.isEmpty) return <Widget>[];
          return [
            SectionHeader(
              title: child.name,
              onSeeMore: () => onOpenChild(child),
            ),
            SizedBox(
              height: 248,
              child: ListView.separated(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                scrollDirection: Axis.horizontal,
                itemCount: child.products.length,
                separatorBuilder: (context, index) =>
                    const SizedBox(width: 14),
                itemBuilder: (context, i) {
                  final product = child.products[i];
                  return ProductCard(
                    product: product,
                    onTap: () => Get.toNamed(
                      AppRoutes.productDetail.replaceFirst(
                        ':id',
                        '${product.id}',
                      ),
                    ),
                  );
                },
              ),
            ),
          ];
        }),
      ],
    );
  }
}

class _CategoryDetailSkeleton extends StatelessWidget {
  const _CategoryDetailSkeleton();

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        SizedBox(
          height: 100,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: 5,
            separatorBuilder: (context, index) => const SizedBox(width: 10),
            itemBuilder: (context, index) => const Column(
              children: [
                SkeletonBox(width: 68, height: 68, radius: 34),
                SizedBox(height: 8),
                SkeletonBox(width: 48, height: 10, radius: 6),
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),
        const SkeletonBox(height: 22, width: 100, radius: 8),
        const SizedBox(height: 14),
        const _ProductsSkeleton(),
      ],
    );
  }
}

class _ProductsSkeleton extends StatelessWidget {
  const _ProductsSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
      itemCount: 4,
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        mainAxisSpacing: 16,
        crossAxisSpacing: 14,
        childAspectRatio: 0.72,
      ),
      itemBuilder: (context, index) => const Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(child: SkeletonBox(radius: 22)),
          SizedBox(height: 10),
          SkeletonBox(height: 12, width: 90, radius: 6),
          SizedBox(height: 8),
          SkeletonBox(height: 14, width: 56, radius: 6),
        ],
      ),
    );
  }
}
