import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/employee/providers/employee_provider.dart';
import 'package:superapp_namira_flutter/shared/widgets/loading_widget.dart';
import 'package:superapp_namira_flutter/shared/widgets/scale_press.dart';
import 'package:superapp_namira_flutter/shared/widgets/stitch_badge.dart';

class EmployeeDashboardScreen extends ConsumerStatefulWidget {
  const EmployeeDashboardScreen({super.key});

  @override
  ConsumerState<EmployeeDashboardScreen> createState() => _EmployeeDashboardScreenState();
}

class _EmployeeDashboardScreenState extends ConsumerState<EmployeeDashboardScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      ref.read(employeeProvider.notifier).loadToday();
    });
  }

  Color _statusColor(String? status) {
    switch (status) {
      case 'present':
        return AppColors.success;
      case 'late':
        return AppColors.warning;
      case 'sick':
      case 'permit':
      case 'business_trip':
        return AppColors.info;
      default:
        return AppColors.textOnSurfaceVariant;
    }
  }

  String _statusLabel(String? status) {
    switch (status) {
      case 'present':
        return 'Hadir';
      case 'late':
        return 'Terlambat';
      case 'sick':
        return 'Sakit';
      case 'permit':
        return 'Izin';
      case 'business_trip':
        return 'Dinas Luar';
      default:
        return 'Belum Absen';
    }
  }

  IconData _statusIcon(String? status) {
    switch (status) {
      case 'present':
        return Icons.check_circle_outline;
      case 'late':
        return Icons.warning_amber_outlined;
      case 'sick':
      case 'permit':
      case 'business_trip':
        return Icons.info_outline;
      default:
        return Icons.pending_outlined;
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(employeeProvider);
    final attendance = state.todayAttendance?['attendance'] as Map<String, dynamic>?;
    final hasCheckIn = attendance != null;
    final hasCheckOut = hasCheckIn && attendance['check_out_time'] != null;
    final todayStatus = attendance?['status'] as String?;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text(
          'Karyawan',
          style: GoogleFonts.plusJakartaSans(fontSize: 18, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
        ),
        backgroundColor: AppColors.surface,
        foregroundColor: AppColors.textPrimary,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
      ),
      body: state.status == EmployeeStatus.loadingToday
          ? const LoadingWidget(message: 'Memuat...')
          : RefreshIndicator(
              onRefresh: () => ref.read(employeeProvider.notifier).loadToday(),
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(parent: BouncingScrollPhysics()),
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(24),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: AppColors.outlineVariant.withAlpha(77)),
                        boxShadow: [
                          BoxShadow(color: Colors.black.withAlpha(13), blurRadius: 3, offset: const Offset(0, 1)),
                        ],
                      ),
                      child: Column(
                        children: [
                          Container(
                            width: 64,
                            height: 64,
                            decoration: BoxDecoration(
                              color: (hasCheckIn ? _statusColor(todayStatus) : AppColors.textOnSurfaceVariant).withAlpha(26),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Icon(
                              _statusIcon(todayStatus),
                              size: 32,
                              color: hasCheckIn ? _statusColor(todayStatus) : AppColors.textOnSurfaceVariant,
                            ),
                          ),
                          const SizedBox(height: 12),
                          Text(
                            _statusLabel(todayStatus),
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 20,
                              fontWeight: FontWeight.w700,
                              color: hasCheckIn ? _statusColor(todayStatus) : AppColors.textOnSurfaceVariant,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            attendance?['date'] ?? 'Belum ada data hari ini',
                            style: GoogleFonts.plusJakartaSans(fontSize: 13, color: AppColors.textOnSurfaceVariant),
                          ),
                          if (hasCheckIn) ...[
                            const SizedBox(height: 16),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Column(
                                  children: [
                                    Text('Check-In', style: GoogleFonts.plusJakartaSans(fontSize: 11, color: AppColors.border)),
                                    const SizedBox(height: 4),
                                    Text(attendance['check_in_time'] ?? '-', style: GoogleFonts.plusJakartaSans(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
                                  ],
                                ),
                                if (hasCheckOut) ...[
                                  Container(width: 1, height: 32, color: AppColors.outlineVariant, margin: const EdgeInsets.symmetric(horizontal: 32)),
                                  Column(
                                    children: [
                                      Text('Check-Out', style: GoogleFonts.plusJakartaSans(fontSize: 11, color: AppColors.border)),
                                      const SizedBox(height: 4),
                                      Text(attendance['check_out_time'] ?? '-', style: GoogleFonts.plusJakartaSans(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
                                    ],
                                  ),
                                ],
                                if (attendance['duration'] != null) ...[
                                  Container(width: 1, height: 32, color: AppColors.outlineVariant, margin: const EdgeInsets.symmetric(horizontal: 32)),
                                  Column(
                                    children: [
                                      Text('Durasi', style: GoogleFonts.plusJakartaSans(fontSize: 11, color: AppColors.border)),
                                      const SizedBox(height: 4),
                                      Text(attendance['duration'], style: GoogleFonts.plusJakartaSans(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.textPrimary)),
                                    ],
                                  ),
                                ],
                              ],
                            ),
                          ],
                          if (hasCheckIn && todayStatus == 'late' && attendance['late_minutes'] != null) ...[
                            const SizedBox(height: 12),
                            StitchBadge.warning('Terlambat ${attendance['late_minutes']} menit'),
                          ],
                          if (attendance?['approval_status'] == 'pending') ...[
                            const SizedBox(height: 12),
                            StitchBadge.warning('Menunggu Approval'),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),
                    Row(
                      children: [
                        Expanded(
                          child: ScalePress(
                            onTap: () => context.push('/attendance'),
                            child: Container(
                              padding: const EdgeInsets.all(20),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: AppColors.outlineVariant.withAlpha(77)),
                              ),
                              child: Column(
                                children: [
                                  const Icon(Icons.fingerprint_outlined, size: 28, color: AppColors.primary),
                                  const SizedBox(height: 8),
                                  Text('Presensi', style: GoogleFonts.plusJakartaSans(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textPrimary)),
                                ],
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: ScalePress(
                            onTap: () => context.push('/employee/history'),
                            child: Container(
                              padding: const EdgeInsets.all(20),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: AppColors.outlineVariant.withAlpha(77)),
                              ),
                              child: Column(
                                children: [
                                  const Icon(Icons.calendar_month_outlined, size: 28, color: AppColors.primary),
                                  const SizedBox(height: 8),
                                  Text('Riwayat', style: GoogleFonts.plusJakartaSans(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textPrimary)),
                                ],
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: ScalePress(
                            onTap: () => context.push('/employee/staff'),
                            child: Container(
                              padding: const EdgeInsets.all(20),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: AppColors.outlineVariant.withAlpha(77)),
                              ),
                              child: Column(
                                children: [
                                  const Icon(Icons.people_outline, size: 28, color: AppColors.primary),
                                  const SizedBox(height: 8),
                                  Text('Staff', style: GoogleFonts.plusJakartaSans(fontSize: 12, fontWeight: FontWeight.w600, color: AppColors.textPrimary)),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    if (todayStatus == null || todayStatus == 'absent') ...[
                      const SizedBox(height: 24),
                      SizedBox(
                        width: double.infinity,
                        height: 48,
                        child: ElevatedButton.icon(
                          onPressed: () => context.push('/employee/checkin'),
                          icon: const Icon(Icons.login_outlined, size: 18),
                          label: const Text('Check-In Sekarang'),
                          style: ElevatedButton.styleFrom(textStyle: GoogleFonts.plusJakartaSans(fontSize: 14, fontWeight: FontWeight.w600)),
                        ),
                      ),
                    ] else if (!hasCheckOut) ...[
                      const SizedBox(height: 24),
                      SizedBox(
                        width: double.infinity,
                        height: 48,
                        child: ElevatedButton.icon(
                          onPressed: () => context.push('/employee/checkout'),
                          icon: const Icon(Icons.logout_outlined, size: 18),
                          label: const Text('Check-Out'),
                          style: ElevatedButton.styleFrom(textStyle: GoogleFonts.plusJakartaSans(fontSize: 14, fontWeight: FontWeight.w600)),
                        ),
                      ),
                    ],
                    if (state.successMessage != null) ...[
                      const SizedBox(height: 16),
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: AppColors.success.withAlpha(26),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.check_circle, size: 18, color: AppColors.success),
                            const SizedBox(width: 8),
                            Expanded(child: Text(state.successMessage!, style: GoogleFonts.plusJakartaSans(fontSize: 13, color: AppColors.success))),
                          ],
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
    );
  }
}
