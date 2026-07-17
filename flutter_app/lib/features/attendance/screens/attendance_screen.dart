import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/attendance/providers/attendance_provider.dart';
import 'package:superapp_namira_flutter/shared/widgets/loading_widget.dart';
import 'package:superapp_namira_flutter/shared/widgets/namira_badge.dart';

class AttendanceScreen extends ConsumerStatefulWidget {
  const AttendanceScreen({super.key});

  @override
  ConsumerState<AttendanceScreen> createState() => _AttendanceScreenState();
}

class _AttendanceScreenState extends ConsumerState<AttendanceScreen> {
  String _selectedType = 'present';
  final _noteController = TextEditingController();

  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(attendanceProvider.notifier).loadToday());
  }

  @override
  void dispose() {
    _noteController.dispose();
    super.dispose();
  }

  Future<void> _handleCheckIn() async {
    final error = await ref.read(attendanceProvider.notifier).checkIn(
      type: _selectedType,
      note: _noteController.text.isNotEmpty ? _noteController.text : null,
    );

    if (error != null && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error),
          backgroundColor: AppColors.error,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      );
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Berhasil check-in!'),
          backgroundColor: AppColors.success,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      );
    }
  }

  Future<void> _handleCheckOut() async {
    final error = await ref.read(attendanceProvider.notifier).checkOut(
      latitude: -7.5361,
      longitude: 112.2384,
    );

    if (error != null && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(error),
          backgroundColor: AppColors.error,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      );
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Text('Berhasil check-out!'),
          backgroundColor: AppColors.success,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final attState = ref.watch(attendanceProvider);

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Presensi'),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
      ),
      body: attState.status == AttendanceStatus.loading
          ? const LoadingWidget(message: 'Memuat data presensi...')
          : SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildTodayCard(attState),
                  const SizedBox(height: 20),
                  if (!attState.hasCheckedIn) _buildCheckInSection(attState),
                  if (attState.hasCheckedIn && !attState.hasCheckedOut)
                    _buildCheckOutSection(),
                  const SizedBox(height: 20),
                  _buildInfoSection(),
                ],
              ),
            ),
    );
  }

  Widget _buildTodayCard(AttendanceState attState) {
    final attendance = attState.todayAttendance;
    final hasCheckedIn = attState.hasCheckedIn;
    final hasCheckedOut = attState.hasCheckedOut;

    Color statusColor;
    String statusText;

    if (hasCheckedOut) {
      statusColor = AppColors.info;
      statusText = 'Selesai';
    } else if (hasCheckedIn) {
      statusColor = AppColors.success;
      statusText = 'Sudah Check-In';
    } else {
      statusColor = AppColors.warning;
      statusText = 'Belum Absen';
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [statusColor, statusColor.withAlpha(179)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: statusColor.withAlpha(77),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          NamiraBadge(
            label: statusText,
            color: Colors.white.withAlpha(51),
            textColor: Colors.white,
          ),
          const SizedBox(height: 16),
          if (hasCheckedIn && attendance != null) ...[
            Row(
              children: [
                const Icon(Icons.login, color: Colors.white, size: 20),
                const SizedBox(width: 8),
                Text(
                  'Check-In: ${attendance['check_in_time'] ?? '-'}',
                  style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                    fontSize: 16,
                  ),
                ),
              ],
            ),
            if (attendance['status'] == 'late' &&
                attendance['late_minutes'] != null) ...[
              const SizedBox(height: 4),
              Text(
                'Terlambat ${attendance['late_minutes']} menit',
                style: TextStyle(
                  color: Colors.white.withAlpha(204),
                  fontSize: 12,
                ),
              ),
            ],
            if (hasCheckedOut) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.logout, color: Colors.white, size: 20),
                  const SizedBox(width: 8),
                  Text(
                    'Check-Out: ${attendance['check_out_time'] ?? '-'}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w600,
                      fontSize: 16,
                    ),
                  ),
                ],
              ),
            ],
          ] else
            const Text(
              'Anda belum melakukan presensi hari ini',
              style: TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.w500,
                fontSize: 15,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildCheckInSection(AttendanceState attState) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Jenis Kehadiran',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: AppColors.textPrimary,
          ),
        ),
        const SizedBox(height: 12),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            _TypeChip(
              label: 'WFO (Hadir)',
              icon: Icons.work_outlined,
              isSelected: _selectedType == 'present',
              onTap: () => setState(() => _selectedType = 'present'),
            ),
            _TypeChip(
              label: 'Dinas Luar',
              icon: Icons.directions_car_outlined,
              isSelected: _selectedType == 'business_trip',
              onTap: () => setState(() => _selectedType = 'business_trip'),
            ),
            _TypeChip(
              label: 'Sakit',
              icon: Icons.sick_outlined,
              isSelected: _selectedType == 'sick',
              onTap: () => setState(() => _selectedType = 'sick'),
            ),
            _TypeChip(
              label: 'Izin',
              icon: Icons.event_available_outlined,
              isSelected: _selectedType == 'permit',
              onTap: () => setState(() => _selectedType = 'permit'),
            ),
          ],
        ),
        if (_selectedType != 'present') ...[
          const SizedBox(height: 16),
          TextField(
            controller: _noteController,
            maxLines: 3,
            decoration: InputDecoration(
              labelText: 'Keterangan',
              hintText: 'Masukkan keterangan...',
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(10),
              ),
            ),
          ),
        ],
        const SizedBox(height: 20),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: attState.status == AttendanceStatus.loading
                ? null
                : _handleCheckIn,
            icon: attState.status == AttendanceStatus.loading
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Icon(Icons.fingerprint, size: 24),
            label: const Text('Check-In Sekarang'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildCheckOutSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: _handleCheckOut,
            icon: const Icon(Icons.logout, size: 24),
            label: const Text('Check-Out Sekarang'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.secondary,
              foregroundColor: Colors.white,
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildInfoSection() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Informasi',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: AppColors.textPrimary,
          ),
        ),
        const SizedBox(height: 8),
        _InfoTile(
          icon: Icons.info_outline,
          title: 'Keterangan Presensi',
          subtitle: 'Check-in harus dilakukan di dalam area sekolah (radius lokasi absensi).',
        ),
        const SizedBox(height: 8),
        _InfoTile(
          icon: Icons.access_time,
          title: 'Jam Kerja',
          subtitle: 'Keterlambatan dihitung setelah jam masuk toleransi.',
        ),
      ],
    );
  }
}

class _TypeChip extends StatelessWidget {
  final String label;
  final IconData icon;
  final bool isSelected;
  final VoidCallback onTap;

  const _TypeChip({
    required this.label,
    required this.icon,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(
          color: isSelected
              ? AppColors.primary.withAlpha(26)
              : Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isSelected ? AppColors.primary : AppColors.border,
            width: isSelected ? 2 : 1,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              size: 18,
              color: isSelected ? AppColors.primary : AppColors.textSecondary,
            ),
            const SizedBox(width: 6),
            Text(
              label,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: isSelected ? AppColors.primary : AppColors.textSecondary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _InfoTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;

  const _InfoTile({
    required this.icon,
    required this.title,
    required this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.borderLight),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: AppColors.primary),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: const TextStyle(
                    fontSize: 12,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
