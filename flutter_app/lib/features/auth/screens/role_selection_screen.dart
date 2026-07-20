import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/core/utils/storage_utils.dart';
import 'package:superapp_namira_flutter/features/auth/providers/auth_provider.dart';
import 'package:superapp_namira_flutter/shared/widgets/avatar_widget.dart';

class RoleSelectionScreen extends ConsumerStatefulWidget {
  const RoleSelectionScreen({super.key});

  @override
  ConsumerState<RoleSelectionScreen> createState() => _RoleSelectionScreenState();
}

class _RoleSelectionScreenState extends ConsumerState<RoleSelectionScreen> {
  int? _selectedUnitId;
  String? _selectedRole;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    final authState = ref.read(authProvider);
    final units = authState.units ?? [];
    if (authState.activeUnitId != null) {
      _selectedUnitId = authState.activeUnitId;
    } else if (units.isNotEmpty) {
      _selectedUnitId = units.first['id'] as int?;
    }
  }

  IconData _roleIcon(String role) {
    switch (role) {
      case 'super_admin_yayasan':
      case 'admin_yayasan':
      case 'admin_unit':
      case 'staff_yayasan':
        return Icons.admin_panel_settings_outlined;
      case 'kepala_sekolah':
        return Icons.workspace_premium_outlined;
      case 'teacher':
      case 'guru':
        return Icons.school_outlined;
      case 'wali_kelas':
        return Icons.supervisor_account_outlined;
      case 'bk':
        return Icons.psychology_outlined;
      case 'siswa':
        return Icons.menu_book_outlined;
      case 'finance':
      case 'staff_admin_keuangan':
        return Icons.account_balance_wallet_outlined;
      case 'koordinator_sarpar':
      case 'staff_sarpar':
        return Icons.inventory_2_outlined;
      case 'humas':
        return Icons.campaign_outlined;
      case 'staff_kepegawaian':
        return Icons.people_outline;
      default:
        return Icons.person_outline;
    }
  }

  String _roleLabel(String role) {
    return role
        .split('_')
        .map((w) => w[0].toUpperCase() + w.substring(1))
        .join(' ');
  }

  Future<void> _continue() async {
    if (_selectedUnitId == null || _selectedRole == null) return;
    setState(() => _submitting = true);
    await ref.read(authProvider.notifier).switchUnit(_selectedUnitId!);
    await StorageUtils.saveActiveRole(_selectedRole!);
    if (mounted) context.go('/home');
    setState(() => _submitting = false);
  }

  Future<void> _refreshUnits() async {
    await ref.read(authProvider.notifier).refreshProfile();
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final units = authState.units ?? [];
    final roles = authState.roles;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        automaticallyImplyLeading: false,
        title: Text(
          'Pilih Workspace',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 18,
            fontWeight: FontWeight.w600,
            color: AppColors.textPrimary,
          ),
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: AvatarWidget(
              name: authState.userName,
              radius: 20,
              backgroundColor: AppColors.primaryContainer,
              textColor: AppColors.onPrimaryContainer,
            ),
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: RefreshIndicator(
              onRefresh: _refreshUnits,
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                children: [
                Text(
                  'Pilih unit sekolah dan peran Anda sebelum masuk ke aplikasi.',
                  style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                        color: AppColors.textSecondary,
                      ),
                ),
                const SizedBox(height: 16),
                ...units.map((unit) {
                  final unitId = unit['id'] as int;
                  final isSelected = _selectedUnitId == unitId;
                  return _UnitCard(
                    unit: unit,
                    isSelected: isSelected,
                    roles: roles,
                    selectedRole: _selectedRole,
                    onSelectUnit: () =>
                        setState(() => _selectedUnitId = unitId),
                    onSelectRole: (role) =>
                        setState(() => _selectedRole = role),
                    roleIcon: _roleIcon,
                    roleLabel: _roleLabel,
                  );
                }),
              ],
            ),
            ),
          ),
          Container(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
            decoration: BoxDecoration(
              color: Colors.white,
              border: const Border(
                top: BorderSide(color: AppColors.outlineVariant),
              ),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withAlpha(13),
                  blurRadius: 12,
                  offset: const Offset(0, -4),
                ),
              ],
            ),
            child: SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed:
                    (_selectedUnitId != null && _selectedRole != null && !_submitting)
                        ? _continue
                        : null,
                child: _submitting
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text('Lanjutkan'),
                          SizedBox(width: 8),
                          Icon(Icons.arrow_forward, size: 18),
                        ],
                      ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _UnitCard extends StatelessWidget {
  final Map<String, dynamic> unit;
  final bool isSelected;
  final List<String> roles;
  final String? selectedRole;
  final VoidCallback onSelectUnit;
  final ValueChanged<String> onSelectRole;
  final IconData Function(String) roleIcon;
  final String Function(String) roleLabel;

  const _UnitCard({
    required this.unit,
    required this.isSelected,
    required this.roles,
    required this.selectedRole,
    required this.onSelectUnit,
    required this.onSelectRole,
    required this.roleIcon,
    required this.roleLabel,
  });

  @override
  Widget build(BuildContext context) {
    final name = unit['name'] as String? ?? '';
    final category = unit['category'] as String? ?? '';
    final level = unit['level'] as String? ?? '';

    return AnimatedContainer(
      duration: const Duration(milliseconds: 250),
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isSelected ? AppColors.primary : AppColors.outlineVariant,
          width: isSelected ? 2 : 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withAlpha(isSelected ? 26 : 13),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          InkWell(
            onTap: onSelectUnit,
            borderRadius: BorderRadius.circular(16),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: AppColors.surfaceVariant,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(
                      Icons.account_balance_outlined,
                      color: AppColors.secondary,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          name,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w600,
                            color: AppColors.textPrimary,
                          ),
                        ),
                        const SizedBox(height: 4),
                        if (category.isNotEmpty || level.isNotEmpty)
                          Wrap(
                            spacing: 6,
                            crossAxisAlignment: WrapCrossAlignment.center,
                            children: [
                              if (category.isNotEmpty)
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 2,
                                  ),
                                  decoration: BoxDecoration(
                                    color: AppColors.primary.withAlpha(26),
                                    borderRadius: BorderRadius.circular(999),
                                  ),
                                  child: Text(
                                    category,
                                    style: const TextStyle(
                                      fontSize: 11,
                                      fontWeight: FontWeight.w600,
                                      color: Color(0xFF004D40),
                                    ),
                                  ),
                                ),
                              if (level.isNotEmpty)
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                    vertical: 2,
                                  ),
                                  decoration: BoxDecoration(
                                    color: AppColors.secondary.withAlpha(26),
                                    borderRadius: BorderRadius.circular(999),
                                  ),
                                  child: Text(
                                    level,
                                    style: const TextStyle(
                                      fontSize: 11,
                                      fontWeight: FontWeight.w600,
                                      color: AppColors.secondary,
                                    ),
                                  ),
                                ),
                            ],
                          ),
                      ],
                    ),
                  ),
                  Icon(
                    isSelected
                        ? Icons.expand_less
                        : Icons.chevron_right,
                    color: isSelected
                        ? AppColors.primary
                        : AppColors.textHint,
                  ),
                ],
              ),
            ),
          ),
          if (isSelected)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
              decoration: const BoxDecoration(
                border: Border(
                  top: BorderSide(color: AppColors.outlineVariant),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 8),
                    child: Text(
                      'Pilih Peran',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ),
                  if (roles.isEmpty)
                    const Text(
                      'Tidak ada peran tersedia untuk unit ini.',
                      style: TextStyle(
                        fontSize: 12,
                        color: AppColors.textSecondary,
                      ),
                    )
                  else
                    GridView.count(
                      crossAxisCount: 2,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      mainAxisSpacing: 8,
                      crossAxisSpacing: 8,
                      childAspectRatio: 3.0,
                      children: roles.map((role) {
                        final active = selectedRole == role;
                        return InkWell(
                          onTap: () => onSelectRole(role),
                          borderRadius: BorderRadius.circular(12),
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 8,
                            ),
                            decoration: BoxDecoration(
                              color: active
                                  ? AppColors.primary.withAlpha(20)
                                  : Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                              color: active
                                  ? AppColors.primary
                                  : AppColors.outlineVariant,
                              width: active ? 2 : 1,
                              ),
                            ),
                            child: Row(
                              children: [
                                Icon(
                                  roleIcon(role),
                                  size: 18,
                                  color: active
                                      ? AppColors.primary
                                      : AppColors.textSecondary,
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    roleLabel(role),
                                    style: TextStyle(
                                      fontSize: 12,
                                      fontWeight: active
                                          ? FontWeight.w600
                                          : FontWeight.w500,
                                      color: active
                                          ? AppColors.primary
                                          : AppColors.textPrimary,
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        );
                      }).toList(),
                    ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}
