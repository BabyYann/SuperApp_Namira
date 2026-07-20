import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:superapp_namira_flutter/features/auth/providers/auth_provider.dart';
import 'package:superapp_namira_flutter/shared/widgets/connectivity_banner.dart';
import 'package:superapp_namira_flutter/shared/widgets/scroll_to_top_provider.dart';
import 'package:superapp_namira_flutter/shared/widgets/stitch_bottom_nav.dart';

class MainShell extends ConsumerStatefulWidget {
  final Widget child;

  const MainShell({super.key, required this.child});

  @override
  ConsumerState<MainShell> createState() => _MainShellState();
}

class _MainShellState extends ConsumerState<MainShell> {
  int _currentIndex = 0;

  late final List<NavItem> _navItems;

  @override
  void initState() {
    super.initState();
    _navItems = _buildNavItems();
  }

  List<NavItem> _buildNavItems() {
    return const [
      NavItem(
        icon: Icons.home_outlined,
        activeIcon: Icons.home,
        label: 'Beranda',
        path: '/home',
      ),
      NavItem(
        icon: Icons.school_outlined,
        activeIcon: Icons.school,
        label: 'Akademik',
        path: '/academic',
      ),
      NavItem(
        icon: Icons.fingerprint_outlined,
        activeIcon: Icons.fingerprint,
        label: 'Presensi',
        path: '/attendance',
        isCenter: true,
      ),
      NavItem(
        icon: Icons.account_balance_wallet_outlined,
        activeIcon: Icons.account_balance_wallet,
        label: 'Keuangan',
        path: '/finance',
      ),
      NavItem(
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
    if (index == _currentIndex) {
      ref.read(scrollToTopProvider.notifier).state++;
      return;
    }
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

    final visibleItems = <NavItem>[];
    final indexMapping = <int, int>{};
    for (var i = 0; i < _navItems.length; i++) {
      if (visibleIndices.contains(i)) {
        indexMapping[visibleItems.length] = i;
        visibleItems.add(_navItems[i]);
      }
    }

    final clampedIndex = _clampIndex(visibleItems);

    return Scaffold(
      body: ConnectivityBanner(child: widget.child),
      bottomNavigationBar: StitchBottomNav(
        items: visibleItems,
        currentIndex: clampedIndex,
        onTap: (index) {
          final realIndex = indexMapping[index] ?? index;
          _onTap(realIndex);
        },
      ),
    );
  }

  int _clampIndex(List<NavItem> visibleItems) {
    for (var i = 0; i < visibleItems.length; i++) {
      if (visibleItems[i].path == _navItems[_currentIndex].path) return i;
    }
    return 0;
  }
}
