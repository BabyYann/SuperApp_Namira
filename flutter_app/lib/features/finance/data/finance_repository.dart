import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/core/api/api_response.dart';
import 'package:superapp_namira_flutter/core/api/dio_client.dart';

class FinanceRepository {
  final DioClient _client;
  FinanceRepository(this._client);

  Future<ApiResponse<Map<String, dynamic>>> getDashboard() async {
    try {
      final response = await _client.get('/finance/dashboard');
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat data keuangan: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getBills({
    String? search,
    String? status,
    int page = 1,
  }) async {
    try {
      final response = await _client.get('/finance/bills', queryParameters: {
        if (search != null && search.isNotEmpty) 'search': search,
        if (status != null) 'status': status,
        'page': page,
      });
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat tagihan: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getTransactions({int page = 1}) async {
    try {
      final response = await _client.get('/finance/transactions', queryParameters: {
        'page': page,
      });
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat transaksi: $e');
    }
  }
}

final financeRepositoryProvider = Provider<FinanceRepository>((ref) {
  return FinanceRepository(ref.watch(dioClientProvider));
});
