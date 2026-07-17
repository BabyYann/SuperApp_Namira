import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:superapp_namira_flutter/config/theme.dart';

class MainShell extends StatefulWidget {
  final Widget child;

  const MainShell({super.key, required this.child});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _currentIndex = 0;

  static const _navItems = [
    _NavItem(
      icon: Icons.home_outlined,
      activeIcon: Icons.home,
      label: 'Beranda',
      path: '/home',
    ),
    _NavItem(
      icon: Icons.calendar_today_outlined,
      activeIcon: Icons.calendar_today,
      label: 'Jadwal',
      path: '/schedules',
    ),
    _NavItem(
      icon: Icons.school_outlined,
      activeIcon: Icons.school,
      label: 'Akademik',
      path: '/academic',
    ),
    _NavItem(
      icon: Icons.person_outline,
      activeIcon: Icons.person,
      label: 'Akun',
      path: '/profile',
    ),
  ];

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final location = GoRouterState.of(context).matchedLocation;
    for (var i = 0; i < _navItems.length; i++) {
      if (location.startsWith(_navItems[i].path)) {
        if (_currentIndex != i) {
          setState(() => _currentIndex = i);
        }
        break;
      }
    }
  }

  void _onTap(int index) {
    if (index == _currentIndex) return;
    setState(() => _currentIndex = index);
    context.go(_navItems[index].path);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: widget.child,
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: AppColors.glassWhiteHigh,
          border: const Border(
            top: BorderSide(color: AppColors.borderLight),
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
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: List.generate(_navItems.length, (index) {
                final item = _navItems[index];
                final isActive = _currentIndex == index;
                return _BottomNavItem(
                  item: item,
                  isActive: isActive,
                  onTap: () => _onTap(index),
                );
              }),
            ),
          ),
        ),
      ),
    );
  }
}

class _NavItem {
  final IconData icon;
  final IconData activeIcon;
  final String label;
  final String path;

  const _NavItem({
    required this.icon,
    required this.activeIcon,
    required this.label,
    required this.path,
  });
}

class _BottomNavItem extends StatelessWidget {
  final _NavItem item;
  final bool isActive;
  final VoidCallback onTap;

  const _BottomNavItem({
    required this.item,
    required this.isActive,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      behavior: HitTestBehavior.opaque,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
        decoration: BoxDecoration(
          color: isActive ? AppColors.primary.withAlpha(26) : Colors.transparent,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            AnimatedSwitcher(
              duration: const Duration(milliseconds: 200),
              child: Icon(
                isActive ? item.activeIcon : item.icon,
                key: ValueKey(isActive),
                size: 24,
                color: isActive ? AppColors.primary : AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              item.label,
              style: TextStyle(
                fontSize: 11,
                fontWeight: isActive ? FontWeight.w600 : FontWeight.w500,
                color: isActive ? AppColors.primary : AppColors.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
