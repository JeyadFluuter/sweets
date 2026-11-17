import 'dart:convert';

import 'package:sweet_store/core/constants/api_endpoints.dart';
import 'package:sweet_store/core/network/api_client.dart';
import 'package:sweet_store/core/storage/app_storage.dart';
import 'package:sweet_store/models/category_model.dart';
import 'package:sweet_store/models/collection_model.dart';
import 'package:sweet_store/models/home_payload_model.dart';
import 'package:sweet_store/models/product_model.dart';

class CatalogService {
  CatalogService(this._api, this._storage);

  final ApiClient _api;
  final AppStorage _storage;

  Future<HomePayloadModel> fetchHome({bool forceNetwork = false}) async {
    if (!forceNetwork) {
      final cached = await readHomeCache();
      if (cached != null) {
        // نُرجع الكاش ونحدّث بالخلفية من الخارج إن رُغب
      }
    }

    final body = await _api.get(ApiEndpoints.home);
    final payload = HomePayloadModel.fromJson(
      Map<String, dynamic>.from(body['data'] as Map),
    );
    await _storage.writeString(
      AppStorage.homeCacheKey,
      jsonEncode(payload.toJson()),
    );
    return payload;
  }

  Future<HomePayloadModel?> readHomeCache() async {
    final raw = await _storage.readString(AppStorage.homeCacheKey);
    if (raw == null) return null;
    try {
      return HomePayloadModel.fromJson(
        Map<String, dynamic>.from(jsonDecode(raw) as Map),
      );
    } catch (_) {
      return null;
    }
  }

  Future<List<CategoryModel>> fetchCategories({bool forceNetwork = false}) async {
    if (!forceNetwork) {
      final cached = await readCategoriesCache();
      if (cached != null && cached.isNotEmpty) return cached;
    }

    final body = await _api.get(ApiEndpoints.categories);
    final list = (body['data'] as List? ?? [])
        .whereType<Map>()
        .map((e) => CategoryModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();
    await _storage.writeString(
      AppStorage.categoriesCacheKey,
      jsonEncode(list.map((e) => e.toJson()).toList()),
    );
    return list;
  }

  Future<List<CategoryModel>?> readCategoriesCache() async {
    final raw = await _storage.readString(AppStorage.categoriesCacheKey);
    if (raw == null) return null;
    try {
      return (jsonDecode(raw) as List)
          .whereType<Map>()
          .map((e) => CategoryModel.fromJson(Map<String, dynamic>.from(e)))
          .toList();
    } catch (_) {
      return null;
    }
  }

  Future<CategoryModel> fetchCategory(int id) async {
    final body = await _api.get(ApiEndpoints.category(id));
    return CategoryModel.fromJson(
      Map<String, dynamic>.from(body['data'] as Map),
    );
  }

  Future<ProductModel> fetchProduct(int id) async {
    final body = await _api.get(ApiEndpoints.product(id));
    return ProductModel.fromJson(
      Map<String, dynamic>.from(body['data'] as Map),
    );
  }

  Future<List<ProductModel>> fetchPopular({int limit = 50}) async {
    final body = await _api.get(
      ApiEndpoints.productsPopular,
      query: {'limit': limit},
    );
    return (body['data'] as List? ?? [])
        .whereType<Map>()
        .map((e) => ProductModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<List<CollectionModel>> fetchCollections({int limit = 50}) async {
    final body = await _api.get(
      ApiEndpoints.collections,
      query: {'limit': limit},
    );
    return (body['data'] as List? ?? [])
        .whereType<Map>()
        .map((e) => CollectionModel.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<CollectionModel> fetchCollection(int id) async {
    final body = await _api.get(ApiEndpoints.collection(id));
    return CollectionModel.fromJson(
      Map<String, dynamic>.from(body['data'] as Map),
    );
  }
}
