import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/cart_controller.dart';
import 'package:sweet_store/controllers/checkout_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/models/order_model.dart';
import 'package:sweet_store/services/fulfillment_service.dart';
import 'package:sweet_store/services/order_service.dart';
import 'package:sweet_store/widgets/app_arrows.dart';
import 'package:sweet_store/widgets/app_button.dart';
import 'package:sweet_store/widgets/skeleton_box.dart';

class CheckoutView extends StatefulWidget {
  const CheckoutView({super.key});

  @override
  State<CheckoutView> createState() => _CheckoutViewState();
}

class _CheckoutViewState extends State<CheckoutView> {
  late final CheckoutController checkout;

  @override
  void initState() {
    super.initState();
    if (Get.isRegistered<CheckoutController>()) {
      Get.delete<CheckoutController>(force: true);
    }
    checkout = Get.put(
      CheckoutController(
        Get.find<FulfillmentService>(),
        Get.find<OrderService>(),
        Get.find<CartController>(),
      ),
    );
  }

  @override
  void dispose() {
    if (Get.isRegistered<CheckoutController>()) {
      Get.delete<CheckoutController>(force: true);
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final step = checkout.step.value;
      return Scaffold(
        backgroundColor: AppColors.background,
        appBar: AppBar(
          backgroundColor: AppColors.background,
          elevation: 0,
          leading: IconButton(
            onPressed: checkout.back,
            icon: const FixedArrowIcon(AppArrows.back),
          ),
          title: Text(
            step >= 3 ? 'تم إرسال الطلب' : 'تأكيد الطلب',
          ),
        ),
        body: Column(
          children: [
            if (step < 3)
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
                child: _CheckoutStepper(currentStep: step + 1),
              ),
            Expanded(child: _stepBody(checkout, step)),
            if (step < 3)
              SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                  child: AppButton(
                    label: step == 2 ? 'إرسال الطلب' : 'التالي',
                    loading: checkout.isSubmitting.value,
                    onPressed: () {
                      if (step == 2) {
                        checkout.submitOrder();
                      } else {
                        checkout.next();
                      }
                    },
                  ),
                ),
              ),
          ],
        ),
      );
    });
  }

  Widget _stepBody(CheckoutController c, int step) {
    switch (step) {
      case 0:
        return _DeliveryStep(controller: c);
      case 1:
        return _ScheduleStep(controller: c);
      case 2:
        return _ReviewStep(controller: c);
      default:
        return _SuccessStep(order: c.placedOrder.value);
    }
  }
}

class _CheckoutStepper extends StatelessWidget {
  const _CheckoutStepper({required this.currentStep});

  final int currentStep;

  @override
  Widget build(BuildContext context) {
    const labels = ['الاستلام', 'الموعد', 'المراجعة'];
    return Row(
      children: List.generate(labels.length * 2 - 1, (i) {
        if (i.isOdd) {
          final after = (i ~/ 2) + 1;
          return Expanded(
            child: Container(
              height: 3,
              margin: const EdgeInsets.symmetric(horizontal: 4),
              color: currentStep > after ? AppColors.mint : AppColors.border,
            ),
          );
        }
        final step = (i ~/ 2) + 1;
        final active = step == currentStep;
        final done = step < currentStep;
        return Column(
          children: [
            Container(
              width: 28,
              height: 28,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: done || active ? AppColors.mint : AppColors.fieldFill,
                shape: BoxShape.circle,
              ),
              child: done
                  ? const Icon(Icons.check, size: 14, color: Colors.white)
                  : Text(
                      '$step',
                      style: TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 12,
                        color: active ? Colors.white : AppColors.textMuted,
                      ),
                    ),
            ),
            const SizedBox(height: 4),
            Text(
              labels[step - 1],
              style: TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w700,
                color: active || done
                    ? AppColors.textDark
                    : AppColors.textMuted,
              ),
            ),
          ],
        );
      }),
    );
  }
}

class _DeliveryStep extends StatelessWidget {
  const _DeliveryStep({required this.controller});

  final CheckoutController controller;

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final type = controller.deliveryType.value;
      return ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
        children: [
          Text(
            'طريقة الاستلام',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _TypeCard(
                  title: 'استلام',
                  subtitle: 'من المعمل',
                  icon: Icons.storefront_rounded,
                  selected: type == 'pickup',
                  onTap: () => controller.setDeliveryType('pickup'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _TypeCard(
                  title: 'توصيل',
                  subtitle: 'إلى عنوانك',
                  icon: Icons.delivery_dining_rounded,
                  selected: type == 'delivery',
                  onTap: () => controller.setDeliveryType('delivery'),
                ),
              ),
            ],
          ),
          if (type == 'delivery') ...[
            const SizedBox(height: 18),
            TextField(
              onChanged: (v) => controller.addressText.value = v,
              maxLines: 2,
              decoration: const InputDecoration(
                labelText: 'عنوان التوصيل',
                hintText: 'المدينة — الحي — أقرب نقطة دالة',
              ),
            ),
          ],
          const SizedBox(height: 18),
          SwitchListTile.adaptive(
            contentPadding: EdgeInsets.zero,
            title: const Text(
              'المستلم أنا',
              style: TextStyle(fontWeight: FontWeight.w700),
            ),
            value: controller.recipientIsSelf.value,
            activeThumbColor: AppColors.mint,
            onChanged: (v) => controller.recipientIsSelf.value = v,
          ),
          if (!controller.recipientIsSelf.value) ...[
            TextField(
              onChanged: (v) => controller.recipientName.value = v,
              decoration: const InputDecoration(labelText: 'اسم المستلم'),
            ),
            const SizedBox(height: 12),
            TextField(
              onChanged: (v) => controller.recipientPhone.value = v,
              keyboardType: TextInputType.phone,
              decoration: const InputDecoration(
                labelText: 'هاتف المستلم',
                hintText: '09xxxxxxxx',
              ),
            ),
          ],
          const SizedBox(height: 12),
          TextField(
            onChanged: (v) => controller.customerNote.value = v,
            maxLines: 3,
            decoration: const InputDecoration(
              labelText: 'ملاحظة (اختياري)',
              hintText: 'مثال: بدون مكسرات',
            ),
          ),
        ],
      );
    });
  }
}

class _TypeCard extends StatelessWidget {
  const _TypeCard({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.selected,
    required this.onTap,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Ink(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: selected ? const Color(0xFFE8F8EF) : AppColors.surface,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(
            color: selected ? AppColors.mint : AppColors.border,
            width: selected ? 1.6 : 1,
          ),
        ),
        child: Column(
          children: [
            Icon(icon, color: AppColors.mintDark, size: 30),
            const SizedBox(height: 8),
            Text(title, style: const TextStyle(fontWeight: FontWeight.w800)),
            Text(
              subtitle,
              style: const TextStyle(
                color: AppColors.textMuted,
                fontSize: 12,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _ScheduleStep extends StatelessWidget {
  const _ScheduleStep({required this.controller});

  final CheckoutController controller;

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      if (controller.isLoadingDates.value && controller.dates.isEmpty) {
        return ListView(
          padding: const EdgeInsets.all(16),
          children: [
            const SkeletonBox(height: 28, width: 120, radius: 8),
            const SizedBox(height: 14),
            SizedBox(
              height: 96,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: 5,
                separatorBuilder: (context, index) =>
                    const SizedBox(width: 10),
                itemBuilder: (context, index) =>
                    const SkeletonBox(width: 72, height: 96, radius: 18),
              ),
            ),
            const SizedBox(height: 28),
            const SkeletonBox(height: 28, width: 100, radius: 8),
            const SizedBox(height: 14),
            const SkeletonBox(height: 72, radius: 18),
            const SizedBox(height: 10),
            const SkeletonBox(height: 72, radius: 18),
          ],
        );
      }

      if (controller.errorMessage.value != null && controller.dates.isEmpty) {
        return Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(controller.errorMessage.value!),
              const SizedBox(height: 12),
              ElevatedButton(
                onPressed: controller.loadDates,
                child: const Text('إعادة المحاولة'),
              ),
            ],
          ),
        );
      }

      if (controller.dates.isEmpty) {
        return const Center(
          child: Padding(
            padding: EdgeInsets.all(24),
            child: Text(
              'لا توجد تواريخ متاحة حالياً. جرّب لاحقاً أو تواصل مع المعمل.',
              textAlign: TextAlign.center,
            ),
          ),
        );
      }

      final selectedDate = controller.selectedDate.value;
      final selectedSlot = controller.selectedSlot.value;

      return ListView(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 28),
        children: [
          if (selectedDate != null)
            _SelectionSummary(
              dateLabel: _DateLabels.long(selectedDate),
              timeLabel: selectedSlot?.label,
            ),
          const SizedBox(height: 18),
          Row(
            children: [
              Text(
                'اليوم',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
              ),
              const Spacer(),
              Text(
                '${controller.dates.length} يوم متاح',
                style: const TextStyle(
                  color: AppColors.textMuted,
                  fontWeight: FontWeight.w600,
                  fontSize: 12,
                ),
              ),
            ],
          ),
          const SizedBox(height: 6),
          const Text(
            'مرّر واختر اليوم المناسب',
            style: TextStyle(color: AppColors.textMuted, fontSize: 13),
          ),
          const SizedBox(height: 14),
          SizedBox(
            height: 108,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: controller.dates.length,
              separatorBuilder: (context, index) => const SizedBox(width: 10),
              itemBuilder: (context, i) {
                final date = controller.dates[i];
                final selected = selectedDate == date;
                final labels = _DateLabels.parse(date);
                return _DateCard(
                  weekday: labels.weekday,
                  day: labels.day,
                  month: labels.month,
                  isToday: labels.isToday,
                  selected: selected,
                  onTap: () => controller.selectDate(date),
                );
              },
            ),
          ),
          const SizedBox(height: 26),
          Row(
            children: [
              Text(
                'الفترة',
                style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
              ),
              const Spacer(),
              if (selectedDate != null)
                Text(
                  _DateLabels.short(selectedDate),
                  style: const TextStyle(
                    color: AppColors.mintDark,
                    fontWeight: FontWeight.w700,
                    fontSize: 12,
                  ),
                ),
            ],
          ),
          const SizedBox(height: 6),
          const Text(
            'اختر ساعة الاستلام أو التوصيل',
            style: TextStyle(color: AppColors.textMuted, fontSize: 13),
          ),
          const SizedBox(height: 14),
          if (controller.isLoadingSlots.value)
            GridView.count(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisCount: 2,
              mainAxisSpacing: 10,
              crossAxisSpacing: 10,
              childAspectRatio: 1.55,
              children: List.generate(
                4,
                (_) => const SkeletonBox(radius: 18),
              ),
            )
          else if (controller.slots.isEmpty)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: AppColors.border),
              ),
              child: const Text(
                'لا توجد فترات لهذا اليوم. جرّب يوماً آخر.',
                textAlign: TextAlign.center,
                style: TextStyle(color: AppColors.textMuted),
              ),
            )
          else
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: controller.slots.length,
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 2,
                mainAxisSpacing: 10,
                crossAxisSpacing: 10,
                childAspectRatio: 1.45,
              ),
              itemBuilder: (context, i) {
                final slot = controller.slots[i];
                final selected = selectedSlot?.slotId == slot.slotId &&
                    selectedSlot?.start == slot.start;
                return _SlotCard(
                  start: slot.timeForOrder,
                  end: slot.end.length >= 5
                      ? slot.end.substring(0, 5)
                      : slot.end,
                  remaining: slot.remaining,
                  selected: selected,
                  onTap: () => controller.selectSlot(slot),
                );
              },
            ),
        ],
      );
    });
  }
}

class _SelectionSummary extends StatelessWidget {
  const _SelectionSummary({required this.dateLabel, this.timeLabel});

  final String dateLabel;
  final String? timeLabel;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            AppColors.mint.withValues(alpha: 0.18),
            const Color(0xFFE8F8EF),
          ],
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.mint.withValues(alpha: 0.35)),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: const BoxDecoration(
              color: AppColors.mint,
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.event_available_rounded,
              color: Colors.white,
              size: 22,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'موعدك المختار',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textMuted,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  timeLabel == null ? dateLabel : '$dateLabel · $timeLabel',
                  style: const TextStyle(
                    fontWeight: FontWeight.w800,
                    fontSize: 14,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _DateCard extends StatelessWidget {
  const _DateCard({
    required this.weekday,
    required this.day,
    required this.month,
    required this.selected,
    required this.onTap,
    this.isToday = false,
  });

  final String weekday;
  final String day;
  final String month;
  final bool selected;
  final bool isToday;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(20),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          width: 76,
          padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
          decoration: BoxDecoration(
            color: selected ? AppColors.mint : AppColors.surface,
            borderRadius: BorderRadius.circular(20),
            border: Border.all(
              color: selected
                  ? AppColors.mint
                  : isToday
                      ? AppColors.mint.withValues(alpha: 0.45)
                      : AppColors.border,
              width: selected || isToday ? 1.5 : 1,
            ),
            boxShadow: selected
                ? [
                    BoxShadow(
                      color: AppColors.mint.withValues(alpha: 0.28),
                      blurRadius: 14,
                      offset: const Offset(0, 6),
                    ),
                  ]
                : [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.03),
                      blurRadius: 8,
                      offset: const Offset(0, 3),
                    ),
                  ],
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                weekday,
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: selected ? Colors.white70 : AppColors.textMuted,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                day,
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.w800,
                  height: 1,
                  color: selected ? Colors.white : AppColors.textDark,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                month,
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: selected ? Colors.white70 : AppColors.textMuted,
                ),
              ),
              if (isToday && !selected) ...[
                const SizedBox(height: 4),
                Container(
                  width: 6,
                  height: 6,
                  decoration: const BoxDecoration(
                    color: AppColors.mint,
                    shape: BoxShape.circle,
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _SlotCard extends StatelessWidget {
  const _SlotCard({
    required this.start,
    required this.end,
    required this.remaining,
    required this.selected,
    required this.onTap,
  });

  final String start;
  final String end;
  final int remaining;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final low = remaining <= 2;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          padding: const EdgeInsets.fromLTRB(12, 12, 12, 10),
          decoration: BoxDecoration(
            color: selected ? const Color(0xFFE8F8EF) : AppColors.surface,
            borderRadius: BorderRadius.circular(18),
            border: Border.all(
              color: selected ? AppColors.mint : AppColors.border,
              width: selected ? 1.7 : 1,
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: selected ? 0.06 : 0.03),
                blurRadius: selected ? 14 : 8,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(
                    Icons.schedule_rounded,
                    size: 18,
                    color: selected ? AppColors.mintDark : AppColors.textMuted,
                  ),
                  const Spacer(),
                  if (selected)
                    const Icon(
                      Icons.check_circle_rounded,
                      size: 18,
                      color: AppColors.mint,
                    ),
                ],
              ),
              const Spacer(),
              Text(
                start,
                style: TextStyle(
                  fontWeight: FontWeight.w800,
                  fontSize: 20,
                  color: selected ? AppColors.mintDark : AppColors.textDark,
                ),
              ),
              Text(
                'إلى $end',
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: AppColors.textMuted,
                ),
              ),
              const SizedBox(height: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: low
                      ? AppColors.coral.withValues(alpha: 0.12)
                      : AppColors.fieldFill,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  remaining <= 0
                      ? 'مكتمل'
                      : low
                          ? 'متبقي $remaining فقط'
                          : 'متبقي $remaining',
                  style: TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    color: low ? AppColors.coral : AppColors.textMuted,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _DateLabels {
  const _DateLabels({
    required this.weekday,
    required this.day,
    required this.month,
    required this.isToday,
  });

  final String weekday;
  final String day;
  final String month;
  final bool isToday;

  static const _weekdays = [
    'إثن',
    'ثلا',
    'أرب',
    'خمي',
    'جمع',
    'سبت',
    'أحد',
  ];

  static const _weekdaysFull = [
    'الإثنين',
    'الثلاثاء',
    'الأربعاء',
    'الخميس',
    'الجمعة',
    'السبت',
    'الأحد',
  ];

  static const _months = [
    'يناير',
    'فبراير',
    'مارس',
    'أبريل',
    'مايو',
    'يونيو',
    'يوليو',
    'أغسطس',
    'سبتمبر',
    'أكتوبر',
    'نوفمبر',
    'ديسمبر',
  ];

  static _DateLabels parse(String raw) {
    final dt = DateTime.tryParse(raw);
    if (dt == null) {
      return const _DateLabels(
        weekday: '—',
        day: '—',
        month: '',
        isToday: false,
      );
    }
    final now = DateTime.now();
    final isToday =
        dt.year == now.year && dt.month == now.month && dt.day == now.day;
    return _DateLabels(
      weekday: _weekdays[dt.weekday - 1],
      day: '${dt.day}',
      month: _months[dt.month - 1],
      isToday: isToday,
    );
  }

  static String short(String raw) {
    final dt = DateTime.tryParse(raw);
    if (dt == null) return raw;
    return '${_weekdaysFull[dt.weekday - 1]} ${dt.day}';
  }

  static String long(String raw) {
    final dt = DateTime.tryParse(raw);
    if (dt == null) return raw;
    final now = DateTime.now();
    final isToday =
        dt.year == now.year && dt.month == now.month && dt.day == now.day;
    final label =
        '${_weekdaysFull[dt.weekday - 1]} · ${dt.day} ${_months[dt.month - 1]}';
    return isToday ? 'اليوم · ${dt.day} ${_months[dt.month - 1]}' : label;
  }
}

class _ReviewStep extends StatelessWidget {
  const _ReviewStep({required this.controller});

  final CheckoutController controller;

  @override
  Widget build(BuildContext context) {
    final cart = Get.find<CartController>().cart.value;
    return Obx(() {
      final c = controller;
      return ListView(
        padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
        children: [
          Text(
            'مراجعة الطلب',
            style: Theme.of(context).textTheme.titleLarge?.copyWith(
                  fontWeight: FontWeight.w800,
                ),
          ),
          const SizedBox(height: 14),
          _InfoCard(
            rows: [
              (
                'الاستلام',
                c.deliveryType.value == 'delivery' ? 'توصيل' : 'من المعمل'
              ),
              if (c.deliveryType.value == 'delivery')
                ('العنوان', c.addressText.value.trim()),
              ('التاريخ', c.selectedDate.value ?? '—'),
              ('الوقت', c.selectedSlot.value?.timeForOrder ?? '—'),
              (
                'المستلم',
                c.recipientIsSelf.value
                    ? 'أنا'
                    : '${c.recipientName.value} · ${c.recipientPhone.value}'
              ),
              if (c.customerNote.value.trim().isNotEmpty)
                ('ملاحظة', c.customerNote.value.trim()),
            ],
          ),
          const SizedBox(height: 14),
          _InfoCard(
            rows: [
              ('الأصناف', '${cart.itemsCount}'),
              ('المجموع الفرعي', cart.displaySubtotal),
              if (cart.discount > 0) ('الخصم', '- ${cart.displayDiscount}'),
              ('إجمالي السلة', cart.displayTotal),
            ],
          ),
          const SizedBox(height: 10),
          const Text(
            'قد تُضاف رسوم توصيل عند اختيار التوصيل حسب إعدادات المعمل.',
            style: TextStyle(
              color: AppColors.textMuted,
              fontSize: 12,
              height: 1.4,
            ),
          ),
        ],
      );
    });
  }
}

class _InfoCard extends StatelessWidget {
  const _InfoCard({required this.rows});

  final List<(String, String)> rows;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(18),
      ),
      child: Column(
        children: [
          for (var i = 0; i < rows.length; i++) ...[
            if (i > 0) const SizedBox(height: 10),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  rows[i].$1,
                  style: const TextStyle(
                    color: AppColors.textMuted,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    rows[i].$2,
                    textAlign: TextAlign.end,
                    style: const TextStyle(fontWeight: FontWeight.w800),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}

class _SuccessStep extends StatelessWidget {
  const _SuccessStep({required this.order});

  final OrderModel? order;

  @override
  Widget build(BuildContext context) {
    final o = order;
    return Padding(
      padding: const EdgeInsets.all(24),
      child: Column(
        children: [
          const Spacer(),
          Container(
            width: 96,
            height: 96,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: AppColors.mint.withValues(alpha: 0.18),
            ),
            child: const Icon(
              Icons.check_rounded,
              size: 48,
              color: AppColors.mintDark,
            ),
          ),
          const SizedBox(height: 20),
          const Text(
            'تم إرسال طلبك للمراجعة',
            style: TextStyle(fontWeight: FontWeight.w800, fontSize: 20),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          Text(
            o == null
                ? 'سنُعلمك عند قبول الطلب لإتمام الدفع إن لزم.'
                : 'رقم الطلب ${o.number ?? o.id}\nالحالة: ${o.statusLabel ?? o.status ?? '—'}\nالموعد: ${o.fulfillmentLabel}',
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: AppColors.textMuted,
              height: 1.5,
            ),
          ),
          const Spacer(),
          AppButton(
            label: 'تفاصيل الطلب',
            onPressed: o == null
                ? null
                : () => Get.offNamed(
                      AppRoutes.orderDetail.replaceFirst(':id', '${o.id}'),
                    ),
          ),
          const SizedBox(height: 10),
          TextButton(
            onPressed: () => Get.offAllNamed(AppRoutes.main),
            child: const Text('العودة للرئيسية'),
          ),
        ],
      ),
    );
  }
}
