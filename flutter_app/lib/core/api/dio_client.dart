import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:superapp_namira_flutter/config/env.dart';
import 'package:superapp_namira_flutter/core/api/api_exception.dart';
import 'package:superapp_namira_flutter/core/constants/app_constants.dart';
import 'package:superapp_namira_flutter/core/utils/storage_utils.dart';

final dioClientProvider = Provider<DioClient>((ref) {
  return DioClient();
});

class DioClient {
  late final Dio _dio;

  DioClient() {
    _dio = Dio(
      BaseOptions(
        baseUrl: Env.apiBaseUrl,
        connectTimeout: AppConstants.apiTimeout,
        receiveTimeout: AppConstants.apiTimeout,
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      ),
    );
    _dio.interceptors.addAll([
      _AuthInterceptor(),
      _LogInterceptor(),
    ]);
  }

  Dio get dio => _dio;

  Future<Response> get(String path, {Map<String, dynamic>? queryParameters}) async {
    try {
      return await _dio.get(path, queryParameters: queryParameters);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<Response> post(String path, {dynamic data}) async {
    try {
      return await _dio.post(path, data: data);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<Response> put(String path, {dynamic data}) async {
    try {
      return await _dio.put(path, data: data);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<Response> patch(String path, {dynamic data}) async {
    try {
      return await _dio.patch(path, data: data);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<Response> delete(String path) async {
    try {
      return await _dio.delete(path);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<Response> upload(String path, FormData data) async {
    try {
      return await _dio.post(
        path,
        data: data,
        options: Options(
          contentType: 'multipart/form-data',
        ),
      );
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  ApiException _handleError(DioException e) {
    switch (e.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return ApiException(
          message: 'Koneksi timeout, periksa jaringan Anda',
        );
      case DioExceptionType.connectionError:
        return ApiException(
          message: 'Tidak dapat terhubung ke server',
        );
      case DioExceptionType.badResponse:
        final statusCode = e.response?.statusCode;
        final data = e.response?.data;

        if (statusCode == 401) {
          StorageUtils.clearAuth();
        }

        if (statusCode != null && data != null) {
          return ApiException.fromResponse(statusCode, data);
        }
        return ApiException(
          statusCode: statusCode,
          message: ApiException.defaultMessage(statusCode ?? 0),
        );
      case DioExceptionType.cancel:
        return ApiException(message: 'Permintaan dibatalkan');
      default:
        return ApiException(message: 'Terjadi kesalahan tidak terduga');
    }
  }
}

class _AuthInterceptor extends Interceptor {
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await StorageUtils.getToken();
    if (token != null && token.isNotEmpty) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    final activeUnit = await StorageUtils.getActiveUnit();
    if (activeUnit != null) {
      options.headers['X-Active-Unit'] = activeUnit.toString();
    }
    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    if (err.response?.statusCode == 401) {
      await StorageUtils.clearAuth();
    }
    handler.next(err);
  }
}

class _LogInterceptor extends Interceptor {
  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    handler.next(options);
  }

  @override
  void onResponse(Response response, ResponseInterceptorHandler handler) {
    handler.next(response);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    handler.next(err);
  }
}
