import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/core/api/api_response.dart';
import 'package:superapp_namira_flutter/core/api/dio_client.dart';
import 'package:superapp_namira_flutter/core/constants/app_constants.dart';

class LmsRepository {
  final DioClient _client;
  LmsRepository(this._client);

  Future<ApiResponse<Map<String, dynamic>>> getClassrooms() async {
    try {
      final response = await _client.get(ApiEndpoints.lmsClassrooms);
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat kelas: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getClassroomDetail(int id) async {
    try {
      final response = await _client.get('${ApiEndpoints.lmsClassrooms}/$id');
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat detail kelas: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getMaterial(int id) async {
    try {
      final response = await _client.get('${ApiEndpoints.lmsMaterials}/$id');
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat materi: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getAssignment(int id) async {
    try {
      final response = await _client.get('${ApiEndpoints.lmsAssignments}/$id');
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat tugas: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getMyTasks() async {
    try {
      final response = await _client.get(ApiEndpoints.lmsMyTasks);
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat tugas saya: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getGradebook(int classroomId) async {
    try {
      final response =
          await _client.get('${ApiEndpoints.lmsGradebook}/$classroomId');
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat buku nilai: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> submitAssignment(
    int id,
    String? text,
  ) async {
    try {
      final response = await _client.post(
        '${ApiEndpoints.lmsAssignments}/$id/submit',
        data: {if (text != null && text.isNotEmpty) 'submission_text': text},
      );
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal mengumpulkan tugas: $e');
    }
  }
}

final lmsRepositoryProvider = Provider<LmsRepository>((ref) {
  return LmsRepository(ref.watch(dioClientProvider));
});
