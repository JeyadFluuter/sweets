import 'package:sweet_store/core/constants/api_endpoints.dart';
import 'package:sweet_store/core/network/api_client.dart';
import 'package:sweet_store/models/notification_model.dart';

class NotificationService {
  NotificationService(this._api);

  final ApiClient _api;

  Future<({List<NotificationModel> items, int unreadCount})> list({
    int perPage = 30,
  }) async {
    final body = await _api.get(
      ApiEndpoints.notifications,
      query: {'per_page': perPage},
    );
    final data = body['data'];
    if (data is! Map) {
      return (items: <NotificationModel>[], unreadCount: 0);
    }
    final map = Map<String, dynamic>.from(data);
    final rawItems = map['data'] as List? ?? [];
    final items = rawItems
        .whereType<Map>()
        .map((e) => NotificationModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();
    final unread = map['unread_count'] as int? ??
        items.where((n) => n.isUnread).length;
    return (items: items, unreadCount: unread);
  }

  Future<void> markRead(int id) async {
    await _api.post(ApiEndpoints.notificationRead(id));
  }

  Future<void> delete(int id) async {
    await _api.delete(ApiEndpoints.notification(id));
  }
}
