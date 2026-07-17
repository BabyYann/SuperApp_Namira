import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:superapp_namira_flutter/config/theme.dart';

class NamiraBadge extends StatelessWidget {
  final String label;
  final Color? color;
  final Color? textColor;
  final bool isSmall;
  final bool isOutlined;
  final IconData? icon;

  const NamiraBadge({
    super.key,
    required this.label,
    this.color,
    this.textColor,
    this.isSmall = false,
    this.isOutlined = false,
    this.icon,
  });

  NamiraBadge.role({
    super.key,
    required String role,
  })  : label = role.replaceAll('_', ' ').toUpperCase(),
        color = AppColors.primary,
        textColor = Colors.white,
        isSmall = false,
        isOutlined = false,
        icon = null;

  const NamiraBadge.status({
    super.key,
    required this.label,
    required Color statusColor,
  })  : color = statusColor,
        textColor = Colors.white,
        isSmall = false,
        isOutlined = false,
        icon = null;

  const NamiraBadge.outlined({
    super.key,
    required this.label,
    required Color borderColor,
  })  : color = borderColor,
        textColor = borderColor,
        isSmall = false,
        isOutlined = true,
        icon = null;

  @override
  Widget build(BuildContext context) {
    final effectiveColor = color ?? AppColors.primary;
    final effectiveTextColor = textColor ?? Colors.white;
    final fontSize = isSmall ? 10.0 : 11.0;
    final horizontalPadding = isSmall ? 6.0 : 8.0;
    final verticalPadding = isSmall ? 2.0 : 3.0;

    if (isOutlined) {
      return Container(
        padding: EdgeInsets.symmetric(
          horizontal: horizontalPadding,
          vertical: verticalPadding,
        ),
        decoration: BoxDecoration(
          color: Colors.transparent,
          borderRadius: BorderRadius.circular(6),
          border: Border.all(color: effectiveColor, width: 1),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (icon != null) ...[
              Icon(icon, size: 12, color: effectiveColor),
              const SizedBox(width: 4),
            ],
            Text(
              label,
              style: GoogleFonts.plusJakartaSans(
                fontSize: fontSize,
                fontWeight: FontWeight.w600,
                color: effectiveColor,
                letterSpacing: 0.3,
              ),
            ),
          ],
        ),
      );
    }

    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: horizontalPadding,
        vertical: verticalPadding,
      ),
      decoration: BoxDecoration(
        color: effectiveColor,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, size: 12, color: effectiveTextColor),
            const SizedBox(width: 4),
          ],
          Text(
            label,
            style: GoogleFonts.plusJakartaSans(
              fontSize: fontSize,
              fontWeight: FontWeight.w600,
              color: effectiveTextColor,
              letterSpacing: 0.3,
            ),
          ),
        ],
      ),
    );
  }
}
