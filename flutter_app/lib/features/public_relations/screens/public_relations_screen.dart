import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/public_relations/data/public_relations_repository.dart';
import 'package:superapp_namira_flutter/shared/widgets/loading_widget.dart';
import 'package:superapp_namira_flutter/shared/widgets/namira_badge.dart';

class PublicRelationsScreen extends ConsumerStatefulWidget {
  const PublicRelationsScreen({super.key});

  @override
  ConsumerState<PublicRelationsScreen> createState() =>
      _PublicRelationsScreenState();
}

class _PublicRelationsScreenState extends ConsumerState<PublicRelationsScreen>
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
        title: const Text('Humas'),
        backgroundColor: AppColors.secondary,
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Berita'),
            Tab(text: 'Agenda'),
            Tab(text: 'Kunjungan'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: const [
          _NewsTab(),
          _EventsTab(),
          _DestinationsTab(),
        ],
      ),
    );
  }
}

class _NewsTab extends ConsumerStatefulWidget {
  const _NewsTab();

  @override
  ConsumerState<_NewsTab> createState() => _NewsTabState();
}

class _NewsTabState extends ConsumerState<_NewsTab> {
  List<dynamic> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final result = await ref.read(publicRelationsRepositoryProvider).getNews();
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
        ? const LoadingWidget(message: 'Memuat berita...')
        : ListView.builder(
            padding: const EdgeInsets.symmetric(vertical: 8),
            itemCount: _items.length,
            itemBuilder: (context, i) {
              final n = _items[i];
              return Container(
                margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.borderLight),
                ),
                child: Material(
                  color: Colors.transparent,
                  child: InkWell(
                    borderRadius: BorderRadius.circular(12),
                    onTap: () => context.push('/pr/news/${n['id']}'),
                    child: Padding(
                      padding: const EdgeInsets.all(14),
                      child: Row(
                        children: [
                          Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              color: AppColors.primary.withAlpha(26),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Icon(Icons.article_outlined,
                                color: AppColors.primary),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(n['title'] ?? '-',
                                    style: const TextStyle(
                                        fontWeight: FontWeight.w600, fontSize: 14)),
                                if (n['published_at'] != null)
                                  Text(n['published_at'],
                                      style: const TextStyle(
                                          fontSize: 11, color: AppColors.textHint)),
                              ],
                            ),
                          ),
                          const Icon(Icons.chevron_right,
                              color: AppColors.textHint),
                        ],
                      ),
                    ),
                  ),
                ),
              );
            },
          );
  }
}

class _EventsTab extends ConsumerStatefulWidget {
  const _EventsTab();

  @override
  ConsumerState<_EventsTab> createState() => _EventsTabState();
}

class _EventsTabState extends ConsumerState<_EventsTab> {
  List<dynamic> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final result = await ref.read(publicRelationsRepositoryProvider).getEvents();
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
        ? const LoadingWidget(message: 'Memuat agenda...')
        : ListView.builder(
            padding: const EdgeInsets.symmetric(vertical: 8),
            itemCount: _items.length,
            itemBuilder: (context, i) {
              final e = _items[i];
              final status = e['computed_status'] ?? e['status'] ?? '';
              final statusColor = switch (status) {
                'ongoing' => AppColors.success,
                'completed' => AppColors.textHint,
                'cancelled' => AppColors.error,
                _ => AppColors.info,
              };
              return Container(
                margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.borderLight),
                ),
                child: Material(
                  color: Colors.transparent,
                  child: InkWell(
                    borderRadius: BorderRadius.circular(12),
                    onTap: () => context.push('/pr/events/${e['id']}'),
                    child: Padding(
                      padding: const EdgeInsets.all(14),
                      child: Row(
                        children: [
                          Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              color: AppColors.secondary.withAlpha(26),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Icon(Icons.event_outlined,
                                color: AppColors.secondary),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(e['title'] ?? '-',
                                    style: const TextStyle(
                                        fontWeight: FontWeight.w600, fontSize: 14)),
                                if (e['start_date'] != null)
                                  Text(e['start_date'],
                                      style: const TextStyle(
                                          fontSize: 11, color: AppColors.textHint)),
                              ],
                            ),
                          ),
                          NamiraBadge(
                            label: status.toUpperCase(),
                            color: statusColor,
                            textColor: Colors.white,
                            isSmall: true,
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              );
            },
          );
  }
}

class _DestinationsTab extends ConsumerStatefulWidget {
  const _DestinationsTab();

  @override
  ConsumerState<_DestinationsTab> createState() => _DestinationsTabState();
}

class _DestinationsTabState extends ConsumerState<_DestinationsTab> {
  List<dynamic> _items = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final result =
        await ref.read(publicRelationsRepositoryProvider).getDestinations();
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
        ? const LoadingWidget(message: 'Memuat tujuan kunjungan...')
        : ListView.builder(
            padding: const EdgeInsets.symmetric(vertical: 8),
            itemCount: _items.length,
            itemBuilder: (context, i) {
              final d = _items[i];
              return Container(
                margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.borderLight),
                ),
                child: Material(
                  color: Colors.transparent,
                  child: InkWell(
                    borderRadius: BorderRadius.circular(12),
                    onTap: () => context.push('/pr/destinations/${d['id']}'),
                    child: Padding(
                      padding: const EdgeInsets.all(14),
                      child: Row(
                        children: [
                          Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              color: AppColors.success.withAlpha(26),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Icon(Icons.place_outlined,
                                color: AppColors.success),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(d['name'] ?? '-',
                                    style: const TextStyle(
                                        fontWeight: FontWeight.w600, fontSize: 14)),
                                Text('${d['city'] ?? ''} ${d['country'] ?? ''}',
                                    style: const TextStyle(
                                        fontSize: 12, color: AppColors.textSecondary)),
                              ],
                            ),
                          ),
                          const Icon(Icons.chevron_right,
                              color: AppColors.textHint),
                        ],
                      ),
                    ),
                  ),
                ),
              );
            },
          );
  }
}

class PrNewsDetailScreen extends ConsumerStatefulWidget {
  final int id;
  const PrNewsDetailScreen({super.key, required this.id});

  @override
  ConsumerState<PrNewsDetailScreen> createState() => _PrNewsDetailScreenState();
}

class _PrNewsDetailScreenState extends ConsumerState<PrNewsDetailScreen> {
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
        await ref.read(publicRelationsRepositoryProvider).getNewsDetail(widget.id);
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
        title: const Text('Detail Berita'),
        backgroundColor: AppColors.secondary,
        foregroundColor: Colors.white,
      ),
      body: _loading
          ? const LoadingWidget(message: 'Memuat...')
          : _data == null
              ? const Center(child: Text('Berita tidak ditemukan'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(_data!['title'] ?? '-',
                          style: const TextStyle(
                              fontSize: 20, fontWeight: FontWeight.w700)),
                      const SizedBox(height: 8),
                      Text(_data!['published_at'] ?? '',
                          style: const TextStyle(color: AppColors.textHint, fontSize: 12)),
                      const SizedBox(height: 16),
                      Text(_data!['content'] ?? '-',
                          style: const TextStyle(fontSize: 14, height: 1.6)),
                    ],
                  ),
                ),
    );
  }
}

class PrEventDetailScreen extends ConsumerStatefulWidget {
  final int id;
  const PrEventDetailScreen({super.key, required this.id});

  @override
  ConsumerState<PrEventDetailScreen> createState() => _PrEventDetailScreenState();
}

class _PrEventDetailScreenState extends ConsumerState<PrEventDetailScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final result = await ref
        .read(publicRelationsRepositoryProvider)
        .getEventDetail(widget.id);
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
        title: const Text('Detail Agenda'),
        backgroundColor: AppColors.secondary,
        foregroundColor: Colors.white,
      ),
      body: _loading
          ? const LoadingWidget(message: 'Memuat...')
          : _data == null
              ? const Center(child: Text('Agenda tidak ditemukan'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(_data!['title'] ?? '-',
                          style: const TextStyle(
                              fontSize: 20, fontWeight: FontWeight.w700)),
                      const SizedBox(height: 8),
                      _row(Icons.event_outlined, _data!['start_date'] ?? '-'),
                      _row(Icons.location_on_outlined, _data!['location'] ?? '-'),
                      _row(Icons.person_outline, _data!['contact_person'] ?? '-'),
                      const SizedBox(height: 16),
                      Text(_data!['description'] ?? '-',
                          style: const TextStyle(fontSize: 14, height: 1.6)),
                    ],
                  ),
                ),
    );
  }

  Widget _row(IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Icon(icon, size: 16, color: AppColors.textSecondary),
          const SizedBox(width: 8),
          Expanded(
            child: Text(text,
                style: const TextStyle(fontSize: 13, color: AppColors.textSecondary)),
          ),
        ],
      ),
    );
  }
}

class PrDestinationDetailScreen extends ConsumerStatefulWidget {
  final int id;
  const PrDestinationDetailScreen({super.key, required this.id});

  @override
  ConsumerState<PrDestinationDetailScreen> createState() =>
      _PrDestinationDetailScreenState();
}

class _PrDestinationDetailScreenState
    extends ConsumerState<PrDestinationDetailScreen> {
  Map<String, dynamic>? _data;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final result = await ref
        .read(publicRelationsRepositoryProvider)
        .getDestinationDetail(widget.id);
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
        title: const Text('Detail Kunjungan'),
        backgroundColor: AppColors.secondary,
        foregroundColor: Colors.white,
      ),
      body: _loading
          ? const LoadingWidget(message: 'Memuat...')
          : _data == null
              ? const Center(child: Text('Tujuan tidak ditemukan'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(_data!['name'] ?? '-',
                          style: const TextStyle(
                              fontSize: 20, fontWeight: FontWeight.w700)),
                      const SizedBox(height: 8),
                      _row(Icons.place_outlined,
                          '${_data!['city'] ?? ''} ${_data!['country'] ?? ''}'),
                      _row(Icons.category_outlined, _data!['type'] ?? '-'),
                      _row(Icons.flight_outlined, _data!['visit_type'] ?? '-'),
                      _row(Icons.calendar_today_outlined, _data!['visit_date'] ?? '-'),
                      const SizedBox(height: 16),
                      Text(_data!['description'] ?? '-',
                          style: const TextStyle(fontSize: 14, height: 1.6)),
                    ],
                  ),
                ),
    );
  }

  Widget _row(IconData icon, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Icon(icon, size: 16, color: AppColors.textSecondary),
          const SizedBox(width: 8),
          Expanded(
            child: Text(text,
                style: const TextStyle(fontSize: 13, color: AppColors.textSecondary)),
          ),
        ],
      ),
    );
  }
}
