import 'package:convex_bottom_bar/convex_bottom_bar.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/auth/providers/auth_provider.dart';

class MainShell extends ConsumerStatefulWidget {
  final Widget child;

  const MainShell({super.key, required this.child});

  @override
  ConsumerState<MainShell> createState() => _MainShellState();
}

class _MainShellState extends ConsumerState<MainShell> {
  int _currentIndex = 0;

  late final List<_NavItem> _navItems;

  @override
  void initState() {
    super.initState();
    _navItems = _buildNavItems();
  }

  List<_NavItem> _buildNavItems() {
    return const [
      _NavItem(
        icon: Icons.home_outlined,
        activeIcon: Icons.home,
        label: 'Beranda',
        path: '/home',
      ),
      _NavItem(
        icon: Icons.school_outlined,
        activeIcon: Icons.school,
        label: 'Akademik',
        path: '/academic',
      ),
      _NavItem(
        icon: Icons.fingerprint,
        activeIcon: Icons.fingerprint,
        label: 'Presensi',
        path: '/attendance',
        isCenter: true,
      ),
      _NavItem(
        icon: Icons.account_balance_wallet_outlined,
        activeIcon: Icons.account_balance_wallet,
        label: 'Keuangan',
        path: '/finance',
      ),
      _NavItem(
        icon: Icons.person_outline,
        activeIcon: Icons.person,
        label: 'Akun',
        path: '/profile',
      ),
    ];
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final location = GoRouterState.of(context).matchedLocation;
    for (var i = 0; i < _navItems.length; i++) {
      if (location.startsWith(_navItems[i].path) ||
          (location == '/schedules' && _navItems[i].path == '/academic')) {
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
    final authState = ref.watch(authProvider);
    final roles = authState.roles;

    final hasFinance = roles.any((r) =>
        ['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah',
         'staff_yayasan', 'finance', 'staff_admin_keuangan'].contains(r));
    final hasAcademic = roles.any((r) =>
        ['super_admin_yayasan', 'admin_yayasan', 'admin_unit', 'kepala_sekolah',
         'teacher', 'wali_kelas', 'staff_yayasan'].contains(r));

    final visibleIndices = <int>{};
    if (hasAcademic) {
      visibleIndices.addAll([0, 1, 2, 3, 4]);
    } else if (hasFinance) {
      visibleIndices.addAll([0, 2, 3, 4]);
    } else {
      visibleIndices.addAll([0, 2, 4]);
    }

    final tabs = <TabItem>[];
    for (var i = 0; i < _navItems.length; i++) {
      if (!visibleIndices.contains(i)) continue;
      final item = _navItems[i];
      tabs.add(
        TabItem(
          icon: item.isCenter ? item.activeIcon : item.icon,
          title: item.label,
        ),
      );
    }

    final visibleItems = _navItems
        .where((item) =>
            visibleIndices.contains(_navItems.indexOf(item)))
        .toList();

    return Scaffold(
      body: widget.child,
      bottomNavigationBar: ConvexAppBar(
        style: TabStyle.fixedCircle,
        backgroundColor: Colors.white,
        activeColor: AppColors.primary,
        color: AppColors.textSecondary,
        elevation: 8,
        height: 60,
        curveSize: 90,
        top: -28,
        items: tabs,
        initialActiveIndex: _clampIndex(visibleItems),
        onTap: (index) {
          final realIndex = _realIndex(visibleItems, index);
          _onTap(realIndex);
        },
      ),
    );
  }

  int _clampIndex(List<_NavItem> visibleItems) {
    for (var i = 0; i < visibleItems.length; i++) {
      if (visibleItems[i].path == _navItems[_currentIndex].path) return i;
    }
    return 0;
  }

  int _realIndex(List<_NavItem> visibleItems, int visibleIndex) {
    return _navItems.indexOf(visibleItems[visibleIndex]);
  }
}

class _NavItem {
  final IconData icon;
  final IconData activeIcon;
  final String label;
  final String path;
  final bool isCenter;

  const _NavItem({
    required this.icon,
    required this.activeIcon,
    required this.label,
    required this.path,
    this.isCenter = false,
  });
}
