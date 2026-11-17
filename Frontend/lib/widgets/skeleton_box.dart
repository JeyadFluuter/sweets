import 'package:flutter/material.dart';
import 'package:skeletonizer/skeletonizer.dart';

/// صندوق هيكل عظمي بسيط مبني على مكتبة skeletonizer
class SkeletonBox extends StatelessWidget {
  const SkeletonBox({
    super.key,
    this.width,
    this.height = 16,
    this.radius = 12,
  });

  final double? width;
  final double height;
  final double radius;

  @override
  Widget build(BuildContext context) {
    return Skeletonizer(
      enabled: true,
      effect: const ShimmerEffect(
        baseColor: Color(0xFFECECEC),
        highlightColor: Color(0xFFF7F7F7),
      ),
      child: Bone(
        width: width,
        height: height,
        borderRadius: BorderRadius.circular(radius),
      ),
    );
  }
}

class HomeSkeleton extends StatelessWidget {
  const HomeSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return Skeletonizer(
      enabled: true,
      effect: const ShimmerEffect(
        baseColor: Color(0xFFECECEC),
        highlightColor: Color(0xFFF7F7F7),
      ),
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
        children: [
          const Bone(height: 44, width: 44, borderRadius: BorderRadius.all(Radius.circular(14))),
          const SizedBox(height: 14),
          const Bone(height: 168, borderRadius: BorderRadius.all(Radius.circular(22))),
          const SizedBox(height: 10),
          const Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Bone(width: 16, height: 7, borderRadius: BorderRadius.all(Radius.circular(8))),
              SizedBox(width: 6),
              Bone(width: 7, height: 7, borderRadius: BorderRadius.all(Radius.circular(8))),
              SizedBox(width: 6),
              Bone(width: 7, height: 7, borderRadius: BorderRadius.all(Radius.circular(8))),
            ],
          ),
          const SizedBox(height: 14),
          const Bone(height: 48, borderRadius: BorderRadius.all(Radius.circular(18))),
          const SizedBox(height: 22),
          const Row(
            children: [
              Expanded(child: Bone(height: 22, borderRadius: BorderRadius.all(Radius.circular(8)))),
              SizedBox(width: 40),
              Bone(width: 70, height: 18, borderRadius: BorderRadius.all(Radius.circular(8))),
            ],
          ),
          const SizedBox(height: 14),
          SizedBox(
            height: 96,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: 5,
              separatorBuilder: (context, index) => const SizedBox(width: 12),
              itemBuilder: (context, index) => const Column(
                children: [
                  Bone.circle(size: 64),
                  SizedBox(height: 8),
                  Bone(width: 54, height: 12, borderRadius: BorderRadius.all(Radius.circular(6))),
                ],
              ),
            ),
          ),
          const SizedBox(height: 22),
          const Row(
            children: [
              Expanded(child: Bone(height: 22, borderRadius: BorderRadius.all(Radius.circular(8)))),
              SizedBox(width: 40),
              Bone(width: 70, height: 18, borderRadius: BorderRadius.all(Radius.circular(8))),
            ],
          ),
          const SizedBox(height: 14),
          SizedBox(
            height: 248,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: 3,
              separatorBuilder: (context, index) => const SizedBox(width: 14),
              itemBuilder: (context, index) => const Bone(
                width: 168,
                height: 248,
                borderRadius: BorderRadius.all(Radius.circular(22)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// هيكل تحميل شاشة المساعد الذكي
class AiChatSkeleton extends StatelessWidget {
  const AiChatSkeleton({super.key});

  @override
  Widget build(BuildContext context) {
    return Skeletonizer(
      enabled: true,
      effect: const ShimmerEffect(
        baseColor: Color(0xFFE4EEE8),
        highlightColor: Color(0xFFF5FAF7),
      ),
      child: ListView(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        children: [
          const Align(
            alignment: AlignmentDirectional.centerStart,
            child: Bone(
              width: 72,
              height: 12,
              borderRadius: BorderRadius.all(Radius.circular(8)),
            ),
          ),
          const SizedBox(height: 8),
          const Align(
            alignment: AlignmentDirectional.centerStart,
            child: Bone(
              width: 260,
              height: 72,
              borderRadius: BorderRadius.only(
                topLeft: Radius.circular(18),
                topRight: Radius.circular(18),
                bottomLeft: Radius.circular(6),
                bottomRight: Radius.circular(18),
              ),
            ),
          ),
          const SizedBox(height: 14),
          const Align(
            alignment: AlignmentDirectional.centerEnd,
            child: Bone(
              width: 180,
              height: 48,
              borderRadius: BorderRadius.only(
                topLeft: Radius.circular(18),
                topRight: Radius.circular(18),
                bottomLeft: Radius.circular(18),
                bottomRight: Radius.circular(6),
              ),
            ),
          ),
          const SizedBox(height: 14),
          const Align(
            alignment: AlignmentDirectional.centerStart,
            child: Bone(
              width: 220,
              height: 58,
              borderRadius: BorderRadius.only(
                topLeft: Radius.circular(18),
                topRight: Radius.circular(18),
                bottomLeft: Radius.circular(6),
                bottomRight: Radius.circular(18),
              ),
            ),
          ),
          const SizedBox(height: 16),
          const Bone(
            height: 150,
            borderRadius: BorderRadius.all(Radius.circular(20)),
          ),
          const SizedBox(height: 10),
          const Bone(
            height: 110,
            borderRadius: BorderRadius.all(Radius.circular(18)),
          ),
          const SizedBox(height: 18),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: const [
              Bone(width: 120, height: 36, borderRadius: BorderRadius.all(Radius.circular(18))),
              Bone(width: 140, height: 36, borderRadius: BorderRadius.all(Radius.circular(18))),
              Bone(width: 110, height: 36, borderRadius: BorderRadius.all(Radius.circular(18))),
            ],
          ),
        ],
      ),
    );
  }
}
