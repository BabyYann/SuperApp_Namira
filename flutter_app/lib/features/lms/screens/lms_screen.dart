import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/lms/data/lms_repository.dart';
import 'package:superapp_namira_flutter/shared/widgets/empty_state.dart';
import 'package:superapp_namira_flutter/shared/widgets/loading_widget.dart';
import 'package:superapp_namira_flutter/shared/widgets/namira_badge.dart';

class LmsScreen extends ConsumerStatefulWidget {
  const LmsScreen({super.key});

  @override
  ConsumerState<LmsScreen> createState() => _LmsScreenState();
}

class _LmsScreenState extends ConsumerState<LmsScreen> {
  List<dynamic> _classrooms = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final result = await ref.read(lmsRepositoryProvider).getClassrooms();
    if (mounted) {
      setState(() {
        _classrooms = (result.data?['data'] as List<dynamic>?) ?? [];
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('LMS'),
        backgroundColor: const Color(0xFF805AD5),
        foregroundColor: Colors.white,
        actions: [
          IconButton(
            icon: const Icon(Icons.assignment_outlined),
            tooltip: 'Tugas Saya',
            onPressed: () => context.push('/lms/my-tasks'),
          ),
        ],
      ),
      body: _loading
          ? const LoadingWidget(message: 'Memuat kelas...')
          : _classrooms.isEmpty
              ? const EmptyState(
                  icon: Icons.school_outlined,
                  title: 'Belum ada kelas',
                  subtitle: 'Kelas yang Anda ampu/ikuti akan muncul di sini.',
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    itemCount: _classrooms.length,
                    itemBuilder: (context, index) =>
                        _buildClassroomTile(_classrooms[index]),
                  ),
                ),
    );
  }

  Widget _buildClassroomTile(Map<String, dynamic> c) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.borderLight),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha(8),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(14),
          onTap: () => context.push('/lms/classrooms/${c['id']}'),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: const Color(0xFF805AD5).withAlpha(26),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(
                    Icons.menu_book_outlined,
                    color: Color(0xFF805AD5),
                  ),
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        c['subject'] ?? '-',
                        style: const TextStyle(
                          fontSize: 15,
                          fontWeight: FontWeight.w600,
                          color: AppColors.textPrimary,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        c['classroom'] ?? '-',
                        style: const TextStyle(
                          fontSize: 12,
                          color: AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Row(
                        children: [
                          NamiraBadge(
                            label: '${c['materials_count'] ?? 0} Materi',
                            color: AppColors.primary,
                            textColor: Colors.white,
                            isSmall: true,
                          ),
                          const SizedBox(width: 6),
                          NamiraBadge(
                            label: '${c['assignments_count'] ?? 0} Tugas',
                            color: AppColors.warning,
                            textColor: Colors.white,
                            isSmall: true,
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const Icon(
                  Icons.chevron_right,
                  color: AppColors.textHint,
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class LmsClassroomDetailScreen extends ConsumerStatefulWidget {
  final int classroomId;
  const LmsClassroomDetailScreen({super.key, required this.classroomId});

  @override
  ConsumerState<LmsClassroomDetailScreen> createState() =>
      _LmsClassroomDetailScreenState();
}

class _LmsClassroomDetailScreenState
    extends ConsumerState<LmsClassroomDetailScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final result =
        await ref.read(lmsRepositoryProvider).getClassroomDetail(widget.classroomId);
    if (mounted) {
      setState(() {
        _data = result.data?['data'] as Map<String, dynamic>?;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text(_data?['subject'] ?? 'Detail Kelas'),
        backgroundColor: const Color(0xFF805AD5),
        foregroundColor: Colors.white,
      ),
      body: _loading
          ? const LoadingWidget(message: 'Memuat detail kelas...')
          : _data == null
              ? const EmptyState(
                  icon: Icons.error_outline,
                  title: 'Kelas tidak ditemukan',
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView(
                    padding: const EdgeInsets.all(16),
                    children: [
                      _sectionHeader('Materi', Icons.menu_book_outlined),
                      ..._buildMaterials(),
                      const SizedBox(height: 16),
                      _sectionHeader('Tugas', Icons.assignment_outlined),
                      ..._buildAssignments(),
                    ],
                  ),
                ),
    );
  }

  Widget _sectionHeader(String title, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Icon(icon, size: 18, color: const Color(0xFF805AD5)),
          const SizedBox(width: 8),
          Text(
            title,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }

  List<Widget> _buildMaterials() {
    final materials = (_data?['materials'] as List<dynamic>?) ?? [];
    if (materials.isEmpty) {
      return [const Text('Belum ada materi.', style: TextStyle(color: AppColors.textSecondary))];
    }
    return materials.map((m) {
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
            const Icon(Icons.description_outlined, color: AppColors.primary, size: 20),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(m['title'] ?? '-',
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                  if (m['published_at'] != null)
                    Text(m['published_at'],
                        style: const TextStyle(fontSize: 11, color: AppColors.textHint)),
                ],
              ),
            ),
          ],
        ),
      );
    }).toList();
  }

  List<Widget> _buildAssignments() {
    final assignments = (_data?['assignments'] as List<dynamic>?) ?? [];
    if (assignments.isEmpty) {
      return [const Text('Belum ada tugas.', style: TextStyle(color: AppColors.textSecondary))];
    }
    return assignments.map((a) {
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
            const Icon(Icons.assignment_outlined, color: AppColors.warning, size: 20),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(a['title'] ?? '-',
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                  if (a['due_date'] != null)
                    Text('Tenggat: ${a['due_date']}',
                        style: const TextStyle(fontSize: 11, color: AppColors.textHint)),
                ],
              ),
            ),
            NamiraBadge(
              label: '${a['submissions_count'] ?? 0}',
              color: AppColors.secondary,
              textColor: Colors.white,
              isSmall: true,
            ),
          ],
        ),
      );
    }).toList();
  }
}

class LmsMyTasksScreen extends ConsumerStatefulWidget {
  const LmsMyTasksScreen({super.key});

  @override
  ConsumerState<LmsMyTasksScreen> createState() => _LmsMyTasksScreenState();
}

class _LmsMyTasksScreenState extends ConsumerState<LmsMyTasksScreen> {
  List<dynamic> _tasks = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final result = await ref.read(lmsRepositoryProvider).getMyTasks();
    if (mounted) {
      setState(() {
        _tasks = (result.data?['data'] as List<dynamic>?) ?? [];
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Tugas Saya'),
        backgroundColor: const Color(0xFF805AD5),
        foregroundColor: Colors.white,
      ),
      body: _loading
          ? const LoadingWidget(message: 'Memuat tugas...')
          : _tasks.isEmpty
              ? const EmptyState(
                  icon: Icons.assignment_outlined,
                  title: 'Tidak ada tugas',
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    itemCount: _tasks.length,
                    itemBuilder: (context, index) => _buildTaskTile(_tasks[index]),
                  ),
                ),
    );
  }

  Widget _buildTaskTile(Map<String, dynamic> t) {
    final status = t['status'] ?? 'submitted';
    final statusColor = switch (status) {
      'graded' => AppColors.success,
      'submitted' => AppColors.info,
      _ => AppColors.warning,
    };
    final statusLabel = switch (status) {
      'graded' => 'Dinilai',
      'submitted' => 'Terkumpul',
      _ => 'Belum',
    };

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.borderLight),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: AppColors.warning.withAlpha(26),
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(Icons.assignment_outlined, color: AppColors.warning),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(t['assignment'] ?? '-',
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                const SizedBox(height: 2),
                Text(t['subject'] ?? t['classroom'] ?? '-',
                    style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
                if (t['due_date'] != null)
                  Text('Tenggat: ${t['due_date']}',
                      style: const TextStyle(fontSize: 11, color: AppColors.textHint)),
              ],
            ),
          ),
          Column(
            children: [
              NamiraBadge(
                label: statusLabel,
                color: statusColor,
                textColor: Colors.white,
                isSmall: true,
              ),
              if (t['grade'] != null)
                Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Text('${t['grade']}',
                      style: const TextStyle(
                          fontWeight: FontWeight.w700, color: AppColors.success, fontSize: 13)),
                ),
            ],
          ),
        ],
      ),
    );
  }
}
