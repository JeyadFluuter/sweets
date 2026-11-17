import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/ai_gate_controller.dart';
import 'package:sweet_store/controllers/cart_controller.dart';
import 'package:sweet_store/controllers/home_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/services/ai_service.dart';
import 'package:sweet_store/services/cart_service.dart';
import 'package:sweet_store/services/catalog_service.dart';
import 'package:sweet_store/services/notification_service.dart';
import 'package:sweet_store/views/account/account_view.dart';
import 'package:sweet_store/views/ai/ai_chat_view.dart';
import 'package:sweet_store/views/home/home_view.dart';
import 'package:sweet_store/views/orders/orders_view.dart';

class MainShellView extends StatefulWidget {
  const MainShellView({super.key});

  @override
  State<MainShellView> createState() => _MainShellViewState();
}

class _MainShellViewState extends State<MainShellView> {
  int _index = 0;

  late final AiGateController _aiGate;

  @override
  void initState() {
    super.initState();
    if (!Get.isRegistered<HomeController>()) {
      Get.put(
        HomeController(
          Get.find<CatalogService>(),
          Get.find<NotificationService>(),
        ),
        permanent: true,
      );
    }
    if (!Get.isRegistered<CartController>()) {
      Get.put(
        CartController(Get.find<CartService>(), Get.find<CatalogService>()),
        permanent: true,
      );
    } else {
      Get.find<CartController>().refreshCart();
    }

    _aiGate = Get.isRegistered<AiGateController>()
        ? Get.find<AiGateController>()
        : Get.put(AiGateController(Get.find<AiService>()), permanent: true);
  }

  List<_ShellTab> _tabs(bool aiVisible) {
    final tabs = <_ShellTab>[
      const _ShellTab(
        icon: Icons.home_rounded,
        label: 'الرئيسية',
        page: HomeView(),
      ),
      const _ShellTab(
        icon: Icons.receipt_long_rounded,
        label: 'طلباتي',
        page: OrdersView(),
      ),
      if (aiVisible)
        const _ShellTab(
          icon: Icons.auto_awesome_rounded,
          label: 'المساعد',
          page: AiChatView(),
        ),
      const _ShellTab(
        icon: Icons.person_rounded,
        label: 'حسابي',
        page: AccountView(),
      ),
    ];
    return tabs;
  }

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final aiVisible = _aiGate.isVisible;
      final tabs = _tabs(aiVisible);
      final safeIndex = _index.clamp(0, tabs.length - 1);
      if (safeIndex != _index) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (mounted) setState(() => _index = safeIndex);
        });
      }

      return Scaffold(
        backgroundColor: AppColors.background,
        body: IndexedStack(
          index: safeIndex,
          children: tabs.map((t) => t.page).toList(growable: false),
        ),
        bottomNavigationBar: Container(
          decoration: BoxDecoration(
            color: AppColors.surface,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.06),
                blurRadius: 16,
                offset: const Offset(0, -4),
              ),
            ],
          ),
          child: SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(8, 8, 8, 8),
              child: Row(
                children: [
                  for (var i = 0; i < tabs.length; i++)
                    _NavItem(
                      icon: tabs[i].icon,
                      label: tabs[i].label,
                      selected: safeIndex == i,
                      onTap: () => setState(() => _index = i),
                    ),
                ],
              ),
            ),
          ),
        ),
      );
    });
  }
}

class _ShellTab {
  const _ShellTab({
    required this.icon,
    required this.label,
    required this.page,
  });

  final IconData icon;
  final String label;
  final Widget page;
}

class _NavItem extends StatelessWidget {
  const _NavItem({
    required this.icon,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final color = selected ? AppColors.mintDark : AppColors.textMuted;
    return Expanded(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          padding: const EdgeInsets.symmetric(vertical: 8),
          decoration: BoxDecoration(
            color: selected
                ? AppColors.mint.withValues(alpha: 0.12)
                : Colors.transparent,
            borderRadius: BorderRadius.circular(16),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, color: color, size: 24),
              const SizedBox(height: 2),
              Text(
                label,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
                  color: color,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
