import 'package:sweet_store/core/constants/api_endpoints.dart';
import 'package:sweet_store/core/utils/json_parsers.dart';

class CollectionModel {
  const CollectionModel({
    required this.id,
    required this.name,
    this.slug,
    this.description,
    this.price,
    this.imageUrl,
  });

  final int id;
  final String name;
  final String? slug;
  final String? description;
  final num? price;
  final String? imageUrl;

  factory CollectionModel.fromJson(Map<String, dynamic> json) {
    String? image = asString(json['image_url']);
    final images = json['images'];
    if ((image == null || image.isEmpty) &&
        images is List &&
        images.isNotEmpty) {
      final first = images.first;
      if (first is Map && first['url'] != null) {
        image = first['url'].toString();
      }
    }

    return CollectionModel(
      id: asInt(json['id']) ?? 0,
      name: asString(json['name']) ?? '',
      slug: asString(json['slug']),
      description: asString(json['description']),
      price: asNum(json['price']),
      imageUrl: ApiEndpoints.resolveMediaUrl(image),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'slug': slug,
        'description': description,
        'price': price,
        'image_url': imageUrl,
        'images': imageUrl == null ? [] : [{'url': imageUrl}],
      };

  String get displayPrice {
    final value = price ?? 0;
    return '${value.toStringAsFixed(value % 1 == 0 ? 0 : 2)} د.ل';
  }
}
