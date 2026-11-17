import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/constants/app_strings.dart';

class BrandLogo extends StatelessWidget {
  const BrandLogo({
    super.key,
    this.size = 120,
    this.showTitle = true,
    this.titleColor = AppColors.textDark,
    this.onYellow = false,
  });

  final double size;
  final bool showTitle;
  final Color titleColor;
  final bool onYellow;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: size,
          height: size,
          decoration: BoxDecoration(
            color: Colors.white,
            shape: BoxShape.circle,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: onYellow ? 0.08 : 0.06),
                blurRadius: 24,
                offset: const Offset(0, 8),
              ),
            ],
          ),
          child: CustomPaint(
            painter: _CupcakePainter(color: AppColors.coral),
          ),
        ),
        if (showTitle) ...[
          const SizedBox(height: 18),
          Text(
            AppStrings.appName,
            style: GoogleFonts.fredoka(
              fontSize: size * 0.28,
              fontWeight: FontWeight.w600,
              color: titleColor,
              letterSpacing: 0.4,
            ),
          ),
        ],
      ],
    );
  }
}

class _CupcakePainter extends CustomPainter {
  _CupcakePainter({required this.color});

  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = size.width * 0.045
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round;

    final cx = size.width / 2;
    final cy = size.height / 2;

    // cherry
    canvas.drawCircle(
      Offset(cx, cy - size.height * 0.28),
      size.width * 0.045,
      paint,
    );
    // stem
    canvas.drawLine(
      Offset(cx, cy - size.height * 0.22),
      Offset(cx + size.width * 0.04, cy - size.height * 0.34),
      paint,
    );

    // frosting swirl
    final frosting = Path()
      ..moveTo(cx - size.width * 0.22, cy - size.height * 0.06)
      ..quadraticBezierTo(
        cx - size.width * 0.18,
        cy - size.height * 0.22,
        cx,
        cy - size.height * 0.18,
      )
      ..quadraticBezierTo(
        cx + size.width * 0.18,
        cy - size.height * 0.22,
        cx + size.width * 0.22,
        cy - size.height * 0.06,
      );
    canvas.drawPath(frosting, paint);

    // cup body
    final cup = Path()
      ..moveTo(cx - size.width * 0.2, cy - size.height * 0.02)
      ..lineTo(cx - size.width * 0.14, cy + size.height * 0.22)
      ..quadraticBezierTo(
        cx,
        cy + size.height * 0.28,
        cx + size.width * 0.14,
        cy + size.height * 0.22,
      )
      ..lineTo(cx + size.width * 0.2, cy - size.height * 0.02);
    canvas.drawPath(cup, paint);

    // wrapper lines
    for (final dx in [-0.08, 0.0, 0.08]) {
      canvas.drawLine(
        Offset(cx + size.width * dx, cy + size.height * 0.02),
        Offset(cx + size.width * dx * 0.7, cy + size.height * 0.18),
        paint,
      );
    }
  }

  @override
  bool shouldRepaint(covariant _CupcakePainter oldDelegate) =>
      oldDelegate.color != color;
}
