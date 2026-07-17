import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/academic/data/academic_repository.dart';
import 'package:superapp_namira_flutter/shared/widgets/loading_widget.dart';

class ScheduleScreen extends ConsumerStatefulWidget {
  const ScheduleScreen({super.key});

  @override
  ConsumerState<ScheduleScreen> createState() => _ScheduleScreenState();
}

class _ScheduleScreenState extends ConsumerState<ScheduleScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;
  int? _selectedClassroomId;

  @override
  void initState() {
    super.initState();
    _loadSchedules();
  }

  Future<void> _loadSchedules() async {
    setState(() => _loading = true);
    final repo = ref.read(academicRepositoryProvider);
    final result = await repo.getSchedules(classroomId: _selectedClassroomId);
    if (result.success && mounted) {
      setState(() {
        _data = result.data;
        _loading = false;
      });
    } else if (mounted) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final dayOrder = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Jadwal Pelajaran'),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
      ),
      body: _loading
          ? const LoadingWidget(message: 'Memuat jadwal...')
          : _data == null
              ? const Center(child: Text('Gagal memuat data'))
              : RefreshIndicator(
                  onRefresh: _loadSchedules,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      if (_data!['classrooms'] != null &&
                          (_data!['classrooms'] as List).isNotEmpty)
                        _buildClassroomFilter(_data!['classrooms']),
                      const SizedBox(height: 12),
                      if (_data!['schedule'] != null)
                        ...dayOrder.map((day) {
                          final items = (_data!['schedule'] as Map<String, dynamic>?)?[day] as List<dynamic>?;
                          if (items == null || items.isEmpty) return const SizedBox.shrink();
                          return _buildDaySection(day, items);
                        })
                      else if (_data!['personal_schedule'] != null)
                        ...dayOrder.map((day) {
                          final items = (_data!['personal_schedule'] as Map<String, dynamic>?)?[day] as List<dynamic>?;
                          if (items == null || items.isEmpty) return const SizedBox.shrink();
                          return _buildDaySection(day, items);
                        })
                      else
                        const Center(
                          child: Padding(
                            padding: EdgeInsets.all(32),
                            child: Text('Pilih kelas untuk melihat jadwal',
                                style: TextStyle(color: AppColors.textSecondary)),
                          ),
                        ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildClassroomFilter(List classrooms) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.border),
      ),
      child: DropdownButton<int?>(
        value: _selectedClassroomId,
        isExpanded: true,
        underline: const SizedBox(),
        hint: const Text('Pilih Kelas'),
        items: [
          const DropdownMenuItem<int?>(value: null, child: Text('Semua Kelas')),
          ...classrooms.map<DropdownMenuItem<int?>>((c) => DropdownMenuItem(
                value: c['id'],
                child: Text(c['name']),
              )),
        ],
        onChanged: (val) {
          setState(() => _selectedClassroomId = val);
          _loadSchedules();
        },
      ),
    );
  }

  Widget _buildDaySection(String day, List items) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: AppColors.primary.withAlpha(26),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(
            day,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: AppColors.primary,
            ),
          ),
        ),
        const SizedBox(height: 6),
        ...items.map((item) => Container(
              margin: const EdgeInsets.only(bottom: 6),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: AppColors.borderLight),
              ),
              child: Row(
                children: [
                  Container(
                    width: 4,
                    height: 40,
                    decoration: BoxDecoration(
                      color: AppColors.primary,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          item['subject'] ?? '-',
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          '${item['start_time'] ?? ''} - ${item['end_time'] ?? ''}',
                          style: const TextStyle(
                            fontSize: 12,
                            color: AppColors.textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Text(
                    item['teacher'] ?? item['classroom'] ?? '',
                    style: const TextStyle(
                      fontSize: 12,
                      color: AppColors.textHint,
                    ),
                  ),
                ],
              ),
            )),
        const SizedBox(height: 12),
      ],
    );
  }
}
