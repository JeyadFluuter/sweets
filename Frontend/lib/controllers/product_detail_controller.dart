import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/cart_controller.dart';
import 'package:sweet_store/controllers/quantity_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/models/product_model.dart';
import 'package:sweet_store/services/cart_service.dart';
import 'package:sweet_store/services/catalog_service.dart';

class ProductDetailController extends GetxController {
  ProductDetailController(this._catalog);

  final CatalogService _catalog;

  final loading = true.obs;
  final errorMessage = RxnString();
  final product = Rxn<ProductModel>();
  final imageIndex = 0.obs;
  final adding = false.obs;

  late final QuantityController quantity;
  final pageController = PageController();

  num get unitPrice =>
      product.value?.effectivePrice ?? product.value?.price ?? 0;

  num get total => unitPrice * quantity.qty.value;

  List<String> get images {
    final p = product.value;
    if (p == null) return const [];
    if (p.imageUrls.isNotEmpty) return p.imageUrls;
    if (p.imageUrl != null && p.imageUrl!.isNotEmpty) return [p.imageUrl!];
    return const [];
  }

  @override
  void onInit() {
    super.onInit();
    quantity = QuantityController();
    if (!Get.isRegistered<CartController>()) {
      Get.put(
        CartController(Get.find<CartService>(), Get.find<CatalogService>()),
        permanent: true,
      );
    }
    Future.microtask(load);
  }

  @override
  void onClose() {
    pageController.dispose();
    super.onClose();
  }

  Future<void> load() async {
    final id = int.tryParse(Get.parameters['id'] ?? '');
    if (id == null) {
      errorMessage.value = 'صنف غير موجود';
      loading.value = false;
      return;
    }

    loading.value = true;
    errorMessage.value = null;
    try {
      final item = await _catalog.fetchProduct(id);
      product.value = item;
      quantity.configure(
        min: item.minOrderQty,
        max: item.maxOrderQty,
        initial: item.minOrderQty,
      );
    } catch (e) {
      errorMessage.value = e.toString();
      product.value = null;
    } finally {
      loading.value = false;
    }
  }

  void onImageChanged(int index) => imageIndex.value = index;

  Future<bool> addToCart() async {
    final item = product.value;
    if (item == null) return false;
    adding.value = true;
    final ok = await Get.find<CartController>().addProduct(
      item.id,
      quantity: quantity.qty.value,
    );
    adding.value = false;
    if (ok) {
      Get.snackbar(
        'تمت الإضافة',
        'أُضيف ${item.name} إلى السلة',
        snackPosition: SnackPosition.BOTTOM,
        backgroundColor: AppColors.mint,
        colorText: Colors.white,
      );
    }
    return ok;
  }

  Future<void> buyNow() async {
    final ok = await addToCart();
    if (ok) Get.toNamed(AppRoutes.cart);
  }
}
