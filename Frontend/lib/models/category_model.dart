import 'package:sweet_store/core/constants/api_endpoints.dart';
import 'package:sweet_store/core/utils/json_parsers.dart';
import 'package:sweet_store/models/product_model.dart';

class CategoryModel {
  const CategoryModel({
    required this.id,
    required this.name,
    this.slug,
    this.parentId,
    this.imageUrl,
    this.children = const [],
    this.products = const [],
  });

  final int id;
  final String name;
  final String? slug;
  final int? parentId;
  final String? imageUrl;
  final List<CategoryModel> children;
  final List<ProductModel> products;

  factory CategoryModel.fromJson(Map<String, dynamic> json) {
    return CategoryModel(
      id: asInt(json['id']) ?? 0,
      name: asString(json['name']) ?? '',
      slug: asString(json['slug']),
      parentId: asInt(json['parent_id']),
      imageUrl: ApiEndpoints.resolveMediaUrl(asString(json['image_url'])),
      children: (json['children'] as List? ?? [])
          .whereType<Map>()
          .map((e) => CategoryModel.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      products: (json['products'] as List? ?? [])
          .whereType<Map>()
          .map((e) => ProductModel.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'slug': slug,
        'parent_id': parentId,
        'image_url': imageUrl,
        'children': children.map((e) => e.toJson()).toList(),
        'products': products.map((e) => e.toJson()).toList(),
      };
}
