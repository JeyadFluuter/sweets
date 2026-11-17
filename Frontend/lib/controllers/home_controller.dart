import 'package:get/get.dart';
import 'package:sweet_store/controllers/ai_chat_controller.dart';
import 'package:sweet_store/controllers/ai_gate_controller.dart';
import 'package:sweet_store/controllers/auth_controller.dart';
import 'package:sweet_store/core/network/api_exception.dart';
import 'package:sweet_store/models/banner_model.dart';
import 'package:sweet_store/models/category_model.dart';
import 'package:sweet_store/models/collection_model.dart';
import 'package:sweet_store/models/home_payload_model.dart';
import 'package:sweet_store/models/product_model.dart';
import 'package:sweet_store/services/catalog_service.dart';
import 'package:sweet_store/services/notification_service.dart';

class HomeController extends GetxController {
  HomeController(this._catalog, this._notifications);

  final CatalogService _catalog;
  final NotificationService _notifications;

  final isLoading = true.obs;
  final isRefreshing = false.obs;
  final errorMessage = RxnString();

  final banners = <BannerModel>[].obs;
  final categories = <CategoryModel>[].obs;
  final popularProducts = <ProductModel>[].obs;
  final offers = <ProductModel>[].obs;
  final collections = <CollectionModel>[].obs;
  final unreadNotifications = 0.obs;

  String get greetingName {
    final auth = Get.find<AuthController>();
    return auth.customer.value?.name ?? 'ضيف';
  }

  @override
  void onInit() {
    super.onInit();
    // بعد أول إطار حتى لا نحدّث Obx أثناء البناء
    Future.microtask(() => load());
  }

  Future<void> load({bool force = false}) async {
    if (force) {
      isRefreshing.value = true;
    } else if (banners.isEmpty && categories.isEmpty) {
      isLoading.value = true;
    }
    errorMessage.value = null;

    final cached = await _catalog.readHomeCache();
    if (cached != null && !force) {
      _applyHome(cached);
      isLoading.value = false;
    }

    final cachedCats = await _catalog.readCategoriesCache();
    if (cachedCats != null && cachedCats.isNotEmpty && !force) {
      categories.assignAll(cachedCats);
    }

    try {
      final results = await Future.wait([
        _catalog.fetchHome(forceNetwork: true),
        _catalog.fetchCategories(forceNetwork: true),
        _refreshAiAssistantStatus(),
      ]);
      _applyHome(results[0] as HomePayloadModel);
      categories.assignAll(results[1] as List<CategoryModel>);
      errorMessage.value = null;
      await refreshUnreadCount();
    } on ApiException catch (e) {
      if (banners.isEmpty && categories.isEmpty) {
        errorMessage.value = e.message;
      }
    } catch (e, st) {
      assert(() {
        // ignore: avoid_print
        print('Home load failed: $e\n$st');
        return true;
      }());
      if (banners.isEmpty && categories.isEmpty) {
        errorMessage.value = 'تعذّر تحميل الرئيسية';
      }
    } finally {
      isLoading.value = false;
      isRefreshing.value = false;
    }
  }

  Future<void> refreshUnreadCount() async {
    final auth = Get.find<AuthController>();
    if (!auth.isLoggedIn) {
      unreadNotifications.value = 0;
      return;
    }
    try {
      final result = await _notifications.list(perPage: 1);
      unreadNotifications.value = result.unreadCount;
    } catch (_) {}
  }

  /// مع كل تحديث للرئيسية نتحقق من تفعيل المساعد لإظهار/إخفاء التبويب.
  Future<void> _refreshAiAssistantStatus() async {
    try {
      if (Get.isRegistered<AiGateController>()) {
        await Get.find<AiGateController>().refreshStatus(silent: true);
      }
      if (Get.isRegistered<AiChatController>()) {
        await Get.find<AiChatController>().refreshStatus(silent: true);
      }
    } catch (_) {}
  }

  void _applyHome(HomePayloadModel payload) {
    banners.assignAll(payload.banners);
    popularProducts.assignAll(payload.popularProducts);
    offers.assignAll(payload.offers);
    collections.assignAll(payload.collections);
    if (categories.isEmpty) {
      categories.assignAll(payload.categories);
    }
  }
}
