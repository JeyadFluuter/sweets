import 'dart:io';

import 'package:flutter/foundation.dart';

/// مسارات الـ API وفق `FLUTTER_API.md`
class ApiEndpoints {
  ApiEndpoints._();

  /// Android emulator → 10.0.2.2 | iOS simulator / desktop → 127.0.0.1
  static String get baseUrl {
    if (kIsWeb) {
      return 'http://127.0.0.1:8000/api/v1';
    }
    if (Platform.isAndroid) {
      return 'http://10.0.2.2:8000/api/v1';
    }
    return 'http://127.0.0.1:8000/api/v1';
  }

  /// أصل السيرفر بدون /api/v1 — لروابط ملفات التخزين
  static String get origin {
    final uri = Uri.parse(baseUrl);
    final port = uri.hasPort ? ':${uri.port}' : '';
    return '${uri.scheme}://${uri.host}$port';
  }

  /// يحوّل روابط localhost/مسارات نسبية ليعمل على المحاكي والجهاز
  static String? resolveMediaUrl(String? url) {
    if (url == null || url.trim().isEmpty) return null;
    final trimmed = url.trim();
    if (trimmed.startsWith('/')) {
      return '$origin$trimmed';
    }
    final parsed = Uri.tryParse(trimmed);
    if (parsed == null) return trimmed;
    if (parsed.host == 'localhost' || parsed.host == '127.0.0.1') {
      final path = parsed.hasQuery
          ? '${parsed.path}?${parsed.query}'
          : parsed.path;
      return '$origin$path';
    }
    return trimmed;
  }

  // Auth
  static const String register = '/auth/register';
  static const String login = '/auth/login';
  static const String guest = '/auth/guest';
  static const String logout = '/auth/logout';
  static const String me = '/me';

  // Catalog
  static const String home = '/home';
  static const String banners = '/banners';
  static const String categories = '/categories';
  static const String productsPopular = '/products/popular';
  static const String collections = '/collections';

  // Cart
  static const String cart = '/cart';
  static const String cartItems = '/cart/items';
  static const String cartCoupon = '/cart/coupon';

  // Orders + Fulfillment
  static const String orders = '/orders';
  static const String fulfillmentDates = '/fulfillment/available-dates';
  static const String fulfillmentSlots = '/fulfillment/slots';

  // Notifications
  static const String notifications = '/notifications';
  static const String devices = '/devices';

  // AI
  static const String aiStatus = '/ai/status';
  static const String aiChat = '/ai/chat';
  static const String aiConversations = '/ai/conversations';

  static String category(int id) => '/categories/$id';
  static String categoryProducts(int id) => '/categories/$id/products';
  static String product(int id) => '/products/$id';
  static String collection(int id) => '/collections/$id';
  static String order(int id) => '/orders/$id';
  static String orderPay(int id) => '/orders/$id/pay';
  static String orderCancel(int id) => '/orders/$id/cancel';
  static String notificationRead(int id) => '/notifications/$id/read';
  static String notification(int id) => '/notifications/$id';
}
