import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:superapp_namira_flutter/config/theme.dart';

class StitchBadge extends StatelessWidget {
  final String label;
  final Color backgroundColor;
  final Color textColor;
  final bool isSmall;

  const StitchBadge({
    super.key,
    required this.label,
    this.backgroundColor = AppColors.primaryContainer,
    this.textColor = AppColors.onPrimaryContainer,
    this.isSmall = false,
  });

  factory StitchBadge.success(String label) =>
      StitchBadge(label: label, backgroundColor: const Color(0xFFC6F6D5), textColor: const Color(0xFF22543D));

  factory StitchBadge.warning(String label) =>
      StitchBadge(label: label, backgroundColor: const Color(0xFFFEFCBF), textColor: const Color(0xFF744210));

  factory StitchBadge.error(String label) =>
      StitchBadge(label: label, backgroundColor: AppColors.errorContainer, textColor: AppColors.onErrorContainer);

  factory StitchBadge.info(String label) =>
      StitchBadge(label: label, backgroundColor: AppColors.secondaryContainer, textColor: AppColors.onSecondaryContainer);

  factory StitchBadge.primary(String label) =>
      StitchBadge(label: label, backgroundColor: AppColors.primary.withAlpha(26), textColor: AppColors.primary);

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: isSmall ? 6 : 8,
        vertical: isSmall ? 2 : 3,
      ),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label.toUpperCase(),
        style: GoogleFonts.plusJakartaSans(
          fontSize: isSmall ? 9 : 10,
          fontWeight: FontWeight.w700,
          color: textColor,
          letterSpacing: 0.5,
          height: 1.2,
        ),
      ),
    );
  }
}
