import 'package:get/get.dart';
import 'package:sweet_store/controllers/product_detail_controller.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/services/catalog_service.dart';
import 'package:sweet_store/views/auth/login_view.dart';
import 'package:sweet_store/views/auth/register_view.dart';
import 'package:sweet_store/views/cart/cart_view.dart';
import 'package:sweet_store/views/catalog/categories_view.dart';
import 'package:sweet_store/views/catalog/category_detail_view.dart';
import 'package:sweet_store/views/catalog/collection_detail_view.dart';
import 'package:sweet_store/views/catalog/collections_view.dart';
import 'package:sweet_store/views/catalog/popular_view.dart';
import 'package:sweet_store/views/catalog/product_detail_view.dart';
import 'package:sweet_store/views/catalog/search_view.dart';
import 'package:sweet_store/views/checkout/checkout_view.dart';
import 'package:sweet_store/views/notifications/notifications_view.dart';
import 'package:sweet_store/views/orders/order_detail_view.dart';
import 'package:sweet_store/views/orders/payment_view.dart';
import 'package:sweet_store/views/shell/main_shell_view.dart';
import 'package:sweet_store/views/splash/splash_view.dart';

class AppPages {
  static final routes = <GetPage<dynamic>>[
    GetPage(name: AppRoutes.splash, page: () => const SplashView()),
    GetPage(name: AppRoutes.login, page: () => const LoginView()),
    GetPage(name: AppRoutes.register, page: () => const RegisterView()),
    GetPage(name: AppRoutes.main, page: () => const MainShellView()),
    GetPage(name: AppRoutes.home, page: () => const MainShellView()),
    GetPage(name: AppRoutes.categories, page: () => const CategoriesView()),
    GetPage(
      name: AppRoutes.categoryDetail,
      page: () => const CategoryDetailView(),
    ),
    GetPage(name: AppRoutes.popular, page: () => const PopularView()),
    GetPage(name: AppRoutes.collections, page: () => const CollectionsView()),
    GetPage(
      name: AppRoutes.collectionDetail,
      page: () => const CollectionDetailView(),
    ),
    GetPage(
      name: AppRoutes.productDetail,
      page: () => const ProductDetailView(),
      binding: BindingsBuilder(() {
        Get.put(ProductDetailController(Get.find<CatalogService>()));
      }),
    ),
    GetPage(
      name: AppRoutes.notifications,
      page: () => const NotificationsView(),
    ),
    GetPage(name: AppRoutes.cart, page: () => const CartView()),
    GetPage(name: AppRoutes.search, page: () => const SearchView()),
    GetPage(name: AppRoutes.checkout, page: () => const CheckoutView()),
    GetPage(
      name: AppRoutes.orderPay,
      page: () => const PaymentView(),
    ),
    GetPage(
      name: AppRoutes.orderDetail,
      page: () => const OrderDetailView(),
    ),
  ];
}
