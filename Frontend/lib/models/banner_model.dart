import 'package:sweet_store/core/constants/api_endpoints.dart';
import 'package:sweet_store/core/utils/json_parsers.dart';

class BannerModel {
  const BannerModel({
    required this.id,
    this.title,
    this.linkType,
    this.linkId,
    this.imageUrl,
  });

  final int id;
  final String? title;
  final String? linkType;
  final int? linkId;
  final String? imageUrl;

  factory BannerModel.fromJson(Map<String, dynamic> json) {
    return BannerModel(
      id: asInt(json['id']) ?? 0,
      title: asString(json['title']),
      linkType: asString(json['link_type']),
      linkId: asInt(json['link_id']),
      imageUrl: ApiEndpoints.resolveMediaUrl(asString(json['image_url'])),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'title': title,
        'link_type': linkType,
        'link_id': linkId,
        'image_url': imageUrl,
      };
}
