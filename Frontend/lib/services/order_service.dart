import 'package:sweet_store/core/constants/api_endpoints.dart';
import 'package:sweet_store/core/network/api_client.dart';
import 'package:sweet_store/models/order_model.dart';

class OrderService {
  OrderService(this._api);

  final ApiClient _api;

  Future<List<OrderModel>> fetchOrders({int perPage = 20}) async {
    final body = await _api.get(
      ApiEndpoints.orders,
      query: {'per_page': perPage},
    );
    final data = body['data'];
    final list = data is Map
        ? (data['data'] as List? ?? [])
        : (data as List? ?? []);
    return list
        .whereType<Map>()
        .map((e) => OrderModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<OrderModel> fetchOrder(int id) async {
    final body = await _api.get(ApiEndpoints.order(id));
    return OrderModel.fromJson(Map<String, dynamic>.from(body['data'] as Map));
  }

  Future<OrderModel> placeOrder({
    required String deliveryType,
    required String fulfillmentDate,
    required String fulfillmentTime,
    String? addressText,
    num? lat,
    num? lng,
    bool recipientIsSelf = true,
    String? recipientName,
    String? recipientPhone,
    String? customerNote,
  }) async {
    final payload = <String, dynamic>{
      'delivery_type': deliveryType,
      'fulfillment_date': fulfillmentDate,
      'fulfillment_time': fulfillmentTime,
      'recipient_is_self': recipientIsSelf,
      if (addressText != null && addressText.isNotEmpty)
        'address_text': addressText,
      'lat': ?lat,
      'lng': ?lng,
      if (!recipientIsSelf) ...{
        'recipient_name': recipientName,
        'recipient_phone': recipientPhone,
      },
      if (customerNote != null && customerNote.trim().isNotEmpty)
        'customer_note': customerNote.trim(),
    };

    final body = await _api.post(ApiEndpoints.orders, body: payload);
    return OrderModel.fromJson(Map<String, dynamic>.from(body['data'] as Map));
  }

  Future<({PaymentModel payment, OrderModel order})> pay({
    required int orderId,
    required int paymentPlan,
  }) async {
    final body = await _api.post(
      ApiEndpoints.orderPay(orderId),
      body: {'payment_plan': paymentPlan},
    );
    final data = Map<String, dynamic>.from(body['data'] as Map);
    return (
      payment: PaymentModel.fromJson(
        Map<String, dynamic>.from(data['payment'] as Map),
      ),
      order: OrderModel.fromJson(
        Map<String, dynamic>.from(data['order'] as Map),
      ),
    );
  }

  Future<OrderModel> cancel(int orderId) async {
    final body = await _api.post(ApiEndpoints.orderCancel(orderId));
    return OrderModel.fromJson(Map<String, dynamic>.from(body['data'] as Map));
  }
}
