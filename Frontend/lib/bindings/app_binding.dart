import 'package:get/get.dart';
import 'package:sweet_store/controllers/ai_gate_controller.dart';
import 'package:sweet_store/controllers/auth_controller.dart';
import 'package:sweet_store/controllers/cart_controller.dart';
import 'package:sweet_store/controllers/home_controller.dart';
import 'package:sweet_store/core/network/api_client.dart';
import 'package:sweet_store/core/storage/app_storage.dart';
import 'package:sweet_store/core/storage/token_storage.dart';
import 'package:sweet_store/services/ai_service.dart';
import 'package:sweet_store/services/auth_service.dart';
import 'package:sweet_store/services/cart_service.dart';
import 'package:sweet_store/services/catalog_service.dart';
import 'package:sweet_store/services/fulfillment_service.dart';
import 'package:sweet_store/services/notification_service.dart';
import 'package:sweet_store/services/order_service.dart';

class AppBinding extends Bindings {
  @override
  void dependencies() {
    final storage = AppStorage();
    final tokenStorage = TokenStorage(storage);
    final api = ApiClient(tokenStorage);

    Get.put<AppStorage>(storage, permanent: true);
    Get.put<TokenStorage>(tokenStorage, permanent: true);
    Get.put<ApiClient>(api, permanent: true);
    Get.put<AuthService>(AuthService(api, tokenStorage), permanent: true);
    Get.put<CatalogService>(CatalogService(api, storage), permanent: true);
    Get.put<NotificationService>(NotificationService(api), permanent: true);
    Get.put<CartService>(CartService(api), permanent: true);
    Get.put<FulfillmentService>(FulfillmentService(api), permanent: true);
    Get.put<OrderService>(OrderService(api), permanent: true);
    Get.put<AiService>(AiService(api), permanent: true);
    Get.put<AuthController>(
      AuthController(Get.find<AuthService>()),
      permanent: true,
    );
    Get.put<CartController>(
      CartController(Get.find<CartService>(), Get.find<CatalogService>()),
      permanent: true,
    );
    Get.put<HomeController>(
      HomeController(
        Get.find<CatalogService>(),
        Get.find<NotificationService>(),
      ),
      permanent: true,
    );
    Get.put<AiGateController>(
      AiGateController(Get.find<AiService>()),
      permanent: true,
    );
  }
}
