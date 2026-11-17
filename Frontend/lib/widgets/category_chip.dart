import 'package:flutter/material.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/models/category_model.dart';

class CategoryChip extends StatelessWidget {
  const CategoryChip({
    super.key,
    required this.category,
    required this.onTap,
    this.color = const Color(0xFFFFF3E0),
    this.compact = false,
    this.selected = false,
  });

  final CategoryModel category;
  final VoidCallback onTap;
  final Color color;
  final bool compact;
  final bool selected;

  @override
  Widget build(BuildContext context) {
    final size = compact ? 52.0 : 68.0;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: SizedBox(
        width: compact ? 72 : 84,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 180),
              width: size,
              height: size,
              decoration: BoxDecoration(
                color: selected ? AppColors.mint : color,
                shape: BoxShape.circle,
                border: selected
                    ? Border.all(color: AppColors.mintDark, width: 2.5)
                    : null,
                boxShadow: [
                  BoxShadow(
                    color: (selected ? AppColors.mint : color)
                        .withValues(alpha: 0.45),
                    blurRadius: selected ? 14 : 12,
                    offset: const Offset(0, 5),
                  ),
                ],
              ),
              clipBehavior: Clip.antiAlias,
              child: category.imageUrl == null
                  ? Center(
                      child: Text(
                        category.name.isNotEmpty
                            ? category.name.characters.first
                            : '?',
                        style: TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: compact ? 18 : 22,
                          color: selected ? Colors.white : AppColors.textDark,
                        ),
                      ),
                    )
                  : ColorFiltered(
                      colorFilter: selected
                          ? const ColorFilter.mode(
                              Colors.black26,
                              BlendMode.darken,
                            )
                          : const ColorFilter.mode(
                              Colors.transparent,
                              BlendMode.dst,
                            ),
                      child: Image.network(
                        category.imageUrl!,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) => Center(
                          child: Text(
                            category.name.isNotEmpty
                                ? category.name.characters.first
                                : '?',
                            style: TextStyle(
                              fontWeight: FontWeight.w800,
                              fontSize: compact ? 18 : 22,
                              color: selected
                                  ? Colors.white
                                  : AppColors.textDark,
                            ),
                          ),
                        ),
                      ),
                    ),
            ),
            const SizedBox(height: 8),
            Text(
              category.name,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    fontWeight: FontWeight.w700,
                    color: selected ? AppColors.mintDark : AppColors.textDark,
                  ),
            ),
          ],
        ),
      ),
    );
  }
}
