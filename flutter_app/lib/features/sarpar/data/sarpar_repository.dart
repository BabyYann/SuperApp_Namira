import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/core/api/api_response.dart';
import 'package:superapp_namira_flutter/core/api/dio_client.dart';

class SarparRepository {
  final DioClient _client;
  SarparRepository(this._client);

  Future<ApiResponse<Map<String, dynamic>>> getDashboard() async {
    try {
      final response = await _client.get('/sarpar/dashboard');
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat data sarpar: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getInventories({
    String? search,
    String? status,
    int page = 1,
  }) async {
    try {
      final response = await _client.get('/sarpar/inventories', queryParameters: {
        if (search != null && search.isNotEmpty) 'search': search,
        if (status != null) 'status': status,
        'page': page,
      });
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat inventaris: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getLoans({int page = 1}) async {
    try {
      final response = await _client.get('/sarpar/loans', queryParameters: {'page': page});
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat peminjaman: $e');
    }
  }
}

final sarparRepositoryProvider = Provider<SarparRepository>((ref) {
  return SarparRepository(ref.watch(dioClientProvider));
});
