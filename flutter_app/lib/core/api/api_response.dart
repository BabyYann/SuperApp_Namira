class ApiResponse<T> {
  final bool success;
  final T? data;
  final String? message;
  final int? statusCode;

  ApiResponse({
    required this.success,
    this.data,
    this.message,
    this.statusCode,
  });

  factory ApiResponse.success(T data, {String? message}) {
    return ApiResponse(
      success: true,
      data: data,
      message: message,
    );
  }

  factory ApiResponse.error(String message, {int? statusCode}) {
    return ApiResponse(
      success: false,
      message: message,
      statusCode: statusCode,
    );
  }
}

class PaginatedData<T> {
  final List<T> items;
  final int currentPage;
  final int lastPage;
  final int total;
  final int perPage;

  PaginatedData({
    required this.items,
    required this.currentPage,
    required this.lastPage,
    required this.total,
    required this.perPage,
  });

  bool get hasNextPage => currentPage < lastPage;
  bool get hasPrevPage => currentPage > 1;

  factory PaginatedData.fromJson(
    Map<String, dynamic> json,
    T Function(dynamic) fromJsonT,
  ) {
    final data = json['data'] as List<dynamic>? ?? [];
    return PaginatedData(
      items: data.map((e) => fromJsonT(e)).toList(),
      currentPage: json['current_page'] ?? 1,
      lastPage: json['last_page'] ?? 1,
      total: json['total'] ?? 0,
      perPage: json['per_page'] ?? 20,
    );
  }
}
