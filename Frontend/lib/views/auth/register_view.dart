import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/auth_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/constants/app_strings.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/app_button.dart';
import 'package:sweet_store/widgets/app_text_field.dart';
import 'package:sweet_store/widgets/brand_logo.dart';

class RegisterView extends StatefulWidget {
  const RegisterView({super.key});

  @override
  State<RegisterView> createState() => _RegisterViewState();
}

class _RegisterViewState extends State<RegisterView> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _phone = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  bool _obscure = true;
  bool _obscureConfirm = true;

  AuthController get auth => Get.find<AuthController>();

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    _email.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final ok = await auth.register(
      name: _name.text.trim(),
      phone: _phone.text.trim(),
      email: _email.text.trim(),
      password: _password.text,
      passwordConfirmation: _confirm.text,
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        leading: const AppBackButton(),
        title: const Text(AppStrings.createAccount),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.fromLTRB(24, 8, 24, 24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Center(child: BrandLogo(size: 84)),
                const SizedBox(height: 20),
                Text(
                  AppStrings.registerSubtitle,
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.bodyMedium,
                ),
                const SizedBox(height: 28),
                AppTextField(
                  controller: _name,
                  label: AppStrings.name,
                  textInputAction: TextInputAction.next,
                  prefixIcon: Icons.person_outline_rounded,
                  validator: (v) {
                    if (v == null || v.trim().isEmpty) return 'أدخل الاسم';
                    return null;
                  },
                ),
                const SizedBox(height: 14),
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
                    if (!RegExp(r'^09[1-5]\d{7}$').hasMatch(value)) {
                      return 'مثال صحيح: 0912345678';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 14),
                AppTextField(
                  controller: _email,
                  label: AppStrings.emailOptional,
                  keyboardType: TextInputType.emailAddress,
                  textInputAction: TextInputAction.next,
                  prefixIcon: Icons.mail_outline_rounded,
                  validator: (v) {
                    final value = v?.trim() ?? '';
                    if (value.isEmpty) return null;
                    if (!value.contains('@')) return 'بريد غير صالح';
                    return null;
                  },
                ),
                const SizedBox(height: 14),
                AppTextField(
                  controller: _password,
                  label: AppStrings.password,
                  obscureText: _obscure,
                  textInputAction: TextInputAction.next,
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
                    if (v == null || v.length < 6) {
                      return 'كلمة المرور 6 أحرف على الأقل';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 14),
                AppTextField(
                  controller: _confirm,
                  label: AppStrings.confirmPassword,
                  obscureText: _obscureConfirm,
                  textInputAction: TextInputAction.done,
                  prefixIcon: Icons.lock_outline_rounded,
                  suffix: IconButton(
                    onPressed: () =>
                        setState(() => _obscureConfirm = !_obscureConfirm),
                    icon: Icon(
                      _obscureConfirm
                          ? Icons.visibility_outlined
                          : Icons.visibility_off_outlined,
                      color: AppColors.textMuted,
                    ),
                  ),
                  validator: (v) {
                    if (v != _password.text) {
                      return 'تأكيد كلمة المرور غير متطابق';
                    }
                    return null;
                  },
                ),
                const SizedBox(height: 28),
                Obx(
                  () => AppButton(
                    label: AppStrings.register,
                    loading: auth.isLoading.value,
                    onPressed: _submit,
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      AppStrings.haveAccount,
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                    TextButton(
                      onPressed: () => Get.back(),
                      child: const Text(AppStrings.login),
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
