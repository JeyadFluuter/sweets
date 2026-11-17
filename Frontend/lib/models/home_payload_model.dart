import 'package:sweet_store/models/banner_model.dart';
import 'package:sweet_store/models/category_model.dart';
import 'package:sweet_store/models/collection_model.dart';
import 'package:sweet_store/models/product_model.dart';

class HomePayloadModel {
  const HomePayloadModel({
    this.banners = const [],
    this.categories = const [],
    this.popularProducts = const [],
    this.offers = const [],
    this.collections = const [],
  });

  final List<BannerModel> banners;
  final List<CategoryModel> categories;
  final List<ProductModel> popularProducts;
  final List<ProductModel> offers;
  final List<CollectionModel> collections;

  factory HomePayloadModel.fromJson(Map<String, dynamic> json) {
    List<T> mapList<T>(
      dynamic raw,
      T Function(Map<String, dynamic>) mapper,
    ) {
      return (raw as List? ?? [])
          .whereType<Map>()
          .map((e) => mapper(Map<String, dynamic>.from(e)))
          .toList();
    }

    return HomePayloadModel(
      banners: mapList(json['banners'], BannerModel.fromJson),
      categories: mapList(json['categories'], CategoryModel.fromJson),
      popularProducts: mapList(json['popular_products'], ProductModel.fromJson),
      offers: mapList(json['offers'], ProductModel.fromJson),
      collections: mapList(json['collections'], CollectionModel.fromJson),
    );
  }

  Map<String, dynamic> toJson() => {
        'banners': banners.map((e) => e.toJson()).toList(),
        'categories': categories.map((e) => e.toJson()).toList(),
        'popular_products': popularProducts.map((e) => e.toJson()).toList(),
        'offers': offers.map((e) => e.toJson()).toList(),
        'collections': collections.map((e) => e.toJson()).toList(),
      };
}
