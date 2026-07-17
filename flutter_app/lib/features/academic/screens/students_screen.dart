import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/academic/data/academic_repository.dart';
import 'package:superapp_namira_flutter/shared/widgets/loading_widget.dart';
import 'package:superapp_namira_flutter/shared/widgets/namira_badge.dart';

class StudentsScreen extends ConsumerStatefulWidget {
  const StudentsScreen({super.key});

  @override
  ConsumerState<StudentsScreen> createState() => _StudentsScreenState();
}

class _StudentsScreenState extends ConsumerState<StudentsScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;
  String _search = '';
  int _currentPage = 1;

  @override
  void initState() {
    super.initState();
    _loadStudents();
  }

  Future<void> _loadStudents({int page = 1}) async {
    setState(() => _loading = true);
    final repo = ref.read(academicRepositoryProvider);
    final result = await repo.getStudents(search: _search, page: page);
    if (result.success && mounted) {
      setState(() {
        _data = result.data;
        _currentPage = page;
        _loading = false;
      });
    } else if (mounted) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final students = (_data?['data'] as List<dynamic>?) ?? [];
    final totalPages = (_data?['last_page'] as int?) ?? 1;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Data Siswa'),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: TextField(
              decoration: InputDecoration(
                hintText: 'Cari nama, NIS, atau NISN...',
                prefixIcon: const Icon(Icons.search, size: 20),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
                contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              ),
              onSubmitted: (val) {
                _search = val;
                _loadStudents();
              },
            ),
          ),
          Expanded(
            child: _loading
                ? const LoadingWidget(message: 'Memuat data siswa...')
                : students.isEmpty
                    ? const Center(child: Text('Tidak ada data siswa'))
                    : RefreshIndicator(
                        onRefresh: () => _loadStudents(page: _currentPage),
                        child: ListView.builder(
                          padding: const EdgeInsets.symmetric(horizontal: 12),
                          itemCount: students.length,
                          itemBuilder: (context, index) {
                            final s = students[index];
                            return _buildStudentTile(s);
                          },
                        ),
                      ),
          ),
          if (totalPages > 1)
            Container(
              padding: const EdgeInsets.all(8),
              color: Colors.white,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  if (_currentPage > 1)
                    IconButton(
                      onPressed: () => _loadStudents(page: _currentPage - 1),
                      icon: const Icon(Icons.chevron_left, color: AppColors.primary),
                    ),
                  Text('$_currentPage / $totalPages'),
                  if (_currentPage < totalPages)
                    IconButton(
                      onPressed: () => _loadStudents(page: _currentPage + 1),
                      icon: const Icon(Icons.chevron_right, color: AppColors.primary),
                    ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildStudentTile(Map<String, dynamic> student) {
    final gender = student['gender'] ?? '';
    final classroom = student['classroom']?['name'] ?? '-';

    return Container(
      margin: const EdgeInsets.only(bottom: 6),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.borderLight),
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 20,
            backgroundColor: gender == 'Laki-laki'
                ? AppColors.accent.withAlpha(51)
                : AppColors.primary.withAlpha(51),
            child: Icon(
              gender == 'Laki-laki' ? Icons.male : Icons.female,
              color: gender == 'Laki-laki' ? AppColors.accent : AppColors.primary,
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  student['full_name'] ?? '-',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  'NIS: ${student['nis'] ?? '-'}',
                  style: const TextStyle(
                    fontSize: 11,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
          NamiraBadge(
            label: classroom,
            color: AppColors.secondary,
            textColor: Colors.white,
            isSmall: true,
          ),
        ],
      ),
    );
  }
}
