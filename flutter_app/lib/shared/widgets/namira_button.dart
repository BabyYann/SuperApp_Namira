import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:superapp_namira_flutter/config/theme.dart';

class NamiraButton extends StatelessWidget {
  final String label;
  final VoidCallback? onPressed;
  final IconData? icon;
  final bool isLoading;
  final bool isOutlined;
  final bool isSecondary;
  final Color? color;
  final double? width;
  final double? height;

  const NamiraButton({
    super.key,
    required this.label,
    this.onPressed,
    this.icon,
    this.isLoading = false,
    this.isOutlined = false,
    this.isSecondary = false,
    this.color,
    this.width,
    this.height,
  });

  const NamiraButton.outlined({
    super.key,
    required this.label,
    this.onPressed,
    this.icon,
    this.isLoading = false,
    this.isSecondary = false,
    this.color,
    this.width,
    this.height,
  }) : isOutlined = true;

  const NamiraButton.secondary({
    super.key,
    required this.label,
    this.onPressed,
    this.icon,
    this.isLoading = false,
    this.color,
    this.width,
    this.height,
  })  : isOutlined = false,
        isSecondary = true;

  Color get _effectiveColor => color ?? AppColors.primary;

  @override
  Widget build(BuildContext context) {
    if (isOutlined) {
      return SizedBox(
        width: width,
        height: height ?? 48,
        child: OutlinedButton.icon(
          onPressed: isLoading ? null : onPressed,
          icon: isLoading
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: AppColors.primary,
                  ),
                )
              : Icon(icon ?? Icons.arrow_forward, size: 18),
          label: Text(label),
          style: OutlinedButton.styleFrom(
            foregroundColor: _effectiveColor,
            side: BorderSide(color: _effectiveColor),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(10),
            ),
            textStyle: GoogleFonts.plusJakartaSans(
              fontSize: 14,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      );
    }

    if (isSecondary) {
      return SizedBox(
        width: width,
        height: height ?? 48,
        child: ElevatedButton.icon(
          onPressed: isLoading ? null : onPressed,
          icon: isLoading
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(
                    strokeWidth: 2,
                    color: Colors.white,
                  ),
                )
              : Icon(icon ?? Icons.arrow_forward, size: 18),
          label: Text(label),
          style: ElevatedButton.styleFrom(
            backgroundColor: _effectiveColor.withAlpha(26),
            foregroundColor: _effectiveColor,
            elevation: 0,
            shadowColor: Colors.transparent,
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(10),
            ),
            textStyle: GoogleFonts.plusJakartaSans(
              fontSize: 14,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      );
    }

    return SizedBox(
      width: width,
      height: height ?? 48,
      child: ElevatedButton.icon(
        onPressed: isLoading ? null : onPressed,
        icon: isLoading
            ? const SizedBox(
                width: 18,
                height: 18,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  color: Colors.white,
                ),
              )
            : Icon(icon ?? Icons.arrow_forward, size: 18),
        label: Text(label),
        style: ElevatedButton.styleFrom(
          backgroundColor: _effectiveColor,
          foregroundColor: Colors.white,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(10),
          ),
          textStyle: GoogleFonts.plusJakartaSans(
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
    );
  }
}
