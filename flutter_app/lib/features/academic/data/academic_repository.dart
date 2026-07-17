import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/core/api/api_response.dart';
import 'package:superapp_namira_flutter/core/api/dio_client.dart';

class AcademicRepository {
  final DioClient _client;
  AcademicRepository(this._client);

  Future<ApiResponse<Map<String, dynamic>>> getSchedules({int? classroomId}) async {
    try {
      final response = await _client.get('/academic/schedules',
          queryParameters: {if (classroomId != null) 'classroom_id': classroomId});
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat jadwal: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getStudents({
    String? search,
    int? classroomId,
    int page = 1,
  }) async {
    try {
      final response = await _client.get('/academic/students', queryParameters: {
        if (search != null && search.isNotEmpty) 'search': search,
        if (classroomId != null) 'classroom_id': classroomId,
        'page': page,
      });
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat siswa: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getJournals({String? date}) async {
    try {
      final response = await _client.get('/academic/journals',
          queryParameters: {if (date != null) 'date': date});
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat jurnal: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getStudentAttendance({
    int? classroomId,
    String? date,
  }) async {
    try {
      final response = await _client.get('/academic/student-attendance', queryParameters: {
        if (classroomId != null) 'classroom_id': classroomId,
        if (date != null) 'date': date,
      });
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat absensi: $e');
    }
  }
}

final academicRepositoryProvider = Provider<AcademicRepository>((ref) {
  return AcademicRepository(ref.watch(dioClientProvider));
});
