import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/auth/providers/auth_provider.dart';
import 'package:superapp_namira_flutter/features/home/providers/dashboard_provider.dart';
import 'package:superapp_namira_flutter/shared/widgets/avatar_widget.dart';
import 'package:superapp_namira_flutter/shared/widgets/dashboard_card.dart';
import 'package:superapp_namira_flutter/shared/widgets/loading_widget.dart';
import 'package:superapp_namira_flutter/shared/widgets/namira_badge.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(dashboardProvider.notifier).load());
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final dashState = ref.watch(dashboardProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      body: RefreshIndicator(
        onRefresh: () async {
          await ref.read(dashboardProvider.notifier).refresh();
          await ref.read(authProvider.notifier).refreshProfile();
        },
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(
            parent: BouncingScrollPhysics(),
          ),
          slivers: [
            _buildAppBar(context, authState),
            if (dashState.status == DashboardStatus.loading)
              const SliverFillRemaining(
                child: LoadingWidget(message: 'Memuat dashboard...'),
              )
            else if (dashState.status == DashboardStatus.error)
              SliverFillRemaining(
                child: Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.error_outline,
                          size: 48, color: AppColors.error),
                      const SizedBox(height: 16),
                      Text(dashState.errorMessage ?? 'Terjadi kesalahan'),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: () =>
                            ref.read(dashboardProvider.notifier).refresh(),
                        child: const Text('Coba Lagi'),
                      ),
                    ],
                  ),
                ),
              )
            else ...[
              _buildStatsSection(dashState, authState),
              _buildModuleSection(context, authState),
              const SliverToBoxAdapter(child: SizedBox(height: 16)),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildAppBar(BuildContext context, AuthState authState) {
    return SliverAppBar(
      pinned: true,
      expandedHeight: 160,
      backgroundColor: AppColors.primary,
      foregroundColor: Colors.white,
      automaticallyImplyLeading: false,
      title: const Text(
        'SuperApp Namira',
        style: TextStyle(
          fontSize: 18,
          fontWeight: FontWeight.w600,
          color: Colors.white,
        ),
      ),
      actions: [
        PopupMenuButton<String>(
          icon: AvatarWidget(
            name: authState.userName,
            radius: 16,
            backgroundColor: Colors.white.withAlpha(51),
            textColor: Colors.white,
          ),
          onSelected: (value) {
            if (value == 'logout') {
              ref.read(authProvider.notifier).logout();
              context.go('/login');
            }
          },
          itemBuilder: (context) => [
            PopupMenuItem(
              enabled: false,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    authState.userName,
                    style: const TextStyle(
                      fontWeight: FontWeight.w600,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    authState.userEmail,
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            const PopupMenuDivider(),
            const PopupMenuItem(
              value: 'profile',
              child: Row(
                children: [
                  Icon(Icons.person_outline, size: 20),
                  SizedBox(width: 8),
                  Text('Profil Saya'),
                ],
              ),
            ),
            const PopupMenuItem(
              value: 'logout',
              child: Row(
                children: [
                  Icon(Icons.logout, size: 20, color: AppColors.error),
                  SizedBox(width: 8),
                  Text('Keluar',
                      style: TextStyle(color: AppColors.error)),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(width: 8),
      ],
      flexibleSpace: FlexibleSpaceBar(
        background: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [AppColors.primary, AppColors.primaryDark],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
          child: SafeArea(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 52, 20, 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  Text(
                    'Selamat Datang,',
                    style: TextStyle(
                      fontSize: 13,
                      color: Colors.white.withAlpha(204),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    authState.userName.isNotEmpty
                        ? authState.userName
                        : 'Pengguna',
                    style: const TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Wrap(
                    spacing: 6,
                    runSpacing: 4,
                    children: authState.roles.map((role) {
                      return NamiraBadge(
                        label: role.replaceAll('_', ' ').toUpperCase(),
                        color: Colors.white.withAlpha(51),
                        textColor: Colors.white,
                        isSmall: true,
                      );
                    }).toList(),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStatsSection(DashboardState dashState, AuthState authState) {
    final stats = dashState.stats;
    final roles = authState.roles;
    if (stats == null) return const SliverToBoxAdapter(child: SizedBox.shrink());

    final isAdmin = roles.any((r) => [
          'super_admin_yayasan',
          'admin_yayasan',
          'admin_unit',
          'kepala_sekolah',
          'staff_yayasan',
        ].contains(r));
    final isTeacher = roles.any((r) => ['teacher', 'guru'].contains(r));
    final isStudent = roles.any((r) => ['siswa', 'student'].contains(r));

    List<Widget> statCards = [];

    if (isAdmin) {
      statCards = _buildAdminStats(stats);
    } else if (isTeacher) {
      statCards = _buildTeacherStats(stats);
    } else if (isStudent) {
      statCards = _buildStudentStats(stats);
    } else {
      statCards = _buildStaffStats(stats);
    }

    if (statCards.isEmpty) return const SliverToBoxAdapter(child: SizedBox.shrink());

    return SliverToBoxAdapter(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Text(
                  'Ringkasan',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                ),
                const Spacer(),
                if (dashState.unit != null)
                  NamiraBadge(
                    label: dashState.unit!['name'] ?? '',
                    color: AppColors.primary,
                    textColor: Colors.white,
                  ),
              ],
            ),
            if (dashState.academicYear != null) ...[
              const SizedBox(height: 4),
              Text(
                'Tahun Akademik ${dashState.academicYear!['name']} - ${dashState.academicYear!['semester']}',
                style: const TextStyle(
                  fontSize: 12,
                  color: AppColors.textSecondary,
                ),
              ),
            ],
            const SizedBox(height: 12),
            GridView.count(
              crossAxisCount: 2,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: 10,
              crossAxisSpacing: 10,
              childAspectRatio: 1.6,
              children: statCards,
            ),
          ],
        ),
      ),
    );
  }

  List<Widget> _buildAdminStats(Map<String, dynamic> stats) {
    return [
      _StatCard(
        icon: Icons.school_outlined,
        label: 'Siswa',
        value: '${stats['total_students'] ?? 0}',
        color: AppColors.primary,
      ),
      _StatCard(
        icon: Icons.person_outlined,
        label: 'Guru',
        value: '${stats['total_teachers'] ?? 0}',
        color: AppColors.accent,
      ),
      _StatCard(
        icon: Icons.people_outline,
        label: 'Staf',
        value: '${stats['total_staff'] ?? 0}',
        color: AppColors.secondary,
      ),
      _StatCard(
        icon: Icons.home_outlined,
        label: 'Kelas',
        value: '${stats['total_classes'] ?? 0}',
        color: AppColors.success,
      ),
      _StatCard(
        icon: Icons.receipt_long_outlined,
        label: 'Tagihan Aktif',
        value: '${stats['active_bills'] ?? 0}',
        color: AppColors.warning,
      ),
      _StatCard(
        icon: Icons.payments_outlined,
        label: 'Terealisasi',
        value: '${stats['collection_rate'] ?? 0}%',
        color: AppColors.info,
      ),
    ];
  }

  List<Widget> _buildTeacherStats(Map<String, dynamic> stats) {
    final attendance = stats['attendance_today'];
    return [
      _StatCard(
        icon: Icons.school_outlined,
        label: 'Siswa',
        value: '${stats['total_students'] ?? 0}',
        color: AppColors.primary,
      ),
      _StatCard(
        icon: Icons.home_outlined,
        label: 'Kelas',
        value: '${stats['total_classes'] ?? 0}',
        color: AppColors.accent,
      ),
      _StatCard(
        icon: Icons.fingerprint,
        label: 'Presensi',
        value: attendance != null
            ? (attendance['status'] ?? '-').toString().toUpperCase()
            : 'BELUM',
        color: attendance != null ? AppColors.success : AppColors.warning,
        valueFontSize: 12,
      ),
    ];
  }

  List<Widget> _buildStudentStats(Map<String, dynamic> stats) {
    final attendance = stats['attendance_today'];
    return [
      _StatCard(
        icon: Icons.fingerprint,
        label: 'Absen Hari Ini',
        value: attendance != null
            ? (attendance['status'] ?? '-').toString().toUpperCase()
            : 'BELUM',
        color: attendance != null ? AppColors.success : AppColors.warning,
        valueFontSize: 12,
      ),
      _StatCard(
        icon: Icons.receipt_long_outlined,
        label: 'Tagihan',
        value: '${stats['unpaid_bills'] ?? 0}',
        color: AppColors.error,
      ),
    ];
  }

  List<Widget> _buildStaffStats(Map<String, dynamic> stats) {
    final attendance = stats['attendance_today'];
    return [
      _StatCard(
        icon: Icons.fingerprint,
        label: 'Presensi Hari Ini',
        value: attendance != null
            ? (attendance['status'] ?? '-').toString().toUpperCase()
            : 'BELUM',
        color: attendance != null ? AppColors.success : AppColors.warning,
        valueFontSize: 12,
      ),
    ];
  }

  Widget _buildModuleSection(BuildContext context, AuthState authState) {
    final roles = authState.roles;
    final isAdmin = roles.any((r) => [
          'super_admin_yayasan',
          'admin_yayasan',
          'admin_unit',
          'kepala_sekolah',
          'staff_yayasan',
        ].contains(r));

    final modules = <Map<String, dynamic>>[];

    if (isAdmin) {
      modules.addAll([
        {'icon': Icons.school_outlined, 'label': 'Akademik', 'color': AppColors.primary},
        {'icon': Icons.account_balance_wallet_outlined, 'label': 'Keuangan', 'color': AppColors.secondary},
        {'icon': Icons.people_outline, 'label': 'Karyawan', 'color': AppColors.accent},
        {'icon': Icons.psychology_outlined, 'label': 'Konseling', 'color': AppColors.success},
        {'icon': Icons.inventory_2_outlined, 'label': 'Sarpar', 'color': AppColors.warning},
        {'icon': Icons.menu_book_outlined, 'label': 'LMS', 'color': const Color(0xFF805AD5)},
      ]);
    } else {
      if (roles.any((r) => ['teacher', 'guru'].contains(r))) {
        modules.addAll([
          {'icon': Icons.schedule_outlined, 'label': 'Jadwal', 'color': AppColors.primary},
          {'icon': Icons.edit_note, 'label': 'Jurnal', 'color': AppColors.accent},
          {'icon': Icons.fact_check_outlined, 'label': 'Absensi', 'color': AppColors.success},
        ]);
      }
      if (roles.any((r) => ['siswa', 'student'].contains(r))) {
        modules.addAll([
          {'icon': Icons.receipt_long_outlined, 'label': 'Tagihan', 'color': AppColors.warning},
          {'icon': Icons.menu_book_outlined, 'label': 'LMS', 'color': const Color(0xFF805AD5)},
        ]);
      }
      if (roles.any((r) => ['finance', 'staff_admin_keuangan'].contains(r))) {
        modules.addAll([
          {'icon': Icons.account_balance_wallet_outlined, 'label': 'Keuangan', 'color': AppColors.secondary},
        ]);
      }
      modules.add(
        {'icon': Icons.fingerprint, 'label': 'Presensi', 'color': AppColors.primary},
      );
    }

    return SliverToBoxAdapter(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Menu Utama',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: 12),
            GridView.count(
              crossAxisCount: 3,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              childAspectRatio: 1.0,
              children: modules.map((m) {
                return DashboardCard(
                  icon: m['icon'] as IconData,
                  label: m['label'] as String,
                  color: m['color'] as Color,
                  onTap: () => _navigateToModule(context, m['label'] as String),
                );
              }).toList(),
            ),
          ],
        ),
      ),
    );
  }

  void _navigateToModule(BuildContext context, String label) {
    final routes = {
      'Jadwal': '/schedules',
      'Akademik': '/academic',
      'Keuangan': '/finance',
      'Konseling': '/counseling',
      'Sarpar': '/sarpar',
      'Presensi': '/attendance',
      'Tagihan': '/finance',
      'LMS': null,
      'Jurnal': null,
      'Absensi': '/attendance',
      'Karyawan': null,
    };
    final route = routes[label];
    if (route != null) {
      context.push(route);
    }
  }
}

class _StatCard extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color color;
  final double? valueFontSize;

  const _StatCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
    this.valueFontSize,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.borderLight),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: color.withAlpha(26),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, size: 18, color: color),
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: valueFontSize ?? 18,
              fontWeight: FontWeight.w700,
              color: color,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w500,
              color: AppColors.textSecondary,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}
