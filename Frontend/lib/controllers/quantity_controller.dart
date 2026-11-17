import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/widgets/quantity_edit_dialog.dart';

/// تحكم كمية مشترك (زائد/ناقص/كتابة) مع تلميح الحد عند التجاوز فقط
class QuantityController {
  QuantityController({
    int min = 1,
    int max = 99,
    int initial = 1,
  })  : min = min < 1 ? 1 : min,
        max = max < (min < 1 ? 1 : min) ? (min < 1 ? 1 : min) : max {
    qty.value = initial.clamp(this.min, this.max);
  }

  int min;
  int max;

  final qty = 1.obs;
  final limitHint = RxnString();

  void configure({required int min, required int max, int? initial}) {
    this.min = min < 1 ? 1 : min;
    this.max = max < this.min ? this.min : max;
    qty.value = (initial ?? qty.value).clamp(this.min, this.max);
    limitHint.value = null;
  }

  void clearHint() => limitHint.value = null;

  void minus() {
    final next = nextAfterMinus();
    if (next != null) qty.value = next;
  }

  void plus() {
    final next = nextAfterPlus();
    if (next != null) qty.value = next;
  }

  /// الكمية التالية بعد الناقص، أو null إذا وصل للحد الأدنى
  int? nextAfterMinus() {
    if (qty.value <= min) {
      limitHint.value = 'أقل كمية: $min';
      return null;
    }
    clearHint();
    return qty.value - 1;
  }

  /// الكمية التالية بعد الزائد، أو null إذا وصل للحد الأقصى
  int? nextAfterPlus() {
    if (qty.value >= max) {
      limitHint.value = 'أكثر كمية: $max';
      return null;
    }
    clearHint();
    return qty.value + 1;
  }

  void setQty(int value) {
    clearHint();
    qty.value = value.clamp(min, max);
  }

  Future<void> editManually(BuildContext context) async {
    final result = await showQuantityEditDialog(
      context,
      value: qty.value,
      min: min,
      max: max,
    );
    if (result == null) return;
    setQty(result);
  }

  /// يفتح الدايلوق ويرجع القيمة إن تم التأكيد (بدون فرض التزامن المحلي فقط)
  Future<int?> pickManually(BuildContext context) async {
    final result = await showQuantityEditDialog(
      context,
      value: qty.value,
      min: min,
      max: max,
    );
    if (result == null) return null;
    clearHint();
    return result.clamp(min, max);
  }
}
