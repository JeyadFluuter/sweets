import 'package:sweet_store/core/constants/api_endpoints.dart';
import 'package:sweet_store/core/utils/json_parsers.dart';

class ProductModel {
  const ProductModel({
    required this.id,
    required this.name,
    this.slug,
    this.description,
    this.price,
    this.discountPrice,
    this.effectivePrice,
    this.hasActiveDiscount = false,
    this.categoryId,
    this.imageUrl,
    this.imageUrls = const [],
    this.ingredients,
    this.allergenAlerts,
    this.minOrderQty = 1,
    this.maxOrderQty = 99,
  });

  final int id;
  final String name;
  final String? slug;
  final String? description;
  final num? price;
  final num? discountPrice;
  final num? effectivePrice;
  final bool hasActiveDiscount;
  final int? categoryId;
  final String? imageUrl;
  final List<String> imageUrls;
  final String? ingredients;
  final String? allergenAlerts;
  final int minOrderQty;
  final int maxOrderQty;

  bool get hasIngredients =>
      ingredients != null && ingredients!.trim().isNotEmpty;

  bool get hasAllergenAlerts =>
      allergenAlerts != null && allergenAlerts!.trim().isNotEmpty;

  factory ProductModel.fromJson(Map<String, dynamic> json) {
    final urls = <String>[];
    final images = json['images'];
    if (images is List) {
      for (final item in images) {
        if (item is Map && item['url'] != null) {
          final resolved = ApiEndpoints.resolveMediaUrl(item['url'].toString());
          if (resolved != null && resolved.isNotEmpty) urls.add(resolved);
        }
      }
    }

    String? image = ApiEndpoints.resolveMediaUrl(asString(json['image_url']));
    if ((image == null || image.isEmpty) && urls.isNotEmpty) {
      image = urls.first;
    } else if (image != null && image.isNotEmpty && urls.isEmpty) {
      urls.add(image);
    }

    return ProductModel(
      id: asInt(json['id']) ?? 0,
      name: asString(json['name']) ?? '',
      slug: asString(json['slug']),
      description: asString(json['description']),
      price: asNum(json['price']),
      discountPrice: asNum(json['discount_price']),
      effectivePrice: asNum(json['effective_price']),
      hasActiveDiscount: asBool(json['has_active_discount']),
      categoryId: asInt(json['category_id']),
      imageUrl: image,
      imageUrls: urls,
      ingredients: asString(json['ingredients']),
      allergenAlerts: asString(json['allergen_alerts']),
      minOrderQty: _clampMin(asInt(json['min_order_qty']) ?? 1),
      maxOrderQty: _clampMax(
        asInt(json['max_order_qty']) ?? 99,
        asInt(json['min_order_qty']) ?? 1,
      ),
    );
  }

  static int _clampMin(int value) => value < 1 ? 1 : value;

  static int _clampMax(int max, int min) {
    final safeMin = _clampMin(min);
    if (max < safeMin) return safeMin;
    return max;
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'slug': slug,
        'description': description,
        'price': price,
        'discount_price': discountPrice,
        'effective_price': effectivePrice,
        'has_active_discount': hasActiveDiscount,
        'category_id': categoryId,
        'ingredients': ingredients,
        'allergen_alerts': allergenAlerts,
        'min_order_qty': minOrderQty,
        'max_order_qty': maxOrderQty,
        'image_url': imageUrl,
        'images': imageUrls.map((u) => {'url': u}).toList(),
      };

  String get displayPrice {
    final value = effectivePrice ?? price ?? 0;
    return '${value.toStringAsFixed(value % 1 == 0 ? 0 : 2)} د.ل';
  }
}
