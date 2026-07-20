import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/core/api/api_response.dart';
import 'package:superapp_namira_flutter/core/api/dio_client.dart';
import 'package:superapp_namira_flutter/core/constants/app_constants.dart';

class PublicRelationsRepository {
  final DioClient _client;
  PublicRelationsRepository(this._client);

  Future<ApiResponse<Map<String, dynamic>>> getNews({int page = 1}) async {
    try {
      final response = await _client.get(ApiEndpoints.prNews,
          queryParameters: {'page': page});
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat berita: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getNewsDetail(int id) async {
    try {
      final response = await _client.get('${ApiEndpoints.prNews}/$id');
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat detail berita: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getEvents({int page = 1}) async {
    try {
      final response = await _client.get(ApiEndpoints.prEvents,
          queryParameters: {'page': page});
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat agenda: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getEventDetail(int id) async {
    try {
      final response = await _client.get('${ApiEndpoints.prEvents}/$id');
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat detail agenda: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getDestinations({int page = 1}) async {
    try {
      final response = await _client.get(ApiEndpoints.prDestinations,
          queryParameters: {'page': page});
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat tujuan kunjungan: $e');
    }
  }

  Future<ApiResponse<Map<String, dynamic>>> getDestinationDetail(int id) async {
    try {
      final response = await _client.get('${ApiEndpoints.prDestinations}/$id');
      return ApiResponse.success(response.data as Map<String, dynamic>);
    } catch (e) {
      return ApiResponse.error('Gagal memuat detail tujuan: $e');
    }
  }
}

final publicRelationsRepositoryProvider =
    Provider<PublicRelationsRepository>((ref) {
  return PublicRelationsRepository(ref.watch(dioClientProvider));
});
