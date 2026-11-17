import 'package:sweet_store/models/customer_model.dart';

class AuthSessionModel {
  const AuthSessionModel({
    required this.token,
    required this.customer,
  });

  final String token;
  final CustomerModel customer;

  factory AuthSessionModel.fromJson(Map<String, dynamic> json) {
    return AuthSessionModel(
      token: json['token'] as String,
      customer: CustomerModel.fromJson(
        Map<String, dynamic>.from(json['customer'] as Map),
      ),
    );
  }
}
