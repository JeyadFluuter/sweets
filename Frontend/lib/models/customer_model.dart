class CustomerModel {
  const CustomerModel({
    required this.id,
    required this.name,
    this.phone,
    this.email,
    required this.isGuest,
    this.createdAt,
  });

  final int id;
  final String name;
  final String? phone;
  final String? email;
  final bool isGuest;
  final String? createdAt;

  factory CustomerModel.fromJson(Map<String, dynamic> json) {
    return CustomerModel(
      id: json['id'] as int,
      name: (json['name'] as String?) ?? '',
      phone: json['phone'] as String?,
      email: json['email'] as String?,
      isGuest: json['is_guest'] == true,
      createdAt: json['created_at'] as String?,
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'phone': phone,
        'email': email,
        'is_guest': isGuest,
        'created_at': createdAt,
      };
}
