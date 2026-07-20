import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/core/api/api_response.dart';
import 'package:superapp_namira_flutter/core/api/dio_client.dart';
import 'package:superapp_namira_flutter/core/constants/app_constants.dart';

class StudentPortalRepository {
  final DioClient _client;
  StudentPortalRepository(this._client);

  Future<ApiResponse<Map<String, dynamic>>> getDashboard() async {
    try {
      final response = await _client.get(ApiEndpoints.studentDashboard);
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat dashboard: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getTasks() async {
    try {
      final response = await _client.get(ApiEndpoints.studentTasks);
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat tugas: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getPickup() async {
    try {
      final response = await _client.get(ApiEndpoints.studentPickup);
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat permintaan jemput: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> requestPickup({
    double? lat,
    double? lng,
  }) async {
    try {
      final response = await _client.post(
        ApiEndpoints.studentPickup,
        data: {
          if (lat != null) 'latitude': lat,
          if (lng != null) 'longitude': lng,
        },
      );
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal meminta jemput: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getGrades() async {
    try {
      final response = await _client.get(ApiEndpoints.studentGrades);
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat nilai: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getSchedule() async {
    try {
      final response = await _client.get(ApiEndpoints.studentSchedule);
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat jadwal: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getProfile() async {
    try {
      final response = await _client.get(ApiEndpoints.studentProfile);
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat profil: $e');
    }
  }
}

final studentPortalRepositoryProvider =
    Provider<StudentPortalRepository>((ref) {
  return StudentPortalRepository(ref.watch(dioClientProvider));
});
