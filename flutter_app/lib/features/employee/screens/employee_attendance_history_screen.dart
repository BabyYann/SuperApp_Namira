import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/employee/providers/employee_provider.dart';
import 'package:superapp_namira_flutter/shared/widgets/stitch_badge.dart';

class EmployeeAttendanceHistoryScreen extends ConsumerStatefulWidget {
  const EmployeeAttendanceHistoryScreen({super.key});

  @override
  ConsumerState<EmployeeAttendanceHistoryScreen> createState() => _EmployeeAttendanceHistoryScreenState();
}

class _EmployeeAttendanceHistoryScreenState extends ConsumerState<EmployeeAttendanceHistoryScreen> {
  late DateTime _selectedMonth;
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _selectedMonth = DateTime.now();
    _load();
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scrollController.removeListener(_onScroll);
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
      final state = ref.read(employeeProvider);
      if (state.hasMoreHistory && state.status != EmployeeStatus.loadingHistory) {
        ref.read(employeeProvider.notifier).loadHistory(
          month: '${_selectedMonth.year}-${_selectedMonth.month.toString().padLeft(2, '0')}',
          loadMore: true,
        );
      }
    }
  }

  void _load() {
    ref.read(employeeProvider.notifier).loadHistory(
      month: '${_selectedMonth.year}-${_selectedMonth.month.toString().padLeft(2, '0')}',
    );
  }

  void _previousMonth() {
    setState(() {
      _selectedMonth = DateTime(_selectedMonth.year, _selectedMonth.month - 1);
    });
    _load();
  }

  void _nextMonth() {
    setState(() {
      _selectedMonth = DateTime(_selectedMonth.year, _selectedMonth.month + 1);
    });
    _load();
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'present':
        return AppColors.success;
      case 'late':
        return AppColors.warning;
      case 'sick':
      case 'permit':
        return AppColors.info;
      case 'business_trip':
        return AppColors.primary;
      default:
        return AppColors.textOnSurfaceVariant;
    }
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'present':
        return 'Hadir';
      case 'late':
        return 'Telat';
      case 'sick':
        return 'Sakit';
      case 'permit':
        return 'Izin';
      case 'business_trip':
        return 'Dinas';
      default:
        return '-';
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(employeeProvider);
    final records = (state.historyData?['data'] as List<dynamic>?) ?? [];

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text(
          'Riwayat Absensi',
          style: GoogleFonts.plusJakartaSans(fontSize: 18, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
        ),
        backgroundColor: AppColors.surface,
        foregroundColor: AppColors.textPrimary,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
      ),
      body: Column(
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
            color: Colors.white,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                IconButton(
                  icon: const Icon(Icons.chevron_left),
                  onPressed: _previousMonth,
                  color: AppColors.textPrimary,
                ),
                Text(
                  '${_months[_selectedMonth.month - 1]} ${_selectedMonth.year}',
                  style: GoogleFonts.plusJakartaSans(fontSize: 16, fontWeight: FontWeight.w700, color: AppColors.textPrimary),
                ),
                IconButton(
                  icon: const Icon(Icons.chevron_right),
                  onPressed: _selectedMonth.month == DateTime.now().month && _selectedMonth.year == DateTime.now().year ? null : _nextMonth,
                  color: _selectedMonth.month == DateTime.now().month && _selectedMonth.year == DateTime.now().year ? AppColors.border : AppColors.textPrimary,
                ),
              ],
            ),
          ),
          Expanded(
            child: state.status == EmployeeStatus.loadingHistory && records.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : records.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.calendar_month_outlined, size: 48, color: AppColors.border),
                            const SizedBox(height: 12),
                            Text('Belum ada data absensi', style: GoogleFonts.plusJakartaSans(fontSize: 14, color: AppColors.border)),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: () async {
                          _load();
                        },
                        child: ListView.separated(
                          controller: _scrollController,
                          padding: const EdgeInsets.all(24),
                          itemCount: records.length + (state.hasMoreHistory ? 1 : 0),
                          separatorBuilder: (_, __) => const SizedBox(height: 8),
                          itemBuilder: (context, index) {
                            if (index == records.length) {
                              return const Center(
                                child: Padding(
                                  padding: EdgeInsets.all(16),
                                  child: CircularProgressIndicator(strokeWidth: 2),
                                ),
                              );
                            }
                            final r = records[index] as Map<String, dynamic>;
                            return Container(
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: AppColors.outlineVariant.withAlpha(77)),
                              ),
                              child: Row(
                                children: [
                                  Container(
                                    width: 44,
                                    height: 44,
                                    decoration: BoxDecoration(
                                      color: _statusColor(r['status'] ?? '').withAlpha(26),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Center(
                                      child: Text(
                                        '${DateTime.parse(r['date']).day}',
                                        style: GoogleFonts.plusJakartaSans(fontSize: 16, fontWeight: FontWeight.w700, color: _statusColor(r['status'] ?? '')),
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          r['name'] ?? 'Pegawai',
                                          style: GoogleFonts.plusJakartaSans(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
                                        ),
                                        const SizedBox(height: 2),
                                        Text(
                                          r['date'],
                                          style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.textOnSurfaceVariant),
                                        ),
                                      ],
                                    ),
                                  ),
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.end,
                                    children: [
                                      StitchBadge(
                                        label: _statusLabel(r['status'] ?? ''),
                                        backgroundColor: _statusColor(r['status'] ?? '').withAlpha(26),
                                        textColor: _statusColor(r['status'] ?? ''),
                                      ),
                                      if (r['check_in'] != null || r['check_out'] != null) ...[
                                        const SizedBox(height: 4),
                                        Text(
                                          '${r['check_in'] ?? '-'} - ${r['check_out'] ?? '-'}',
                                          style: GoogleFonts.plusJakartaSans(fontSize: 11, color: AppColors.border),
                                        ),
                                      ],
                                    ],
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }
}

const _months = [
  'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];
