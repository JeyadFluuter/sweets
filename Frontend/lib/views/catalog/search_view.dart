import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/models/collection_model.dart';
import 'package:sweet_store/models/product_model.dart';
import 'package:sweet_store/services/catalog_service.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/skeleton_box.dart';

class SearchView extends StatefulWidget {
  const SearchView({super.key});

  @override
  State<SearchView> createState() => _SearchViewState();
}

class _SearchViewState extends State<SearchView> {
  final _service = Get.find<CatalogService>();
  late final TextEditingController _query;
  bool _loading = true;
  String? _error;
  List<ProductModel> _products = [];
  List<CollectionModel> _collections = [];

  @override
  void initState() {
    super.initState();
    final initial = (Get.arguments as String?)?.trim() ??
        (Get.parameters['q'] ?? '').trim();
    _query = TextEditingController(text: initial);
    _search(initial);
  }

  @override
  void dispose() {
    _query.dispose();
    super.dispose();
  }

  Future<void> _search(String raw) async {
    final q = raw.trim();
    if (q.isEmpty) {
      setState(() {
        _products = [];
        _collections = [];
        _loading = false;
        _error = null;
      });
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final results = await Future.wait([
        _service.fetchPopular(limit: 50),
        _service.fetchCollections(limit: 50),
      ]);
      final products = results[0] as List<ProductModel>;
      final collections = results[1] as List<CollectionModel>;
      final needle = q.toLowerCase();

      if (!mounted) return;
      setState(() {
        _products = products
            .where((p) => p.name.toLowerCase().contains(needle))
            .toList();
        _collections = collections
            .where((c) => c.name.toLowerCase().contains(needle))
            .toList();
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
        title: TextField(
          controller: _query,
          autofocus: _query.text.isEmpty,
          textInputAction: TextInputAction.search,
          onSubmitted: _search,
          decoration: const InputDecoration(
            hintText: 'ابحث عن حلويات أو باقات...',
            border: InputBorder.none,
            enabledBorder: InputBorder.none,
            focusedBorder: InputBorder.none,
            filled: false,
            contentPadding: EdgeInsets.zero,
          ),
        ),
        actions: [
          IconButton(
            onPressed: () => _search(_query.text),
            icon: const Icon(Icons.search_rounded),
          ),
        ],
      ),
      body: _loading
          ? ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: 6,
              itemBuilder: (context, index) => const Padding(
                padding: EdgeInsets.only(bottom: 10),
                child: SkeletonBox(height: 72, radius: 14),
              ),
            )
          : _error != null
              ? Center(child: Text(_error!))
              : (_products.isEmpty && _collections.isEmpty)
                  ? Center(
                      child: Text(
                        _query.text.trim().isEmpty
                            ? 'اكتب اسم حلوى أو باقة للبحث'
                            : 'لا توجد نتائج',
                      ),
                    )
                  : ListView(
                      padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
                      children: [
                        if (_products.isNotEmpty) ...[
                          Text(
                            'أصناف',
                            style: Theme.of(context)
                                .textTheme
                                .titleMedium
                                ?.copyWith(fontWeight: FontWeight.w800),
                          ),
                          const SizedBox(height: 8),
                          ..._products.map(
                            (p) => ListTile(
                              onTap: () => Get.toNamed(
                                AppRoutes.productDetail
                                    .replaceFirst(':id', '${p.id}'),
                              ),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(14),
                              ),
                              tileColor: AppColors.surface,
                              title: Text(
                                p.name,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              subtitle: Text(p.displayPrice),
                              trailing: const EnterChevron(),
                            ),
                          ),
                          const SizedBox(height: 16),
                        ],
                        if (_collections.isNotEmpty) ...[
                          Text(
                            'باقات',
                            style: Theme.of(context)
                                .textTheme
                                .titleMedium
                                ?.copyWith(fontWeight: FontWeight.w800),
                          ),
                          const SizedBox(height: 8),
                          ..._collections.map(
                            (c) => ListTile(
                              onTap: () => Get.toNamed(
                                AppRoutes.collectionDetail
                                    .replaceFirst(':id', '${c.id}'),
                              ),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(14),
                              ),
                              tileColor: AppColors.surface,
                              title: Text(
                                c.name,
                                style: const TextStyle(
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                              subtitle: Text(c.displayPrice),
                              trailing: const EnterChevron(),
                            ),
                          ),
                        ],
                      ],
                    ),
    );
  }
}
