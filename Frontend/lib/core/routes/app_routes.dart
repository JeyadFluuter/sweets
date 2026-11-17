abstract class AppRoutes {
  static const splash = '/';
  static const login = '/login';
  static const register = '/register';
  static const main = '/main';
  static const home = '/home'; // توافق خلفي → نفس الشِل
  static const categories = '/categories';
  static const categoryDetail = '/categories/:id';
  static const popular = '/popular';
  static const collections = '/collections';
  static const collectionDetail = '/collections/:id';
  static const productDetail = '/products/:id';
  static const notifications = '/notifications';
  static const cart = '/cart';
  static const search = '/search';
  static const checkout = '/checkout';
  static const orderDetail = '/orders/:id';
  static const orderPay = '/orders/:id/pay';
}
