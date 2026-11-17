import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/models/category_model.dart';
import 'package:sweet_store/models/product_model.dart';
import 'package:sweet_store/services/catalog_service.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/category_chip.dart';
import 'package:sweet_store/widgets/product_card.dart';
import 'package:sweet_store/widgets/skeleton_box.dart';

/// شاشة الأقسام: كل الأصناف أولاً، ثم فلترة بالأب ثم الابن
class CategoriesView extends StatefulWidget {
  const CategoriesView({super.key});

  static const pastel = [
    Color(0xFFFFF3E0),
    Color(0xFFE6F9F0),
    Color(0xFFFFE8EA),
    Color(0xFFEAF2FF),
    Color(0xFFFFF0E8),
  ];

  @override
  State<CategoriesView> createState() => _CategoriesViewState();
}

class _CategoriesViewState extends State<CategoriesView> {
  final _service = Get.find<CatalogService>();
  final _parentScroll = ScrollController();
  final _childScroll = ScrollController();

  List<CategoryModel> _parents = [];
  CategoryModel? _selectedParent; // null = الكل
  CategoryModel? _selectedChild; // null = كل أصناف الأب / الكل
  CategoryModel? _parentDetail; // تفاصيل الأب مع أبنائه ومنتجاته

  List<ProductModel> _allProducts = [];
  List<ProductModel> _products = [];

  bool _loadingCats = true;
  bool _loadingProducts = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  @override
  void dispose() {
    _parentScroll.dispose();
    _childScroll.dispose();
    super.dispose();
  }

  Future<void> _bootstrap() async {
    setState(() {
      _loadingCats = true;
      _loadingProducts = true;
      _error = null;
    });

    try {
      final cached = await _service.readCategoriesCache();
      if (cached != null && cached.isNotEmpty && mounted) {
        setState(() {
          _parents = cached;
          _loadingCats = false;
        });
      }

      final results = await Future.wait([
        _service.fetchCategories(forceNetwork: true),
        _service.fetchPopular(limit: 80),
      ]);

      if (!mounted) return;
      setState(() {
        _parents = results[0] as List<CategoryModel>;
        _allProducts = results[1] as List<ProductModel>;
        _products = List.of(_allProducts);
        _loadingCats = false;
        _loadingProducts = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loadingCats = false;
        _loadingProducts = false;
      });
    }
  }

  Future<void> _selectAll() async {
    setState(() {
      _selectedParent = null;
      _selectedChild = null;
      _parentDetail = null;
      _loadingProducts = true;
    });

    try {
      final products = _allProducts.isNotEmpty
          ? _allProducts
          : await _service.fetchPopular(limit: 80);
      if (!mounted) return;
      setState(() {
        _allProducts = products;
        _products = List.of(products);
        _loadingProducts = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loadingProducts = false);
    }
  }

  Future<void> _selectParent(CategoryModel parent) async {
    if (_selectedParent?.id == parent.id && _selectedChild == null) return;

    setState(() {
      _selectedParent = parent;
      _selectedChild = null;
      _loadingProducts = true;
    });

    try {
      final detail = await _service.fetchCategory(parent.id);
      if (!mounted) return;

      final products = _collectProducts(detail);
      setState(() {
        _parentDetail = detail;
        _products = products;
        _loadingProducts = false;
      });

      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!_childScroll.hasClients) return;
        _childScroll.animateTo(
          0,
          duration: const Duration(milliseconds: 220),
          curve: Curves.easeOut,
        );
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loadingProducts = false;
      });
    }
  }

  Future<void> _selectChild(CategoryModel child) async {
    if (_selectedChild?.id == child.id) {
      // إعادة الضغط تلغي فلتر الابن
      setState(() {
        _selectedChild = null;
        if (_parentDetail != null) {
          _products = _collectProducts(_parentDetail!);
        }
      });
      return;
    }

    setState(() {
      _selectedChild = child;
      _loadingProducts = true;
    });

    try {
      final cachedChild = _parentDetail?.children
          .where((c) => c.id == child.id)
          .firstOrNull;

      List<ProductModel> products;
      if (cachedChild != null && cachedChild.products.isNotEmpty) {
        products = cachedChild.products;
      } else {
        final detail = await _service.fetchCategory(child.id);
        products = detail.products;
        if (_parentDetail != null) {
          final updatedChildren = _parentDetail!.children
              .map((c) => c.id == detail.id ? detail : c)
              .toList();
          _parentDetail = CategoryModel(
            id: _parentDetail!.id,
            name: _parentDetail!.name,
            slug: _parentDetail!.slug,
            parentId: _parentDetail!.parentId,
            imageUrl: _parentDetail!.imageUrl,
            children: updatedChildren,
            products: _parentDetail!.products,
          );
        }
      }

      if (!mounted) return;
      setState(() {
        _products = products;
        _loadingProducts = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loadingProducts = false;
      });
    }
  }

  List<ProductModel> _collectProducts(CategoryModel detail) {
    final map = <int, ProductModel>{};
    for (final p in detail.products) {
      map[p.id] = p;
    }
    for (final child in detail.children) {
      for (final p in child.products) {
        map[p.id] = p;
      }
    }
    return map.values.toList();
  }

  List<CategoryModel> get _children =>
      _parentDetail?.children.isNotEmpty == true
          ? _parentDetail!.children
          : (_selectedParent?.children ?? const []);

  String get _title {
    if (_selectedChild != null) return _selectedChild!.name;
    if (_selectedParent != null) return _selectedParent!.name;
    return 'كل الأصناف';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        elevation: 0,
        leading: const AppBackButton(),
        title: const Text('الأقسام'),
      ),
      body: _loadingCats && _parents.isEmpty
          ? const _FullSkeleton()
          : _error != null && _parents.isEmpty
              ? Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(_error!),
                      const SizedBox(height: 12),
                      ElevatedButton(
                        onPressed: _bootstrap,
                        child: const Text('إعادة المحاولة'),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
                  color: AppColors.mint,
                  onRefresh: _bootstrap,
                  child: CustomScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    slivers: [
                      SliverToBoxAdapter(child: _buildParentsRow()),
                      if (_selectedParent != null && _children.isNotEmpty)
                        SliverToBoxAdapter(child: _buildChildrenRow()),
                      SliverToBoxAdapter(
                        child: Padding(
                          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                          child: Row(
                            children: [
                              Expanded(
                                child: Text(
                                  _title,
                                  style: Theme.of(context)
                                      .textTheme
                                      .titleLarge
                                      ?.copyWith(fontWeight: FontWeight.w800),
                                ),
                              ),
                              Text(
                                _loadingProducts
                                    ? '...'
                                    : '${_products.length} صنف',
                                style: const TextStyle(
                                  color: AppColors.textMuted,
                                  fontWeight: FontWeight.w600,
                                  fontSize: 12,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                      if (_loadingProducts)
                        const SliverToBoxAdapter(child: _ProductsGridSkeleton())
                      else if (_products.isEmpty)
                        const SliverFillRemaining(
                          hasScrollBody: false,
                          child: Center(
                            child: Text(
                              'لا توجد أصناف في هذا القسم',
                              style: TextStyle(color: AppColors.textMuted),
                            ),
                          ),
                        )
                      else
                        SliverPadding(
                          padding: const EdgeInsets.fromLTRB(16, 0, 16, 28),
                          sliver: SliverGrid(
                            gridDelegate:
                                const SliverGridDelegateWithFixedCrossAxisCount(
                              crossAxisCount: 2,
                              mainAxisSpacing: 16,
                              crossAxisSpacing: 14,
                              childAspectRatio: 0.72,
                            ),
                            delegate: SliverChildBuilderDelegate(
                              (context, i) {
                                final product = _products[i];
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
                              childCount: _products.length,
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildParentsRow() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Padding(
          padding: EdgeInsets.fromLTRB(16, 8, 16, 10),
          child: Text(
            'الأقسام',
            style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16),
          ),
        ),
        SizedBox(
          height: 108,
          child: ListView.separated(
            controller: _parentScroll,
            padding: const EdgeInsets.symmetric(horizontal: 16),
            scrollDirection: Axis.horizontal,
            itemCount: _parents.length + 1,
            separatorBuilder: (context, index) => const SizedBox(width: 10),
            itemBuilder: (context, i) {
              if (i == 0) {
                return _AllChip(
                  selected: _selectedParent == null,
                  onTap: _selectAll,
                );
              }
              final cat = _parents[i - 1];
              return CategoryChip(
                category: cat,
                selected: _selectedParent?.id == cat.id,
                color: CategoriesView.pastel[(i - 1) % CategoriesView.pastel.length],
                onTap: () => _selectParent(cat),
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildChildrenRow() {
    return AnimatedSize(
      duration: const Duration(milliseconds: 220),
      curve: Curves.easeOutCubic,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 10),
            child: Row(
              children: [
                const Text(
                  'الأقسام الفرعية',
                  style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
                ),
                const Spacer(),
                if (_selectedChild != null)
                  TextButton(
                    onPressed: () {
                      setState(() {
                        _selectedChild = null;
                        if (_parentDetail != null) {
                          _products = _collectProducts(_parentDetail!);
                        }
                      });
                    },
                    child: const Text('إظهار الكل'),
                  ),
              ],
            ),
          ),
          SizedBox(
            height: 100,
            child: ListView.separated(
              controller: _childScroll,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              scrollDirection: Axis.horizontal,
              itemCount: _children.length,
              separatorBuilder: (context, index) => const SizedBox(width: 10),
              itemBuilder: (context, i) {
                final child = _children[i];
                return CategoryChip(
                  category: child,
                  compact: true,
                  selected: _selectedChild?.id == child.id,
                  color: CategoriesView
                      .pastel[(i + 2) % CategoriesView.pastel.length],
                  onTap: () => _selectChild(child),
                );
              },
            ),
          ),
          const SizedBox(height: 4),
        ],
      ),
    );
  }
}

class _AllChip extends StatelessWidget {
  const _AllChip({required this.selected, required this.onTap});

  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: SizedBox(
        width: 84,
        child: Column(
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 180),
              width: 68,
              height: 68,
              decoration: BoxDecoration(
                color: selected ? AppColors.mint : const Color(0xFFE8F8EF),
                shape: BoxShape.circle,
                border: selected
                    ? Border.all(color: AppColors.mintDark, width: 2.5)
                    : null,
                boxShadow: [
                  BoxShadow(
                    color: AppColors.mint.withValues(alpha: 0.28),
                    blurRadius: 12,
                    offset: const Offset(0, 5),
                  ),
                ],
              ),
              child: Icon(
                Icons.grid_view_rounded,
                color: selected ? Colors.white : AppColors.mintDark,
                size: 28,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              'الكل',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 12,
                color: selected ? AppColors.mintDark : AppColors.textDark,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _FullSkeleton extends StatelessWidget {
  const _FullSkeleton();

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
        const SizedBox(height: 18),
        const SkeletonBox(height: 22, width: 120, radius: 8),
        const SizedBox(height: 14),
        const _ProductsGridSkeleton(),
      ],
    );
  }
}

class _ProductsGridSkeleton extends StatelessWidget {
  const _ProductsGridSkeleton();

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
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
