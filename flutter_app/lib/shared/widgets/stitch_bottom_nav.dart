import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:superapp_namira_flutter/config/theme.dart';

class NavItem {
  final IconData icon;
  final IconData activeIcon;
  final String label;
  final String path;
  final bool isCenter;

  const NavItem({
    required this.icon,
    required this.activeIcon,
    required this.label,
    required this.path,
    this.isCenter = false,
  });
}

class StitchBottomNav extends ConsumerStatefulWidget {
  final List<NavItem> items;
  final int currentIndex;
  final ValueChanged<int> onTap;

  const StitchBottomNav({
    super.key,
    required this.items,
    required this.currentIndex,
    required this.onTap,
  });

  @override
  ConsumerState<StitchBottomNav> createState() => _StitchBottomNavState();
}

class _StitchBottomNavState extends ConsumerState<StitchBottomNav> {
  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        border: const Border(
          top: BorderSide(color: AppColors.outlineVariant),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha(13),
            blurRadius: 12,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: List.generate(widget.items.length, (i) {
              final item = widget.items[i];
              final isActive = widget.currentIndex == i;

              if (item.isCenter) {
                return GestureDetector(
                  onTap: () => widget.onTap(i),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Transform.translate(
                        offset: const Offset(0, -12),
                        child: Container(
                          width: 52,
                          height: 52,
                          decoration: BoxDecoration(
                            color: AppColors.primary,
                            shape: BoxShape.circle,
                            boxShadow: [
                              BoxShadow(
                                color: AppColors.primary.withAlpha(77),
                                blurRadius: 12,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: Icon(
                            isActive ? item.activeIcon : item.icon,
                            color: Colors.white,
                            size: 24,
                          ),
                        ),
                      ),
                      Text(
                        item.label,
                        style: GoogleFonts.plusJakartaSans(
                          fontSize: 11,
                          fontWeight: FontWeight.w500,
                          letterSpacing: 0.5,
                          color: isActive
                              ? AppColors.primary
                              : AppColors.textHint,
                          height: 1,
                        ),
                      ),
                      const SizedBox(height: 2),
                    ],
                  ),
                );
              }

              final labelColor = isActive
                  ? AppColors.onSecondaryContainer
                  : AppColors.textHint;

              return GestureDetector(
                onTap: () => widget.onTap(i),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: isActive
                            ? AppColors.secondaryContainer
                            : Colors.transparent,
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Icon(
                        isActive ? item.activeIcon : item.icon,
                        color: isActive
                            ? AppColors.onSecondaryContainer
                            : AppColors.textHint,
                        size: 22,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      item.label,
                      style: GoogleFonts.plusJakartaSans(
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                        letterSpacing: 0.5,
                        color: labelColor,
                        height: 1,
                      ),
                    ),
                    const SizedBox(height: 2),
                  ],
                ),
              );
            }),
          ),
        ),
      ),
    );
  }
}
