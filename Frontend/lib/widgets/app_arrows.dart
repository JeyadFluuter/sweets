import 'package:flutter/material.dart';
import 'package:sweet_store/core/constants/app_colors.dart';

/// أسهم ثابتة لاتنعكس مع RTL:
/// دخول / عرض المزيد → يسار `<`
/// رجوع / خروج → يمين `>`
class AppArrows {
  AppArrows._();

  static const IconData enter = Icons.chevron_left_rounded; // <
  static const IconData back = Icons.chevron_right_rounded; // >
}

class FixedArrowIcon extends StatelessWidget {
  const FixedArrowIcon(
    this.icon, {
    super.key,
    this.size = 24,
    this.color = AppColors.textDark,
  });

  final IconData icon;
  final double size;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.ltr,
      child: Icon(icon, size: size, color: color),
    );
  }
}

class AppBackButton extends StatelessWidget {
  const AppBackButton({super.key, this.onPressed, this.color});

  final VoidCallback? onPressed;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return IconButton(
      tooltip: 'رجوع',
      onPressed: onPressed ?? () => Navigator.of(context).maybePop(),
      icon: FixedArrowIcon(
        AppArrows.back,
        color: color ?? AppColors.textDark,
      ),
    );
  }
}

class EnterChevron extends StatelessWidget {
  const EnterChevron({super.key, this.color = AppColors.textMuted});

  final Color color;

  @override
  Widget build(BuildContext context) {
    return FixedArrowIcon(AppArrows.enter, color: color, size: 22);
  }
}
