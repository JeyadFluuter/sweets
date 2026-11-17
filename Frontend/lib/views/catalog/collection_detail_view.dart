import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/cart_controller.dart';
import 'package:sweet_store/controllers/quantity_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/models/collection_model.dart';
import 'package:sweet_store/services/cart_service.dart';
import 'package:sweet_store/services/catalog_service.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/quantity_stepper.dart';
import 'package:sweet_store/widgets/skeleton_box.dart';

/// تفاصيل الباقة كوحدة كاملة — بدون عرض المحتويات الداخلية
class CollectionDetailView extends StatefulWidget {
  const CollectionDetailView({super.key});

  @override
  State<CollectionDetailView> createState() => _CollectionDetailViewState();
}

class _CollectionDetailViewState extends State<CollectionDetailView> {
  final _service = Get.find<CatalogService>();
  final _quantity = QuantityController();
  CollectionModel? _collection;
  bool _loading = true;
  String? _error;
  bool _adding = false;

  num get _total {
    final unit = _collection?.price ?? 0;
    return unit * _quantity.qty.value;
  }

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<CartController>()) {
      Get.put(
        CartController(Get.find<CartService>(), Get.find<CatalogService>()),
        permanent: true,
      );
    }
    _load();
  }

  Future<void> _load() async {
    final id = int.tryParse(Get.parameters['id'] ?? '');
    if (id == null) {
      setState(() {
        _error = 'باقة غير موجودة';
        _loading = false;
      });
      return;
    }
    try {
      final item = await _service.fetchCollection(id);
      if (!mounted) return;
      setState(() {
        _collection = item;
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

  Future<void> _addToCart() async {
    final collection = _collection;
    if (collection == null) return;
    setState(() => _adding = true);
    final ok = await Get.find<CartController>().addCollection(
      collection.id,
      quantity: _quantity.qty.value,
    );
    if (!mounted) return;
    setState(() => _adding = false);
    if (ok) {
      Get.snackbar(
        'تمت الإضافة',
        'أُضيفت ${collection.name} إلى السلة',
        snackPosition: SnackPosition.BOTTOM,
        backgroundColor: AppColors.mint,
        colorText: Colors.white,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(
        body: SafeArea(
          child: Padding(
            padding: EdgeInsets.all(20),
            child: Column(
              children: [
                SkeletonBox(height: 280, radius: 24),
                SizedBox(height: 20),
                SkeletonBox(height: 28, radius: 10),
                SizedBox(height: 12),
                SkeletonBox(height: 80, radius: 12),
              ],
            ),
          ),
        ),
      );
    }

    if (_error != null || _collection == null) {
      return Scaffold(
        appBar: AppBar(leading: const AppBackButton()),
        body: Center(child: Text(_error ?? 'غير متاحة')),
      );
    }

    final c = _collection!;
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        leading: const AppBackButton(),
        title: const Text('الباقة'),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 120),
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(24),
            child: AspectRatio(
              aspectRatio: 1.15,
              child: ColoredBox(
                color: const Color(0xFFFFF3E8),
                child: c.imageUrl == null
                    ? const Icon(
                        Icons.card_giftcard_rounded,
                        size: 72,
                        color: AppColors.coral,
                      )
                    : Image.network(
                        c.imageUrl!,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) =>
                            const Icon(
                          Icons.card_giftcard_rounded,
                          size: 72,
                          color: AppColors.coral,
                        ),
                      ),
              ),
            ),
          ),
          const SizedBox(height: 18),
          Text(
            c.name,
            style: const TextStyle(
              fontWeight: FontWeight.w800,
              fontSize: 24,
              color: AppColors.textDark,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            c.displayPrice,
            style: const TextStyle(
              color: AppColors.mintDark,
              fontWeight: FontWeight.w800,
              fontSize: 22,
            ),
          ),
          if (c.description != null && c.description!.trim().isNotEmpty) ...[
            const SizedBox(height: 18),
            const Text(
              'عن الباقة',
              style: TextStyle(
                fontWeight: FontWeight.w800,
                fontSize: 16,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              c.description!,
              style: const TextStyle(
                color: AppColors.textMuted,
                height: 1.55,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ],
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
          child: Row(
            children: [
              QuantityStepper(controller: _quantity),
              const SizedBox(width: 12),
              Expanded(
                child: FilledButton(
                  onPressed: _adding ? null : _addToCart,
                  style: FilledButton.styleFrom(
                    backgroundColor: AppColors.mint,
                    foregroundColor: Colors.white,
                    minimumSize: const Size.fromHeight(52),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(28),
                    ),
                  ),
                  child: _adding
                      ? const SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(
                            strokeWidth: 2.4,
                            color: Colors.white,
                          ),
                        )
                      : Obx(() {
                          final total = _total;
                          return Text(
                            'أضف للسلة · ${total.toStringAsFixed(total % 1 == 0 ? 0 : 2)} د.ل',
                            style: const TextStyle(fontWeight: FontWeight.w800),
                          );
                        }),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
