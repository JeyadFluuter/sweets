import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/auth_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/constants/app_strings.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/app_button.dart';
import 'package:sweet_store/widgets/brand_logo.dart';

class AccountView extends StatelessWidget {
  const AccountView({super.key});

  @override
  Widget build(BuildContext context) {
    final auth = Get.find<AuthController>();

    return Scaffold(
      appBar: AppBar(title: const Text('حسابي')),
      body: Obx(() {
        final customer = auth.customer.value;
        return ListView(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 100),
          children: [
            const Center(child: BrandLogo(size: 88)),
            const SizedBox(height: 16),
            Text(
              customer?.name ?? 'ضيف',
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.w800,
                  ),
            ),
            if (customer?.phone != null) ...[
              const SizedBox(height: 4),
              Text(
                customer!.phone!,
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            ],
            const SizedBox(height: 28),
            _tile(
              icon: Icons.person_outline,
              title: 'تعديل بياناتي',
              onTap: () => Get.snackbar('قريباً', 'واجهة تعديل الحساب'),
            ),
            _tile(
              icon: Icons.notifications_none_rounded,
              title: 'الإشعارات',
              onTap: () => Get.toNamed(AppRoutes.notifications),
            ),
            const SizedBox(height: 24),
            AppButton(
              label: AppStrings.logout,
              backgroundColor: AppColors.coral,
              onPressed: () async {
                await auth.logout();
                Get.offAllNamed(AppRoutes.login);
              },
            ),
          ],
        );
      }),
    );
  }

  Widget _tile({
    required IconData icon,
    required String title,
    required VoidCallback onTap,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      child: ListTile(
        onTap: onTap,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        tileColor: AppColors.surface,
        leading: Icon(icon, color: AppColors.mintDark),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
        trailing: const EnterChevron(),
      ),
    );
  }
}
