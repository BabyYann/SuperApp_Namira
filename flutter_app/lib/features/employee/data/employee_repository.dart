import 'package:dio/dio.dart';
import 'package:superapp_namira_flutter/core/api/dio_client.dart';
import 'package:superapp_namira_flutter/core/api/api_response.dart';
import 'package:superapp_namira_flutter/core/constants/app_constants.dart';

class EmployeeRepository {
  final DioClient _client;

  EmployeeRepository(this._client);

  Future<ApiResponse<Map<String, dynamic>>> getTodayAttendance() async {
    try {
      final response = await _client.get(ApiEndpoints.attendanceToday);
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(
        e.response?.data?['message'] ?? 'Gagal memuat absensi hari ini',
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
    int? locationId,
  }) async {
    try {
      final data = <String, dynamic>{'type': type};
      if (latitude != null) data['latitude'] = latitude;
      if (longitude != null) data['longitude'] = longitude;
      if (photo != null) data['photo'] = photo;
      if (note != null) data['note'] = note;
      if (locationId != null) data['attendance_location_id'] = locationId;

      final response = await _client.post(ApiEndpoints.attendanceCheckIn, data: data);
      return ApiResponse.success(response.data, message: response.data['message']);
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
      final data = <String, dynamic>{
        'latitude': latitude,
        'longitude': longitude,
      };
      if (photo != null) data['photo'] = photo;

      final response = await _client.post(ApiEndpoints.attendanceCheckOut, data: data);
      return ApiResponse.success(response.data, message: response.data['message']);
    } on DioException catch (e) {
      return ApiResponse.error(
        e.response?.data?['message'] ?? 'Gagal check-out',
        statusCode: e.response?.statusCode,
      );
    } catch (e) {
      return ApiResponse.error('Terjadi kesalahan: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getHistory({String? month, int page = 1}) async {
    try {
      final params = <String, dynamic>{'page': page};
      if (month != null) params['month'] = month;

      final response = await _client.get(ApiEndpoints.attendanceHistory, queryParameters: params);
      return ApiResponse.success(response.data);
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
      final response = await _client.get(ApiEndpoints.attendanceLocations);
      return ApiResponse.success(response.data['data'] ?? []);
    } on DioException catch (e) {
      return ApiResponse.error(
        e.response?.data?['message'] ?? 'Gagal memuat lokasi',
        statusCode: e.response?.statusCode,
      );
    } catch (e) {
      return ApiResponse.error('Terjadi kesalahan: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getStaffList({String? search, int page = 1}) async {
    try {
      final params = <String, dynamic>{'page': page};
      if (search != null && search.isNotEmpty) params['search'] = search;

      final response = await _client.get(ApiEndpoints.employeeStaff, queryParameters: params);
      return ApiResponse.success(response.data);
    } on DioException catch (e) {
      return ApiResponse.error(
        e.response?.data?['message'] ?? 'Gagal memuat data staff',
        statusCode: e.response?.statusCode,
      );
    } catch (e) {
      return ApiResponse.error('Terjadi kesalahan: $e');
    }
  }

  Future<ApiResponse<List<dynamic>>> getAttendanceRecap({
    String? startDate,
    String? endDate,
  }) async {
    try {
      final params = <String, dynamic>{};
      if (startDate != null) params['start_date'] = startDate;
      if (endDate != null) params['end_date'] = endDate;

      final response = await _client.get(ApiEndpoints.employeeAttendance, queryParameters: params);
      return ApiResponse.success(response.data['data'] ?? []);
    } on DioException catch (e) {
      return ApiResponse.error(
        e.response?.data?['message'] ?? 'Gagal memuat rekap absensi',
        statusCode: e.response?.statusCode,
      );
    } catch (e) {
      return ApiResponse.error('Terjadi kesalahan: $e');
    }
  }
}
