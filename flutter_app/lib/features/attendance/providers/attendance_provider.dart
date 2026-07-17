import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/features/attendance/data/attendance_repository.dart';

enum AttendanceStatus { initial, loading, checkedIn, checkedOut, notCheckedIn, error }

class AttendanceState {
  final AttendanceStatus status;
  final Map<String, dynamic>? todayAttendance;
  final Map<String, dynamic>? historyData;
  final String? errorMessage;

  const AttendanceState({
    this.status = AttendanceStatus.initial,
    this.todayAttendance,
    this.historyData,
    this.errorMessage,
  });

  AttendanceState copyWith({
    AttendanceStatus? status,
    Map<String, dynamic>? todayAttendance,
    Map<String, dynamic>? historyData,
    String? errorMessage,
  }) {
    return AttendanceState(
      status: status ?? this.status,
      todayAttendance: todayAttendance ?? this.todayAttendance,
      historyData: historyData ?? this.historyData,
      errorMessage: errorMessage,
    );
  }

  bool get hasCheckedIn => todayAttendance != null;
  bool get hasCheckedOut =>
      todayAttendance != null && todayAttendance!['check_out_time'] != null;
}

class AttendanceNotifier extends StateNotifier<AttendanceState> {
  final AttendanceRepository _repository;

  AttendanceNotifier(this._repository) : super(const AttendanceState()) {
    loadToday();
  }

  Future<void> loadToday() async {
    state = state.copyWith(status: AttendanceStatus.loading);
    try {
      final result = await _repository.getToday();
      if (result.success && result.data != null) {
        final attendance = result.data!['attendance'];
        state = state.copyWith(
          status: attendance != null
              ? (attendance['check_out_time'] != null
                  ? AttendanceStatus.checkedOut
                  : AttendanceStatus.checkedIn)
              : AttendanceStatus.notCheckedIn,
          todayAttendance: attendance,
        );
      } else {
        state = state.copyWith(
          status: AttendanceStatus.error,
          errorMessage: result.message,
        );
      }
    } catch (e) {
      debugPrint('[Attendance] Load error: $e');
      state = state.copyWith(
        status: AttendanceStatus.error,
        errorMessage: 'Gagal memuat data absensi',
      );
    }
  }

  Future<String?> checkIn({
    required String type,
    double? latitude,
    double? longitude,
    String? photo,
    String? note,
  }) async {
    state = state.copyWith(status: AttendanceStatus.loading);
    try {
      final result = await _repository.checkIn(
        type: type,
        latitude: latitude,
        longitude: longitude,
        photo: photo,
        note: note,
      );

      if (result.success && result.data != null) {
        final attendance = result.data!['attendance'];
        state = state.copyWith(
          status: AttendanceStatus.checkedIn,
          todayAttendance: attendance,
        );
        return null;
      }

      state = state.copyWith(
        status: AttendanceStatus.notCheckedIn,
        errorMessage: result.message,
      );
      return result.message;
    } catch (e) {
      debugPrint('[Attendance] Check-in error: $e');
      state = state.copyWith(
        status: AttendanceStatus.error,
        errorMessage: 'Gagal check-in',
      );
      return 'Gagal check-in';
    }
  }

  Future<String?> checkOut({
    required double latitude,
    required double longitude,
    String? photo,
  }) async {
    state = state.copyWith(status: AttendanceStatus.loading);
    try {
      final result = await _repository.checkOut(
        latitude: latitude,
        longitude: longitude,
        photo: photo,
      );

      if (result.success && result.data != null) {
        final attendance = result.data!['attendance'];
        state = state.copyWith(
          status: AttendanceStatus.checkedOut,
          todayAttendance: attendance,
        );
        return null;
      }

      state = state.copyWith(
        errorMessage: result.message,
      );
      return result.message;
    } catch (e) {
      debugPrint('[Attendance] Check-out error: $e');
      state = state.copyWith(
        status: AttendanceStatus.error,
        errorMessage: 'Gagal check-out',
      );
      return 'Gagal check-out';
    }
  }

  Future<void> loadHistory({String? month}) async {
    try {
      final result = await _repository.getHistory(month: month);
      if (result.success && result.data != null) {
        state = state.copyWith(historyData: result.data);
      }
    } catch (e) {
      debugPrint('[Attendance] History error: $e');
    }
  }
}

final attendanceProvider =
    StateNotifierProvider<AttendanceNotifier, AttendanceState>((ref) {
  final repository = ref.watch(attendanceRepositoryProvider);
  return AttendanceNotifier(repository);
});
