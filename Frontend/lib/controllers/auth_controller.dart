import 'package:get/get.dart';
import 'package:sweet_store/core/network/api_exception.dart';
import 'package:sweet_store/models/customer_model.dart';
import 'package:sweet_store/services/auth_service.dart';

class AuthController extends GetxController {
  AuthController(this._authService);

  final AuthService _authService;

  final customer = Rxn<CustomerModel>();
  final isLoading = false.obs;
  final errorMessage = RxnString();
  final bootstrapped = false.obs;

  bool get isLoggedIn => customer.value != null;
  bool get hasTokenSession => customer.value != null;

  /// يستدعي `/me` إن وُجد توكن — للاستخدام من السبلاش
  Future<void> bootstrap() async {
    final token = await _authService.savedToken();
    if (token == null) {
      customer.value = null;
      bootstrapped.value = true;
      return;
    }

    try {
      customer.value = await _authService.me();
    } catch (_) {
      // توكن منتهي أو شبكة: نمسح الجلسة ونطلب دخولاً جديداً
      await _authService.logout();
      customer.value = null;
    }
    bootstrapped.value = true;
  }

  Future<bool> login({required String phone, required String password}) async {
    return _run(() async {
      final session =
          await _authService.login(phone: phone, password: password);
      customer.value = session.customer;
    });
  }

  Future<bool> register({
    required String name,
    required String phone,
    required String password,
    required String passwordConfirmation,
    String? email,
  }) async {
    return _run(() async {
      final session = await _authService.register(
        name: name,
        phone: phone,
        password: password,
        passwordConfirmation: passwordConfirmation,
        email: email,
      );
      customer.value = session.customer;
    });
  }

  Future<bool> continueAsGuest() async {
    return _run(() async {
      final session = await _authService.guest();
      customer.value = session.customer;
    });
  }

  Future<void> logout() async {
    await _authService.logout();
    customer.value = null;
  }

  Future<bool> _run(Future<void> Function() action) async {
    isLoading.value = true;
    errorMessage.value = null;
    try {
      await action();
      isLoading.value = false;
      return true;
    } on ApiException catch (e) {
      errorMessage.value = e.firstError;
      isLoading.value = false;
      return false;
    } catch (_) {
      errorMessage.value = 'حدث خطأ غير متوقع';
      isLoading.value = false;
      return false;
    }
  }
}
