import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/core/api/api_response.dart';
import 'package:superapp_namira_flutter/core/api/dio_client.dart';

class CounselingRepository {
  final DioClient _client;
  CounselingRepository(this._client);

  Future<ApiResponse<Map<String, dynamic>>> getViolations({
    String? search,
    int page = 1,
  }) async {
    try {
      final response = await _client.get('/counseling/violations', queryParameters: {
        if (search != null && search.isNotEmpty) 'search': search,
        'page': page,
      });
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat pelanggaran: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getSessions({
    String? search,
    String? status,
    int page = 1,
  }) async {
    try {
      final response = await _client.get('/counseling/sessions', queryParameters: {
        if (search != null && search.isNotEmpty) 'search': search,
        if (status != null) 'status': status,
        'page': page,
      });
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat sesi konseling: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getAchievements({
    String? search,
    int page = 1,
  }) async {
    try {
      final response = await _client.get('/counseling/achievements', queryParameters: {
        if (search != null && search.isNotEmpty) 'search': search,
        'page': page,
      });
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat prestasi: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getCategories({String? search}) async {
    try {
      final response = await _client.get('/counseling/categories', queryParameters: {
        if (search != null && search.isNotEmpty) 'search': search,
      });
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat kategori: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getSessionDetail(int id) async {
    try {
      final response = await _client.get('/counseling/sessions/$id');
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat detail sesi: $e');
    }
  }
}

final counselingRepositoryProvider = Provider<CounselingRepository>((ref) {
  return CounselingRepository(ref.watch(dioClientProvider));
});
