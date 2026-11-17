import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/models/product_model.dart';
import 'package:sweet_store/services/catalog_service.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/product_card.dart';
import 'package:sweet_store/widgets/skeleton_box.dart';

class PopularView extends StatefulWidget {
  const PopularView({super.key});

  @override
  State<PopularView> createState() => _PopularViewState();
}

class _PopularViewState extends State<PopularView> {
  final _service = Get.find<CatalogService>();
  List<ProductModel> _items = [];
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
      final items = await _service.fetchPopular();
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
      appBar: AppBar(
        leading: const AppBackButton(),
        title: const Text('الأكثر طلباً'),
      ),
      body: _loading
          ? GridView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: 6,
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                mainAxisSpacing: 16,
                crossAxisSpacing: 14,
                childAspectRatio: 0.72,
              ),
              itemBuilder: (context, index) => const SkeletonBox(radius: 22),
            )
          : _error != null
              ? Center(child: Text(_error!))
              : GridView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _items.length,
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    mainAxisSpacing: 16,
                    crossAxisSpacing: 14,
                    childAspectRatio: 0.72,
                  ),
                  itemBuilder: (context, i) {
                    final product = _items[i];
                    return ProductCard(
                      product: product,
                      width: null,
                      onTap: () => Get.toNamed(
                        AppRoutes.productDetail
                            .replaceFirst(':id', '${product.id}'),
                      ),
                    );
                  },
                ),
    );
  }
}
