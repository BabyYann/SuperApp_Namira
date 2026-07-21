import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:superapp_namira_flutter/config/theme.dart';
import 'package:superapp_namira_flutter/features/employee/providers/employee_provider.dart';

class EmployeeStaffListScreen extends ConsumerStatefulWidget {
  const EmployeeStaffListScreen({super.key});

  @override
  ConsumerState<EmployeeStaffListScreen> createState() => _EmployeeStaffListScreenState();
}

class _EmployeeStaffListScreenState extends ConsumerState<EmployeeStaffListScreen> {
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(employeeProvider.notifier).loadStaff());
    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.removeListener(_onScroll);
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent - 200) {
      final state = ref.read(employeeProvider);
      if (state.status != EmployeeStatus.loadingStaff) {
        ref.read(employeeProvider.notifier).loadStaff(loadMore: true);
      }
    }
  }

  void _onSearch(String value) {
    ref.read(employeeProvider.notifier).loadStaff(search: value);
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(employeeProvider);
    final staffList = (state.staffData?['data'] as List<dynamic>?) ?? [];

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: Text(
          'Daftar Staff',
          style: GoogleFonts.plusJakartaSans(fontSize: 18, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
        ),
        backgroundColor: AppColors.surface,
        foregroundColor: AppColors.textPrimary,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
      ),
      body: Column(
        children: [
          Container(
            color: Colors.white,
            padding: const EdgeInsets.fromLTRB(24, 8, 24, 16),
            child: SizedBox(
              height: 44,
              child: TextField(
                controller: _searchController,
                onChanged: _onSearch,
                style: GoogleFonts.plusJakartaSans(fontSize: 14, color: AppColors.textPrimary),
                decoration: InputDecoration(
                  hintText: 'Cari staff...',
                  hintStyle: GoogleFonts.plusJakartaSans(fontSize: 14, color: AppColors.border),
                  prefixIcon: const Icon(Icons.search_outlined, size: 20),
                  prefixIconColor: AppColors.textOnSurfaceVariant,
                  suffixIcon: _searchController.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.close, size: 18),
                          onPressed: () {
                            _searchController.clear();
                            _onSearch('');
                          },
                        )
                      : null,
                  filled: true,
                  fillColor: AppColors.surface,
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(10), borderSide: BorderSide.none),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16),
                ),
              ),
            ),
          ),
          Expanded(
            child: state.status == EmployeeStatus.loadingStaff && staffList.isEmpty
                ? const Center(child: CircularProgressIndicator())
                : staffList.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.people_outline, size: 48, color: AppColors.border),
                            const SizedBox(height: 12),
                            Text('Tidak ada data staff', style: GoogleFonts.plusJakartaSans(fontSize: 14, color: AppColors.border)),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: () => ref.read(employeeProvider.notifier).loadStaff(search: _searchController.text),
                        child: ListView.separated(
                          controller: _scrollController,
                          padding: const EdgeInsets.all(24),
                          itemCount: staffList.length + (state.staffData?['next_page_url'] != null ? 1 : 0),
                          separatorBuilder: (_, __) => const SizedBox(height: 8),
                          itemBuilder: (context, index) {
                            if (index == staffList.length) {
                              return const Center(child: Padding(padding: EdgeInsets.all(16), child: CircularProgressIndicator(strokeWidth: 2)));
                            }
                            final s = staffList[index] as Map<String, dynamic>;
                            return Container(
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: AppColors.outlineVariant.withAlpha(77)),
                              ),
                              child: Row(
                                children: [
                                  CircleAvatar(
                                    radius: 22,
                                    backgroundColor: AppColors.primary.withAlpha(26),
                                    child: Text(
                                      (s['name'] as String? ?? '?')[0].toUpperCase(),
                                      style: GoogleFonts.plusJakartaSans(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.primary),
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          s['name'] ?? '-',
                                          style: GoogleFonts.plusJakartaSans(fontSize: 14, fontWeight: FontWeight.w600, color: AppColors.textPrimary),
                                        ),
                                        if (s['position'] != null)
                                          Text(
                                            s['position'],
                                            style: GoogleFonts.plusJakartaSans(fontSize: 12, color: AppColors.textOnSurfaceVariant),
                                          ),
                                        if (s['nip'] != null)
                                          Text(
                                            'NIP: ${s['nip']}',
                                            style: GoogleFonts.plusJakartaSans(fontSize: 11, color: AppColors.border),
                                          ),
                                      ],
                                    ),
                                  ),
                                  if (s['is_active'] == false)
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: AppColors.error.withAlpha(26),
                                        borderRadius: BorderRadius.circular(6),
                                      ),
                                      child: Text(
                                        'Nonaktif',
                                        style: GoogleFonts.plusJakartaSans(fontSize: 10, fontWeight: FontWeight.w600, color: AppColors.error),
                                      ),
                                    ),
                                ],
                              ),
                            );
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }
}
