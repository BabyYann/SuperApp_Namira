import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/core/api/api_response.dart';
import 'package:superapp_namira_flutter/core/api/dio_client.dart';
import 'package:superapp_namira_flutter/core/constants/app_constants.dart';

class DashboardRepository {
  final DioClient _client;

  DashboardRepository(this._client);

  Future<ApiResponse<Map<String, dynamic>>> getDashboard() async {
    try {
      final response = await _client.get(ApiEndpoints.dashboard);
      final data = response.data as Map<String, dynamic>;
      return ApiResponse.success(data);
    } catch (e) {
      return ApiResponse.error('Gagal memuat dashboard: $e');
    }
  }
}

final dashboardRepositoryProvider = Provider<DashboardRepository>((ref) {
  final client = ref.watch(dioClientProvider);
  return DashboardRepository(client);
});
