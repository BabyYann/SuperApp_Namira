import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/core/api/api_response.dart';
import 'package:superapp_namira_flutter/core/api/dio_client.dart';
import 'package:superapp_namira_flutter/core/constants/app_constants.dart';

class NotificationRepository {
  final DioClient _client;
  NotificationRepository(this._client);

  Future<ApiResponse<Map<String, dynamic>>> getNotifications({int page = 1}) async {
    try {
      final response = await _client.get(ApiEndpoints.notifications,
          queryParameters: {'page': page});
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat notifikasi: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> markRead(int id) async {
    try {
      final response =
          await _client.post('${ApiEndpoints.notifications}/$id/read');
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal menandai notifikasi: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> markAllRead() async {
    try {
      final response =
          await _client.post('${ApiEndpoints.notifications}/read-all');
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal menandai semua: $e');
    }
  }
}

final notificationRepositoryProvider = Provider<NotificationRepository>((ref) {
  return NotificationRepository(ref.watch(dioClientProvider));
});
