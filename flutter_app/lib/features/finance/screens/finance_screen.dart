import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/finance/data/finance_repository.dart';
import 'package:superapp_namira_flutter/shared/widgets/loading_widget.dart';

class FinanceScreen extends ConsumerStatefulWidget {
  const FinanceScreen({super.key});

  @override
  ConsumerState<FinanceScreen> createState() => _FinanceScreenState();
}

class _FinanceScreenState extends ConsumerState<FinanceScreen> {
  Map<String, dynamic>? _dashboard;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  Future<void> _loadDashboard() async {
    setState(() => _loading = true);
    final repo = ref.read(financeRepositoryProvider);
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
    final recent = (_dashboard?['recent_transactions'] as List<dynamic>?) ?? [];

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Keuangan'),
        backgroundColor: AppColors.secondary,
        foregroundColor: Colors.white,
      ),
      body: _loading
          ? const LoadingWidget(message: 'Memuat data keuangan...')
          : RefreshIndicator(
              onRefresh: _loadDashboard,
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  if (stats != null) ...[
                    _buildStatsGrid(stats),
                    const SizedBox(height: 20),
                  ],
                  const Text(
                    'Transaksi Terakhir',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      color: AppColors.textPrimary,
                    ),
                  ),
                  const SizedBox(height: 12),
                  if (recent.isEmpty)
                    const Center(
                      child: Padding(
                        padding: EdgeInsets.all(32),
                        child: Text('Belum ada transaksi',
                            style: TextStyle(color: AppColors.textSecondary)),
                      ),
                    )
                  else
                    ...recent.map((t) => _buildTransactionTile(t)),
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
          icon: Icons.receipt_long_outlined,
          label: 'Total Tagihan',
          value: _formatCurrency(stats['total_bills'] ?? 0),
          color: AppColors.info,
        ),
        _StatCard(
          icon: Icons.payments_outlined,
          label: 'Terealisasi',
          value: _formatCurrency(stats['total_paid'] ?? 0),
          color: AppColors.success,
        ),
        _StatCard(
          icon: Icons.warning_amber_outlined,
          label: 'Tunggakan',
          value: _formatCurrency(stats['total_arrears'] ?? 0),
          color: AppColors.error,
        ),
        _StatCard(
          icon: Icons.calendar_month_outlined,
          label: 'Bulan Ini',
          value: _formatCurrency(stats['income_this_month'] ?? 0),
          color: AppColors.primary,
        ),
      ],
    );
  }

  Widget _buildTransactionTile(Map<String, dynamic> tx) {
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
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: AppColors.success.withAlpha(26),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(Icons.payment, color: AppColors.success, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  tx['student'] ?? '-',
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                ),
                Text(
                  '${tx['classroom'] ?? ''} | ${tx['date'] ?? ''}',
                  style: const TextStyle(
                    fontSize: 11,
                    color: AppColors.textSecondary,
                  ),
                ),
              ],
            ),
          ),
          Text(
            _formatCurrency(tx['amount'] ?? 0),
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w700,
              color: AppColors.success,
            ),
          ),
        ],
      ),
    );
  }

  String _formatCurrency(double amount) {
    if (amount >= 1000000000) return 'Rp${(amount / 1000000000).toStringAsFixed(1)}M';
    if (amount >= 1000000) return 'Rp${(amount / 1000000).toStringAsFixed(1)}jt';
    if (amount >= 1000) return 'Rp${(amount / 1000).toStringAsFixed(0)}rb';
    return 'Rp${amount.toStringAsFixed(0)}';
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
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w700,
              color: color,
            ),
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
