import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/sarpar/data/sarpar_repository.dart';
import 'package:superapp_namira_flutter/shared/widgets/loading_widget.dart';
import 'package:superapp_namira_flutter/shared/widgets/namira_badge.dart';

class SarparScreen extends ConsumerStatefulWidget {
  const SarparScreen({super.key});

  @override
  ConsumerState<SarparScreen> createState() => _SarparScreenState();
}

class _SarparScreenState extends ConsumerState<SarparScreen> {
  Map<String, dynamic>? _dashboard;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final repo = ref.read(sarparRepositoryProvider);
    final result = await repo.getDashboard();
    if (result.success && mounted) {
      setState(() {
        _dashboard = result.data;
        _loading = false;
      });
    } else if (mounted) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final stats = _dashboard?['stats'] as Map<String, dynamic>?;
    final alerts = (_dashboard?['alerts'] as List<dynamic>?) ?? [];

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Sarana & Prasarana'),
        backgroundColor: AppColors.warning,
        foregroundColor: Colors.white,
      ),
      body: _loading
          ? const LoadingWidget(message: 'Memuat data sarpar...')
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (stats != null) ...[
                    _buildStatsGrid(stats),
                    const SizedBox(height: 20),
                  ],
                  const Text(
                    'Pemeliharaan',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 12),
                  if (stats != null) ...[
                    _buildMaintenanceRow('Menunggu Perbaikan', '${stats['pending_maintenance'] ?? 0}', AppColors.warning),
                    _buildMaintenanceRow('Sedang Dikerjakan', '${stats['in_progress_maintenance'] ?? 0}', AppColors.info),
                    _buildMaintenanceRow('Selesai Bulan Ini', '${stats['completed_maintenance'] ?? 0}', AppColors.success),
                  ],
                  if (alerts.isNotEmpty) ...[
                    const SizedBox(height: 20),
                    const Text(
                      'Peringatan',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: AppColors.error,
                      ),
                    ),
                    const SizedBox(height: 12),
                    ...alerts.map((a) => _buildAlertTile(a)),
                  ],
                ],
              ),
            ),
    );
  }

  Widget _buildStatsGrid(Map<String, dynamic> stats) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 10,
      crossAxisSpacing: 10,
      childAspectRatio: 1.6,
      children: [
        _StatCard(
          icon: Icons.inventory_2_outlined,
          label: 'Total Barang',
          value: '${stats['total_inventories'] ?? 0}',
          color: AppColors.primary,
        ),
        _StatCard(
          icon: Icons.check_circle_outline,
          label: 'Tersedia',
          value: '${stats['available'] ?? 0}',
          color: AppColors.success,
        ),
        _StatCard(
          icon: Icons.build_outlined,
          label: 'Diperbaiki',
          value: '${stats['maintenance'] ?? 0}',
          color: AppColors.warning,
        ),
        _StatCard(
          icon: Icons.bookmark_outline,
          label: 'Dipinjam',
          value: '${stats['loaned'] ?? 0}',
          color: AppColors.accent,
        ),
      ],
    );
  }

  Widget _buildMaintenanceRow(String label, String value, Color color) {
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
          Container(
            width: 4,
            height: 32,
            decoration: BoxDecoration(
              color: color,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color: AppColors.textPrimary,
              ),
            ),
          ),
          NamiraBadge(label: value, color: color, textColor: Colors.white, isSmall: true),
        ],
      ),
    );
  }

  Widget _buildAlertTile(Map<String, dynamic> alert) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.error.withAlpha(13),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.error.withAlpha(51)),
      ),
      child: Row(
        children: [
          const Icon(Icons.warning_amber_outlined, size: 20, color: AppColors.error),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              alert['message'] ?? alert['description'] ?? '-',
              style: const TextStyle(
                fontSize: 12,
                color: AppColors.textPrimary,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color color;

  const _StatCard({
    required this.icon,
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.borderLight),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: color.withAlpha(26),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, size: 18, color: color),
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: color),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: const TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w500,
              color: AppColors.textSecondary,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }
}
