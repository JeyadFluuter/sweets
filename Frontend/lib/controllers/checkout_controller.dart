import 'package:get/get.dart';
import 'package:sweet_store/controllers/cart_controller.dart';
import 'package:sweet_store/core/network/api_exception.dart';
import 'package:sweet_store/models/order_model.dart';
import 'package:sweet_store/services/fulfillment_service.dart';
import 'package:sweet_store/services/order_service.dart';

class CheckoutController extends GetxController {
  CheckoutController(this._fulfillment, this._orders, this._cart);

  final FulfillmentService _fulfillment;
  final OrderService _orders;
  final CartController _cart;

  /// 0 نوع الاستلام · 1 الموعد · 2 المراجعة · 3 تم الإرسال
  final step = 0.obs;

  final deliveryType = 'pickup'.obs;
  final addressText = ''.obs;
  final recipientIsSelf = true.obs;
  final recipientName = ''.obs;
  final recipientPhone = ''.obs;
  final customerNote = ''.obs;

  final dates = <String>[].obs;
  final selectedDate = RxnString();
  final slots = <FulfillmentSlot>[].obs;
  final selectedSlot = Rxn<FulfillmentSlot>();
  final earliestAt = RxnString();

  final isLoadingDates = false.obs;
  final isLoadingSlots = false.obs;
  final isSubmitting = false.obs;
  final errorMessage = RxnString();

  final placedOrder = Rxn<OrderModel>();

  @override
  void onInit() {
    super.onInit();
    loadDates();
  }

  void setDeliveryType(String type) {
    if (deliveryType.value == type) return;
    deliveryType.value = type;
    selectedDate.value = null;
    selectedSlot.value = null;
    slots.clear();
    loadDates();
  }

  Future<void> loadDates() async {
    isLoadingDates.value = true;
    errorMessage.value = null;
    try {
      final result = await _fulfillment.availableDates(deliveryType.value);
      dates.assignAll(result.dates);
      earliestAt.value = result.earliestAt;
      if (dates.isEmpty) {
        selectedDate.value = null;
        selectedSlot.value = null;
        slots.clear();
      } else if (selectedDate.value == null ||
          !dates.contains(selectedDate.value)) {
        // أول تاريخ متاح مباشرة لتسهيل الاستخدام
        await selectDate(dates.first);
      }
    } on ApiException catch (e) {
      errorMessage.value = e.firstError;
      dates.clear();
    } catch (_) {
      errorMessage.value = 'تعذّر تحميل التواريخ المتاحة';
      dates.clear();
    } finally {
      isLoadingDates.value = false;
    }
  }

  Future<void> selectDate(String date) async {
    if (selectedDate.value == date && slots.isNotEmpty) return;
    selectedDate.value = date;
    selectedSlot.value = null;
    await loadSlots();
  }

  Future<void> loadSlots() async {
    final date = selectedDate.value;
    if (date == null) return;
    isLoadingSlots.value = true;
    try {
      final list = await _fulfillment.slots(
        deliveryType: deliveryType.value,
        date: date,
      );
      slots.assignAll(list);
      // اختيار أول فترة تلقائياً إن وُجدت
      if (list.isNotEmpty) {
        selectedSlot.value = list.first;
      }
    } on ApiException catch (e) {
      Get.snackbar('المواعيد', e.firstError, snackPosition: SnackPosition.BOTTOM);
      slots.clear();
    } catch (_) {
      Get.snackbar(
        'المواعيد',
        'تعذّر تحميل الفترات',
        snackPosition: SnackPosition.BOTTOM,
      );
      slots.clear();
    } finally {
      isLoadingSlots.value = false;
    }
  }

  void selectSlot(FulfillmentSlot slot) {
    selectedSlot.value = slot;
  }

  bool validateDeliveryStep() {
    if (deliveryType.value == 'delivery' &&
        addressText.value.trim().isEmpty) {
      Get.snackbar(
        'التوصيل',
        'أدخل عنوان التوصيل',
        snackPosition: SnackPosition.BOTTOM,
      );
      return false;
    }
    if (!recipientIsSelf.value) {
      if (recipientName.value.trim().isEmpty ||
          recipientPhone.value.trim().isEmpty) {
        Get.snackbar(
          'المستلم',
          'أدخل اسم ورقم المستلم',
          snackPosition: SnackPosition.BOTTOM,
        );
        return false;
      }
    }
    return true;
  }

  bool validateScheduleStep() {
    if (selectedDate.value == null || selectedSlot.value == null) {
      Get.snackbar(
        'الموعد',
        'اختر التاريخ والفترة',
        snackPosition: SnackPosition.BOTTOM,
      );
      return false;
    }
    return true;
  }

  void next() {
    if (step.value == 0) {
      if (!validateDeliveryStep()) return;
      step.value = 1;
      if (dates.isEmpty) loadDates();
      return;
    }
    if (step.value == 1) {
      if (!validateScheduleStep()) return;
      step.value = 2;
      return;
    }
  }

  void back() {
    if (step.value > 0 && step.value < 3) {
      step.value--;
    } else {
      Get.back();
    }
  }

  Future<bool> submitOrder() async {
    if (!validateDeliveryStep() || !validateScheduleStep()) return false;

    isSubmitting.value = true;
    try {
      final order = await _orders.placeOrder(
        deliveryType: deliveryType.value,
        fulfillmentDate: selectedDate.value!,
        fulfillmentTime: selectedSlot.value!.timeForOrder,
        addressText: deliveryType.value == 'delivery'
            ? addressText.value.trim()
            : null,
        recipientIsSelf: recipientIsSelf.value,
        recipientName:
            recipientIsSelf.value ? null : recipientName.value.trim(),
        recipientPhone:
            recipientIsSelf.value ? null : recipientPhone.value.trim(),
        customerNote: customerNote.value.trim().isEmpty
            ? null
            : customerNote.value.trim(),
      );
      placedOrder.value = order;
      step.value = 3;
      await _cart.refreshCart();
      isSubmitting.value = false;
      return true;
    } on ApiException catch (e) {
      isSubmitting.value = false;
      Get.snackbar('الطلب', e.firstError, snackPosition: SnackPosition.BOTTOM);
      return false;
    } catch (_) {
      isSubmitting.value = false;
      Get.snackbar(
        'الطلب',
        'تعذّر إرسال الطلب',
        snackPosition: SnackPosition.BOTTOM,
      );
      return false;
    }
  }
}
