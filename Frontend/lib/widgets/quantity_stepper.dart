import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/quantity_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';

class QuantityStepper extends StatelessWidget {
  const QuantityStepper({
    super.key,
    required this.controller,
    this.showWriteHint = false,
  });

  final QuantityController controller;
  final bool showWriteHint;

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      final value = controller.qty.value;
      final hint = controller.limitHint.value;
      final atMin = value <= controller.min;
      final atMax = value >= controller.max;

      return Column(
        crossAxisAlignment: CrossAxisAlignment.end,
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            decoration: BoxDecoration(
              color: AppColors.fieldFill,
              borderRadius: BorderRadius.circular(28),
              border: Border.all(color: AppColors.border),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                _RoundBtn(
                  icon: Icons.remove_rounded,
                  muted: atMin,
                  onTap: controller.minus,
                ),
                InkWell(
                  onTap: () => controller.editManually(context),
                  borderRadius: BorderRadius.circular(10),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 10,
                    ),
                    child: Text(
                      '$value',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w800,
                            decoration: TextDecoration.underline,
                            decorationColor: AppColors.textHint,
                            decorationStyle: TextDecorationStyle.dotted,
                          ),
                    ),
                  ),
                ),
                _RoundBtn(
                  icon: Icons.add_rounded,
                  muted: atMax,
                  onTap: controller.plus,
                ),
              ],
            ),
          ),
          if (hint != null || showWriteHint) ...[
            const SizedBox(height: 6),
            Text(
              hint ?? 'اضغط الرقم للكتابة',
              style: TextStyle(
                color: hint == null ? AppColors.textHint : AppColors.coral,
                fontSize: 11,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ],
      );
    });
  }
}

class _RoundBtn extends StatelessWidget {
  const _RoundBtn({
    required this.icon,
    required this.onTap,
    this.muted = false,
  });

  final IconData icon;
  final VoidCallback onTap;
  final bool muted;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(24),
        child: SizedBox(
          width: 42,
          height: 42,
          child: Icon(
            icon,
            color: muted ? AppColors.textHint : AppColors.textDark,
          ),
        ),
      ),
    );
  }
}
