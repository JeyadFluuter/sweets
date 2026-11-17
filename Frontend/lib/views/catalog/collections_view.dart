import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/cart_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/models/collection_model.dart';
import 'package:sweet_store/services/catalog_service.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/collection_card.dart';
import 'package:sweet_store/widgets/skeleton_box.dart';

class CollectionsView extends StatefulWidget {
  const CollectionsView({super.key});

  @override
  State<CollectionsView> createState() => _CollectionsViewState();
}

class _CollectionsViewState extends State<CollectionsView> {
  final _service = Get.find<CatalogService>();
  List<CollectionModel> _items = [];
  bool _loading = true;
  String? _error;

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
      final items = await _service.fetchCollections();
      if (!mounted) return;
      setState(() {
        _items = items;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
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
        title: const Text('الباقات'),
      ),
      body: RefreshIndicator(
        color: AppColors.mint,
        onRefresh: _load,
        child: _loading
            ? GridView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: 4,
                gridDelegate: _grid,
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
              )
            : _error != null
                ? ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: [
                      SizedBox(
                        height: MediaQuery.sizeOf(context).height * 0.3,
                      ),
                      Center(child: Text(_error!)),
                    ],
                  )
                : _items.isEmpty
                    ? ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        children: [
                          SizedBox(
                            height: MediaQuery.sizeOf(context).height * 0.3,
                          ),
                          const Center(child: Text('لا توجد باقات بعد')),
                        ],
                      )
                    : GridView.builder(
                        padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
                        itemCount: _items.length,
                        gridDelegate: _grid,
                        itemBuilder: (context, i) {
                          final item = _items[i];
                          return CollectionCard(
                            collection: item,
                            width: null,
                            onTap: () => Get.toNamed(
                              AppRoutes.collectionDetail
                                  .replaceFirst(':id', '${item.id}'),
                            ),
                            onAdd: () async {
                              final ok = await Get.find<CartController>()
                                  .addCollection(item.id);
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
    );
  }

  static const _grid = SliverGridDelegateWithFixedCrossAxisCount(
    crossAxisCount: 2,
    mainAxisSpacing: 16,
    crossAxisSpacing: 14,
    childAspectRatio: 0.72,
  );
}
