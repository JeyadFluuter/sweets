import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:sweet_store/app.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
  ]);
  runApp(const SweetStoreApp());
}
