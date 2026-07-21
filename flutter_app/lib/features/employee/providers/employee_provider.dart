import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/core/api/dio_client.dart';
import 'package:superapp_namira_flutter/features/employee/data/employee_repository.dart';

final employeeRepositoryProvider = Provider<EmployeeRepository>((ref) {
  return EmployeeRepository(ref.watch(dioClientProvider));
});

enum EmployeeStatus { idle, loading, loadingToday, loadingHistory, loadingStaff, checkingIn, checkingOut, success, error }

class EmployeeState {
  final EmployeeStatus status;
  final String? errorMessage;
  final Map<String, dynamic>? todayAttendance;
  final Map<String, dynamic>? historyData;
  final List<dynamic> locations;
  final Map<String, dynamic>? staffData;
  final List<dynamic> attendanceRecap;
  final String? successMessage;
  final int historyPage;
  final bool hasMoreHistory;

  const EmployeeState({
    this.status = EmployeeStatus.idle,
    this.errorMessage,
    this.todayAttendance,
    this.historyData,
    this.locations = const [],
    this.staffData,
    this.attendanceRecap = const [],
    this.successMessage,
    this.historyPage = 1,
    this.hasMoreHistory = true,
  });

  EmployeeState copyWith({
    EmployeeStatus? status,
    String? errorMessage,
    Map<String, dynamic>? todayAttendance,
    Map<String, dynamic>? historyData,
    List<dynamic>? locations,
    Map<String, dynamic>? staffData,
    List<dynamic>? attendanceRecap,
    String? successMessage,
    int? historyPage,
    bool? hasMoreHistory,
  }) {
    return EmployeeState(
      status: status ?? this.status,
      errorMessage: errorMessage,
      todayAttendance: todayAttendance ?? this.todayAttendance,
      historyData: historyData ?? this.historyData,
      locations: locations ?? this.locations,
      staffData: staffData ?? this.staffData,
      attendanceRecap: attendanceRecap ?? this.attendanceRecap,
      successMessage: successMessage,
      historyPage: historyPage ?? this.historyPage,
      hasMoreHistory: hasMoreHistory ?? this.hasMoreHistory,
    );
  }
}

class EmployeeNotifier extends StateNotifier<EmployeeState> {
  final EmployeeRepository _repository;

  EmployeeNotifier(this._repository) : super(const EmployeeState());

  Future<void> loadToday() async {
    state = state.copyWith(status: EmployeeStatus.loadingToday, errorMessage: null);
    final result = await _repository.getTodayAttendance();
    if (result.success) {
      state = state.copyWith(
        status: EmployeeStatus.success,
        todayAttendance: result.data,
      );
    } else {
      state = state.copyWith(status: EmployeeStatus.error, errorMessage: result.message);
    }
  }

  Future<String?> checkIn({
    required String type,
    double? latitude,
    double? longitude,
    String? photo,
    String? note,
    int? locationId,
  }) async {
    state = state.copyWith(status: EmployeeStatus.checkingIn, errorMessage: null);
    final result = await _repository.checkIn(
      type: type,
      latitude: latitude,
      longitude: longitude,
      photo: photo,
      note: note,
      locationId: locationId,
    );
    if (result.success) {
      state = state.copyWith(
        status: EmployeeStatus.success,
        successMessage: result.message,
        todayAttendance: result.data,
      );
      return null;
    } else {
      state = state.copyWith(status: EmployeeStatus.error, errorMessage: result.message);
      return result.message;
    }
  }

  Future<String?> checkOut({
    required double latitude,
    required double longitude,
    String? photo,
  }) async {
    state = state.copyWith(status: EmployeeStatus.checkingOut, errorMessage: null);
    final result = await _repository.checkOut(
      latitude: latitude,
      longitude: longitude,
      photo: photo,
    );
    if (result.success) {
      state = state.copyWith(
        status: EmployeeStatus.success,
        successMessage: result.message,
        todayAttendance: result.data,
      );
      return null;
    } else {
      state = state.copyWith(status: EmployeeStatus.error, errorMessage: result.message);
      return result.message;
    }
  }

  Future<void> loadHistory({String? month, bool loadMore = false}) async {
    final page = loadMore ? state.historyPage + 1 : 1;
    state = state.copyWith(
      status: loadMore ? state.status : EmployeeStatus.loadingHistory,
      historyPage: page,
      errorMessage: null,
    );
    final result = await _repository.getHistory(month: month, page: page);
    if (result.success) {
      final newData = result.data;
      final records = (newData?['data'] as List<dynamic>?) ?? [];
      final totalPages = (newData?['last_page'] as int?) ?? 1;

      if (loadMore && state.historyData != null) {
        final existing = (state.historyData!['data'] as List<dynamic>?) ?? [];
        newData?['data'] = [...existing, ...records];
      }

      state = state.copyWith(
        status: EmployeeStatus.success,
        historyData: newData,
        hasMoreHistory: page < totalPages,
      );
    } else {
      state = state.copyWith(status: EmployeeStatus.error, errorMessage: result.message);
    }
  }

  Future<void> loadLocations() async {
    final result = await _repository.getLocations();
    if (result.success) {
      state = state.copyWith(locations: result.data ?? []);
    }
  }

  Future<void> loadStaff({String? search, bool loadMore = false}) async {
    final page = loadMore ? ((state.staffData?['current_page'] as int?) ?? 1) + 1 : 1;
    state = state.copyWith(
      status: loadMore ? state.status : EmployeeStatus.loadingStaff,
      errorMessage: null,
    );
    final result = await _repository.getStaffList(search: search, page: page);
    if (result.success) {
      final newData = result.data;
      if (loadMore && state.staffData != null) {
        final existing = (state.staffData!['data'] as List<dynamic>?) ?? [];
        final newRecords = (newData?['data'] as List<dynamic>?) ?? [];
        newData?['data'] = [...existing, ...newRecords];
      }
      state = state.copyWith(status: EmployeeStatus.success, staffData: newData);
    } else {
      state = state.copyWith(status: EmployeeStatus.error, errorMessage: result.message);
    }
  }

  Future<void> loadAttendanceRecap({String? startDate, String? endDate}) async {
    state = state.copyWith(status: EmployeeStatus.loading, errorMessage: null);
    final result = await _repository.getAttendanceRecap(startDate: startDate, endDate: endDate);
    if (result.success) {
      state = state.copyWith(status: EmployeeStatus.success, attendanceRecap: result.data ?? []);
    } else {
      state = state.copyWith(status: EmployeeStatus.error, errorMessage: result.message);
    }
  }

  void clearMessages() {
    state = state.copyWith(successMessage: null, errorMessage: null);
  }
}

final employeeProvider = StateNotifierProvider<EmployeeNotifier, EmployeeState>((ref) {
  return EmployeeNotifier(ref.read(employeeRepositoryProvider));
});
