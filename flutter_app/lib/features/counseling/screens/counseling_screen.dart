import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/counseling/data/counseling_repository.dart';
import 'package:superapp_namira_flutter/shared/widgets/loading_widget.dart';
import 'package:superapp_namira_flutter/shared/widgets/namira_badge.dart';

class CounselingScreen extends ConsumerStatefulWidget {
  const CounselingScreen({super.key});

  @override
  ConsumerState<CounselingScreen> createState() => _CounselingScreenState();
}

class _CounselingScreenState extends ConsumerState<CounselingScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
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
        title: const Text('Konseling'),
        backgroundColor: AppColors.success,
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Pelanggaran'),
            Tab(text: 'Sesi'),
            Tab(text: 'Prestasi'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: const [
          _ViolationsTab(),
          _SessionsTab(),
          _AchievementsTab(),
        ],
      ),
    );
  }
}

class _ViolationsTab extends ConsumerStatefulWidget {
  const _ViolationsTab();

  @override
  ConsumerState<_ViolationsTab> createState() => _ViolationsTabState();
}

class _ViolationsTabState extends ConsumerState<_ViolationsTab> {
  List<dynamic> _violations = [];
  bool _loading = true;
  String _search = '';

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final repo = ref.read(counselingRepositoryProvider);
    final result = await repo.getViolations(search: _search);
    if (result.success && mounted) {
      setState(() {
        _violations = (result.data?['data'] as List<dynamic>?) ?? [];
        _loading = false;
      });
    } else if (mounted) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(12),
          child: TextField(
            decoration: InputDecoration(
              hintText: 'Cari pelanggaran...',
              prefixIcon: const Icon(Icons.search, size: 20),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            ),
            onSubmitted: (val) {
              _search = val;
              _load();
            },
          ),
        ),
        Expanded(
          child: _loading
              ? const LoadingWidget(message: 'Memuat pelanggaran...')
              : _violations.isEmpty
                  ? const Center(child: Text('Tidak ada pelanggaran'))
                  : RefreshIndicator(
                      onRefresh: _load,
                      child: ListView.builder(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        itemCount: _violations.length,
                        itemBuilder: (context, index) => _buildViolationTile(_violations[index]),
                      ),
                    ),
        ),
      ],
    );
  }

  Widget _buildViolationTile(Map<String, dynamic> v) {
    final severity = v['severity'] ?? 'ringan';
    final severityColor = switch (severity) {
      'berat' => AppColors.error,
      'sedang' => AppColors.warning,
      _ => AppColors.info,
    };

    return Container(
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
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: severityColor.withAlpha(26),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(Icons.gavel_outlined, size: 18, color: severityColor),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        v['student'] ?? '-',
                        style: const TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: AppColors.textPrimary,
                        ),
                      ),
                    ),
                    NamiraBadge(
                      label: severity.toUpperCase(),
                      color: severityColor,
                      textColor: Colors.white,
                      isSmall: true,
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  v['violation_category'] ?? v['description'] ?? '-',
                  style: const TextStyle(
                    fontSize: 12,
                    color: AppColors.textSecondary,
                  ),
                ),
                if (v['incident_date'] != null) ...[
                  const SizedBox(height: 2),
                  Text(
                    v['incident_date'],
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.textHint,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SessionsTab extends ConsumerStatefulWidget {
  const _SessionsTab();

  @override
  ConsumerState<_SessionsTab> createState() => _SessionsTabState();
}

class _SessionsTabState extends ConsumerState<_SessionsTab> {
  List<dynamic> _sessions = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final repo = ref.read(counselingRepositoryProvider);
    final result = await repo.getSessions();
    if (result.success && mounted) {
      setState(() {
        _sessions = (result.data?['data'] as List<dynamic>?) ?? [];
        _loading = false;
      });
    } else if (mounted) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return _loading
        ? const LoadingWidget(message: 'Memuat sesi konseling...')
        : _sessions.isEmpty
            ? const Center(child: Text('Tidak ada sesi konseling'))
            : RefreshIndicator(
                onRefresh: _load,
                child: ListView.builder(
                  padding: const EdgeInsets.all(12),
                  itemCount: _sessions.length,
                  itemBuilder: (context, index) => _buildSessionTile(_sessions[index]),
                ),
              );
  }

  Widget _buildSessionTile(Map<String, dynamic> s) {
    final status = s['status'] ?? 'scheduled';
    final statusColor = switch (status) {
      'completed' => AppColors.success,
      'in_progress' => AppColors.info,
      'cancelled' => AppColors.error,
      _ => AppColors.warning,
    };
    final statusLabel = switch (status) {
      'completed' => 'Selesai',
      'in_progress' => 'Berlangsung',
      'cancelled' => 'Dibatalkan',
      _ => 'Terjadwal',
    };

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.borderLight),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  s['student'] ?? '-',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
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
          if (s['type'] != null) ...[
            const SizedBox(height: 4),
            Text(
              'Tipe: ${s['type']}',
              style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
            ),
          ],
          if (s['session_date'] != null) ...[
            const SizedBox(height: 2),
            Text(
              '${s['session_date']} ${s['session_time'] ?? ''}',
              style: const TextStyle(fontSize: 11, color: AppColors.textHint),
            ),
          ],
        ],
      ),
    );
  }
}

class _AchievementsTab extends ConsumerStatefulWidget {
  const _AchievementsTab();

  @override
  ConsumerState<_AchievementsTab> createState() => _AchievementsTabState();
}

class _AchievementsTabState extends ConsumerState<_AchievementsTab> {
  List<dynamic> _achievements = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final repo = ref.read(counselingRepositoryProvider);
    final result = await repo.getAchievements();
    if (result.success && mounted) {
      setState(() {
        _achievements = (result.data?['data'] as List<dynamic>?) ?? [];
        _loading = false;
      });
    } else if (mounted) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return _loading
        ? const LoadingWidget(message: 'Memuat prestasi...')
        : _achievements.isEmpty
            ? const Center(child: Text('Tidak ada prestasi'))
            : RefreshIndicator(
                onRefresh: _load,
                child: ListView.builder(
                  padding: const EdgeInsets.all(12),
                  itemCount: _achievements.length,
                  itemBuilder: (context, index) => _buildAchievementTile(_achievements[index]),
                ),
              );
  }

  Widget _buildAchievementTile(Map<String, dynamic> a) {
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
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: AppColors.warning.withAlpha(26),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(Icons.emoji_events_outlined, size: 18, color: AppColors.warning),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  a['student'] ?? '-',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  a['title'] ?? a['achievement_type'] ?? '-',
                  style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                ),
                if (a['date'] != null)
                  Text(
                    a['date'],
                    style: const TextStyle(fontSize: 11, color: AppColors.textHint),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
