import 'package:flutter_test/flutter_test.dart';
import 'package:get/get.dart';
import 'package:sweet_store/app.dart';
import 'package:sweet_store/bindings/app_binding.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('Sweet Store splash boots', (tester) async {
    Get.reset();
    AppBinding().dependencies();
    await tester.pumpWidget(const SweetStoreApp());
    expect(find.text('Sweet Store'), findsWidgets);
  });
}
