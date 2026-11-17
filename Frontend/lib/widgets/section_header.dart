import 'package:flutter/material.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/widgets/app_arrows.dart';

class SectionHeader extends StatelessWidget {
  const SectionHeader({
    super.key,
    required this.title,
    this.onSeeMore,
    this.seeMoreLabel = 'عرض المزيد',
  });

  final String title;
  final VoidCallback? onSeeMore;
  final String seeMoreLabel;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 12),
      child: Row(
        children: [
          Expanded(
            child: Text(
              title,
              style: Theme.of(context).textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.w800,
                  ),
            ),
          ),
          if (onSeeMore != null)
            TextButton(
              onPressed: onSeeMore,
              style: TextButton.styleFrom(
                foregroundColor: AppColors.mintDark,
                padding: const EdgeInsets.symmetric(horizontal: 4),
                minimumSize: Size.zero,
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    seeMoreLabel,
                    style: const TextStyle(fontWeight: FontWeight.w700),
                  ),
                  const SizedBox(width: 2),
                  const EnterChevron(color: AppColors.mintDark),
                ],
              ),
            ),
        ],
      ),
    );
  }
}
