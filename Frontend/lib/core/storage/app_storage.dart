import 'package:shared_preferences/shared_preferences.dart';

class AppStorage {
  static const tokenKey = 'auth_token';
  static const customerKey = 'customer_json';
  static const homeCacheKey = 'home_cache_json';
  static const categoriesCacheKey = 'categories_cache_json';

  Future<SharedPreferences> get _prefs async => SharedPreferences.getInstance();

  Future<void> writeString(String key, String value) async {
    final prefs = await _prefs;
    await prefs.setString(key, value);
  }

  Future<String?> readString(String key) async {
    final prefs = await _prefs;
    return prefs.getString(key);
  }

  Future<void> remove(String key) async {
    final prefs = await _prefs;
    await prefs.remove(key);
  }

  Future<void> clearSession() async {
    final prefs = await _prefs;
    await prefs.remove(tokenKey);
    await prefs.remove(customerKey);
  }
}
