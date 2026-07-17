import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/core/api/api_response.dart';
import 'package:superapp_namira_flutter/core/api/dio_client.dart';
import 'package:superapp_namira_flutter/core/constants/app_constants.dart';
import 'package:superapp_namira_flutter/core/utils/storage_utils.dart';

class AuthRepository {
  final DioClient _client;

  AuthRepository(this._client);

  Future<ApiResponse<Map<String, dynamic>>> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await _client.post(
        ApiEndpoints.login,
        data: {
          'email': email,
          'password': password,
        },
      );

      final data = response.data as Map<String, dynamic>;
      final token = data['token'] as String?;
      final user = data['user'] as Map<String, dynamic>?;

      if (token != null && user != null) {
        await StorageUtils.saveToken(token);
        await StorageUtils.saveUser(user);
        return ApiResponse.success(data);
      }

      return ApiResponse.error('Data login tidak lengkap');
    } on DioException catch (e) {
      return ApiResponse.error(
        e.response?.data?['message'] ?? 'Gagal login',
        statusCode: e.response?.statusCode,
      );
    } catch (e) {
      return ApiResponse.error('Terjadi kesalahan: $e');
    }
  }

  Future<ApiResponse<void>> logout() async {
    try {
      await _client.post(ApiEndpoints.logout);
      await StorageUtils.clearAuth();
      return ApiResponse.success(null, message: 'Logout berhasil');
    } catch (e) {
      await StorageUtils.clearAuth();
      return ApiResponse.success(null, message: 'Logout berhasil');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getProfile() async {
    try {
      final response = await _client.get(ApiEndpoints.user);
      final user = response.data as Map<String, dynamic>;
      await StorageUtils.saveUser(user);
      return ApiResponse.success(user);
    } on DioException catch (e) {
      return ApiResponse.error(
        e.response?.data?['message'] ?? 'Gagal memuat profil',
        statusCode: e.response?.statusCode,
      );
    } catch (e) {
      return ApiResponse.error('Terjadi kesalahan: $e');
    }
  }

  Future<ApiResponse<List<dynamic>>> getUnits() async {
    try {
      final response = await _client.get(ApiEndpoints.units);
      final data = response.data as Map<String, dynamic>;
      return ApiResponse.success(data['data'] ?? []);
    } on DioException catch (e) {
      return ApiResponse.error(
        e.response?.data?['message'] ?? 'Gagal memuat unit',
        statusCode: e.response?.statusCode,
      );
    } catch (e) {
      return ApiResponse.error('Terjadi kesalahan: $e');
    }
  }

  Future<ApiResponse<void>> switchUnit(int unitId) async {
    try {
      await _client.post(
        ApiEndpoints.switchUnit,
        data: {'unit_id': unitId},
      );
      await StorageUtils.saveActiveUnit(unitId);
      return ApiResponse.success(null, message: 'Unit berhasil diubah');
    } on DioException catch (e) {
      return ApiResponse.error(
        e.response?.data?['message'] ?? 'Gagal mengubah unit',
        statusCode: e.response?.statusCode,
      );
    } catch (e) {
      return ApiResponse.error('Terjadi kesalahan: $e');
    }
  }
}

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  final client = ref.watch(dioClientProvider);
  return AuthRepository(client);
});
