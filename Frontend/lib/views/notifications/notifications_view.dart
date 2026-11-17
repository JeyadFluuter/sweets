import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/notifications_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/models/notification_model.dart';
import 'package:sweet_store/services/notification_service.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/skeleton_box.dart';

class NotificationsView extends StatelessWidget {
  const NotificationsView({super.key});

  @override
  Widget build(BuildContext context) {
    final controller = Get.put(
      NotificationsController(Get.find<NotificationService>()),
    );

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.background,
        elevation: 0,
        leading: const AppBackButton(),
        title: const Text('الإشعارات'),
        actions: [
          Obx(() {
            final count = controller.unreadCount.value;
            if (count <= 0) return const SizedBox.shrink();
            return Padding(
              padding: const EdgeInsetsDirectional.only(end: 16),
              child: Center(
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 5,
                  ),
                  decoration: BoxDecoration(
                    color: AppColors.mint.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(
                    '$count غير مقروء',
                    style: const TextStyle(
                      color: AppColors.mintDark,
                      fontWeight: FontWeight.w800,
                      fontSize: 12,
                    ),
                  ),
                ),
              ),
            );
          }),
        ],
      ),
      body: Obx(() {
        if (controller.isLoading.value && controller.items.isEmpty) {
          return ListView.builder(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
            itemCount: 6,
            itemBuilder: (context, index) => const Padding(
              padding: EdgeInsets.only(bottom: 12),
              child: SkeletonBox(height: 96, radius: 22),
            ),
          );
        }

        if (controller.errorMessage.value != null &&
            controller.items.isEmpty) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(
                    Icons.wifi_off_rounded,
                    size: 48,
                    color: AppColors.textMuted,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    controller.errorMessage.value!,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: AppColors.textMuted,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 16),
                  FilledButton(
                    onPressed: controller.load,
                    style: FilledButton.styleFrom(
                      backgroundColor: AppColors.mint,
                    ),
                    child: const Text('إعادة المحاولة'),
                  ),
                ],
              ),
            ),
          );
        }

        if (controller.items.isEmpty) {
          return const Center(
            child: Padding(
              padding: EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    Icons.notifications_none_rounded,
                    size: 64,
                    color: AppColors.textHint,
                  ),
                  SizedBox(height: 14),
                  Text(
                    'لا توجد إشعارات',
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 17,
                      color: AppColors.textDark,
                    ),
                  ),
                  SizedBox(height: 6),
                  Text(
                    'ستظهر هنا تحديثات طلباتك وعروضك',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: AppColors.textMuted,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
          );
        }

        return RefreshIndicator(
          color: AppColors.mint,
          onRefresh: controller.load,
          child: ListView.separated(
            physics: const AlwaysScrollableScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 28),
            itemCount: controller.items.length,
            separatorBuilder: (context, index) => const SizedBox(height: 12),
            itemBuilder: (context, i) {
              final item = controller.items[i];
              return Dismissible(
                key: ValueKey(item.id),
                direction: DismissDirection.startToEnd,
                background: Container(
                  alignment: AlignmentDirectional.centerStart,
                  padding: const EdgeInsets.symmetric(horizontal: 22),
                  decoration: BoxDecoration(
                    color: AppColors.danger,
                    borderRadius: BorderRadius.circular(22),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.delete_outline_rounded, color: Colors.white),
                      SizedBox(width: 8),
                      Text(
                        'حذف',
                        style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ),
                ),
                onDismissed: (_) => controller.remove(item),
                child: _NotificationCard(
                  item: item,
                  onTap: () async {
                    await controller.markRead(item);
                    if (item.orderId != null) {
                      Get.toNamed(
                        AppRoutes.orderDetail
                            .replaceFirst(':id', '${item.orderId}'),
                      );
                    }
                  },
                ),
              );
            },
          ),
        );
      }),
    );
  }
}

class _NotificationCard extends StatelessWidget {
  const _NotificationCard({
    required this.item,
    required this.onTap,
  });

  final NotificationModel item;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final tone = _toneFor(item.type);
    final unread = item.isUnread;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(22),
        child: Ink(
          decoration: BoxDecoration(
            color: unread ? const Color(0xFFF0FBF5) : AppColors.surface,
            borderRadius: BorderRadius.circular(22),
            border: Border.all(
              color: unread
                  ? AppColors.mint.withValues(alpha: 0.35)
                  : AppColors.border.withValues(alpha: 0.7),
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: unread ? 0.06 : 0.04),
                blurRadius: 16,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Container(
                  width: 4,
                  decoration: BoxDecoration(
                    color: unread ? AppColors.mint : Colors.transparent,
                    borderRadius: const BorderRadiusDirectional.only(
                      topStart: Radius.circular(22),
                      bottomStart: Radius.circular(22),
                    ),
                  ),
                ),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(12, 14, 14, 14),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 46,
                          height: 46,
                          decoration: BoxDecoration(
                            color: tone.bg,
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: Icon(tone.icon, color: tone.fg, size: 22),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Expanded(
                                    child: Text(
                                      item.title,
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis,
                                      style: TextStyle(
                                        fontWeight: unread
                                            ? FontWeight.w800
                                            : FontWeight.w700,
                                        fontSize: 15,
                                        height: 1.25,
                                        color: AppColors.textDark,
                                      ),
                                    ),
                                  ),
                                  if (unread) ...[
                                    const SizedBox(width: 8),
                                    Container(
                                      width: 9,
                                      height: 9,
                                      margin: const EdgeInsets.only(top: 4),
                                      decoration: const BoxDecoration(
                                        color: AppColors.coral,
                                        shape: BoxShape.circle,
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                              if (item.body.trim().isNotEmpty) ...[
                                const SizedBox(height: 6),
                                Text(
                                  item.body,
                                  maxLines: 3,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    color: AppColors.textMuted,
                                    fontWeight: FontWeight.w600,
                                    fontSize: 13,
                                    height: 1.35,
                                  ),
                                ),
                              ],
                              const SizedBox(height: 10),
                              Row(
                                children: [
                                  const Icon(
                                    Icons.schedule_rounded,
                                    size: 14,
                                    color: AppColors.textHint,
                                  ),
                                  const SizedBox(width: 4),
                                  Text(
                                    _relativeTime(item.createdAt),
                                    style: const TextStyle(
                                      color: AppColors.textHint,
                                      fontWeight: FontWeight.w700,
                                      fontSize: 11,
                                    ),
                                  ),
                                  if (item.orderId != null) ...[
                                    const Spacer(),
                                    const Text(
                                      'عرض الطلب',
                                      style: TextStyle(
                                        color: AppColors.mintDark,
                                        fontWeight: FontWeight.w800,
                                        fontSize: 12,
                                      ),
                                    ),
                                    const SizedBox(width: 2),
                                    const FixedArrowIcon(
                                      AppArrows.enter,
                                      color: AppColors.mintDark,
                                      size: 16,
                                    ),
                                  ],
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  static _NotifTone _toneFor(String? type) {
    final key = (type ?? '').toLowerCase();
    if (key.contains('reject') || key.contains('expired')) {
      return const _NotifTone(
        icon: Icons.cancel_outlined,
        bg: Color(0xFFFFEBE9),
        fg: AppColors.coral,
      );
    }
    if (key.contains('payment') || key.contains('accepted')) {
      return const _NotifTone(
        icon: Icons.payments_outlined,
        bg: Color(0xFFE8F8EF),
        fg: AppColors.mintDark,
      );
    }
    if (key.contains('slot') || key.contains('suggest')) {
      return const _NotifTone(
        icon: Icons.event_available_rounded,
        bg: Color(0xFFFFF4E5),
        fg: Color(0xFFD97706),
      );
    }
    if (key.contains('status') || key.contains('order')) {
      return const _NotifTone(
        icon: Icons.local_shipping_outlined,
        bg: Color(0xFFEEF4FF),
        fg: Color(0xFF3B82F6),
      );
    }
    return const _NotifTone(
      icon: Icons.notifications_rounded,
      bg: Color(0xFFFFF1E4),
      fg: AppColors.coralSoft,
    );
  }

  static String _relativeTime(String? raw) {
    if (raw == null || raw.isEmpty) return 'الآن';
    final date = DateTime.tryParse(raw)?.toLocal();
    if (date == null) return raw;

    final diff = DateTime.now().difference(date);
    if (diff.inMinutes < 1) return 'الآن';
    if (diff.inMinutes < 60) return 'منذ ${diff.inMinutes} د';
    if (diff.inHours < 24) return 'منذ ${diff.inHours} س';
    if (diff.inDays < 7) return 'منذ ${diff.inDays} يوم';
    final d = date.day.toString().padLeft(2, '0');
    final m = date.month.toString().padLeft(2, '0');
    return '$d/$m/${date.year}';
  }
}

class _NotifTone {
  const _NotifTone({
    required this.icon,
    required this.bg,
    required this.fg,
  });

  final IconData icon;
  final Color bg;
  final Color fg;
}
