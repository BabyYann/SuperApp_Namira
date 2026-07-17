import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/core/api/api_response.dart';
import 'package:superapp_namira_flutter/core/api/dio_client.dart';

class AttendanceRepository {
  final DioClient _client;

  AttendanceRepository(this._client);

  Future<ApiResponse<Map<String, dynamic>>> getToday() async {
    try {
      final response = await _client.get('/attendance/today');
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      return ApiResponse.error(
        e.response?.data?['message'] ?? 'Gagal memuat data hari ini',
        statusCode: e.response?.statusCode,
      );
    } catch (e) {
      return ApiResponse.error('Terjadi kesalahan: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> checkIn({
    required String type,
    double? latitude,
    double? longitude,
    String? photo,
    String? note,
  }) async {
    try {
      final response = await _client.post('/attendance/check-in', data: {
        'type': type,
        'latitude': ?latitude,
        'longitude': ?longitude,
        'photo': ?photo,
        'note': ?note,
      });
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      return ApiResponse.error(
        e.response?.data?['message'] ?? 'Gagal check-in',
        statusCode: e.response?.statusCode,
      );
    } catch (e) {
      return ApiResponse.error('Terjadi kesalahan: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> checkOut({
    required double latitude,
    required double longitude,
    String? photo,
  }) async {
    try {
      final response = await _client.post('/attendance/check-out', data: {
        'latitude': latitude,
        'longitude': longitude,
        'photo': ?photo,
      });
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      return ApiResponse.error(
        e.response?.data?['message'] ?? 'Gagal check-out',
        statusCode: e.response?.statusCode,
      );
    } catch (e) {
      return ApiResponse.error('Terjadi kesalahan: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getHistory({String? month}) async {
    try {
      final response = await _client.get(
        '/attendance/history',
        queryParameters: {'month': ?month},
      );
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } on DioException catch (e) {
      return ApiResponse.error(
        e.response?.data?['message'] ?? 'Gagal memuat riwayat',
        statusCode: e.response?.statusCode,
      );
    } catch (e) {
      return ApiResponse.error('Terjadi kesalahan: $e');
    }
  }

  Future<ApiResponse<List<dynamic>>> getLocations() async {
    try {
      final response = await _client.get('/attendance/locations');
      final data = response.data as Map<String, dynamic>;
      return ApiResponse.success(data['data'] ?? []);
    } on DioException catch (e) {
      return ApiResponse.error(
        e.response?.data?['message'] ?? 'Gagal memuat lokasi',
        statusCode: e.response?.statusCode,
      );
    } catch (e) {
      return ApiResponse.error('Terjadi kesalahan: $e');
    }
  }
}

final attendanceRepositoryProvider = Provider<AttendanceRepository>((ref) {
  final client = ref.watch(dioClientProvider);
  return AttendanceRepository(client);
});
