import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/auth_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/constants/app_strings.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/widgets/app_button.dart';
import 'package:sweet_store/widgets/app_text_field.dart';
import 'package:sweet_store/widgets/brand_logo.dart';

class LoginView extends StatefulWidget {
  const LoginView({super.key});

  @override
  State<LoginView> createState() => _LoginViewState();
}

class _LoginViewState extends State<LoginView> {
  final _formKey = GlobalKey<FormState>();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  bool _obscure = true;

  AuthController get auth => Get.find<AuthController>();

  @override
  void dispose() {
    _phone.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final ok = await auth.login(
      phone: _phone.text.trim(),
      password: _password.text,
    );
    if (!mounted) return;
    if (ok) {
      Get.offAllNamed(AppRoutes.main);
    } else if (auth.errorMessage.value != null) {
      Get.snackbar(
        'تنبيه',
        auth.errorMessage.value!,
        snackPosition: SnackPosition.BOTTOM,
        backgroundColor: AppColors.danger,
        colorText: Colors.white,
      );
    }
  }

  Future<void> _guest() async {
    final ok = await auth.continueAsGuest();
    if (!mounted) return;
    if (ok) {
      Get.offAllNamed(AppRoutes.main);
    } else if (auth.errorMessage.value != null) {
      Get.snackbar('تنبيه', auth.errorMessage.value!);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(24, 28, 24, 24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Center(child: BrandLogo(size: 96)),
                const SizedBox(height: 36),
                Text(
                  AppStrings.welcomeBack,
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.headlineMedium,
                ),
                const SizedBox(height: 8),
                Text(
                  AppStrings.loginSubtitle,
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                const SizedBox(height: 32),
                AppTextField(
                  controller: _phone,
                  label: AppStrings.phone,
                  hint: AppStrings.phoneHint,
                  keyboardType: TextInputType.phone,
                  textInputAction: TextInputAction.next,
                  prefixIcon: Icons.phone_outlined,
                  validator: (v) {
                    final value = v?.trim() ?? '';
                    if (value.isEmpty) return 'أدخل رقم الهاتف';
                    if (!RegExp(r'^09[1-5]\d{7}$').hasMatch(value) &&
                        !RegExp(r'^(\+?218|00218)?9[1-5]\d{7}$')
                            .hasMatch(value.replaceAll(' ', ''))) {
                      return 'رقم ليبي غير صالح';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                AppTextField(
                  controller: _password,
                  label: AppStrings.password,
                  obscureText: _obscure,
                  textInputAction: TextInputAction.done,
                  prefixIcon: Icons.lock_outline_rounded,
                  suffix: IconButton(
                    onPressed: () => setState(() => _obscure = !_obscure),
                    icon: Icon(
                      _obscure
                          ? Icons.visibility_outlined
                          : Icons.visibility_off_outlined,
                      color: AppColors.textMuted,
                    ),
                  ),
                  validator: (v) {
                    if (v == null || v.isEmpty) return 'أدخل كلمة المرور';
                    return null;
                  },
                ),
                const SizedBox(height: 28),
                Obx(
                  () => AppButton(
                    label: AppStrings.login,
                    loading: auth.isLoading.value,
                    onPressed: _submit,
                  ),
                ),
                const SizedBox(height: 12),
                Obx(
                  () => AppButton(
                    label: AppStrings.guestContinue,
                    backgroundColor: AppColors.primaryYellow,
                    foregroundColor: AppColors.textDark,
                    onPressed: auth.isLoading.value ? null : _guest,
                  ),
                ),
                const SizedBox(height: 20),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      AppStrings.noAccount,
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                    TextButton(
                      onPressed: () => Get.toNamed(AppRoutes.register),
                      child: const Text(AppStrings.createAccount),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
