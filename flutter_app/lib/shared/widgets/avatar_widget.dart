import 'package:flutter/material.dart';
import 'package:superapp_namira_flutter/config/theme.dart';

class AvatarWidget extends StatelessWidget {
  final String? imageUrl;
  final String? name;
  final double radius;
  final Color? backgroundColor;
  final Color? textColor;

  const AvatarWidget({
    super.key,
    this.imageUrl,
    this.name,
    this.radius = 20,
    this.backgroundColor,
    this.textColor,
  });

  @override
  Widget build(BuildContext context) {
    final effectiveBg = backgroundColor ?? AppColors.primary;
    final effectiveTextColor = textColor ?? Colors.white;
    final initial = _getInitial(name);

    if (imageUrl != null && imageUrl!.isNotEmpty) {
      return CircleAvatar(
        radius: radius,
        backgroundColor: effectiveBg,
        backgroundImage: NetworkImage(imageUrl!),
        onBackgroundImageError: (_, _) {},
        child: Text(
          initial,
          style: TextStyle(
            fontSize: radius * 0.7,
            fontWeight: FontWeight.w700,
            color: effectiveTextColor,
          ),
        ),
      );
    }

    return CircleAvatar(
      radius: radius,
      backgroundColor: effectiveBg,
      child: Text(
        initial,
        style: TextStyle(
          fontSize: radius * 0.7,
          fontWeight: FontWeight.w700,
          color: effectiveTextColor,
        ),
      ),
    );
  }

  String _getInitial(String? name) {
    if (name == null || name.isEmpty) return '?';
    final parts = name.trim().split(RegExp(r'\s+'));
    if (parts.length >= 2) {
      return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    }
    return parts[0][0].toUpperCase();
  }
}
