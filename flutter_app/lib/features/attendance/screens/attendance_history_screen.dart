import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/attendance/providers/attendance_provider.dart';
import 'package:superapp_namira_flutter/shared/widgets/namira_badge.dart';

class AttendanceHistoryScreen extends ConsumerStatefulWidget {
  const AttendanceHistoryScreen({super.key});

  @override
  ConsumerState<AttendanceHistoryScreen> createState() =>
      _AttendanceHistoryScreenState();
}

class _AttendanceHistoryScreenState
    extends ConsumerState<AttendanceHistoryScreen> {
  late DateTime _selectedMonth;

  @override
  void initState() {
    super.initState();
    _selectedMonth = DateTime.now();
    _loadHistory();
  }

  void _loadHistory() {
    final monthStr =
        '${_selectedMonth.year}-${_selectedMonth.month.toString().padLeft(2, '0')}';
    ref.read(attendanceProvider.notifier).loadHistory(month: monthStr);
  }

  void _prevMonth() {
    setState(() {
      _selectedMonth = DateTime(_selectedMonth.year, _selectedMonth.month - 1);
    });
    _loadHistory();
  }

  void _nextMonth() {
    final now = DateTime.now();
    if (_selectedMonth.isBefore(DateTime(now.year, now.month))) {
      setState(() {
        _selectedMonth = DateTime(_selectedMonth.year, _selectedMonth.month + 1);
      });
      _loadHistory();
    }
  }

  @override
  Widget build(BuildContext context) {
    final attState = ref.watch(attendanceProvider);
    final historyData = attState.historyData;
    final records = (historyData?['data'] as List<dynamic>?) ?? [];

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Riwayat Presensi'),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
      ),
      body: Column(
        children: [
          _buildMonthSelector(),
          Expanded(
            child: records.isEmpty
                ? const Center(
                    child: Text(
                      'Tidak ada data presensi',
                      style: TextStyle(color: AppColors.textSecondary),
                    ),
                  )
                : ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    itemCount: records.length,
                    itemBuilder: (context, index) {
                      return _AttendanceTile(data: records[index]);
                    },
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildMonthSelector() {
    final monthNames = [
      '', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
      'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
    ];

    final now = DateTime.now();
    final isCurrentMonth = _selectedMonth.year == now.year &&
        _selectedMonth.month == now.month;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      color: Colors.white,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          IconButton(
            onPressed: _prevMonth,
            icon: const Icon(Icons.chevron_left, color: AppColors.primary),
          ),
          Text(
            '${monthNames[_selectedMonth.month]} ${_selectedMonth.year}',
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
          IconButton(
            onPressed: isCurrentMonth ? null : _nextMonth,
            icon: Icon(
              Icons.chevron_right,
              color: isCurrentMonth ? AppColors.textHint : AppColors.primary,
            ),
          ),
        ],
      ),
    );
  }
}

class _AttendanceTile extends StatelessWidget {
  final Map<String, dynamic> data;

  const _AttendanceTile({required this.data});

  @override
  Widget build(BuildContext context) {
    final status = data['status'] ?? 'absent';
    final date = data['date'] ?? '';
    final checkIn = data['check_in_time'];
    final checkOut = data['check_out_time'];
    final lateMinutes = data['late_minutes'];

    Color statusColor;
    String statusLabel;

    switch (status) {
      case 'present':
        statusColor = AppColors.success;
        statusLabel = 'Hadir';
        break;
      case 'late':
        statusColor = AppColors.warning;
        statusLabel = 'Terlambat';
        break;
      case 'sick':
        statusColor = AppColors.info;
        statusLabel = 'Sakit';
        break;
      case 'permit':
        statusColor = const Color(0xFF805AD5);
        statusLabel = 'Izin';
        break;
      case 'business_trip':
        statusColor = AppColors.secondary;
        statusLabel = 'Dinas';
        break;
      case 'alpha':
        statusColor = AppColors.error;
        statusLabel = 'Alpha';
        break;
      default:
        statusColor = AppColors.textHint;
        statusLabel = 'Tidak Hadir';
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.borderLight),
      ),
      child: Row(
        children: [
          Container(
            width: 48,
            height: 48,
            decoration: BoxDecoration(
              color: statusColor.withAlpha(26),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Center(
              child: Text(
                _getDay(date),
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: statusColor,
                ),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _formatDate(date),
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 2),
                if (checkIn != null)
                  Text(
                    'Masuk: $checkIn${checkOut != null ? ' | Keluar: $checkOut' : ''}',
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textSecondary,
                    ),
                  ),
                if (lateMinutes != null && lateMinutes > 0)
                  Text(
                    'Terlambat $lateMinutes menit',
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.warning,
                    ),
                  ),
              ],
            ),
          ),
          NamiraBadge(
            label: statusLabel,
            color: statusColor,
            textColor: Colors.white,
            isSmall: true,
          ),
        ],
      ),
    );
  }

  String _getDay(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      return '${date.day}';
    } catch (_) {
      return '--';
    }
  }

  String _formatDate(String dateStr) {
    try {
      final date = DateTime.parse(dateStr);
      final dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
      return '${dayNames[date.weekday % 7]}, ${date.day}/${date.month}/${date.year}';
    } catch (_) {
      return dateStr;
    }
  }
}
