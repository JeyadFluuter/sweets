import 'package:sweet_store/core/constants/api_endpoints.dart';
import 'package:sweet_store/core/network/api_client.dart';
import 'package:sweet_store/models/order_model.dart';

class FulfillmentService {
  FulfillmentService(this._api);

  final ApiClient _api;

  Future<({List<String> dates, String? earliestAt})> availableDates(
    String deliveryType,
  ) async {
    final body = await _api.get(
      ApiEndpoints.fulfillmentDates,
      query: {'delivery_type': deliveryType},
    );
    final data = Map<String, dynamic>.from(body['data'] as Map);
    final dates = (data['dates'] as List? ?? [])
        .map((e) => e.toString())
        .where((e) => e.isNotEmpty)
        .toList();
    return (dates: dates, earliestAt: data['earliest_at']?.toString());
  }

  Future<List<FulfillmentSlot>> slots({
    required String deliveryType,
    required String date,
  }) async {
    final body = await _api.get(
      ApiEndpoints.fulfillmentSlots,
      query: {
        'delivery_type': deliveryType,
        'date': date,
      },
    );
    final data = Map<String, dynamic>.from(body['data'] as Map);
    return (data['slots'] as List? ?? [])
        .whereType<Map>()
        .map((e) => FulfillmentSlot.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }
}
