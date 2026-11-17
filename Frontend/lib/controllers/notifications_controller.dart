import 'package:get/get.dart';
import 'package:sweet_store/controllers/home_controller.dart';
import 'package:sweet_store/core/network/api_exception.dart';
import 'package:sweet_store/models/notification_model.dart';
import 'package:sweet_store/services/notification_service.dart';

class NotificationsController extends GetxController {
  NotificationsController(this._service);

  final NotificationService _service;

  final items = <NotificationModel>[].obs;
  final unreadCount = 0.obs;
  final isLoading = true.obs;
  final errorMessage = RxnString();

  @override
  void onInit() {
    super.onInit();
    load();
  }

  Future<void> load() async {
    isLoading.value = true;
    errorMessage.value = null;
    try {
      final result = await _service.list();
      items.assignAll(result.items);
      unreadCount.value = result.unreadCount;
      _syncHomeBadge();
    } on ApiException catch (e) {
      errorMessage.value = e.message;
    } catch (_) {
      errorMessage.value = 'تعذّر تحميل الإشعارات';
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> markRead(NotificationModel item) async {
    if (!item.isUnread) return;
    try {
      await _service.markRead(item.id);
      await load();
    } catch (_) {}
  }

  Future<void> remove(NotificationModel item) async {
    try {
      await _service.delete(item.id);
      items.removeWhere((e) => e.id == item.id);
      unreadCount.value = items.where((e) => e.isUnread).length;
      _syncHomeBadge();
    } catch (_) {}
  }

  void _syncHomeBadge() {
    if (Get.isRegistered<HomeController>()) {
      Get.find<HomeController>().unreadNotifications.value = unreadCount.value;
    }
  }
}
