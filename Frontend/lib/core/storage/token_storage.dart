import 'package:sweet_store/core/storage/app_storage.dart';

class TokenStorage {
  TokenStorage([AppStorage? storage]) : _storage = storage ?? AppStorage();

  final AppStorage _storage;

  Future<void> saveToken(String token) =>
      _storage.writeString(AppStorage.tokenKey, token);

  Future<String?> readToken() => _storage.readString(AppStorage.tokenKey);

  Future<void> saveCustomerJson(String json) =>
      _storage.writeString(AppStorage.customerKey, json);

  Future<String?> readCustomerJson() =>
      _storage.readString(AppStorage.customerKey);

  Future<void> clear() => _storage.clearSession();
}
