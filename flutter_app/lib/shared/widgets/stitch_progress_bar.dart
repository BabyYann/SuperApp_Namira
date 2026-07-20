import 'package:flutter/material.dart';
import 'package:superapp_namira_flutter/config/theme.dart';

class StitchProgressBar extends StatelessWidget {
  final double value;
  final double height;
  final Color? barColor;
  final Color? trackColor;

  const StitchProgressBar({
    super.key,
    required this.value,
    this.height = 6,
    this.barColor,
    this.trackColor,
  });

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(height / 2),
      child: SizedBox(
        height: height,
        child: LinearProgressIndicator(
          value: value.clamp(0.0, 1.0),
          backgroundColor: trackColor ?? AppColors.outlineVariant,
          valueColor: AlwaysStoppedAnimation<Color>(
            barColor ?? AppColors.primary,
          ),
        ),
      ),
    );
  }
}
