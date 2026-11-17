class NotificationModel {
  const NotificationModel({
    required this.id,
    this.orderId,
    this.type,
    required this.title,
    required this.body,
    this.readAt,
    this.createdAt,
  });

  final int id;
  final int? orderId;
  final String? type;
  final String title;
  final String body;
  final String? readAt;
  final String? createdAt;

  bool get isUnread => readAt == null;

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    return NotificationModel(
      id: json['id'] as int,
      orderId: json['order_id'] as int?,
      type: json['type'] as String?,
      title: (json['title'] as String?) ?? '',
      body: (json['body'] as String?) ?? '',
      readAt: json['read_at'] as String?,
      createdAt: json['created_at'] as String?,
    );
  }
}
