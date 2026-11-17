import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/network/api_exception.dart';
import 'package:sweet_store/models/cart_model.dart';
import 'package:sweet_store/models/product_model.dart';
import 'package:sweet_store/services/cart_service.dart';
import 'package:sweet_store/services/catalog_service.dart';

class CartController extends GetxController {
  CartController(this._service, this._catalog);

  final CartService _service;
  final CatalogService _catalog;

  final cart = CartModel.empty().obs;
  final suggestions = <ProductModel>[].obs;
  final isLoading = false.obs;
  final isMutating = false.obs;
  final suggestionsLoading = false.obs;
  final errorMessage = RxnString();

  int get badgeCount => cart.value.itemsCount;
  bool get hasItems => cart.value.isNotEmpty;

  @override
  void onInit() {
    super.onInit();
    refreshCart();
  }

  /// يُستدعى عند فتح شاشة السلة لضمان مزامنة أحدث بيانات الـ API
  Future<void> syncFromBackend() => refreshCart();

  Future<void> refreshCart() async {
    isLoading.value = true;
    errorMessage.value = null;
    try {
      cart.value = await _service.fetch();
      await loadSuggestions();
    } on ApiException catch (e) {
      errorMessage.value = e.message;
    } catch (_) {
      errorMessage.value = 'تعذّر تحميل السلة';
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> loadSuggestions() async {
    suggestionsLoading.value = true;
    try {
      final popular = await _catalog.fetchPopular(limit: 12);
      final inCart = cart.value.items
          .where((i) => i.isProduct)
          .map((i) => i.itemId)
          .toSet();
      suggestions.assignAll(
        popular.where((p) => !inCart.contains(p.id)).take(8).toList(),
      );
    } catch (_) {
      // الاقتراحات ثانوية
    } finally {
      suggestionsLoading.value = false;
    }
  }

  Future<bool> addProduct(int productId, {int quantity = 1}) async {
    final ok = await _mutate(() async {
      cart.value = await _service.addItem(
        itemType: 'product',
        itemId: productId,
        quantity: quantity,
      );
    });
    if (ok) await loadSuggestions();
    return ok;
  }

  Future<bool> addCollection(int collectionId, {int quantity = 1}) async {
    final ok = await _mutate(() async {
      cart.value = await _service.addItem(
        itemType: 'collection',
        itemId: collectionId,
        quantity: quantity,
      );
    });
    if (ok) await loadSuggestions();
    return ok;
  }

  /// يطابق PATCH /cart/items/{id} — الكمية 0 تحذف السطر في الباك اند
  Future<bool> updateQuantity(int cartItemId, int quantity) async {
    if (quantity <= 0) {
      return removeWithUndo(cartItemId);
    }
    return _mutate(() async {
      cart.value = await _service.updateItem(cartItemId, quantity);
    });
  }

  Future<bool> remove(int cartItemId) async {
    final ok = await _mutate(() async {
      // DELETE /cart/items/{id} — أو PATCH quantity=0 حسب الـ API
      cart.value = await _service.updateItem(cartItemId, 0);
    });
    if (ok) await loadSuggestions();
    return ok;
  }

  Future<bool> removeWithUndo(int cartItemId) async {
    final item = cart.value.items.firstWhereOrNull((e) => e.id == cartItemId);
    if (item == null) return false;

    final ok = await remove(cartItemId);
    if (!ok) return false;

    Get.closeAllSnackbars();
    Get.snackbar(
      'السلة',
      'تم حذف ${item.name}',
      snackPosition: SnackPosition.BOTTOM,
      duration: const Duration(seconds: 4),
      mainButton: TextButton(
        onPressed: () async {
          Get.closeCurrentSnackbar();
          await _mutate(() async {
            cart.value = await _service.addItem(
              itemType: item.itemType,
              itemId: item.itemId,
              quantity: item.quantity,
            );
          });
          await loadSuggestions();
        },
        child: const Text(
          'تراجع',
          style: TextStyle(
            color: AppColors.mint,
            fontWeight: FontWeight.w800,
          ),
        ),
      ),
    );
    return true;
  }

  Future<bool> clearCart() async {
    final ok = await _mutate(() async {
      cart.value = await _service.clear();
    });
    if (ok) await loadSuggestions();
    return ok;
  }

  Future<bool> applyCoupon(String code) async {
    final trimmed = code.trim();
    if (trimmed.isEmpty) {
      Get.snackbar(
        'الكوبون',
        'أدخل رمز الكوبون',
        snackPosition: SnackPosition.BOTTOM,
      );
      return false;
    }
    final ok = await _mutate(() async {
      cart.value = await _service.applyCoupon(trimmed);
    });
    if (ok) {
      Get.snackbar(
        'الكوبون',
        'تم تطبيق الكوبون',
        snackPosition: SnackPosition.BOTTOM,
      );
    }
    return ok;
  }

  Future<bool> removeCoupon() async {
    return _mutate(() async {
      cart.value = await _service.removeCoupon();
    });
  }

  Future<bool> _mutate(Future<void> Function() action) async {
    isMutating.value = true;
    try {
      await action();
      isMutating.value = false;
      return true;
    } on ApiException catch (e) {
      isMutating.value = false;
      Get.snackbar('السلة', e.firstError, snackPosition: SnackPosition.BOTTOM);
      // أعد المزامنة حتى لا تبقى الواجهة بحالة قديمة بعد فشل جزئي
      try {
        cart.value = await _service.fetch();
      } catch (_) {}
      return false;
    } catch (_) {
      isMutating.value = false;
      Get.snackbar(
        'السلة',
        'تعذّرت العملية',
        snackPosition: SnackPosition.BOTTOM,
      );
      try {
        cart.value = await _service.fetch();
      } catch (_) {}
      return false;
    }
  }
}
