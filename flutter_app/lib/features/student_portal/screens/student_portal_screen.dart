import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/student_portal/data/student_portal_repository.dart';
import 'package:superapp_namira_flutter/shared/widgets/loading_widget.dart';
import 'package:superapp_namira_flutter/shared/widgets/namira_badge.dart';

class StudentPortalScreen extends ConsumerStatefulWidget {
  const StudentPortalScreen({super.key});

  @override
  ConsumerState<StudentPortalScreen> createState() => _StudentPortalScreenState();
}

class _StudentPortalScreenState extends ConsumerState<StudentPortalScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 5, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Portal Siswa'),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          isScrollable: true,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Beranda'),
            Tab(text: 'Tugas'),
            Tab(text: 'Jadwal'),
            Tab(text: 'Nilai'),
            Tab(text: 'Profil'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: const [
          _StudentDashboardTab(),
          _StudentTasksTab(),
          _StudentScheduleTab(),
          _StudentGradesTab(),
          _StudentProfileTab(),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/student/pickup'),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        icon: const Icon(Icons.local_taxi_outlined),
        label: const Text('Jemput'),
      ),
    );
  }
}

class _StudentDashboardTab extends ConsumerStatefulWidget {
  const _StudentDashboardTab();

  @override
  ConsumerState<_StudentDashboardTab> createState() =>
      _StudentDashboardTabState();
}

class _StudentDashboardTabState extends ConsumerState<_StudentDashboardTab> {
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
        await ref.read(studentPortalRepositoryProvider).getDashboard();
    if (mounted) {
      setState(() {
        _data = result.data?['data'] as Map<String, dynamic>?;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const LoadingWidget(message: 'Memuat...');
    if (_data == null) {
      return const Center(child: Text('Data tidak tersedia'));
    }
    final student = _data!['student'] as Map<String, dynamic>? ?? {};
    final bills = (_data!['unpaid_bills'] as List<dynamic>?) ?? [];
    final tasks = (_data!['tasks'] as List<dynamic>?) ?? [];

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [AppColors.primary, Color(0xFF004D40)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Halo, ${student['name'] ?? '-'}',
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 18,
                        fontWeight: FontWeight.w700)),
                const SizedBox(height: 4),
                Text('Kelas ${student['classroom'] ?? '-'} · NIS ${student['nis'] ?? '-'}',
                    style: const TextStyle(color: Colors.white70, fontSize: 12)),
              ],
            ),
          ),
          const SizedBox(height: 16),
          const Text('Tagihan Belum Lunas',
              style: TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
          const SizedBox(height: 8),
          if (bills.isEmpty)
            const Text('Tidak ada tagihan.', style: TextStyle(color: AppColors.textSecondary))
          else
            ...bills.map((b) => Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: AppColors.borderLight),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.receipt_long_outlined,
                          color: AppColors.error, size: 20),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(b['name'] ?? '-',
                                style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                            Text('Jatuh tempo: ${b['due_date'] ?? '-'}',
                                style: const TextStyle(fontSize: 11, color: AppColors.textHint)),
                          ],
                        ),
                      ),
                      Text('Rp ${(b['amount'] ?? 0).toString()}',
                          style: const TextStyle(
                              fontWeight: FontWeight.w700, color: AppColors.error, fontSize: 13)),
                    ],
                  ),
                )),
          const SizedBox(height: 16),
          const Text('Tugas Terbaru',
              style: TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
          const SizedBox(height: 8),
          if (tasks.isEmpty)
            const Text('Tidak ada tugas.', style: TextStyle(color: AppColors.textSecondary))
          else
            ...tasks.take(5).map((t) => Container(
                  margin: const EdgeInsets.only(bottom: 8),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: AppColors.borderLight),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.assignment_outlined,
                          color: AppColors.warning, size: 20),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(t['title'] ?? '-',
                            style: const TextStyle(fontSize: 13)),
                      ),
                      NamiraBadge(
                        label: (t['status'] ?? '').toString().toUpperCase(),
                        color: AppColors.info,
                        textColor: Colors.white,
                        isSmall: true,
                      ),
                    ],
                  ),
                )),
        ],
      ),
    );
  }
}

class _StudentTasksTab extends ConsumerStatefulWidget {
  const _StudentTasksTab();

  @override
  ConsumerState<_StudentTasksTab> createState() => _StudentTasksTabState();
}

class _StudentTasksTabState extends ConsumerState<_StudentTasksTab> {
  List<dynamic> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final result = await ref.read(studentPortalRepositoryProvider).getTasks();
    if (mounted) {
      setState(() {
        _items = (result.data?['data'] as List<dynamic>?) ?? [];
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return _loading
        ? const LoadingWidget(message: 'Memuat tugas...')
        : ListView.builder(
            padding: const EdgeInsets.symmetric(vertical: 8),
            itemCount: _items.length,
            itemBuilder: (context, i) {
              final t = _items[i];
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
                    const Icon(Icons.assignment_outlined, color: AppColors.warning),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(t['title'] ?? '-',
                              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                          if (t['due_date'] != null)
                            Text('Tenggat: ${t['due_date']}',
                                style: const TextStyle(fontSize: 11, color: AppColors.textHint)),
                        ],
                      ),
                    ),
                    NamiraBadge(
                      label: (t['status'] ?? '').toString().toUpperCase(),
                      color: AppColors.info,
                      textColor: Colors.white,
                      isSmall: true,
                    ),
                  ],
                ),
              );
            },
          );
  }
}

class _StudentScheduleTab extends ConsumerStatefulWidget {
  const _StudentScheduleTab();

  @override
  ConsumerState<_StudentScheduleTab> createState() => _StudentScheduleTabState();
}

class _StudentScheduleTabState extends ConsumerState<_StudentScheduleTab> {
  List<dynamic> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final result = await ref.read(studentPortalRepositoryProvider).getSchedule();
    if (mounted) {
      setState(() {
        _items = (result.data?['data'] as List<dynamic>?) ?? [];
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return _loading
        ? const LoadingWidget(message: 'Memuat jadwal...')
        : _items.isEmpty
            ? const Center(child: Text('Tidak ada jadwal'))
            : ListView.builder(
                padding: const EdgeInsets.symmetric(vertical: 8),
                itemCount: _items.length,
                itemBuilder: (context, i) {
                  final s = _items[i];
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
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                          decoration: BoxDecoration(
                            color: AppColors.primary.withAlpha(26),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text('${s['day'] ?? '-'}\n${s['start_time'] ?? ''}',
                              textAlign: TextAlign.center,
                              style: const TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w600,
                                  color: AppColors.primary)),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(s['subject'] ?? '-',
                                  style: const TextStyle(
                                      fontWeight: FontWeight.w600, fontSize: 14)),
                              Text('${s['teacher'] ?? '-'} · ${s['room'] ?? '-'}',
                                  style: const TextStyle(
                                      fontSize: 12, color: AppColors.textSecondary)),
                              Text('${s['start_time'] ?? ''} - ${s['end_time'] ?? ''}',
                                  style: const TextStyle(fontSize: 11, color: AppColors.textHint)),
                            ],
                          ),
                        ),
                      ],
                    ),
                  );
                },
              );
  }
}

class _StudentGradesTab extends ConsumerStatefulWidget {
  const _StudentGradesTab();

  @override
  ConsumerState<_StudentGradesTab> createState() => _StudentGradesTabState();
}

class _StudentGradesTabState extends ConsumerState<_StudentGradesTab> {
  List<dynamic> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final result = await ref.read(studentPortalRepositoryProvider).getGrades();
    if (mounted) {
      setState(() {
        _items = (result.data?['data'] as List<dynamic>?) ?? [];
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return _loading
        ? const LoadingWidget(message: 'Memuat nilai...')
        : _items.isEmpty
            ? const Center(child: Text('Belum ada nilai'))
            : ListView.builder(
                padding: const EdgeInsets.symmetric(vertical: 8),
                itemCount: _items.length,
                itemBuilder: (context, i) {
                  final g = _items[i];
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
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(g['assignment'] ?? '-',
                                  style: const TextStyle(
                                      fontWeight: FontWeight.w600, fontSize: 14)),
                              Text(g['subject'] ?? '-',
                                  style: const TextStyle(
                                      fontSize: 12, color: AppColors.textSecondary)),
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: AppColors.success.withAlpha(26),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text('${g['grade'] ?? '-'}',
                              style: const TextStyle(
                                  fontWeight: FontWeight.w700, color: AppColors.success)),
                        ),
                      ],
                    ),
                  );
                },
              );
  }
}

class _StudentProfileTab extends ConsumerStatefulWidget {
  const _StudentProfileTab();

  @override
  ConsumerState<_StudentProfileTab> createState() => _StudentProfileTabState();
}

class _StudentProfileTabState extends ConsumerState<_StudentProfileTab> {
  Map<String, dynamic>? _data;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final result = await ref.read(studentPortalRepositoryProvider).getProfile();
    if (mounted) {
      setState(() {
        _data = result.data?['data'] as Map<String, dynamic>?;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const LoadingWidget(message: 'Memuat profil...');
    if (_data == null) return const Center(child: Text('Profil tidak tersedia'));
    final rows = [
      ['Nama', _data!['name'] ?? '-'],
      ['NIS', _data!['nis'] ?? '-'],
      ['NISN', _data!['nisn'] ?? '-'],
      ['Kelas', _data!['classroom'] ?? '-'],
      ['Jenis Kelamin', _data!['gender'] ?? '-'],
      ['Orang Tua', _data!['parent_name'] ?? '-'],
      ['No. Orang Tua', _data!['parent_phone'] ?? '-'],
      ['Alamat', _data!['address'] ?? '-'],
    ];
    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        const SizedBox(height: 8),
        ...rows.map((r) => Container(
              margin: const EdgeInsets.only(bottom: 8),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: AppColors.borderLight),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SizedBox(
                    width: 120,
                    child: Text(r[0],
                        style: const TextStyle(color: AppColors.textSecondary, fontSize: 13)),
                  ),
                  Expanded(
                    child: Text(r[1],
                        style: const TextStyle(
                            fontWeight: FontWeight.w600, fontSize: 13, color: AppColors.textPrimary)),
                  ),
                ],
              ),
            )),
      ],
    );
  }
}

class StudentPickupScreen extends ConsumerStatefulWidget {
  const StudentPickupScreen({super.key});

  @override
  ConsumerState<StudentPickupScreen> createState() => _StudentPickupScreenState();
}

class _StudentPickupScreenState extends ConsumerState<StudentPickupScreen> {
  List<dynamic> _items = [];
  bool _loading = true;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final result = await ref.read(studentPortalRepositoryProvider).getPickup();
    if (mounted) {
      setState(() {
        _items = (result.data?['data'] as List<dynamic>?) ?? [];
        _loading = false;
      });
    }
  }

  Future<void> _requestPickup() async {
    setState(() => _submitting = true);
    final result = await ref.read(studentPortalRepositoryProvider).requestPickup();
    setState(() => _submitting = false);
    if (result.success && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(result.data?['message'] ?? 'Permintaan dikirim'),
        backgroundColor: AppColors.success,
      ));
      _load();
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
        content: Text(result.message ?? 'Gagal meminta jemput'),
        backgroundColor: AppColors.error,
      ));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Permintaan Jemput'),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
      ),
      body: _loading
          ? const LoadingWidget(message: 'Memuat...')
          : Column(
              children: [
                Padding(
                  padding: const EdgeInsets.all(16),
                  child: SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _submitting ? null : _requestPickup,
                      icon: _submitting
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : const Icon(Icons.local_taxi_outlined),
                      label: const Text('Minta Jemput Sekarang'),
                    ),
                  ),
                ),
                const Divider(),
                Expanded(
                  child: _items.isEmpty
                      ? const Center(child: Text('Belum ada permintaan'))
                      : ListView.builder(
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          itemCount: _items.length,
                          itemBuilder: (context, i) {
                            final p = _items[i];
                            final status = p['status'] ?? 'pending';
                            final color = switch (status) {
                              'completed' => AppColors.success,
                              'notified' => AppColors.info,
                              _ => AppColors.warning,
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
                                  const Icon(Icons.local_taxi_outlined,
                                      color: AppColors.primary),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(p['requested_at'] ?? '-',
                                            style: const TextStyle(
                                                fontWeight: FontWeight.w600, fontSize: 13)),
                                        Text('Lat: ${p['latitude']} · Lng: ${p['longitude']}',
                                            style: const TextStyle(
                                                fontSize: 11, color: AppColors.textHint)),
                                      ],
                                    ),
                                  ),
                                  NamiraBadge(
                                    label: status.toUpperCase(),
                                    color: color,
                                    textColor: Colors.white,
                                    isSmall: true,
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                ),
              ],
            ),
    );
  }
}
