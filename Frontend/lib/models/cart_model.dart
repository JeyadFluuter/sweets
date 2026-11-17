import 'package:sweet_store/core/utils/json_parsers.dart';

String moneyLabel(num value, String currency) {
  final amount = value.toStringAsFixed(value % 1 == 0 ? 0 : 2);
  switch (currency.toUpperCase()) {
    case 'LYD':
      return '$amount د.ل';
    case 'USD':
      return '\$$amount';
    case 'EUR':
      return '€$amount';
    default:
      return '$amount $currency';
  }
}

class CartItemModel {
  const CartItemModel({
    required this.id,
    required this.itemType,
    required this.itemId,
    required this.name,
    required this.quantity,
    required this.unitPrice,
    required this.lineTotal,
    this.subtitle,
    this.imageUrl,
    this.minOrderQty = 1,
    this.maxOrderQty = 99,
    this.currency = 'LYD',
  });

  final int id;
  final String itemType;
  final int itemId;
  final String name;
  final String? subtitle;
  final String? imageUrl;
  final int quantity;
  final num unitPrice;
  final num lineTotal;
  final int minOrderQty;
  final int maxOrderQty;
  final String currency;

  bool get isCollection => itemType == 'collection';
  bool get isProduct => itemType == 'product';

  String get displayUnitPrice => moneyLabel(unitPrice, currency);
  String get displayLineTotal => moneyLabel(lineTotal, currency);

  factory CartItemModel.fromJson(
    Map<String, dynamic> json, {
    String currency = 'LYD',
  }) {
    final image = asString(json['image_url']);
    return CartItemModel(
      id: asInt(json['id']) ?? 0,
      itemType: asString(json['item_type']) ?? 'product',
      itemId: asInt(json['item_id']) ?? 0,
      name: asString(json['name']) ?? '',
      subtitle: asString(json['subtitle']),
      imageUrl: (image == null || image.isEmpty) ? null : image,
      quantity: asInt(json['quantity']) ?? 1,
      unitPrice: asNum(json['unit_price']) ?? 0,
      lineTotal: asNum(json['line_total']) ?? 0,
      minOrderQty: (() {
        final v = asInt(json['min_order_qty']) ?? 1;
        return v < 1 ? 1 : v;
      })(),
      maxOrderQty: (() {
        final min = asInt(json['min_order_qty']) ?? 1;
        final max = asInt(json['max_order_qty']) ?? 99;
        final safeMin = min < 1 ? 1 : min;
        return max < safeMin ? safeMin : max;
      })(),
      currency: currency,
    );
  }
}

class CartCouponModel {
  const CartCouponModel({
    required this.id,
    required this.code,
    required this.discount,
    this.currency = 'LYD',
  });

  final int id;
  final String code;
  final num discount;
  final String currency;

  String get displayDiscount => moneyLabel(discount, currency);

  factory CartCouponModel.fromJson(
    Map<String, dynamic> json, {
    String currency = 'LYD',
  }) {
    return CartCouponModel(
      id: asInt(json['id']) ?? 0,
      code: asString(json['code']) ?? '',
      discount: asNum(json['discount']) ?? 0,
      currency: currency,
    );
  }
}

class CartModel {
  const CartModel({
    this.id,
    this.items = const [],
    this.subtotal = 0,
    this.discount = 0,
    this.total = 0,
    this.currency = 'LYD',
    this.coupon,
  });

  final int? id;
  final List<CartItemModel> items;
  final num subtotal;
  final num discount;
  final num total;
  final String currency;
  final CartCouponModel? coupon;

  bool get isEmpty => items.isEmpty;
  bool get isNotEmpty => items.isNotEmpty;

  int get itemsCount =>
      items.fold<int>(0, (sum, item) => sum + item.quantity);

  String get displayTotal => moneyLabel(total, currency);
  String get displaySubtotal => moneyLabel(subtotal, currency);
  String get displayDiscount => moneyLabel(discount, currency);

  factory CartModel.fromJson(Map<String, dynamic> json) {
    final currency = asString(json['currency']) ?? 'LYD';

    CartCouponModel? coupon;
    final rawCoupon = json['coupon'];
    if (rawCoupon is Map) {
      coupon = CartCouponModel.fromJson(
        Map<String, dynamic>.from(rawCoupon),
        currency: currency,
      );
    }

    return CartModel(
      id: asInt(json['id']),
      items: (json['items'] as List? ?? [])
          .whereType<Map>()
          .map(
            (e) => CartItemModel.fromJson(
              Map<String, dynamic>.from(e),
              currency: currency,
            ),
          )
          .toList(),
      subtotal: asNum(json['subtotal']) ?? 0,
      discount: asNum(json['discount']) ?? 0,
      total: asNum(json['total']) ?? 0,
      currency: currency,
      coupon: coupon,
    );
  }

  factory CartModel.empty() => const CartModel();
}
