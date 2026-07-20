import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/notifications/data/notification_repository.dart';
import 'package:superapp_namira_flutter/shared/widgets/empty_state.dart';
import 'package:superapp_namira_flutter/shared/widgets/loading_widget.dart';

class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});

  @override
  ConsumerState<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  List<dynamic> _items = [];
  bool _loading = true;
  bool _markingAll = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final result = await ref.read(notificationRepositoryProvider).getNotifications();
    if (mounted) {
      setState(() {
        _items = (result.data?['data'] as List<dynamic>?) ?? [];
        _loading = false;
      });
    }
  }

  Future<void> _markRead(int id) async {
    await ref.read(notificationRepositoryProvider).markRead(id);
    _load();
  }

  Future<void> _markAll() async {
    setState(() => _markingAll = true);
    await ref.read(notificationRepositoryProvider).markAllRead();
    setState(() => _markingAll = false);
    _load();
  }

  @override
  Widget build(BuildContext context) {
    final unread = _items.where((n) => n['is_read'] == false).length;
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Notifikasi'),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        actions: [
          if (unread > 0)
            _markingAll
                ? const Padding(
                    padding: EdgeInsets.all(12),
                    child: SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    ),
                  )
                : TextButton(
                    onPressed: _markAll,
                    child: const Text('Tandai Semua',
                        style: TextStyle(color: Colors.white)),
                  ),
        ],
      ),
      body: _loading
          ? const LoadingWidget(message: 'Memuat notifikasi...')
          : _items.isEmpty
              ? const EmptyState(
                  icon: Icons.notifications_off_outlined,
                  title: 'Tidak ada notifikasi',
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    itemCount: _items.length,
                    itemBuilder: (context, i) => _buildTile(_items[i]),
                  ),
                ),
    );
  }

  Widget _buildTile(Map<String, dynamic> n) {
    final isUnread = n['is_read'] == false;
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
      decoration: BoxDecoration(
        color: isUnread ? AppColors.primary.withAlpha(13) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isUnread ? AppColors.primary.withAlpha(77) : AppColors.borderLight,
        ),
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(12),
          onTap: () {
            if (isUnread) _markRead(n['id']);
          },
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: isUnread
                        ? AppColors.primary.withAlpha(26)
                        : AppColors.surfaceVariant,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(
                    Icons.notifications_outlined,
                    size: 20,
                    color: isUnread ? AppColors.primary : AppColors.textSecondary,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(n['title'] ?? '-',
                          style: TextStyle(
                            fontSize: 14,
                            fontWeight:
                                isUnread ? FontWeight.w700 : FontWeight.w500,
                            color: AppColors.textPrimary,
                          )),
                      const SizedBox(height: 2),
                      Text(n['message'] ?? '',
                          style: const TextStyle(
                              fontSize: 12, color: AppColors.textSecondary)),
                      if (n['created_at'] != null)
                        Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: Text(n['created_at'],
                              style: const TextStyle(
                                  fontSize: 11, color: AppColors.textHint)),
                        ),
                    ],
                  ),
                ),
                if (isUnread)
                  Container(
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: AppColors.primary,
                      shape: BoxShape.circle,
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
