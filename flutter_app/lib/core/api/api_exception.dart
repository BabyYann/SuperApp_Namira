class ApiException implements Exception {
  final int? statusCode;
  final String message;
  final Map<String, dynamic>? errors;

  ApiException({
    this.statusCode,
    required this.message,
    this.errors,
  });

  @override
  String toString() => 'ApiException($statusCode): $message';

  factory ApiException.fromResponse(int statusCode, dynamic data) {
    if (data is Map<String, dynamic>) {
      final message = data['message'] ?? 'Terjadi kesalahan';
      final errors = data['errors'] as Map<String, dynamic>?;
      return ApiException(
        statusCode: statusCode,
        message: message.toString(),
        errors: errors,
      );
    }
      return ApiException(
        statusCode: statusCode,
        message: defaultMessage(statusCode),
      );
  }

  static String defaultMessage(int statusCode) {
    switch (statusCode) {
      case 400:
        return 'Permintaan tidak valid';
      case 401:
        return 'Sesi telah berakhir, silakan login kembali';
      case 403:
        return 'Anda tidak memiliki akses';
      case 404:
        return 'Data tidak ditemukan';
      case 422:
        return 'Data tidak valid';
      case 429:
        return 'Terlalu banyak permintaan, coba lagi nanti';
      case 500:
        return 'Kesalahan server, coba lagi nanti';
      case 502:
        return 'Server tidak tersedia';
      case 503:
        return 'Layanan sedang tidak tersedia';
      default:
        return 'Terjadi kesalahan ($statusCode)';
    }
  }

  String? getValidationErrors() {
    if (errors == null || errors!.isEmpty) return null;
    final messages = <String>[];
    errors!.forEach((field, msgs) {
      if (msgs is List) {
        for (final msg in msgs) {
          messages.add('$msg');
        }
      }
    });
    return messages.isNotEmpty ? messages.join('\n') : null;
  }
}
