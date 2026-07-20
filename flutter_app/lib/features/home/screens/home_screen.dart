import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/auth/providers/auth_provider.dart';
import 'package:superapp_namira_flutter/features/home/providers/dashboard_provider.dart';
import 'package:superapp_namira_flutter/shared/widgets/app_bar_header.dart';
import 'package:superapp_namira_flutter/shared/widgets/dashboard_card.dart';
import 'package:superapp_namira_flutter/shared/widgets/loading_widget.dart';
import 'package:superapp_namira_flutter/shared/widgets/scroll_to_top_provider.dart';
import 'package:superapp_namira_flutter/shared/widgets/stat_card.dart';
import 'package:superapp_namira_flutter/shared/widgets/stitch_badge.dart';

class HomeScreen extends ConsumerStatefulWidget {
  const HomeScreen({super.key});

  @override
  ConsumerState<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends ConsumerState<HomeScreen> {
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(dashboardProvider.notifier).load());
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final dashState = ref.watch(dashboardProvider);

    ref.listen<int>(scrollToTopProvider, (prev, next) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          0,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });

    return Scaffold(
      backgroundColor: AppColors.background,
      body: RefreshIndicator(
        onRefresh: () async {
          await ref.read(dashboardProvider.notifier).refresh();
          await ref.read(authProvider.notifier).refreshProfile();
        },
        child: CustomScrollView(
          controller: _scrollController,
          physics: const AlwaysScrollableScrollPhysics(
            parent: BouncingScrollPhysics(),
          ),
          slivers: [
            SliverToBoxAdapter(
              child: AppBarHeader(
                userName: authState.userName.isNotEmpty
                    ? authState.userName
                    : 'Pengguna',
                subtitle: authState.roles.isNotEmpty
                    ? authState.roles.first.replaceAll('_', ' ').toUpperCase()
                    : null,
                notificationCount: 0,
                onNotificationTap: () => context.push('/notifications'),
              ),
            ),
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
                      const Icon(Icons.error_outline, size: 48, color: AppColors.error),
                      const SizedBox(height: 16),
                      Text(dashState.errorMessage ?? 'Terjadi kesalahan'),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: () => ref.read(dashboardProvider.notifier).refresh(),
                        child: const Text('Coba Lagi'),
                      ),
                    ],
                  ),
                ),
              )
            else ...[
              _buildStatsSection(dashState, authState),
              _buildModuleSection(context, authState),
              const SliverToBoxAdapter(child: SizedBox(height: 24)),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildStatsSection(DashboardState dashState, AuthState authState) {
    final stats = dashState.stats;
    final roles = authState.roles;
    if (stats == null) return const SliverToBoxAdapter(child: SizedBox.shrink());

    final isAdmin = roles.any((r) => [
          'super_admin_yayasan', 'admin_yayasan', 'admin_unit',
          'kepala_sekolah', 'staff_yayasan',
        ].contains(r));
    final isTeacher = roles.any((r) => ['teacher', 'guru'].contains(r));
    final isStudent = roles.any((r) => ['siswa', 'student'].contains(r));

    List<Widget> cards = [];

    if (isAdmin) {
      cards = _buildAdminStats(stats);
    } else if (isTeacher) {
      cards = _buildTeacherStats(stats);
    } else if (isStudent) {
      cards = _buildStudentStats(stats);
    } else {
      cards = _buildStaffStats(stats);
    }

    if (cards.isEmpty) return const SliverToBoxAdapter(child: SizedBox.shrink());

    return SliverToBoxAdapter(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Text(
                  'Ringkasan',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                ),
                const Spacer(),
                if (dashState.unit != null)
                  StitchBadge.primary(dashState.unit!['name'] ?? ''),
              ],
            ),
            if (dashState.academicYear != null) ...[
              const SizedBox(height: 4),
              Text(
                'Tahun Akademik ${dashState.academicYear!['name']} - ${dashState.academicYear!['semester']}',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 12,
                  color: AppColors.textOnSurfaceVariant,
                  letterSpacing: 0.5,
                ),
              ),
            ],
            const SizedBox(height: 12),
            GridView.count(
              crossAxisCount: 2,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              mainAxisSpacing: 12,
              crossAxisSpacing: 12,
              childAspectRatio: 1.4,
              children: cards,
            ),
          ],
        ),
      ),
    );
  }

  List<Widget> _buildAdminStats(Map<String, dynamic> stats) {
    return [
      StatCard(
        icon: Icons.school_outlined,
        iconColor: AppColors.primary,
        label: 'Siswa',
        value: '${stats['total_students'] ?? 0}',
      ),
      StatCard(
        icon: Icons.person_outlined,
        iconColor: AppColors.info,
        label: 'Guru',
        value: '${stats['total_teachers'] ?? 0}',
      ),
      StatCard(
        icon: Icons.people_outline,
        iconColor: AppColors.secondary,
        label: 'Staf',
        value: '${stats['total_staff'] ?? 0}',
      ),
      StatCard(
        icon: Icons.home_outlined,
        iconColor: AppColors.success,
        label: 'Kelas',
        value: '${stats['total_classes'] ?? 0}',
      ),
      StatCard(
        icon: Icons.receipt_long_outlined,
        iconColor: AppColors.warning,
        label: 'Tagihan Aktif',
        value: '${stats['active_bills'] ?? 0}',
      ),
      StatCard(
        icon: Icons.payments_outlined,
        iconColor: AppColors.info,
        label: 'Terealisasi',
        value: '${stats['collection_rate'] ?? 0}%',
      ),
    ];
  }

  List<Widget> _buildTeacherStats(Map<String, dynamic> stats) {
    final attendance = stats['attendance_today'];
    return [
      StatCard(
        icon: Icons.school_outlined,
        iconColor: AppColors.primary,
        label: 'Siswa',
        value: '${stats['total_students'] ?? 0}',
      ),
      StatCard(
        icon: Icons.home_outlined,
        iconColor: AppColors.info,
        label: 'Kelas',
        value: '${stats['total_classes'] ?? 0}',
      ),
      StatCard(
        icon: Icons.fingerprint,
        iconColor: attendance != null ? AppColors.success : AppColors.warning,
        label: 'Presensi',
        value: attendance != null
            ? (attendance['status'] ?? '-').toString().toUpperCase()
            : 'BELUM',
      ),
    ];
  }

  List<Widget> _buildStudentStats(Map<String, dynamic> stats) {
    final attendance = stats['attendance_today'];
    return [
      StatCard(
        icon: Icons.fingerprint,
        iconColor: attendance != null ? AppColors.success : AppColors.warning,
        label: 'Absen Hari Ini',
        value: attendance != null
            ? (attendance['status'] ?? '-').toString().toUpperCase()
            : 'BELUM',
      ),
      StatCard(
        icon: Icons.receipt_long_outlined,
        iconColor: AppColors.error,
        label: 'Tagihan',
        value: '${stats['unpaid_bills'] ?? 0}',
      ),
    ];
  }

  List<Widget> _buildStaffStats(Map<String, dynamic> stats) {
    final attendance = stats['attendance_today'];
    return [
      StatCard(
        icon: Icons.fingerprint,
        iconColor: attendance != null ? AppColors.success : AppColors.warning,
        label: 'Presensi Hari Ini',
        value: attendance != null
            ? (attendance['status'] ?? '-').toString().toUpperCase()
            : 'BELUM',
      ),
    ];
  }

  Widget _buildModuleSection(BuildContext context, AuthState authState) {
    final roles = authState.roles;
    final isAdmin = roles.any((r) => [
          'super_admin_yayasan', 'admin_yayasan', 'admin_unit',
          'kepala_sekolah', 'staff_yayasan',
        ].contains(r));

    final modules = <Map<String, dynamic>>[];

    if (isAdmin) {
      modules.addAll([
        {'icon': Icons.school_outlined, 'label': 'Akademik', 'color': AppColors.primary},
        {'icon': Icons.account_balance_wallet_outlined, 'label': 'Keuangan', 'color': AppColors.secondary},
        {'icon': Icons.people_outline, 'label': 'Karyawan', 'color': AppColors.info},
        {'icon': Icons.psychology_outlined, 'label': 'Konseling', 'color': AppColors.success},
        {'icon': Icons.inventory_2_outlined, 'label': 'Sarpar', 'color': AppColors.warning},
        {'icon': Icons.menu_book_outlined, 'label': 'LMS', 'color': const Color(0xFF805AD5)},
        {'icon': Icons.campaign_outlined, 'label': 'Humas', 'color': AppColors.secondary},
        {'icon': Icons.notifications_outlined, 'label': 'Notifikasi', 'color': AppColors.info},
      ]);
    } else {
      if (roles.any((r) => ['teacher', 'guru'].contains(r))) {
        modules.addAll([
          {'icon': Icons.schedule_outlined, 'label': 'Jadwal', 'color': AppColors.primary},
          {'icon': Icons.edit_note, 'label': 'Jurnal', 'color': AppColors.info},
          {'icon': Icons.fact_check_outlined, 'label': 'Absensi', 'color': AppColors.success},
          {'icon': Icons.menu_book_outlined, 'label': 'LMS', 'color': const Color(0xFF805AD5)},
        ]);
      }
      if (roles.any((r) => ['siswa', 'student'].contains(r))) {
        modules.addAll([
          {'icon': Icons.receipt_long_outlined, 'label': 'Tagihan', 'color': AppColors.warning},
          {'icon': Icons.menu_book_outlined, 'label': 'LMS', 'color': const Color(0xFF805AD5)},
          {'icon': Icons.school_outlined, 'label': 'Portal Siswa', 'color': AppColors.primary},
        ]);
      }
      if (roles.any((r) => ['finance', 'staff_admin_keuangan'].contains(r))) {
        modules.addAll([
          {'icon': Icons.account_balance_wallet_outlined, 'label': 'Keuangan', 'color': AppColors.secondary},
        ]);
      }
      if (roles.any((r) => ['humas'].contains(r))) {
        modules.addAll([
          {'icon': Icons.campaign_outlined, 'label': 'Humas', 'color': AppColors.secondary},
        ]);
      }
      modules.addAll([
        {'icon': Icons.fingerprint, 'label': 'Presensi', 'color': AppColors.primary},
        {'icon': Icons.notifications_outlined, 'label': 'Notifikasi', 'color': AppColors.info},
      ]);
    }

    return SliverToBoxAdapter(
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 20, 16, 0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Menu Utama',
              style: GoogleFonts.plusJakartaSans(
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
      'LMS': '/lms',
      'Humas': '/public-relations',
      'Portal Siswa': '/student',
      'Notifikasi': '/notifications',
      'Jurnal': null,
      'Absensi': '/attendance',
      'Karyawan': null,
    };
    final route = routes[label];
    if (route != null) {
      context.push(route);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Modul $label segera hadir'),
          backgroundColor: AppColors.textSecondary,
          duration: const Duration(seconds: 2),
        ),
      );
    }
  }
}
