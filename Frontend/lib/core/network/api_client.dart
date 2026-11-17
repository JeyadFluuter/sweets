import 'package:dio/dio.dart';
import 'package:sweet_store/core/constants/api_endpoints.dart';
import 'package:sweet_store/core/network/api_exception.dart';
import 'package:sweet_store/core/storage/token_storage.dart';

class ApiClient {
  ApiClient(this._tokenStorage) {
    _dio = Dio(
      BaseOptions(
        baseUrl: ApiEndpoints.baseUrl,
        connectTimeout: const Duration(seconds: 20),
        receiveTimeout: const Duration(seconds: 20),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStorage.readToken();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
      ),
    );
  }

  final TokenStorage _tokenStorage;
  late final Dio _dio;

  Dio get dio => _dio;

  Future<Map<String, dynamic>> get(
    String path, {
    Map<String, dynamic>? query,
  }) async {
    try {
      final res = await _dio.get<dynamic>(path, queryParameters: query);
      return _unwrap(res.data, res.statusCode);
    } on DioException catch (e) {
      throw _mapDio(e);
    }
  }

  Future<Map<String, dynamic>> post(
    String path, {
    Map<String, dynamic>? body,
  }) async {
    try {
      final res = await _dio.post<dynamic>(path, data: body);
      return _unwrap(res.data, res.statusCode);
    } on DioException catch (e) {
      throw _mapDio(e);
    }
  }

  Future<Map<String, dynamic>> patch(
    String path, {
    Map<String, dynamic>? body,
  }) async {
    try {
      final res = await _dio.patch<dynamic>(path, data: body);
      return _unwrap(res.data, res.statusCode);
    } on DioException catch (e) {
      throw _mapDio(e);
    }
  }

  Future<Map<String, dynamic>> delete(String path) async {
    try {
      final res = await _dio.delete<dynamic>(path);
      return _unwrap(res.data, res.statusCode);
    } on DioException catch (e) {
      throw _mapDio(e);
    }
  }

  Map<String, dynamic> _unwrap(dynamic raw, int? statusCode) {
    if (raw is! Map) {
      throw ApiException('استجابة غير صالحة من الخادم', statusCode: statusCode);
    }
    final body = Map<String, dynamic>.from(raw);
    final success = body['success'] == true;
    if (!success) {
      throw ApiException(
        (body['message'] as String?) ?? 'حدث خطأ',
        statusCode: statusCode,
        errors: body['errors'] is Map
            ? Map<String, dynamic>.from(body['errors'] as Map)
            : null,
      );
    }
    return body;
  }

  ApiException _mapDio(DioException e) {
    final data = e.response?.data;
    if (data is Map) {
      final map = Map<String, dynamic>.from(data);
      return ApiException(
        (map['message'] as String?) ?? 'فشل الاتصال بالخادم',
        statusCode: e.response?.statusCode,
        errors: map['errors'] is Map
            ? Map<String, dynamic>.from(map['errors'] as Map)
            : null,
      );
    }
    if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout) {
      return ApiException('انتهت مهلة الاتصال');
    }
    if (e.type == DioExceptionType.connectionError) {
      return ApiException('تعذّر الاتصال بالخادم. تأكد أن الـ API يعمل.');
    }
    return ApiException(e.message ?? 'حدث خطأ غير متوقع');
  }
}
