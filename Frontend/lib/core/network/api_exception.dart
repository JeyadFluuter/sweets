class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.errors});

  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  @override
  String toString() => message;

  String get firstError {
    if (errors == null || errors!.isEmpty) return message;
    final first = errors!.values.first;
    if (first is List && first.isNotEmpty) return first.first.toString();
    return first.toString();
  }
}
