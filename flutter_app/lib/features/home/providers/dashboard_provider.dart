import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/features/home/data/dashboard_repository.dart';

enum DashboardStatus { initial, loading, loaded, error }

class DashboardState {
  final DashboardStatus status;
  final Map<String, dynamic>? data;
  final String? errorMessage;

  const DashboardState({
    this.status = DashboardStatus.initial,
    this.data,
    this.errorMessage,
  });

  DashboardState copyWith({
    DashboardStatus? status,
    Map<String, dynamic>? data,
    String? errorMessage,
  }) {
    return DashboardState(
      status: status ?? this.status,
      data: data ?? this.data,
      errorMessage: errorMessage,
    );
  }

  Map<String, dynamic>? get unit => data?['unit'];
  Map<String, dynamic>? get academicYear => data?['academic_year'];
  Map<String, dynamic>? get stats => data?['stats'];
  List<dynamic>? get recentActivity => data?['recent_activity'];
  List<String> get roles {
    final r = data?['roles'];
    if (r is List) return r.map((e) => e.toString()).toList();
    return [];
  }
}

class DashboardNotifier extends StateNotifier<DashboardState> {
  final DashboardRepository _repository;

  DashboardNotifier(this._repository) : super(const DashboardState()) {
    load();
  }

  Future<void> load() async {
    state = state.copyWith(status: DashboardStatus.loading);
    try {
      final result = await _repository.getDashboard();
      if (result.success && result.data != null) {
        state = state.copyWith(
          status: DashboardStatus.loaded,
          data: result.data,
        );
      } else {
        state = state.copyWith(
          status: DashboardStatus.error,
          errorMessage: result.message,
        );
      }
    } catch (e) {
      debugPrint('[Dashboard] Load error: $e');
      state = state.copyWith(
        status: DashboardStatus.error,
        errorMessage: 'Gagal memuat data dashboard',
      );
    }
  }

  Future<void> refresh() async => load();
}

final dashboardProvider =
    StateNotifierProvider<DashboardNotifier, DashboardState>((ref) {
  final repository = ref.watch(dashboardRepositoryProvider);
  return DashboardNotifier(repository);
});
