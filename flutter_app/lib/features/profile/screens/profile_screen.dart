import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/auth/providers/auth_provider.dart';
import 'package:superapp_namira_flutter/shared/widgets/avatar_widget.dart';
import 'package:superapp_namira_flutter/shared/widgets/namira_badge.dart';

class ProfileScreen extends ConsumerWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final user = authState.user;

    return Scaffold(
      backgroundColor: AppColors.background,
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            pinned: true,
            expandedHeight: 200,
            backgroundColor: AppColors.primary,
            foregroundColor: Colors.white,
            flexibleSpace: FlexibleSpaceBar(
              background: Container(
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    colors: [AppColors.primary, AppColors.primaryDark],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                ),
                child: SafeArea(
                  child: Center(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        AvatarWidget(
                          name: authState.userName,
                          radius: 32,
                          backgroundColor: Colors.white.withAlpha(51),
                          textColor: Colors.white,
                        ),
                        const SizedBox(height: 12),
                        Text(
                          authState.userName,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          authState.userEmail,
                          style: TextStyle(
                            fontSize: 13,
                            color: Colors.white.withAlpha(204),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 6,
                          runSpacing: 4,
                          alignment: WrapAlignment.center,
                          children: authState.roles
                              .map((r) => NamiraBadge(
                                    label: r.replaceAll('_', ' ').toUpperCase(),
                                    color: Colors.white.withAlpha(51),
                                    textColor: Colors.white,
                                    isSmall: true,
                                  ))
                              .toList(),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _buildSectionTitle('Unit Aktif'),
                  const SizedBox(height: 8),
                  _buildInfoTile(
                    icon: Icons.business_outlined,
                    label: 'Unit',
                    value: _getActiveUnitName(authState),
                  ),
                  const SizedBox(height: 20),
                  _buildSectionTitle('Informasi Akun'),
                  const SizedBox(height: 8),
                  if (user != null) ...[
                    _buildInfoTile(
                      icon: Icons.person_outline,
                      label: 'Nama Lengkap',
                      value: user['full_name'] ?? authState.userName,
                    ),
                    const SizedBox(height: 6),
                    _buildInfoTile(
                      icon: Icons.email_outlined,
                      label: 'Email',
                      value: user['email'] ?? authState.userEmail,
                    ),
                    if (user['phone'] != null && user['phone'].toString().isNotEmpty) ...[
                      const SizedBox(height: 6),
                      _buildInfoTile(
                        icon: Icons.phone_outlined,
                        label: 'Telepon',
                        value: user['phone'],
                      ),
                    ],
                    if (user['nip'] != null) ...[
                      const SizedBox(height: 6),
                      _buildInfoTile(
                        icon: Icons.badge_outlined,
                        label: 'NIP',
                        value: user['nip'],
                      ),
                    ],
                  ],
                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: () {
                        ref.read(authProvider.notifier).logout();
                        context.go('/login');
                      },
                      icon: const Icon(Icons.logout, color: AppColors.error),
                      label: const Text(
                        'Keluar',
                        style: TextStyle(color: AppColors.error),
                      ),
                      style: OutlinedButton.styleFrom(
                        side: const BorderSide(color: AppColors.error),
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 32),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Text(
      title,
      style: const TextStyle(
        fontSize: 16,
        fontWeight: FontWeight.w600,
        color: AppColors.textPrimary,
      ),
    );
  }

  String _getActiveUnitName(AuthState authState) {
    if (authState.units != null && authState.activeUnitId != null) {
      for (final u in authState.units!) {
        if (u['id'] == authState.activeUnitId) return u['name'] ?? '-';
      }
    }
    return '-';
  }

  Widget _buildInfoTile({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: AppColors.borderLight),
      ),
      child: Row(
        children: [
          Icon(icon, size: 20, color: AppColors.primary),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    fontSize: 11,
                    color: AppColors.textSecondary,
                  ),
                ),
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w500,
                    color: AppColors.textPrimary,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
