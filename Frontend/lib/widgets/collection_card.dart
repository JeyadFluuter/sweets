import 'package:flutter/material.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/models/collection_model.dart';

/// نفس أسلوب كرت الأصناف: صورة + اسم + سعر + زر سلة
class CollectionCard extends StatelessWidget {
  const CollectionCard({
    super.key,
    required this.collection,
    required this.onTap,
    this.onAdd,
    this.width = 168,
  });

  final CollectionModel collection;
  final VoidCallback onTap;
  final VoidCallback? onAdd;
  final double? width;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(22),
        child: SizedBox(
          width: width,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Expanded(
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(22),
                  child: ColoredBox(
                    color: const Color(0xFFFFF3E8),
                    child: collection.imageUrl == null
                        ? const Icon(
                            Icons.card_giftcard_rounded,
                            size: 52,
                            color: AppColors.coral,
                          )
                        : Image.network(
                            collection.imageUrl!,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) =>
                                const Icon(
                              Icons.card_giftcard_rounded,
                              size: 52,
                              color: AppColors.coral,
                            ),
                          ),
                  ),
                ),
              ),
              const SizedBox(height: 10),
              Text(
                collection.name,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontWeight: FontWeight.w700,
                  fontSize: 14,
                  height: 1.2,
                  color: AppColors.textDark,
                ),
              ),
              const SizedBox(height: 8),
              Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          collection.displayPrice,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 15,
                            color: AppColors.textDark,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Material(
                    color: AppColors.mint,
                    shape: const CircleBorder(),
                    elevation: 0,
                    shadowColor: AppColors.mint.withValues(alpha: 0.4),
                    child: InkWell(
                      customBorder: const CircleBorder(),
                      onTap: onAdd ?? onTap,
                      child: const SizedBox(
                        width: 38,
                        height: 38,
                        child: Icon(
                          Icons.shopping_bag_outlined,
                          color: Colors.white,
                          size: 20,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
