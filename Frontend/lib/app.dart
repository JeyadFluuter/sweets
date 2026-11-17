import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:get/get.dart';
import 'package:sweet_store/bindings/app_binding.dart';
import 'package:sweet_store/core/routes/app_pages.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/core/theme/app_theme.dart';

class SweetStoreApp extends StatelessWidget {
  const SweetStoreApp({super.key});

  @override
  Widget build(BuildContext context) {
    return GetMaterialApp(
      title: 'Sweet Store',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      locale: const Locale('ar'),
      textDirection: TextDirection.rtl,
      initialBinding: AppBinding(),
      initialRoute: AppRoutes.splash,
      getPages: AppPages.routes,
      defaultTransition: Transition.cupertino,
      builder: (context, child) {
        return AnnotatedRegion<SystemUiOverlayStyle>(
          value: SystemUiOverlayStyle.dark,
          child: child ?? const SizedBox.shrink(),
        );
      },
    );
  }
}
