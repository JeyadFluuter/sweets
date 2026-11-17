import 'package:sweet_store/core/constants/api_endpoints.dart';
import 'package:sweet_store/core/network/api_client.dart';
import 'package:sweet_store/models/cart_model.dart';

class CartService {
  CartService(this._api);

  final ApiClient _api;

  Future<CartModel> fetch() async {
    final body = await _api.get(ApiEndpoints.cart);
    return CartModel.fromJson(Map<String, dynamic>.from(body['data'] as Map));
  }

  Future<CartModel> addItem({
    required String itemType,
    required int itemId,
    int quantity = 1,
  }) async {
    final body = await _api.post(
      ApiEndpoints.cartItems,
      body: {
        'item_type': itemType,
        'item_id': itemId,
        'quantity': quantity,
      },
    );
    return CartModel.fromJson(Map<String, dynamic>.from(body['data'] as Map));
  }

  Future<CartModel> updateItem(int itemId, int quantity) async {
    final body = await _api.patch(
      '${ApiEndpoints.cartItems}/$itemId',
      body: {'quantity': quantity},
    );
    return CartModel.fromJson(Map<String, dynamic>.from(body['data'] as Map));
  }

  Future<CartModel> removeItem(int itemId) async {
    final body = await _api.delete('${ApiEndpoints.cartItems}/$itemId');
    return CartModel.fromJson(Map<String, dynamic>.from(body['data'] as Map));
  }

  Future<CartModel> clear() async {
    final body = await _api.delete(ApiEndpoints.cart);
    return CartModel.fromJson(Map<String, dynamic>.from(body['data'] as Map));
  }

  Future<CartModel> applyCoupon(String code) async {
    final body = await _api.post(
      ApiEndpoints.cartCoupon,
      body: {'code': code},
    );
    return CartModel.fromJson(Map<String, dynamic>.from(body['data'] as Map));
  }

  Future<CartModel> removeCoupon() async {
    final body = await _api.delete(ApiEndpoints.cartCoupon);
    return CartModel.fromJson(Map<String, dynamic>.from(body['data'] as Map));
  }
}
