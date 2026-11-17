import 'dart:convert';

import 'package:sweet_store/core/constants/api_endpoints.dart';
import 'package:sweet_store/core/network/api_client.dart';
import 'package:sweet_store/core/storage/token_storage.dart';
import 'package:sweet_store/models/auth_session_model.dart';
import 'package:sweet_store/models/customer_model.dart';

class AuthService {
  AuthService(this._api, this._storage);

  final ApiClient _api;
  final TokenStorage _storage;

  Future<AuthSessionModel> register({
    required String name,
    required String phone,
    required String password,
    required String passwordConfirmation,
    String? email,
  }) async {
    final body = await _api.post(
      ApiEndpoints.register,
      body: {
        'name': name,
        'phone': phone,
        'password': password,
        'password_confirmation': passwordConfirmation,
        if (email != null && email.trim().isNotEmpty) 'email': email.trim(),
      },
    );
    final session = AuthSessionModel.fromJson(
      Map<String, dynamic>.from(body['data'] as Map),
    );
    await _persist(session);
    return session;
  }

  Future<AuthSessionModel> login({
    required String phone,
    required String password,
  }) async {
    final body = await _api.post(
      ApiEndpoints.login,
      body: {'phone': phone, 'password': password},
    );
    final session = AuthSessionModel.fromJson(
      Map<String, dynamic>.from(body['data'] as Map),
    );
    await _persist(session);
    return session;
  }

  Future<AuthSessionModel> guest() async {
    final body = await _api.post(ApiEndpoints.guest);
    final session = AuthSessionModel.fromJson(
      Map<String, dynamic>.from(body['data'] as Map),
    );
    await _persist(session);
    return session;
  }

  Future<CustomerModel> me() async {
    final body = await _api.get(ApiEndpoints.me);
    final customer = CustomerModel.fromJson(
      Map<String, dynamic>.from(body['data'] as Map),
    );
    await _storage.saveCustomerJson(jsonEncode(customer.toJson()));
    return customer;
  }

  Future<void> logout() async {
    try {
      await _api.post(ApiEndpoints.logout);
    } catch (_) {
      // حتى لو فشل الطلب نمسح الجلسة محلياً
    }
    await _storage.clear();
  }

  Future<String?> savedToken() => _storage.readToken();

  Future<CustomerModel?> savedCustomer() async {
    final raw = await _storage.readCustomerJson();
    if (raw == null) return null;
    return CustomerModel.fromJson(
      Map<String, dynamic>.from(jsonDecode(raw) as Map),
    );
  }

  Future<void> _persist(AuthSessionModel session) async {
    await _storage.saveToken(session.token);
    await _storage.saveCustomerJson(jsonEncode(session.customer.toJson()));
  }
}
